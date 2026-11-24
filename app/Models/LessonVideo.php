<?php

namespace App\Models;

use Database\Factories\LessonVideoFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class LessonVideo extends Model
{
    /** @use HasFactory<LessonVideoFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = ['title', 'provider', 'provider_asset_id', 'disk', 'path', 'status', 'duration_seconds', 'processing_error', 'metadata', 'created_by', 'updated_by'];

    protected function casts(): array
    {
        return ['metadata' => 'array'];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function progress(): HasMany
    {
        return $this->hasMany(VideoProgress::class);
    }
}
