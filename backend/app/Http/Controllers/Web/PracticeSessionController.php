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
    public function destroy(Request $request, PracticeSession $practiceSession): JsonResponse|RedirectResponse
    {
        Gate::authorize('delete', $practiceSession);

        $practiceSession->delete();

        if (! $request->expectsJson()) {
            return redirect()->route('practice.history')->with('status', 'Sesi latihan berhasil dihapus.');
        }

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

        // Sessions from the first VOIC version: a compact archive report.
        $practiceSession->load(['aiRole', 'messages', 'user']);

        return view('practice.show', [
            'session' => $practiceSession,
            'role' => $practiceSession->aiRole,
            'messages' => $practiceSession->messages,
            'benchmarkData' => $verificationService->getBenchmarkData($practiceSession),
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
     * Download the user's practice history as CSV (opens in Excel and Google Sheets): one row per session
     * with what its report measured. Sessions from the first VOIC version keep their 0-100 score.
     */
    public function exportCsv(Request $request, ExamBlueprint $blueprint): StreamedResponse
    {
        /** @var User $user */
        $user = $request->user();

        $sessions = $user->practiceSessions()->orderByDesc('id')->get();

        $criteria = [
            'fluency' => 'Kelancaran & Alur',
            'vocabulary' => 'Kosakata & Istilah',
            'structure' => 'Isi & Struktur',
            'delivery' => 'Delivery & Kepercayaan Diri',
        ];

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="voic-riwayat-latihan-'.now()->format('Ymd-His').'.csv"',
        ];

        return response()->stream(function () use ($sessions, $criteria, $blueprint) {
            $file = fopen('php://output', 'w');
            fwrite($file, "\xEF\xBB\xBF"); // UTF-8 BOM, so Excel shows "·" and accents correctly

            fputcsv($file, [
                'ID Sesi', 'Tanggal', 'Mode', 'Jenis', 'Skenario / Topik', 'Bahasa', 'Durasi (Detik)',
                'Band Keseluruhan (0-9)', ...array_values($criteria),
                'Kata per Menit', 'Kata Pengisi', 'Kontak Mata (%)', 'Skor (0-100)', 'Fokus Perbaikan',
            ]);

            foreach ($sessions as $session) {
                $notes = is_array($session->feedback_notes) ? $session->feedback_notes : [];
                $report = $notes['exam_report'] ?? $notes['coach_report'] ?? null;
                $isExamReport = isset($notes['exam_report']);
                $band = fn (?float $value): string => $value === null ? '' : number_format($value, 1, '.', '');

                $row = [
                    $session->id,
                    $session->created_at?->format('Y-m-d H:i'),
                    $session->exam_mode && $blueprint->hasMode($session->exam_mode)
                        ? $blueprint->text($blueprint->mode($session->exam_mode)['title'], 'id') : '',
                    match (true) {
                        $session->exam_mode === null => 'Versi lama',
                        isset($notes['coach_report']) => 'Coaching',
                        $isExamReport => 'Ujian',
                        default => 'Belum selesai',
                    },
                    $session->scenario_type,
                    strtoupper((string) ($report['language'] ?? $notes['language'] ?? 'id')),
                    $session->duration_seconds,
                    $isExamReport ? $band((float) $report['overall_band']) : '',
                ];
                foreach (array_keys($criteria) as $key) {
                    $row[] = $isExamReport && isset($report['criteria'][$key]['band']) ? $band((float) $report['criteria'][$key]['band']) : '';
                }
                $row[] = $report['stats']['average_wpm'] ?? $notes['pace_wpm'] ?? '';
                $row[] = $report['stats']['filler_count'] ?? $notes['filler_words_count'] ?? '';
                $row[] = $report['stats']['eye_contact'] ?? $notes['eye_contact_score'] ?? '';
                $row[] = $session->overall_score === null ? '' : rtrim(rtrim(number_format((float) $session->overall_score, 2, '.', ''), '0'), '.');
                $row[] = implode('; ', $isExamReport ? array_column($report['focus'] ?? [], 'title') : ($report['practice'] ?? []));

                fputcsv($file, array_map($this->plainCell(...), $row));
            }

            fclose($file);
        }, 200, $headers);
    }

    /**
     * A spreadsheet runs a cell that starts with = + - @ as a formula: write such text as plain text.
     */
    protected function plainCell(mixed $value): mixed
    {
        return is_string($value) && $value !== '' && in_array($value[0], ['=', '+', '-', '@', "\t", "\r"], true)
            ? "'".$value
            : $value;
    }
}
