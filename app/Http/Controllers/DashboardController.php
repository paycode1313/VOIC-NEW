<?php

namespace App\Http\Controllers;

use App\Models\PracticeSession;
use App\Services\AiRoleplayService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Display the user dashboard with practice analytics, badge showcase, and leaderboard.
     */
    public function __invoke(Request $request, AiRoleplayService $aiService): View
    {
        $user = $request->user();
        $deepLearningStats = $aiService->getDeepLearningTelemetry($user);

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

        // Leaderboard: Top 10 Practice Sessions across all participants
        $leaderboardSessions = PracticeSession::with(['user', 'aiRole'])
            ->where('overall_score', '>', 0)
            ->orderByDesc('overall_score')
            ->take(10)
            ->get();

        // Master Badge Showcase definitions
        $masterBadges = [
            'tatapan_elang' => [
                'id' => 'tatapan_elang',
                'name' => 'Tatapan Elang',
                'icon' => 'eye',
                'desc' => 'Kontak mata fokus kamera ≥ 80%',
                'tag' => 'Fokus Visual',
            ],
            'artikulasi_emas' => [
                'id' => 'artikulasi_emas',
                'name' => 'Artikulasi Emas',
                'icon' => 'mic',
                'desc' => 'Bicara lugas tanpa jeda gumaman',
                'tag' => 'Kelancaran',
            ],
            'vokal_dinamis' => [
                'id' => 'vokal_dinamis',
                'name' => 'Vokal Dinamis',
                'icon' => 'audio',
                'desc' => 'Intonasi ekspresif & tidak monoton',
                'tag' => 'Intonasi',
            ],
            'sprint_warrior' => [
                'id' => 'sprint_warrior',
                'name' => 'Sprint Warrior',
                'icon' => 'bolt',
                'desc' => 'Menyelesaikan simulasi cepat 60-90s',
                'tag' => 'Kecepatan',
            ],
            'duta_karisma' => [
                'id' => 'duta_karisma',
                'name' => 'Duta Karisma',
                'icon' => 'shield',
                'desc' => 'Kombinasi tatapan mantap & senyum ramah',
                'tag' => 'Karisma',
            ],
            'high_achiever' => [
                'id' => 'high_achiever',
                'name' => 'High Achiever',
                'icon' => 'trophy',
                'desc' => 'Meraih skor keseluruhan istimewa ≥ 85',
                'tag' => 'Prestasi',
            ],
            'global_communicator' => [
                'id' => 'global_communicator',
                'name' => 'Global Communicator',
                'icon' => 'globe',
                'desc' => 'Menyelesaikan simulasi dalam Bahasa Inggris',
                'tag' => 'Bahasa',
            ],
        ];

        // Calculate unlocked badges for current user
        $unlockedBadgesCount = [];
        foreach ($user->practiceSessions as $session) {
            $notes = is_array($session->feedback_notes) ? $session->feedback_notes : [];
            $badges = $notes['badges'] ?? [];
            if (is_array($badges)) {
                foreach ($badges as $badgeKey) {
                    $unlockedBadgesCount[$badgeKey] = ($unlockedBadgesCount[$badgeKey] ?? 0) + 1;
                }
            }
        }

        $badgeShowcase = [];
        $unlockedTotal = 0;
        foreach ($masterBadges as $key => $meta) {
            $isUnlocked = isset($unlockedBadgesCount[$key]);
            if ($isUnlocked) {
                $unlockedTotal++;
            }
            $badgeShowcase[] = array_merge($meta, [
                'unlocked' => $isUnlocked,
                'count' => $unlockedBadgesCount[$key] ?? 0,
            ]);
        }

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
            'leaderboardSessions' => $leaderboardSessions,
            'badgeShowcase' => $badgeShowcase,
            'unlockedTotal' => $unlockedTotal,
            'totalMasterBadges' => count($masterBadges),
            'deepLearningStats' => $deepLearningStats,
            'streakData' => $user->getStreakData(),
            'rankProgress' => $user->getRankProgress(),
            'recommendedMission' => $user->getNextRecommendedMission(),
        ]);
    }
}
