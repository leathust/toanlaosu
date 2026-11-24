<?php

namespace App\Models;

use Database\Factories\LessonMaterialFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class LessonMaterial extends Model
{
    /** @use HasFactory<LessonMaterialFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = ['title', 'disk', 'path', 'original_name', 'mime_type', 'size_bytes', 'allow_download', 'watermark_enabled', 'created_by', 'updated_by'];

    protected function casts(): array
    {
        return ['allow_download' => 'boolean', 'watermark_enabled' => 'boolean'];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function views(): HasMany
    {
        return $this->hasMany(MaterialView::class);
    }
}
