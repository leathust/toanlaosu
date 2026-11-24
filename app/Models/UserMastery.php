<?php

namespace App\Models;

use Database\Factories\UserMasteryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserMastery extends Model
{
    /** @use HasFactory<UserMasteryFactory> */
    use HasFactory;

    protected $table = 'user_mastery';

    protected $fillable = ['user_id', 'skill_id', 'mastery_score', 'confidence_score', 'attempts_count', 'correct_count', 'last_practiced_at', 'next_review_at', 'calculation_version'];

    protected function casts(): array
    {
        return ['mastery_score' => 'decimal:2', 'confidence_score' => 'decimal:2', 'last_practiced_at' => 'datetime', 'next_review_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function skill(): BelongsTo
    {
        return $this->belongsTo(Skill::class);
    }
}
