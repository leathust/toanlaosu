<?php

namespace Database\Factories;

use App\Models\Question;
use App\Models\QuestionVersion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QuestionVersion>
 */
class QuestionVersionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'question_id' => Question::factory(),
            'version' => 1,
            'stem' => fake()->paragraph(),
            'solution' => fake()->optional()->paragraph(),
            'solution_blocks' => null,
            'answer_config' => ['type' => 'single_choice'],
            'scoring_policy' => ['points' => 1],
            'allow_negative' => false,
            'content_hash' => hash('sha256', fake()->sentence()),
            'status' => 'draft',
            'published_at' => null,
            'created_by' => null,
        ];
    }
}
