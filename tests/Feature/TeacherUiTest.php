<?php

use App\Models\Course;
use App\Models\Role;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

function teacherFixture(): array
{
    $teacher = User::factory()->create();
    $teacher->roles()->attach(Role::factory()->create(['name' => 'teacher'])->id);
    $course = Course::factory()->create();
    $course->teachers()->attach($teacher->id, ['scope' => 'editor']);
    $unit = Unit::factory()->create(['course_id' => $course->id]);

    return compact('teacher', 'course', 'unit');
}

it('shows only assigned courses in the teacher workspace', function () {
    $fixture = teacherFixture();

    $this->actingAs($fixture['teacher'])
        ->get(route('teacher.dashboard'))
        ->assertOk()
        ->assertSee($fixture['course']->title);
});

it('enforces teacher course scope and creates draft content', function () {
    $fixture = teacherFixture();
    $other = User::factory()->create();

    $this->actingAs($other)->get(route('teacher.course', $fixture['course']))->assertForbidden();
    $this->actingAs($fixture['teacher'])
        ->postJson(route('teacher.lessons.store', $fixture['course']), ['unit_id' => $fixture['unit']->id, 'title' => 'Mô hình radar', 'summary' => 'Draft'])
        ->assertCreated();
    $this->actingAs($fixture['teacher'])
        ->postJson(route('teacher.questions.store'), ['stem' => 'Khoảng cách là gì?', 'question_type' => 'numeric', 'difficulty' => 'basic'])
        ->assertCreated();
    $this->actingAs($fixture['teacher'])
        ->postJson(route('teacher.scenes.store'), ['name' => 'Radar scene', 'scene_key' => 'radar-scene', 'engine' => 'svg'])
        ->assertCreated();
});

it('rejects authoring requests from non-teachers', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('teacher.dashboard'))->assertForbidden();
});
