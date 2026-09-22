<?php

namespace Database\Factories;

use App\Models\Player;
use App\Models\Room;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Player>
 */
class PlayerFactory extends Factory
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
            'name' => fake()->name(),
            'rating' => fake()->numberBetween(1, 5),
            'is_captain' => false,
            'captain_user_id' => null,
        ];
    }
}
