<?php

namespace App\Models;

use Database\Factories\LessonBlockProgressFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LessonBlockProgress extends Model
{
    /** @use HasFactory<LessonBlockProgressFactory> */
    use HasFactory;

    protected $fillable = ['user_id', 'lesson_block_id', 'status', 'attempts_count', 'seconds_spent', 'hints_used', 'started_at', 'completed_at', 'skipped_at', 'last_interaction_at', 'metadata'];

    protected function casts(): array
    {
        return ['started_at' => 'datetime', 'completed_at' => 'datetime', 'skipped_at' => 'datetime', 'last_interaction_at' => 'datetime', 'metadata' => 'array'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function lessonBlock(): BelongsTo
    {
        return $this->belongsTo(LessonBlock::class);
    }
}
