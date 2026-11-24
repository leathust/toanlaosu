<?php

namespace App\Models;

use Database\Factories\AiKnowledgeDocumentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AiKnowledgeDocument extends Model
{
    /** @use HasFactory<AiKnowledgeDocumentFactory> */
    use HasFactory;

    protected $fillable = ['source_type', 'source_id', 'source_version', 'title', 'checksum', 'status', 'provider', 'provider_document_id', 'indexed_at', 'error_message', 'metadata'];

    protected function casts(): array
    {
        return ['indexed_at' => 'datetime', 'metadata' => 'array'];
    }

    public function chunks(): HasMany
    {
        return $this->hasMany(AiKnowledgeChunk::class, 'document_id');
    }
}
