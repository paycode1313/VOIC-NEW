<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePracticeSessionRequest;
use App\Models\AiRole;
use App\Models\PracticeSession;
use App\Models\User;
use App\Services\AiRoleplayService;
use App\Services\CredentialVerificationService;
use App\Services\Exam\ExamBlueprint;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PracticeSessionController extends Controller
{
    /**
     * The practice room now runs the three-part mock exam flow; keep the old URL working.
     */
    public function create(Request $request, ExamBlueprint $blueprint): RedirectResponse
    {
        $mode = $blueprint->modeForRoleType($request->query('role'));

        return $mode
            ? redirect()->route('exam.setup', $mode)
            : redirect()->route('exam.index');
    }

    /**
     * Start a new interactive AI roleplay session.
     */
    public function start(Request $request, AiRoleplayService $aiService): JsonResponse
    {
        $validated = $request->validate([
            'ai_role_id' => ['required', 'exists:ai_roles,id'],
            'scenario_type' => ['nullable', 'string', 'max:100'],
            'language' => ['nullable', 'string', 'in:id,en'],
            'context_material' => ['nullable', 'string', 'max:3000'],
        ]);

        /** @var User $user */
        $user = $request->user();

        /** @var AiRole $role */
        $role = AiRole::findOrFail($validated['ai_role_id']);

        $scenarioType = $validated['scenario_type'] ?? $role->name;
        $language = $validated['language'] ?? 'id';
        $contextMaterial = $validated['context_material'] ?? null;

        /** @var PracticeSession $session */
        $session = $user->practiceSessions()->create([
            'ai_role_id' => $role->id,
            'scenario_type' => $scenarioType,
            'duration_seconds' => 0,
            'overall_score' => 0,
            'feedback_notes' => [
                'language' => $language,
                'context_material' => $contextMaterial,
            ],
        ]);

        $greeting = $aiService->getInitialGreeting($role, $scenarioType, $language, $contextMaterial);

        // Store opening turn from the AI character
        $initialMessage = $session->messages()->create([
            'sender' => 'ai',
            'message' => $greeting['message'],
            'audio_url' => $greeting['audio_url'],
            'facial_status' => null,
            'timestamp_seconds' => 0,
        ]);

        return response()->json([
            'success' => true,
            'session_id' => $session->id,
            'language' => $language,
            'role' => [
                'id' => $role->id,
                'name' => $role->name,
                'role_type' => $role->role_type,
                'avatar' => $role->avatar,
                'voice_id' => $role->voice_id,
            ],
            'initial_message' => [
                'id' => $initialMessage->id,
                'sender' => 'ai',
                'message' => $greeting['message'],
                'audio_url' => $greeting['audio_url'],
                'timestamp_seconds' => 0,
            ],
        ], 201);
    }

    /**
     * Process an incoming conversational turn (speech text + facial telemetry).
     */
    public function message(Request $request, PracticeSession $practiceSession, AiRoleplayService $aiService): JsonResponse
    {
        Gate::authorize('update', $practiceSession);

        $validated = $request->validate([
            'message' => ['required', 'string', 'max:2000'],
            'facial_status' => ['nullable', 'array'],
            'facial_status.status' => ['nullable', 'string'],
            'facial_status.eye_contact_score' => ['nullable', 'numeric'],
            'facial_status.is_smiling' => ['nullable', 'boolean'],
            'facial_status.face_detected' => ['nullable', 'boolean'],
            'timestamp_seconds' => ['required', 'integer', 'min:0'],
            'language' => ['nullable', 'string', 'in:id,en'],
        ]);

        $language = $validated['language'] ?? ($practiceSession->feedback_notes['language'] ?? 'id');

        $result = $aiService->processTurn(
            $practiceSession,
            $validated['message'],
            $validated['facial_status'] ?? [],
            (int) $validated['timestamp_seconds'],
            $language
        );

        return response()->json([
            'success' => true,
            'user_message' => $result['user_message'],
            'ai_message' => $result['ai_message'],
            'response_text' => $result['response_text'],
            'audio_url' => $result['audio_url'],
            'facial_critique' => $result['facial_critique'],
            'coherence_score' => $result['coherence_score'] ?? 86.0,
            'coherence_category' => $result['coherence_category'] ?? 'Relevan & Nyambung',
            'salient_entities' => $result['salient_entities'] ?? [],
            'ai_engine' => $result['ai_engine'] ?? 'persona_fallback',
            'stage' => $result['stage'] ?? 'core',
        ]);
    }

    /**
     * Finalize an interactive practice session, save evaluation scores and generate AI conclusion.
     */
    public function finish(Request $request, PracticeSession $practiceSession, AiRoleplayService $aiService): JsonResponse
    {
        Gate::authorize('update', $practiceSession);

        $validated = $request->validate([
            'duration_seconds' => ['required', 'integer', 'min:1'],
            'face_score' => ['required', 'numeric', 'between:0,100'],
            'voice_score' => ['required', 'numeric', 'between:0,100'],
            'overall_score' => ['required', 'numeric', 'between:0,100'],
            'feedback_notes' => ['nullable', 'array'],
            'feedback_notes.language' => ['nullable', 'string', 'in:id,en'],
            'feedback_notes.context_material' => ['nullable', 'string', 'max:3000'],
            'feedback_notes.summary' => ['nullable', 'string', 'max:1000'],
            'feedback_notes.eye_contact_score' => ['nullable', 'numeric', 'between:0,100'],
            'feedback_notes.smile_rate' => ['nullable', 'numeric', 'between:0,100'],
            'feedback_notes.pace_wpm' => ['nullable', 'numeric', 'min:0'],
            'feedback_notes.pitch_dynamics_score' => ['nullable', 'numeric', 'between:0,100'],
            'feedback_notes.clarity_score' => ['nullable', 'numeric', 'between:0,100'],
            'feedback_notes.avg_volume' => ['nullable', 'numeric', 'min:0'],
            'feedback_notes.total_words' => ['nullable', 'integer', 'min:0'],
            'feedback_notes.filler_words_count' => ['nullable', 'integer', 'min:0'],
            'feedback_notes.filler_words_list' => ['nullable', 'array'],
            'feedback_notes.filler_words_list.*' => ['nullable', 'string', 'max:50'],
            'feedback_notes.timeline_events' => ['nullable', 'array'],
            'feedback_notes.badges' => ['nullable', 'array'],
            'feedback_notes.badges.*' => ['nullable', 'string', 'max:100'],
            'feedback_notes.strengths' => ['nullable', 'array'],
            'feedback_notes.strengths.*' => ['nullable', 'string', 'max:500'],
            'feedback_notes.improvements' => ['nullable', 'array'],
            'feedback_notes.improvements.*' => ['nullable', 'string', 'max:500'],
            'feedback_notes.detected_uptalking' => ['nullable', 'integer', 'min:0'],
            'feedback_notes.awkward_pauses' => ['nullable', 'integer', 'min:0'],
            'feedback_notes.current_stage' => ['nullable', 'string', 'max:50'],
            'feedback_notes.timeline_bookmarks' => ['nullable', 'array'],
            'feedback_notes.timeline_bookmarks.*.second' => ['nullable', 'numeric'],
            'feedback_notes.timeline_bookmarks.*.type' => ['nullable', 'string', 'max:50'],
            'feedback_notes.timeline_bookmarks.*.label' => ['nullable', 'string', 'max:150'],
            'feedback_notes.timeline_bookmarks.*.note' => ['nullable', 'string', 'max:300'],
        ]);

        $faceScore = (float) $validated['face_score'];
        $voiceScore = (float) $validated['voice_score'];
        $overallScore = (float) $validated['overall_score'];
        $existingFeedback = is_array($practiceSession->feedback_notes) ? $practiceSession->feedback_notes : [];
        $feedbackNotes = array_merge($existingFeedback, $validated['feedback_notes'] ?? []);
        $language = $feedbackNotes['language'] ?? 'id';

        $aiConclusion = $aiService->generateFinalConclusion(
            $practiceSession,
            $faceScore,
            $voiceScore,
            $overallScore,
            $feedbackNotes,
            $language
        );

        $practiceSession->update([
            'duration_seconds' => (int) $validated['duration_seconds'],
            'face_score' => $faceScore,
            'voice_score' => $voiceScore,
            'overall_score' => $overallScore,
            'ai_conclusion' => $aiConclusion,
            'feedback_notes' => $feedbackNotes,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Sesi berhasil diselesaikan.',
            'redirect_url' => route('practice.show', $practiceSession),
        ]);
    }

    /**
     * Store a newly created practice session result in MySQL (legacy/direct submit).
     */
    public function store(StorePracticeSessionRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $session = $user->practiceSessions()->create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Sesi latihan berhasil disimpan.',
            'session_id' => $session->id,
            'redirect_url' => route('practice.show', $session),
        ], 201);
    }

    /**
     * Display paginated history of all practice sessions with search and filter.
     */
    public function index(Request $request): View
    {
        /** @var User $user */
        $user = $request->user();

        $query = $user->practiceSessions()->with('aiRole')->latest();

        // Filter by AI role type
        if ($request->filled('role')) {
            $role = (string) $request->input('role');
            $mode = app(ExamBlueprint::class)->modeForRoleType($role);
            $query->where(function ($q) use ($role, $mode) {
                $q->whereHas('aiRole', fn ($roleQuery) => $roleQuery->where('role_type', $role));
                if ($mode !== null) {
                    $q->orWhere('exam_mode', $mode);
                }
            });
        }

        // Search by scenario type or AI conclusion
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('scenario_type', 'like', "%{$search}%")
                    ->orWhere('ai_conclusion', 'like', "%{$search}%");
            });
        }

        // Filter by score range
        if ($request->filled('min_score')) {
            $query->where('overall_score', '>=', (float) $request->input('min_score'));
        }

        $sessions = $query->paginate(15)->withQueryString();

        $roleOptions = AiRole::where('is_active', true)
            ->orderBy('id')
            ->get(['id', 'name', 'role_type']);

        return view('practice.history', [
            'sessions' => $sessions,
            'roleOptions' => $roleOptions,
            'currentRole' => $request->input('role'),
            'currentSearch' => $request->input('search'),
            'currentMinScore' => $request->input('min_score'),
        ]);
    }

    /**
     * Delete a practice session and all associated messages.
     */
    public function destroy(PracticeSession $practiceSession): JsonResponse
    {
        Gate::authorize('delete', $practiceSession);

        $practiceSession->delete();

        return response()->json([
            'success' => true,
            'message' => 'Sesi latihan berhasil dihapus.',
        ]);
    }

    /**
     * Display detailed post-practice feedback and performance metrics.
     */
    public function show(PracticeSession $practiceSession, CredentialVerificationService $verificationService): View|RedirectResponse
    {
        Gate::authorize('view', $practiceSession);

        // Exam sessions have their own band-score report.
        if ($practiceSession->isExam()) {
            return redirect()->to($practiceSession->reportUrl());
        }

        $practiceSession->load(['aiRole', 'messages', 'user']);
        $benchmarkData = $verificationService->getBenchmarkData($practiceSession);

        $user = $practiceSession->user ?? auth()->user();
        $streakData = $user ? $user->getStreakData() : ['streak' => 1, 'has_practiced_today' => true, 'is_streak_at_risk' => false];
        $rankProgress = $user ? $user->getRankProgress() : [
            'level' => 1,
            'title' => 'Novice Speaker',
            'current_sessions' => 1,
            'next_sessions' => 3,
            'progress_pct' => 33,
            'needed_sessions' => 2,
            'next_title' => 'Confident Communicator',
        ];
        $recommendedMission = $user ? $user->getNextRecommendedMission() : [
            'id' => 'first_streak',
            'title' => 'Kunci Streak Hari Pertama',
            'subtitle' => 'Target: Buat Akun & Lanjutkan Latihan',
            'desc' => 'Simpan rapor ini dan lanjutkan latihan besok untuk membangun kebiasaan berbicara percaya diri!',
            'icon' => 'sparkles',
            'role_key' => 'dosen_penguji',
            'badge_target' => 'Tatapan Elang',
            'route' => route('register', ['claim_session_id' => $practiceSession->id]),
        ];

        return view('practice.show', [
            'session' => $practiceSession,
            'role' => $practiceSession->aiRole,
            'messages' => $practiceSession->messages,
            'benchmarkData' => $benchmarkData,
            'streakData' => $streakData,
            'rankProgress' => $rankProgress,
            'recommendedMission' => $recommendedMission,
        ]);
    }

    /**
     * Public verification page for certificate authentication (scanned via QR code).
     */
    public function verify(PracticeSession $practiceSession, CredentialVerificationService $verificationService): View
    {
        $practiceSession->load(['aiRole', 'user']);
        $benchmarkData = $verificationService->getBenchmarkData($practiceSession);

        return view('practice.verify', [
            'session' => $practiceSession,
            'role' => $practiceSession->aiRole,
            'user' => $practiceSession->user,
            'benchmarkData' => $benchmarkData,
        ]);
    }

    /**
     * Export all practice session telemetry metrics of the authenticated user to CSV.
     */
    public function exportCsv(Request $request): StreamedResponse
    {
        /** @var User $user */
        $user = $request->user();

        $sessions = $user->practiceSessions()
            ->with('aiRole')
            ->orderByDesc('id')
            ->get();

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="voic-telemetry-sessions-'.now()->format('Ymd-His').'.csv"',
        ];

        $callback = function () use ($sessions) {
            $file = fopen('php://output', 'w');
            // Add UTF-8 BOM for Excel compatibility
            fwrite($file, "\xEF\xBB\xBF");

            // CSV Header
            fputcsv($file, [
                'ID Sesi',
                'Skenario / Topik',
                'Karakter AI Penguji',
                'Bahasa',
                'Tanggal Sesi',
                'Durasi (Detik)',
                'Skor Optik Wajah (0-100)',
                'Skor Suara & Vokal (0-100)',
                'Skor Keseluruhan (0-100)',
                'Kontak Mata (%)',
                'Ekspresi Rileks (%)',
                'Kecepatan Bicara (WPM)',
                'Dinamika Intonasi (%)',
                'Jumlah Kata Gumaman (Filler)',
                'Lencana / Badges',
                'Kesimpulan AI',
            ]);

            foreach ($sessions as $session) {
                $feedback = is_array($session->feedback_notes) ? $session->feedback_notes : [];
                $badges = isset($feedback['badges']) && is_array($feedback['badges']) ? implode(', ', $feedback['badges']) : '-';
                fputcsv($file, [
                    $session->id,
                    $session->scenario_type,
                    $session->aiRole?->name ?? 'Evaluator AI',
                    strtoupper($feedback['language'] ?? 'ID'),
                    $session->created_at->format('Y-m-d H:i:s'),
                    $session->duration_seconds,
                    $session->face_score ?? '-',
                    $session->voice_score ?? '-',
                    $session->overall_score ?? '-',
                    $feedback['eye_contact_score'] ?? '-',
                    $feedback['smile_rate'] ?? '-',
                    $feedback['pace_wpm'] ?? '-',
                    $feedback['pitch_dynamics_score'] ?? '-',
                    $feedback['filler_words_count'] ?? 0,
                    $badges,
                    $session->ai_conclusion ?? '-',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
