<?php

namespace Database\Factories;

use App\Models\ExamAttemptAnswer;
use App\Models\ExamAttemptQuestion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExamAttemptAnswer>
 */
class ExamAttemptAnswerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'exam_attempt_question_id' => ExamAttemptQuestion::factory(),
            'answer' => ['value' => 'A'],
            'save_version' => 1,
            'first_saved_at' => now(),
            'last_saved_at' => now(),
            'submitted_at' => null,
            'is_correct' => null,
            'score' => null,
            'grading_details' => null,
        ];
    }
}
