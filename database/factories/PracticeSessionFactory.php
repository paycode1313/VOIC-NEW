<?php

namespace Database\Factories;

use App\Models\PracticeSession;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PracticeSession>
 */
class PracticeSessionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $faceScore = fake()->randomFloat(2, 65, 95);
        $voiceScore = fake()->randomFloat(2, 60, 95);
        $overall = round(($faceScore * 0.5) + ($voiceScore * 0.5), 2);

        return [
            'user_id' => User::factory(),
            'ai_role_id' => null,
            'scenario_type' => fake()->randomElement([
                'Sidang Skripsi',
                'Pitching Startup',
                'Presentasi Umum',
                'Wawancara Kerja',
            ]),
            'duration_seconds' => fake()->numberBetween(60, 600),
            'face_score' => $faceScore,
            'voice_score' => $voiceScore,
            'overall_score' => $overall,
            'ai_conclusion' => fake()->randomElement([
                'Pemaparan materi secara keseluruhan meyakinkan, pertahankan konsistensi kontak mata.',
                'Argumen terstruktur dengan baik, namun kurangi gestur tegang saat menjawab pertanyaan kritis.',
                'Artikulasi vokal sangat jelas, tingkatkan variasi nada bicara agar audiens tetap fokus.',
            ]),
            'feedback_notes' => [
                'summary' => fake()->randomElement([
                    'Kontak mata sangat baik dan artikulasi terdengar jelas.',
                    'Ekspresi wajah cukup rileks, pertahankan tempo bicara.',
                    'Penyampaian materi terstruktur dengan intonasi meyakinkan.',
                    'Sedikit tegang pada pembukaan, namun ritme stabil hingga akhir.',
                ]),
                'eye_contact_score' => fake()->numberBetween(70, 95),
                'smile_rate' => fake()->numberBetween(40, 85),
                'pace_wpm' => fake()->numberBetween(110, 150),
                'clarity_score' => fake()->numberBetween(65, 95),
            ],
        ];
    }
}
