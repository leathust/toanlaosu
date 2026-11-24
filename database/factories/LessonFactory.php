<?php

namespace Database\Factories;

use App\Models\Lesson;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Lesson>
 */
class LessonFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'unit_id' => Unit::factory(),
            'slug' => fake()->unique()->slug(3),
            'title' => fake()->sentence(5),
            'summary' => fake()->optional()->paragraph(),
            'status' => 'draft',
            'estimated_minutes' => fake()->numberBetween(10, 90),
            'published_at' => null,
            'lock_version' => 1,
            'sort_order' => fake()->numberBetween(0, 10),
            'created_by' => null,
            'updated_by' => null,
        ];
    }
}
