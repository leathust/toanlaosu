<?php

namespace App\Models;

use Database\Factories\AiUsageLogFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiUsageLog extends Model
{
    /** @use HasFactory<AiUsageLogFactory> */
    use HasFactory;

    public $timestamps = false;

    protected $fillable = ['user_id', 'chat_message_id', 'provider', 'model', 'operation', 'status', 'prompt_tokens', 'completion_tokens', 'cost', 'latency_ms', 'error_code', 'occurred_at', 'retention_expires_at'];

    protected function casts(): array
    {
        return ['cost' => 'decimal:6', 'occurred_at' => 'datetime', 'retention_expires_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function chatMessage(): BelongsTo
    {
        return $this->belongsTo(ChatMessage::class);
    }
}
