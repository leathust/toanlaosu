<?php

namespace Database\Factories;

use App\Models\WebhookEvent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WebhookEvent>
 */
class WebhookEventFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'provider' => 'video_provider',
            'provider_event_id' => fake()->unique()->uuid(),
            'event_type' => 'asset.ready',
            'status' => 'received',
            'payload' => ['asset_id' => fake()->uuid()],
            'received_at' => now(),
            'processed_at' => null,
            'failed_at' => null,
            'error_message' => null,
        ];
    }
}
