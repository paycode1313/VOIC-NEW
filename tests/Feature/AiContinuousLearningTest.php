<?php

namespace Tests\Feature;

use App\Models\AiRole;
use App\Models\PracticeSession;
use App\Models\User;
use Database\Seeders\AiRoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AiContinuousLearningTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that user model returns default AI learning profile structure.
     */
    public function test_user_has_default_ai_learning_profile(): void
    {
        $user = User::factory()->create();

        $profile = $user->getAiLearningProfile();

        $this->assertIsArray($profile);
        $this->assertEquals(1, $profile['learning_level']);
        $this->assertEquals(0, $profile['total_turns_learned']);
        $this->assertArrayHasKey('identified_weaknesses', $profile);
        $this->assertArrayHasKey('mastered_topics', $profile);
    }

    /**
     * Test sending turn receives coherence telemetry and entity grounding.
     */
    public function test_message_turn_returns_coherence_and_salient_entities(): void
    {
        Http::fake([
            '*/chat' => Http::response([
                'role_name' => 'VOIC-HRD',
                'response_text' => 'Terkait kepemimpinan proyek yang kamu sampaikan, apa tantangan teknis terberat yang kamu selesaikan?',
                'facial_critique' => null,
                'audio_file' => 'tts_test.mp3',
                'coherence_score' => 92.5,
                'coherence_category' => 'Sangat Relevan & Terarah',
                'salient_entities' => ['kepemimpinan', 'proyek', 'performa'],
            ], 200),
        ]);

        $this->seed(AiRoleSeeder::class);
        $user = User::factory()->create();
        $role = AiRole::where('role_type', 'hrd')->firstOrFail();

        $session = PracticeSession::factory()->create([
            'user_id' => $user->id,
            'ai_role_id' => $role->id,
            'scenario_type' => 'Wawancara Kerja',
        ]);

        $response = $this->actingAs($user)->postJson(route('practice.message', $session), [
            'message' => 'Saya memimpin proyek transformasi backend dan berhasil mengoptimasi performa API hingga 40%.',
            'facial_status' => [
                'status' => 'fokus',
                'eye_contact_score' => 88,
                'is_smiling' => true,
            ],
            'timestamp_seconds' => 20,
        ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'coherence_score' => 92.5,
            'coherence_category' => 'Sangat Relevan & Terarah',
            'salient_entities' => ['kepemimpinan', 'proyek', 'performa'],
        ]);
    }

    /**
     * Test finishing session updates the user's continuous learning profile.
     */
    public function test_finishing_session_updates_user_ai_learning_profile(): void
    {
        Http::fake([
            '*/conclude' => Http::response([
                'conclusion' => 'Hasil evaluasi menunjukkan pemahaman yang sangat mendalam.',
            ], 200),
            '*/learn/sync' => Http::response([
                'success' => true,
            ], 200),
        ]);

        $this->seed(AiRoleSeeder::class);
        $user = User::factory()->create();
        $role = AiRole::where('role_type', 'dosen_penguji')->firstOrFail();

        $session = PracticeSession::factory()->create([
            'user_id' => $user->id,
            'ai_role_id' => $role->id,
            'scenario_type' => 'Sidang Skripsi',
        ]);

        // Add 3 user turns
        for ($i = 1; $i <= 3; $i++) {
            $session->messages()->create([
                'sender' => 'user',
                'message' => 'Jawaban mahasiswa turn '.$i,
                'timestamp_seconds' => $i * 10,
            ]);
        }

        $response = $this->actingAs($user)->postJson(route('practice.finish', $session), [
            'duration_seconds' => 95,
            'face_score' => 84.0,
            'voice_score' => 88.0,
            'overall_score' => 86.0,
            'feedback_notes' => [
                'pace_wpm' => 125,
                'improvements' => ['Tingkatkan variasi intonasi pada bab 3'],
                'strengths' => ['Penguasaan materi sangat baik'],
            ],
        ]);

        $response->assertOk();

        $user->refresh();
        $profile = $user->getAiLearningProfile();

        $this->assertGreaterThanOrEqual(3, $profile['total_turns_learned']);
        $this->assertContains('Sidang Skripsi', $profile['mastered_topics']);
        $this->assertContains('Tingkatkan variasi intonasi pada bab 3', $profile['identified_weaknesses']);
    }

    /**
     * Test dashboard renders the Deep Learning adaptive card.
     */
    public function test_dashboard_renders_deep_learning_adaptive_card(): void
    {
        $user = User::factory()->create([
            'ai_learning_profile' => [
                'learning_level' => 3,
                'total_turns_learned' => 45,
                'adaptive_mastery_rate' => 84.5,
                'identified_weaknesses' => ['Kontak mata kamera'],
                'mastered_topics' => ['Sidang Skripsi'],
            ],
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSeeText('Otak AI & Continuous Deep Learning');
        $response->assertSeeText('Level 3');
        $response->assertSeeText('Koherensi Semantik');
    }
}
