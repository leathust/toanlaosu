<?php

namespace App\Models;

use Database\Factories\ExamEventFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExamEvent extends Model
{
    /** @use HasFactory<ExamEventFactory> */
    use HasFactory;

    public $timestamps = false;

    protected $fillable = ['exam_attempt_id', 'event_type', 'client_occurred_at', 'occurred_at', 'ip_address', 'user_agent', 'metadata'];

    protected function casts(): array
    {
        return ['client_occurred_at' => 'datetime', 'occurred_at' => 'datetime', 'metadata' => 'array'];
    }

    public function examAttempt(): BelongsTo
    {
        return $this->belongsTo(ExamAttempt::class, 'exam_attempt_id');
    }
}
