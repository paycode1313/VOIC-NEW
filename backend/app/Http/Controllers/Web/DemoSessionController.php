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

        // Sessions from the first VOIC version: a compact archive report.
        $practiceSession->load(['aiRole', 'messages']);

        return view('practice.show', [
            'session' => $practiceSession,
            'role' => $practiceSession->aiRole,
            'messages' => $practiceSession->messages,
            'benchmarkData' => $verificationService->getBenchmarkData($practiceSession),
            'isDemo' => true,
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
