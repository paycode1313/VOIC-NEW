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

        $this->actingAs($user)->get(route('practice.create'))->assertRedirect(route('exam.index'));

        $this->actingAs($user)->get(route('exam.index'))
            ->assertOk()
            ->assertSee('Prof. Hendra')
            ->assertSee('Kak Nadia')
            ->assertSee('Pak William');
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
     * Test that other users cannot view or delete someone else's session.
     */
    public function test_other_user_cannot_access_or_modify_session(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();

        $session = PracticeSession::factory()->create([
            'user_id' => $owner->id,
        ]);

        $this->actingAs($intruder)->get(route('practice.show', $session))->assertForbidden();
        $this->actingAs($intruder)->deleteJson(route('practice.destroy', $session))->assertForbidden();
        $this->assertModelExists($session);
    }

    /**
     * Test that practice report displays industry benchmark analytics and verifiable digital certificate.
     */
    public function test_practice_report_displays_industry_benchmark_and_digital_certificate(): void
    {
        $this->seed(AiRoleSeeder::class);
        $user = User::factory()->create();
        $role = AiRole::where('role_type', 'hrd')->firstOrFail();

        $session = PracticeSession::factory()->create([
            'user_id' => $user->id,
            'ai_role_id' => $role->id,
            'scenario_type' => 'Wawancara HRD',
            'face_score' => 88.0,
            'voice_score' => 86.0,
            'overall_score' => 87.0,
            'ai_conclusion' => 'Kandidat sangat potensial dan percaya diri.',
            'feedback_notes' => [
                'eye_contact_score' => 89.0,
                'smile_rate' => 85.0,
                'pace_wpm' => 128,
                'clarity_score' => 90.0,
                'filler_words_count' => 1,
            ],
        ]);

        $response = $this->actingAs($user)->get(route('practice.show', $session));

        $response->assertOk();
        $response->assertSee('Tolak Ukur Standar Industri');
        $response->assertSee('Komparasi Metrik Performa Anda vs Standar Kelulusan');
        $response->assertSee('Sertifikat Evaluasi Terverifikasi Sistem');
        $response->assertSee('VOIC-CERT-');
        $response->assertSee('qrcode.min.js');
    }

    /**
     * Test that any user/judge can publicly verify a digital certificate via QR Code link without login.
     */
    public function test_public_user_can_access_verification_page_without_authentication(): void
    {
        $this->seed(AiRoleSeeder::class);
        $user = User::factory()->create(['name' => 'Peserta Unggulan']);
        $role = AiRole::where('role_type', 'dosen_penguji')->firstOrFail();

        $session = PracticeSession::factory()->create([
            'user_id' => $user->id,
            'ai_role_id' => $role->id,
            'scenario_type' => 'Sidang Skripsi',
            'face_score' => 90.0,
            'voice_score' => 85.0,
            'overall_score' => 87.5,
            'ai_conclusion' => 'Argumen ilmiah sangat kokoh dan lugas.',
        ]);

        $response = $this->get($session->verifyUrl());

        $response->assertOk();
        $response->assertViewIs('practice.verify');
        $response->assertSee('Sertifikat Hasil Evaluasi AI Multimodal');
        $response->assertSee('Kredensial Sah');
        $response->assertSee('Peserta Unggulan');
        $response->assertSee('VOIC-Dosen Penguji');
        $response->assertSee('VOIC-CERT-');
    }

    /**
     * Certificates cannot be opened by guessing a session ID: the link must carry a valid signature.
     */
    public function test_verification_page_rejects_unsigned_or_tampered_links(): void
    {
        $user = User::factory()->create(['name' => 'Peserta Rahasia']);
        $session = PracticeSession::factory()->create(['user_id' => $user->id]);
        $other = PracticeSession::factory()->create(['user_id' => $user->id]);

        $this->get('/verify/'.$session->id)->assertForbidden();

        $tampered = str_replace('/verify/'.$session->id, '/verify/'.$other->id, $session->verifyUrl());
        $this->get($tampered)->assertForbidden();
    }

    /**
     * Test that user can preselect role via query parameter on practice page.
     */
    public function test_user_can_preselect_role_via_query_parameter(): void
    {
        $this->seed(AiRoleSeeder::class);
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('practice.create', ['role' => 'investor']))
            ->assertRedirect(route('exam.setup', 'pitching'));
    }
}
