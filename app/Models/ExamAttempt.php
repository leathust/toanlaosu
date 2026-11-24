<?php

namespace App\Models;

use Database\Factories\ExamAttemptFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExamAttempt extends Model
{
    /** @use HasFactory<ExamAttemptFactory> */
    use HasFactory;

    protected $fillable = ['public_id', 'exam_version_id', 'user_id', 'attempt_number', 'status', 'started_at', 'deadline_at', 'submitted_at', 'graded_at', 'score', 'max_score', 'save_version', 'retention_expires_at', 'metadata'];

    protected function casts(): array
    {
        return ['started_at' => 'datetime', 'deadline_at' => 'datetime', 'submitted_at' => 'datetime', 'graded_at' => 'datetime', 'score' => 'decimal:4', 'max_score' => 'decimal:4', 'retention_expires_at' => 'datetime', 'metadata' => 'array'];
    }

    public function examVersion(): BelongsTo
    {
        return $this->belongsTo(ExamVersion::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function questions(): HasMany
    {
        return $this->hasMany(ExamAttemptQuestion::class)->orderBy('sort_order');
    }

    public function events(): HasMany
    {
        return $this->hasMany(ExamEvent::class)->orderBy('occurred_at');
    }
}
