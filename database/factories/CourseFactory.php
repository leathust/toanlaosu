<?php

namespace Database\Factories;

use App\Models\Course;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Course>
 */
class CourseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'slug' => fake()->unique()->slug(4),
            'title' => fake()->sentence(5),
            'summary' => fake()->optional()->paragraph(),
            'status' => 'draft',
            'difficulty' => fake()->randomElement(['basic', 'intermediate', 'advanced']),
            'estimated_minutes' => fake()->numberBetween(30, 240),
            'published_at' => null,
            'created_by' => null,
            'updated_by' => null,
        ];
    }
}
