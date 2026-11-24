<?php

namespace Database\Factories;

use App\Models\AiKnowledgeDocument;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AiKnowledgeDocument>
 */
class AiKnowledgeDocumentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'source_type' => 'lesson',
            'source_id' => 1,
            'source_version' => 1,
            'title' => fake()->sentence(5),
            'checksum' => hash('sha256', fake()->sentence()),
            'status' => 'indexed',
            'provider' => null,
            'provider_document_id' => null,
            'indexed_at' => now(),
            'error_message' => null,
            'metadata' => null,
        ];
    }
}
