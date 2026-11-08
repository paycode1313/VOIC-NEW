<?php

namespace Tests\Feature;

use App\Models\PracticeSession;
use App\Models\User;
use App\Services\Exam\ExamFlowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ExamFlowTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Simulate no local AI service and no TTS: everything runs through the offline composer.
     */
    private function offline(): void
    {
        Http::fake(['*' => Http::response([], 503)]);
    }

    public function test_mode_picker_and_setup_pages_render_for_guests(): void
    {
        $this->offline();

        $this->get(route('exam.index'))
            ->assertOk()
            ->assertSee('Wawancara Kerja')
            ->assertSee('Sidang Skripsi')
            ->assertSee('Pitching Startup');

        $this->get(route('exam.setup', 'sidang'))
            ->assertOk()
            ->assertSee('Judul skripsi')
            ->assertSee('Prof. Hendra');

        $this->get('/latihan/tidak-ada')->assertNotFound();
    }

    public function test_guest_can_start_an_exam_and_open_the_room(): void
    {
        $this->offline();

        $response = $this->post(route('exam.start', 'wawancara'), [
            'topic' => 'Frontend Developer',
            'language' => 'id',
            'parts' => [1, 2, 3],
            'guest_name' => 'Rina',
            'consent' => '1',
        ]);

        $session = PracticeSession::firstOrFail();
        $response->assertRedirect(route('exam.room', $session));

        $this->assertSame('wawancara', $session->exam_mode);
        $this->assertNull($session->user_id);
        $this->assertSame(1, $session->exam_state['part']);
        $this->assertSame(ExamFlowService::PHASE_ANSWERING, $session->exam_state['phase']);

        $opening = $session->messages()->first();
        $this->assertSame('ai', $opening->sender);
        $this->assertStringContainsString('Kak Nadia', $opening->message);
        $this->assertStringContainsString('perkenalkan diri', $opening->message);

        $this->get(route('exam.room', $session))->assertOk()->assertSee('Mulai ujian');
    }

    public function test_short_answer_gets_a_follow_up_that_reuses_the_candidate_words(): void
    {
        $this->offline();

        $session = $this->startExam('wawancara', [1]);

        $response = $this->postJson(route('exam.answer', $session), [
            'answer' => 'Saya lulusan informatika.',
            'duration_seconds' => 4,
            'timestamp_seconds' => 10,
        ]);

        $response->assertOk()->assertJsonPath('part', 1);
        $text = $response->json('examiner_message.text');
        $this->assertStringContainsString('?', $text);
        $this->assertSame('offline_composer', $response->json('examiner_message.engine'));
        $this->assertSame(1, $session->refresh()->exam_state['followups_used']);
        $this->assertSame(0, $session->exam_state['question_index']);
    }

    public function test_full_three_part_exam_produces_a_report(): void
    {
        $this->offline();

        $user = User::factory()->create();
        $this->actingAs($user);

        $session = $this->startExam('pitching', [1, 2, 3]);
        $answer = 'Kami membangun aplikasi kasir untuk UMKM karena pencatatan manual membuat pemilik toko kehilangan sekitar 10 persen pendapatan setiap bulan. Saat ini kami punya 300 pelanggan aktif dengan retensi 80 persen, misalnya toko kelontong di Malang yang menghemat dua jam per hari.';

        $sawCueCard = false;
        for ($i = 0; $i < 20; $i++) {
            $state = $session->refresh()->exam_state;
            if ($state['phase'] === ExamFlowService::PHASE_COMPLETED) {
                break;
            }

            if ($state['phase'] === ExamFlowService::PHASE_CUE_CARD) {
                $sawCueCard = true;
            }

            $this->postJson(route('exam.answer', $session), [
                'answer' => $answer,
                'duration_seconds' => $state['phase'] === ExamFlowService::PHASE_CUE_CARD ? 90 : 25,
                'timestamp_seconds' => $i * 30,
                'facial' => ['eye_contact' => 82, 'face_visible' => 100, 'samples' => 40],
            ])->assertOk();
        }

        $this->assertTrue($sawCueCard, 'Part 2 cue card phase was never reached.');
        $this->assertSame(ExamFlowService::PHASE_COMPLETED, $session->refresh()->exam_state['phase']);

        // Every examiner turn must be distinct: no repeated questions.
        $aiLines = $session->messages()->where('sender', 'ai')->pluck('message');
        $this->assertSame($aiLines->count(), $aiLines->unique()->count());

        $this->postJson(route('exam.finish', $session), ['duration_seconds' => 600])
            ->assertOk()
            ->assertJsonPath('redirect_url', route('exam.result', $session));

        $session->refresh();
        $report = $session->feedback_notes['exam_report'];
        $this->assertGreaterThan(0, $report['overall_band']);
        $this->assertLessThanOrEqual(9, $report['overall_band']);
        $this->assertCount(4, $report['criteria']);
        $this->assertSame(82, $report['stats']['eye_contact']);
        $this->assertNotEmpty(array_filter($report['answers'], fn ($item) => $item['kind'] === 'long_turn'));
        $this->assertNotEmpty(array_filter($report['answers'], fn ($item) => $item['improved'] !== null));
        $this->assertGreaterThan(0, $session->overall_score);

        $this->get(route('exam.result', $session))
            ->assertOk()
            ->assertSee('Masukan per jawaban')
            ->assertSee(number_format($report['overall_band'], 1));

        $this->postJson(route('exam.answer', $session), ['answer' => 'lagi', 'duration_seconds' => 1, 'timestamp_seconds' => 1])
            ->assertStatus(409);
    }

    public function test_model_asks_the_next_question_in_its_own_words(): void
    {
        $modelReply = 'Menarik, YOLOv8 untuk cacat kain dengan 2000 gambar itu cukup besar. Ngomong-ngomong, apa yang awalnya bikin Anda tertarik ke topik ini, apakah dari pengalaman di pabrik?';
        Http::fake([
            '*/exam/respond' => Http::response(['reply' => $modelReply]),
            '*/tts' => Http::response(['audio_file' => 'abc.mp3']),
            '*' => Http::response([], 503),
        ]);

        $session = $this->startExam('sidang', [1]);

        $response = $this->postJson(route('exam.answer', $session), [
            'answer' => 'Nama saya Andi, judul penelitian saya adalah deteksi cacat kain menggunakan YOLOv8 dengan dataset 2000 gambar dari pabrik tekstil di Bandung.',
            'duration_seconds' => 15,
            'timestamp_seconds' => 15,
        ])->assertOk();

        // The model's own wording is spoken as-is; no scripted question is bolted on.
        $this->assertSame($modelReply, $response->json('examiner_message.text'));
        $this->assertSame('local_llm', $response->json('examiner_message.engine'));
        $this->assertStringEndsWith('/audio/abc.mp3', $response->json('examiner_message.audio_url'));
        $this->assertSame(1, $session->refresh()->exam_state['question_index']);

        // The model was told which topic comes next, as guidance rather than a script.
        Http::assertSent(function ($request) {
            if (! str_ends_with($request->url(), '/exam/respond')) {
                return false;
            }
            $messages = $request['messages'];
            $instruction = $messages[count($messages) - 1]['content'];

            return $request['directive'] === 'next' && str_contains($instruction, 'dengan kata-katamu sendiri');
        });
    }

    public function test_model_reaction_without_question_falls_back_to_the_planned_question(): void
    {
        Http::fake([
            '*/exam/respond' => Http::response(['reply' => 'Baik, YOLOv8 dengan 2000 gambar, saya catat.']),
            '*' => Http::response([], 503),
        ]);

        $session = $this->startExam('sidang', [1]);
        $response = $this->postJson(route('exam.answer', $session), [
            'answer' => 'Judul penelitian saya deteksi cacat kain menggunakan YOLOv8 dengan dataset 2000 gambar dari pabrik tekstil.',
            'duration_seconds' => 12,
            'timestamp_seconds' => 12,
        ])->assertOk();

        $text = $response->json('examiner_message.text');
        $this->assertStringStartsWith('Baik, YOLOv8 dengan 2000 gambar, saya catat.', $text);
        $this->assertStringEndsWith('?', $text);
    }

    public function test_candidate_can_ask_back_without_the_exam_moving_on(): void
    {
        $this->offline();
        $session = $this->startExam('wawancara', [1]);
        $before = $session->exam_state;

        $response = $this->postJson(route('exam.answer', $session), [
            'answer' => 'Maaf kak, maksudnya gimana ya?',
            'duration_seconds' => 3,
            'timestamp_seconds' => 3,
        ])->assertOk();

        $this->assertStringContainsString('Maksud saya', $response->json('examiner_message.text'));
        $after = $session->refresh()->exam_state;
        $this->assertSame($before['question_index'], $after['question_index']);
        $this->assertSame($before['followups_used'], $after['followups_used']);
        $this->assertSame('clarification', $session->messages()->where('sender', 'user')->first()->meta['kind']);
    }

    public function test_status_reports_which_ai_engine_is_answering(): void
    {
        Http::fake(['*/voice/status' => Http::response([
            'llm' => ['available' => true, 'models' => ['qwen2.5:3b', 'voic-coach:latest']],
            'stt' => ['available' => true],
            'tts' => ['engine' => 'piper', 'local' => true],
        ])]);
        $this->getJson(route('exam.status'))->assertOk()->assertJson([
            'engine' => 'local_llm',
            'model' => 'voic-coach:latest',
            'stt_local' => true,
            'tts_local' => true,
        ]);
    }

    public function test_status_reports_offline_fallback_when_no_ai_is_reachable(): void
    {
        $this->offline();
        $this->getJson(route('exam.status'))->assertOk()->assertJson(['engine' => 'offline_composer']);
    }

    public function test_voice_answer_is_transcribed_locally_and_reply_streams_sentence_by_sentence(): void
    {
        $ndjson = implode("\n", [
            json_encode(['type' => 'sentence', 'text' => 'Dua ribu gambar dari pabrik itu dataset yang lumayan.', 'audio_file' => 'tts_1_a.wav']),
            json_encode(['type' => 'sentence', 'text' => 'Ngomong-ngomong, apa yang bikin Anda tertarik ke topik ini?', 'audio_file' => null, 'audio_data' => 'data:audio/wav;base64,UklGRg==']),
            json_encode(['type' => 'done', 'reply' => '...', 'issues' => []]),
        ])."\n";
        Http::fake([
            '*/stt' => Http::response(['text' => 'Judul penelitian saya deteksi cacat kain dengan YOLOv8 memakai 2000 gambar dari pabrik tekstil.', 'engine' => 'faster-whisper:small']),
            '*/exam/respond/stream' => Http::response($ndjson, 200, ['Content-Type' => 'application/x-ndjson']),
            '*' => Http::response([], 503),
        ]);

        $session = $this->startExam('sidang', [1]);
        $response = $this->post(route('exam.answer.stream', $session), [
            'audio' => UploadedFile::fake()->create('answer.webm', 40, 'audio/webm'),
            'duration_seconds' => 9,
            'timestamp_seconds' => 9,
        ])->assertOk();

        $events = array_map(fn ($line) => json_decode($line, true), array_values(array_filter(explode("\n", $response->streamedContent()))));
        $this->assertSame(['transcript', 'sentence', 'sentence', 'done'], array_column($events, 'type'));
        $this->assertStringContainsString('YOLOv8', $events[0]['text']);
        $this->assertSame(route('exam.audio', 'tts_1_a.wav'), $events[1]['audio_url']);
        $this->assertSame('data:audio/wav;base64,UklGRg==', $events[2]['audio_url']);
        $this->assertSame(1, $events[3]['state']['part']);

        $ai = $session->messages()->where('sender', 'ai')->latest('id')->first();
        $this->assertSame('Dua ribu gambar dari pabrik itu dataset yang lumayan. Ngomong-ngomong, apa yang bikin Anda tertarik ke topik ini?', $ai->message);
        $this->assertSame('local_llm', $ai->meta['engine']);
        $this->assertSame(route('exam.audio', 'tts_1_a.wav'), $ai->audio_url);
        Http::assertSent(fn ($request) => str_ends_with($request->url(), '/exam/respond/stream') && $request['inline_audio'] === true);
        $this->assertStringContainsString('YOLOv8', $session->messages()->where('sender', 'user')->first()->message);
    }

    public function test_stream_falls_back_to_one_offline_sentence_when_no_model_runs(): void
    {
        $this->offline();
        $session = $this->startExam('wawancara', [1]);

        $response = $this->post(route('exam.answer.stream', $session), [
            'answer' => 'Saya lulusan informatika dan dua tahun jadi web developer di agensi digital yang pegang proyek klien e-commerce.',
            'duration_seconds' => 12,
            'timestamp_seconds' => 12,
        ])->assertOk();

        $events = array_map(fn ($line) => json_decode($line, true), array_values(array_filter(explode("\n", $response->streamedContent()))));
        $this->assertSame(['sentence', 'done'], array_column($events, 'type'));
        $this->assertStringEndsWith('?', $events[0]['text']);
        $this->assertNull($events[0]['audio_url']);
    }

    public function test_silent_recording_asks_the_candidate_to_repeat_without_recording_anything(): void
    {
        Http::fake(['*/stt' => Http::response(['text' => '']), '*' => Http::response([], 503)]);
        $session = $this->startExam('wawancara', [1]);

        $response = $this->post(route('exam.answer.stream', $session), [
            'audio' => UploadedFile::fake()->create('answer.webm', 10, 'audio/webm'),
            'duration_seconds' => 3,
            'timestamp_seconds' => 3,
        ])->assertOk();

        $events = array_map(fn ($line) => json_decode($line, true), array_values(array_filter(explode("\n", $response->streamedContent()))));
        $this->assertSame(['transcript', 'retry'], array_column($events, 'type'));
        $this->assertSame(0, $session->messages()->where('sender', 'user')->count());
    }

    public function test_audio_proxy_only_serves_generated_voice_files(): void
    {
        Http::fake(['*/audio/tts_1_a.wav' => Http::response('RIFF-fake-wav', 200)]);

        $response = $this->get(route('exam.audio', 'tts_1_a.wav'))->assertOk();
        $this->assertSame('RIFF-fake-wav', $response->streamedContent());
        $this->assertSame('audio/wav', $response->headers->get('Content-Type'));

        $this->get('/latihan/audio/..%2F.env')->assertNotFound();
    }

    public function test_status_explains_why_the_ai_is_offline(): void
    {
        Http::fake(['*/voice/status' => Http::response(['llm' => ['available' => false, 'models' => []], 'stt' => ['available' => false], 'tts' => ['local' => false]])]);

        $this->getJson(route('exam.status'))->assertOk()->assertJson([
            'engine' => 'offline_composer',
            'reason' => 'Ollama tidak berjalan.',
        ]);
    }

    public function test_status_flags_missing_voic_coach_but_keeps_local_ai_on(): void
    {
        Http::fake(['*/voice/status' => Http::response(['llm' => ['available' => true, 'models' => ['qwen2.5:3b'], 'exam_model_ready' => false], 'stt' => ['available' => false], 'tts' => ['local' => false]])]);

        $status = $this->getJson(route('exam.status'))->assertOk()->json();
        $this->assertSame('local_llm', $status['engine']);
        $this->assertSame('qwen2.5:3b', $status['model']);
        $this->assertStringContainsString('ollama create voic-coach', $status['fix']);
    }

    public function test_status_detects_an_ai_service_started_before_the_update(): void
    {
        Http::fake([
            '*/voice/status' => Http::response(['detail' => 'Not Found'], 404),
            '*/health/ollama' => Http::response(['ollama_active' => true, 'available_models' => ['voic-coach:latest']]),
        ]);

        $status = $this->getJson(route('exam.status'))->assertOk()->json();
        $this->assertSame('local_llm', $status['engine']);
        $this->assertStringContainsString('versi lama', $status['reason']);
    }

    public function test_doctor_reports_unreachable_ai_service(): void
    {
        Http::fake(fn () => throw new ConnectionException('Connection refused'));

        $this->artisan('voic:doctor')
            ->expectsOutputToContain('tidak bisa dihubungi')
            ->assertFailed();
    }

    public function test_exam_cannot_start_without_consent(): void
    {
        $this->offline();

        $this->post(route('exam.start', 'sidang'), ['topic' => 'x', 'parts' => [1]])
            ->assertSessionHasErrors('consent');
        $this->assertSame(0, PracticeSession::count());
    }

    public function test_report_has_a_qr_link_that_opens_on_a_phone_without_login(): void
    {
        $this->offline();
        config(['exam.public_url' => 'http://192.168.1.50:8000']);
        $session = $this->finishedSession();

        $page = $this->get(route('exam.result', $session))->assertOk()->assertSee('Bawa pulang rapormu');
        preg_match('/data-url="([^"]+)"/', $page->getContent(), $match);
        $shareUrl = html_entity_decode($match[1]);
        $this->assertStringStartsWith('http://192.168.1.50:8000/latihan/hasil/'.$session->id.'?expires=', $shareUrl);

        // A different browser (the phone) with no session can open the signed link, read-only.
        $this->flushSession();
        $path = substr($shareUrl, strlen('http://192.168.1.50:8000'));
        $this->get($path)->assertOk()->assertSee('Masukan per jawaban')->assertDontSee('Hapus data saya');

        // Tampering with the link is rejected.
        $this->get(str_replace('expires=', 'expires=1', $path))->assertForbidden();
    }

    public function test_candidate_can_delete_their_session(): void
    {
        $this->offline();
        $session = $this->finishedSession();
        Storage::fake('local');
        Storage::disk('local')->put($session->recordingsDirectory().'/1.webm', 'audio');

        $this->delete(route('exam.destroy', $session))->assertRedirect(route('exam.index'));

        $this->assertModelMissing($session);
        Storage::disk('local')->assertMissing($session->recordingsDirectory().'/1.webm');
    }

    public function test_prune_removes_old_guest_sessions_and_old_recordings(): void
    {
        $this->offline();
        Storage::fake('local');
        $oldGuest = $this->finishedSession();
        $user = User::factory()->create();
        $this->actingAs($user);
        $oldUser = $this->finishedSession();
        $fresh = $this->finishedSession();
        foreach ([$oldGuest, $oldUser, $fresh] as $session) {
            Storage::disk('local')->put($session->recordingsDirectory().'/1.webm', 'audio');
        }
        PracticeSession::whereKey([$oldGuest->id, $oldUser->id])->update(['created_at' => now()->subDays(10)]);

        $this->artisan('voic:prune')->assertSuccessful();

        $this->assertModelMissing($oldGuest);
        $this->assertModelExists($oldUser);
        Storage::disk('local')->assertMissing($oldUser->recordingsDirectory().'/1.webm');
        Storage::disk('local')->assertExists($fresh->recordingsDirectory().'/1.webm');
    }

    /**
     * Start and immediately finish a one-part exam with one answer.
     */
    private function finishedSession(): PracticeSession
    {
        $session = $this->startExam('sidang', [1]);
        $this->postJson(route('exam.answer', $session), [
            'answer' => 'Penelitian saya memakai YOLOv8 dengan 2000 gambar dari pabrik tekstil karena butuh deteksi cepat.',
            'duration_seconds' => 12,
            'timestamp_seconds' => 12,
        ])->assertOk();
        $this->postJson(route('exam.finish', $session), ['duration_seconds' => 60])->assertOk();

        return $session->refresh();
    }

    public function test_other_visitors_cannot_answer_someone_elses_session(): void
    {
        $this->offline();

        $owner = User::factory()->create();
        $this->actingAs($owner);
        $session = $this->startExam('sidang', [1]);

        $this->actingAs(User::factory()->create());
        $this->postJson(route('exam.answer', $session), ['answer' => 'x', 'duration_seconds' => 1, 'timestamp_seconds' => 1])
            ->assertForbidden();
        $this->get(route('exam.room', $session))->assertForbidden();
    }

    public function test_rated_and_cloud_replies_are_exported_as_training_data(): void
    {
        config(['services.groq.key' => 'test-key']);
        Http::fake([
            'api.groq.com/*' => Http::response(['choices' => [['message' => ['content' => 'Dataset 2000 gambar dari pabrik itu cukup besar. Bagaimana Anda membagi data latih dan uji?']]]]),
            '*' => Http::response([], 503),
        ]);

        $user = User::factory()->create();
        $this->actingAs($user);
        $session = $this->startExam('sidang', [3]);

        $this->postJson(route('exam.answer', $session), [
            'answer' => 'Saya memakai YOLOv8 dengan dataset 2000 gambar dari pabrik tekstil karena butuh deteksi real-time.',
            'duration_seconds' => 12,
            'timestamp_seconds' => 12,
        ])->assertOk()->assertJsonPath('examiner_message.engine', 'cloud_llm');

        $reply = $session->messages()->where('sender', 'ai')->latest('id')->first();
        $this->assertSame('follow_up', $reply->meta['kind']);
        $this->assertStringStartsWith('Dataset 2000 gambar', $reply->meta['reply']);

        // Rating marks the reply; a "bad" rating removes it from the export.
        $this->postJson(route('exam.rate', [$session, $reply]), ['rating' => 'good'])->assertOk();
        $this->assertSame('good', $reply->refresh()->meta['rating']);

        $output = 'storage/framework/testing/sft.jsonl';
        $this->artisan('voic:export-training-data', ['--output' => $output, '--eval-output' => 'storage/framework/testing/eval.jsonl', '--eval-ratio' => 0])
            ->assertSuccessful();

        $rows = array_map(fn ($line) => json_decode($line, true), array_filter(explode("\n", file_get_contents(base_path($output)))));
        $fromSession = array_values(array_filter($rows, fn ($row) => $row['meta']['source'] === 'rated_good'));
        $this->assertCount(1, $fromSession);
        $this->assertNotEmpty(array_filter($rows, fn ($row) => $row['meta']['source'] === 'seed'));

        $messages = $fromSession[0]['messages'];
        $this->assertSame('system', $messages[0]['role']);
        $this->assertStringContainsString('Prof. Hendra', $messages[0]['content']);
        // History ends with the examiner question being answered, exactly like at inference time.
        $this->assertSame('assistant', $messages[count($messages) - 3]['role']);
        $this->assertStringContainsString('Jawaban peserta: Saya memakai YOLOv8', $messages[count($messages) - 2]['content']);
        $this->assertSame($reply->meta['reply'], $messages[count($messages) - 1]['content']);

        $this->postJson(route('exam.rate', [$session, $reply]), ['rating' => 'bad'])->assertOk();
        $this->artisan('voic:export-training-data', ['--output' => $output, '--eval-output' => 'storage/framework/testing/eval.jsonl', '--eval-ratio' => 0])
            ->assertSuccessful();
        $this->assertStringNotContainsString('rated_good', file_get_contents(base_path($output)));
        $this->assertStringNotContainsString('cloud_distill', file_get_contents(base_path($output)));

        File::delete([base_path($output), base_path('storage/framework/testing/eval.jsonl')]);
    }

    public function test_a_message_from_another_session_cannot_be_rated(): void
    {
        $this->offline();
        $first = $this->startExam('sidang', [1]);
        $second = $this->startExam('sidang', [1]);
        $foreignMessage = $first->messages()->first();

        $this->postJson(route('exam.rate', [$second, $foreignMessage]), ['rating' => 'good'])->assertNotFound();
    }

    /**
     * @param  list<int>  $parts
     */
    private function startExam(string $mode, array $parts): PracticeSession
    {
        $this->post(route('exam.start', $mode), [
            'topic' => 'Topik uji',
            'language' => 'id',
            'parts' => $parts,
            'consent' => '1',
        ])->assertRedirect();

        return PracticeSession::latest('id')->firstOrFail();
    }
}
