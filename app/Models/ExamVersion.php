<?php

namespace App\Models;

use Database\Factories\ExamVersionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExamVersion extends Model
{
    /** @use HasFactory<ExamVersionFactory> */
    use HasFactory;

    protected $fillable = ['exam_id', 'version', 'settings_snapshot', 'total_points', 'status', 'published_at', 'created_by'];

    protected function casts(): array
    {
        return ['settings_snapshot' => 'array', 'total_points' => 'decimal:4', 'published_at' => 'datetime'];
    }

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    public function questions(): HasMany
    {
        return $this->hasMany(ExamQuestion::class)->orderBy('sort_order');
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(ExamAttempt::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
