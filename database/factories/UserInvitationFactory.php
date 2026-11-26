<?php

namespace Database\Factories;

use App\Models\Role;
use App\Models\User;
use App\Models\UserInvitation;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<UserInvitation>
 */
class UserInvitationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'email' => fake()->unique()->safeEmail(),
            'name' => fake()->name(),
            'role_id' => Role::factory(),
            'invited_by' => User::factory(),
            'token_hash' => hash('sha256', Str::random(64)),
            'expires_at' => now()->addDay(),
            'accepted_at' => null,
            'revoked_at' => null,
        ];
    }
}
