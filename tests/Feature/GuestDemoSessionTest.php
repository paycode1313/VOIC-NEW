<?php

namespace Tests\Feature;

use App\Models\AiRole;
use App\Models\PracticeSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
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
        // The booth demo now opens the three-part mock exam, which guests can take without an account.
        $this->get(route('demo.create'))->assertRedirect(route('exam.index'));
        $this->get(route('demo.create', ['role' => 'hrd']))->assertRedirect(route('exam.setup', 'wawancara'));
        $this->get(route('exam.index'))->assertOk()->assertSee('Wawancara Kerja');
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
        Http::fake(['*' => Http::response([], 503)]);
        $this->post(route('exam.start', 'sidang'), [
            'guest_name' => 'Calon User Baru',
            'parts' => [1],
            'consent' => '1',
        ])->assertRedirect();

        $session = PracticeSession::latest('id')->firstOrFail();
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
