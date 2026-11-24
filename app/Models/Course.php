<?php

namespace App\Models;

use Database\Factories\CourseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Course extends Model
{
    /** @use HasFactory<CourseFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = ['slug', 'title', 'summary', 'status', 'difficulty', 'estimated_minutes', 'published_at', 'created_by', 'updated_by'];

    protected function casts(): array
    {
        return ['published_at' => 'datetime'];
    }

    public function tracks(): BelongsToMany
    {
        return $this->belongsToMany(Track::class, 'track_course')->withPivot('sort_order');
    }

    public function learningTargets(): BelongsToMany
    {
        return $this->belongsToMany(LearningTarget::class);
    }

    public function teachers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'course_teachers')
            ->withPivot(['scope', 'assigned_by', 'assigned_at', 'revoked_at']);
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    public function units(): HasMany
    {
        return $this->hasMany(Unit::class)->orderBy('sort_order');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
