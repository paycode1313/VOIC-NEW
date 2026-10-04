<?php

namespace Database\Factories;

use App\Models\PracticeSession;
use App\Models\SessionMessage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SessionMessage>
 */
class SessionMessageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $sender = fake()->randomElement(['user', 'ai']);

        return [
            'practice_session_id' => PracticeSession::factory(),
            'sender' => $sender,
            'message' => $sender === 'user'
                ? fake()->sentence(12)
                : 'Bagus, pemaparan Anda cukup terstruktur. Bisakah jelaskan lebih detail mengenai batasan masalah penelitian?',
            'audio_url' => null,
            'facial_status' => [
                'emotion' => fake()->randomElement(['confident', 'neutral', 'nervous']),
                'eye_contact_ratio' => fake()->numberBetween(65, 95),
                'smile_detected' => fake()->boolean(60),
            ],
            'timestamp_seconds' => fake()->numberBetween(5, 120),
        ];
    }
}
