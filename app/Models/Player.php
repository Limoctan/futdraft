<?php

namespace App\Models;

use Database\Factories\PlayerFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $room_id
 * @property string $name
 * @property int $rating
 * @property bool $is_captain
 * @property int|null $captain_user_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Room $room
 * @property-read User|null $captainUser
 * @property-read Collection<int, Team> $teams
 * @property-read Payment|null $payment
 */
class Player extends Model
{
    /** @use HasFactory<PlayerFactory> */
    use HasFactory;

    protected $fillable = [
        'room_id',
        'name',
        'rating',
        'is_captain',
        'captain_user_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'rating' => 'integer',
            'is_captain' => 'boolean',
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
     * @return BelongsTo<User, $this>
     */
    public function captainUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'captain_user_id');
    }

    /**
     * @return BelongsToMany<Team, $this>
     */
    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class, 'team_players')->withPivot('pick_number');
    }

    /**
     * @return HasOne<Payment, $this>
     */
    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class);
    }
}
