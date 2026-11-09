<?php

namespace Tests\Feature;

use App\Models\PracticeSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Kimi K3 through Ollama ("kimi-k3:cloud"): as the live examiner, and as the teacher that
 * writes distillation data for the local student model.
 */
class TeacherModelTest extends TestCase
{
    use RefreshDatabase;

    private const REPLY = 'Dua tahun di e-commerce itu pengalaman yang relevan sekali. Bagian mana dari proyek React itu yang paling menantang buat kamu?';

    private function startExam(string $mode): PracticeSession
    {
        $this->post(route('exam.start', $mode), [
            'topic' => 'Frontend Developer',
            'language' => 'id',
            'parts' => [1],
            'guest_name' => 'Rina',
            'consent' => '1',
        ]);

        return PracticeSession::latest('id')->firstOrFail();
    }

    public function test_cloud_examiner_model_is_sent_and_labelled(): void
    {
        config(['exam.ai_model' => 'kimi-k3:cloud']);
        Http::fake([
            '*/exam/respond' => Http::response(['reply' => self::REPLY, 'model' => 'kimi-k3:cloud']),
            '*' => Http::response([], 503),
        ]);

        $session = $this->startExam('wawancara');
        $this->postJson(route('exam.answer', $session), [
            'answer' => 'Saya Dimas, dua tahun jadi web developer di agensi, banyak pegang proyek e-commerce pakai React.',
            'duration_seconds' => 10,
            'timestamp_seconds' => 10,
        ])->assertOk()->assertJsonPath('examiner_message.engine', 'ollama_cloud');

        Http::assertSent(fn ($request) => str_ends_with($request->url(), '/exam/respond') && $request['model'] === 'kimi-k3:cloud');
    }

    public function test_reply_from_the_local_fallback_is_labelled_local(): void
    {
        config(['exam.ai_model' => 'kimi-k3:cloud']);
        Http::fake([
            '*/exam/respond' => Http::response(['reply' => self::REPLY, 'model' => 'voic-coach']),
            '*' => Http::response([], 503),
        ]);

        $session = $this->startExam('wawancara');
        $this->postJson(route('exam.answer', $session), [
            'answer' => 'Saya Dimas, dua tahun jadi web developer di agensi, banyak pegang proyek e-commerce pakai React.',
            'duration_seconds' => 10,
            'timestamp_seconds' => 10,
        ])->assertOk()->assertJsonPath('examiner_message.engine', 'local_llm');
    }

    /**
     * @param  list<string>  $models
     * @return array<string, mixed>
     */
    private function voiceStatus(array $models): array
    {
        return ['llm' => ['available' => true, 'models' => $models, 'exam_model_ready' => true], 'stt' => ['available' => true], 'tts' => ['local' => true]];
    }

    public function test_status_reports_the_cloud_model_once_it_is_pulled(): void
    {
        config(['exam.ai_model' => 'kimi-k3:cloud']);
        Http::fake(['*/voice/status' => Http::response($this->voiceStatus(['voic-coach:latest', 'kimi-k3:cloud']))]);

        $this->getJson(route('exam.status'))->assertOk()->assertJson(['engine' => 'ollama_cloud', 'model' => 'kimi-k3:cloud']);
    }

    public function test_status_explains_how_to_pull_a_missing_cloud_model(): void
    {
        config(['exam.ai_model' => 'kimi-k3:cloud']);
        Http::fake(['*/voice/status' => Http::response($this->voiceStatus(['voic-coach:latest']))]);

        $response = $this->getJson(route('exam.status'))->assertOk();
        $this->assertSame('local_llm', $response->json('engine'));
        $this->assertStringContainsString('ollama pull kimi-k3:cloud', $response->json('fix'));
    }

    public function test_consent_says_answers_go_online_only_with_a_cloud_model(): void
    {
        $this->get(route('exam.setup', 'sidang'))->assertSee('tidak dikirim ke internet');

        config(['exam.ai_model' => 'kimi-k3:cloud']);
        $this->get(route('exam.setup', 'sidang'))
            ->assertDontSee('tidak dikirim ke internet')
            ->assertSee('dikirim ke AI online (kimi-k3:cloud lewat Ollama)');
    }

    public function test_teacher_command_builds_distillation_data_that_never_counts_as_visitors(): void
    {
        Http::fake([
            '*/api/tags' => Http::response(['models' => [['name' => 'kimi-k3:cloud'], ['name' => 'qwen3:4b']]]),
            '*/api/chat' => Http::response(['message' => ['content' => '<think>jawab singkat</think>Saya dua tahun pegang proyek e-commerce pakai React dan Laravel, salah satunya toko online dengan 5000 pengguna.']]),
            '*/voice/status' => Http::response(['llm' => ['available' => true, 'models' => ['kimi-k3:cloud']]]),
            '*/exam/respond' => Http::response(['reply' => self::REPLY, 'model' => 'kimi-k3:cloud']),
            '*' => Http::response([], 503),
        ]);

        $this->artisan('voic:teacher-generate', ['--sessions' => 2, '--mode' => ['wawancara'], '--coach-share' => 0, '--max-turns' => 3, '--seed' => 7])
            ->expectsOutputToContain('php artisan voic:export-training-data')
            ->assertSuccessful();

        $teacher = User::where('email', PracticeSession::TEACHER_EMAIL)->firstOrFail();
        $this->assertSame(2, $teacher->practiceSessions()->count());
        $this->assertSame(0, PracticeSession::takenByPeople()->count());

        // The candidate's <think> block is never stored as their answer.
        $answers = $teacher->practiceSessions()->first()->messages()->where('sender', 'user')->pluck('message');
        $this->assertNotEmpty($answers);
        $this->assertStringNotContainsString('<think>', $answers->implode(' '));

        // The examiner turns went through the AI service with the teacher model.
        Http::assertSent(fn ($request) => str_ends_with($request->url(), '/exam/respond') && $request['model'] === 'kimi-k3:cloud');

        $output = 'storage/framework/testing/teacher_sft.jsonl';
        $this->artisan('voic:export-training-data', ['--output' => $output, '--eval-output' => 'storage/framework/testing/teacher_eval.jsonl', '--eval-ratio' => 0])
            ->assertSuccessful();

        $rows = collect(File::lines(base_path($output)))->filter()->map(fn ($line) => json_decode($line, true));
        $teacherRows = $rows->filter(fn ($row) => $row['meta']['source'] === 'teacher_distill');
        $this->assertNotEmpty($teacherRows);
        $this->assertSame(self::REPLY, $teacherRows->first()['messages'][count($teacherRows->first()['messages']) - 1]['content']);

        File::delete([base_path($output), base_path('storage/framework/testing/teacher_eval.jsonl')]);
    }

    public function test_teacher_command_stops_when_the_ai_service_ignores_the_teacher(): void
    {
        Http::fake([
            '*/api/tags' => Http::response(['models' => [['name' => 'kimi-k3:cloud'], ['name' => 'qwen3:4b']]]),
            '*/api/chat' => Http::response(['message' => ['content' => 'Halo.']]),
            '*/voice/status' => Http::response(['llm' => ['available' => true]]),
            // An AI service started before the update answers with its local model.
            '*/exam/respond' => Http::response(['reply' => 'Oke.', 'model' => 'voic-coach']),
        ]);

        $this->artisan('voic:teacher-generate', ['--sessions' => 1])
            ->expectsOutputToContain('kode lama')
            ->assertFailed();

        $this->assertSame(0, PracticeSession::count());
    }

    public function test_teacher_command_explains_a_missing_subscription(): void
    {
        Http::fake([
            '*/api/tags' => Http::response(['models' => [['name' => 'kimi-k3:cloud'], ['name' => 'qwen3:4b']]]),
            '*/api/chat' => Http::response(['error' => 'this model requires a subscription'], 402),
            '*/voice/status' => Http::response(['llm' => ['available' => true]]),
        ]);

        $this->artisan('voic:teacher-generate', ['--sessions' => 1])
            ->expectsOutputToContain('langganan')
            ->assertFailed();
    }

    public function test_teacher_command_explains_how_to_get_the_teacher_model(): void
    {
        Http::fake(['*/api/tags' => Http::response(['models' => [['name' => 'qwen3:4b']]])]);

        $this->artisan('voic:teacher-generate', ['--sessions' => 1])
            ->expectsOutputToContain('ollama signin')
            ->assertFailed();

        $this->assertSame(0, PracticeSession::count());
    }

    private const OPENROUTER_TEACHER = 'openrouter/moonshotai/kimi-k2.6:free';

    /**
     * @return array<string, mixed>
     */
    private function openRouterReply(string $content): array
    {
        return ['choices' => [['message' => ['content' => $content]]]];
    }

    public function test_free_kimi_on_openrouter_can_be_the_teacher(): void
    {
        config(['services.openrouter.key' => 'sk-or-test', 'services.openrouter.retries' => 0]);
        Http::fake([
            'openrouter.ai/*' => Http::response($this->openRouterReply(self::REPLY)),
            '*' => Http::response([], 503),
        ]);

        $this->artisan('voic:teacher-generate', [
            '--sessions' => 1, '--mode' => ['wawancara'], '--coach-share' => 0, '--max-turns' => 2,
            '--teacher' => self::OPENROUTER_TEACHER, '--candidate' => self::OPENROUTER_TEACHER,
        ])->expectsOutputToContain('Model guru siap')->assertSuccessful();

        $session = User::where('email', PracticeSession::TEACHER_EMAIL)->firstOrFail()->practiceSessions()->firstOrFail();
        $engines = $session->messages()->where('sender', 'ai')->get()->map(fn ($message) => $message->meta['engine'] ?? null)->filter();
        $this->assertContains('openrouter', $engines->all());

        // OpenRouter gets its own model id, with room for Kimi to think and without the reasoning text.
        Http::assertSent(fn ($request) => str_contains($request->url(), 'openrouter.ai/api/v1/chat/completions')
            && $request['model'] === 'moonshotai/kimi-k2.6:free'
            && $request['reasoning'] === ['exclude' => true]
            && $request->hasHeader('Authorization', 'Bearer sk-or-test'));
        // No Ollama or AI service is needed for an OpenRouter teacher and candidate.
        Http::assertNotSent(fn ($request) => str_contains($request->url(), '11434'));

        $output = 'storage/framework/testing/openrouter_sft.jsonl';
        $this->artisan('voic:export-training-data', ['--output' => $output, '--eval-output' => 'storage/framework/testing/openrouter_eval.jsonl', '--eval-ratio' => 0])->assertSuccessful();
        $sources = collect(File::lines(base_path($output)))->filter()->map(fn ($line) => json_decode($line, true)['meta']['source']);
        $this->assertContains('teacher_distill', $sources->all());
        File::delete([base_path($output), base_path('storage/framework/testing/openrouter_eval.jsonl')]);
    }

    public function test_openrouter_teacher_needs_an_api_key(): void
    {
        config(['services.openrouter.key' => null]);

        $this->artisan('voic:teacher-generate', ['--sessions' => 1, '--teacher' => self::OPENROUTER_TEACHER, '--candidate' => self::OPENROUTER_TEACHER])
            ->expectsOutputToContain('OPENROUTER_API_KEY')
            ->assertFailed();
    }

    public function test_command_stops_politely_when_the_free_daily_quota_runs_out(): void
    {
        config(['services.openrouter.key' => 'sk-or-test', 'services.openrouter.retries' => 0]);
        Http::fake([
            'openrouter.ai/*' => Http::sequence()
                ->push($this->openRouterReply('Halo'))                      // preflight
                ->push($this->openRouterReply('Saya lulusan akuntansi.'))   // candidate
                ->push(['error' => ['message' => 'Rate limit exceeded: free-models-per-day']], 429),
            '*' => Http::response([], 503),
        ]);

        $this->artisan('voic:teacher-generate', [
            '--sessions' => 3, '--mode' => ['wawancara'], '--coach-share' => 0, '--max-turns' => 4,
            '--teacher' => self::OPENROUTER_TEACHER, '--candidate' => self::OPENROUTER_TEACHER,
        ])->expectsOutputToContain('Kuota gratis OpenRouter habis');

        // It stopped after the first session instead of filling three with fallback replies.
        $this->assertSame(1, PracticeSession::count());
    }

    public function test_openrouter_model_can_also_be_the_live_examiner(): void
    {
        config(['exam.ai_model' => self::OPENROUTER_TEACHER, 'services.openrouter.key' => 'sk-or-test']);
        Http::fake([
            'openrouter.ai/*' => Http::response($this->openRouterReply(self::REPLY)),
            '*/voice/status' => Http::response($this->voiceStatus(['voic-coach:latest'])),
            '*' => Http::response([], 503),
        ]);

        $this->getJson(route('exam.status'))->assertOk()->assertJson(['engine' => 'openrouter', 'model' => 'moonshotai/kimi-k2.6:free']);
        $this->get(route('exam.setup', 'wawancara'))->assertSee('moonshotai/kimi-k2.6:free lewat OpenRouter');

        $session = $this->startExam('wawancara');
        $this->postJson(route('exam.answer', $session), [
            'answer' => 'Saya Dimas, dua tahun jadi web developer di agensi, banyak pegang proyek e-commerce pakai React.',
            'duration_seconds' => 10,
            'timestamp_seconds' => 10,
        ])->assertOk()->assertJsonPath('examiner_message.engine', 'openrouter');

        // The AI service is never asked to run an OpenRouter model name.
        Http::assertNotSent(fn ($request) => str_ends_with($request->url(), '/exam/respond') && ($request['model'] ?? null) === self::OPENROUTER_TEACHER);
    }
}
