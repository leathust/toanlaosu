<?php

namespace App\Models;

use Database\Factories\AdminAlertStateFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminAlertState extends Model
{
    /** @use HasFactory<AdminAlertStateFactory> */
    use HasFactory;

    protected $fillable = ['fingerprint', 'source', 'source_id', 'status', 'updated_by', 'acknowledged_at', 'resolved_at'];

    protected function casts(): array
    {
        return ['acknowledged_at' => 'datetime', 'resolved_at' => 'datetime'];
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
