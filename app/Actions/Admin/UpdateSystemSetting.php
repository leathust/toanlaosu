<?php

namespace App\Actions\Admin;

use App\Models\AuditLog;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class UpdateSystemSetting
{
    public function execute(User $actor, string $key, mixed $value): SystemSetting
    {
        Gate::forUser($actor)->authorize('viewAny', User::class);
        abort_unless(array_key_exists($key, SystemSetting::schema()), 422, 'Unknown system setting.');
        $value = SystemSetting::schema()[$key] === 'boolean' ? (bool) $value : (int) $value;

        return DB::transaction(function () use ($actor, $key, $value): SystemSetting {
            $setting = SystemSetting::query()->lockForUpdate()->firstOrNew(['key' => $key]);
            $old = $setting->exists ? $setting->value : null;
            $setting->fill(['value' => $value, 'updated_by' => $actor->getKey()])->save();
            AuditLog::query()->create([
                'actor_id' => $actor->getKey(),
                'event' => 'system_setting.updated',
                'auditable_type' => SystemSetting::class,
                'auditable_id' => $setting->getKey(),
                'old_values' => ['key' => $key, 'value' => $old],
                'new_values' => ['key' => $key, 'value' => $value],
                'retention_expires_at' => now()->addMonths(6),
            ]);

            return $setting->fresh();
        });
    }
}
