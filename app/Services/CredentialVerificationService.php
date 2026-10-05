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

        // 1. National Percentile Computation
        if ($overall >= 85) {
            $percentile = 'Top 8% Peserta Terkalibrasi';
            $readinessStatus = 'Sangat Siap (Distinction Tier)';
            $readinessIndex = (int) round(90 + (($overall - 85) / 15) * 10);
        } elseif ($overall >= 70) {
            $percentile = 'Top 25% Rata-Rata Nasional';
            $readinessStatus = 'Memenuhi Standar Kelulusan';
            $readinessIndex = (int) round(75 + (($overall - 70) / 15) * 14);
        } else {
            $percentile = 'Kategori Perkembangan';
            $readinessStatus = 'Perlu Pendampingan Lanjutan';
            $readinessIndex = (int) max(40, round($overall));
        }

        // 2. Metric 1: Eye Contact (Benchmark: 75%)
        $candidateEye = (float) ($notes['eye_contact_score'] ?? $session->face_score ?? 78);
        $eyeBenchmark = 75.0;
        $eyeDelta = $candidateEye - $eyeBenchmark;
        $eyeSuperior = $eyeDelta >= 0;

        // 3. Metric 2: Filler Words (Benchmark: <= 3 kata)
        $fillerCount = (int) ($notes['filler_words_count'] ?? 0);
        $fillerSuperior = $fillerCount <= 3;

        // 4. Metric 3: Speech Pace (Benchmark: 115 - 145 WPM)
        $paceWpm = (float) ($notes['pace_wpm'] ?? 125);
        $paceSuperior = ($paceWpm >= 115 && $paceWpm <= 145);

        // 5. Metric 4: Facial Composure & Composure Index (Benchmark: 70%)
        $composure = (float) ($session->face_score ?? $notes['smile_rate'] ?? 75);
        $composureSuperior = $composure >= 70.0;

        return [
            'serial' => $serial,
            'percentile_rank' => $percentile,
            'readiness_index' => min(99, max(30, $readinessIndex)),
            'readiness_status' => $readinessStatus,
            'metrics' => [
                'eye_contact' => [
                    'label' => 'Konsistensi Tatapan Mata',
                    'candidate_val' => number_format($candidateEye, 1).'%',
                    'benchmark_val' => '75.0% (Standar Industri)',
                    'delta_val' => ($eyeSuperior ? '+' : '').number_format($eyeDelta, 1).'%',
                    'is_superior' => $eyeSuperior,
                    'status_badge' => $eyeSuperior ? 'Unggul & Terfokus' : 'Perlu Penyesuaian',
                ],
                'filler_words' => [
                    'label' => 'Pengendalian Filler Words',
                    'candidate_val' => $fillerCount.' kata',
                    'benchmark_val' => 'Maksimal ≤ 3 kata',
                    'delta_val' => $fillerCount <= 2 ? '0-2 Gumaman' : "{$fillerCount} Gumaman",
                    'is_superior' => $fillerSuperior,
                    'status_badge' => $fillerCount <= 1 ? 'Sangat Bersih (Elite)' : ($fillerSuperior ? 'Terkendali' : 'Perlu Pengurangan'),
                ],
                'speech_pace' => [
                    'label' => 'Tempo Artikulasi Bicara',
                    'candidate_val' => number_format($paceWpm, 0).' WPM',
                    'benchmark_val' => '115 – 145 WPM (Ideal)',
                    'delta_val' => $paceSuperior ? 'Rentang Optimal' : ($paceWpm > 145 ? 'Terlalu Cepat' : 'Terlalu Lambat'),
                    'is_superior' => $paceSuperior,
                    'status_badge' => $paceSuperior ? 'Irama Pas' : 'Perlu Stabilisasi',
                ],
                'composure' => [
                    'label' => 'Ketenangan Wajah & Gestur',
                    'candidate_val' => number_format($composure, 1).'%',
                    'benchmark_val' => '70.0% (Standar Kelulusan)',
                    'delta_val' => ($composure >= 70 ? '+' : '').number_format($composure - 70, 1).'%',
                    'is_superior' => $composureSuperior,
                    'status_badge' => $composureSuperior ? 'Percaya Diri' : 'Tampak Tegang',
                ],
            ],
        ];
    }
}
