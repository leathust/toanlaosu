<?php

use App\Models\LessonBlock;
use App\Models\LessonBlockProgress;
use App\Models\LessonMaterial;
use App\Models\LessonVideo;
use App\Models\MaterialView;
use App\Models\QuestionVersion;
use App\Models\Skill;
use App\Models\User;
use App\Models\UserAttempt;
use App\Models\UserMastery;
use App\Models\VideoProgress;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

it('stores one progress record per user and lesson block', function () {
    $user = User::factory()->create();
    $block = LessonBlock::factory()->create();
    $progress = LessonBlockProgress::factory()->for($user)->for($block)->create([
        'status' => 'completed',
        'completed_at' => now(),
    ]);

    expect($progress->fresh()->status)->toBe('completed')
        ->and($block->fresh()->progress)->toHaveCount(1)
        ->and($user->fresh()->lessonBlockProgress->first()->is($progress))->toBeTrue();

    expect(fn () => LessonBlockProgress::factory()->for($user)->for($block)->create())
        ->toThrow(QueryException::class);
});

it('tracks video progress and anonymous material views separately', function () {
    $user = User::factory()->create();
    $video = LessonVideo::factory()->create();
    $material = LessonMaterial::factory()->create();
    $progress = VideoProgress::factory()->for($user)->for($video)->create([
        'completion_percent' => 75.50,
        'watched_ranges' => [[10, 45]],
    ]);
    $anonymousView = MaterialView::factory()->for($material, 'lessonMaterial')->create(['user_id' => null]);

    expect($progress->fresh()->completion_percent)->toBe('75.50')
        ->and($progress->fresh()->watched_ranges)->toBe([[10, 45]])
        ->and($anonymousView->fresh()->user_id)->toBeNull()
        ->and($material->fresh()->views)->toHaveCount(1);
});

it('stores attempts and one mastery record per skill', function () {
    $user = User::factory()->create();
    $version = QuestionVersion::factory()->create();
    $skill = Skill::factory()->create();
    $attempt = UserAttempt::factory()->for($user)->for($version)->create([
        'answer' => ['value' => 'B'],
        'is_correct' => false,
        'score' => 0,
    ]);
    $mastery = UserMastery::factory()->for($user)->for($skill)->create([
        'mastery_score' => 62.50,
        'confidence_score' => 80.00,
    ]);

    expect($attempt->fresh()->answer)->toBe(['value' => 'B'])
        ->and($attempt->fresh()->is_correct)->toBeFalse()
        ->and($mastery->fresh()->mastery_score)->toBe('62.50')
        ->and($user->fresh()->masteryRecords->first()->skill->is($skill))->toBeTrue();
});
