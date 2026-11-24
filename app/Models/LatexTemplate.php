<?php

namespace App\Models;

use Database\Factories\LatexTemplateFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class LatexTemplate extends Model
{
    /** @use HasFactory<LatexTemplateFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = ['public_id', 'owner_id', 'name', 'status', 'active_version_id', 'created_by', 'updated_by'];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(LatexTemplateVersion::class);
    }

    public function activeVersion(): BelongsTo
    {
        return $this->belongsTo(LatexTemplateVersion::class, 'active_version_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
