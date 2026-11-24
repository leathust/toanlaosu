<?php

namespace Database\Factories;

use App\Models\VisualScene;
use App\Models\VisualSceneVersion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VisualSceneVersion>
 */
class VisualSceneVersionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'visual_scene_id' => VisualScene::factory(),
            'version' => 1,
            'schema_version' => '1.0',
            'config' => ['objects' => []],
            'steps' => [],
            'allowed_commands' => ['play', 'pause', 'seek'],
            'fallback_disk' => null,
            'fallback_path' => null,
            'status' => 'draft',
            'published_at' => null,
            'created_by' => null,
        ];
    }
}
