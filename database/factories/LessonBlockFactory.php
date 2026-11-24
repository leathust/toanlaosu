<?php

namespace Database\Factories;

use App\Models\Lesson;
use App\Models\LessonBlock;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LessonBlock>
 */
class LessonBlockFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'lesson_id' => Lesson::factory(),
            'block_type' => 'note',
            'sort_order' => fake()->numberBetween(1, 20),
            'is_blocking' => true,
            'body' => fake()->optional()->paragraph(),
            'video_id' => null,
            'material_id' => null,
            'question_id' => null,
            'visual_scene_id' => null,
            'settings' => null,
            'created_by' => null,
            'updated_by' => null,
        ];
    }
}
