<?php

namespace Database\Factories;

use App\Models\ExamAttempt;
use App\Models\ExamAttemptQuestion;
use App\Models\ExamQuestion;
use App\Models\QuestionVersion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExamAttemptQuestion>
 */
class ExamAttemptQuestionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'exam_attempt_id' => ExamAttempt::factory(),
            'exam_question_id' => ExamQuestion::factory(),
            'question_version_id' => QuestionVersion::factory(),
            'sort_order' => 1,
            'option_order' => null,
            'points' => 1,
            'snapshot' => null,
        ];
    }
}
