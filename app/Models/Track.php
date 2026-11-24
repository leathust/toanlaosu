<?php

namespace App\Models;

use Database\Factories\TrackFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Track extends Model
{
    /** @use HasFactory<TrackFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = ['slug', 'title', 'description', 'status', 'published_at', 'sort_order', 'created_by', 'updated_by'];

    protected function casts(): array
    {
        return ['published_at' => 'datetime'];
    }

    public function courses(): BelongsToMany
    {
        return $this->belongsToMany(Course::class, 'track_course')
            ->withPivot('sort_order')
            ->orderBy('track_course.sort_order');
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
