<?php

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\UserStatus;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

it('casts account status and detects locked accounts', function () {
    $activeUser = User::factory()->create();
    $suspendedUser = User::factory()->create(['status' => UserStatus::Suspended]);
    $lockedUser = User::factory()->create(['locked_at' => now()]);

    expect($activeUser->status)->toBe(UserStatus::Active)
        ->and($activeUser->isActive())->toBeTrue()
        ->and($suspendedUser->isActive())->toBeFalse()
        ->and($lockedUser->isActive())->toBeFalse();
});

it('seeds system roles and their permissions', function () {
    $this->seed();

    $admin = Role::query()->where('name', 'admin')->firstOrFail();
    $teacher = Role::query()->where('name', 'teacher')->firstOrFail();
    $student = Role::query()->where('name', 'student')->firstOrFail();

    expect(Role::query()->where('is_system', true)->count())->toBe(3)
        ->and($admin->permissions)->toHaveCount(Permission::query()->count())
        ->and($teacher->permissions->pluck('name')->all())->toEqualCanonicalizing([
            'view_courses',
            'manage_courses',
            'manage_questions',
            'manage_exams',
            'export_latex',
            'use_ai_tutor',
        ])
        ->and($student->permissions->pluck('name')->all())->toEqualCanonicalizing([
            'view_courses',
            'take_exams',
            'use_ai_tutor',
        ]);
});

it('assigns roles to users through the relationship', function () {
    $user = User::factory()->create();
    $role = Role::factory()->create();

    $user->roles()->attach($role);

    expect($user->fresh()->roles)->toHaveCount(1)
        ->and($role->fresh()->users->first()->is($user))->toBeTrue();
});

it('prevents duplicate role assignments', function () {
    $user = User::factory()->create();
    $role = Role::factory()->create();

    $user->roles()->attach($role);

    expect(fn () => $user->roles()->attach($role))->toThrow(QueryException::class);
});
