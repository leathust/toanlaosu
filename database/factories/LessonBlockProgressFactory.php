<?php

namespace Database\Factories;

use App\Models\LessonBlock;
use App\Models\LessonBlockProgress;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LessonBlockProgress>
 */
class LessonBlockProgressFactory extends Factory
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
            'lesson_block_id' => LessonBlock::factory(),
            'status' => 'in_progress',
            'attempts_count' => 1,
            'seconds_spent' => fake()->numberBetween(0, 600),
            'hints_used' => 0,
            'started_at' => now(),
            'completed_at' => null,
            'skipped_at' => null,
            'last_interaction_at' => now(),
            'metadata' => null,
        ];
    }
}
