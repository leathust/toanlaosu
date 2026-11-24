<?php

use App\Models\Role;
use App\Models\User;
use App\UserStatus;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

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

it('redirects teacher and admin accounts to the workspace chooser', function () {
    $teacher = User::factory()->create(['email' => 'teacher@example.test']);
    $teacher->roles()->attach(Role::factory()->create(['name' => 'teacher']));

    $this->post(route('login.store'), ['email' => $teacher->email, 'password' => 'password'])
        ->assertRedirect(route('workspace', ['role' => 'teacher']));

    auth()->logout();

    $admin = User::factory()->create(['email' => 'admin@example.test']);
    $admin->roles()->attach(Role::factory()->create(['name' => 'admin']));

    $this->post(route('login.store'), ['email' => $admin->email, 'password' => 'password'])
        ->assertRedirect(route('workspace', ['role' => 'admin']));
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
