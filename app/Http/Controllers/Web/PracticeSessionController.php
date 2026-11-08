<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\AiRole;
use App\Models\PracticeSession;
use App\Models\User;
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
