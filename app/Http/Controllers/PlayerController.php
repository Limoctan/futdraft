<?php

namespace App\Http\Controllers;

use App\Enums\RoomStatus;
use App\Models\Player;
use App\Models\Room;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PlayerController extends Controller
{
    public function store(Request $request, Room $room): RedirectResponse
    {
        $this->authorizeRoomMember($room);
        $this->authorizeModifiable($room);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
        ]);

        $room->players()->create([
            'name' => $validated['name'],
            'rating' => $validated['rating'],
            'is_captain' => false,
            'captain_user_id' => null,
        ]);

        $room->syncCapacityStatus();

        return redirect()->route('rooms.show', $room);
    }

    public function update(Request $request, Room $room, Player $player): RedirectResponse
    {
        $this->authorizeRoomMember($room);
        $this->authorizeModifiable($room);
        $this->authorizePlayerInRoom($room, $player);

        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'rating' => ['sometimes', 'required', 'integer', 'min:1', 'max:5'],
        ]);

        $player->update($validated);

        return redirect()->route('rooms.show', $room);
    }

    public function destroy(Room $room, Player $player): RedirectResponse
    {
        $this->authorizeRoomMember($room);
        $this->authorizeModifiable($room);
        $this->authorizePlayerInRoom($room, $player);

        $player->delete();

        $room->syncCapacityStatus();

        return redirect()->route('rooms.show', $room);
    }

    private function authorizeRoomMember(Room $room): void
    {
        $user = Auth::user();

        if (! $user || ! $room->isMember($user)) {
            abort(403, 'You are not a member of this room.');
        }
    }

    private function authorizeModifiable(Room $room): void
    {
        if ($room->status === RoomStatus::Drafting || $room->status === RoomStatus::Completed) {
            abort(403, 'Cannot modify players when draft is in progress or completed.');
        }
    }

    private function authorizePlayerInRoom(Room $room, Player $player): void
    {
        if ($player->room_id !== $room->id) {
            abort(404);
        }
    }
}
