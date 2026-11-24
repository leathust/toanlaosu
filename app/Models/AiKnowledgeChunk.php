<?php

namespace App\Models;

use Database\Factories\AiKnowledgeChunkFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AiKnowledgeChunk extends Model
{
    /** @use HasFactory<AiKnowledgeChunkFactory> */
    use HasFactory;

    protected $fillable = ['document_id', 'chunk_index', 'content', 'token_count', 'content_hash', 'external_vector_id', 'metadata'];

    protected function casts(): array
    {
        return ['metadata' => 'array'];
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(AiKnowledgeDocument::class);
    }

    public function sources(): HasMany
    {
        return $this->hasMany(ChatMessageSource::class, 'ai_knowledge_chunk_id');
    }
}
