<?php

namespace Database\Factories;

use App\Models\ExamAttempt;
use App\Models\ExamVersion;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ExamAttempt>
 */
class ExamAttemptFactory extends Factory
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
            'exam_version_id' => ExamVersion::factory(),
            'user_id' => User::factory(),
            'attempt_number' => 1,
            'status' => 'in_progress',
            'started_at' => now(),
            'deadline_at' => now()->addMinutes(90),
            'submitted_at' => null,
            'graded_at' => null,
            'score' => null,
            'max_score' => 1,
            'save_version' => 0,
            'retention_expires_at' => now()->addYear(),
            'metadata' => null,
        ];
    }
}
