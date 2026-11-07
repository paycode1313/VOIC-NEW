<?php

namespace App\Services\Exam;

use App\Models\PracticeSession;
use App\Models\SessionMessage;
use App\Services\AiRoleplayService;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Turns a finished exam transcript into a band-score report.
 */
class ExamReportService
{
    /**
     * Criteria keys in display order.
     *
     * @var list<string>
     */
    public const CRITERIA = ['fluency', 'vocabulary', 'structure', 'delivery'];

    public function __construct(
        protected ExamBlueprint $blueprint,
        protected SpeakingAnalyzer $analyzer,
        protected AiRoleplayService $roleplay,
    ) {}

    /**
     * Build the report, persist scores on the session and return the report.
     *
     * @return array<string, mixed>
     */
    public function finalize(PracticeSession $session, int $durationSeconds): array
    {
        $state = $session->exam_state;
        $mode = $state['mode'];
        $language = $state['language'];

        $answers = $session->messages()
            ->where('sender', 'user')
            ->whereNotNull('exam_part')
            ->orderBy('id')
            ->get()
            ->reject(fn (SessionMessage $message) => ($message->meta['kind'] ?? null) === 'clarification')
            ->values();

        $items = $answers->map(fn (SessionMessage $message, int $index) => $this->assessAnswer($message, $mode, $language, $index))->values()->all();

        $eyeContact = $this->averageEyeContact($answers->all());
        $criteria = $this->aggregateCriteria($items, $language, $eyeContact !== null);
        $overallBand = $items === [] ? 0.0 : $this->analyzer->toBand(array_sum(array_column($criteria, 'band')) / count($criteria));

        $items = $this->attachImprovedAnswers($items, $mode, $language, $state['topic'] ?? null);

        $totalWords = array_sum(array_map(fn ($item) => $item['metrics']['word_count'], $items));
        $speakingSeconds = array_sum(array_map(fn ($item) => $item['metrics']['duration_seconds'], $items));
        $fillerTotals = [];
        foreach ($items as $item) {
            foreach ($item['metrics']['fillers'] as $filler => $count) {
                $fillerTotals[$filler] = ($fillerTotals[$filler] ?? 0) + $count;
            }
        }
        arsort($fillerTotals);

        $report = [
            'version' => 1,
            'mode' => $mode,
            'language' => $language,
            'overall_band' => $overallBand,
            'level' => $this->levelLabel($overallBand, $language),
            'criteria' => $criteria,
            'answers' => $items,
            'stats' => [
                'answers' => count($items),
                'total_words' => $totalWords,
                'speaking_seconds' => $speakingSeconds,
                'average_wpm' => $speakingSeconds >= 10 ? (int) round($totalWords / ($speakingSeconds / 60)) : null,
                'fillers' => $fillerTotals,
                'filler_count' => array_sum($fillerTotals),
                'eye_contact' => $eyeContact,
            ],
            'strengths' => $this->strengths($criteria, $language, $eyeContact !== null),
            'focus' => $this->focusAreas($criteria, $items, $language),
        ];

        $fluency = $criteria['fluency']['band'] ?? 0;
        $delivery = $criteria['delivery']['band'] ?? 0;
        $notes = is_array($session->feedback_notes) ? $session->feedback_notes : [];
        $notes['exam_report'] = $report;
        $notes['pace_wpm'] = $report['stats']['average_wpm'];
        $notes['improvements'] = array_column($report['focus'], 'title');
        $notes['strengths'] = $report['strengths'];

        $session->update([
            'duration_seconds' => max($durationSeconds, $speakingSeconds),
            'voice_score' => round($fluency / 9 * 100, 2),
            'face_score' => round($delivery / 9 * 100, 2),
            'overall_score' => round($overallBand / 9 * 100, 2),
            'ai_conclusion' => $this->summary($report, $language),
            'feedback_notes' => $notes,
        ]);

        $this->roleplay->syncUserLearningProfile($session, (float) $session->overall_score, $notes);

        return $report;
    }

    /**
     * Analyze and score one answer.
     *
     * @return array<string, mixed>
     */
    protected function assessAnswer(SessionMessage $message, string $mode, string $language, int $index): array
    {
        $meta = $message->meta ?? [];
        $question = (string) ($meta['question'] ?? '');
        $metrics = $this->analyzer->analyzeAnswer($message->message, (int) ($meta['duration_seconds'] ?? 0), $question, $mode);
        $facial = is_array($message->facial_status) ? $message->facial_status : null;
        $scores = $this->analyzer->scoreAnswer($metrics, (int) $message->exam_part, $facial);

        return [
            'index' => $index + 1,
            'part' => (int) $message->exam_part,
            'kind' => $meta['kind'] ?? 'answer',
            'question' => $this->questionOnly($question),
            'answer' => $message->message,
            'metrics' => $metrics,
            'scores' => $scores,
            'band' => $this->analyzer->toBand(array_sum($scores) / count($scores)),
            'notes' => $this->answerNotes($metrics, (int) $message->exam_part, $mode, $language),
            'improved' => null,
            'improved_source' => null,
        ];
    }

    /**
     * Keep only the question sentence(s) from an examiner turn.
     */
    protected function questionOnly(string $text): string
    {
        $sentences = preg_split('/(?<=[.!?])\s+/u', trim($text)) ?: [];
        $questions = array_values(array_filter($sentences, fn ($sentence) => str_ends_with(trim($sentence), '?')));

        if ($questions !== []) {
            return implode(' ', array_slice($questions, -2));
        }

        return implode(' ', array_slice($sentences, -1));
    }

    /**
     * Concrete, transcript-based notes for one answer.
     *
     * @param  array<string, mixed>  $metrics
     * @return list<array{type: string, text: string}>
     */
    protected function answerNotes(array $metrics, int $part, string $mode, string $language): array
    {
        $isEn = $language === 'en';
        $notes = [];
        $target = $part === 2 ? 160 : 45;

        if ($metrics['word_count'] < $target * 0.5) {
            $notes[] = ['type' => 'warn', 'text' => $isEn
                ? "Only {$metrics['word_count']} words. Aim for around {$target} so you can give a reason and an example."
                : "Hanya {$metrics['word_count']} kata. Targetkan sekitar {$target} kata agar sempat memberi alasan dan contoh."];
        }

        if ($metrics['filler_count'] >= 2) {
            $list = implode(', ', array_map(fn ($word, $count) => "\"{$word}\" ×{$count}", array_keys($metrics['fillers']), $metrics['fillers']));
            $notes[] = ['type' => 'warn', 'text' => ($isEn ? 'Filler words: ' : 'Kata pengisi: ').$list.($isEn ? '. Pause silently instead.' : '. Lebih baik diam sejenak daripada mengisi jeda.')];
        }

        if ($metrics['wpm'] !== null && $metrics['wpm'] > SpeakingAnalyzer::PACE_MAX) {
            $notes[] = ['type' => 'warn', 'text' => $isEn ? "Fast pace ({$metrics['wpm']} wpm). Slow down on key points." : "Tempo cepat ({$metrics['wpm']} kata/menit). Pelankan di poin penting."];
        } elseif ($metrics['wpm'] !== null && $metrics['wpm'] < SpeakingAnalyzer::PACE_MIN && $metrics['word_count'] >= 15) {
            $notes[] = ['type' => 'warn', 'text' => $isEn ? "Slow pace ({$metrics['wpm']} wpm) with long pauses." : "Tempo lambat ({$metrics['wpm']} kata/menit), banyak jeda panjang."];
        }

        if (! $metrics['has_example'] && $metrics['word_count'] >= 10) {
            $notes[] = ['type' => 'tip', 'text' => $isEn ? 'Add a concrete example to make it believable.' : 'Tambahkan contoh konkret supaya lebih meyakinkan.'];
        }

        if (! $metrics['has_numbers'] && in_array($mode, ['pitching', 'wawancara', 'sidang'], true) && $part !== 1) {
            $notes[] = ['type' => 'tip', 'text' => match ($mode) {
                'pitching' => $isEn ? 'Investors want numbers: users, revenue, CAC, retention.' : 'Investor butuh angka: jumlah pengguna, pendapatan, CAC, retensi.',
                'sidang' => $isEn ? 'Quote your results (accuracy, sample size, significance).' : 'Sebutkan hasil terukur (akurasi, jumlah sampel, signifikansi).',
                default => $isEn ? 'Quantify the result (time saved, % growth, team size).' : 'Ukur hasilnya (waktu yang dihemat, % peningkatan, ukuran tim).',
            }];
        }

        if (count($metrics['connectors']) >= 2) {
            $notes[] = ['type' => 'good', 'text' => ($isEn ? 'Good linking words: ' : 'Kata penghubung bagus: ').implode(', ', array_slice($metrics['connectors'], 0, 4)).'.'];
        }

        if (count($metrics['domain_terms']) >= 2) {
            $notes[] = ['type' => 'good', 'text' => ($isEn ? 'Relevant terms used: ' : 'Istilah relevan: ').implode(', ', array_slice($metrics['domain_terms'], 0, 5)).'.'];
        }

        return $notes;
    }

    /**
     * Average the four criteria across answers, weighting the long turn double.
     *
     * @param  list<array<string, mixed>>  $items
     * @return array<string, array{label: string, band: float, description: string}>
     */
    protected function aggregateCriteria(array $items, string $language, bool $hasCamera = true): array
    {
        $isEn = $language === 'en';
        $deliveryDescription = $hasCamera
            ? ($isEn ? 'Eye contact, composure and steady pace.' : 'Kontak mata, ketenangan, dan tempo stabil.')
            : ($isEn ? 'Estimated from pace only, turn the camera on for eye contact.' : 'Estimasi dari tempo saja, nyalakan kamera untuk mengukur kontak mata.');
        $labels = [
            'fluency' => [$isEn ? 'Fluency & Coherence' : 'Kelancaran & Alur', $isEn ? 'Pace, fillers and how well ideas connect.' : 'Tempo, kata pengisi, dan keterhubungan ide.'],
            'vocabulary' => [$isEn ? 'Vocabulary' : 'Kosakata & Istilah', $isEn ? 'Word variety and field-specific terms.' : 'Variasi kata dan istilah sesuai bidang.'],
            'structure' => [$isEn ? 'Content & Structure' : 'Isi & Struktur', $isEn ? 'Relevance, framework, examples and evidence.' : 'Relevansi, kerangka jawaban, contoh, dan bukti.'],
            'delivery' => [$isEn ? 'Delivery & Presence' : 'Delivery & Kepercayaan Diri', $deliveryDescription],
        ];

        $result = [];
        foreach (self::CRITERIA as $key) {
            $sum = 0.0;
            $weight = 0.0;
            foreach ($items as $item) {
                $itemWeight = $item['part'] === 2 ? 2.0 : 1.0;
                $sum += $item['scores'][$key] * $itemWeight;
                $weight += $itemWeight;
            }

            $result[$key] = [
                'label' => $labels[$key][0],
                'band' => $weight > 0 ? $this->analyzer->toBand($sum / $weight) : 0.0,
                'description' => $labels[$key][1],
            ];
        }

        return $result;
    }

    /**
     * Ask the local model to rewrite the weakest answers; fall back to a framework template.
     *
     * @param  list<array<string, mixed>>  $items
     * @return list<array<string, mixed>>
     */
    protected function attachImprovedAnswers(array $items, string $mode, string $language, ?string $topic): array
    {
        if ($items === []) {
            return $items;
        }

        $order = array_keys($items);
        usort($order, fn ($a, $b) => $items[$a]['band'] <=> $items[$b]['band']);
        $targets = array_slice($order, 0, 3);

        $improved = [];
        $baseUrl = (string) Config::get('services.ai_service.url', 'http://127.0.0.1:8001');

        try {
            $response = Http::connectTimeout(1.5)->timeout(30)->post("{$baseUrl}/exam/improve", [
                'mode' => $mode,
                'role_type' => $this->blueprint->mode($mode)['role_type'],
                'language' => $language,
                'topic' => $topic,
                'items' => array_map(fn ($key) => [
                    'key' => $key,
                    'question' => $items[$key]['question'],
                    'answer' => $items[$key]['answer'],
                ], $targets),
            ]);

            if ($response->successful()) {
                foreach ((array) $response->json('items', []) as $row) {
                    $text = trim((string) ($row['improved'] ?? ''));
                    if (isset($row['key']) && $text !== '') {
                        $improved[(int) $row['key']] = $text;
                    }
                }
            }
        } catch (\Throwable $exception) {
            Log::info('Local improve endpoint unavailable: '.$exception->getMessage());
        }

        foreach ($targets as $key) {
            if (isset($improved[$key])) {
                $items[$key]['improved'] = $improved[$key];
                $items[$key]['improved_source'] = 'ai';
            } else {
                $items[$key]['improved'] = $this->frameworkTemplate($mode, $items[$key]['part'], $language);
                $items[$key]['improved_source'] = 'framework';
            }
        }

        return $items;
    }

    /**
     * Answer framework shown when no model can rewrite the answer.
     */
    protected function frameworkTemplate(string $mode, int $part, string $language): string
    {
        $isEn = $language === 'en';

        return match ($mode) {
            'wawancara' => $isEn
                ? 'Use STAR: "At [place], we faced [situation]. My job was [task]. I [2 concrete actions]. As a result, [measurable result], and I learned [lesson]."'
                : 'Pakai STAR: "Waktu di [tempat], kami menghadapi [situasi]. Tugas saya [tanggung jawab]. Saya lalu [2 tindakan konkret]. Hasilnya [dampak terukur], dan saya belajar [pelajaran]."',
            'sidang' => $isEn
                ? 'Answer as: claim → reason → evidence → limitation. "I chose [method] because [reason]. This is supported by [data/result]. Its limitation is [x], which I handled by [y]."'
                : 'Jawab dengan pola klaim → alasan → bukti → batasan. "Saya memilih [metode] karena [alasan]. Ini didukung oleh [data/hasil]. Keterbatasannya [x], yang saya tangani dengan [y]."',
            default => $isEn
                ? 'Lead with the number: "Today we have [metric]. That matters because [insight]. Compared to [competitor], we win on [edge]. Next we will [milestone]."'
                : 'Buka dengan angka: "Saat ini kami punya [metrik]. Ini penting karena [insight]. Dibanding [kompetitor], kami unggul di [keunggulan]. Berikutnya kami akan [milestone]."',
        };
    }

    /**
     * @param  array<string, array{label: string, band: float, description: string}>  $criteria
     * @return list<string>
     */
    protected function strengths(array $criteria, string $language, bool $hasCamera = true): array
    {
        // Without camera data, delivery is only an estimate, so it is never claimed as a strength.
        $sorted = $hasCamera ? $criteria : array_diff_key($criteria, ['delivery' => true]);
        uasort($sorted, fn ($a, $b) => $b['band'] <=> $a['band']);

        return array_values(array_map(
            fn ($criterion) => ($language === 'en' ? 'Strongest: ' : 'Terkuat: ').$criterion['label'].' ('.number_format($criterion['band'], 1).')',
            array_slice($sorted, 0, 2)
        ));
    }

    /**
     * The two weakest criteria with an actionable drill each.
     *
     * @param  array<string, array{label: string, band: float, description: string}>  $criteria
     * @param  list<array<string, mixed>>  $items
     * @return list<array{criterion: string, title: string, drill: string}>
     */
    protected function focusAreas(array $criteria, array $items, string $language): array
    {
        $isEn = $language === 'en';
        $sorted = $criteria;
        uasort($sorted, fn ($a, $b) => $a['band'] <=> $b['band']);

        $drills = [
            'fluency' => $isEn
                ? 'Record a 60-second answer, count your fillers, then redo it replacing each filler with a silent pause.'
                : 'Rekam jawaban 60 detik, hitung kata pengisinya, lalu ulangi dengan mengganti setiap "eh" menjadi jeda diam.',
            'vocabulary' => $isEn
                ? 'Write 10 key terms for your field and use at least 3 of them in every answer.'
                : 'Tulis 10 istilah kunci bidangmu dan pakai minimal 3 di setiap jawaban.',
            'structure' => $isEn
                ? 'Answer every question in three beats: direct answer, reason, concrete example with a number.'
                : 'Jawab setiap pertanyaan dalam tiga ketukan: jawaban langsung, alasan, contoh konkret dengan angka.',
            'delivery' => $isEn
                ? 'Put a sticker next to your camera and keep your eyes on it while you speak.'
                : 'Tempel stiker di samping kamera dan arahkan pandangan ke situ selama berbicara.',
        ];

        return array_values(array_map(
            fn ($key) => ['criterion' => $key, 'title' => $criteria[$key]['label'], 'drill' => $drills[$key]],
            array_slice(array_keys($sorted), 0, 2)
        ));
    }

    /**
     * @param  list<SessionMessage>  $answers
     */
    protected function averageEyeContact(array $answers): ?int
    {
        $values = array_values(array_filter(array_map(
            fn (SessionMessage $message) => is_array($message->facial_status) && isset($message->facial_status['eye_contact']) ? (float) $message->facial_status['eye_contact'] : null,
            $answers
        ), fn ($value) => $value !== null));

        return $values === [] ? null : (int) round(array_sum($values) / count($values));
    }

    protected function levelLabel(float $band, string $language): string
    {
        $isEn = $language === 'en';

        return match (true) {
            $band >= 8.0 => $isEn ? 'Excellent' : 'Sangat Siap',
            $band >= 6.5 => $isEn ? 'Good' : 'Siap',
            $band >= 5.0 => $isEn ? 'Developing' : 'Berkembang',
            default => $isEn ? 'Needs practice' : 'Perlu Latihan',
        };
    }

    /**
     * One-paragraph summary saved as the session conclusion.
     *
     * @param  array<string, mixed>  $report
     */
    protected function summary(array $report, string $language): string
    {
        $band = number_format($report['overall_band'], 1);
        $focus = $report['focus'][0]['title'] ?? null;
        $strong = $report['strengths'][0] ?? null;

        if ($language === 'en') {
            return trim("Overall band {$band} ({$report['level']}) from {$report['stats']['answers']} answers. ".($strong ? "{$strong}. " : '').($focus ? "Main focus for next practice: {$focus}." : ''));
        }

        return trim("Band keseluruhan {$band} ({$report['level']}) dari {$report['stats']['answers']} jawaban. ".($strong ? "{$strong}. " : '').($focus ? "Fokus latihan berikutnya: {$focus}." : ''));
    }
}
