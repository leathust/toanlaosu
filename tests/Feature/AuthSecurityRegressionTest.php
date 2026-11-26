<?php

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

uses(LazilyRefreshDatabase::class);

it('keeps state-changing authentication routes behind the web middleware', function () {
    expect(Route::getRoutes()->getByName('logout')->middleware())->toContain('web')
        ->and(Route::getRoutes()->getByName('account.password.update')->middleware())->toContain('web');
});

it('keeps unknown and wrong-password login responses generic', function () {
    $user = User::factory()->create(['email' => 'known@example.test']);
    RateLimiter::clear('login:known@example.test|ip:127.0.0.1');
    RateLimiter::clear('login:unknown@example.test|ip:127.0.0.1');

    $wrong = $this->from(route('login'))->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);
    $unknown = $this->from(route('login'))->post(route('login.store'), [
        'email' => 'unknown@example.test',
        'password' => 'wrong-password',
    ]);

    $wrong->assertRedirect(route('login'))->assertSessionHasErrors('email');
    $unknown->assertRedirect(route('login'))->assertSessionHasErrors('email');
    expect(session('errors')->first('email'))->toBe('Email hoặc mật khẩu không đúng.');
});

it('prevents role escalation across teacher and admin boundaries', function () {
    $student = User::factory()->create();
    $student->roles()->attach(Role::factory()->create(['name' => 'student']));

    $this->actingAs($student)->get(route('teacher.dashboard'))->assertForbidden();
    $this->actingAs($student)->get(route('admin.dashboard'))->assertForbidden();
});

it('rotates the session identifier after successful login', function () {
    $user = User::factory()->create();
    $user->roles()->attach(Role::factory()->create(['name' => 'student']));
    $before = $this->app['session.store']->getId();

    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password'])
        ->assertRedirect(route('student.dashboard'));

    expect($this->app['session.store']->getId())->not->toBe($before);
});
