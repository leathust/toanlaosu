<?php

namespace Database\Factories;

use App\Models\LessonVideo;
use App\Models\User;
use App\Models\VideoProgress;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VideoProgress>
 */
class VideoProgressFactory extends Factory
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
            'lesson_video_id' => LessonVideo::factory(),
            'last_position_seconds' => fake()->numberBetween(0, 1200),
            'watched_ranges' => [],
            'watched_seconds' => fake()->numberBetween(0, 1200),
            'completion_percent' => fake()->randomFloat(2, 0, 100),
            'last_watched_at' => now(),
            'completed_at' => null,
        ];
    }
}
