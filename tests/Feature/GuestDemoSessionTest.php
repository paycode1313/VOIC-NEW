<?php

namespace Tests\Feature;

use App\Models\AiRole;
use App\Models\PracticeSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GuestDemoSessionTest extends TestCase
{
    use RefreshDatabase;

    private AiRole $role;

    protected function setUp(): void
    {
        parent::setUp();

        $this->role = AiRole::create([
            'name' => 'VOIC-Dosen Penguji',
            'role_type' => 'dosen_penguji',
            'system_prompt' => 'Anda adalah dosen penguji kritis.',
            'avatar' => '/images/avatars/dosen.jpg',
            'voice_id' => 'id-ID-ArdiNeural',
            'difficulty_level' => 'Sulit',
            'description' => 'Dosen Penguji Sidang Skripsi',
            'personality_traits' => ['kritis', 'metodologis'],
            'is_active' => true,
        ]);
    }

    public function test_guest_can_access_demo_page_without_authentication(): void
    {
        $response = $this->get(route('demo.create'));

        $response->assertOk();
        $response->assertSee('Mode Booth Pameran Innofest 2026');
        $response->assertSee('VOIC-Dosen Penguji');
    }

    public function test_guest_can_start_demo_session_with_custom_name_and_institution(): void
    {
        $response = $this->postJson(route('demo.start'), [
            'ai_role_id' => $this->role->id,
            'scenario_type' => 'Sidang Skripsi Kilat',
            'language' => 'id',
            'guest_name' => 'Budi Santoso (Dewan Juri)',
            'guest_institution' => 'Universitas Brawijaya',
        ]);

        $response->assertCreated();
        $response->assertJson([
            'success' => true,
            'is_demo' => true,
            'guest_name' => 'Budi Santoso (Dewan Juri)',
        ]);

        $sessionId = $response->json('session_id');
        $this->assertNotNull($sessionId);

        $session = PracticeSession::find($sessionId);
        $this->assertNotNull($session);
        $this->assertNull($session->user_id);
        $this->assertTrue($session->isGuest());
        $this->assertEquals('Budi Santoso (Dewan Juri)', $session->getParticipantName());
        $this->assertEquals('Universitas Brawijaya', $session->getParticipantInstitution());
        $this->assertTrue($session->feedback_notes['is_guest']);
        $this->assertEquals($session->id, session('active_guest_session_id'));
    }

    public function test_guest_can_send_turn_messages_in_active_demo_session(): void
    {
        $startResponse = $this->postJson(route('demo.start'), [
            'ai_role_id' => $this->role->id,
            'guest_name' => 'Sarah Pameran',
        ]);

        $sessionId = $startResponse->json('session_id');

        $messageResponse = $this->postJson(route('demo.message', $sessionId), [
            'message' => 'Selamat pagi Bapak, saya ingin mempresentasikan penelitian saya.',
            'facial_status' => [
                'status' => 'Fokus & Rileks',
                'eye_contact_score' => 88.5,
                'is_smiling' => true,
                'face_detected' => true,
            ],
            'timestamp_seconds' => 15,
            'language' => 'id',
        ]);

        $messageResponse->assertOk();
        $messageResponse->assertJsonStructure([
            'success',
            'user_message',
            'ai_message',
            'response_text',
            'coherence_score',
        ]);

        $this->assertDatabaseHas('session_messages', [
            'practice_session_id' => $sessionId,
            'sender' => 'user',
            'message' => 'Selamat pagi Bapak, saya ingin mempresentasikan penelitian saya.',
        ]);
    }

    public function test_guest_can_finish_demo_session_and_view_scorecard(): void
    {
        $startResponse = $this->postJson(route('demo.start'), [
            'ai_role_id' => $this->role->id,
            'guest_name' => 'Dr. Bambang',
            'guest_institution' => 'Kemenristek',
        ]);

        $sessionId = $startResponse->json('session_id');

        $finishResponse = $this->postJson(route('demo.finish', $sessionId), [
            'duration_seconds' => 120,
            'face_score' => 85.0,
            'voice_score' => 88.0,
            'overall_score' => 86.5,
            'feedback_notes' => [
                'eye_contact_score' => 86.0,
                'smile_rate' => 78.0,
                'pace_wpm' => 135,
                'pitch_dynamics_score' => 82.0,
            ],
        ]);

        $finishResponse->assertOk();
        $finishResponse->assertJson([
            'success' => true,
            'redirect_url' => route('demo.show', $sessionId),
        ]);

        $session = PracticeSession::find($sessionId);
        $this->assertEquals(86.5, (float) $session->overall_score);
        $this->assertNotNull($session->ai_conclusion);

        // View scorecard
        $viewResponse = $this->get(route('demo.show', $sessionId));
        $viewResponse->assertOk();
        $viewResponse->assertSee('Dr. Bambang');
        $viewResponse->assertSee('Kemenristek');
        $viewResponse->assertSee('Mode Booth Innofest');
        $viewResponse->assertSee('Reset untuk Pengunjung Berikutnya');
    }

    public function test_guest_can_reset_demo_session_for_next_visitor(): void
    {
        $this->withSession([
            'active_guest_session_id' => 999,
            'guest_session_token' => 'sample-token',
            'guest_name' => 'Pengunjung Lama',
        ]);

        $response = $this->post(route('demo.reset'));

        $response->assertRedirect(route('demo.create'));
        $this->assertNull(session('active_guest_session_id'));
        $this->assertNull(session('guest_session_token'));
    }

    public function test_guest_session_is_claimed_when_visitor_registers_new_account(): void
    {
        $startResponse = $this->postJson(route('demo.start'), [
            'ai_role_id' => $this->role->id,
            'guest_name' => 'Calon User Baru',
        ]);

        $sessionId = $startResponse->json('session_id');
        $session = PracticeSession::find($sessionId);
        $this->assertNull($session->user_id);

        // Visitor chooses to register an account
        $registerResponse = $this->post(route('register'), [
            'name' => 'Calon User Baru',
            'email' => 'calonuser@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $registerResponse->assertRedirect(route('dashboard'));

        $newUser = User::where('email', 'calonuser@example.com')->first();
        $this->assertNotNull($newUser);

        // Verify session is now attached to the registered user
        $session->refresh();
        $this->assertEquals($newUser->id, $session->user_id);
        $this->assertFalse($session->isGuest());
    }

    public function test_public_qr_verification_shows_guest_name_and_affiliation(): void
    {
        $session = PracticeSession::create([
            'user_id' => null,
            'ai_role_id' => $this->role->id,
            'scenario_type' => 'Demo Sidang Innofest',
            'duration_seconds' => 120,
            'face_score' => 90.0,
            'voice_score' => 92.0,
            'overall_score' => 91.0,
            'ai_conclusion' => 'Presentasi sangat meyakinkan.',
            'feedback_notes' => [
                'is_guest' => true,
                'guest_name' => 'Profesor Haryanto',
                'guest_institution' => 'Dewan Juri Innofest 2026',
            ],
        ]);

        $response = $this->get(route('practice.verify', $session));

        $response->assertOk();
        $response->assertSee('Profesor Haryanto');
        $response->assertSee('Dewan Juri Innofest 2026');
        $response->assertSee('Terverifikasi');
    }
}
