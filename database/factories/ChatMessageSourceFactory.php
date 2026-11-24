<?php

namespace Database\Factories;

use App\Models\AiKnowledgeChunk;
use App\Models\ChatMessage;
use App\Models\ChatMessageSource;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ChatMessageSource>
 */
class ChatMessageSourceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'chat_message_id' => ChatMessage::factory(),
            'ai_knowledge_chunk_id' => AiKnowledgeChunk::factory(),
            'citation_order' => 1,
            'label' => fake()->sentence(3),
            'quoted_excerpt' => fake()->optional()->sentence(),
            'metadata' => null,
        ];
    }
}
