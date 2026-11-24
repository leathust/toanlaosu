<?php

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\LearningTarget;
use App\Models\Track;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

it('builds an ordered curriculum with learning targets', function () {
    $track = Track::factory()->create();
    $course = Course::factory()->create();
    $target = LearningTarget::factory()->create(['code' => 'THPT']);
    $unit = Unit::factory()->for($course)->create(['sort_order' => 1]);
    $lesson = $unit->lessons()->create([
        'slug' => 'lesson-one',
        'title' => 'Lesson one',
        'status' => 'draft',
        'sort_order' => 1,
    ]);

    $track->courses()->attach($course, ['sort_order' => 1]);
    $course->learningTargets()->attach($target);

    $course->load(['tracks', 'learningTargets', 'units.lessons']);

    expect($course->tracks)->toHaveCount(1)
        ->and($course->learningTargets->first()->is($target))->toBeTrue()
        ->and($course->units->first()->is($unit))->toBeTrue()
        ->and($course->units->first()->lessons->first()->is($lesson))->toBeTrue();
});

it('assigns a teacher with a scoped course access grant', function () {
    $course = Course::factory()->create();
    $teacher = User::factory()->create();
    $admin = User::factory()->create();

    $course->teachers()->attach($teacher, [
        'scope' => 'editor',
        'assigned_by' => $admin->id,
    ]);

    $assignment = $course->fresh()->teachers->first()->pivot;

    expect($assignment->scope)->toBe('editor')
        ->and($assignment->assigned_by)->toBe($admin->id)
        ->and($teacher->fresh()->teachingCourses->first()->is($course))->toBeTrue();
});

it('supports multiple enrollment periods for one course and learner', function () {
    $course = Course::factory()->create();
    $student = User::factory()->create();

    $firstPeriod = Enrollment::factory()->for($course)->for($student)->create(['period_number' => 1]);
    $secondPeriod = Enrollment::factory()->for($course)->for($student)->create(['period_number' => 2]);

    expect($student->fresh()->enrollments)->toHaveCount(2)
        ->and($course->fresh()->enrollments->pluck('period_number')->all())->toEqualCanonicalizing([1, 2])
        ->and($firstPeriod->course->is($course))->toBeTrue()
        ->and($secondPeriod->user->is($student))->toBeTrue();
});

it('prevents duplicate enrollment periods for the same learner and course', function () {
    $course = Course::factory()->create();
    $student = User::factory()->create();

    Enrollment::factory()->for($course)->for($student)->create(['period_number' => 1]);

    expect(fn () => Enrollment::factory()->for($course)->for($student)->create(['period_number' => 1]))
        ->toThrow(QueryException::class);
});
