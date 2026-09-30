<?php

namespace App\Events;

use App\Models\Room;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;

class PlayerRemoved implements ShouldBroadcast
{
    use SerializesModels;

    public function __construct(public Room $room, public int $playerId) {}

    public function broadcastOn(): array
    {
        return [new PresenceChannel('room.'.$this->room->id)];
    }
}
