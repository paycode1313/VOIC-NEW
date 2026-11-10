<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\Exam\ExamBlueprint;
use App\Services\Exam\ExamProgressService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Personal overview: band-score progress, what to practise next, and recent sessions.
     */
    public function __invoke(Request $request, ExamProgressService $progressService, ExamBlueprint $blueprint): View
    {
        $user = $request->user();
        $sessions = $user->practiceSessions();

        $totalSessions = (clone $sessions)->count();
        $progress = $progressService->forUser($user);
        $lastMode = (clone $sessions)->whereNotNull('exam_mode')->latest()->value('exam_mode') ?? $this->modeForGoal($user->onboarding_goal);

        return view('dashboard', [
            'totalSessions' => $totalSessions,
            'averageScore' => $totalSessions > 0 ? round((float) (clone $sessions)->avg('overall_score'), 1) : 0,
            'bestScore' => $totalSessions > 0 ? round((float) (clone $sessions)->max('overall_score'), 1) : 0,
            'totalDurationSeconds' => (int) (clone $sessions)->sum('duration_seconds'),
            'recentSessions' => (clone $sessions)->latest()->take(6)->get(),
            'progress' => $progress['points'] !== [] ? $progress : null,
            'streakData' => $user->getStreakData(),
            'modes' => $blueprint->modes(),
            'blueprint' => $blueprint,
            'nextMode' => $lastMode,
            'drill' => fn (string $criterion) => $progressService->drillUrl($lastMode, $criterion),
        ]);
    }

    /**
     * Map the onboarding goal to an exam mode.
     */
    protected function modeForGoal(?string $goal): string
    {
        return match ($goal) {
            'wawancara_kerja' => 'wawancara',
            'pitching_startup' => 'pitching',
            default => 'sidang',
        };
    }
}
