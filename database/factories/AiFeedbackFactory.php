<?php

namespace Database\Factories;

use App\Models\AiFeedback;
use App\Models\ChatMessage;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AiFeedback>
 */
class AiFeedbackFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'chat_message_id' => ChatMessage::factory(),
            'user_id' => User::factory(),
            'rating' => fake()->numberBetween(1, 5),
            'reason' => null,
            'comment' => fake()->optional()->sentence(),
            'reviewed_by' => null,
            'reviewed_at' => null,
        ];
    }
}
