<?php

namespace Database\Factories;

use App\Models\AiRole;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AiRole>
 */
class AiRoleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name().', M.T.',
            'role_type' => fake()->randomElement(['dosen_penguji', 'hrd', 'investor']),
            'avatar' => null,
            'description' => fake()->paragraph(),
            'system_prompt' => 'Anda adalah penguji AI yang profesional dan teliti.',
            'personality_traits' => ['kritis', 'teliti', 'ramah'],
            'voice_id' => 'id-ID-ArdiNeural',
            'difficulty_level' => 'Sedang',
            'is_active' => true,
        ];
    }
}
