<?php

use App\Models\ChatThread;
use App\Models\Course;
use App\Models\Role;
use App\Models\User;
use App\UserStatus;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

function accessUserWithRole(string $role, array $attributes = []): User
{
    $user = User::factory()->create($attributes);
    $user->roles()->attach(Role::factory()->create(['name' => $role]));

    return $user;
}

it('logs out an inactive authenticated account before workspace access', function () {
    $user = accessUserWithRole('teacher', ['status' => UserStatus::Suspended]);

    $this->actingAs($user)
        ->get(route('teacher.dashboard'))
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors('email');

    $this->assertGuest();
});

it('enforces workspace roles before controller execution', function () {
    $student = accessUserWithRole('student');
    $teacher = accessUserWithRole('teacher');

    $this->actingAs($student)->get(route('teacher.dashboard'))->assertForbidden();
    $this->actingAs($teacher)->get(route('admin.dashboard'))->assertForbidden();
});

it('rejects a revoked teacher scope on course detail', function () {
    $teacher = accessUserWithRole('teacher');
    $course = Course::factory()->create();
    $course->teachers()->attach($teacher->id, ['scope' => 'editor', 'revoked_at' => now()]);

    $this->actingAs($teacher)
        ->get(route('teacher.course', $course))
        ->assertForbidden();
});

it('returns JSON forbidden for an inactive account on a protected action', function () {
    $user = accessUserWithRole('student', ['locked_at' => now()]);
    $thread = ChatThread::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->postJson(route('student.chat.command', $thread), ['command' => 'pause'])
        ->assertForbidden()
        ->assertJsonPath('message', 'Tài khoản hiện không thể sử dụng.');

    $this->assertGuest();
});
