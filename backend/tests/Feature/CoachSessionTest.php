<?php

namespace Tests\Feature;

use App\Models\PracticeSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CoachSessionTest extends TestCase
{
    use RefreshDatabase;

    private function startCoach(string $mode = 'wawancara'): PracticeSession
    {
        $this->post(route('exam.start', $mode), [
            'topic' => 'Data Analyst',
            'session_type' => 'coach',
            'consent' => '1',
        ])->assertRedirect();

        return PracticeSession::latest('id')->firstOrFail();
    }

    public function test_setup_defaults_to_coaching(): void
    {
        $this->get(route('exam.setup', 'wawancara'))->assertOk()
            ->assertSee('Ngobrol dengan coach')
            ->assertSee('Simulasi ujian')
            ->assertSee('data-default-type="coach"', false);

        $this->get(route('exam.setup', ['mode' => 'wawancara', 'type' => 'exam']))->assertSee('data-default-type="exam"', false);
    }

    public function test_coach_opens_with_a_friendly_question_and_a_ten_minute_timer(): void
    {
        Http::fake(['*' => Http::response([], 503)]);
        $session = $this->startCoach();

        $this->assertTrue($session->isCoaching());
        $this->assertStringContainsString('bukan tes', $session->messages()->first()->message);

        $this->get(route('exam.room', $session))->assertOk()->assertSee('remaining_seconds', false);
        $this->assertSame('Coach Wawancara · Data Analyst', $session->scenario_type);
    }

    public function test_coach_turn_teaches_with_the_coach_persona_and_never_moves_through_parts(): void
    {
        $reply = 'Seru banget, magang di e-commerce itu modal bagus. Coba buka dengan hasilnya, misalnya: "Saya membantu tim menaikkan konversi 15 persen." Sekarang coba ucapkan ulang perkenalanmu dengan kalimat itu.';
        Http::fake([
            '*/exam/respond' => Http::response(['reply' => $reply]),
            '*' => Http::response([], 503),
        ]);
        $session = $this->startCoach();

        $response = $this->postJson(route('exam.answer', $session), [
            'answer' => 'Halo kak, saya Rina, lulusan statistika, pernah magang jadi analis data di e-commerce.',
            'duration_seconds' => 9,
            'timestamp_seconds' => 9,
        ])->assertOk()
            ->assertJsonPath('session_type', 'coach')
            ->assertJsonPath('completed', false);

        // The full coaching reply (4 sentences, ends without a question) is kept as is.
        $this->assertSame($reply, $response->json('examiner_message.text'));
        $this->assertGreaterThan(500, $response->json('remaining_seconds'));

        Http::assertSent(function ($request) {
            if (! str_ends_with($request->url(), '/exam/respond')) {
                return false;
            }
            $system = $request['messages'][0]['content'];

            return $request['directive'] === 'coach'
                && str_contains($system, 'BUKAN tes')
                && str_contains($system, 'pola STAR');
        });
    }

    public function test_offline_coach_gives_a_concrete_tip_and_keeps_going(): void
    {
        Http::fake(['*' => Http::response([], 503)]);
        $session = $this->startCoach();

        $text = $this->postJson(route('exam.answer', $session), [
            'answer' => 'Eh saya eh lulusan statistika.',
            'duration_seconds' => 4,
            'timestamp_seconds' => 4,
        ])->assertOk()->json('examiner_message.text');

        $this->assertStringContainsString('"eh"', $text);
        $this->assertStringContainsString('Coba ucapkan ulang', $text);
    }

    public function test_session_closes_when_time_is_up_and_shows_coaching_notes(): void
    {
        Http::fake(['*' => Http::response([], 503)]);
        $session = $this->startCoach();

        $this->postJson(route('exam.answer', $session), [
            'answer' => 'Saya pernah memimpin proyek dashboard penjualan, waktu itu datanya berantakan, jadi saya bersihkan dan hasilnya laporan selesai dua hari lebih cepat.',
            'duration_seconds' => 14,
            'timestamp_seconds' => 14,
        ])->assertOk();

        // Wrap-up hint in the last 90 seconds, then the timer runs out while the person is silent.
        $this->travel(9)->minutes();
        $this->travel(31)->seconds();
        $this->postJson(route('exam.answer', $session), ['answer' => '', 'duration_seconds' => 0, 'timestamp_seconds' => 600, 'time_up' => true])
            ->assertOk()
            ->assertJsonPath('completed', true);

        $this->assertSame(1, $session->messages()->where('sender', 'user')->count());
        $closing = $session->messages()->where('sender', 'ai')->latest('id')->first();
        $this->assertSame('coach_close', $closing->meta['kind']);
        $this->assertStringNotContainsString('?', $closing->message);

        $this->postJson(route('exam.finish', $session))->assertOk();
        $notes = $session->refresh()->feedback_notes['coach_report'];
        $this->assertSame('coach', $notes['type']);
        $this->assertSame(1, $notes['stats']['turns']);
        $this->assertNotEmpty($notes['strengths']);
        $this->assertNotEmpty($notes['practice']);
        $this->assertArrayNotHasKey('exam_report', $session->feedback_notes);

        $this->get(route('exam.result', $session))->assertOk()
            ->assertSee('Catatan coaching kamu')
            ->assertSee('Sudah bagus')
            ->assertSee('Latih berikutnya')
            ->assertDontSee('Band');

        $this->postJson(route('exam.answer', $session), ['answer' => 'lagi', 'duration_seconds' => 1, 'timestamp_seconds' => 1])->assertStatus(409);
    }

    public function test_coach_phrases_are_collected_from_quoted_examples(): void
    {
        $reply = 'Bagus. Coba bilang: "Saya terbiasa menjelaskan data rumit dengan bahasa sederhana untuk tim bisnis." Sekarang coba ulangi dengan kalimat itu.';
        Http::fake([
            '*/exam/respond' => Http::response(['reply' => $reply]),
            '*' => Http::response([], 503),
        ]);
        $session = $this->startCoach();
        $this->postJson(route('exam.answer', $session), ['answer' => 'Saya suka menjelaskan data ke tim bisnis supaya mereka paham.', 'duration_seconds' => 6, 'timestamp_seconds' => 6])->assertOk();

        $this->postJson(route('exam.finish', $session))->assertOk();

        $this->assertContains('Saya terbiasa menjelaskan data rumit dengan bahasa sederhana untuk tim bisnis.', $session->refresh()->feedback_notes['coach_report']['phrases']);
        $this->get(route('exam.result', $session))->assertSee('Kalimat yang bisa kamu pakai');
    }

    public function test_coaching_sessions_stay_out_of_band_progress_and_leaderboard(): void
    {
        Http::fake(['*' => Http::response([], 503)]);
        $user = User::factory()->create();
        $this->actingAs($user);
        $session = $this->startCoach();
        $this->postJson(route('exam.answer', $session), ['answer' => 'Halo kak, saya Rina dari statistika.', 'duration_seconds' => 4, 'timestamp_seconds' => 4])->assertOk();
        $this->postJson(route('exam.finish', $session))->assertOk();

        $this->get(route('dashboard'))->assertOk()->assertSee($session->reportUrl(), false)->assertDontSee('Perkembangan band keseluruhan');
        $this->get(route('practice.history'))->assertOk()->assertSee('coach');
    }

    public function test_a_weak_answer_from_the_report_can_be_practised_again_with_the_coach(): void
    {
        Http::fake(['*' => Http::response([], 503)]);
        $user = User::factory()->create();
        $this->actingAs($user);

        // A finished exam with a weak answer.
        $this->post(route('exam.start', 'wawancara'), ['topic' => 'Data Analyst', 'session_type' => 'exam', 'parts' => [1], 'consent' => '1'])->assertRedirect();
        $exam = PracticeSession::latest('id')->firstOrFail();
        for ($i = 0; $i < 8 && ($exam->refresh()->exam_state['phase'] ?? null) !== 'completed'; $i++) {
            $this->postJson(route('exam.answer', $exam), ['answer' => 'Saya suka data karena seru aja sih.', 'duration_seconds' => 6, 'timestamp_seconds' => $i * 10])->assertOk();
        }
        $this->postJson(route('exam.finish', $exam), ['duration_seconds' => 120])->assertOk();
        $answer = $exam->refresh()->feedback_notes['exam_report']['answers'][0];

        // Every answer card offers a retry with the coach.
        $this->get(route('exam.result', $exam))->assertOk()->assertSee('Latih ulang pertanyaan ini');

        $this->post(route('exam.start', 'wawancara'), [
            'session_type' => 'coach', 'language' => 'id', 'topic' => 'Data Analyst', 'consent' => '1',
            'retry_question' => $answer['question'], 'retry_answer' => $answer['answer'],
        ])->assertRedirect();
        $coach = PracticeSession::latest('id')->firstOrFail();

        // The coach opens with that very question and asks for another go.
        $opening = $coach->messages()->first()->message;
        $this->assertStringContainsString('latih ulang', $opening);
        $this->assertStringContainsString($answer['question'], $opening);

        // The coach model knows the first answer, to compare and show the progress.
        $this->postJson(route('exam.answer', $coach), ['answer' => 'Saya tertarik ke data karena waktu magang saya bikin dashboard penjualan yang dipakai tiap minggu.', 'duration_seconds' => 12, 'timestamp_seconds' => 20])->assertOk();
        Http::assertSent(fn ($request) => str_contains((string) json_encode($request->data()), 'Saya suka data karena seru aja sih.')
            && str_contains((string) json_encode($request->data(), JSON_UNESCAPED_UNICODE), 'melatih ulang'));
    }
}
