<?php

namespace Tests\Feature\Api;

use App\Models\PracticeSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ExamApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_modes_are_public(): void
    {
        Http::fake(['*' => Http::response([], 503)]);
        $this->getJson(route('api.v1.exam.modes'))
            ->assertOk()
            ->assertJsonCount(3, 'modes')
            ->assertJsonPath('modes.0.key', 'wawancara')
            ->assertJsonPath('modes.1.examiner.name', 'Prof. Hendra');
    }

    public function test_mobile_client_runs_a_full_exam_and_gets_a_real_report(): void
    {
        Http::fake(['*' => Http::response([], 503)]);
        Sanctum::actingAs($user = User::factory()->create());

        $start = $this->postJson(route('api.v1.exam.start', 'sidang'), ['topic' => 'Deteksi cacat kain', 'parts' => [1], 'consent' => true])
            ->assertCreated()
            ->assertJsonPath('part', 1);
        $sessionId = $start->json('session_id');
        $this->assertStringContainsString('Prof. Hendra', $start->json('examiner_message.text'));

        for ($i = 0; $i < 8; $i++) {
            $turn = $this->postJson(route('api.v1.exam.answer', $sessionId), [
                'answer' => 'Penelitian saya memakai YOLOv8 dengan 2000 gambar dari pabrik tekstil karena butuh deteksi cepat dan akurat.',
                'duration_seconds' => 15,
            ])->assertOk();
            if ($turn->json('completed')) {
                break;
            }
        }
        $this->assertTrue($turn->json('completed'));

        $finish = $this->postJson(route('api.v1.exam.finish', $sessionId))->assertOk();
        $this->assertGreaterThan(0, $finish->json('evaluation.report.overall_band'));
        $this->assertCount(4, $finish->json('evaluation.report.criteria'));
        $this->assertSame($user->id, PracticeSession::find($sessionId)->user_id);

        $this->getJson(route('api.v1.exam.show', $sessionId))->assertOk()->assertJsonPath('evaluation.id', $sessionId);
    }

    public function test_voice_answers_are_transcribed_by_local_whisper(): void
    {
        Storage::fake('local');
        Http::fake([
            '*/stt' => Http::response(['text' => 'Saya memakai YOLOv8 untuk deteksi cacat kain di pabrik tekstil.']),
            '*' => Http::response([], 503),
        ]);
        Sanctum::actingAs(User::factory()->create());
        $sessionId = $this->postJson(route('api.v1.exam.start', 'sidang'), ['parts' => [1], 'consent' => true])->json('session_id');

        $this->post(route('api.v1.exam.answer', $sessionId), [
            'audio' => UploadedFile::fake()->createWithContent('a.m4a', str_repeat('A', 3000)),
            'duration_seconds' => 8,
        ], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('transcript', 'Saya memakai YOLOv8 untuk deteksi cacat kain di pabrik tekstil.');

        $answer = PracticeSession::find($sessionId)->messages()->where('sender', 'user')->first();
        Storage::disk('local')->assertExists($answer->meta['recording']);
    }

    public function test_requires_token_and_ownership(): void
    {
        Http::fake(['*' => Http::response([], 503)]);
        $this->postJson(route('api.v1.exam.start', 'sidang'), ['consent' => true])->assertUnauthorized();

        Sanctum::actingAs(User::factory()->create());
        $sessionId = $this->postJson(route('api.v1.exam.start', 'sidang'), ['parts' => [1], 'consent' => true])->json('session_id');

        Sanctum::actingAs(User::factory()->create());
        $this->postJson(route('api.v1.exam.answer', $sessionId), ['answer' => 'x', 'duration_seconds' => 1])->assertForbidden();
        $this->getJson(route('api.v1.exam.show', $sessionId))->assertForbidden();
    }

    public function test_consent_is_required(): void
    {
        Http::fake(['*' => Http::response([], 503)]);
        Sanctum::actingAs(User::factory()->create());

        $this->postJson(route('api.v1.exam.start', 'sidang'), ['parts' => [1]])->assertUnprocessable()->assertJsonValidationErrors('consent');
    }
}
