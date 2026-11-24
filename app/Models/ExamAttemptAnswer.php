<?php

namespace App\Models;

use Database\Factories\ExamAttemptAnswerFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExamAttemptAnswer extends Model
{
    /** @use HasFactory<ExamAttemptAnswerFactory> */
    use HasFactory;

    protected $fillable = ['exam_attempt_question_id', 'answer', 'save_version', 'first_saved_at', 'last_saved_at', 'submitted_at', 'is_correct', 'score', 'grading_details'];

    protected function casts(): array
    {
        return ['answer' => 'array', 'first_saved_at' => 'datetime', 'last_saved_at' => 'datetime', 'submitted_at' => 'datetime', 'is_correct' => 'boolean', 'score' => 'decimal:4', 'grading_details' => 'array'];
    }

    public function examAttemptQuestion(): BelongsTo
    {
        return $this->belongsTo(ExamAttemptQuestion::class);
    }
}
