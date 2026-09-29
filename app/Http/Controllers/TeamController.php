<?php

namespace App\Http\Controllers;

use App\Enums\RoomStatus;
use App\Enums\TeamColor;
use App\Models\Room;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class TeamController extends Controller
{
    public function update(Request $request, Room $room, Team $team): RedirectResponse
    {
        $this->authorizeTeamInRoom($room, $team);
        $this->authorizeTeamUpdate($room, $team);
        $this->authorizeDraftCompleted($room);

        $validated = $request->validate([
            'color' => ['sometimes', 'string', Rule::enum(TeamColor::class)],
            'name' => ['sometimes', 'nullable', 'string', 'max:255'],
        ]);

        if (isset($validated['color']) && $this->colorTakenByAnotherTeam($room, $team, $validated['color'])) {
            throw ValidationException::withMessages([
                'color' => 'That color is already taken by another team in this room.',
            ]);
        }

        $team->update($validated);

        return redirect()->route('rooms.show', $room);
    }

    private function authorizeTeamInRoom(Room $room, Team $team): void
    {
        if ($team->room_id !== $room->id) {
            abort(404);
        }
    }

    private function authorizeTeamUpdate(Room $room, Team $team): void
    {
        $user = Auth::user();

        if ($team->captain_user_id !== $user->id && ! $room->isAdmin($user)) {
            abort(403, 'Only the team captain or a room admin can update this team.');
        }
    }

    private function authorizeDraftCompleted(Room $room): void
    {
        if ($room->status !== RoomStatus::Completed) {
            abort(403, 'Teams can only be changed after the draft completes.');
        }
    }

    private function colorTakenByAnotherTeam(Room $room, Team $team, string $color): bool
    {
        return $room->teams()
            ->whereKeyNot($team->id)
            ->where('color', $color)
            ->exists();
    }
}
