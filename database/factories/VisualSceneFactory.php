<?php

namespace Database\Factories;

use App\Models\VisualScene;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<VisualScene>
 */
class VisualSceneFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'public_id' => (string) Str::ulid(),
            'name' => fake()->sentence(3),
            'engine' => 'svg',
            'scene_key' => fake()->slug(3),
            'status' => 'draft',
            'published_version_id' => null,
            'created_by' => null,
            'updated_by' => null,
        ];
    }
}
