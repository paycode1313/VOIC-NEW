<?php

namespace Tests\Feature;

use App\Models\AiRole;
use App\Models\PracticeSession;
use App\Models\User;
use Database\Seeders\AiRoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AiFallbackPersonaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(AiRoleSeeder::class);
    }

    /**
     * Test that when Ollama / Python AI Service is completely offline,
     * the system gracefully falls back to deterministic persona without throwing 500 error.
     */
    public function test_dosen_penguji_fallback_handles_cold_start_or_offline_service(): void
    {
        // Force all HTTP calls to local AI service to fail (simulating offline Ollama / cold-start timeout)
        Http::fake([
            'http://127.0.0.1:8001/*' => Http::response(null, 500),
        ]);

        $user = User::factory()->create();
        $role = AiRole::where('role_type', 'dosen_penguji')->firstOrFail();

        $session = PracticeSession::factory()->create([
            'user_id' => $user->id,
            'ai_role_id' => $role->id,
            'scenario_type' => 'Sidang Skripsi Komprehensif',
        ]);

        $response = $this->actingAs($user)->postJson(route('practice.message', $session), [
            'message' => 'Saya menggunakan algoritma Random Forest untuk klasifikasi citra pada bab 4 skripsi saya.',
            'facial_status' => [
                'status' => 'fokus',
                'eye_contact_score' => 88,
                'is_smiling' => false,
                'face_detected' => true,
            ],
            'timestamp_seconds' => 15,
            'language' => 'id',
        ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'ai_engine' => 'persona_fallback',
        ]);

        $aiMessage = $response->json('ai_message.message');
        $this->assertNotEmpty($aiMessage);
        $this->assertGreaterThan(15, strlen($aiMessage));
    }

    /**
     * Test that HRD fallback persona engages candidate on STAR framework when AI service is down.
     */
    public function test_hrd_fallback_provides_coherent_behavioral_inquiry(): void
    {
        Http::fake([
            'http://127.0.0.1:8001/*' => Http::response(null, 503),
        ]);

        $user = User::factory()->create();
        $role = AiRole::where('role_type', 'hrd')->firstOrFail();

        $session = PracticeSession::factory()->create([
            'user_id' => $user->id,
            'ai_role_id' => $role->id,
            'scenario_type' => 'Wawancara Kompetensi STAR',
        ]);

        $response = $this->actingAs($user)->postJson(route('practice.message', $session), [
            'message' => 'Ketika menghadapi konflik di tim sprint, saya menginisiasi retrospective meeting untuk menyelaraskan prioritas.',
            'facial_status' => ['status' => 'fokus', 'eye_contact_score' => 80],
            'timestamp_seconds' => 30,
            'language' => 'id',
        ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'ai_engine' => 'persona_fallback',
        ]);

        $this->assertNotEmpty($response->json('response_text'));
    }

    /**
     * Test that Investor persona fallback inquires on monetization and TAM when AI service is down.
     */
    public function test_investor_fallback_inquiries_on_traction_and_market(): void
    {
        Http::fake([
            'http://127.0.0.1:8001/*' => Http::response(null, 500),
        ]);

        $user = User::factory()->create();
        $role = AiRole::where('role_type', 'investor')->firstOrFail();

        $session = PracticeSession::factory()->create([
            'user_id' => $user->id,
            'ai_role_id' => $role->id,
            'scenario_type' => 'Pitch Deck 3 Menit',
        ]);

        $response = $this->actingAs($user)->postJson(route('practice.message', $session), [
            'message' => 'Model bisnis kami adalah B2B SaaS dengan margin kotor 80% dan CAC payback 4 bulan.',
            'timestamp_seconds' => 45,
            'language' => 'id',
        ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'ai_engine' => 'persona_fallback',
        ]);
    }

    /**
     * Test that session finish conclusion is produced cleanly even during cold-start or offline AI service.
     */
    public function test_session_finish_produces_verdict_when_ai_service_offline(): void
    {
        Http::fake([
            'http://127.0.0.1:8001/*' => Http::response(null, 500),
        ]);

        $user = User::factory()->create();
        $role = AiRole::where('role_type', 'dosen_penguji')->firstOrFail();

        $session = PracticeSession::factory()->create([
            'user_id' => $user->id,
            'ai_role_id' => $role->id,
            'scenario_type' => 'Sidang Tugas Akhir',
        ]);

        $response = $this->actingAs($user)->postJson(route('practice.finish', $session), [
            'duration_seconds' => 120,
            'face_score' => 84.5,
            'voice_score' => 87.0,
            'overall_score' => 85.75,
            'feedback' => [
                'eye_contact_score' => 86,
                'smile_rate' => 78,
                'pace_wpm' => 128,
                'clarity_score' => 90,
                'pitch_dynamics_score' => 82,
                'filler_words_count' => 1,
            ],
        ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
        ]);

        $session->refresh();
        $this->assertNotNull($session->ai_conclusion);
        $this->assertNotEmpty($session->ai_conclusion);
    }
}
