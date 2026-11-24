<?php

namespace Database\Factories;

use App\Models\LearningTarget;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LearningTarget>
 */
class LearningTargetFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => fake()->unique()->lexify('TGT-????'),
            'name' => fake()->sentence(3),
            'description' => fake()->optional()->paragraph(),
            'sort_order' => fake()->numberBetween(0, 10),
        ];
    }
}
