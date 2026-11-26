<?php

namespace App\Http\Controllers;

use App\Actions\Admin\ApproveContent;
use App\Actions\Admin\BulkUpdateUserStatus;
use App\Actions\Admin\UpdateSystemSetting;
use App\Actions\Admin\UpdateUserStatus;
use App\Data\Admin\AdminDashboardFilters;
use App\Http\Requests\AdminBulkUserStatusRequest;
use App\Http\Requests\AdminContentApprovalRequest;
use App\Http\Requests\AdminUserStatusRequest;
use App\Http\Requests\UpdateSystemSettingRequest;
use App\Models\AiUsageLog;
use App\Models\AuditLog;
use App\Models\Course;
use App\Models\Question;
use App\Models\SystemSetting;
use App\Models\User;
use App\Models\UserInvitation;
use App\Models\VisualScene;
use App\Services\Admin\AdminDashboardQuery;
use App\UserStatus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Inertia\Inertia;
use Inertia\Response;

class AdminController extends Controller
{
    public function dashboard(Request $request, AdminDashboardQuery $dashboardQuery): Response
    {
        Gate::authorize('viewAny', User::class);
        $filters = AdminDashboardFilters::fromRequest($request);
        $dashboard = $dashboardQuery->overview($filters);
        $audits = AuditLog::query()->with('actor')->latest('occurred_at')->limit(8)->get();

        return Inertia::render('Admin/Overview', [
            'filters' => [
                'range' => $filters->range,
                'compare' => $filters->compare,
                'course' => $filters->courseId,
                'timezone' => $filters->timezone,
            ],
            'filterOptions' => [
                'ranges' => [
                    ['value' => '7d', 'label' => '7 ngày'],
                    ['value' => '30d', 'label' => '30 ngày'],
                    ['value' => '90d', 'label' => '90 ngày'],
                ],
                'comparisons' => [
                    ['value' => 'previous', 'label' => 'So với kỳ trước'],
                    ['value' => 'none', 'label' => 'Không so sánh'],
                ],
                'timezones' => ['Asia/Ho_Chi_Minh', 'Asia/Bangkok', 'UTC'],
                'courses' => Course::query()->orderBy('title')->get(['id', 'title'])->map(fn (Course $course): array => ['id' => $course->id, 'title' => $course->title])->values()->all(),
            ],
            'kpis' => $dashboard['kpis'],
            'trend' => $dashboard['trend'],
            'courseHealth' => $dashboard['courseHealth'],
            'freshness' => $dashboard['freshness'],
            'alerts' => [],
            'recentAudits' => $audits->map(fn (AuditLog $audit): array => [
                'id' => $audit->id,
                'event' => $audit->event,
                'actor' => $audit->actor?->name ?? 'System',
                'occurredAt' => $audit->occurred_at?->toIso8601String(),
            ])->values()->all(),
        ]);
    }

    public function users(Request $request): View
    {
        Gate::authorize('viewAny', User::class);
        $query = User::query()->with('roles')->latest('id');
        if ($request->filled('q')) {
            $query->where(fn ($builder) => $builder->where('name', 'like', '%'.$request->string('q').'%')->orWhere('email', 'like', '%'.$request->string('q').'%'));
        }
        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }
        if ($request->filled('role')) {
            $query->whereHas('roles', fn ($builder) => $builder->where('name', $request->string('role')));
        }
        if ($request->boolean('verified')) {
            $query->whereNotNull('email_verified_at');
        }
        if ($request->boolean('locked')) {
            $query->whereNotNull('locked_at');
        }
        if ($request->filled('last_login')) {
            $query->where('last_login_at', '>=', now()->subDays($request->integer('last_login')));
        }
        if ($request->string('invite_state')->toString() === 'pending') {
            $emails = UserInvitation::query()->whereNull('accepted_at')->whereNull('revoked_at')->where('expires_at', '>', now())->pluck('email');
            $query->whereIn('email', $emails);
        }
        $users = $query->paginate((int) min(max($request->integer('per_page', 20), 1), 100))->withQueryString();

        return view('admin.users', compact('users'));
    }

    public function content(Request $request): View
    {
        Gate::authorize('viewAny', User::class);
        $status = $request->string('status', 'draft')->toString();
        $draftQuestions = Question::query()->with('owner')->where('status', $status)->latest('id')->paginate(20, ['*'], 'questions_page')->withQueryString();
        $draftScenes = VisualScene::query()->with('creator')->where('status', $status)->latest('id')->paginate(20, ['*'], 'scenes_page')->withQueryString();
        $draftCourses = Course::query()->where('status', $status)->latest('id')->paginate(20, ['*'], 'courses_page')->withQueryString();

        return view('admin.content', compact('draftQuestions', 'draftScenes', 'draftCourses'));
    }

    public function audit(Request $request): View
    {
        Gate::authorize('viewAny', User::class);
        $query = AuditLog::query()->with('actor')->latest('occurred_at');
        if ($request->filled('event')) {
            $query->where('event', 'like', '%'.$request->string('event').'%');
        }
        if ($request->filled('actor_id')) {
            $query->where('actor_id', $request->integer('actor_id'));
        }
        $audits = $query->paginate(30)->withQueryString();

        return view('admin.audit', compact('audits'));
    }

    public function aiUsage(Request $request): View
    {
        Gate::authorize('viewAny', User::class);
        $query = AiUsageLog::query()->with('user')->latest('occurred_at');
        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }
        if ($request->filled('provider')) {
            $query->where('provider', $request->string('provider'));
        }
        $logs = $query->paginate(30)->withQueryString();
        $settings = SystemSetting::query()->whereIn('key', ['ai.enabled', 'ai.daily_budget'])->get();

        return view('admin.ai-usage', compact('logs', 'settings'));
    }

    public function updateUserStatus(AdminUserStatusRequest $request, User $user, UpdateUserStatus $action): RedirectResponse
    {
        $action->execute($request->user(), $user, UserStatus::from($request->validated('status')), $request->validated('reason'));

        return back()->with('status', 'User status updated.');
    }

    public function bulkUserStatus(AdminBulkUserStatusRequest $request, BulkUpdateUserStatus $action): RedirectResponse
    {
        $result = $action->execute($request->user(), $request->validated('user_ids'), UserStatus::from($request->validated('status')), $request->validated('reason'));

        return back()->with('status', "{$result['updated']} users updated; ".count($result['failed']).' failed.');
    }

    public function approveQuestion(AdminContentApprovalRequest $request, Question $question, ApproveContent $action): RedirectResponse
    {
        $action->execute($request->user(), $question, $request->validated('reason'));

        return back()->with('status', 'Question approved.');
    }

    public function approveScene(AdminContentApprovalRequest $request, VisualScene $scene, ApproveContent $action): RedirectResponse
    {
        $action->execute($request->user(), $scene, $request->validated('reason'));

        return back()->with('status', 'Scene approved.');
    }

    public function updateSetting(UpdateSystemSettingRequest $request, string $key, UpdateSystemSetting $action): RedirectResponse
    {
        $action->execute($request->user(), $key, $request->validated('value'));

        return back()->with('status', 'System setting updated.');
    }
}
