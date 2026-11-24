<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Enrollment>
 */
class EnrollmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'course_id' => Course::factory(),
            'user_id' => User::factory(),
            'period_number' => fake()->unique()->numberBetween(1, 999999),
            'status' => 'active',
            'starts_at' => now(),
            'ends_at' => now()->addMonths(3),
            'source' => 'manual',
            'granted_by' => null,
            'revoked_at' => null,
        ];
    }
}
