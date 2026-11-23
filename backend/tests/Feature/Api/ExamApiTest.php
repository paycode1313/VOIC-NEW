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
            ->assertJsonPath('modes.1.examiner.name', 'VOIC-SIDANG');
    }

    public function test_mobile_client_runs_a_full_exam_and_gets_a_real_report(): void
    {
        Http::fake(['*' => Http::response([], 503)]);
        Sanctum::actingAs($user = User::factory()->create());

        $start = $this->postJson(route('api.v1.exam.start', 'sidang'), ['topic' => 'Deteksi cacat kain', 'parts' => [1], 'consent' => true])
            ->assertCreated()
            ->assertJsonPath('part', 1);
        $sessionId = $start->json('session_id');
        $this->assertStringContainsString('VOIC Sidang', $start->json('examiner_message.text'));

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

    public function test_mobile_client_gets_the_report_extras_and_can_delete_its_data(): void
    {
        Storage::fake('local');
        Http::fake([
            '*/stt' => Http::response(['text' => 'Saya memakai YOLOv8 untuk deteksi cacat kain di pabrik tekstil karena cepat.']),
            '*' => Http::response([], 503),
        ]);
        Sanctum::actingAs(User::factory()->create());

        $this->getJson(route('api.v1.exam.status'))->assertOk()->assertJsonPath('success', true);

        $sessionId = $this->postJson(route('api.v1.exam.start', 'sidang'), ['parts' => [1], 'consent' => true])->json('session_id');
        $this->getJson(route('api.v1.exam.backchannels', $sessionId))->assertOk()->assertJsonStructure(['clips']);

        $this->post(route('api.v1.exam.answer', $sessionId), [
            'audio' => UploadedFile::fake()->createWithContent('a.m4a', str_repeat('A', 3000)),
            'duration_seconds' => 8,
        ], ['Accept' => 'application/json'])->assertOk();

        $session = PracticeSession::find($sessionId);
        $answer = $session->messages()->where('sender', 'user')->first();
        $reply = $session->messages()->where('sender', 'ai')->latest('id')->first();

        $recording = $this->get(route('api.v1.exam.recording', [$sessionId, $answer->id]));
        $recording->assertOk();
        $this->assertSame('audio/mp4', $recording->headers->get('Content-Type'));
        $this->getJson(route('api.v1.exam.recording', [$sessionId, $reply->id]))->assertNotFound();

        $this->postJson(route('api.v1.exam.rate', [$sessionId, $reply->id]), ['rating' => 'good'])->assertOk()->assertJsonPath('rating', 'good');
        $this->assertSame('good', $reply->fresh()->meta['rating']);

        // Extras appear once there is a report.
        $this->getJson(route('api.v1.exam.improvements', $sessionId))->assertNotFound();
        $this->postJson(route('api.v1.exam.finish', $sessionId), ['duration_seconds' => 30])->assertOk();
        $this->getJson(route('api.v1.exam.improvements', $sessionId))
            ->assertOk()
            ->assertJsonPath('session_type', 'exam')
            ->assertJsonStructure(['improvements']);

        $this->deleteJson(route('api.v1.exam.destroy', $sessionId))->assertOk();
        $this->assertNull(PracticeSession::find($sessionId));
        Storage::disk('local')->assertMissing($answer->meta['recording']);
    }

    public function test_report_extras_belong_to_the_owner_only(): void
    {
        Http::fake(['*' => Http::response([], 503)]);
        Sanctum::actingAs(User::factory()->create());
        $sessionId = $this->postJson(route('api.v1.exam.start', 'sidang'), ['parts' => [1], 'consent' => true])->json('session_id');
        $reply = PracticeSession::find($sessionId)->messages()->where('sender', 'ai')->first();

        Sanctum::actingAs(User::factory()->create());
        $this->getJson(route('api.v1.exam.backchannels', $sessionId))->assertForbidden();
        $this->getJson(route('api.v1.exam.improvements', $sessionId))->assertForbidden();
        $this->postJson(route('api.v1.exam.rate', [$sessionId, $reply->id]), ['rating' => 'bad'])->assertForbidden();
        $this->deleteJson(route('api.v1.exam.destroy', $sessionId))->assertForbidden();
        $this->assertNotNull(PracticeSession::find($sessionId));
    }
}
