<?php

namespace App\Http\Middleware;

use App\Services\Admin\AdminActionCenterQuery;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    /**
     * Share data with every Inertia page.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'app' => [
                'name' => config('app.name'),
            ],
            'auth' => [
                'user' => function () use ($request): ?array {
                    $user = $request->user();

                    if ($user === null) {
                        return null;
                    }

                    return [
                        ...$user->only(['id', 'name', 'email']),
                        'roles' => $user->roles()->pluck('name')->values()->all(),
                    ];
                },
            ],
            'navigation' => [
                'admin' => function () use ($request): array {
                    if (! $request->user()?->hasRole('admin')) {
                        return [];
                    }

                    return [
                        ['label' => 'Overview', 'href' => route('admin.dashboard'), 'description' => 'KPI và tín hiệu hệ thống'],
                        ['label' => 'Users & access', 'href' => route('admin.users'), 'description' => 'Tài khoản, role và invite'],
                        ['label' => 'Content review', 'href' => route('admin.content.review'), 'description' => 'Hàng đợi duyệt nội dung'],
                        ['label' => 'Exams', 'href' => route('admin.exams'), 'description' => 'Live exam health vÃ  incident recovery'],
                        ['label' => 'AI operations', 'href' => route('admin.ai'), 'description' => 'Usage, quality và provider'],
                        ['label' => 'Learning analytics', 'href' => route('admin.analytics.learning'), 'description' => 'Funnel, mastery và diagnostics'],
                        ['label' => 'Action Center', 'href' => route('admin.action-center'), 'description' => 'Operational alerts và hàng đợi hành động', 'badge' => app(AdminActionCenterQuery::class)->countOpen()],
                        ['label' => 'Audit & system', 'href' => route('admin.operations'), 'description' => 'Audit trail và health'],
                    ];
                },
            ],
        ];
    }
}
