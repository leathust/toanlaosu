<?php

use App\Models\Lesson;
use App\Models\LessonBlock;
use App\Models\LessonMaterial;
use App\Models\LessonVideo;
use App\Models\Question;
use App\Models\QuestionVersion;
use App\Models\Skill;
use App\Models\User;
use App\Models\VisualScene;
use App\Models\VisualSceneVersion;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

it('connects lesson blocks to media assets and preserves ordering', function () {
    $lesson = Lesson::factory()->create();
    $video = LessonVideo::factory()->create();
    $material = LessonMaterial::factory()->create();

    $videoBlock = LessonBlock::factory()->for($lesson)->create([
        'block_type' => 'video',
        'sort_order' => 1,
        'video_id' => $video->id,
    ]);
    $materialBlock = LessonBlock::factory()->for($lesson)->create([
        'block_type' => 'material',
        'sort_order' => 2,
        'material_id' => $material->id,
    ]);

    $lesson->load('blocks.video', 'blocks.material');

    expect($lesson->blocks)->toHaveCount(2)
        ->and($lesson->blocks->first()->is($videoBlock))->toBeTrue()
        ->and($lesson->blocks->first()->video->is($video))->toBeTrue()
        ->and($lesson->blocks->last()->is($materialBlock))->toBeTrue()
        ->and($lesson->blocks->last()->material->is($material))->toBeTrue();
});

it('keeps question ownership independent from course placement', function () {
    $owner = User::factory()->create();
    $question = Question::factory()->create([
        'owner_id' => $owner->id,
        'question_type' => 'single_choice',
    ]);
    $version = QuestionVersion::factory()->for($question)->create([
        'version' => 1,
        'allow_negative' => false,
    ]);
    $skill = Skill::factory()->create();
    $version->skills()->attach($skill, ['weight' => 100]);

    $question->load('owner', 'versions');

    expect($question->owner->is($owner))->toBeTrue()
        ->and($question->versions->first()->is($version))->toBeTrue()
        ->and($version->fresh()->allow_negative)->toBeFalse()
        ->and($version->fresh()->skills->first()->is($skill))->toBeTrue();
});

it('stores versioned scene configuration for dynamic 2d content', function () {
    $scene = VisualScene::factory()->create([
        'engine' => 'svg',
        'scene_key' => 'radar-aircraft',
    ]);
    $version = VisualSceneVersion::factory()->for($scene)->create([
        'config' => ['center' => [0, 0], 'radius' => 10],
        'steps' => [['time' => 0, 'aircraft' => [0, 20]]],
        'allowed_commands' => ['play', 'pause', 'seek'],
    ]);
    $scene->update(['published_version_id' => $version->id]);

    expect($scene->fresh()->publishedVersion->is($version))->toBeTrue()
        ->and($version->fresh()->config)->toMatchArray(['radius' => 10])
        ->and($version->fresh()->steps)->toHaveCount(1);
});
