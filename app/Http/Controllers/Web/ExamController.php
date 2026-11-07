<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\ExamAnswerRequest;
use App\Http\Requests\ExamStreamAnswerRequest;
use App\Http\Requests\StartExamRequest;
use App\Models\AiRole;
use App\Models\PracticeSession;
use App\Models\SessionMessage;
use App\Services\Exam\ExamBlueprint;
use App\Services\Exam\ExamFlowService;
use App\Services\Exam\ExaminerService;
use App\Services\Exam\ExamReportService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExamController extends Controller
{
    public function __construct(protected ExamBlueprint $blueprint) {}

    /**
     * Show the practice mode picker.
     */
    public function index(Request $request): View
    {
        $language = $this->language($request);

        $recent = $request->user()
            ? $request->user()->practiceSessions()->whereNotNull('exam_mode')->latest()->take(4)->get()
            : collect();

        return view('exam.index', [
            'modes' => $this->blueprint->modes(),
            'blueprint' => $this->blueprint,
            'language' => $language,
            'recentSessions' => $recent,
        ]);
    }

    /**
     * Show the setup screen for one mode.
     */
    public function setup(Request $request, string $mode): View
    {
        abort_unless($this->blueprint->hasMode($mode), 404);

        return view('exam.setup', [
            'modeKey' => $mode,
            'mode' => $this->blueprint->mode($mode),
            'blueprint' => $this->blueprint,
            'language' => $this->language($request),
        ]);
    }

    /**
     * Create the session and open the exam room.
     */
    public function start(StartExamRequest $request, string $mode, ExamFlowService $flow): RedirectResponse
    {
        abort_unless($this->blueprint->hasMode($mode), 404);

        $definition = $this->blueprint->mode($mode);
        $language = $request->validated('language') ?? 'id';
        $topic = $request->filled('topic') ? trim((string) $request->validated('topic')) : null;
        $user = $request->user();

        $notes = [
            'language' => $language,
            'context_material' => $request->validated('context_material'),
            'topic' => $topic,
        ];

        if (! $user) {
            $guestToken = Str::random(40);
            $notes += [
                'is_guest' => true,
                'guest_name' => $request->filled('guest_name') ? trim((string) $request->validated('guest_name')) : 'Pengunjung',
                'guest_token' => $guestToken,
            ];
        }

        /** @var PracticeSession $session */
        $session = PracticeSession::create([
            'user_id' => $user?->id,
            'ai_role_id' => AiRole::where('role_type', $definition['role_type'])->value('id'),
            'scenario_type' => $this->blueprint->text($definition['title'], $language).($topic ? ' · '.Str::limit($topic, 60) : ''),
            'exam_mode' => $mode,
            'duration_seconds' => 0,
            'feedback_notes' => $notes,
        ]);

        if (! $user) {
            $request->session()->put('active_guest_session_id', $session->id);
            $request->session()->put('guest_session_token', $notes['guest_token']);
            $request->session()->put('pending_claim_session_id', $session->id);
        }

        $flow->start($session, $mode, $request->parts(), $language, $topic);

        return redirect()->route('exam.room', $session);
    }

    /**
     * Show the live exam room.
     */
    public function room(PracticeSession $practiceSession, ExamFlowService $flow): View|RedirectResponse
    {
        abort_unless($practiceSession->isExam(), 404);
        Gate::authorize('update', $practiceSession);

        if (isset($practiceSession->feedback_notes['exam_report'])) {
            return redirect()->route('exam.result', $practiceSession);
        }

        $definition = $this->blueprint->mode($practiceSession->exam_mode);
        $language = $practiceSession->exam_state['language'] ?? 'id';
        $lastMessage = $practiceSession->messages()->where('sender', 'ai')->latest('id')->first();

        return view('exam.room', [
            'session' => $practiceSession,
            'modeKey' => $practiceSession->exam_mode,
            'mode' => $definition,
            'blueprint' => $this->blueprint,
            'language' => $language,
            'initialState' => $flow->payload($practiceSession, $lastMessage),
            'transcript' => $practiceSession->messages()->orderBy('id')->get(['id', 'sender', 'message', 'exam_part']),
        ]);
    }

    /**
     * Receive one answer and return the examiner's next turn.
     */
    public function answer(ExamAnswerRequest $request, PracticeSession $practiceSession, ExamFlowService $flow): JsonResponse
    {
        abort_unless($practiceSession->isExam(), 404);

        if (($practiceSession->exam_state['phase'] ?? null) === ExamFlowService::PHASE_COMPLETED) {
            return response()->json(['message' => 'Sesi sudah selesai.'], 409);
        }

        $payload = $flow->answer(
            $practiceSession,
            trim((string) $request->validated('answer')),
            (int) $request->validated('duration_seconds'),
            $request->validated('facial'),
            (int) $request->validated('timestamp_seconds'),
        );

        return response()->json($payload);
    }

    /**
     * Same as answer(), but streams NDJSON so the browser can speak each examiner sentence
     * as soon as it exists. Accepts a recorded answer (transcribed by local Whisper) or text.
     *
     * Events: transcript, sentence (text + audio_url), done (exam state), retry, error.
     */
    public function answerStream(ExamStreamAnswerRequest $request, PracticeSession $practiceSession, ExamFlowService $flow, ExaminerService $examiner): StreamedResponse|JsonResponse
    {
        abort_unless($practiceSession->isExam(), 404);

        if (($practiceSession->exam_state['phase'] ?? null) === ExamFlowService::PHASE_COMPLETED) {
            return response()->json(['message' => 'Sesi sudah selesai.'], 409);
        }

        $audioPath = $request->file('audio')?->getRealPath();
        $typed = trim((string) $request->validated('answer', ''));
        $state = $practiceSession->exam_state;

        return response()->stream(function () use ($request, $practiceSession, $flow, $examiner, $audioPath, $typed, $state) {
            $send = function (array $event): void {
                echo json_encode($event, JSON_UNESCAPED_UNICODE)."\n";
                if (ob_get_level() > 0) {
                    ob_flush();
                }
                flush();
            };

            $answer = $typed;
            if ($audioPath !== null && $answer === '') {
                $transcript = $examiner->transcribe($audioPath, $state['language'] ?? 'id', $state['topic'] ?? null);
                if ($transcript === null) {
                    $send(['type' => 'error', 'code' => 'stt_unavailable', 'message' => 'Pengenal suara lokal tidak aktif.']);

                    return;
                }
                $answer = $transcript['text'];
                $send(['type' => 'transcript', 'text' => $answer, 'engine' => $transcript['engine']]);

                if ($answer === '') {
                    $send(['type' => 'retry', 'message' => 'Suaramu belum terdengar jelas, coba ulangi ya.']);

                    return;
                }
            }

            $payload = $flow->answer(
                $practiceSession,
                $answer,
                (int) $request->validated('duration_seconds'),
                $request->validated('facial'),
                (int) $request->validated('timestamp_seconds'),
                fn (string $text, ?string $audioUrl) => $send(['type' => 'sentence', 'text' => $text, 'audio_url' => $audioUrl]),
            );

            $send(['type' => 'done', 'state' => $payload]);
        }, 200, [
            'Content-Type' => 'application/x-ndjson; charset=UTF-8',
            'Cache-Control' => 'no-cache, no-store',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    /**
     * Proxy a generated voice file from the local AI service, so remote devices can play it.
     */
    public function audio(string $file): StreamedResponse
    {
        abort_unless(preg_match('/^tts_[A-Za-z0-9_]+\.(wav|mp3)$/', $file) === 1, 404);

        $response = Http::timeout(10)->withOptions(['stream' => true])
            ->get(rtrim((string) Config::get('services.ai_service.url', 'http://127.0.0.1:8001'), '/').'/audio/'.$file);
        abort_unless($response->successful(), 404);

        $body = $response->toPsrResponse()->getBody();

        return response()->stream(function () use ($body) {
            while (! $body->eof()) {
                echo $body->read(8192);
            }
        }, 200, [
            'Content-Type' => str_ends_with($file, '.wav') ? 'audio/wav' : 'audio/mpeg',
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }

    /**
     * Finish the exam and build the report.
     */
    public function finish(Request $request, PracticeSession $practiceSession, ExamReportService $reports): JsonResponse
    {
        abort_unless($practiceSession->isExam(), 404);
        Gate::authorize('update', $practiceSession);

        $validated = $request->validate([
            'duration_seconds' => ['nullable', 'integer', 'min:0', 'max:7200'],
        ]);

        if (! isset($practiceSession->feedback_notes['exam_report'])) {
            $reports->finalize($practiceSession, (int) ($validated['duration_seconds'] ?? 0));
        }

        return response()->json([
            'redirect_url' => route('exam.result', $practiceSession),
        ]);
    }

    /**
     * Show the feedback report.
     */
    public function result(PracticeSession $practiceSession): View|RedirectResponse
    {
        abort_unless($practiceSession->isExam(), 404);
        Gate::authorize('view', $practiceSession);

        $report = $practiceSession->feedback_notes['exam_report'] ?? null;
        if (! $report) {
            return redirect()->route('exam.room', $practiceSession);
        }

        return view('exam.result', [
            'session' => $practiceSession,
            'modeKey' => $practiceSession->exam_mode,
            'mode' => $this->blueprint->mode($practiceSession->exam_mode),
            'blueprint' => $this->blueprint,
            'language' => $report['language'] ?? 'id',
            'report' => $report,
            'transcript' => $practiceSession->messages()->orderBy('id')->get(['id', 'sender', 'message', 'exam_part', 'meta']),
            'canRate' => Gate::allows('update', $practiceSession),
        ]);
    }

    /**
     * Report which AI engine is answering, so the room can show it honestly.
     */
    public function status(ExaminerService $examiner): JsonResponse
    {
        return response()->json($examiner->availability());
    }

    /**
     * Rate whether an examiner reply felt natural and on-topic. Good ratings become training data.
     */
    public function rate(Request $request, PracticeSession $practiceSession, SessionMessage $message): JsonResponse
    {
        abort_unless($practiceSession->isExam() && $message->sender === 'ai', 404);
        Gate::authorize('update', $practiceSession);

        $validated = $request->validate([
            'rating' => ['required', 'string', 'in:good,bad'],
        ]);

        $message->update(['meta' => array_merge($message->meta ?? [], ['rating' => $validated['rating']])]);

        return response()->json(['rating' => $validated['rating']]);
    }

    /**
     * Interface language from the query string, defaulting to Indonesian.
     */
    protected function language(Request $request): string
    {
        return $request->query('lang') === 'en' ? 'en' : 'id';
    }
}
