<?php

namespace App\Models;

use Database\Factories\LearningTargetFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class LearningTarget extends Model
{
    /** @use HasFactory<LearningTargetFactory> */
    use HasFactory;

    protected $fillable = ['code', 'name', 'description', 'sort_order'];

    public function courses(): BelongsToMany
    {
        return $this->belongsToMany(Course::class);
    }
}
