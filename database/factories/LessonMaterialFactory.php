<?php

namespace Database\Factories;

use App\Models\LessonMaterial;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LessonMaterial>
 */
class LessonMaterialFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(4),
            'disk' => 'public',
            'path' => fake()->filePath(),
            'original_name' => fake()->word().'.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => fake()->numberBetween(1000, 5000000),
            'allow_download' => false,
            'watermark_enabled' => true,
            'created_by' => null,
            'updated_by' => null,
        ];
    }
}
