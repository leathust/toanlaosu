<?php

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;

uses(LazilyRefreshDatabase::class);

it('returns a neutral response and sends a reset notification for an existing email', function () {
    Notification::fake();
    $user = User::factory()->create(['email' => 'reset@example.test']);

    $response = $this->post(route('password.email'), ['email' => ' RESET@EXAMPLE.TEST ']);

    $response->assertRedirect(route('password.request'))
        ->assertSessionHas('status', 'Nếu email tồn tại, hướng dẫn đặt lại mật khẩu sẽ được gửi trong ít phút.');
    Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use ($user): bool {
        $message = $notification->toMail($user);
        $url = $message->actionUrl;

        return is_string($url)
            && parse_url($url, PHP_URL_HOST) === parse_url(config('app.url'), PHP_URL_HOST)
            && str_contains($url, '/reset-password/');
    });
});

it('keeps forgot password responses neutral for unknown emails', function () {
    Notification::fake();

    $response = $this->post(route('password.email'), ['email' => 'unknown@example.test']);

    $response->assertRedirect(route('password.request'))
        ->assertSessionHas('status', 'Nếu email tồn tại, hướng dẫn đặt lại mật khẩu sẽ được gửi trong ít phút.');
    Notification::assertNothingSent();
});

it('rate limits reset link requests by email and IP', function () {
    $email = 'reset-throttle@example.test';
    RateLimiter::clear('password-reset:'.$email.'|ip:127.0.0.1');

    foreach (range(1, 3) as $attempt) {
        $this->post(route('password.email'), ['email' => $email])
            ->assertRedirect(route('password.request'));
    }

    $this->post(route('password.email'), ['email' => $email])
        ->assertRedirect(route('password.request'))
        ->assertSessionHas('status');
});

it('resets a password with a valid token, revokes sessions and rejects token reuse', function () {
    $user = User::factory()->create(['email' => 'reset@example.test', 'password' => 'old-password']);
    $oldRememberToken = $user->remember_token;
    DB::table('sessions')->insert([
        'id' => 'reset-session',
        'user_id' => $user->id,
        'ip_address' => '127.0.0.1',
        'user_agent' => 'Pest',
        'payload' => 'payload',
        'last_activity' => now()->timestamp,
    ]);
    $token = Password::broker()->createToken($user);

    $this->post(route('password.update'), [
        'token' => $token,
        'email' => $user->email,
        'password' => 'new-secure-password',
        'password_confirmation' => 'new-secure-password',
    ])->assertRedirect(route('login'))
        ->assertSessionHas('status', 'Mật khẩu đã được cập nhật. Bạn có thể đăng nhập bằng mật khẩu mới.');

    $user->refresh();
    expect(Hash::check('new-secure-password', $user->password))->toBeTrue()
        ->and($user->remember_token)->not->toBe($oldRememberToken)
        ->and(DB::table('sessions')->where('id', 'reset-session')->exists())->toBeFalse()
        ->and(AuditLog::query()->where('event', 'password.reset')->where('auditable_id', $user->id)->exists())->toBeTrue();

    $this->post(route('password.update'), [
        'token' => $token,
        'email' => $user->email,
        'password' => 'another-secure-password',
        'password_confirmation' => 'another-secure-password',
    ])->assertSessionHasErrors('email');
});

it('requires a confirmed password with the minimum length', function () {
    $user = User::factory()->create(['email' => 'reset@example.test']);
    $token = Password::broker()->createToken($user);

    $this->post(route('password.update'), [
        'token' => $token,
        'email' => $user->email,
        'password' => 'short',
        'password_confirmation' => 'different',
    ])->assertSessionHasErrors(['password']);
});

it('rejects an expired reset token without changing the password', function () {
    $user = User::factory()->create(['email' => 'expired@example.test', 'password' => 'old-password']);
    $token = Password::broker()->createToken($user);
    Carbon::setTestNow(now()->addMinutes(61));

    $this->post(route('password.update'), [
        'token' => $token,
        'email' => $user->email,
        'password' => 'new-secure-password',
        'password_confirmation' => 'new-secure-password',
    ])->assertSessionHasErrors('email');

    expect(Hash::check('old-password', $user->fresh()->password))->toBeTrue();
    Carbon::setTestNow();
});
