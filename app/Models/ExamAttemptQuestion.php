<?php

namespace App\Models;

use Database\Factories\ExamAttemptQuestionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ExamAttemptQuestion extends Model
{
    /** @use HasFactory<ExamAttemptQuestionFactory> */
    use HasFactory;

    protected $fillable = ['exam_attempt_id', 'exam_question_id', 'question_version_id', 'sort_order', 'option_order', 'points', 'snapshot'];

    protected function casts(): array
    {
        return ['option_order' => 'array', 'points' => 'decimal:4', 'snapshot' => 'array'];
    }

    public function attempt(): BelongsTo
    {
        return $this->belongsTo(ExamAttempt::class, 'exam_attempt_id');
    }

    public function examQuestion(): BelongsTo
    {
        return $this->belongsTo(ExamQuestion::class);
    }

    public function questionVersion(): BelongsTo
    {
        return $this->belongsTo(QuestionVersion::class);
    }

    public function answer(): HasOne
    {
        return $this->hasOne(ExamAttemptAnswer::class);
    }
}
