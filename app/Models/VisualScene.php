<?php

namespace App\Models;

use Database\Factories\VisualSceneFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class VisualScene extends Model
{
    /** @use HasFactory<VisualSceneFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = ['public_id', 'name', 'engine', 'scene_key', 'status', 'published_version_id', 'created_by', 'updated_by', 'assigned_reviewer_id'];

    public function versions(): HasMany
    {
        return $this->hasMany(VisualSceneVersion::class);
    }

    public function publishedVersion(): BelongsTo
    {
        return $this->belongsTo(VisualSceneVersion::class, 'published_version_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function assignedReviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_reviewer_id');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
