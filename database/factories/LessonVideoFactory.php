<?php

namespace Database\Factories;

use App\Models\LessonVideo;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LessonVideo>
 */
class LessonVideoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(4),
            'provider' => 'storage',
            'provider_asset_id' => fake()->unique()->uuid(),
            'disk' => 'public',
            'path' => fake()->filePath(),
            'status' => 'ready',
            'duration_seconds' => fake()->numberBetween(60, 3600),
            'processing_error' => null,
            'metadata' => null,
            'created_by' => null,
            'updated_by' => null,
        ];
    }
}
