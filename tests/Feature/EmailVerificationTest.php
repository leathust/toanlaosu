<?php

use App\Models\AuditLog;
use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;

uses(LazilyRefreshDatabase::class);

it('shows the verification notice and can resend a throttled verification email', function () {
    Notification::fake();
    $user = User::factory()->unverified()->create();

    $this->actingAs($user)->get(route('verification.notice'))
        ->assertOk()
        ->assertSee('Kiểm tra hộp thư của bạn');

    $this->actingAs($user)->post(route('verification.send'))
        ->assertRedirect()
        ->assertSessionHas('status', 'verification-link-sent');
    Notification::assertSentTo($user, VerifyEmail::class);
});

it('fulfills only a valid signed verification URL', function () {
    $user = User::factory()->unverified()->create();
    $url = URL::temporarySignedRoute('verification.verify', now()->addHour(), [
        'id' => $user->id,
        'hash' => sha1($user->getEmailForVerification()),
    ]);

    $this->actingAs($user)->get($url)
        ->assertRedirect(route('account.settings'))
        ->assertSessionHas('status', 'Email đã được xác minh thành công.');
    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();

    $this->actingAs(User::factory()->unverified()->create())
        ->get(route('verification.verify', ['id' => $user->id, 'hash' => 'invalid']))
        ->assertForbidden();
});

it('throttles repeated verification email requests', function () {
    $user = User::factory()->unverified()->create();
    RateLimiter::clear('verification:'.$user->id.'|ip:127.0.0.1');

    foreach (range(1, 3) as $attempt) {
        $this->actingAs($user)->post(route('verification.send'))
            ->assertRedirect();
    }

    $this->actingAs($user)->post(route('verification.send'))
        ->assertTooManyRequests();
});

it('redirects unverified users away from protected learning and operations routes', function () {
    $student = User::factory()->unverified()->create();
    $teacher = User::factory()->unverified()->create();
    $teacher->roles()->attach(Role::factory()->create(['name' => 'teacher']));

    $this->actingAs($student)->get(route('student.chat'))
        ->assertRedirect(route('verification.notice'));
    $this->actingAs($teacher)->get(route('teacher.dashboard'))
        ->assertRedirect(route('verification.notice'));
});

it('requires current password to change email and sends verification again', function () {
    Notification::fake();
    $user = User::factory()->create(['email' => 'old@example.test']);

    $this->actingAs($user)->withSession(['auth.password_confirmed_at' => now()->unix()])->put(route('account.email.update'), [
        'email' => 'new@example.test',
        'current_password' => 'password',
    ])->assertRedirect();

    $user->refresh();
    expect($user->email)->toBe('new@example.test')
        ->and($user->email_verified_at)->toBeNull()
        ->and(AuditLog::query()->where('event', 'account.email_changed')->exists())->toBeTrue();
    Notification::assertSentTo($user, VerifyEmail::class);
});

it('changes password, revokes other sessions and writes an audit event', function () {
    $user = User::factory()->create(['password' => 'old-password']);
    $this->actingAs($user);
    DB::table('sessions')->insert([
        'id' => 'other-account-session',
        'user_id' => $user->id,
        'ip_address' => '127.0.0.1',
        'user_agent' => 'Pest',
        'payload' => 'payload',
        'last_activity' => now()->timestamp,
    ]);

    $this->withSession(['auth.password_confirmed_at' => now()->unix()])->put(route('account.password.update'), [
        'current_password' => 'old-password',
        'password' => 'new-account-password',
        'password_confirmation' => 'new-account-password',
    ])->assertRedirect();

    expect(Hash::check('new-account-password', $user->fresh()->password))->toBeTrue()
        ->and(DB::table('sessions')->where('id', 'other-account-session')->exists())->toBeFalse()
        ->and(AuditLog::query()->where('event', 'account.password_changed')->exists())->toBeTrue();
});
