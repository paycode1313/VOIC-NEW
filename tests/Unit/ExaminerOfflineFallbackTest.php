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
