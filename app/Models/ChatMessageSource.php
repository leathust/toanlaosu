<?php

namespace App\Models;

use Database\Factories\ChatMessageSourceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatMessageSource extends Model
{
    /** @use HasFactory<ChatMessageSourceFactory> */
    use HasFactory;

    protected $fillable = ['chat_message_id', 'ai_knowledge_chunk_id', 'citation_order', 'label', 'quoted_excerpt', 'metadata'];

    protected function casts(): array
    {
        return ['metadata' => 'array'];
    }

    public function message(): BelongsTo
    {
        return $this->belongsTo(ChatMessage::class, 'chat_message_id');
    }

    public function chunk(): BelongsTo
    {
        return $this->belongsTo(AiKnowledgeChunk::class, 'ai_knowledge_chunk_id');
    }
}
