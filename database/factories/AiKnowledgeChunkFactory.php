<?php

namespace Database\Factories;

use App\Models\AiKnowledgeChunk;
use App\Models\AiKnowledgeDocument;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AiKnowledgeChunk>
 */
class AiKnowledgeChunkFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'document_id' => AiKnowledgeDocument::factory(),
            'chunk_index' => 0,
            'content' => fake()->paragraph(),
            'token_count' => fake()->numberBetween(20, 500),
            'content_hash' => hash('sha256', fake()->paragraph()),
            'external_vector_id' => null,
            'metadata' => null,
        ];
    }
}
