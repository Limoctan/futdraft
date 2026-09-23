<?php

namespace App\Http\Controllers;

use App\Enums\RoomStatus;
use App\Models\Player;
use App\Models\Room;
use App\Models\Team;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DraftController extends Controller
{
    public function pick(Request $request, Room $room): RedirectResponse
    {
        $this->authorizeMember($room);
        $this->ensureDrafting($room);

        if ($room->isPickTimerExpired()) {
            throw ValidationException::withMessages([
                'draft' => 'The pick timer has expired. An auto-pick is required.',
            ]);
        }

        $team = $this->currentTeamOrFail($room);
        $user = Auth::user();

        if ($team->captain_user_id !== $user->id) {
            throw ValidationException::withMessages([
                'draft' => 'It is not your turn to pick.',
            ]);
        }

        $validated = $request->validate([
            'player_id' => ['required', 'integer', 'exists:players,id'],
        ]);

        $player = $room->players()->whereKey($validated['player_id'])->first();

        if ($player === null) {
            throw ValidationException::withMessages([
                'player_id' => 'That player is not available to draft.',
            ]);
        }

        $availableIds = $room->availableDraftPlayers()->pluck('id');

        if (! $availableIds->contains($player->id)) {
            throw ValidationException::withMessages([
                'player_id' => 'That player is not available to draft.',
            ]);
        }

        return $this->recordPick($room, $team, $player, autoPicked: false, pickedByUserId: $user->id);
    }

    public function autoPick(Request $request, Room $room): RedirectResponse
    {
        $this->authorizeMember($room);
        $this->ensureDrafting($room);

        if (! $room->isPickTimerExpired()) {
            throw ValidationException::withMessages([
                'draft' => 'The pick timer has not expired yet.',
            ]);
        }

        $team = $this->currentTeamOrFail($room);

        $player = $room->availableDraftPlayers()
            ->sortBy([['rating', 'desc'], ['id', 'asc']])
            ->first();

        if ($player === null) {
            throw ValidationException::withMessages([
                'draft' => 'No players are available to draft.',
            ]);
        }

        return $this->recordPick(
            $room,
            $team,
            $player,
            autoPicked: true,
            pickedByUserId: $team->captain_user_id,
        );
    }

    public function current(Request $request, Room $room): JsonResponse
    {
        $this->authorizeMember($room);

        $picks = $room->draftPicks()
            ->orderBy('pick_number')
            ->with(['player', 'team.captain'])
            ->get();

        return response()->json([
            'status' => $room->status->value,
            'draft_order' => $room->draft_order,
            'current_pick_index' => $room->current_pick_index,
            'current_team_id' => $room->currentDraftTeamId(),
            'current_captain_user_id' => $this->currentTeam($room)?->captain_user_id,
            'pick_deadline' => $room->pickDeadline()?->toIso8601String(),
            'timer_expired' => $room->isPickTimerExpired(),
            'is_complete' => $room->isDraftComplete(),
            'available_players' => $room->availableDraftPlayers(),
            'picks_count' => $picks->count(),
            'picks' => $picks,
        ]);
    }

    private function ensureDrafting(Room $room): void
    {
        if ($room->status !== RoomStatus::Drafting) {
            throw ValidationException::withMessages([
                'room' => 'The draft is not in progress.',
            ]);
        }
    }

    private function currentTeam(Room $room): ?Team
    {
        $teamId = $room->currentDraftTeamId();

        if ($teamId === null) {
            return null;
        }

        return $room->teams()->whereKey($teamId)->first();
    }

    private function currentTeamOrFail(Room $room): Team
    {
        $team = $this->currentTeam($room);

        if ($team === null) {
            throw ValidationException::withMessages([
                'draft' => 'Draft order is not set.',
            ]);
        }

        return $team;
    }

    private function recordPick(Room $room, Team $team, Player $player, bool $autoPicked, int $pickedByUserId): RedirectResponse
    {
        DB::transaction(function () use ($room, $team, $player, $autoPicked, $pickedByUserId) {
            $nextTeamPickNumber = $team->players()->count() + 1;
            $team->players()->attach($player->id, ['pick_number' => $nextTeamPickNumber]);

            $room->draftPicks()->create([
                'team_id' => $team->id,
                'player_id' => $player->id,
                'pick_number' => $room->draftPicks()->max('pick_number') + 1,
                'picked_by_user_id' => $pickedByUserId,
                'auto_picked' => $autoPicked,
            ]);

            $room->increment('current_pick_index');
            $room->refresh();

            if ($room->isDraftComplete()) {
                $room->update(['status' => RoomStatus::Completed]);
            }
        });

        return redirect()->route('rooms.show', $room);
    }

    private function authorizeMember(Room $room): void
    {
        $user = Auth::user();

        if (! $user || ! $room->isMember($user)) {
            abort(403, 'You are not a member of this room.');
        }
    }
}
