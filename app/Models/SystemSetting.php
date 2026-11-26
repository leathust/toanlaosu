<?php

namespace App\Models;

use Database\Factories\SystemSettingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SystemSetting extends Model
{
    /** @use HasFactory<SystemSettingFactory> */
    use HasFactory;

    protected $fillable = ['key', 'value', 'updated_by'];

    protected function casts(): array
    {
        return ['value' => 'json'];
    }

    /**
     * @return array<string, string>
     */
    public static function schema(): array
    {
        return [
            'ai.enabled' => 'boolean',
            'ai.daily_budget' => 'integer',
            'audit_retention_months' => 'integer',
            'exam_retention_years' => 'integer',
            'ai_vector_search_enabled' => 'boolean',
        ];
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
