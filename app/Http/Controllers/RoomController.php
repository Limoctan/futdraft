<?php

namespace App\Http\Controllers;

use App\Enums\Currency;
use App\Enums\RoomStatus;
use App\Models\Room;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
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

        $room->load(['creator', 'users']);

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
