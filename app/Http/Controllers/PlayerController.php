<?php

namespace App\Http\Controllers;

use App\Enums\RoomStatus;
use App\Models\Player;
use App\Models\Room;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class PlayerController extends Controller
{
    public function store(Request $request, Room $room): RedirectResponse
    {
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
        $this->authorizeModifiable($room);
        $this->authorizePlayerInRoom($room, $player);

        if ($player->is_captain) {
            abort(403, 'A captain self-pick cannot be removed. Reassign the captain first.');
        }

        $player->delete();

        $room->syncCapacityStatus();

        return redirect()->route('rooms.show', $room);
    }

    public function markPaid(Request $request, Room $room, Player $player): RedirectResponse
    {
        $this->authorizePlayerInRoom($room, $player);

        $isAdmin = $room->isAdmin(Auth::user());

        $request->validate([
            'reference_image' => [
                $isAdmin ? 'nullable' : 'required',
                'file',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:5120',
            ],
        ]);

        $paymentAttributes = [
            'marked_by_user_id' => Auth::id(),
            'paid_at' => now(),
        ];

        if ($request->hasFile('reference_image')) {
            $image = $request->file('reference_image');
            $extension = $image->guessExtension() ?? 'bin';

            $paymentAttributes['reference_image_path'] = $image->storeAs(
                "payments/{$room->id}",
                "{$player->id}_".now()->timestamp.'.'.$extension,
                'payments',
            );
        }

        $player->payment()->updateOrCreate([], $paymentAttributes);

        return redirect()->route('rooms.show', $room);
    }

    public function paymentImage(Room $room, Player $player): Response
    {
        $this->authorizePlayerInRoom($room, $player);

        $path = $player->payment?->reference_image_path;

        abort_if($path === null, 404);

        abort_unless(Storage::disk('payments')->exists($path), 404);

        return Storage::disk('payments')->response($path);
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
