<?php

use App\Models\ChatThread;
use App\Models\Exam;
use App\Models\ExamQuestion;
use App\Models\ExamVersion;
use App\Models\QuestionOption;
use App\Models\QuestionVersion;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

function lifecycleExamFixture(): array
{
    $user = User::factory()->create();
    $exam = Exam::factory()->create(['status' => 'published', 'starts_at' => now()->subMinute(), 'ends_at' => now()->addHour(), 'max_attempts' => 1]);
    $version = ExamVersion::factory()->create(['exam_id' => $exam->id, 'status' => 'published', 'published_at' => now(), 'total_points' => 1]);
    $questionVersion = QuestionVersion::factory()->create(['scoring_policy' => ['points' => 1], 'answer_config' => ['type' => 'single_choice']]);
    QuestionOption::factory()->create(['question_version_id' => $questionVersion->id, 'option_key' => 'A', 'is_correct' => true]);
    ExamQuestion::factory()->create(['exam_version_id' => $version->id, 'question_version_id' => $questionVersion->id, 'sort_order' => 1, 'points' => 1]);

    return compact('user', 'exam', 'version');
}

it('grades an answer on submit and records a graded timestamp', function () {
    $fixture = lifecycleExamFixture();
    $this->actingAs($fixture['user'])->get(route('student.exam.show', $fixture['exam']));
    $attempt = $fixture['version']->attempts()->first();
    $question = $attempt->questions()->first();

    $this->actingAs($fixture['user'])->postJson(route('student.exam.answers.save', $attempt), ['exam_attempt_question_id' => $question->id, 'answer' => 'A', 'save_version' => 0])->assertOk();
    $this->actingAs($fixture['user'])->postJson(route('student.exam.submit', $attempt))->assertOk()->assertJsonPath('idempotent', false)->assertJsonPath('data.score', '1.0000');

    expect($attempt->fresh()->graded_at)->not->toBeNull()
        ->and($question->answer()->first()->is_correct)->toBeTrue();
});

it('rejects autosave and submit after the server deadline', function () {
    $fixture = lifecycleExamFixture();
    $this->actingAs($fixture['user'])->get(route('student.exam.show', $fixture['exam']));
    $attempt = $fixture['version']->attempts()->first();
    $question = $attempt->questions()->first();
    $attempt->update(['deadline_at' => now()->subSecond()]);

    $this->actingAs($fixture['user'])->postJson(route('student.exam.answers.save', $attempt), ['exam_attempt_question_id' => $question->id, 'answer' => 'A', 'save_version' => 0])->assertConflict();
    $this->actingAs($fixture['user'])->postJson(route('student.exam.submit', $attempt))->assertConflict();
});

it('enforces max attempts after a submitted attempt', function () {
    $fixture = lifecycleExamFixture();
    $this->actingAs($fixture['user'])->get(route('student.exam.show', $fixture['exam']));
    $attempt = $fixture['version']->attempts()->first();
    $this->actingAs($fixture['user'])->postJson(route('student.exam.submit', $attempt))->assertOk();

    $this->actingAs($fixture['user'])->get(route('student.exam.show', $fixture['exam']))->assertConflict();
});

it('disables AI tutor and scene commands in exam context', function () {
    $user = User::factory()->create();
    $thread = ChatThread::factory()->create(['user_id' => $user->id, 'mode' => 'exam']);

    $this->actingAs($user)
        ->postJson(route('student.chat.send'), ['content' => 'Giải giúp tôi câu này', 'context' => 'exam'])
        ->assertForbidden();

    $this->actingAs($user)
        ->postJson(route('student.chat.command', $thread), ['command' => 'pause'])
        ->assertForbidden();
});
