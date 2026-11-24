<?php

namespace App\Models;

use Database\Factories\ChatThreadFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ChatThread extends Model
{
    /** @use HasFactory<ChatThreadFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = ['public_id', 'user_id', 'course_id', 'lesson_id', 'question_version_id', 'lesson_block_id', 'mode', 'status', 'title', 'last_message_at', 'retention_expires_at', 'metadata'];

    protected function casts(): array
    {
        return ['last_message_at' => 'datetime', 'retention_expires_at' => 'datetime', 'metadata' => 'array'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    public function questionVersion(): BelongsTo
    {
        return $this->belongsTo(QuestionVersion::class);
    }

    public function lessonBlock(): BelongsTo
    {
        return $this->belongsTo(LessonBlock::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ChatMessage::class)->orderBy('created_at');
    }
}
