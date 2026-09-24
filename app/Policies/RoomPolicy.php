<?php

namespace App\Policies;

use App\Models\Room;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class RoomPolicy
{
    public function view(User $user, Room $room): Response
    {
        return $room->isMember($user)
            ? Response::allow()
            : Response::deny('You are not a member of this room.');
    }

    public function manage(User $user, Room $room): Response
    {
        return $room->isAdmin($user)
            ? Response::allow()
            : Response::deny('You are not an admin of this room.');
    }
}
