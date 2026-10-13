<?php

namespace App\Services;

use App\Models\PracticeSession;

class CredentialVerificationService
{
    /**
     * Generate a cryptographic-style unique certificate serial number for a session.
     */
    public function generateCertificateSerial(PracticeSession $session): string
    {
        $hashInput = $session->id.'|'.$session->created_at?->toISOString().'|'.($session->user_id ?? 'guest');
        $hash = strtoupper(substr(hash('sha256', $hashInput), 0, 10));

        return "VOIC-CERT-{$hash}";
    }

    /**
     * Compute comprehensive industry benchmark comparisons and readiness ranking.
     *
     * @return array{
     *     serial: string,
     *     percentile_rank: string,
     *     readiness_index: int,
     *     readiness_status: string,
     *     metrics: array<string, array{
     *         label: string,
     *         candidate_val: string,
     *         benchmark_val: string,
     *         delta_val: string,
     *         is_superior: bool,
     *         status_badge: string
     *     }>
     * }
     */
    public function getBenchmarkData(PracticeSession $session): array
    {
        $serial = $this->generateCertificateSerial($session);
        $overall = (float) ($session->overall_score ?? 0);
        $notes = $session->feedback_notes ?? [];

        $language = $notes['language'] ?? 'id';

        // 1. Tiga Lapis Tolak Ukur (Personal, Komunitas, Nasional)
        // Lapis 1: Personal Progression (Histori sesi user sendiri)
        $personalHistory = [
            'has_history' => false,
            'sessions_count' => 0,
            'avg_score' => $overall,
            'delta_score' => 0.0,
            'summary' => 'Sesi pertama Anda — ini menjadi titik acuan awal perkembangan.',
        ];

        if ($session->user_id) {
            $priorSessions = PracticeSession::where('user_id', $session->user_id)
                ->where('id', '!=', $session->id)
                ->whereNotNull('overall_score')
                ->latest()
                ->take(5)
                ->get();

            if ($priorSessions->isNotEmpty()) {
                $priorAvg = round($priorSessions->avg('overall_score'), 1);
                $diff = round($overall - $priorAvg, 1);
                $personalHistory = [
                    'has_history' => true,
                    'sessions_count' => $priorSessions->count(),
                    'avg_score' => $priorAvg,
                    'delta_score' => $diff,
                    'summary' => $diff >= 0
                        ? "+{$diff} poin lebih tinggi dari rata-rata {$priorSessions->count()} sesi latihan Anda sebelumnya."
                        : "{$diff} poin dari rata-rata {$priorSessions->count()} sesi Anda. Terus asah ritme bicara Anda.",
                ];
            }
        }

        // Lapis 2: Komunitas Pengguna VOIC (Agregat persentil anonim pengguna aktif)
        $totalCommunitySessions = PracticeSession::whereNotNull('overall_score')->count();
        $lowerOrEqualCount = PracticeSession::whereNotNull('overall_score')
            ->where('overall_score', '<=', $overall)
            ->count();

        $communityPercentile = ($totalCommunitySessions > 0)
            ? (int) min(99, max(15, round(($lowerOrEqualCount / $totalCommunitySessions) * 100)))
            : 75;

        $communityLabel = "Skor Anda lebih tinggi dari {$communityPercentile}% pengguna VOIC";

        // Lapis 3: Kualifikasi Kesiapan (Standar Acuan Kalibrasi Sidang & Interview)
        if ($overall >= 85) {
            $percentile = 'Top '.(100 - min(95, $communityPercentile)).'% Komunitas VOIC (Tier Keunggulan)';
            $readinessStatus = 'Sangat Siap (Distinction Tier)';
            $readinessIndex = (int) round(90 + (($overall - 85) / 15) * 10);
        } elseif ($overall >= 70) {
            $percentile = 'Top '.(100 - min(90, $communityPercentile)).'% Komunitas VOIC (Standar Kelulusan)';
            $readinessStatus = 'Memenuhi Standar Kelulusan';
            $readinessIndex = (int) round(75 + (($overall - 70) / 15) * 14);
        } else {
            $percentile = 'Komunitas VOIC (Tier Pembinaan Lanjutan)';
            $readinessStatus = 'Perlu Pendampingan Lanjutan';
            $readinessIndex = (int) max(40, round($overall));
        }

        // 2. Metric 1: Head Pose & Eye Alignment (Benchmark: 75%)
        $candidateEye = (float) ($notes['eye_contact_score'] ?? $session->face_score ?? 78);
        $eyeBenchmark = 75.0;
        $eyeDelta = $candidateEye - $eyeBenchmark;
        $eyeSuperior = $eyeDelta >= 0;

        // 3. Metric 2: Filler Words (Benchmark: <= 3 kata with natural conversational tolerance)
        $fillerCount = (int) ($notes['filler_words_count'] ?? 0);
        $fillerSuperior = $fillerCount <= 3;

        // 4. Metric 3: Speech Pace Normalized by Language (Indonesian agglutinative vs English word length)
        $paceWpm = (float) ($notes['pace_wpm'] ?? 125);
        $isEnglish = ($language === 'en');
        $minIdealWpm = $isEnglish ? 120.0 : 100.0;
        $maxIdealWpm = $isEnglish ? 155.0 : 135.0;
        $paceSuperior = ($paceWpm >= $minIdealWpm && $paceWpm <= $maxIdealWpm);

        // 5. Metric 4: Facial Composure & Composure Index (Benchmark: 70%)
        $composure = (float) ($session->face_score ?? $notes['smile_rate'] ?? 75);
        $composureSuperior = $composure >= 70.0;

        return [
            'serial' => $serial,
            'percentile_rank' => $percentile,
            'community_percentile' => $communityPercentile,
            'community_label' => $communityLabel,
            'personal_progression' => $personalHistory,
            'readiness_index' => min(99, max(30, $readinessIndex)),
            'readiness_status' => $readinessStatus,
            'metrics' => [
                'eye_contact' => [
                    'label' => 'Orientasi Tatapan & Hadap Kamera',
                    'candidate_val' => number_format($candidateEye, 1).'%',
                    'benchmark_val' => '75.0% (Acuan Kalibrasi VOIC)',
                    'delta_val' => ($eyeSuperior ? '+' : '').number_format($eyeDelta, 1).'%',
                    'is_superior' => $eyeSuperior,
                    'status_badge' => $eyeSuperior ? 'Unggul & Terarah' : 'Perlu Penyesuaian',
                ],
                'filler_words' => [
                    'label' => 'Pengendalian Filler Words',
                    'candidate_val' => $fillerCount.' kata',
                    'benchmark_val' => 'Toleransi Wajar ≤ 3 kata',
                    'delta_val' => $fillerCount <= 2 ? 'Terkendali Wajar' : "{$fillerCount} Gumaman",
                    'is_superior' => $fillerSuperior,
                    'status_badge' => $fillerCount <= 1 ? 'Sangat Bersih (Alami)' : ($fillerSuperior ? 'Terkendali (Wajar)' : 'Perlu Pengurangan'),
                ],
                'speech_pace' => [
                    'label' => 'Tempo Artikulasi Bicara (Terkalibrasi)',
                    'candidate_val' => number_format($paceWpm, 0).' WPM',
                    'benchmark_val' => $isEnglish ? '120 – 155 WPM (Standar EN)' : '100 – 135 WPM (Standar ID)',
                    'delta_val' => $paceSuperior ? 'Rentang Optimal' : ($paceWpm > $maxIdealWpm ? 'Terlalu Cepat' : 'Terlalu Lambat'),
                    'is_superior' => $paceSuperior,
                    'status_badge' => $paceSuperior ? 'Irama Pas & Runtut' : 'Perlu Stabilisasi',
                ],
                'composure' => [
                    'label' => 'Ketenangan Wajah & Gestur',
                    'candidate_val' => number_format($composure, 1).'%',
                    'benchmark_val' => '70.0% (Standar Acuan Kelulusan)',
                    'delta_val' => ($composure >= 70 ? '+' : '').number_format($composure - 70, 1).'%',
                    'is_superior' => $composureSuperior,
                    'status_badge' => $composureSuperior ? 'Percaya Diri' : 'Tampak Tegang',
                ],
            ],
        ];
    }
}
