<?php

namespace Database\Factories;

use App\Models\ExamVersion;
use App\Models\ExportJob;
use App\Models\LatexTemplateVersion;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ExportJob>
 */
class ExportJobFactory extends Factory
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
            'exam_version_id' => ExamVersion::factory(),
            'latex_template_version_id' => LatexTemplateVersion::factory(),
            'mode' => 'tex',
            'status' => 'queued',
            'options' => null,
            'idempotency_key' => fake()->unique()->uuid(),
            'disk' => null,
            'path' => null,
            'requested_at' => now(),
            'completed_at' => null,
            'failed_at' => null,
            'error_message' => null,
            'expires_at' => now()->addDay(),
        ];
    }
}
