<?php

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\LessonBlock;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\QuestionVersion;
use App\Models\Unit;
use App\Models\User;
use App\Models\UserAttempt;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

function learningFixture(): array
{
    $user = User::factory()->create();
    $course = Course::factory()->create();
    Enrollment::factory()->create(['user_id' => $user->id, 'course_id' => $course->id]);
    $unit = Unit::factory()->create(['course_id' => $course->id]);
    $lesson = Lesson::factory()->create(['unit_id' => $unit->id]);
    $block = LessonBlock::factory()->create(['lesson_id' => $lesson->id]);

    return compact('user', 'course', 'unit', 'lesson', 'block');
}

it('loads an authenticated dashboard from active enrollments', function () {
    $fixture = learningFixture();

    $this->actingAs($fixture['user'])
        ->get(route('student.dashboard'))
        ->assertOk()
        ->assertSee($fixture['user']->name)
        ->assertSee('1 khóa');
});

it('records block completion idempotently for an enrolled student', function () {
    $fixture = learningFixture();

    $this->actingAs($fixture['user'])
        ->postJson(route('student.lesson.progress', $fixture['block']))
        ->assertOk()
        ->assertJsonPath('data.status', 'completed');

    $this->actingAs($fixture['user'])
        ->postJson(route('student.lesson.progress', $fixture['block']))
        ->assertOk();

    expect($fixture['user']->lessonBlockProgress()->where('lesson_block_id', $fixture['block']->id)->count())->toBe(1);
});

it('rejects progress updates without course enrollment', function () {
    $fixture = learningFixture();
    $otherUser = User::factory()->create();

    $this->actingAs($otherUser)
        ->postJson(route('student.lesson.progress', $fixture['block']))
        ->assertForbidden();
});

it('grades and persists a submitted answer without negative scores', function () {
    $fixture = learningFixture();
    $question = Question::factory()->create();
    $version = QuestionVersion::factory()->create([
        'question_id' => $question->id,
        'answer_config' => ['type' => 'single_choice'],
        'scoring_policy' => ['points' => 2],
        'allow_negative' => false,
    ]);
    QuestionOption::factory()->create(['question_version_id' => $version->id, 'option_key' => 'A', 'is_correct' => true]);

    $response = $this->actingAs($fixture['user'])
        ->postJson(route('student.practice.attempts.store'), [
            'question_version_id' => $version->id,
            'lesson_block_id' => $fixture['block']->id,
            'answer' => 'A',
        ]);

    $response->assertCreated()->assertJsonPath('data.is_correct', true)->assertJsonPath('data.score', '2.0000');
    expect(UserAttempt::query()->where('user_id', $fixture['user']->id)->value('attempt_number'))->toBe(1);
});
