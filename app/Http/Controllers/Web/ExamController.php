<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\ExamAnswerRequest;
use App\Http\Requests\ExamStreamAnswerRequest;
use App\Http\Requests\StartExamRequest;
use App\Models\AiRole;
use App\Models\PracticeSession;
use App\Models\SessionMessage;
use App\Models\StudySurvey;
use App\Services\Exam\ExamBlueprint;
use App\Services\Exam\ExamFlowService;
use App\Services\Exam\ExaminerService;
use App\Services\Exam\ExamProgressService;
use App\Services\Exam\ExamReportService;
use App\Services\Study\UserStudy;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
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

        $progress = $request->user() ? app(ExamProgressService::class)->forUser($request->user()) : null;

        return view('exam.index', [
            'modes' => $this->blueprint->modes(),
            'blueprint' => $this->blueprint,
            'language' => $language,
            'recentSessions' => $recent,
            'progress' => $progress && $progress['points'] !== [] ? $progress : null,
            'lastMode' => $recent->first()?->exam_mode ?? 'wawancara',
        ]);
    }

    /**
     * Show the setup screen for one mode.
     */
    public function setup(Request $request, string $mode): View
    {
        abort_unless($this->blueprint->hasMode($mode), 404);

        $parts = array_values(array_intersect([1, 2, 3], array_map('intval', (array) $request->query('parts', [1, 2, 3]))));
        $focus = in_array($request->query('focus'), ExamReportService::CRITERIA, true) ? $request->query('focus') : null;

        return view('exam.setup', [
            'modeKey' => $mode,
            'mode' => $this->blueprint->mode($mode),
            'blueprint' => $this->blueprint,
            'language' => $this->language($request),
            'selectedParts' => $parts === [] ? [1, 2, 3] : $parts,
            'focus' => $focus,
            // Coaching is the default; drills and explicit ?type=exam open the scored mock exam.
            'sessionType' => $focus !== null || $request->query('type') === 'exam' ? ExamFlowService::TYPE_EXAM : ExamFlowService::TYPE_COACH,
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
            // Campus (or school/company): the examiner greets the candidate with name and campus.
            'campus' => $request->filled('campus') ? trim((string) $request->validated('campus')) : null,
        ];

        // User trial: the participant code is remembered for the next practice on this device.
        $studyCode = UserStudy::normalizeCode($request->validated('study_code'));
        if ($studyCode !== null) {
            $notes['study_code'] = $studyCode;
            $request->session()->put('study_code', $studyCode);
        }

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
            'scenario_type' => ($request->validated('session_type') === ExamFlowService::TYPE_COACH
                ? $this->blueprint->text($definition['coach']['title'], $language)
                : $this->blueprint->text($definition['title'], $language)).($topic ? ' · '.Str::limit($topic, 60) : ''),
            'exam_mode' => $mode,
            'duration_seconds' => 0,
            'feedback_notes' => $notes,
        ]);

        if (! $user) {
            $request->session()->put('active_guest_session_id', $session->id);
            $request->session()->put('guest_session_token', $notes['guest_token']);
            $request->session()->put('pending_claim_session_id', $session->id);
        }

        if ($request->validated('session_type') === ExamFlowService::TYPE_COACH) {
            $flow->startCoach($session, $mode, $language, $topic);
        } else {
            $flow->start($session, $mode, $request->parts(), $language, $topic, focus: $request->validated('focus'));
        }

        return redirect()->route('exam.room', $session);
    }

    /**
     * Show the live exam room.
     */
    public function room(PracticeSession $practiceSession, ExamFlowService $flow): View|RedirectResponse
    {
        abort_unless($practiceSession->isExam(), 404);
        Gate::authorize('update', $practiceSession);

        if ($practiceSession->hasReport()) {
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
            timeUp: (bool) $request->validated('time_up', false),
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

        $typed = trim((string) $request->validated('answer', ''));
        $state = $practiceSession->exam_state;

        // Keep the recording so the candidate can replay it in the report (removed by voic:prune).
        $pending = $request->file('audio')?->storeAs(
            $practiceSession->recordingsDirectory(),
            'pending-'.Str::random(12).'.webm',
            'local'
        );
        $audioPath = $pending ? Storage::disk('local')->path($pending) : null;

        return response()->stream(function () use ($request, $practiceSession, $flow, $examiner, $audioPath, $pending, $typed, $state) {
            // Latency per turn (ms since the answer arrived), for voic:latency and the pitch deck.
            $startedAt = hrtime(true);
            $elapsed = fn (): int => (int) round((hrtime(true) - $startedAt) / 1e6);
            $latency = ['stt_ms' => null, 'first_sentence_ms' => null, 'first_sound_ms' => null];

            $send = function (array $event) use (&$latency, $elapsed): void {
                if (($event['type'] ?? null) === 'sentence') {
                    $latency['first_sentence_ms'] ??= $elapsed();
                    if (! empty($event['audio_url'])) {
                        $latency['first_sound_ms'] ??= $elapsed();
                    }
                }
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
                    Storage::disk('local')->delete($pending);
                    $send(['type' => 'error', 'code' => 'stt_unavailable', 'message' => 'Pengenal suara lokal tidak aktif.']);

                    return;
                }
                $answer = $transcript['text'];
                $latency['stt_ms'] = $elapsed();
                $send(['type' => 'transcript', 'text' => $answer, 'engine' => $transcript['engine']]);

                if ($answer === '') {
                    Storage::disk('local')->delete($pending);
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
                (bool) $request->validated('time_up', false),
            );

            if ($pending !== null) {
                $this->attachRecording($practiceSession, $pending);
            }

            $this->recordLatency($practiceSession, $latency + ['total_ms' => $elapsed(), 'voice' => $audioPath !== null] + $flow->lastTimings());

            $send(['type' => 'done', 'state' => $payload]);
        }, 200, [
            'Content-Type' => 'application/x-ndjson; charset=UTF-8',
            'Cache-Control' => 'no-cache, no-store',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    /**
     * Rename the pending upload after the answer it belongs to and remember it on the message.
     */
    protected function attachRecording(PracticeSession $practiceSession, string $pending): void
    {
        $message = $practiceSession->messages()->where('sender', 'user')->latest('id')->first();
        if ($message === null) {
            Storage::disk('local')->delete($pending);

            return;
        }

        $path = $practiceSession->recordingsDirectory().'/'.$message->id.'.webm';
        Storage::disk('local')->move($pending, $path);
        $message->update(['meta' => array_merge($message->meta ?? [], ['recording' => $path])]);
    }

    /**
     * Play back a recorded answer to its owner.
     */
    public function recording(PracticeSession $practiceSession, SessionMessage $message): BinaryFileResponse
    {
        abort_unless($practiceSession->isExam() && $message->sender === 'user', 404);
        Gate::authorize('update', $practiceSession);

        $path = $message->meta['recording'] ?? null;
        abort_unless(is_string($path) && Storage::disk('local')->exists($path), 404);

        return response()->file(Storage::disk('local')->path($path), [
            'Content-Type' => 'audio/webm',
            'Cache-Control' => 'private, max-age=600',
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

        if (! $practiceSession->hasReport()) {
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

        if (! $practiceSession->hasReport()) {
            return redirect()->route('exam.room', $practiceSession);
        }

        return $this->resultView($practiceSession, Gate::allows('update', $practiceSession));
    }

    /**
     * Read-only report opened from the QR code on a phone (signed link, no login needed).
     */
    public function shared(PracticeSession $practiceSession): View
    {
        abort_unless($practiceSession->isExam() && $practiceSession->hasReport(), 404);

        return $this->resultView($practiceSession, false, shared: true);
    }

    /**
     * Delete a session and its recordings at the candidate's request.
     */
    public function destroy(PracticeSession $practiceSession): RedirectResponse
    {
        abort_unless($practiceSession->isExam(), 404);
        Gate::authorize('update', $practiceSession);

        $practiceSession->deleteWithRecordings();

        return redirect()->route('exam.index')->with('status', 'Data sesi latihan sudah dihapus.');
    }

    protected function resultView(PracticeSession $practiceSession, bool $canManage, bool $shared = false): View
    {
        if (isset($practiceSession->feedback_notes['coach_report'])) {
            $coach = $practiceSession->feedback_notes['coach_report'];

            return view('exam.coach-result', [
                'session' => $practiceSession,
                'modeKey' => $practiceSession->exam_mode,
                'mode' => $this->blueprint->mode($practiceSession->exam_mode),
                'blueprint' => $this->blueprint,
                'language' => $coach['language'] ?? 'id',
                'report' => $coach,
                'transcript' => $practiceSession->messages()->orderBy('id')->get(['id', 'sender', 'message', 'exam_part', 'meta']),
                'canRate' => $canManage,
                'canManage' => $canManage,
                'shared' => $shared,
                'shareUrl' => $practiceSession->shareUrl(),
            ]);
        }

        $report = $practiceSession->feedback_notes['exam_report'];

        return view('exam.result', [
            'session' => $practiceSession,
            'modeKey' => $practiceSession->exam_mode,
            'mode' => $this->blueprint->mode($practiceSession->exam_mode),
            'blueprint' => $this->blueprint,
            'language' => $report['language'] ?? 'id',
            'report' => $report,
            'transcript' => $practiceSession->messages()->orderBy('id')->get(['id', 'sender', 'message', 'exam_part', 'meta']),
            'canRate' => $canManage,
            'canManage' => $canManage,
            'shared' => $shared,
            'shareUrl' => $practiceSession->shareUrl(),
            'drill' => fn (string $criterion) => app(ExamProgressService::class)->drillUrl($practiceSession->exam_mode, $criterion),
            'study' => $this->studyState($practiceSession, $canManage && ! $shared),
        ]);
    }

    /**
     * Attach the measured latency to the examiner reply this turn produced.
     *
     * @param  array<string, int|bool|null>  $latency
     */
    protected function recordLatency(PracticeSession $practiceSession, array $latency): void
    {
        $reply = $practiceSession->messages()->where('sender', 'ai')->latest('id')->first();
        if ($reply !== null) {
            $reply->update(['meta' => array_merge($reply->meta ?? [], ['latency' => $latency])]);
        }
    }

    /**
     * User-trial banner on the report: which practice this was, and the questionnaire after the third.
     *
     * @return array{code: string, attempt: ?int, total: int, survey: bool}|null
     */
    protected function studyState(PracticeSession $practiceSession, bool $isOwner): ?array
    {
        $code = UserStudy::codeOf($practiceSession);
        if (! $isOwner || $code === null) {
            return null;
        }
        $study = app(UserStudy::class);

        return [
            'code' => $code,
            'attempt' => $study->attemptNumber($practiceSession),
            'total' => UserStudy::ATTEMPTS_FOR_SURVEY,
            'survey' => $study->needsSurvey($practiceSession),
        ];
    }

    /**
     * Store the user-trial questionnaire (once per participant code).
     */
    public function survey(Request $request, PracticeSession $practiceSession): RedirectResponse
    {
        Gate::authorize('update', $practiceSession);
        abort_unless(app(UserStudy::class)->needsSurvey($practiceSession), 404);

        $rules = ['comment' => ['nullable', 'string', 'max:1000']];
        foreach (array_keys(UserStudy::QUESTIONS) as $key) {
            $rules["answers.{$key}"] = ['required', 'integer', 'between:1,5'];
        }
        $validated = $request->validate($rules, ['answers.*.required' => 'Mohon jawab semua pertanyaan.']);

        StudySurvey::create([
            'code' => UserStudy::codeOf($practiceSession),
            'practice_session_id' => $practiceSession->id,
            'answers' => array_map('intval', $validated['answers']),
            'comment' => $validated['comment'] ?? null,
        ]);

        return redirect()->route('exam.result', $practiceSession)->with('status', 'study-survey-saved');
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
