<?php

namespace App\Models;

use Database\Factories\QuestionVersionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QuestionVersion extends Model
{
    /** @use HasFactory<QuestionVersionFactory> */
    use HasFactory;

    protected $fillable = ['question_id', 'version', 'stem', 'solution', 'answer_config', 'scoring_policy', 'allow_negative', 'content_hash', 'status', 'published_at', 'created_by'];

    protected function casts(): array
    {
        return ['answer_config' => 'array', 'scoring_policy' => 'array', 'allow_negative' => 'boolean', 'published_at' => 'datetime'];
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function options(): HasMany
    {
        return $this->hasMany(QuestionOption::class)->orderBy('sort_order');
    }

    public function steps(): HasMany
    {
        return $this->hasMany(QuestionStep::class)->orderBy('sort_order');
    }

    public function skills(): BelongsToMany
    {
        return $this->belongsToMany(Skill::class)->withPivot('weight');
    }
}
