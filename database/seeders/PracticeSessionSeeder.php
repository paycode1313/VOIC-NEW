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
        $primaryUser = User::first() ?? User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        $roles = AiRole::all();
        $dosenRole = $roles->firstWhere('role_type', 'dosen_penguji') ?? $roles->first();
        $hrdRole = $roles->firstWhere('role_type', 'hrd') ?? $roles->first();
        $investorRole = $roles->firstWhere('role_type', 'investor') ?? $roles->first();

        // 1. Seed sessions for Primary User
        $userSessionData = [
            [
                'scenario' => 'Sidang Skripsi Bab 4 & 5',
                'role' => $dosenRole,
                'duration' => 180,
                'face' => 88.5,
                'voice' => 91.0,
                'overall' => 89.8,
                'badges' => ['tatapan_elang', 'artikulasi_emas', 'high_achiever'],
                'lang' => 'id',
            ],
            [
                'scenario' => 'Wawancara HRD (Behavioral STAR)',
                'role' => $hrdRole,
                'duration' => 120,
                'face' => 92.0,
                'voice' => 85.5,
                'overall' => 88.8,
                'badges' => ['duta_karisma', 'vokal_dinamis', 'high_achiever'],
                'lang' => 'id',
            ],
            [
                'scenario' => 'Elevator Pitch Startup 60s',
                'role' => $investorRole,
                'duration' => 65,
                'face' => 86.0,
                'voice' => 88.0,
                'overall' => 87.0,
                'badges' => ['sprint_warrior', 'high_achiever'],
                'lang' => 'id',
            ],
            [
                'scenario' => 'Global Talent Mock Interview',
                'role' => $hrdRole,
                'duration' => 140,
                'face' => 84.0,
                'voice' => 86.0,
                'overall' => 85.0,
                'badges' => ['global_communicator', 'high_achiever'],
                'lang' => 'en',
            ],
        ];

        foreach ($userSessionData as $data) {
            $session = PracticeSession::create([
                'user_id' => $primaryUser->id,
                'ai_role_id' => $data['role']?->id,
                'scenario_type' => $data['scenario'],
                'duration_seconds' => $data['duration'],
                'face_score' => $data['face'],
                'voice_score' => $data['voice'],
                'overall_score' => $data['overall'],
                'ai_conclusion' => 'Pemaparan sangat meyakinkan dengan artikulasi stabil dan kontak mata terarah.',
                'feedback_notes' => [
                    'language' => $data['lang'],
                    'eye_contact_score' => $data['face'],
                    'smile_rate' => 82.0,
                    'pace_wpm' => 135,
                    'pitch_dynamics_score' => 85.0,
                    'clarity_score' => 88.0,
                    'avg_volume' => 78.0,
                    'total_words' => 180,
                    'filler_words_count' => 0,
                    'badges' => $data['badges'],
                    'strengths' => ['Tatapan fokus ke lensa kamera', 'Artikulasi lugas tanpa jeda gumaman'],
                    'improvements' => ['Jaga kestabilan napas pada kalimat panjang'],
                ],
            ]);

            SessionMessage::create([
                'practice_session_id' => $session->id,
                'sender' => 'user',
                'message' => 'Selamat pagi bapak/ibu penguji, izinkan saya memaparkan hasil pengujian metodologi sistem ini.',
                'facial_status' => ['emotion' => 'fokus', 'eye_contact_ratio' => 90, 'smile_detected' => true],
                'timestamp_seconds' => 5,
            ]);

            SessionMessage::create([
                'practice_session_id' => $session->id,
                'sender' => 'ai',
                'message' => 'Baik, langsung ke poin utama: bagaimana Anda membuktikan keabsahan dataset yang digunakan?',
                'facial_status' => null,
                'timestamp_seconds' => 20,
            ]);
        }

        // 2. Seed realistic competitors for Booth Leaderboard
        $competitors = [
            [
                'name' => 'Farhan Pratama (Informatika)',
                'email' => 'farhan.pratama@kampus.ac.id',
                'scenario' => 'Sidang Skripsi Deep Learning',
                'role' => $dosenRole,
                'duration' => 180,
                'face' => 94.0,
                'voice' => 96.0,
                'overall' => 95.0,
                'badges' => ['tatapan_elang', 'artikulasi_emas', 'vokal_dinamis', 'high_achiever'],
            ],
            [
                'name' => 'Amanda Putri (Sistem Informasi)',
                'email' => 'amanda.putri@kampus.ac.id',
                'scenario' => 'Wawancara Kerja UI/UX Lead',
                'role' => $hrdRole,
                'duration' => 135,
                'face' => 95.5,
                'voice' => 91.0,
                'overall' => 93.3,
                'badges' => ['duta_karisma', 'artikulasi_emas', 'high_achiever'],
            ],
            [
                'name' => 'Kevin Wijaya (Teknik Elektro)',
                'email' => 'kevin.fintech@startup.id',
                'scenario' => 'Pitching Startup IoT Edge',
                'role' => $investorRole,
                'duration' => 75,
                'face' => 89.0,
                'voice' => 94.0,
                'overall' => 91.5,
                'badges' => ['sprint_warrior', 'vokal_dinamis', 'high_achiever'],
            ],
            [
                'name' => 'Siti Nurhaliza (Ilmu Komunikasi)',
                'email' => 'siti.nurhaliza@campus.edu',
                'scenario' => 'Public Speaking & Keynote',
                'role' => $hrdRole,
                'duration' => 160,
                'face' => 90.0,
                'voice' => 89.0,
                'overall' => 89.5,
                'badges' => ['tatapan_elang', 'duta_karisma', 'high_achiever'],
            ],
            [
                'name' => 'David Pratama (English Pitch)',
                'email' => 'david.pratama@global.org',
                'scenario' => 'Global Seed Round Pitching',
                'role' => $investorRole,
                'duration' => 90,
                'face' => 88.0,
                'voice' => 90.0,
                'overall' => 89.0,
                'badges' => ['global_communicator', 'sprint_warrior', 'high_achiever'],
            ],
        ];

        foreach ($competitors as $c) {
            $compUser = User::firstOrCreate(
                ['email' => $c['email']],
                [
                    'name' => $c['name'],
                    'password' => bcrypt('password'),
                    'email_verified_at' => now(),
                ]
            );

            PracticeSession::create([
                'user_id' => $compUser->id,
                'ai_role_id' => $c['role']?->id,
                'scenario_type' => $c['scenario'],
                'duration_seconds' => $c['duration'],
                'face_score' => $c['face'],
                'voice_score' => $c['voice'],
                'overall_score' => $c['overall'],
                'ai_conclusion' => 'Performa sangat kompeten dan teruji di bawah tekanan.',
                'feedback_notes' => [
                    'language' => str_contains($c['scenario'], 'Global') ? 'en' : 'id',
                    'eye_contact_score' => $c['face'],
                    'smile_rate' => 85.0,
                    'pace_wpm' => 138,
                    'pitch_dynamics_score' => 90.0,
                    'clarity_score' => 92.0,
                    'avg_volume' => 80.0,
                    'total_words' => 195,
                    'filler_words_count' => 0,
                    'badges' => $c['badges'],
                ],
            ]);
        }
    }
}
