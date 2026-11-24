<?php

use App\Models\Exam;
use App\Models\ExamQuestion;
use App\Models\ExamVersion;
use App\Models\QuestionVersion;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

function examFixture(): array
{
    $user = User::factory()->create();
    $exam = Exam::factory()->create([
        'status' => 'published',
        'starts_at' => now()->subMinute(),
        'ends_at' => now()->addHour(),
        'max_attempts' => 1,
    ]);
    $version = ExamVersion::factory()->create([
        'exam_id' => $exam->id,
        'status' => 'published',
        'published_at' => now(),
        'total_points' => 1,
    ]);
    $questionVersion = QuestionVersion::factory()->create(['scoring_policy' => ['points' => 1]]);
    $question = ExamQuestion::factory()->create([
        'exam_version_id' => $version->id,
        'question_version_id' => $questionVersion->id,
        'sort_order' => 1,
        'points' => 1,
    ]);

    return compact('user', 'exam', 'version', 'question');
}

it('starts an exam with a server-derived deadline and question snapshot', function () {
    $fixture = examFixture();

    $this->actingAs($fixture['user'])
        ->get(route('student.exam.show', $fixture['exam']))
        ->assertOk()
        ->assertSee('Còn lại')
        ->assertSee($fixture['question']->questionVersion->stem);
});

it('autosaves answers with optimistic save versioning', function () {
    $fixture = examFixture();
    $response = $this->actingAs($fixture['user'])->get(route('student.exam.show', $fixture['exam']));
    $attempt = $fixture['version']->attempts()->first();
    $attemptQuestion = $attempt->questions()->first();

    $this->actingAs($fixture['user'])
        ->postJson(route('student.exam.answers.save', $attempt), [
            'exam_attempt_question_id' => $attemptQuestion->id,
            'answer' => 'A',
            'save_version' => 0,
        ])
        ->assertOk()
        ->assertJsonPath('save_version', 1);

    $this->actingAs($fixture['user'])
        ->postJson(route('student.exam.answers.save', $attempt), [
            'exam_attempt_question_id' => $attemptQuestion->id,
            'answer' => 'B',
            'save_version' => 0,
        ])
        ->assertConflict();
});

it('submits an exam idempotently and protects another user', function () {
    $fixture = examFixture();
    $this->actingAs($fixture['user'])->get(route('student.exam.show', $fixture['exam']));
    $attempt = $fixture['version']->attempts()->first();

    $this->actingAs($fixture['user'])->postJson(route('student.exam.submit', $attempt))->assertOk()->assertJsonPath('idempotent', false);
    $this->actingAs($fixture['user'])->postJson(route('student.exam.submit', $attempt))->assertOk()->assertJsonPath('idempotent', true);
    expect($attempt->fresh()->status)->toBe('submitted');

    $this->actingAs(User::factory()->create())->postJson(route('student.exam.submit', $attempt))->assertForbidden();
});

it('respects result and answer release policies in review', function () {
    $fixture = examFixture();
    $this->actingAs($fixture['user'])->get(route('student.exam.show', $fixture['exam']));
    $attempt = $fixture['version']->attempts()->first();
    $attempt->update(['status' => 'submitted', 'submitted_at' => now(), 'score' => 1]);
    $fixture['exam']->update(['result_release_at' => now()->addHour(), 'answer_release_at' => now()->addHour()]);

    $this->actingAs($fixture['user'])
        ->get(route('student.exam.result', $attempt))
        ->assertOk()
        ->assertSee('Kết quả chưa được công bố')
        ->assertDontSee('Điểm</p>');

    $fixture['exam']->update(['result_release_at' => now()->subMinute(), 'answer_release_at' => now()->subMinute()]);
    $this->actingAs($fixture['user'])
        ->get(route('student.exam.result', $attempt))
        ->assertOk()
        ->assertSee('Review câu hỏi');
});

it('lists only the authenticated user exam history', function () {
    $fixture = examFixture();
    $this->actingAs($fixture['user'])->get(route('student.exam.show', $fixture['exam']));
    $other = User::factory()->create();
    $this->actingAs($other)->get(route('student.exam.history', $fixture['exam']))->assertOk()->assertSee('Chưa có lần thi nào.');
    $this->actingAs($fixture['user'])->get(route('student.exam.history', $fixture['exam']))->assertOk()->assertSee('Lần thi 1');
});
