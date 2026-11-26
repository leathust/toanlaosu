<?php

namespace App\Actions\Admin;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class RevokeUserSessions
{
    public function execute(User $actor, User $subject, Request $request): int
    {
        Gate::forUser($actor)->authorize('update', $subject);
        $count = DB::table(config('session.table', 'sessions'))->where('user_id', $subject->id)->delete();
        AuditLog::query()->create([
            'actor_id' => $actor->id,
            'event' => 'admin.user_sessions_revoked',
            'auditable_type' => User::class,
            'auditable_id' => $subject->id,
            'new_values' => ['sessions_revoked' => $count],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'retention_expires_at' => now()->addMonths(6),
        ]);

        return $count;
    }
}
