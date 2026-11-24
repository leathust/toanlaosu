<?php

namespace Database\Factories;

use App\Models\QuestionOption;
use App\Models\QuestionVersion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QuestionOption>
 */
class QuestionOptionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'question_version_id' => QuestionVersion::factory(),
            'option_key' => fake()->unique()->randomLetter(),
            'content' => fake()->sentence(),
            'is_correct' => false,
            'score' => null,
            'feedback' => null,
            'sort_order' => fake()->numberBetween(1, 4),
        ];
    }
}
