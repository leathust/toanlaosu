<?php

namespace App\Models;

use Database\Factories\ExamFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Exam extends Model
{
    /** @use HasFactory<ExamFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = ['public_id', 'course_id', 'title', 'instructions', 'status', 'starts_at', 'ends_at', 'duration_minutes', 'late_entry_minutes', 'grace_period_seconds', 'max_attempts', 'randomize_questions', 'randomize_options', 'result_release_at', 'answer_release_at', 'created_by', 'updated_by'];

    protected function casts(): array
    {
        return ['starts_at' => 'datetime', 'ends_at' => 'datetime', 'result_release_at' => 'datetime', 'answer_release_at' => 'datetime', 'randomize_questions' => 'boolean', 'randomize_options' => 'boolean'];
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function versions(): HasMany
    {
        return $this->hasMany(ExamVersion::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
