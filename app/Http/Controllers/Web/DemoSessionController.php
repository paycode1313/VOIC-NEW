<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\PracticeSession;
use App\Services\CredentialVerificationService;
use App\Services\Exam\ExamBlueprint;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class DemoSessionController extends Controller
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
     * Display demo scorecard for guest.
     */
    public function show(PracticeSession $practiceSession, CredentialVerificationService $verificationService): View|RedirectResponse
    {
        Gate::authorize('view', $practiceSession);

        // Exam sessions have their own band-score report.
        if ($practiceSession->isExam()) {
            return redirect()->to($practiceSession->reportUrl());
        }

        $practiceSession->load(['aiRole', 'messages']);
        $benchmarkData = $verificationService->getBenchmarkData($practiceSession);

        $user = auth()->user();
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
            'isDemo' => true,
            'streakData' => $streakData,
            'rankProgress' => $rankProgress,
            'recommendedMission' => $recommendedMission,
        ]);
    }

    /**
     * Reset guest booth session state for the next visitor.
     */
    public function reset(Request $request): RedirectResponse
    {
        $request->session()->forget(['active_guest_session_id', 'guest_session_token', 'guest_name', 'guest_institution']);

        return redirect()->route('demo.create')->with('status', 'Booth siap untuk pengunjung berikutnya!');
    }
}
