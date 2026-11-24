<?php

namespace App\Models;

use Database\Factories\ChatMessageFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ChatMessage extends Model
{
    /** @use HasFactory<ChatMessageFactory> */
    use HasFactory;

    public $timestamps = false;

    protected $fillable = ['chat_thread_id', 'parent_id', 'role', 'status', 'content', 'model', 'provider_message_id', 'tool_calls', 'prompt_tokens', 'completion_tokens', 'latency_ms', 'error_code', 'created_at'];

    protected function casts(): array
    {
        return ['tool_calls' => 'array', 'created_at' => 'datetime'];
    }

    public function thread(): BelongsTo
    {
        return $this->belongsTo(ChatThread::class, 'chat_thread_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(ChatMessage::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(ChatMessage::class, 'parent_id');
    }

    public function sources(): HasMany
    {
        return $this->hasMany(ChatMessageSource::class);
    }

    public function usageLogs(): HasMany
    {
        return $this->hasMany(AiUsageLog::class);
    }

    public function feedback(): HasOne
    {
        return $this->hasOne(AiFeedback::class);
    }
}
