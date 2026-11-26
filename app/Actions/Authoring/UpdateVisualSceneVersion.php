<?php

namespace App\Actions\Authoring;

use App\Models\AuditLog;
use App\Models\User;
use App\Models\VisualScene;
use App\Models\VisualSceneVersion;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class UpdateVisualSceneVersion
{
    /** @param array<string, mixed> $data */
    public function execute(User $actor, VisualScene $scene, VisualSceneVersion $version, array $data): VisualSceneVersion
    {
        Gate::forUser($actor)->authorize('update', $scene);

        return DB::transaction(function () use ($actor, $scene, $version, $data): VisualSceneVersion {
            $lockedScene = VisualScene::query()->lockForUpdate()->findOrFail($scene->getKey());
            $lockedVersion = $lockedScene->versions()->lockForUpdate()->findOrFail($version->getKey());

            if ((int) $data['version'] !== (int) $lockedVersion->version || $lockedVersion->status === 'published' || $lockedScene->published_version_id === $lockedVersion->getKey()) {
                throw new ConflictHttpException('Phiên bản đã thay đổi hoặc đã publish. Hãy mở đúng draft mới nhất trước khi lưu.');
            }

            $oldValues = $lockedVersion->only(['version', 'config', 'steps', 'allowed_commands', 'status']);
            $lockedVersion->forceFill([
                'config' => $data['config'],
                'steps' => $data['steps'],
                'allowed_commands' => $data['allowed_commands'],
                'status' => 'draft',
            ])->save();
            $lockedScene->forceFill(['name' => $data['name'], 'scene_key' => $data['scene_key'], 'status' => 'draft', 'updated_by' => $actor->getKey()])->save();

            AuditLog::query()->create([
                'actor_id' => $actor->getKey(),
                'event' => 'content.scene_version_updated',
                'auditable_type' => VisualSceneVersion::class,
                'auditable_id' => $lockedVersion->getKey(),
                'old_values' => $oldValues,
                'new_values' => $lockedVersion->only(['version', 'config', 'steps', 'allowed_commands', 'status']),
                'retention_expires_at' => now()->addMonths(6),
            ]);

            return $lockedVersion->fresh();
        });
    }
}
