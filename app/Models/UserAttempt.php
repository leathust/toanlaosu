<?php

namespace App\Models;

use Database\Factories\UserAttemptFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserAttempt extends Model
{
    /** @use HasFactory<UserAttemptFactory> */
    use HasFactory;

    protected $fillable = ['user_id', 'question_version_id', 'lesson_block_id', 'attempt_number', 'status', 'answer', 'is_correct', 'score', 'max_score', 'hints_used', 'response_time_ms', 'submitted_at', 'feedback'];

    protected function casts(): array
    {
        return ['answer' => 'array', 'is_correct' => 'boolean', 'score' => 'decimal:4', 'max_score' => 'decimal:4', 'submitted_at' => 'datetime', 'feedback' => 'array'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function questionVersion(): BelongsTo
    {
        return $this->belongsTo(QuestionVersion::class);
    }

    public function lessonBlock(): BelongsTo
    {
        return $this->belongsTo(LessonBlock::class);
    }
}
