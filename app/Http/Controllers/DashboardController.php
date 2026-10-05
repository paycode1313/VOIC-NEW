<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Display the user dashboard with practice analytics.
     */
    public function __invoke(Request $request): View
    {
        $user = $request->user();

        $recentSessions = $user->practiceSessions()
            ->with('aiRole')
            ->latest()
            ->take(10)
            ->get();

        $totalSessions = $user->practiceSessions()->count();
        $averageScore = $totalSessions > 0
            ? round((float) $user->practiceSessions()->avg('overall_score'), 1)
            : 0;
        $bestScore = $totalSessions > 0
            ? round((float) $user->practiceSessions()->max('overall_score'), 1)
            : 0;
        $averageEyeScore = $totalSessions > 0
            ? round((float) $user->practiceSessions()->whereNotNull('face_score')->avg('face_score'), 1)
            : 0;
        $averageVoiceScore = $totalSessions > 0
            ? round((float) $user->practiceSessions()->whereNotNull('voice_score')->avg('voice_score'), 1)
            : 0;
        $totalDurationSeconds = (int) $user->practiceSessions()->sum('duration_seconds');

        // Prepare chart data chronologically (oldest to newest for last 10 sessions)
        $chartSessions = $user->practiceSessions()
            ->latest()
            ->take(10)
            ->get()
            ->reverse()
            ->values();

        $chartLabels = $chartSessions->map(function ($session, int $index) {
            return 'Sesi #'.($index + 1).' ('.$session->created_at->format('d M').')';
        });

        $chartScores = $chartSessions->pluck('overall_score');

        return view('dashboard', [
            'recentSessions' => $recentSessions,
            'totalSessions' => $totalSessions,
            'averageScore' => $averageScore,
            'bestScore' => $bestScore,
            'averageEyeScore' => $averageEyeScore,
            'averageVoiceScore' => $averageVoiceScore,
            'totalDurationSeconds' => $totalDurationSeconds,
            'chartLabels' => $chartLabels,
            'chartScores' => $chartScores,
        ]);
    }
}
