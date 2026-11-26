<?php

namespace App\Actions\Admin;

use App\Models\AuditLog;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class UpdateUserRoles
{
    /**
     * @param  list<string>  $roleNames
     */
    public function execute(User $actor, User $subject, array $roleNames): User
    {
        Gate::forUser($actor)->authorize('update', $subject);
        $roleNames = array_values(array_unique($roleNames));

        return DB::transaction(function () use ($actor, $subject, $roleNames): User {
            $oldRoles = $subject->roles()->pluck('name')->sort()->values()->all();
            $roles = Role::query()->whereIn('name', $roleNames)->get();
            if ($roles->count() !== count($roleNames)) {
                throw ValidationException::withMessages(['roles' => 'Role không hợp lệ.']);
            }

            $removesAdmin = in_array('admin', $oldRoles, true) && ! in_array('admin', $roleNames, true);
            if ($removesAdmin && User::query()->whereHas('roles', fn ($query) => $query->where('name', 'admin'))->count() <= 1) {
                throw ValidationException::withMessages(['roles' => 'Không thể thu hồi Admin cuối cùng.']);
            }

            $subject->roles()->sync($roles->mapWithKeys(fn (Role $role): array => [$role->id => ['assigned_by' => $actor->id, 'assigned_at' => now()]])->all());
            AuditLog::query()->create([
                'actor_id' => $actor->id,
                'event' => 'user.roles_updated',
                'auditable_type' => User::class,
                'auditable_id' => $subject->id,
                'old_values' => ['roles' => $oldRoles],
                'new_values' => ['roles' => $roleNames],
                'retention_expires_at' => now()->addMonths(6),
            ]);

            return $subject->fresh('roles');
        });
    }
}
