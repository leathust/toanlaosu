<?php

namespace Database\Factories;

use App\Models\Exam;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Exam>
 */
class ExamFactory extends Factory
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
            'course_id' => null,
            'title' => fake()->sentence(5),
            'instructions' => fake()->optional()->paragraph(),
            'status' => 'draft',
            'starts_at' => now()->addDay(),
            'ends_at' => now()->addDay()->addHours(2),
            'duration_minutes' => 90,
            'late_entry_minutes' => 0,
            'grace_period_seconds' => 30,
            'max_attempts' => 1,
            'randomize_questions' => false,
            'randomize_options' => false,
            'result_release_at' => null,
            'answer_release_at' => null,
            'created_by' => null,
            'updated_by' => null,
        ];
    }
}
