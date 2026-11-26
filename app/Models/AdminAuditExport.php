<?php

namespace App\Models;

use Database\Factories\AdminAuditExportFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminAuditExport extends Model
{
    /** @use HasFactory<AdminAuditExportFactory> */
    use HasFactory;

    protected $fillable = ['public_id', 'requested_by', 'idempotency_key', 'status', 'filters', 'disk', 'path', 'row_count', 'requested_at', 'completed_at', 'failed_at', 'error_message', 'expires_at'];

    protected function casts(): array
    {
        return ['filters' => 'array', 'requested_at' => 'datetime', 'completed_at' => 'datetime', 'failed_at' => 'datetime', 'expires_at' => 'datetime'];
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }
}
