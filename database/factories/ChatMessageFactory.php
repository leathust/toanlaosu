<?php

namespace Database\Factories;

use App\Models\ChatMessage;
use App\Models\ChatThread;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ChatMessage>
 */
class ChatMessageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'chat_thread_id' => ChatThread::factory(),
            'parent_id' => null,
            'role' => 'assistant',
            'status' => 'completed',
            'content' => fake()->paragraph(),
            'model' => 'gpt-4o-mini',
            'provider_message_id' => null,
            'tool_calls' => null,
            'prompt_tokens' => 100,
            'completion_tokens' => 200,
            'latency_ms' => fake()->numberBetween(100, 5000),
            'error_code' => null,
            'created_at' => now(),
        ];
    }
}
