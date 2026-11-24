<?php

namespace App\Models;

use Database\Factories\MaterialViewFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaterialView extends Model
{
    /** @use HasFactory<MaterialViewFactory> */
    use HasFactory;

    public $timestamps = false;

    protected $fillable = ['user_id', 'lesson_material_id', 'action', 'ip_address', 'user_agent', 'occurred_at'];

    protected function casts(): array
    {
        return ['occurred_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function lessonMaterial(): BelongsTo
    {
        return $this->belongsTo(LessonMaterial::class);
    }
}
