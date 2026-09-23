<?php

namespace App\Models;

use App\Enums\RoomStatus;
use Database\Factories\RoomFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property int $user_id
 * @property string $name
 * @property string $date
 * @property string $invite_code
 * @property int $team_size
 * @property int $num_teams
 * @property int $price_in_cents
 * @property string $currency
 * @property RoomStatus $status
 * @property array<string, mixed>|null $draft_order
 * @property int $current_pick_index
 * @property Carbon|null $draft_started_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read User $creator
 * @property-read Collection<int, User> $users
 * @property-read Collection<int, Player> $players
 * @property-read Collection<int, Team> $teams
 * @property-read Collection<int, Message> $messages
 * @property-read Collection<int, DraftPick> $draftPicks
 */
class Room extends Model
{
    /** @use HasFactory<RoomFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'name',
        'date',
        'team_size',
        'num_teams',
        'price_in_cents',
        'currency',
        'status',
        'draft_order',
        'current_pick_index',
        'draft_started_at',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'status' => RoomStatus::class,
            'draft_order' => 'array',
            'current_pick_index' => 'integer',
            'draft_started_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Room $room) {
            $room->invite_code = $room->invite_code ?? self::generateUniqueInviteCode();
        });
    }

    public static function generateUniqueInviteCode(): string
    {
        do {
            $code = strtoupper(Str::random(6));
        } while (static::withTrashed()->where('invite_code', $code)->exists());

        return $code;
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'room_users')
            ->withPivot('is_admin')
            ->withTimestamps();
    }

    public function players(): HasMany
    {
        return $this->hasMany(Player::class);
    }

    public function teams(): HasMany
    {
        return $this->hasMany(Team::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function draftPicks(): HasMany
    {
        return $this->hasMany(DraftPick::class);
    }

    public function isAdmin(User $user): bool
    {
        return $this->users()
            ->where('users.id', $user->id)
            ->where('room_users.is_admin', true)
            ->exists();
    }

    public function isMember(User $user): bool
    {
        return $this->users()->where('users.id', $user->id)->exists();
    }

    public function playerCount(): int
    {
        return $this->players()->count();
    }

    public function isFull(): bool
    {
        return $this->playerCount() >= ($this->team_size * $this->num_teams);
    }

    public function hasAllCaptains(): bool
    {
        return $this->teams()->count() === $this->num_teams;
    }

    public function syncCapacityStatus(): void
    {
        $required = $this->team_size * $this->num_teams;
        $count = $this->playerCount();

        if ($this->status === RoomStatus::Waiting && $count >= $required) {
            $this->update(['status' => RoomStatus::Full]);
        } elseif ($this->status === RoomStatus::Full && $count < $required) {
            $this->update(['status' => RoomStatus::Waiting]);
        }
    }
}
