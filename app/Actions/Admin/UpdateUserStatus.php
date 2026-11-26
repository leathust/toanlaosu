<?php

namespace App\Actions\Admin;

use App\Models\AuditLog;
use App\Models\User;
use App\UserStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class UpdateUserStatus
{
    public function execute(User $actor, User $subject, UserStatus $status, string $reason): User
    {
        Gate::forUser($actor)->authorize('update', $subject);

        return DB::transaction(function () use ($actor, $subject, $status, $reason): User {
            if ($actor->is($subject) && $status !== UserStatus::Active) {
                throw ValidationException::withMessages(['status' => 'Không thể tự vô hiệu hóa tài khoản Admin hiện tại.']);
            }
            if ($subject->hasRole('admin') && $status !== UserStatus::Active && User::query()->whereHas('roles', fn ($query) => $query->where('name', 'admin'))->where('status', UserStatus::Active->value)->count() <= 1) {
                throw ValidationException::withMessages(['status' => 'Không thể vô hiệu hóa Admin cuối cùng.']);
            }
            $old = $subject->status->value;
            $subject->update(['status' => $status]);
            AuditLog::query()->create([
                'actor_id' => $actor->getKey(), 'event' => 'user.status_updated', 'auditable_type' => User::class,
                'auditable_id' => $subject->getKey(), 'old_values' => ['status' => $old],
                'new_values' => ['status' => $status->value, 'reason' => $reason], 'retention_expires_at' => now()->addMonths(6),
            ]);

            return $subject->fresh();
        });
    }
}
