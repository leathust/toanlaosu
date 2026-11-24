<?php

namespace Database\Factories;

use App\Models\LatexTemplate;
use App\Models\LatexTemplateVersion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LatexTemplateVersion>
 */
class LatexTemplateVersionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'latex_template_id' => LatexTemplate::factory(),
            'version' => 1,
            'preamble' => '\\documentclass{article}',
            'header' => null,
            'question_format' => '\\question{stem}',
            'solution_format' => null,
            'footer' => null,
            'status' => 'draft',
            'published_at' => null,
            'created_by' => null,
        ];
    }
}
