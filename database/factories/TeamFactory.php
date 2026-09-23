<?php

namespace Database\Factories;

use App\Models\Room;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Team>
 */
class TeamFactory extends Factory
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
            'captain_user_id' => User::factory(),
            'name' => null,
            'color' => '#6B7280',
            'pick_order' => 1,
        ];
    }
}
