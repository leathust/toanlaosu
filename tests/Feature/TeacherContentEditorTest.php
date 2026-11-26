<?php

use App\Models\AuditLog;
use App\Models\Question;
use App\Models\QuestionVersion;
use App\Models\Role;
use App\Models\User;
use App\Models\VisualScene;
use App\Models\VisualSceneVersion;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

function contentEditorTeacher(): User
{
    $teacher = User::factory()->create();
    $teacher->roles()->attach(Role::factory()->create(['name' => 'teacher'])->id);

    return $teacher;
}

it('deep links a teacher to the exact question draft version and saves it', function () {
    $teacher = contentEditorTeacher();
    $question = Question::factory()->create(['owner_id' => $teacher->id, 'created_by' => $teacher->id]);
    $version = QuestionVersion::factory()->for($question)->create(['version' => 2, 'stem' => 'Old stem']);
    $headers = ['X-Inertia' => 'true', 'X-Requested-With' => 'XMLHttpRequest', 'X-Inertia-Version' => hash_file('xxh128', public_path('build/manifest.json'))];

    $this->actingAs($teacher)->withHeaders($headers)->get(route('teacher.questions.edit', [$question, $version->version]))->assertOk()->assertJsonPath('component', 'Teacher/QuestionEditor')->assertJsonPath('props.version.version', 2);
    $this->actingAs($teacher)->patch(route('teacher.questions.update', [$question, $version->version]), ['version' => 2, 'stem' => 'Updated stem', 'solution' => 'Explain each step', 'solution_blocks' => [['type' => 'text', 'content' => 'Step one']]])->assertRedirect();

    expect($version->fresh()->stem)->toBe('Updated stem')->and($version->fresh()->solution_blocks)->toHaveCount(1)->and(AuditLog::query()->where('event', 'content.question_version_updated')->exists())->toBeTrue();
});

it('deep links a teacher to the exact scene draft version and keeps published versions immutable', function () {
    $teacher = contentEditorTeacher();
    $scene = VisualScene::factory()->create(['created_by' => $teacher->id]);
    $version = VisualSceneVersion::factory()->for($scene)->create(['version' => 3, 'status' => 'draft']);

    $this->actingAs($teacher)->get(route('teacher.scenes.edit', [$scene, $version->version]))->assertOk();
    $this->actingAs($teacher)->patch(route('teacher.scenes.update', [$scene, $version->version]), ['version' => 3, 'name' => 'Radar animation', 'scene_key' => 'radar-animation', 'config' => ['objects' => [['id' => 'plane']]], 'steps' => [['at' => 0, 'x' => 1]], 'allowed_commands' => ['play', 'pause']])->assertRedirect();
    expect($version->fresh()->config['objects'][0]['id'])->toBe('plane');

    $published = VisualSceneVersion::factory()->for($scene)->create(['version' => 4, 'status' => 'published']);
    $scene->update(['published_version_id' => $published->id]);
    $this->actingAs($teacher)->withExceptionHandling()->patch(route('teacher.scenes.update', [$scene, 4]), ['version' => 4, 'name' => 'Blocked', 'scene_key' => 'blocked', 'config' => [], 'steps' => [], 'allowed_commands' => ['play']])->assertStatus(409);
    expect($published->fresh()->status)->toBe('published');
});

it('shows question and scene teacher deep links in the admin review queue', function () {
    $admin = User::factory()->create();
    $admin->roles()->attach(Role::factory()->create(['name' => 'admin'])->id);
    $question = Question::factory()->create(['status' => 'review']);
    $questionVersion = QuestionVersion::factory()->for($question)->create(['version' => 2]);
    $scene = VisualScene::factory()->create(['status' => 'review']);
    $sceneVersion = VisualSceneVersion::factory()->for($scene)->create(['version' => 3]);

    $this->actingAs($admin)->get(route('admin.content.review'))->assertOk()->assertSee(route('teacher.questions.edit', [$question, $questionVersion->version]), false)->assertSee(route('teacher.scenes.edit', [$scene, $sceneVersion->version]), false);
});

it('blocks a teacher from editing another owners content', function () {
    $teacher = contentEditorTeacher();
    $question = Question::factory()->create();
    $version = QuestionVersion::factory()->for($question)->create();

    $this->actingAs($teacher)->get(route('teacher.questions.edit', [$question, $version->version]))->assertForbidden();
});
