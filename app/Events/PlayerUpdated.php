<?php

namespace App\Events;

use App\Models\Player;
use App\Models\Room;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;

class PlayerUpdated implements ShouldBroadcast
{
    use SerializesModels;

    public function __construct(public Room $room, public Player $player) {}

    public function broadcastOn(): array
    {
        return [new PresenceChannel('room.'.$this->room->id)];
    }
}
