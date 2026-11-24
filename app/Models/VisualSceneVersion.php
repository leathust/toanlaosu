<?php

namespace App\Models;

use Database\Factories\VisualSceneVersionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VisualSceneVersion extends Model
{
    /** @use HasFactory<VisualSceneVersionFactory> */
    use HasFactory;

    protected $fillable = ['visual_scene_id', 'version', 'schema_version', 'config', 'steps', 'allowed_commands', 'fallback_disk', 'fallback_path', 'status', 'published_at', 'created_by'];

    protected function casts(): array
    {
        return ['config' => 'array', 'steps' => 'array', 'allowed_commands' => 'array', 'published_at' => 'datetime'];
    }

    public function visualScene(): BelongsTo
    {
        return $this->belongsTo(VisualScene::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
