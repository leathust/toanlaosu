<?php

namespace App\Models;

use Database\Factories\ExportJobFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExportJob extends Model
{
    /** @use HasFactory<ExportJobFactory> */
    use HasFactory;

    protected $fillable = ['public_id', 'requested_by', 'exam_version_id', 'latex_template_version_id', 'mode', 'status', 'options', 'idempotency_key', 'disk', 'path', 'requested_at', 'completed_at', 'failed_at', 'error_message', 'expires_at'];

    protected function casts(): array
    {
        return ['options' => 'array', 'requested_at' => 'datetime', 'completed_at' => 'datetime', 'failed_at' => 'datetime', 'expires_at' => 'datetime'];
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function examVersion(): BelongsTo
    {
        return $this->belongsTo(ExamVersion::class);
    }

    public function latexTemplateVersion(): BelongsTo
    {
        return $this->belongsTo(LatexTemplateVersion::class);
    }
}
