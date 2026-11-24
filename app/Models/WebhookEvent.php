<?php

namespace App\Models;

use Database\Factories\WebhookEventFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WebhookEvent extends Model
{
    /** @use HasFactory<WebhookEventFactory> */
    use HasFactory;

    public $timestamps = false;

    protected $fillable = ['provider', 'provider_event_id', 'event_type', 'status', 'payload', 'received_at', 'processed_at', 'failed_at', 'error_message'];

    protected function casts(): array
    {
        return ['payload' => 'array', 'received_at' => 'datetime', 'processed_at' => 'datetime', 'failed_at' => 'datetime'];
    }
}
