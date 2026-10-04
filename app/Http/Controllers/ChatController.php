<?php

namespace App\Http\Controllers;

use App\Events\NewMessage;
use App\Models\Room;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    public function index(Room $room): JsonResponse
    {
        return response()->json(
            $room->messages()
                ->with('user:id,name,username,email')
                ->orderBy('id')
                ->get()
        );
    }

    public function store(Request $request, Room $room): RedirectResponse
    {
        $validated = $request->validate([
            'body' => ['required', 'string', 'max:1000'],
        ]);

        $message = $room->messages()->create([
            ...$validated,
            'user_id' => $request->user()->id,
        ]);

        event(new NewMessage($room, $message));

        return to_route('rooms.show', $room);
    }
}
