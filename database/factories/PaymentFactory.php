<?php

namespace Database\Factories;

use App\Models\Payment;
use App\Models\Player;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'player_id' => Player::factory(),
            'marked_by_user_id' => User::factory(),
            'reference_image_path' => null,
            'paid_at' => fake()->dateTimeBetween('-1 week', 'now'),
        ];
    }
}
