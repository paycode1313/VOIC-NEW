<?php

namespace Database\Seeders;

use App\Models\AiRole;
use App\Models\PracticeSession;
use App\Models\SessionMessage;
use App\Models\User;
use Illuminate\Database\Seeder;

class PracticeSessionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::first() ?? User::factory()->create([
            'name' => 'Demo User',
            'email' => 'demo@voic.test',
        ]);

        $roles = AiRole::all();

        // Create 6 sessions with roles and turn-by-turn messages
        for ($i = 0; $i < 6; $i++) {
            $role = $roles->isNotEmpty() ? $roles[$i % $roles->count()] : null;

            $session = PracticeSession::factory()->create([
                'user_id' => $user->id,
                'ai_role_id' => $role?->id,
                'scenario_type' => $role ? $role->name : 'Sidang Skripsi',
            ]);

            // Add sample dialogue messages
            SessionMessage::create([
                'practice_session_id' => $session->id,
                'sender' => 'user',
                'message' => 'Selamat pagi bapak/ibu, terima kasih atas kesempatannya. Hari ini saya akan memaparkan penelitian saya mengenai integrasi AI pada latihan public speaking.',
                'facial_status' => [
                    'emotion' => 'confident',
                    'eye_contact_ratio' => 88,
                    'smile_detected' => true,
                ],
                'timestamp_seconds' => 10,
            ]);

            SessionMessage::create([
                'practice_session_id' => $session->id,
                'sender' => 'ai',
                'message' => 'Baik, silakan langsung ke pokok masalah. Apa kebaruan utama dari metode yang Anda tawarkan dibandingkan sistem yang sudah ada?',
                'facial_status' => null,
                'timestamp_seconds' => 25,
            ]);

            SessionMessage::create([
                'practice_session_id' => $session->id,
                'sender' => 'user',
                'message' => 'Kebaruan utama kami terletak pada komputasi visual di sisi peramban pengguna, sehingga latensi nol dan privasi rekaman tetap terjaga.',
                'facial_status' => [
                    'emotion' => 'confident',
                    'eye_contact_ratio' => 92,
                    'smile_detected' => false,
                ],
                'timestamp_seconds' => 45,
            ]);
        }
    }
}
