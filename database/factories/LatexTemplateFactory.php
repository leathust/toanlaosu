<?php

namespace Database\Factories;

use App\Models\LatexTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<LatexTemplate>
 */
class LatexTemplateFactory extends Factory
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
            'owner_id' => null,
            'name' => fake()->sentence(4),
            'status' => 'draft',
            'active_version_id' => null,
            'created_by' => null,
            'updated_by' => null,
        ];
    }
}
