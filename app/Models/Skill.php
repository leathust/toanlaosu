<?php

namespace App\Models;

use Database\Factories\SkillFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Skill extends Model
{
    /** @use HasFactory<SkillFactory> */
    use HasFactory;

    protected $fillable = ['parent_id', 'code', 'name', 'description', 'sort_order'];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Skill::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Skill::class, 'parent_id')->orderBy('sort_order');
    }

    public function questionVersions(): BelongsToMany
    {
        return $this->belongsToMany(QuestionVersion::class)->withPivot('weight');
    }
}
