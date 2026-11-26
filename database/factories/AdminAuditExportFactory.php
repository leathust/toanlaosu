<?php

namespace Database\Factories;

use App\Models\AdminAuditExport;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<AdminAuditExport>
 */
class AdminAuditExportFactory extends Factory
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
            'requested_by' => User::factory(),
            'idempotency_key' => hash('sha256', fake()->uuid()),
            'status' => 'queued',
            'filters' => ['range' => '30d'],
            'disk' => null,
            'path' => null,
            'row_count' => null,
            'requested_at' => now(),
            'completed_at' => null,
            'failed_at' => null,
            'error_message' => null,
            'expires_at' => null,
        ];
    }
}
