<?php

namespace App\Providers;

use App\Contracts\AI\GenerativeProvider;
use App\Models\User;
use App\Services\AI\LocalTutorProvider;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(GenerativeProvider::class, LocalTutorProvider::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('login', function (Request $request): Limit {
            $email = Str::lower(trim((string) $request->input('email')));

            return Limit::perMinute(6)
                ->by('login:'.$email.'|ip:'.$request->ip())
                ->response(function (Request $request, array $headers) {
                    return redirect()->route('login')
                        ->withErrors(['email' => 'Bạn đã thử đăng nhập quá nhiều lần. Vui lòng thử lại sau ít phút.'])
                        ->onlyInput('email')
                        ->withHeaders($headers);
                });
        });

        RateLimiter::for('password-reset', function (Request $request): Limit {
            $email = Str::lower(trim((string) $request->input('email')));

            return Limit::perMinute(3)
                ->by('password-reset:'.$email.'|ip:'.$request->ip())
                ->response(function (Request $request, array $headers) {
                    return redirect()->route('password.request')
                        ->with('status', 'Nếu email tồn tại, hướng dẫn đặt lại mật khẩu sẽ được gửi trong ít phút.')
                        ->withHeaders($headers);
                });
        });

        RateLimiter::for('invite', function (Request $request): Limit {
            return Limit::perMinute(5)->by('invite:'.hash('sha256', (string) $request->route('token')).'|ip:'.$request->ip());
        });

        RateLimiter::for('verification', function (Request $request): Limit {
            return Limit::perMinute(3)->by('verification:'.$request->user()?->getAuthIdentifier().'|ip:'.$request->ip());
        });

        RateLimiter::for('admin-operations', function (Request $request): Limit {
            return Limit::perMinute(30)->by('admin-operations:'.$request->user()?->getAuthIdentifier().'|ip:'.$request->ip());
        });

        ResetPassword::createUrlUsing(fn (User $user, string $token): string => route('password.reset', [
            'token' => $token,
            'email' => $user->email,
        ]));

        VerifyEmail::toMailUsing(function (User $user, string $url) {
            return (new MailMessage)
                ->subject('Xác minh email Toán Lão Sư')
                ->greeting('Xin chào '.$user->name.',')
                ->line('Hãy xác minh địa chỉ email để tiếp tục sử dụng các tính năng được bảo vệ.')
                ->action('Xác minh email', $url)
                ->line('Nếu bạn không yêu cầu thay đổi này, hãy bỏ qua email.');
        });
    }
}
