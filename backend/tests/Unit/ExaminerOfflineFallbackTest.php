<?php

namespace Tests\Unit;

use App\Services\Exam\ExaminerService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ExaminerOfflineFallbackTest extends TestCase
{
    private function callCloud(ExaminerService $examiner): ?string
    {
        $method = new \ReflectionMethod($examiner, 'callCloudModel');

        return $method->invoke($examiner, [['role' => 'user', 'content' => 'Halo']]);
    }

    public function test_a_dropped_connection_pauses_the_cloud_so_later_turns_go_straight_to_the_local_ai(): void
    {
        Cache::flush();
        config(['services.groq.key' => 'test-key', 'services.groq.fallback_model' => 'second-model']);
        Http::fake(['*' => Http::failedConnection()]);
        $examiner = app(ExaminerService::class);

        $this->assertNull($this->callCloud($examiner));
        // One attempt only: the second Groq model is on the same (dead) network.
        Http::assertSentCount(1);
        $this->assertTrue(ExaminerService::cloudPaused());

        // The next turn does not wait for another timeout.
        $this->assertNull($this->callCloud($examiner));
        Http::assertSentCount(1);
    }

    public function test_cloud_is_used_again_once_the_pause_is_over(): void
    {
        Cache::flush();
        config(['services.groq.key' => 'test-key']);
        Http::fake(['*' => Http::response(['choices' => [['message' => ['content' => 'Oke, lanjut.']]]])]);
        $examiner = app(ExaminerService::class);

        $this->assertSame('Oke, lanjut.', $this->callCloud($examiner));
        $this->assertFalse(ExaminerService::cloudPaused());
    }

    public function test_a_reply_cut_off_by_the_token_limit_keeps_only_its_complete_sentences(): void
    {
        Cache::flush();
        config(['services.groq.key' => 'test-key']);
        // A reasoning model spent its tokens thinking: the reply stops mid-word.
        Http::fake(['*' => Http::response(['choices' => [['finish_reason' => 'length', 'message' => ['content' => 'Oke, lumayan buat dua bulan. Tapi itu kan jawaban buat to']]]])]);

        $this->assertSame('Oke, lumayan buat dua bulan.', $this->callCloud(app(ExaminerService::class)));
    }

    public function test_a_cut_off_reply_without_one_complete_sentence_tries_the_next_model(): void
    {
        Cache::flush();
        config(['services.groq.key' => 'test-key', 'services.groq.fallback_model' => 'second-model']);
        Http::fakeSequence('api.groq.com/*')
            ->push(['choices' => [['finish_reason' => 'length', 'message' => ['content' => 'Tapi tunggu, itu kan jawaban buat to']]]])
            ->push(['choices' => [['finish_reason' => 'stop', 'message' => ['content' => 'Oke. Gimana kamu ngukurnya?']]]]);

        $this->assertSame('Oke. Gimana kamu ngukurnya?', $this->callCloud(app(ExaminerService::class)));
        Http::assertSentCount(2);
    }

    public function test_cerebras_answers_with_the_same_model_when_groq_quota_is_used_up(): void
    {
        Cache::flush();
        config(['services.groq.key' => 'test-key', 'services.groq.fallback_model' => '', 'services.cerebras.key' => 'cb-key', 'services.openai.key' => '']);
        Http::fake([
            'api.groq.com/*' => Http::response(['error' => ['message' => 'Rate limit reached']], 429),
            'api.cerebras.ai/*' => Http::response(['choices' => [['finish_reason' => 'stop', 'message' => ['content' => 'Oke. Gimana kamu ngukurnya?']]]]),
        ]);

        $this->assertSame('Oke. Gimana kamu ngukurnya?', $this->callCloud(app(ExaminerService::class)));
        Http::assertSent(fn ($request) => str_contains($request->url(), 'api.cerebras.ai')
            && $request['model'] === 'gpt-oss-120b'
            && $request['reasoning_effort'] === 'low'
            && $request->hasHeader('Authorization', 'Bearer cb-key'));
    }

    public function test_a_cerebras_key_alone_turns_on_the_cloud_examiner(): void
    {
        config(['services.groq.key' => '', 'services.openai.key' => '', 'services.cerebras.key' => 'cb-key']);

        $this->assertTrue(app(ExaminerService::class)->cloudConfigured());
    }

    public function test_a_short_echo_question_does_not_swallow_the_real_question(): void
    {
        $examiner = app(ExaminerService::class);
        $method = new \ReflectionMethod($examiner, 'upToFirstQuestion');

        // "300 milidetik?" is a reaction, the question is the next one.
        $this->assertSame(
            '300 milidetik? Lumayan. Terus, gimana cara kamu ngukurnya?',
            $method->invoke($examiner, '300 milidetik? Lumayan. Terus, gimana cara kamu ngukurnya? Aku penasaran.'),
        );
        // A real question first: the turn still ends there.
        $this->assertSame(
            'Kenapa kamu pilih Laravel buat API itu?',
            $method->invoke($examiner, 'Kenapa kamu pilih Laravel buat API itu? Terus databasenya apa?'),
        );
        // Only short questions: the first one is kept.
        $this->assertSame('Oh ya?', $method->invoke($examiner, 'Oh ya? Serius?'));
        // A short question with a question word is a real question.
        $this->assertSame('Kenapa?', $method->invoke($examiner, 'Kenapa? Ceritain dong alasannya.'));
        $this->assertSame('Serius? Terus gimana?', $method->invoke($examiner, 'Serius? Terus gimana? Aku penasaran.'));
    }

    public function test_connectivity_reports_offline_when_the_internet_is_down(): void
    {
        Cache::flush();
        Http::fake(['*' => Http::failedConnection()]);

        $status = app(ExaminerService::class)->connectivity();

        $this->assertFalse($status['internet']);
        $this->assertFalse($status['cloud_ai']);
        $this->assertFalse($status['online_voice']);
        $this->assertFalse($status['ai_service']);
    }
}
