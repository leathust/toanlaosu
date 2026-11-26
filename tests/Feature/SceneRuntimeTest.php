<?php

use App\Models\User;
use App\Models\VisualScene;
use App\Models\VisualSceneVersion;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

function publishedScene(): VisualScene
{
    $scene = VisualScene::factory()->create(['scene_key' => 'radar-aircraft', 'status' => 'published']);
    $version = VisualSceneVersion::factory()->for($scene)->create([
        'config' => ['type' => 'radar', 'parameters' => ['radius' => 10]],
        'allowed_commands' => ['play', 'pause', 'seek', 'reset'],
        'status' => 'published',
        'published_at' => now(),
    ]);
    $scene->update(['published_version_id' => $version->id]);

    return $scene->fresh();
}

it('serves a validated published scene payload', function () {
    $scene = publishedScene();
    $user = User::factory()->create();

    $this->actingAs($user)
        ->getJson(route('student.scene.show', $scene->public_id))
        ->assertOk()
        ->assertJsonPath('data.scene_key', 'radar-aircraft')
        ->assertJsonPath('data.config.type', 'radar')
        ->assertJsonPath('data.allowed_commands.0', 'play');
});

it('accepts only commands persisted in the scene allow-list', function () {
    $scene = publishedScene();
    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson(route('student.scene.command', $scene->public_id), ['command' => 'play'])
        ->assertOk()
        ->assertJsonPath('data.accepted', true);

    $this->actingAs($user)
        ->postJson(route('student.scene.command', $scene->public_id), ['command' => 'parameter.update'])
        ->assertUnprocessable();
});

it('rejects invalid persisted scene configuration', function () {
    $scene = VisualScene::factory()->create(['status' => 'published']);
    $version = VisualSceneVersion::factory()->for($scene)->create([
        'config' => ['type' => 'not-allowed', 'parameters' => []],
        'status' => 'published',
        'published_at' => now(),
    ]);
    $scene->update(['published_version_id' => $version->id]);

    $this->actingAs(User::factory()->create())
        ->getJson(route('student.scene.show', $scene->public_id))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['config.type']);
});

it('supports the four approved 2d scene types through the persisted contract', function () {
    $user = User::factory()->create();
    foreach (['radar', 'ant', 'graph', 'surface'] as $type) {
        $scene = VisualScene::factory()->create(['scene_key' => $type]);
        $version = VisualSceneVersion::factory()->for($scene)->create([
            'config' => ['type' => $type, 'parameters' => []],
            'allowed_commands' => ['play', 'pause', 'reset', 'seek'],
            'status' => 'published',
            'published_at' => now(),
        ]);
        $scene->update(['published_version_id' => $version->id]);

        $this->actingAs($user)
            ->getJson(route('student.scene.show', $scene->public_id))
            ->assertOk()
            ->assertJsonPath('data.config.type', $type);
    }
});
