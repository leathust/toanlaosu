<?php

use App\Models\Role;
use App\Models\User;
use App\UserStatus;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;

uses(LazilyRefreshDatabase::class);

it('authenticates an active student and redirects to the student workspace', function () {
    $user = User::factory()->create(['email' => 'student@example.test']);
    $user->roles()->attach(Role::factory()->create(['name' => 'student']));

    $response = $this->post(route('login.store'), [
        'email' => 'student@example.test',
        'password' => 'password',
    ]);

    $response->assertRedirect(route('student.dashboard'));
    $this->assertAuthenticatedAs($user);
});

it('redirects teacher and admin accounts to their authorized workspace', function () {
    $teacher = User::factory()->create(['email' => 'teacher@example.test']);
    $teacher->roles()->attach(Role::factory()->create(['name' => 'teacher']));

    $this->post(route('login.store'), ['email' => $teacher->email, 'password' => 'password'])
        ->assertRedirect(route('teacher.dashboard'));

    auth()->logout();

    $admin = User::factory()->create(['email' => 'admin@example.test']);
    $admin->roles()->attach(Role::factory()->create(['name' => 'admin']));

    $this->post(route('login.store'), ['email' => $admin->email, 'password' => 'password'])
        ->assertRedirect(route('admin.dashboard'));
});

it('sends multi-role accounts to the server-owned workspace chooser', function () {
    $user = User::factory()->create(['email' => 'multi@example.test']);
    $user->roles()->attach([
        Role::factory()->create(['name' => 'student'])->id,
        Role::factory()->create(['name' => 'teacher'])->id,
    ]);

    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password'])
        ->assertRedirect(route('workspace'));

    $this->get(route('workspace'))
        ->assertOk()
        ->assertSee('Học sinh')
        ->assertSee('Giáo viên')
        ->assertDontSee(route('admin.dashboard'));
});

it('shows a no-access state for an active account without a usable role', function () {
    $user = User::factory()->create(['email' => 'norole@example.test']);

    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password'])
        ->assertRedirect(route('workspace'));

    $this->get(route('workspace'))
        ->assertOk()
        ->assertSee('Tài khoản chưa được cấp workspace')
        ->assertSee('chưa có role phù hợp');
});

it('normalizes the login email and records login telemetry', function () {
    $user = User::factory()->create(['email' => 'student@example.test']);
    $user->roles()->attach(Role::factory()->create(['name' => 'student']));

    $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.10'])
        ->post(route('login.store'), ['email' => '  STUDENT@EXAMPLE.TEST ', 'password' => 'password'])
        ->assertRedirect(route('student.dashboard'));

    $user->refresh();

    expect($user->last_login_at)->not->toBeNull()
        ->and($user->last_login_ip)->toBe('192.0.2.10');
});

it('rejects an external intended redirect and keeps protected pages behind login', function () {
    $user = User::factory()->create(['email' => 'student@example.test']);
    $user->roles()->attach(Role::factory()->create(['name' => 'student']));

    $this->get(route('student.dashboard'))
        ->assertRedirect(route('login'));

    $this->withSession(['url.intended' => 'https://evil.example.test/admin'])
        ->post(route('login.store'), ['email' => $user->email, 'password' => 'password'])
        ->assertRedirect(route('student.dashboard'));
});

it('rotates the session and supports an explicit remember device choice', function () {
    $user = User::factory()->create(['email' => 'student@example.test']);
    $user->roles()->attach(Role::factory()->create(['name' => 'student']));
    $sessionId = $this->app['session.store']->getId();

    $response = $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
        'remember' => true,
    ]);

    $response->assertRedirect(route('student.dashboard'));
    expect($this->app['session.store']->getId())->not->toBe($sessionId);
    expect(collect($response->headers->getCookies())->map(fn ($cookie): string => $cookie->getName())->contains(fn (string $name): bool => str_starts_with($name, 'remember_web_')))->toBeTrue();
});

it('limits repeated login attempts by normalized email and IP', function () {
    $email = 'throttle@example.test';
    RateLimiter::clear('login:'.$email.'|ip:127.0.0.1');

    foreach (range(1, 6) as $attempt) {
        $this->post(route('login.store'), ['email' => $email, 'password' => 'wrong-password'])
            ->assertSessionHasErrors('email');
    }

    $this->post(route('login.store'), ['email' => $email, 'password' => 'wrong-password'])
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors('email');
});

it('rejects suspended and locked accounts', function () {
    $suspended = User::factory()->create(['email' => 'suspended@example.test', 'status' => UserStatus::Suspended]);

    $this->post(route('login.store'), ['email' => $suspended->email, 'password' => 'password'])
        ->assertSessionHasErrors('email');
    $this->assertGuest();

    $locked = User::factory()->create(['email' => 'locked@example.test', 'locked_at' => now()]);

    $this->post(route('login.store'), ['email' => $locked->email, 'password' => 'password'])
        ->assertSessionHasErrors('email');
    $this->assertGuest();
});

it('logs out and invalidates the session', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('logout'))
        ->assertRedirect(route('login'));

    $this->assertGuest();
});
