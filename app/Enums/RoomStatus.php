<?php

namespace App\Enums;

enum RoomStatus: string
{
    case Waiting = 'waiting';
    case Full = 'full';
    case Drafting = 'drafting';
    case Completed = 'completed';

    public function label(): string
    {
        return match ($this) {
            self::Waiting => 'Waiting',
            self::Full => 'Full',
            self::Drafting => 'Drafting',
            self::Completed => 'Completed',
        };
    }
}
