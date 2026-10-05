<?php

namespace Tests\Feature;

use App\Models\AiRole;
use App\Models\PracticeSession;
use App\Models\User;
use Database\Seeders\AiRoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InteractivePracticeSessionTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that authenticated users can see the AI character role selection on practice page.
     */
    public function test_user_can_view_ai_roles_on_practice_page(): void
    {
        $this->seed(AiRoleSeeder::class);
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('practice.create'));

        $response->assertOk();
        $response->assertViewIs('practice.create');
        $response->assertSee('VOIC-Dosen Penguji');
        $response->assertSee('VOIC-HRD');
        $response->assertSee('VOIC-Investor');
    }

    /**
     * Test starting an interactive AI session creates a session and initial AI message.
     */
    public function test_user_can_start_interactive_ai_session(): void
    {
        $this->seed(AiRoleSeeder::class);
        $user = User::factory()->create();
        $role = AiRole::where('role_type', 'dosen_penguji')->firstOrFail();

        $response = $this->actingAs($user)->postJson(route('practice.start'), [
            'ai_role_id' => $role->id,
            'scenario_type' => 'Sidang Skripsi',
        ]);

        $response->assertCreated();
        $response->assertJson([
            'success' => true,
        ]);

        $sessionId = $response->json('session_id');

        $this->assertDatabaseHas('practice_sessions', [
            'id' => $sessionId,
            'user_id' => $user->id,
            'ai_role_id' => $role->id,
        ]);

        $this->assertDatabaseHas('session_messages', [
            'practice_session_id' => $sessionId,
            'sender' => 'ai',
        ]);
    }

    /**
     * Test sending a turn-by-turn conversational message with facial telemetry.
     */
    public function test_user_can_send_turn_with_facial_telemetry(): void
    {
        $this->seed(AiRoleSeeder::class);
        $user = User::factory()->create();
        $role = AiRole::where('role_type', 'hrd')->firstOrFail();

        $session = PracticeSession::factory()->create([
            'user_id' => $user->id,
            'ai_role_id' => $role->id,
            'scenario_type' => 'Wawancara Kerja',
        ]);

        $response = $this->actingAs($user)->postJson(route('practice.message', $session), [
            'message' => 'Saya pernah memimpin proyek pengembangan aplikasi dan berhasil meningkatkan performa tim sebesar 30%.',
            'facial_status' => [
                'status' => 'tegang',
                'eye_contact_score' => 65,
                'is_smiling' => false,
            ],
            'timestamp_seconds' => 14,
        ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
        ]);

        // User message stored
        $this->assertDatabaseHas('session_messages', [
            'practice_session_id' => $session->id,
            'sender' => 'user',
            'message' => 'Saya pernah memimpin proyek pengembangan aplikasi dan berhasil meningkatkan performa tim sebesar 30%.',
            'timestamp_seconds' => 14,
        ]);

        // AI message response stored
        $this->assertDatabaseHas('session_messages', [
            'practice_session_id' => $session->id,
            'sender' => 'ai',
        ]);
    }

    /**
     * Test finishing an interactive session updates scores and generates AI conclusion.
     */
    public function test_user_can_finish_interactive_session(): void
    {
        $this->seed(AiRoleSeeder::class);
        $user = User::factory()->create();
        $role = AiRole::where('role_type', 'dosen_penguji')->firstOrFail();

        $session = PracticeSession::factory()->create([
            'user_id' => $user->id,
            'ai_role_id' => $role->id,
            'scenario_type' => 'Sidang Skripsi',
        ]);

        $response = $this->actingAs($user)->postJson(route('practice.finish', $session), [
            'duration_seconds' => 125,
            'face_score' => 86.5,
            'voice_score' => 88.0,
            'overall_score' => 87.25,
            'feedback_notes' => [
                'summary' => 'Sesi sidang skripsi diselesaikan dengan baik.',
                'eye_contact_score' => 88,
                'smile_rate' => 74,
                'pace_wpm' => 134,
                'clarity_score' => 90,
            ],
        ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'redirect_url' => route('practice.show', $session),
        ]);

        $session->refresh();
        $this->assertEquals(125, $session->duration_seconds);
        $this->assertEquals(86.5, $session->face_score);
        $this->assertEquals(88.0, $session->voice_score);
        $this->assertEquals(87.25, $session->overall_score);
        $this->assertNotNull($session->ai_conclusion);
        $this->assertStringContainsString('Hasil Evaluasi', $session->ai_conclusion);
    }

    /**
     * Test viewing report displays AI role verdict, scores, and conversation messages.
     */
    public function test_practice_report_displays_ai_verdict_and_chat_history(): void
    {
        $this->seed(AiRoleSeeder::class);
        $user = User::factory()->create();
        $role = AiRole::where('role_type', 'investor')->firstOrFail();

        $session = PracticeSession::factory()->create([
            'user_id' => $user->id,
            'ai_role_id' => $role->id,
            'scenario_type' => 'Pitching Startup',
            'face_score' => 82.0,
            'voice_score' => 85.0,
            'overall_score' => 83.5,
            'ai_conclusion' => 'Pitching persuasif dan berbobot.',
        ]);

        $session->messages()->create([
            'sender' => 'user',
            'message' => 'Model bisnis kami adalah B2B SaaS dengan langganan tahunan.',
            'facial_status' => ['status' => 'tersenyum', 'eye_contact_score' => 85],
            'timestamp_seconds' => 10,
        ]);

        $session->messages()->create([
            'sender' => 'ai',
            'message' => 'Bagaimana proyeksi churn rate pelanggan Anda?',
            'timestamp_seconds' => 12,
        ]);

        $response = $this->actingAs($user)->get(route('practice.show', $session));

        $response->assertOk();
        $response->assertSee('VOIC-Investor');
        $response->assertSee('Pitching persuasif dan berbobot.');
        $response->assertSee('Model bisnis kami adalah B2B SaaS');
        $response->assertSee('Bagaimana proyeksi churn rate');
        $response->assertSee('Senyum Rileks');
    }

    /**
     * Test that unauthorized users cannot send messages to someone else's session.
     */
    public function test_other_user_cannot_access_or_modify_session(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();

        $session = PracticeSession::factory()->create([
            'user_id' => $owner->id,
        ]);

        $response = $this->actingAs($intruder)->postJson(route('practice.message', $session), [
            'message' => 'Tes injeksi pesan',
            'timestamp_seconds' => 5,
        ]);

        $response->assertForbidden();
    }
}
