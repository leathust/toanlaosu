<?php

namespace App\Http\Requests;

use App\Models\ExamAttempt;
use Illuminate\Foundation\Http\FormRequest;

class SaveExamAnswerRequest extends FormRequest
{
    public function authorize(): bool
    {
        $attempt = $this->route('attempt');

        return $attempt instanceof ExamAttempt && $attempt->user_id === $this->user()?->id;
    }

    public function rules(): array
    {
        return [
            'exam_attempt_question_id' => ['required', 'integer', 'exists:exam_attempt_questions,id'],
            'answer' => ['nullable'],
            'save_version' => ['required', 'integer', 'min:0'],
        ];
    }
}
