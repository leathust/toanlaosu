<?php

namespace Database\Factories;

use App\Models\ExamQuestion;
use App\Models\ExamVersion;
use App\Models\QuestionVersion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExamQuestion>
 */
class ExamQuestionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'exam_version_id' => ExamVersion::factory(),
            'question_version_id' => QuestionVersion::factory(),
            'sort_order' => fake()->numberBetween(1, 20),
            'points' => 1,
            'section_key' => null,
            'settings' => null,
        ];
    }
}
