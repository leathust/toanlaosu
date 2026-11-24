<?php

namespace Database\Factories;

use App\Models\ExamAttempt;
use App\Models\ExamEvent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExamEvent>
 */
class ExamEventFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'exam_attempt_id' => ExamAttempt::factory(),
            'event_type' => 'answer_saved',
            'client_occurred_at' => now(),
            'occurred_at' => now(),
            'ip_address' => fake()->ipv4(),
            'user_agent' => fake()->userAgent(),
            'metadata' => null,
        ];
    }
}
