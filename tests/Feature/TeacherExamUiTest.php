<?php

use App\Models\Course;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\ExamQuestion;
use App\Models\ExamVersion;
use App\Models\QuestionVersion;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

function teacherExamFixture(): array
{
    $teacher = User::factory()->create();
    $teacher->roles()->attach(Role::factory()->create(['name' => 'teacher'])->id);
    $course = Course::factory()->create();
    $course->teachers()->attach($teacher->id, ['scope' => 'editor']);
    $exam = Exam::factory()->create(['course_id' => $course->id]);
    $version = ExamVersion::factory()->create(['exam_id' => $exam->id, 'status' => 'published']);
    $questionVersion = QuestionVersion::factory()->create(['stem' => 'Tính khoảng cách đến radar.']);
    ExamQuestion::factory()->create(['exam_version_id' => $version->id, 'question_version_id' => $questionVersion->id]);

    return compact('teacher', 'course', 'exam', 'version');
}

it('lists only assigned teacher exams and shows operations', function () {
    $fixture = teacherExamFixture();

    $this->actingAs($fixture['teacher'])->get(route('teacher.exams'))->assertOk()->assertSee($fixture['exam']->title);
    $this->actingAs($fixture['teacher'])->get(route('teacher.exam', $fixture['exam']))->assertOk()->assertSee('Question blueprint');
});

it('exports escaped latex and renders learning report within scope', function () {
    $fixture = teacherExamFixture();
    $student = User::factory()->create();
    ExamAttempt::factory()->create(['exam_version_id' => $fixture['version']->id, 'user_id' => $student->id, 'status' => 'submitted', 'score' => 8, 'max_score' => 10]);

    $this->actingAs($fixture['teacher'])->get(route('teacher.exam.export-latex', $fixture['exam']))->assertOk()->assertHeader('Content-Type', 'application/x-tex')->assertSee('Tính khoảng cách');
    $this->actingAs($fixture['teacher'])->get(route('teacher.exam.report', $fixture['exam']))->assertOk()->assertSee('Learning report')->assertSee('1');
});

it('rejects exam operations outside the teacher course scope', function () {
    $fixture = teacherExamFixture();
    $other = User::factory()->create();

    $this->actingAs($other)->get(route('teacher.exam', $fixture['exam']))->assertForbidden();
    $this->actingAs($other)->get(route('teacher.exam.report', $fixture['exam']))->assertForbidden();
});
