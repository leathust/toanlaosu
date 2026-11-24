<?php

namespace Database\Factories;

use App\Models\AiUsageLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AiUsageLog>
 */
class AiUsageLogFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'chat_message_id' => null,
            'provider' => 'openai',
            'model' => 'gpt-4o-mini',
            'operation' => 'chat_completion',
            'status' => 'succeeded',
            'prompt_tokens' => 100,
            'completion_tokens' => 200,
            'cost' => 0.001,
            'latency_ms' => fake()->numberBetween(100, 5000),
            'error_code' => null,
            'occurred_at' => now(),
            'retention_expires_at' => now()->addYear(),
        ];
    }
}
