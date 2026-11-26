<?php

namespace App\Http\Controllers;

use App\Http\Requests\ForgotPasswordRequest;
use App\Http\Requests\ResetPasswordRequest;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PasswordController extends Controller
{
    public function createRequest(): View
    {
        return view('auth.forgot-password');
    }

    public function sendResetLink(ForgotPasswordRequest $request): RedirectResponse
    {
        Password::sendResetLink($request->validated());

        return redirect()->route('password.request')->with('status', 'Nếu email tồn tại, hướng dẫn đặt lại mật khẩu sẽ được gửi trong ít phút.');
    }

    public function createReset(string $token): View
    {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => request()->string('email')->toString(),
        ]);
    }

    public function reset(ResetPasswordRequest $request): RedirectResponse
    {
        $status = Password::reset(
            $request->validated(),
            function (User $user, string $password) use ($request): void {
                $user->forceFill([
                    'password' => $password,
                    'remember_token' => Str::random(60),
                ])->save();

                DB::table(config('session.table', 'sessions'))
                    ->where('user_id', $user->getKey())
                    ->delete();

                AuditLog::query()->create([
                    'actor_id' => $user->getKey(),
                    'event' => 'password.reset',
                    'auditable_type' => User::class,
                    'auditable_id' => $user->getKey(),
                    'new_values' => ['result' => 'success'],
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'occurred_at' => now(),
                    'retention_expires_at' => now()->addMonths(6),
                ]);

                event(new PasswordReset($user));
            },
        );

        if ($status === Password::PasswordReset) {
            Auth::logout();

            return redirect()->route('login')->with('status', 'Mật khẩu đã được cập nhật. Bạn có thể đăng nhập bằng mật khẩu mới.');
        }

        return back()->withErrors(['email' => 'Liên kết đặt lại mật khẩu không hợp lệ hoặc đã hết hạn.']);
    }
}
