<?php

namespace Database\Factories;

use App\Models\Question;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Question>
 */
class QuestionFactory extends Factory
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
            'owner_id' => null,
            'question_type' => 'single_choice',
            'status' => 'draft',
            'difficulty' => 'intermediate',
            'published_version_id' => null,
            'created_by' => null,
            'updated_by' => null,
        ];
    }
}
