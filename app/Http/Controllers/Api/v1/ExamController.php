<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\AiRole;
use App\Models\PracticeSession;
use App\Services\Exam\ExamBlueprint;
use App\Services\Exam\ExamFlowService;
use App\Services\Exam\ExaminerService;
use App\Services\Exam\ExamReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Three-part mock exam for the mobile apps (Sanctum token auth).
 *
 * The mobile client sends either recognised text, or a recording that is
 * transcribed by the same local Whisper as the web app.
 */
class ExamController extends Controller
{
    public function __construct(protected ExamBlueprint $blueprint) {}

    /**
     * Available modes with their parts, for the mobile home screen.
     */
    public function modes(): JsonResponse
    {
        $modes = collect($this->blueprint->modes())->map(fn (array $mode, string $key) => [
            'key' => $key,
            'role_type' => $mode['role_type'],
            'title' => $this->blueprint->text($mode['title'], 'id'),
            'tagline' => $this->blueprint->text($mode['tagline'], 'id'),
            'examiner' => [
                'name' => $mode['examiner']['name'],
                'title' => $this->blueprint->text($mode['examiner']['title'], 'id'),
                'avatar_url' => asset($mode['examiner']['avatar']),
            ],
            'topic_label' => $this->blueprint->text($mode['topic_label'], 'id'),
            'parts' => collect($mode['parts'])->map(fn ($part, $number) => [
                'number' => $number,
                'title' => $this->blueprint->text($part['title'], 'id'),
            ])->values(),
        ])->values();

        return response()->json(['success' => true, 'modes' => $modes]);
    }

    /**
     * Start an exam and return the examiner's opening line.
     */
    public function start(Request $request, string $mode, ExamFlowService $flow): JsonResponse
    {
        abort_unless($this->blueprint->hasMode($mode), 404);

        $validated = $request->validate([
            'topic' => ['nullable', 'string', 'max:150'],
            'context_material' => ['nullable', 'string', 'max:3000'],
            'language' => ['nullable', 'string', 'in:id,en'],
            'parts' => ['nullable', 'array', 'min:1'],
            'parts.*' => ['integer', 'in:1,2,3'],
            'consent' => ['accepted'],
        ]);

        $definition = $this->blueprint->mode($mode);
        $language = $validated['language'] ?? 'id';
        $topic = isset($validated['topic']) ? trim($validated['topic']) : null;

        $session = PracticeSession::create([
            'user_id' => $request->user()->id,
            'ai_role_id' => AiRole::where('role_type', $definition['role_type'])->value('id'),
            'scenario_type' => $this->blueprint->text($definition['title'], $language).($topic ? ' · '.Str::limit($topic, 60) : ''),
            'exam_mode' => $mode,
            'duration_seconds' => 0,
            'feedback_notes' => ['language' => $language, 'context_material' => $validated['context_material'] ?? null, 'topic' => $topic, 'client' => 'mobile'],
        ]);

        $payload = $flow->start($session, $mode, array_map('intval', $validated['parts'] ?? [1, 2, 3]), $language, $topic);

        return response()->json(['success' => true] + $payload, 201);
    }

    /**
     * Send one answer (text, or a recording to transcribe locally) and get the examiner's turn.
     */
    public function answer(Request $request, PracticeSession $practiceSession, ExamFlowService $flow, ExaminerService $examiner): JsonResponse
    {
        $this->authorizeOwner($request, $practiceSession);

        $validated = $request->validate([
            'answer' => ['nullable', 'string', 'max:5000', 'required_without:audio'],
            'audio' => ['nullable', 'file', 'max:20480'],
            'duration_seconds' => ['required', 'integer', 'min:0', 'max:900'],
            'timestamp_seconds' => ['nullable', 'integer', 'min:0'],
        ]);

        if (($practiceSession->exam_state['phase'] ?? null) === ExamFlowService::PHASE_COMPLETED) {
            return response()->json(['success' => false, 'message' => 'Sesi sudah selesai.'], 409);
        }

        $answer = trim((string) ($validated['answer'] ?? ''));
        $transcript = null;
        if ($answer === '' && $request->hasFile('audio')) {
            $state = $practiceSession->exam_state;
            $result = $examiner->transcribe($request->file('audio')->getRealPath(), $state['language'] ?? 'id', $state['topic'] ?? null);
            if ($result === null) {
                return response()->json(['success' => false, 'code' => 'stt_unavailable', 'message' => 'Pengenal suara lokal tidak aktif. Kirim teks hasil pengenalan suara perangkat.'], 503);
            }
            if ($result['text'] === '') {
                return response()->json(['success' => false, 'code' => 'retry', 'message' => 'Suaramu belum terdengar jelas, coba ulangi.'], 422);
            }
            $answer = $transcript = $result['text'];
        }

        $payload = $flow->answer(
            $practiceSession,
            $answer,
            (int) $validated['duration_seconds'],
            null,
            (int) ($validated['timestamp_seconds'] ?? 0),
        );

        if ($transcript !== null && $request->hasFile('audio')) {
            $message = $practiceSession->messages()->where('sender', 'user')->latest('id')->first();
            $path = $request->file('audio')->storeAs($practiceSession->recordingsDirectory(), $message->id.'.webm', 'local');
            $message->update(['meta' => array_merge($message->meta ?? [], ['recording' => $path])]);
        }

        return response()->json(['success' => true, 'transcript' => $transcript] + $payload);
    }

    /**
     * Finish the exam and return the band-score report.
     */
    public function finish(Request $request, PracticeSession $practiceSession, ExamReportService $reports): JsonResponse
    {
        $this->authorizeOwner($request, $practiceSession);

        $validated = $request->validate(['duration_seconds' => ['nullable', 'integer', 'min:0', 'max:7200']]);

        if (! isset($practiceSession->feedback_notes['exam_report'])) {
            $reports->finalize($practiceSession, (int) ($validated['duration_seconds'] ?? 0));
        }

        return response()->json(['success' => true, 'evaluation' => $this->evaluation($practiceSession->refresh())]);
    }

    /**
     * Current state (unfinished) or report (finished) of a session.
     */
    public function show(Request $request, PracticeSession $practiceSession, ExamFlowService $flow): JsonResponse
    {
        $this->authorizeOwner($request, $practiceSession);

        if (isset($practiceSession->feedback_notes['exam_report'])) {
            return response()->json(['success' => true, 'evaluation' => $this->evaluation($practiceSession)]);
        }

        return response()->json(['success' => true] + $flow->payload($practiceSession, $practiceSession->messages()->where('sender', 'ai')->latest('id')->first()));
    }

    /**
     * Mobile report shape: legacy summary fields plus the full band report.
     *
     * @return array<string, mixed>
     */
    protected function evaluation(PracticeSession $session): array
    {
        return [
            'id' => $session->id,
            'scenario_type' => $session->scenario_type,
            'overall_score' => $session->overall_score,
            'face_score' => $session->face_score,
            'voice_score' => $session->voice_score,
            'duration_seconds' => $session->duration_seconds,
            'ai_conclusion' => $session->ai_conclusion,
            'report' => $session->feedback_notes['exam_report'] ?? null,
            'report_url' => route('exam.result', $session),
        ];
    }

    /**
     * Only the owner (token user) may use an exam session through the API.
     */
    protected function authorizeOwner(Request $request, PracticeSession $practiceSession): void
    {
        abort_unless($practiceSession->isExam(), 404);
        abort_unless($practiceSession->user_id === $request->user()->id, 403);
    }
}
