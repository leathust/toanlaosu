<?php

namespace Database\Factories;

use App\Models\Exam;
use App\Models\ExamVersion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExamVersion>
 */
class ExamVersionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'exam_id' => Exam::factory(),
            'version' => 1,
            'settings_snapshot' => ['duration_minutes' => 90],
            'total_points' => 0,
            'status' => 'draft',
            'published_at' => null,
            'created_by' => null,
        ];
    }
}
