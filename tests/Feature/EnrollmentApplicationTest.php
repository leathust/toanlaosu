<?php

use App\Actions\EnrollStudent;
use App\Models\AuditLog;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

function enrollmentTeacher(): User
{
    $teacher = User::factory()->create();
    $teacher->roles()->attach(Role::factory()->create(['name' => 'teacher']));

    return $teacher;
}

it('creates an enrollment and audit event atomically', function () {
    $teacher = enrollmentTeacher();
    $course = Course::factory()->create();
    $course->teachers()->attach($teacher, ['scope' => 'owner', 'assigned_by' => $teacher->id]);
    $student = User::factory()->create();

    $enrollment = app(EnrollStudent::class)->execute($teacher, $course, $student, [
        'period_number' => 1,
        'starts_at' => now()->subDay(),
        'ends_at' => now()->addMonth(),
    ]);

    expect($enrollment->exists)->toBeTrue()
        ->and($enrollment->granted_by)->toBe($teacher->id)
        ->and(AuditLog::query()->where('event', 'enrollment.created')->count())->toBe(1)
        ->and($enrollment->isActiveAt())->toBeTrue();
});

it('keeps repeated enrollment requests idempotent per course period', function () {
    $teacher = enrollmentTeacher();
    $course = Course::factory()->create();
    $course->teachers()->attach($teacher, ['scope' => 'editor', 'assigned_by' => $teacher->id]);
    $student = User::factory()->create();
    $action = app(EnrollStudent::class);
    $attributes = ['period_number' => 2];

    $first = $action->execute($teacher, $course, $student, $attributes);
    $second = $action->execute($teacher, $course, $student, $attributes);

    expect($second->is($first))->toBeTrue()
        ->and(Enrollment::query()->where('course_id', $course->id)->where('user_id', $student->id)->count())->toBe(1)
        ->and(AuditLog::query()->where('event', 'enrollment.created')->count())->toBe(1);
});

it('filters enrollments by active period and revocation', function () {
    $course = Course::factory()->create();
    $student = User::factory()->create();
    $active = Enrollment::factory()->for($course)->for($student)->create([
        'starts_at' => now()->subDay(),
        'ends_at' => now()->addDay(),
    ]);
    Enrollment::factory()->for($course)->for($student)->create([
        'period_number' => 2,
        'starts_at' => now()->subDays(3),
        'ends_at' => now()->addDay(),
        'revoked_at' => now(),
    ]);
    Enrollment::factory()->for($course)->for($student)->create([
        'period_number' => 3,
        'starts_at' => now()->addDay(),
        'ends_at' => now()->addDays(5),
    ]);

    expect($student->activeEnrollments()->pluck('id')->all())->toBe([$active->id])
        ->and($course->activeEnrollments()->count())->toBe(1);
});

it('validates enrollment input at the HTTP boundary', function () {
    $teacher = enrollmentTeacher();
    $course = Course::factory()->create();
    $course->teachers()->attach($teacher, ['scope' => 'owner', 'assigned_by' => $teacher->id]);
    $student = User::factory()->create();

    $this->actingAs($teacher)
        ->postJson(route('teacher.courses.enrollments.store', $course), [
            'user_id' => $student->id,
            'period_number' => 0,
            'starts_at' => now()->addDay()->toDateString(),
            'ends_at' => now()->subDay()->toDateString(),
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['period_number', 'ends_at']);
});
