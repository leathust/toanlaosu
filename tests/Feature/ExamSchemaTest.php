<?php

use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\ExamAttemptAnswer;
use App\Models\ExamAttemptQuestion;
use App\Models\ExamEvent;
use App\Models\ExamQuestion;
use App\Models\ExamVersion;
use App\Models\QuestionVersion;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

it('keeps an immutable exam question snapshot for an attempt', function () {
    $exam = Exam::factory()->create();
    $version = ExamVersion::factory()->for($exam)->create();
    $questionVersion = QuestionVersion::factory()->create();
    $examQuestion = ExamQuestion::factory()->for($version)->for($questionVersion)->create([
        'sort_order' => 1,
        'points' => 2,
    ]);
    $attempt = ExamAttempt::factory()->for($version)->for(User::factory())->create();
    $attemptQuestion = ExamAttemptQuestion::factory()
        ->for($attempt, 'attempt')
        ->for($examQuestion)
        ->for($questionVersion)
        ->create([
            'sort_order' => 1,
            'points' => 2,
            'snapshot' => ['stem' => 'Frozen stem', 'options' => ['A', 'B']],
        ]);

    expect($attempt->fresh()->questions->first()->is($attemptQuestion))->toBeTrue()
        ->and($attemptQuestion->fresh()->snapshot)->toMatchArray(['stem' => 'Frozen stem'])
        ->and($attemptQuestion->fresh()->questionVersion->is($questionVersion))->toBeTrue();
});

it('allows one autosave answer per attempt question and records events', function () {
    $attemptQuestion = ExamAttemptQuestion::factory()->create();
    $answer = ExamAttemptAnswer::factory()->for($attemptQuestion)->create([
        'save_version' => 3,
        'answer' => ['value' => 'C'],
    ]);
    $event = ExamEvent::factory()->for($attemptQuestion->attempt)->create([
        'event_type' => 'answer_saved',
        'metadata' => ['save_version' => 3],
    ]);

    expect($attemptQuestion->fresh()->answer->is($answer))->toBeTrue()
        ->and($answer->fresh()->answer)->toBe(['value' => 'C'])
        ->and($event->fresh()->examAttempt->is($attemptQuestion->attempt))->toBeTrue();

    expect(fn () => ExamAttemptAnswer::factory()->for($attemptQuestion)->create())
        ->toThrow(QueryException::class);
});

it('enforces unique attempt numbers within an exam version and learner', function () {
    $version = ExamVersion::factory()->create();
    $user = User::factory()->create();

    ExamAttempt::factory()->for($version)->for($user)->create(['attempt_number' => 1]);

    expect(fn () => ExamAttempt::factory()->for($version)->for($user)->create(['attempt_number' => 1]))
        ->toThrow(QueryException::class);
});
