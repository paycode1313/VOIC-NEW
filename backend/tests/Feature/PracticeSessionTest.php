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

    /**
     * The history export holds what today's reports measure (mode, band per criterion, pace, fillers),
     * keeps sessions from the first VOIC version with their 0-100 score, and only the owner's sessions.
     */
    public function test_user_can_export_practice_sessions_to_csv(): void
    {
        $this->seed(AiRoleSeeder::class);
        $user = User::factory()->create();
        $role = AiRole::firstOrFail();

        PracticeSession::factory()->create([
            'user_id' => $user->id,
            'ai_role_id' => $role->id,
            'scenario_type' => '=HYPERLINK("http://x")',
            'overall_score' => 89.5,
            'feedback_notes' => ['language' => 'en'],
        ]);
        PracticeSession::factory()->create([
            'user_id' => $user->id,
            'ai_role_id' => $role->id,
            'exam_mode' => 'wawancara',
            'scenario_type' => 'Wawancara Kerja · Data Analyst',
            'duration_seconds' => 420,
            'overall_score' => 72.22,
            'feedback_notes' => ['exam_report' => [
                'language' => 'id',
                'overall_band' => 6.5,
                'criteria' => [
                    'fluency' => ['band' => 6.0], 'vocabulary' => ['band' => 7.0],
                    'structure' => ['band' => 6.5], 'delivery' => ['band' => 6.5],
                ],
                'stats' => ['average_wpm' => 118, 'filler_count' => 9, 'eye_contact' => 74],
                'focus' => [['title' => 'Tutup dengan angka']],
            ]],
        ]);
        PracticeSession::factory()->create(['user_id' => User::factory()->create()->id, 'scenario_type' => 'Milik orang lain']);

        $response = $this->actingAs($user)->get(route('practice.export'));

        $response->assertOk();
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('voic-riwayat-latihan-', $response->headers->get('Content-Disposition'));

        $csv = $response->streamedContent();
        $this->assertStringStartsWith("\xEF\xBB\xBF", $csv, 'Excel needs the UTF-8 BOM to show accents.');
        $rows = array_map('str_getcsv', array_filter(explode("\n", substr($csv, 3))));
        $header = array_shift($rows);
        $this->assertCount(2, $rows);
        $exam = array_combine($header, $rows[0]);
        $legacy = array_combine($header, $rows[1]);

        $this->assertSame('Wawancara Kerja', $exam['Mode']);
        $this->assertSame('Ujian', $exam['Jenis']);
        $this->assertSame('6.5', $exam['Band Keseluruhan (0-9)']);
        $this->assertSame('7.0', $exam['Kosakata & Istilah']);
        $this->assertSame('118', $exam['Kata per Menit']);
        $this->assertSame('74', $exam['Kontak Mata (%)']);
        $this->assertSame('Tutup dengan angka', $exam['Fokus Perbaikan']);
        $this->assertSame('Wawancara Kerja · Data Analyst', $exam['Skenario / Topik']);

        $this->assertSame('Versi lama', $legacy['Jenis']);
        $this->assertSame('', $legacy['Band Keseluruhan (0-9)']);
        $this->assertSame('89.5', $legacy['Skor (0-100)']);
        // Text that a spreadsheet would run as a formula is written as plain text.
        $this->assertSame("'=HYPERLINK(\"http://x\")", $legacy['Skenario / Topik']);
        $this->assertStringNotContainsString('Milik orang lain', $response->streamedContent());
    }
}
