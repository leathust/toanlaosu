<?php

namespace App\Models;

use Database\Factories\LessonBlockFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class LessonBlock extends Model
{
    /** @use HasFactory<LessonBlockFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = ['lesson_id', 'block_type', 'sort_order', 'is_blocking', 'body', 'video_id', 'material_id', 'question_id', 'visual_scene_id', 'settings', 'created_by', 'updated_by'];

    protected function casts(): array
    {
        return ['is_blocking' => 'boolean', 'settings' => 'array'];
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    public function video(): BelongsTo
    {
        return $this->belongsTo(LessonVideo::class, 'video_id');
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(LessonMaterial::class, 'material_id');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    public function visualScene(): BelongsTo
    {
        return $this->belongsTo(VisualScene::class, 'visual_scene_id');
    }

    public function progress(): HasMany
    {
        return $this->hasMany(LessonBlockProgress::class);
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(UserAttempt::class);
    }
}
