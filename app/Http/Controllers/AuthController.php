<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Models\AuditLog;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function workspace(Request $request): View
    {
        $definitions = $this->workspaceDefinitions();
        $workspaces = $request->user()->roles()
            ->whereIn('name', array_keys($definitions))
            ->get(['name', 'display_name', 'description'])
            ->map(fn (Role $role): array => [
                ...$definitions[$role->name],
                'name' => $role->name,
                'display_name' => $role->display_name,
                'description' => $role->description ?: $definitions[$role->name]['description'],
            ])
            ->values()
            ->all();

        return view('auth.workspace', compact('workspaces'));
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $credentials = $request->safe()->only(['email', 'password']);

        $remember = $request->boolean('remember');

        if (! Auth::attempt($credentials, $remember)) {
            $this->audit($request, null, 'auth.login_failed', ['result' => 'failed']);

            return back()->withErrors(['email' => 'Email hoặc mật khẩu không đúng.'])->onlyInput('email');
        }

        $user = $request->user();

        if (! $user || ! $user->isActive()) {
            $this->audit($request, $user, 'auth.login_failed', ['result' => 'inactive']);
            Auth::logout();

            return back()->withErrors(['email' => 'Tài khoản hiện không thể đăng nhập.'])->onlyInput('email');
        }

        $request->session()->regenerate();
        $this->audit($request, $user, 'auth.login_succeeded', ['remember' => $remember]);
        $user->forceFill([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ])->saveQuietly();

        return redirect()->to($this->safeIntendedPath($request, $user) ?? $this->defaultDestination($user));
    }

    public function destroy(Request $request): RedirectResponse
    {
        $this->audit($request, $request->user(), 'auth.logout', ['result' => 'success']);
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    private function defaultDestination(User $user): string
    {
        $roles = $user->roles()
            ->whereIn('name', array_keys($this->workspaceDefinitions()))
            ->pluck('name')
            ->all();

        if (count($roles) !== 1) {
            return route('workspace');
        }

        return route($this->workspaceDefinitions()[$roles[0]]['route']);
    }

    /**
     * @return array<string, array{label: string, description: string, route: string, icon: string, classes: string}>
     */
    private function workspaceDefinitions(): array
    {
        return [
            'student' => [
                'label' => 'Học sinh',
                'description' => 'Tiếp tục bài học, luyện tập và xem lịch thi.',
                'route' => 'student.dashboard',
                'icon' => '◎',
                'classes' => 'border-brand-200 bg-brand-50 text-brand-700',
            ],
            'teacher' => [
                'label' => 'Giáo viên',
                'description' => 'Soạn nội dung trong phạm vi được phân công.',
                'route' => 'teacher.dashboard',
                'icon' => '✎',
                'classes' => 'border-indigo-200 bg-indigo-50 text-indigo-700',
            ],
            'admin' => [
                'label' => 'Admin',
                'description' => 'Vận hành tài khoản, nội dung và hệ thống.',
                'route' => 'admin.dashboard',
                'icon' => '⚙',
                'classes' => 'border-amber-200 bg-amber-50 text-amber-800',
            ],
        ];
    }

    private function safeIntendedPath(LoginRequest $request, User $user): ?string
    {
        $intended = $request->session()->pull('url.intended');

        if (! is_string($intended) || $intended === '') {
            return null;
        }

        $parts = parse_url($intended);
        $path = $parts['path'] ?? null;
        $host = $parts['host'] ?? null;

        if (! is_string($path) || ! str_starts_with($path, '/') || str_starts_with($path, '//')) {
            return null;
        }

        $allowedHosts = array_values(array_filter([
            parse_url(config('app.url'), PHP_URL_HOST),
            $request->getHost(),
        ]));

        if ($host !== null && ! in_array($host, $allowedHosts, true)) {
            return null;
        }

        if ($path === route('login', absolute: false) || $path === route('logout', absolute: false)) {
            return null;
        }

        $allowed = $path === route('workspace', absolute: false)
            || ($user->hasRole('student') && str_starts_with($path, '/student'))
            || ($user->hasRole('teacher') && str_starts_with($path, '/teacher'))
            || ($user->hasRole('admin') && str_starts_with($path, '/admin'));

        return $allowed ? $path.($parts['query'] ?? '') : null;
    }

    /**
     * @param  array<string, bool|string>  $newValues
     */
    private function audit(Request $request, ?User $user, string $event, array $newValues): void
    {
        AuditLog::query()->create([
            'actor_id' => $user?->getKey(),
            'event' => $event,
            'auditable_type' => User::class,
            'auditable_id' => $user?->getKey(),
            'new_values' => $newValues,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'occurred_at' => now(),
            'retention_expires_at' => now()->addMonths(6),
        ]);
    }
}
