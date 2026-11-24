<?php

namespace App\Models;

use Database\Factories\QuestionStepFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuestionStep extends Model
{
    /** @use HasFactory<QuestionStepFactory> */
    use HasFactory;

    protected $fillable = ['question_version_id', 'sort_order', 'step_type', 'prompt', 'answer_config', 'hint', 'explanation', 'visual_scene_version_id'];

    protected function casts(): array
    {
        return ['answer_config' => 'array'];
    }

    public function questionVersion(): BelongsTo
    {
        return $this->belongsTo(QuestionVersion::class);
    }

    public function visualSceneVersion(): BelongsTo
    {
        return $this->belongsTo(VisualSceneVersion::class, 'visual_scene_version_id');
    }
}
