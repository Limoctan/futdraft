<?php

namespace App\Http\Controllers;

use App\Enums\Currency;
use App\Enums\RoomStatus;
use App\Models\Player;
use App\Models\Room;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class RoomController extends Controller
{
    public function index(): Response
    {
        $user = Auth::user();
        $rooms = Room::where('user_id', $user->id)
            ->orWhereHas('users', function ($query) use ($user) {
                $query->where('users.id', $user->id);
            })
            ->with('creator')
            ->get();

        return Inertia::render('dashboard', ['rooms' => $rooms]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'date' => ['required', 'date', 'after_or_equal:today'],
            'team_size' => ['required', 'in:5,7,11'],
            'num_teams' => ['required', 'integer', 'min:2', 'max:10'],
            'price_in_cents' => ['required', 'integer', 'min:0'],
            'currency' => ['required', 'string', Rule::in(Currency::cases())],
        ]);

        $room = Room::create([
            ...$validated,
            'user_id' => Auth::id(),
            'status' => RoomStatus::Waiting,
        ]);

        $room->users()->attach(Auth::id(), ['is_admin' => true]);

        return redirect()->route('rooms.show', $room);
    }

    public function show(Room $room): Response
    {
        $this->authorizeRoomAccess($room);

        $room->load([
            'creator',
            'users',
            'players' => fn ($query) => $query->orderBy('id')->with('payment'),
            'teams' => fn ($query) => $query->orderBy('pick_order')->with(['captain', 'players']),
            'draftPicks' => fn ($query) => $query->orderBy('pick_number')->with(['player', 'team.captain']),
        ]);

        return Inertia::render('rooms/show', ['room' => $room]);
    }

    public function update(Request $request, Room $room): RedirectResponse
    {
        $this->authorizeAdmin($room);

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'date' => ['sometimes', 'date', 'after_or_equal:today'],
            'team_size' => ['sometimes', 'in:5,7,11'],
            'num_teams' => ['sometimes', 'integer', 'min:2', 'max:10'],
            'price_in_cents' => ['sometimes', 'integer', 'min:0'],
            'currency' => ['sometimes', 'string', Rule::in(Currency::cases())],
        ]);

        $room->update($validated);

        return redirect()->route('rooms.show', $room);
    }

    public function join(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'invite_code' => ['required', 'string', 'exists:rooms,invite_code'],
        ]);

        $room = Room::where('invite_code', $validated['invite_code'])->first();

        if (! $room->users()->where('users.id', Auth::id())->exists()) {
            $room->users()->attach(Auth::id(), ['is_admin' => false]);
        }

        return redirect()->route('rooms.show', $room);
    }

    public function assignAdmin(Request $request, Room $room): RedirectResponse
    {
        $this->authorizeAdmin($room);

        $validated = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
        ]);

        $room->users()->updateExistingPivot($validated['user_id'], ['is_admin' => true]);

        return redirect()->route('rooms.show', $room);
    }

    public function revokeAdmin(Request $request, Room $room): RedirectResponse
    {
        $this->authorizeAdmin($room);

        $validated = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
        ]);

        $room->users()->updateExistingPivot($validated['user_id'], ['is_admin' => false]);

        return redirect()->route('rooms.show', $room);
    }

    public function removeUser(Request $request, Room $room): RedirectResponse
    {
        $this->authorizeAdmin($room);

        $validated = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
        ]);

        $room->users()->detach($validated['user_id']);

        return redirect()->route('rooms.show', $room);
    }

    public function assignCaptains(Request $request, Room $room): RedirectResponse
    {
        $this->authorizeAdmin($room);

        if (in_array($room->status, [RoomStatus::Drafting, RoomStatus::Completed], true)) {
            abort(403, 'Captains cannot be changed in this room state.');
        }

        $validated = $request->validate([
            'captains' => ['required', 'array', "size:{$room->num_teams}"],
            'captains.*.user_id' => ['required', 'integer', 'exists:users,id'],
            'captains.*.player_id' => ['required', 'integer', 'exists:players,id'],
        ]);

        $this->validateCaptainAssignments($room, $validated['captains']);

        DB::transaction(function () use ($room, $validated) {
            foreach ($validated['captains'] as $index => $assignment) {
                $team = $room->teams()->updateOrCreate(
                    ['pick_order' => $index + 1],
                    ['captain_user_id' => $assignment['user_id']]
                );

                $this->assignSelfPick($room, $team, (int) $assignment['player_id'], (int) $assignment['user_id']);
            }
        });

        return redirect()->route('rooms.show', $room);
    }

    public function startDraft(Request $request, Room $room): RedirectResponse
    {
        $this->authorizeAdmin($room);

        if (! $room->isFull()) {
            throw ValidationException::withMessages([
                'room' => 'The room must be full before the draft can start.',
            ]);
        }

        if (in_array($room->status, [RoomStatus::Drafting, RoomStatus::Completed], true)) {
            throw ValidationException::withMessages([
                'room' => 'The draft has already started.',
            ]);
        }

        if (! $room->hasAllCaptains()) {
            throw ValidationException::withMessages([
                'captains' => 'All captains must be assigned before the draft can start.',
            ]);
        }

        if ($room->teams()->doesntHave('players')->exists()) {
            throw ValidationException::withMessages([
                'captains' => 'Every captain must have a self-pick before the draft can start.',
            ]);
        }

        DB::transaction(function () use ($room) {
            $draftOrder = $room->teams()
                ->get()
                ->shuffle()
                ->pluck('id')
                ->values()
                ->all();

            $this->recordSelfPicks($room, $draftOrder);

            $room->update([
                'status' => RoomStatus::Drafting,
                'draft_started_at' => now(),
                'draft_order' => $draftOrder,
                'current_pick_index' => 0,
            ]);
        });

        return redirect()->route('rooms.show', $room);
    }

    public function cancelDraft(Request $request, Room $room): RedirectResponse
    {
        $this->authorizeAdmin($room);

        if ($room->status !== RoomStatus::Drafting) {
            throw ValidationException::withMessages([
                'room' => 'There is no active draft to cancel.',
            ]);
        }

        DB::transaction(function () use ($room) {
            $room->draftPicks()->delete();

            foreach ($room->teams as $team) {
                $team->players()->wherePivot('pick_number', '>', 1)->detach();
            }

            $room->update([
                'status' => RoomStatus::Waiting,
                'draft_order' => null,
                'current_pick_index' => 0,
                'draft_started_at' => null,
            ]);
        });

        return redirect()->route('rooms.show', $room);
    }

    /**
     * @param  array<int, int>  $draftOrder
     */
    private function recordSelfPicks(Room $room, array $draftOrder): void
    {
        $pickNumber = 1;

        foreach ($draftOrder as $teamId) {
            $team = $room->teams()->findOrFail($teamId);
            $selfPick = $team->players()->wherePivot('pick_number', 1)->first();

            if ($selfPick === null) {
                continue;
            }

            $room->draftPicks()->create([
                'team_id' => $team->id,
                'player_id' => $selfPick->id,
                'pick_number' => $pickNumber,
                'picked_by_user_id' => $team->captain_user_id,
                'auto_picked' => true,
            ]);

            $pickNumber++;
        }
    }

    /**
     * @param  array<int, array{user_id: int|string, player_id: int|string}>  $captains
     */
    private function validateCaptainAssignments(Room $room, array $captains): void
    {
        $userIds = array_map(fn ($assignment) => (int) $assignment['user_id'], $captains);
        $playerIds = array_map(fn ($assignment) => (int) $assignment['player_id'], $captains);

        if (count($userIds) !== count(array_unique($userIds))) {
            throw ValidationException::withMessages([
                'captains' => 'Each captain can only be assigned once.',
            ]);
        }

        if (count($playerIds) !== count(array_unique($playerIds))) {
            throw ValidationException::withMessages([
                'captains' => 'Each self-pick player can only be assigned once.',
            ]);
        }

        $memberIds = $room->users()->pluck('users.id')->map(fn ($id) => (int) $id)->all();

        foreach ($userIds as $index => $userId) {
            if (! in_array($userId, $memberIds, true)) {
                throw ValidationException::withMessages([
                    "captains.{$index}.user_id" => 'The selected user is not a member of this room.',
                ]);
            }
        }

        $roomPlayerIds = $room->players()->pluck('id')->map(fn ($id) => (int) $id)->all();

        foreach ($playerIds as $index => $playerId) {
            if (! in_array($playerId, $roomPlayerIds, true)) {
                throw ValidationException::withMessages([
                    "captains.{$index}.player_id" => 'The selected player does not belong to this room.',
                ]);
            }
        }
    }

    private function assignSelfPick(Room $room, Team $team, int $playerId, int $captainUserId): void
    {
        $player = Player::where('room_id', $room->id)->findOrFail($playerId);

        $currentSelfPick = $team->players()->wherePivot('pick_number', 1)->first();

        if ($currentSelfPick && $currentSelfPick->id !== $player->id) {
            $team->players()->detach($currentSelfPick->id);
            $currentSelfPick->update([
                'is_captain' => false,
                'captain_user_id' => null,
            ]);
        }

        $player->teams()->detach();
        $team->players()->attach($player->id, ['pick_number' => 1]);
        $player->update([
            'is_captain' => true,
            'captain_user_id' => $captainUserId,
        ]);
    }

    private function authorizeRoomAccess(Room $room): void
    {
        if (! $room->isMember(Auth::user())) {
            abort(403, 'You are not a member of this room.');
        }
    }

    private function authorizeAdmin(Room $room): void
    {
        if (! $room->isAdmin(Auth::user())) {
            abort(403, 'You are not an admin of this room.');
        }
    }
}
