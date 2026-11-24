<?php

namespace Database\Factories;

use App\Models\QuestionVersion;
use App\Models\User;
use App\Models\UserAttempt;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserAttempt>
 */
class UserAttemptFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'question_version_id' => QuestionVersion::factory(),
            'lesson_block_id' => null,
            'attempt_number' => fake()->numberBetween(1, 10),
            'status' => 'submitted',
            'answer' => ['value' => 'A'],
            'is_correct' => true,
            'score' => 1,
            'max_score' => 1,
            'hints_used' => 0,
            'response_time_ms' => fake()->numberBetween(1000, 120000),
            'submitted_at' => now(),
            'feedback' => null,
        ];
    }
}
