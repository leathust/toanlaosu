<?php

namespace App\Models;

use Database\Factories\ExamQuestionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExamQuestion extends Model
{
    /** @use HasFactory<ExamQuestionFactory> */
    use HasFactory;

    protected $fillable = ['exam_version_id', 'question_version_id', 'sort_order', 'points', 'section_key', 'settings'];

    protected function casts(): array
    {
        return ['points' => 'decimal:4', 'settings' => 'array'];
    }

    public function examVersion(): BelongsTo
    {
        return $this->belongsTo(ExamVersion::class);
    }

    public function questionVersion(): BelongsTo
    {
        return $this->belongsTo(QuestionVersion::class);
    }
}
