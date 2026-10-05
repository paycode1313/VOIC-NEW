<?php

namespace Tests\Feature;

use App\Models\AiRole;
use App\Models\PracticeSession;
use App\Models\SessionMessage;
use App\Models\User;
use Database\Seeders\AiRoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PracticeSessionTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that a practice session can be created and has correct relationships.
     */
    public function test_can_create_practice_session_and_associate_with_user(): void
    {
        $user = User::factory()->create();

        $session = PracticeSession::factory()->create([
            'user_id' => $user->id,
            'scenario_type' => 'Sidang Skripsi',
            'duration_seconds' => 300,
            'overall_score' => 88.50,
            'feedback_notes' => ['summary' => 'Bagus sekali'],
        ]);

        $this->assertDatabaseHas('practice_sessions', [
            'id' => $session->id,
            'user_id' => $user->id,
            'scenario_type' => 'Sidang Skripsi',
        ]);

        $this->assertTrue($session->user->is($user));
        $this->assertTrue($user->practiceSessions->contains($session));
    }

    /**
     * Test that attributes are cast properly.
     */
    public function test_casts_attributes_properly(): void
    {
        $session = PracticeSession::factory()->create([
            'duration_seconds' => 120,
            'overall_score' => 75.25,
            'feedback_notes' => [
                'summary' => 'Mantap',
                'eye_contact' => 90,
            ],
        ]);

        $this->assertIsInt($session->duration_seconds);
        $this->assertIsFloat($session->overall_score);
        $this->assertIsArray($session->feedback_notes);
        $this->assertSame('Mantap', $session->feedback_notes['summary']);
    }

    /**
     * Test that AI Role can be created and has practice sessions.
     */
    public function test_ai_role_can_be_associated_with_practice_session(): void
    {
        $role = AiRole::factory()->create([
            'name' => 'VOIC-Dosen Penguji',
            'role_type' => 'dosen_penguji',
        ]);

        $session = PracticeSession::factory()->create([
            'ai_role_id' => $role->id,
            'face_score' => 85.00,
            'voice_score' => 88.00,
            'overall_score' => 86.50,
            'ai_conclusion' => 'Pemaparan baik.',
        ]);

        $this->assertTrue($session->aiRole->is($role));
        $this->assertTrue($role->practiceSessions->contains($session));
        $this->assertIsFloat($session->face_score);
        $this->assertIsFloat($session->voice_score);
        $this->assertSame('Pemaparan baik.', $session->ai_conclusion);
    }

    /**
     * Test that SessionMessage can be created and belongs to PracticeSession.
     */
    public function test_session_message_belongs_to_practice_session(): void
    {
        $session = PracticeSession::factory()->create();

        $message = SessionMessage::create([
            'practice_session_id' => $session->id,
            'sender' => 'ai',
            'message' => 'Jelaskan metodologi penelitian Anda.',
            'facial_status' => ['emotion' => 'neutral'],
            'timestamp_seconds' => 15,
        ]);

        $this->assertDatabaseHas('session_messages', [
            'id' => $message->id,
            'practice_session_id' => $session->id,
            'sender' => 'ai',
        ]);

        $this->assertTrue($session->messages->contains($message));
        $this->assertTrue($message->practiceSession->is($session));
        $this->assertIsArray($message->facial_status);
    }

    /**
     * Test that AiRoleSeeder populates Dosen Penguji, HRD, and Investor roles.
     */
    public function test_ai_role_seeder_populates_roles(): void
    {
        $this->seed(AiRoleSeeder::class);

        $this->assertDatabaseHas('ai_roles', ['role_type' => 'dosen_penguji']);
        $this->assertDatabaseHas('ai_roles', ['role_type' => 'hrd']);
        $this->assertDatabaseHas('ai_roles', ['role_type' => 'investor']);
    }
}
