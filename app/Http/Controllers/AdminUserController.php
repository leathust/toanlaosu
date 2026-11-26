<?php

namespace App\Http\Controllers;

use App\Actions\Admin\RevokeUserSessions;
use App\Actions\Admin\UpdateUserRoles;
use App\Http\Requests\AdminUserRolesRequest;
use App\Models\AuditLog;
use App\Models\Role;
use App\Models\User;
use App\Support\SafeCsv;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class AdminUserController extends Controller
{
    public function show(User $user): View
    {
        Gate::authorize('view', $user);
        $user->load(['roles', 'teachingCourses:id,title']);
        $audits = AuditLog::query()->where('auditable_type', User::class)->where('auditable_id', $user->id)->latest('occurred_at')->limit(20)->get(['id', 'event', 'occurred_at']);

        return view('admin.user-detail', ['subject' => $user, 'audits' => $audits, 'roles' => Role::query()->orderBy('name')->get(['name', 'display_name'])]);
    }

    public function updateRoles(AdminUserRolesRequest $request, User $user, UpdateUserRoles $action): RedirectResponse
    {
        $action->execute($request->user(), $user, $request->validated('roles'));

        return back()->with('status', 'Roles updated.');
    }

    public function revokeSessions(Request $request, User $user, RevokeUserSessions $action): RedirectResponse
    {
        $count = $action->execute($request->user(), $user, $request);

        return back()->with('status', "{$count} session(s) revoked.");
    }

    public function export(Request $request): Response
    {
        Gate::authorize('viewAny', User::class);
        $users = User::query()->with('roles')->orderBy('id')->limit(1000)->get(['id', 'name', 'status', 'email_verified_at', 'last_login_at']);
        $csv = SafeCsv::row(['id', 'name', 'status', 'email_verified', 'last_login']);
        foreach ($users as $user) {
            $csv .= SafeCsv::row([$user->id, $user->name, $user->status->value, $user->email_verified_at?->toIso8601String() ?? '', $user->last_login_at?->toIso8601String() ?? '']);
        }

        AuditLog::query()->create(['actor_id' => $request->user()->id, 'event' => 'admin.users_exported', 'auditable_type' => User::class, 'new_values' => ['rows' => $users->count(), 'columns' => ['id', 'name', 'status', 'email_verified', 'last_login']], 'ip_address' => $request->ip(), 'user_agent' => $request->userAgent(), 'retention_expires_at' => now()->addMonths(6)]);

        return new Response($csv, 200, ['Content-Type' => 'text/csv; charset=UTF-8', 'Content-Disposition' => 'attachment; filename=users-redacted.csv', 'X-Export-Row-Limit' => '1000']);
    }
}
