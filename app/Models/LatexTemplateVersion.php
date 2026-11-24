<?php

namespace App\Models;

use Database\Factories\LatexTemplateVersionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LatexTemplateVersion extends Model
{
    /** @use HasFactory<LatexTemplateVersionFactory> */
    use HasFactory;

    protected $fillable = ['latex_template_id', 'version', 'preamble', 'header', 'question_format', 'solution_format', 'footer', 'status', 'published_at', 'created_by'];

    protected function casts(): array
    {
        return ['published_at' => 'datetime'];
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(LatexTemplate::class, 'latex_template_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
