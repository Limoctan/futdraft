<?php

namespace Database\Factories;

use App\Models\DraftPick;
use App\Models\Player;
use App\Models\Room;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DraftPick>
 */
class DraftPickFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'room_id' => Room::factory(),
            'team_id' => Team::factory(),
            'player_id' => Player::factory(),
            'pick_number' => 1,
            'picked_by_user_id' => User::factory(),
            'auto_picked' => false,
        ];
    }
}
