<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Unit>
 */
class UnitFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'course_id' => Course::factory(),
            'parent_id' => null,
            'slug' => fake()->unique()->slug(2),
            'title' => fake()->sentence(4),
            'description' => fake()->optional()->paragraph(),
            'status' => 'draft',
            'sort_order' => fake()->numberBetween(0, 10),
            'created_by' => null,
            'updated_by' => null,
        ];
    }
}
