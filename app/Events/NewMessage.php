<?php

namespace App\Events;

use App\Models\Message;
use App\Models\Room;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;

class NewMessage implements ShouldBroadcast
{
    use SerializesModels;

    public function __construct(public Room $room, public Message $message) {}

    public function broadcastOn(): array
    {
        return [new PresenceChannel('room.'.$this->room->id)];
    }
}
