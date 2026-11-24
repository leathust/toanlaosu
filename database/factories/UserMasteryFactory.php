<?php

namespace Database\Factories;

use App\Models\Skill;
use App\Models\User;
use App\Models\UserMastery;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserMastery>
 */
class UserMasteryFactory extends Factory
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
            'skill_id' => Skill::factory(),
            'mastery_score' => fake()->randomFloat(2, 0, 100),
            'confidence_score' => fake()->randomFloat(2, 0, 100),
            'attempts_count' => fake()->numberBetween(0, 20),
            'correct_count' => fake()->numberBetween(0, 20),
            'last_practiced_at' => now(),
            'next_review_at' => now()->addDays(3),
            'calculation_version' => 1,
        ];
    }
}
