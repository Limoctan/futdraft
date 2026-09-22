<?php

namespace Database\Factories;

use App\Models\Room;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Room>
 */
class RoomFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->sentence(2),
            'date' => fake()->dateTimeBetween('+1 week', '+1 month'),
            'invite_code' => strtoupper(fake()->bothify('######')),
            'team_size' => fake()->randomElement([5, 7, 11]),
            'num_teams' => fake()->numberBetween(2, 4),
            'price_in_cents' => fake()->numberBetween(0, 10000),
            'currency' => fake()->randomElement(['USD', 'EUR', 'COP', 'ARS', 'MXN', 'CLP', 'BRL']),
            'status' => 'waiting',
        ];
    }
}
