<?php

namespace Database\Factories;

use App\Models\ContentRevision;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ContentRevision>
 */
class ContentRevisionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'content_type' => 'course',
            'content_id' => fake()->numberBetween(1, 100),
            'version' => 1,
            'snapshot' => ['status' => 'draft'],
            'change_summary' => null,
            'created_by' => null,
            'created_at' => now(),
        ];
    }
}
