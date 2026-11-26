<?php

namespace App\Actions\Admin;

use App\Models\AdminAlertState;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class AcknowledgeAlert
{
    public function execute(User $actor, string $fingerprint, string $source, string $sourceId, string $status): AdminAlertState
    {
        Gate::forUser($actor)->authorize('viewAny', User::class);

        return DB::transaction(function () use ($actor, $fingerprint, $source, $sourceId, $status): AdminAlertState {
            $state = AdminAlertState::query()->where('fingerprint', $fingerprint)->lockForUpdate()->first();

            if ($state === null) {
                $state = new AdminAlertState([
                    'fingerprint' => $fingerprint,
                    'source' => $source,
                    'source_id' => $sourceId,
                    'status' => 'open',
                ]);
            }

            $oldStatus = $state->status;
            if ($oldStatus === $status) {
                return $state;
            }

            $state->status = $status;
            $state->updated_by = $actor->getKey();
            $state->acknowledged_at = in_array($status, ['acknowledged', 'resolved'], true)
                ? ($state->acknowledged_at ?? now())
                : null;
            $state->resolved_at = $status === 'resolved' ? now() : null;
            $state->save();

            AuditLog::query()->create([
                'actor_id' => $actor->getKey(),
                'event' => 'admin.alert_'.$status,
                'auditable_type' => AdminAlertState::class,
                'auditable_id' => $state->getKey(),
                'old_values' => ['status' => $oldStatus],
                'new_values' => ['status' => $status, 'source' => $state->source, 'source_id' => $state->source_id],
                'retention_expires_at' => now()->addMonths(6),
            ]);

            return $state->fresh();
        });
    }
}
