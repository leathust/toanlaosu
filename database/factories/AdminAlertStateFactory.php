<?php

namespace Database\Factories;

use App\Models\AdminAlertState;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AdminAlertState>
 */
class AdminAlertStateFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'fingerprint' => hash('sha256', fake()->uuid()),
            'source' => 'failed_job',
            'source_id' => (string) fake()->randomNumber(),
            'status' => 'open',
            'updated_by' => User::factory(),
            'acknowledged_at' => null,
            'resolved_at' => null,
        ];
    }
}
