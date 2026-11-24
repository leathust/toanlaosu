<?php

namespace Database\Factories;

use App\Models\ChatThread;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ChatThread>
 */
class ChatThreadFactory extends Factory
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
            'user_id' => User::factory(),
            'course_id' => null,
            'lesson_id' => null,
            'question_version_id' => null,
            'lesson_block_id' => null,
            'mode' => 'tutor',
            'status' => 'active',
            'title' => fake()->optional()->sentence(4),
            'last_message_at' => null,
            'retention_expires_at' => now()->addYear(),
            'metadata' => null,
        ];
    }
}
