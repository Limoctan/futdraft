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

    public const PICK_TIMER_MINUTES = 2;

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

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'room_users')
            ->withPivot('is_admin')
            ->withTimestamps();
    }

    /**
     * @return HasMany<Player, $this>
     */
    public function players(): HasMany
    {
        return $this->hasMany(Player::class);
    }

    /**
     * @return HasMany<Team, $this>
     */
    public function teams(): HasMany
    {
        return $this->hasMany(Team::class);
    }

    /**
     * @return HasMany<Message, $this>
     */
    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    /**
     * @return HasMany<DraftPick, $this>
     */
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

    /**
     * Snake-draft team id for the current pick index.
     */
    public function currentDraftTeamId(): ?int
    {
        $order = $this->draft_order;

        if (! is_array($order)) {
            return null;
        }

        $teamIds = array_values(array_map(
            static fn ($id): int => (int) $id,
            $order,
        ));

        if ($teamIds === []) {
            return null;
        }

        $teamCount = count($teamIds);
        $round = intdiv($this->current_pick_index, $teamCount);
        $position = $this->current_pick_index % $teamCount;

        if ($round % 2 === 1) {
            $position = $teamCount - 1 - $position;
        }

        return $teamIds[$position];
    }

    public function pickDeadline(): ?Carbon
    {
        if ($this->draft_started_at === null) {
            return null;
        }

        $lastPickAt = $this->draftPicks()->max('created_at');

        return Carbon::parse($lastPickAt ?? $this->draft_started_at)
            ->addMinutes(self::PICK_TIMER_MINUTES);
    }

    public function isPickTimerExpired(): bool
    {
        $deadline = $this->pickDeadline();

        return $deadline !== null && now()->greaterThan($deadline);
    }

    /**
     * Players not yet on a team. Reserve players are only offered once the
     * main list (first team_size × num_teams players by id) is exhausted.
     *
     * @return Collection<int, Player>
     */
    public function availableDraftPlayers(): Collection
    {
        $available = $this->players()
            ->whereDoesntHave('teams')
            ->orderBy('id')
            ->get();

        $required = $this->team_size * $this->num_teams;
        $mainPlayerIds = $this->players()
            ->orderBy('id')
            ->limit($required)
            ->pluck('id');

        $availableMain = $available->filter(
            fn (Player $player) => $mainPlayerIds->contains($player->id)
        )->values();

        if ($availableMain->isNotEmpty()) {
            return $availableMain;
        }

        return $available->values();
    }

    public function isDraftComplete(): bool
    {
        if ($this->teams()->count() !== $this->num_teams) {
            return false;
        }

        return $this->teams()
            ->withCount('players')
            ->get()
            ->every(fn (Team $team) => $team->players_count >= $this->team_size);
    }
}
