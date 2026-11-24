<?php

namespace App\Models;

use Database\Factories\VideoProgressFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VideoProgress extends Model
{
    /** @use HasFactory<VideoProgressFactory> */
    use HasFactory;

    protected $fillable = ['user_id', 'lesson_video_id', 'last_position_seconds', 'watched_ranges', 'watched_seconds', 'completion_percent', 'last_watched_at', 'completed_at'];

    protected function casts(): array
    {
        return ['watched_ranges' => 'array', 'completion_percent' => 'decimal:2', 'last_watched_at' => 'datetime', 'completed_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function lessonVideo(): BelongsTo
    {
        return $this->belongsTo(LessonVideo::class);
    }
}
