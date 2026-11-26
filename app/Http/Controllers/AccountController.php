<?php

namespace App\Http\Controllers;

use App\Http\Requests\ChangeEmailRequest;
use App\Http\Requests\ChangePasswordRequest;
use App\Http\Requests\ConfirmPasswordRequest;
use App\Models\AuditLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AccountController extends Controller
{
    public function edit(Request $request): View
    {
        $currentSessionId = $request->session()->getId();
        $sessions = DB::table(config('session.table', 'sessions'))
            ->where('user_id', $request->user()->getKey())
            ->orderByDesc('last_activity')
            ->get(['id', 'ip_address', 'user_agent', 'last_activity'])
            ->map(fn (object $session): array => [
                'key' => hash('sha256', $session->id),
                'device' => $this->deviceLabel($session->user_agent),
                'ip_address' => $this->maskedIp($session->ip_address),
                'last_activity' => $session->last_activity,
                'is_current' => hash_equals((string) $currentSessionId, (string) $session->id),
            ]);

        return view('auth.account-settings', compact('sessions'));
    }

    public function confirmPasswordForm(): View
    {
        return view('auth.confirm-password');
    }

    public function confirmPassword(ConfirmPasswordRequest $request): RedirectResponse
    {
        $request->session()->put('auth.password_confirmed_at', now()->unix());

        $request->session()->forget('url.intended');

        return redirect()->route('account.settings');
    }

    public function revokeSession(Request $request, string $sessionKey): RedirectResponse
    {
        $session = $this->findSession($request, $sessionKey);

        if ($session === null || $session->id === $request->session()->getId()) {
            return back()->withErrors(['session' => 'Phiên đăng nhập không hợp lệ hoặc là phiên hiện tại.']);
        }

        DB::table(config('session.table', 'sessions'))->where('id', $session->id)->delete();
        $this->auditRequest($request, 'account.session_revoked', ['sessions_revoked' => 1]);

        return back()->with('status', 'Đã thu hồi phiên đăng nhập đã chọn.');
    }

    public function revokeOtherSessions(Request $request): RedirectResponse
    {
        $currentSessionId = $request->session()->getId();
        $count = DB::table(config('session.table', 'sessions'))
            ->where('user_id', $request->user()->getKey())
            ->where('id', '!=', $currentSessionId)
            ->delete();
        $this->auditRequest($request, 'account.sessions_revoked', ['sessions_revoked' => $count]);

        return back()->with('status', "Đã thu hồi {$count} phiên đăng nhập khác.");
    }

    public function updateEmail(ChangeEmailRequest $request): RedirectResponse
    {
        $user = $request->user();
        $user->forceFill([
            'email' => $request->validated('email'),
            'email_verified_at' => null,
        ])->save();
        $user->sendEmailVerificationNotification();
        $this->audit($request, 'account.email_changed', ['email_changed' => true]);

        return back()->with('status', 'Email đã cập nhật. Hãy kiểm tra hộp thư để xác minh địa chỉ mới.');
    }

    public function updatePassword(ChangePasswordRequest $request): RedirectResponse
    {
        $user = $request->user();
        $currentSessionId = $request->session()->getId();

        $user->forceFill([
            'password' => $request->validated('password'),
            'remember_token' => Str::random(60),
        ])->save();

        DB::table(config('session.table', 'sessions'))
            ->where('user_id', $user->getKey())
            ->where('id', '!=', $currentSessionId)
            ->delete();
        $request->session()->regenerate();
        $this->audit($request, 'account.password_changed', ['sessions_revoked' => true]);

        return back()->with('status', 'Mật khẩu đã được cập nhật và các phiên đăng nhập khác đã bị thu hồi.');
    }

    /**
     * @param  array<string, bool>  $newValues
     */
    private function audit(ChangeEmailRequest|ChangePasswordRequest $request, string $event, array $newValues): void
    {
        $this->auditRequest($request, $event, $newValues);
    }

    /**
     * @param  array<string, bool|int>  $newValues
     */
    private function auditRequest(Request $request, string $event, array $newValues): void
    {
        AuditLog::query()->create([
            'actor_id' => $request->user()->getKey(),
            'event' => $event,
            'auditable_type' => get_class($request->user()),
            'auditable_id' => $request->user()->getKey(),
            'new_values' => $newValues,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'occurred_at' => now(),
            'retention_expires_at' => now()->addMonths(6),
        ]);
    }

    private function findSession(Request $request, string $sessionKey): ?object
    {
        return DB::table(config('session.table', 'sessions'))
            ->where('user_id', $request->user()->getKey())
            ->get(['id', 'ip_address', 'user_agent', 'last_activity'])
            ->first(fn (object $session): bool => hash_equals($sessionKey, hash('sha256', $session->id)));
    }

    private function deviceLabel(?string $userAgent): string
    {
        $agent = strtolower((string) $userAgent);

        return match (true) {
            str_contains($agent, 'mobile') => 'Thiết bị di động',
            str_contains($agent, 'tablet') => 'Máy tính bảng',
            str_contains($agent, 'edg') => 'Microsoft Edge',
            str_contains($agent, 'chrome') => 'Google Chrome',
            str_contains($agent, 'firefox') => 'Mozilla Firefox',
            str_contains($agent, 'safari') => 'Safari',
            default => 'Trình duyệt không xác định',
        };
    }

    private function maskedIp(?string $ipAddress): string
    {
        if ($ipAddress === null || $ipAddress === '') {
            return 'Không xác định';
        }

        if (str_contains($ipAddress, ':')) {
            return implode(':', array_slice(explode(':', $ipAddress), 0, 3)).':…';
        }

        $parts = explode('.', $ipAddress);

        return count($parts) === 4 ? implode('.', [$parts[0], $parts[1], '•••', '•••']) : '•••';
    }
}
