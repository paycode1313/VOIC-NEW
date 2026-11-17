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
     * Available modes with their examiner persona, matching topics, and parts,
     * for the mobile home screen.
     */
    public function modes(Request $request): JsonResponse
    {
        $language = $request->query('lang') === 'en' ? 'en' : 'id';

        $modes = collect($this->blueprint->modes())->map(function (array $mode, string $key) use ($language): array {
            $topicsId = array_map(fn (array $topic): string => $this->blueprint->text($topic, 'id'), $mode['topics'] ?? []);
            $topicsEn = array_map(fn (array $topic): string => $this->blueprint->text($topic, 'en'), $mode['topics'] ?? []);

            return [
                'key' => $key,
                'role_type' => $mode['role_type'],
                'accent' => $mode['accent'] ?? 'indigo',
                'title' => $this->blueprint->text($mode['title'], $language),
                'title_i18n' => $mode['title'],
                'tagline' => $this->blueprint->text($mode['tagline'], $language),
                'tagline_i18n' => $mode['tagline'],
                'examiner' => [
                    'name' => $mode['examiner']['name'],
                    'title' => $this->blueprint->text($mode['examiner']['title'], $language),
                    'title_i18n' => $mode['examiner']['title'],
                    'avatar_url' => asset($mode['examiner']['avatar']),
                ],
                'topic_label' => $this->blueprint->text($mode['topic_label'], $language),
                'topic_label_i18n' => $mode['topic_label'],
                'topic_placeholder' => $this->blueprint->text($mode['topic_placeholder'], $language),
                'topic_placeholder_i18n' => $mode['topic_placeholder'],
                'context_label' => $this->blueprint->text($mode['context_label'], $language),
                'context_label_i18n' => $mode['context_label'],
                'topics' => $language === 'en' ? $topicsEn : $topicsId,
                'topics_i18n' => [
                    'id' => $topicsId,
                    'en' => $topicsEn,
                ],
                'parts' => collect($mode['parts'])->map(fn (array $part, int $number): array => [
                    'number' => $number,
                    'title' => $this->blueprint->text($part['title'], $language),
                    'title_i18n' => $part['title'],
                ])->values(),
            ];
        })->values();

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
            'session_type' => ['nullable', 'string', 'in:exam,coach'],
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

        $payload = ($validated['session_type'] ?? 'exam') === ExamFlowService::TYPE_COACH
            ? $flow->startCoach($session, $mode, $language, $topic)
            : $flow->start($session, $mode, array_map('intval', $validated['parts'] ?? [1, 2, 3]), $language, $topic);

        return response()->json(['success' => true] + $this->withAbsoluteAudioUrl($payload), 201);
    }

    /**
     * Send one answer (text, or a recording to transcribe via Whisper) and get the examiner's turn.
     */
    public function answer(Request $request, PracticeSession $practiceSession, ExamFlowService $flow, ExaminerService $examiner): JsonResponse
    {
        $this->authorizeOwner($request, $practiceSession);

        $validated = $request->validate([
            'answer' => ['nullable', 'string', 'max:5000', 'required_without_all:audio,time_up'],
            'time_up' => ['nullable', 'boolean'],
            'audio' => ['nullable', 'file', 'max:20480'],
            'duration_seconds' => ['required', 'integer', 'min:0', 'max:900'],
            'timestamp_seconds' => ['nullable', 'integer', 'min:0'],
            'facial' => ['nullable', 'array'],
            'facial.eye_contact' => ['nullable', 'numeric', 'between:0,100'],
            'facial.face_visible' => ['nullable', 'numeric', 'between:0,100'],
            'facial.smile' => ['nullable', 'numeric', 'between:0,100'],
            'facial.tension' => ['nullable', 'numeric', 'between:0,100'],
            'facial.samples' => ['nullable', 'integer', 'min:0'],
        ]);

        if (($practiceSession->exam_state['phase'] ?? null) === ExamFlowService::PHASE_COMPLETED) {
            return response()->json(['success' => false, 'message' => 'Sesi sudah selesai.'], 409);
        }

        $answer = trim((string) ($validated['answer'] ?? ''));
        $transcript = null;
        if ($answer === '' && $request->hasFile('audio')) {
            $state = $practiceSession->exam_state;
            $audioFile = $request->file('audio');
            $originalName = $audioFile->getClientOriginalName() ?: ('answer.'.($audioFile->getClientOriginalExtension() ?: 'm4a'));
            $result = $examiner->transcribe($audioFile->getRealPath(), $state['language'] ?? 'id', $state['topic'] ?? null, $originalName);
            if ($result === null) {
                return response()->json(['success' => false, 'code' => 'stt_unavailable', 'message' => 'Pengenal suara tidak aktif. Pastikan GROQ_API_KEY terisi atau AI service lokal berjalan.'], 503);
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
            $validated['facial'] ?? null,
            (int) ($validated['timestamp_seconds'] ?? 0),
            timeUp: (bool) ($validated['time_up'] ?? false),
        );

        if ($transcript !== null && $request->hasFile('audio')) {
            $audioFile = $request->file('audio');
            $ext = strtolower((string) ($audioFile->getClientOriginalExtension() ?: 'm4a'));
            $safeExt = in_array($ext, ['webm', 'm4a', 'mp4', 'mp3', 'wav', 'ogg'], true) ? $ext : 'm4a';
            $message = $practiceSession->messages()->where('sender', 'user')->latest('id')->first();
            $path = $audioFile->storeAs($practiceSession->recordingsDirectory(), $message->id.'.'.$safeExt, 'local');
            $message->update(['meta' => array_merge($message->meta ?? [], ['recording' => $path])]);
        }

        return response()->json(['success' => true, 'transcript' => $transcript] + $this->withAbsoluteAudioUrl($payload));
    }

    /**
     * Finish the exam and return the band-score report.
     */
    public function finish(Request $request, PracticeSession $practiceSession, ExamReportService $reports): JsonResponse
    {
        $this->authorizeOwner($request, $practiceSession);

        $validated = $request->validate(['duration_seconds' => ['nullable', 'integer', 'min:0', 'max:7200']]);

        if (! $practiceSession->hasReport()) {
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

        if ($practiceSession->hasReport()) {
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
            'session_type' => $session->isCoaching() ? 'coach' : 'exam',
            'report' => $session->feedback_notes['exam_report'] ?? null,
            'coach_report' => $session->feedback_notes['coach_report'] ?? null,
            'report_url' => route('exam.result', $session),
            'share_url' => $session->shareUrl(),
        ];
    }

    /**
     * Convert relative /latihan/audio/... paths to full URLs so mobile clients can play them directly.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    protected function withAbsoluteAudioUrl(array $payload): array
    {
        $audioUrl = $payload['examiner_message']['audio_url'] ?? null;
        if (is_string($audioUrl) && str_starts_with($audioUrl, '/')) {
            $payload['examiner_message']['audio_url'] = url($audioUrl);
        }

        return $payload;
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
