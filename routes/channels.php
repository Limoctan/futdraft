<?php

use App\Models\Room;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('room.{room}', function (User $user, Room $room) {
    if ($room->isMember($user)) {
        return [
            'id' => $user->id,
            'name' => $user->name,
        ];
    }

    return false;
});
