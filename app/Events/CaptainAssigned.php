<?php

namespace App\Events;

use App\Models\Room;
use App\Models\Team;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;

class CaptainAssigned implements ShouldBroadcast
{
    use SerializesModels;

    public function __construct(public Room $room, public Team $team) {}

    public function broadcastOn(): array
    {
        return [new PresenceChannel('room.'.$this->room->id)];
    }
}
