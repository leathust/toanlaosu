<?php

namespace Database\Factories;

use App\Models\QuestionStep;
use App\Models\QuestionVersion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QuestionStep>
 */
class QuestionStepFactory extends Factory
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
            'sort_order' => fake()->numberBetween(1, 10),
            'step_type' => 'hint',
            'prompt' => fake()->sentence(),
            'answer_config' => null,
            'hint' => fake()->optional()->sentence(),
            'explanation' => fake()->optional()->paragraph(),
            'visual_scene_version_id' => null,
        ];
    }
}
