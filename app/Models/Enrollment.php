<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Database\Factories\EnrollmentFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Enrollment extends Model
{
    /** @use HasFactory<EnrollmentFactory> */
    use HasFactory;

    protected $fillable = ['course_id', 'user_id', 'period_number', 'status', 'starts_at', 'ends_at', 'source', 'granted_by', 'revoked_at'];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function granter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'granted_by');
    }

    public function scopeActiveAt(Builder $query, CarbonInterface $at): Builder
    {
        return $query
            ->where('status', 'active')
            ->whereNull('revoked_at')
            ->where(function (Builder $query) use ($at): void {
                $query->whereNull('starts_at')->orWhere('starts_at', '<=', $at);
            })
            ->where(function (Builder $query) use ($at): void {
                $query->whereNull('ends_at')->orWhere('ends_at', '>=', $at);
            });
    }

    public function isActiveAt(?CarbonInterface $at = null): bool
    {
        return self::query()->whereKey($this->getKey())->activeAt($at ?? now())->exists();
    }
}
