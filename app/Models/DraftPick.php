<?php

namespace App\Models;

use Database\Factories\DraftPickFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $room_id
 * @property int $team_id
 * @property int $player_id
 * @property int $pick_number
 * @property int|null $picked_by_user_id
 * @property bool $auto_picked
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Room $room
 * @property-read Team $team
 * @property-read Player $player
 * @property-read User|null $pickedByUser
 */
class DraftPick extends Model
{
    /** @use HasFactory<DraftPickFactory> */
    use HasFactory;

    protected $fillable = [
        'room_id',
        'team_id',
        'player_id',
        'pick_number',
        'picked_by_user_id',
        'auto_picked',
    ];

    protected function casts(): array
    {
        return [
            'pick_number' => 'integer',
            'auto_picked' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Room, $this>
     */
    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    /**
     * @return BelongsTo<Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * @return BelongsTo<Player, $this>
     */
    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function pickedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'picked_by_user_id');
    }
}
