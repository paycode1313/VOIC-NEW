<?php

namespace Tests\Feature;

use App\Models\PracticeSession;
use App\Models\User;
use App\Services\Exam\ExamFlowService;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Sleep;
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

    public function test_room_ships_the_live_avatar_with_its_face_rig(): void
    {
        $this->offline();

        $session = $this->startExam('sidang', [1]);

        $this->get(route('exam.room', $session))
            ->assertOk()
            ->assertSee('x-ref="avatarCanvas"', false)
            ->assertSee('dosen-portrait.webp', false)
            ->assertSee('mouthW', false);

        foreach (config('exam.modes') as $key => $mode) {
            $rig = $mode['examiner']['rig'];
            $this->assertFileExists(public_path($mode['examiner']['avatar']), $key);
            $this->assertCount(2, $rig['eyes'], $key);
            $this->assertGreaterThan($rig['mouth'][1], $rig['chin'], "{$key}: chin must be below the mouth");
            $this->assertGreaterThan($rig['eyes'][0][1], $rig['mouth'][1], "{$key}: mouth must be below the eyes");
        }
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

    public function test_examiner_responds_when_the_camera_sees_a_tense_face(): void
    {
        $this->offline();

        $session = $this->startExam('wawancara', [1]);

        $response = $this->postJson(route('exam.answer', $session), [
            'answer' => 'Saya lulusan informatika.',
            'duration_seconds' => 4,
            'timestamp_seconds' => 10,
            'facial' => ['eye_contact' => 90, 'face_visible' => 100, 'smile' => 0, 'tension' => 70, 'samples' => 20],
        ]);

        $response->assertOk();
        $this->assertStringContainsString('tarik napas', $response->json('examiner_message.text'));
        $this->assertSame(70, $session->messages()->where('sender', 'user')->first()->facial_status['tension']);
    }

    public function test_examiner_reminds_about_eye_contact_and_ignores_a_relaxed_face(): void
    {
        $this->offline();

        $session = $this->startExam('sidang', [1]);

        $away = $this->postJson(route('exam.answer', $session), [
            'answer' => 'Penelitian saya tentang sistem rekomendasi.',
            'duration_seconds' => 5,
            'timestamp_seconds' => 10,
            'facial' => ['eye_contact' => 20, 'face_visible' => 100, 'tension' => 5, 'samples' => 20],
        ]);
        $this->assertStringContainsString('menatap kamera', $away->json('examiner_message.text'));

        $relaxed = $this->postJson(route('exam.answer', $session), [
            'answer' => 'Metodenya kuantitatif dengan 120 responden.',
            'duration_seconds' => 5,
            'timestamp_seconds' => 20,
            'facial' => ['eye_contact' => 95, 'face_visible' => 100, 'smile' => 60, 'tension' => 0, 'samples' => 20],
        ]);
        $text = $relaxed->json('examiner_message.text');
        $this->assertStringNotContainsString('tegang', $text);
        $this->assertStringNotContainsString('menatap kamera', $text);
    }

    public function test_out_of_range_expression_values_are_rejected(): void
    {
        $this->offline();

        $session = $this->startExam('wawancara', [1]);

        $this->postJson(route('exam.answer', $session), [
            'answer' => 'Halo.',
            'duration_seconds' => 2,
            'timestamp_seconds' => 1,
            'facial' => ['tension' => 150],
        ])->assertJsonValidationErrors('facial.tension');
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
                'facial' => ['eye_contact' => 82, 'face_visible' => 100, 'tension' => 10, 'samples' => 40],
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
        $this->assertSame(90, $report['stats']['composure']);
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

            // The per-turn instruction is a short tag (fast to read on a CPU); what the tag means is
            // explained once in the system prompt.
            return $request['directive'] === 'next'
                && preg_match('/\[Bagian 1 - .+ \| LANJUT: ".+"\]$/su', $instruction) === 1
                && str_contains($messages[0]['content'], '[LANJUT: "topik"]')
                && str_contains($messages[0]['content'], 'dengan kata-katamu sendiri');
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
        Storage::fake('local');
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

        // Waiting time per turn is recorded for voic:latency: speech-to-text, first sound, total.
        $latency = $ai->meta['latency'];
        $this->assertTrue($latency['voice']);
        $this->assertIsInt($latency['stt_ms']);
        $this->assertGreaterThanOrEqual($latency['stt_ms'], $latency['first_sound_ms']);
        $this->assertGreaterThanOrEqual($latency['first_sound_ms'], $latency['total_ms']);
        $this->artisan('voic:latency')->expectsOutputToContain('Sampai suara penguji terdengar')->assertSuccessful();
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

    public function test_recorded_answer_can_be_replayed_by_its_owner_only(): void
    {
        Storage::fake('local');
        Http::fake([
            '*/stt' => Http::response(['text' => 'Penelitian saya memakai YOLOv8 dengan 2000 gambar dari pabrik tekstil karena perlu cepat.']),
            '*' => Http::response([], 503),
        ]);
        $session = $this->startExam('sidang', [1]);

        $this->post(route('exam.answer.stream', $session), [
            'audio' => UploadedFile::fake()->createWithContent('answer.webm', str_repeat('A', 3000)),
            'duration_seconds' => 9,
            'timestamp_seconds' => 9,
        ])->assertOk()->streamedContent();

        $answer = $session->messages()->where('sender', 'user')->first();
        $this->assertSame($session->recordingsDirectory().'/'.$answer->id.'.webm', $answer->meta['recording']);
        Storage::disk('local')->assertExists($answer->meta['recording']);
        $this->assertEmpty(array_filter(Storage::disk('local')->files($session->recordingsDirectory()), fn ($file) => str_contains($file, 'pending-')));

        $this->get(route('exam.recording', [$session, $answer]))->assertOk();

        $this->postJson(route('exam.finish', $session))->assertOk();
        $this->get(route('exam.result', $session))->assertSee(route('exam.recording', [$session, $answer]), false);

        // Someone else (new browser session, not the guest who recorded it) cannot listen.
        $this->flushSession();
        $this->get(route('exam.recording', [$session, $answer]))->assertForbidden();
    }

    public function test_silent_recording_asks_the_candidate_to_repeat_without_recording_anything(): void
    {
        Storage::fake('local');
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
     * Pruning runs every hour so it still happens on a booth laptop that is off at night.
     */
    public function test_prune_is_scheduled_every_hour(): void
    {
        $event = collect(app(Schedule::class)->events())
            ->first(fn ($event) => str_contains((string) $event->command, 'voic:prune'));

        $this->assertNotNull($event);
        $this->assertSame('0 * * * *', $event->expression);
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

    public function test_progress_chart_and_targeted_practice(): void
    {
        $this->offline();
        $this->actingAs(User::factory()->create());
        $this->finishedSession();
        $this->finishedSession();

        $this->get(route('exam.index'))->assertOk()
            ->assertSee('Perkembangan band keseluruhan')
            ->assertSee('Lihat sebagai tabel')
            ->assertSee('Latih ini');

        $this->get(route('exam.result', PracticeSession::latest('id')->first()))->assertSee('Latih ini sekarang');

        // The drill link pre-selects parts and carries the focus to the examiner.
        $this->get(route('exam.setup', ['mode' => 'sidang', 'parts' => [3], 'focus' => 'structure']))
            ->assertOk()->assertSee('Latihan terarah')->assertSee('name="focus" value="structure"', false);

        Http::fake(['*/exam/respond' => Http::response(['reply' => 'Baik, metode itu masuk akal. Kenapa Anda tidak memilih metode lain?']), '*' => Http::response([], 503)]);
        $this->post(route('exam.start', 'sidang'), ['parts' => [3], 'focus' => 'structure', 'consent' => '1'])->assertRedirect();
        $session = PracticeSession::latest('id')->first();
        $this->assertSame('structure', $session->exam_state['focus']);
        $this->postJson(route('exam.answer', $session), ['answer' => 'Saya memakai regresi linear karena datanya numerik dan hubungannya linear.', 'duration_seconds' => 9, 'timestamp_seconds' => 9])->assertOk();
        Http::assertSent(fn ($request) => str_ends_with($request->url(), '/exam/respond') && str_contains($request['messages'][0]['content'], 'Peserta sedang melatih menjawab dengan poin jelas'));
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
        // The answer comes first (as in the next turn's history), the task for this turn at the very end.
        $this->assertStringStartsWith('Saya memakai YOLOv8', $messages[count($messages) - 2]['content']);
        $this->assertMatchesRegularExpression('/\R\[Bagian \d - .+\]$/su', $messages[count($messages) - 2]['content']);
        $this->assertStringNotContainsString('Bagian saat ini', $messages[0]['content']);
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

    public function test_online_speed_mode_lets_the_cloud_model_answer_first_and_falls_back_to_local(): void
    {
        config(['exam.speed_mode' => 'online', 'services.groq.key' => 'test-key']);
        $localReply = 'Dua tahun di proyek React itu relevan. Bagian mana yang paling menantang?';
        $cloudUp = true;
        Http::fake(function ($request) use (&$cloudUp, $localReply) {
            return match (true) {
                str_contains($request->url(), 'api.groq.com') => $cloudUp
                    ? Http::response(['choices' => [['message' => ['content' => 'Wah, 40 toko itu skala yang lumayan. Bagaimana kamu menjaga datanya tetap konsisten?']]]])
                    : Http::response([], 500),
                str_ends_with($request->url(), '/voice/status') => Http::response(['llm' => ['available' => true, 'models' => ['voic-coach:latest'], 'exam_model_ready' => true], 'stt' => ['available' => true], 'tts' => ['local' => true]]),
                str_ends_with($request->url(), '/exam/respond') => Http::response(['reply' => $localReply]),
                default => Http::response([], 503),
            };
        });

        // The room is told to use the browser's instant speech recognition, and shows the engine honestly.
        $this->getJson(route('exam.status'))
            ->assertJsonPath('speed_mode', 'online')
            ->assertJsonPath('engine', 'cloud_llm')
            ->assertJsonPath('prefer_browser_stt', true)
            ->assertJsonPath('stt_local', true);

        $session = $this->startExam('wawancara', [1]);
        $answer = ['answer' => 'Saya membangun dashboard penjualan React untuk 40 toko retail di Malang.', 'duration_seconds' => 15, 'timestamp_seconds' => 15];

        $this->postJson(route('exam.answer', $session), $answer)
            ->assertOk()
            ->assertJsonPath('examiner_message.engine', 'cloud_llm');
        Http::assertNotSent(fn ($request) => str_ends_with($request->url(), '/exam/respond'));

        // Internet gone: the same turn is answered by the local model, without an error for the candidate.
        $cloudUp = false;
        $this->postJson(route('exam.answer', $session), $answer)
            ->assertOk()
            ->assertJsonPath('examiner_message.engine', 'local_llm')
            ->assertJsonPath('examiner_message.text', $localReply);
    }

    public function test_cloud_model_waits_briefly_for_a_free_quota_instead_of_falling_back(): void
    {
        Sleep::fake();
        config(['exam.speed_mode' => 'online', 'services.groq.key' => 'test-key']);
        $calls = 0;
        Http::fake(function ($request) use (&$calls) {
            if (! str_contains($request->url(), 'api.groq.com')) {
                return Http::response([], 503);
            }

            return ++$calls === 1
                ? Http::response(['error' => ['message' => 'Rate limit reached for tokens per minute']], 429, ['retry-after' => '2.5'])
                : Http::response(['choices' => [['message' => ['content' => 'Wah, 40 toko itu skala yang lumayan. Bagaimana kamu menjaga datanya tetap konsisten?']]]]);
        });

        $session = $this->startExam('wawancara', [1]);
        $this->postJson(route('exam.answer', $session), ['answer' => 'Saya membangun dashboard penjualan React untuk 40 toko retail di Malang.', 'duration_seconds' => 15, 'timestamp_seconds' => 15])
            ->assertOk()
            ->assertJsonPath('examiner_message.engine', 'cloud_llm');

        $this->assertSame(2, $calls);
        Sleep::assertSlept(fn ($duration) => $duration->totalMilliseconds === 2500.0);
    }

    public function test_offline_is_the_default_speed_mode_even_with_a_cloud_key(): void
    {
        config(['services.groq.key' => 'test-key']);
        Http::fake([
            '*/voice/status' => Http::response(['llm' => ['available' => true, 'models' => ['voic-coach:latest'], 'exam_model_ready' => true], 'stt' => ['available' => true], 'tts' => ['local' => true]]),
            '*/exam/respond' => Http::response(['reply' => 'Menarik sekali. Apa bagian tersulitnya?']),
            '*' => Http::response([], 503),
        ]);

        $this->getJson(route('exam.status'))
            ->assertJsonPath('speed_mode', 'offline')
            ->assertJsonPath('engine', 'local_llm')
            ->assertJsonPath('prefer_browser_stt', false);

        $session = $this->startExam('wawancara', [1]);
        $this->postJson(route('exam.answer', $session), ['answer' => 'Saya membangun dashboard penjualan untuk 40 toko.', 'duration_seconds' => 15, 'timestamp_seconds' => 15])
            ->assertOk()
            ->assertJsonPath('examiner_message.engine', 'local_llm');
        Http::assertNotSent(fn ($request) => str_contains($request->url(), 'api.groq.com'));
    }

    public function test_prompt_only_grows_at_the_end_so_the_local_model_never_rereads_the_conversation(): void
    {
        // On a laptop CPU the model reads ~20 tokens per second: anything that changes earlier in the
        // prompt costs seconds on every turn. So each turn must repeat the previous prompt word for word.
        $sent = [];
        Http::fake(function ($request) use (&$sent) {
            if (str_ends_with($request->url(), '/exam/prime')) {
                $sent[] = ['prime' => $request['messages']];

                return Http::response(['ok' => true]);
            }
            if (str_ends_with($request->url(), '/exam/respond')) {
                $sent[] = ['turn' => $request['messages']];

                return Http::response(['reply' => 'Menarik, proyek itu terdengar menantang. Apa bagian tersulitnya?']);
            }

            return Http::response([], 503);
        });

        $session = $this->startExam('wawancara', [1, 2, 3]);
        $answers = [
            'Saya Rina, lulusan informatika, dua tahun membangun dashboard penjualan dengan React untuk 40 toko retail.',
            'Bagian tersulitnya menyatukan data dari 40 toko yang formatnya berbeda, jadi saya membuat skrip pembersih data.',
            'Saya tertarik karena ingin produk yang dipakai terus-menerus oleh banyak orang, bukan proyek sekali selesai.',
            'Eee, mungkin teliti, sabar, dan suka belajar hal baru dari rekan satu tim.',
        ];
        foreach ($answers as $index => $answer) {
            $this->postJson(route('exam.answer', $session), ['answer' => $answer, 'duration_seconds' => 20, 'timestamp_seconds' => $index * 30])->assertOk();
        }

        // The session start already let the model read the persona, the rules and the opening line.
        $prime = $sent[0]['prime'];
        $this->assertSame(['system', 'assistant', 'user'], array_column($prime, 'role'));
        $this->assertStringContainsString('Kak Nadia', $prime[0]['content']);

        $turns = array_values(array_filter(array_column($sent, 'turn')));
        $this->assertCount(4, $turns);
        $this->assertSame([$prime[0], $prime[1]], array_slice($turns[0], 0, 2));

        for ($i = 1; $i < count($turns); $i++) {
            $previous = $turns[$i - 1];
            // Everything sent before is still there, unchanged, including the earlier answer with its tag...
            $this->assertSame($previous, array_slice($turns[$i], 0, count($previous)), "Turn {$i} rewrote earlier parts of the prompt.");
            // ...followed only by the examiner's reply and the new answer.
            $this->assertCount(count($previous) + 2, $turns[$i]);
            $this->assertSame('assistant', $turns[$i][count($previous)]['role']);
            $this->assertStringStartsWith($answers[$i], end($turns[$i])['content']);
        }

        // The per-turn part is small: the answer plus a short tag (the full instructions used to be 250-380 characters).
        foreach ($turns as $i => $turn) {
            $this->assertLessThan(200, mb_strlen(end($turn)['content']) - mb_strlen($answers[$i]));
        }
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
