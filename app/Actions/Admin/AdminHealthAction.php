<?php

namespace App\Actions\Admin;

use App\Models\AdminAlertState;
use App\Models\AuditLog;
use App\Models\User;
use App\Models\WebhookEvent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class AdminHealthAction
{
    public function execute(User $actor, string $fingerprint, string $source, string $sourceId, string $action, string $reason, string $idempotencyKey): void
    {
        Gate::forUser($actor)->authorize('viewAny', User::class);
        abort_unless(in_array($source, ['failed_job', 'webhook', 'ai_provider', 'exam'], true), 422, 'Unsupported health signal.');
        abort_unless($action !== 'retry' || $source === 'webhook', 422, 'Only webhook retry is idempotent.');

        DB::transaction(function () use ($actor, $fingerprint, $source, $sourceId, $action, $reason, $idempotencyKey): void {
            if ($action === 'retry') {
                $event = WebhookEvent::query()->lockForUpdate()->findOrFail((int) $sourceId);
                if ($event->status !== 'failed') {
                    return;
                }
                $event->update(['status' => 'received', 'failed_at' => null, 'error_message' => null]);
                AuditLog::query()->create(['actor_id' => $actor->id, 'event' => 'admin.health_retried', 'auditable_type' => WebhookEvent::class, 'auditable_id' => $event->id, 'new_values' => ['source' => $source, 'source_id' => $sourceId, 'reason' => $reason, 'idempotency_key' => hash('sha256', $idempotencyKey)], 'retention_expires_at' => now()->addMonths(6)]);

                return;
            }

            $status = $action === 'acknowledge' ? 'acknowledged' : 'resolved';
            $state = AdminAlertState::query()->where('fingerprint', $fingerprint)->lockForUpdate()->first();
            if ($state === null) {
                $state = new AdminAlertState(['fingerprint' => $fingerprint, 'source' => $source, 'source_id' => $sourceId, 'status' => 'open']);
            }
            if ($state->status === $status) {
                return;
            }
            $oldStatus = $state->status;
            $state->fill(['status' => $status, 'updated_by' => $actor->id, 'acknowledged_at' => $state->acknowledged_at ?? now(), 'resolved_at' => $status === 'resolved' ? now() : null])->save();
            AuditLog::query()->create(['actor_id' => $actor->id, 'event' => 'admin.health_'.$action, 'auditable_type' => AdminAlertState::class, 'auditable_id' => $state->id, 'old_values' => ['status' => $oldStatus], 'new_values' => ['status' => $status, 'source' => $source, 'source_id' => $sourceId, 'reason' => $reason, 'idempotency_key' => hash('sha256', $idempotencyKey)], 'retention_expires_at' => now()->addMonths(6)]);
        });
    }
}
