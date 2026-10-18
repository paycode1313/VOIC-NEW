<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'has_completed_onboarding', 'onboarding_goal', 'onboarding_experience', 'ai_learning_profile'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'has_completed_onboarding' => 'boolean',
            'ai_learning_profile' => 'array',
        ];
    }

    /**
     * Get the practice sessions for the user.
     *
     * @return HasMany<PracticeSession, $this>
     */
    public function practiceSessions(): HasMany
    {
        return $this->hasMany(PracticeSession::class);
    }

    /**
     * Get the structured AI deep learning profile for the user.
     *
     * @return array<string, mixed>
     */
    public function getAiLearningProfile(): array
    {
        $default = [
            'learning_level' => 1,
            'total_turns_learned' => 0,
            'adaptive_mastery_rate' => 70,
            'identified_weaknesses' => [],
            'mastered_topics' => [],
            'preferred_pace_wpm' => 120,
            'recent_coherence_scores' => [],
            'last_trained_at' => null,
        ];

        return array_merge($default, $this->ai_learning_profile ?? []);
    }

    /**
     * Get the current consecutive daily practice streak.
     *
     * @return array{streak: int, has_practiced_today: bool, is_streak_at_risk: bool}
     */
    public function getStreakData(): array
    {
        $dates = $this->practiceSessions()
            ->latest('created_at')
            ->pluck('created_at')
            ->map(fn ($dt) => ($dt instanceof CarbonInterface ? $dt : Carbon::parse($dt))->format('Y-m-d'))
            ->unique()
            ->values();

        if ($dates->isEmpty()) {
            return [
                'streak' => 0,
                'has_practiced_today' => false,
                'is_streak_at_risk' => false,
            ];
        }

        $today = now()->format('Y-m-d');
        $yesterday = now()->subDay()->format('Y-m-d');

        $hasPracticedToday = $dates->contains($today);
        $hasPracticedYesterday = $dates->contains($yesterday);

        // If user hasn't practiced today or yesterday, streak is reset to 0
        if (! $hasPracticedToday && ! $hasPracticedYesterday) {
            return [
                'streak' => 0,
                'has_practiced_today' => false,
                'is_streak_at_risk' => false,
            ];
        }

        $streak = 0;
        $checkDate = $hasPracticedToday ? now() : now()->subDay();

        foreach ($dates as $dateStr) {
            if ($dateStr === $checkDate->format('Y-m-d')) {
                $streak++;
                $checkDate->subDay();
            } else {
                break;
            }
        }

        return [
            'streak' => max(1, $streak),
            'has_practiced_today' => $hasPracticedToday,
            'is_streak_at_risk' => ! $hasPracticedToday && $hasPracticedYesterday,
        ];
    }

    /**
     * Get user rank and level progression details.
     *
     * @return array{level: int, title: string, current_sessions: int, next_sessions: int, progress_pct: int, needed_sessions: int, next_title: string}
     */
    public function getRankProgress(): array
    {
        $totalSessions = $this->practiceSessions()->count();

        $ranks = [
            1 => ['title' => 'Novice Speaker', 'min' => 0, 'next' => 3, 'next_title' => 'Confident Communicator'],
            2 => ['title' => 'Confident Communicator', 'min' => 3, 'next' => 7, 'next_title' => 'Skilled Orator'],
            3 => ['title' => 'Skilled Orator', 'min' => 7, 'next' => 15, 'next_title' => 'Master of Defense'],
            4 => ['title' => 'Master of Defense', 'min' => 15, 'next' => 25, 'next_title' => 'Executive Grandmaster'],
            5 => ['title' => 'Executive Grandmaster', 'min' => 25, 'next' => 25, 'next_title' => 'Max Level Reached'],
        ];

        $currentLevel = 1;
        foreach ($ranks as $lvl => $info) {
            if ($totalSessions >= $info['min']) {
                $currentLevel = $lvl;
            }
        }

        $rankInfo = $ranks[$currentLevel];
        if ($currentLevel === 5) {
            $progressPct = 100;
            $neededSessions = 0;
        } else {
            $range = $rankInfo['next'] - $rankInfo['min'];
            $doneInRange = max(0, $totalSessions - $rankInfo['min']);
            $progressPct = min(100, (int) round(($doneInRange / $range) * 100));
            $neededSessions = max(0, $rankInfo['next'] - $totalSessions);
        }

        return [
            'level' => $currentLevel,
            'title' => $rankInfo['title'],
            'current_sessions' => $totalSessions,
            'next_sessions' => $rankInfo['next'],
            'progress_pct' => $progressPct,
            'needed_sessions' => $neededSessions,
            'next_title' => $rankInfo['next_title'],
        ];
    }

    /**
     * Get the dynamic next daily challenge / mission tailored to user's recent performance.
     *
     * @return array{id: string, title: string, subtitle: string, desc: string, icon: string, role_key: string, badge_target: string, route: string}
     */
    public function getNextRecommendedMission(): array
    {
        $latestSession = $this->practiceSessions()->latest()->first();

        if (! $latestSession) {
            return [
                'id' => 'first_flight',
                'title' => 'Misi Perdana: Uji Nyali Tatap Muka',
                'subtitle' => 'Langkah Awal Membangun Kepercayaan Diri',
                'desc' => 'Mulai simulasi pertamamu bersama VOIC - Dosen Penguji. Bicaralah santai selama 2-3 menit.',
                'icon' => 'sparkles',
                'role_key' => 'dosen_penguji',
                'badge_target' => 'Tatapan Elang',
                'route' => route('practice.create', ['role' => 'dosen_penguji']),
            ];
        }

        $notes = is_array($latestSession->feedback_notes) ? $latestSession->feedback_notes : [];
        $fillerCount = $notes['filler_count'] ?? 0;
        $faceScore = $latestSession->face_score ?? 0;
        $voiceScore = $latestSession->voice_score ?? 0;
        $overallScore = $latestSession->overall_score ?? 0;

        if ($fillerCount > 3) {
            return [
                'id' => 'zero_filler_drill',
                'title' => 'Misi Penakluk Jeda: Drill Bebas Filler',
                'subtitle' => 'Target: Maksimal 1 Kata Jeda',
                'desc' => 'Pada sesi lalu terdeteksi '.$fillerCount.' kata jeda. Coba bicara lebih teratur dengan jeda hening (power pause) tanpa "eemm/ngg..".',
                'icon' => 'mic',
                'role_key' => 'hrd',
                'badge_target' => 'Artikulasi Emas',
                'route' => route('practice.create', ['role' => 'hrd']),
            ];
        }

        if ($faceScore < 70) {
            return [
                'id' => 'eye_focus_drill',
                'title' => 'Misi Tatapan Elang: Kunci Kontak Mata',
                'subtitle' => 'Target: Kontak Mata ≥ 75%',
                'desc' => 'Pertahankan tatapan langsung ke lensa webcam. Latih wibawa dan keyakinan visual saat menjawab pertanyaan penting.',
                'icon' => 'eye',
                'role_key' => 'investor',
                'badge_target' => 'Tatapan Elang',
                'route' => route('practice.create', ['role' => 'investor']),
            ];
        }

        if ($overallScore >= 80) {
            return [
                'id' => 'global_challenge',
                'title' => 'Tantangan Global: Wawancara Bahasa Inggris',
                'subtitle' => 'Buka Badge Global Communicator',
                'desc' => 'Performa Bahasa Indonesia Anda sudah sangat solid! Sekarang uji kemampuan berbicara Anda dalam skenario Wawancara Global.',
                'icon' => 'globe',
                'role_key' => 'hrd',
                'badge_target' => 'Global Communicator',
                'route' => route('practice.create', ['role' => 'hrd', 'lang' => 'en']),
            ];
        }

        return [
            'id' => 'mastery_round',
            'title' => 'Misi Lanjutan: Pertajam Argumen Metodologi',
            'subtitle' => 'Tingkatkan Koherensi Argumen',
            'desc' => 'AI telah mencatat poin jawaban Anda. Di sesi ini, buktikan ketajaman analisis Anda dalam menghadapi sanggahan dosen.',
            'icon' => 'academic-cap',
            'role_key' => 'dosen_penguji',
            'badge_target' => 'High Achiever',
            'route' => route('practice.create', ['role' => 'dosen_penguji']),
        ];
    }
}
