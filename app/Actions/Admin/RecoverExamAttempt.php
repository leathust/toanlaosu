<?php

namespace App\Actions\Admin;

use App\Models\AuditLog;
use App\Models\ExamAttempt;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

final class RecoverExamAttempt
{
    /**
     * @return array{attempt:ExamAttempt,idempotent:bool}
     */
    public function execute(User $actor, ExamAttempt $attempt, string $action, string $reason, string $idempotencyKey): array
    {
        Gate::forUser($actor)->authorize('viewAny', User::class);

        return DB::transaction(function () use ($actor, $attempt, $action, $reason, $idempotencyKey): array {
            $lockedAttempt = ExamAttempt::query()->lockForUpdate()->findOrFail($attempt->getKey());
            $existing = AuditLog::query()
                ->where('event', 'admin.exam_recovery')
                ->where('auditable_type', ExamAttempt::class)
                ->where('auditable_id', $attempt->getKey())
                ->whereJsonContains('new_values->idempotency_key', $idempotencyKey)
                ->latest('id')
                ->first();

            if ($existing !== null) {
                return ['attempt' => $lockedAttempt, 'idempotent' => true];
            }

            if ($action !== 'expire') {
                throw ValidationException::withMessages(['action' => 'Recovery action chưa nằm trong runbook được cho phép.']);
            }

            if ($lockedAttempt->status !== 'in_progress') {
                throw ValidationException::withMessages(['attempt' => 'Attempt không còn ở trạng thái đang thi để expire.']);
            }

            if ($lockedAttempt->deadline_at === null || $lockedAttempt->deadline_at->isFuture()) {
                throw ValidationException::withMessages(['attempt' => 'Chỉ được expire attempt đã quá deadline theo server time.']);
            }

            $oldValues = $lockedAttempt->only(['status', 'deadline_at', 'save_version']);
            $lockedAttempt->forceFill(['status' => 'expired'])->save();
            AuditLog::query()->create([
                'actor_id' => $actor->getKey(),
                'event' => 'admin.exam_recovery',
                'auditable_type' => ExamAttempt::class,
                'auditable_id' => $lockedAttempt->getKey(),
                'old_values' => $oldValues,
                'new_values' => [
                    'action' => $action,
                    'status' => 'expired',
                    'reason' => $reason,
                    'idempotency_key' => $idempotencyKey,
                ],
                'retention_expires_at' => now()->addMonths(6),
            ]);

            return ['attempt' => $lockedAttempt->fresh(), 'idempotent' => false];
        });
    }
}
