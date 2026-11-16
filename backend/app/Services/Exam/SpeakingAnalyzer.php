<?php

namespace App\Services\Exam;

use Illuminate\Support\Str;

/**
 * Measures spoken answers from their transcript and timing.
 *
 * Every number here comes from what the candidate actually said, so the
 * report never shows a score that the transcript cannot explain.
 */
class SpeakingAnalyzer
{
    /**
     * Ideal speaking pace range in words per minute.
     */
    public const PACE_MIN = 105;

    public const PACE_MAX = 165;

    /**
     * Common Indonesian and English function words ignored for vocabulary metrics.
     *
     * @var list<string>
     */
    protected const STOPWORDS = [
        'yang', 'dan', 'di', 'ke', 'dari', 'ini', 'itu', 'untuk', 'dengan', 'pada', 'adalah', 'saya', 'aku', 'kami', 'kita', 'anda', 'kamu',
        'juga', 'akan', 'ada', 'tidak', 'bisa', 'sudah', 'atau', 'jadi', 'karena', 'dalam', 'seperti', 'lebih', 'sangat', 'tersebut', 'oleh',
        'para', 'agar', 'bahwa', 'sebagai', 'ya', 'nya', 'lah', 'kan', 'pun', 'saja', 'lagi', 'mau', 'apa', 'bagaimana', 'kenapa', 'mengapa',
        'tapi', 'tetapi', 'namun', 'kalau', 'jika', 'maka', 'sih', 'dong', 'deh', 'nah', 'oke', 'baik', 'terus', 'gitu', 'begitu', 'banyak',
        'the', 'a', 'an', 'and', 'or', 'of', 'to', 'in', 'on', 'for', 'with', 'is', 'are', 'was', 'were', 'be', 'i', 'my', 'we', 'our', 'you',
        'it', 'that', 'this', 'as', 'at', 'by', 'so', 'but', 'if', 'then', 'very', 'really', 'just', 'also', 'have', 'has', 'had', 'do', 'did',
    ];

    public function __construct(protected ExamBlueprint $blueprint) {}

    /**
     * Analyze one answer.
     *
     * @return array{
     *     word_count: int,
     *     duration_seconds: int,
     *     wpm: ?int,
     *     fillers: array<string, int>,
     *     filler_count: int,
     *     filler_rate: float,
     *     repetitions: int,
     *     connectors: list<string>,
     *     lexical_diversity: float,
     *     domain_terms: list<string>,
     *     structure_terms: list<string>,
     *     has_numbers: bool,
     *     has_example: bool,
     *     relevance: float,
     *     sentences: int
     * }
     */
    public function analyzeAnswer(string $answer, int $durationSeconds, string $question, string $mode): array
    {
        $normalized = $this->normalize($answer);
        $words = $this->words($normalized);
        $wordCount = count($words);

        $fillers = $this->countPhrases($normalized, (array) config('exam.fillers', []));
        $fillerCount = array_sum($fillers);

        $connectors = array_keys($this->countPhrases($normalized, (array) config('exam.connectors', [])));

        $keywords = (array) ($this->blueprint->mode($mode)['keywords'] ?? []);
        $domainTerms = array_keys($this->countPhrases($normalized, (array) ($keywords['domain'] ?? [])));
        $structureTerms = array_keys($this->countPhrases($normalized, (array) ($keywords['structure'] ?? [])));

        $contentWords = $this->contentWords($words);
        $lexicalDiversity = count($contentWords) > 0
            ? round(count(array_unique($contentWords)) / count($contentWords), 2)
            : 0.0;

        $wpm = ($durationSeconds >= 5 && $wordCount > 0)
            ? (int) round($wordCount / ($durationSeconds / 60))
            : null;

        return [
            'word_count' => $wordCount,
            'duration_seconds' => $durationSeconds,
            'wpm' => $wpm,
            'fillers' => $fillers,
            'filler_count' => $fillerCount,
            'filler_rate' => $wordCount > 0 ? round($fillerCount / $wordCount * 100, 1) : 0.0,
            'repetitions' => $this->countRepetitions($words),
            'connectors' => $connectors,
            'lexical_diversity' => $lexicalDiversity,
            'domain_terms' => $domainTerms,
            'structure_terms' => $structureTerms,
            'has_numbers' => (bool) preg_match('/\d|persen|juta|miliar|ribu|percent|million|billion|thousand/u', $normalized),
            'has_example' => (bool) preg_match('/\b(contoh|misal|misalnya|seperti saat|waktu itu|saat itu|pernah|for example|for instance|once|when i)\b/u', $normalized),
            'relevance' => $this->relevance($question, $contentWords, $domainTerms),
            'sentences' => max(1, preg_match_all('/[.!?]+/u', $answer) ?: (int) ceil($wordCount / 18)),
        ];
    }

    /**
     * Score one analyzed answer on the four criteria, each as a 0-9 band.
     *
     * @param  array<string, mixed>  $metrics
     * @param  array<string, mixed>|null  $facial
     * @return array{fluency: float, vocabulary: float, structure: float, delivery: float}
     */
    public function scoreAnswer(array $metrics, int $part, ?array $facial = null): array
    {
        $words = (int) $metrics['word_count'];
        $targetWords = $part === 2 ? 160 : 45;
        $lengthRatio = min(1.0, $words / $targetWords);

        // Fluency & coherence: enough to say, steady pace, few fillers, linked ideas.
        $fluency = 3.0 + 3.0 * $lengthRatio;
        $fluency += $this->paceScore($metrics['wpm']);
        $fluency -= min(2.0, (float) $metrics['filler_rate'] * 0.25);
        $fluency -= min(1.0, (int) $metrics['repetitions'] * 0.25);
        $fluency += min(1.5, count($metrics['connectors']) * 0.5);

        // Vocabulary: lexical variety plus field-specific terms.
        $vocabulary = 3.0 + 3.0 * (float) $metrics['lexical_diversity'] * $lengthRatio;
        $vocabulary += min(3.0, count($metrics['domain_terms']) * 0.75);

        // Structure & content: framework coverage, evidence, relevance to the question.
        $structure = 3.0 + 2.0 * (float) $metrics['relevance'];
        $structure += min(2.0, count($metrics['structure_terms']) * 0.5);
        $structure += $metrics['has_numbers'] ? 1.0 : 0.0;
        $structure += $metrics['has_example'] ? 1.0 : 0.0;
        $structure *= 0.5 + 0.5 * $lengthRatio;

        // Delivery: camera presence when available, otherwise pace stability only.
        $eyeContact = isset($facial['eye_contact']) ? (float) $facial['eye_contact'] : null;
        $delivery = $eyeContact !== null
            ? 3.0 + 4.5 * ($eyeContact / 100) + $this->paceScore($metrics['wpm'])
            : 4.5 + 2.0 * $lengthRatio + $this->paceScore($metrics['wpm']);
        $delivery -= min(1.0, (float) $metrics['filler_rate'] * 0.1);

        return [
            'fluency' => $this->toBand($fluency),
            'vocabulary' => $this->toBand($vocabulary),
            'structure' => $this->toBand($structure),
            'delivery' => $this->toBand($delivery),
        ];
    }

    /**
     * Round a raw score to the nearest half band between 1 and 9.
     */
    public function toBand(float $raw): float
    {
        return max(1.0, min(9.0, round($raw * 2) / 2));
    }

    /**
     * Extract a short phrase (2-4 words) from the answer that the examiner can echo back.
     *
     * Single words sound robotic when echoed ("Hmm, masalah, oke"), so a keyword
     * is only used together with the words that follow it in the same clause.
     */
    public function keyPhrase(string $answer, string $mode): ?string
    {
        $normalized = $this->normalize($answer);

        if (preg_match('/(?<![\p{L}\p{N}])(\d+[.,]?\d*\s?(?:persen|%|juta|ribu|miliar|orang|pengguna|pelanggan|gambar|data|sampel|responden|bulan|tahun|minggu|hari|percent|users|customers|months|years|weeks)(?:\s+[\p{L}\-]{3,}){0,1})/u', $normalized, $match)) {
            $words = explode(' ', trim($match[1]));
            if (count($words) > 2 && in_array(end($words), self::STOPWORDS, true)) {
                array_pop($words);
            }

            return implode(' ', $words);
        }

        $keywords = (array) ($this->blueprint->mode($mode)['keywords'] ?? []);
        $terms = array_merge((array) ($keywords['domain'] ?? []), (array) ($keywords['structure'] ?? []));
        usort($terms, fn ($a, $b) => mb_strlen($b) <=> mb_strlen($a));

        foreach (preg_split('/[,.;!?]|\b(?:dan|tapi|lalu|terus|karena|sehingga|and|but|because)\b/u', $normalized) ?: [] as $clause) {
            $words = $this->words(trim($clause));

            foreach ($terms as $term) {
                $termWords = explode(' ', $term);
                $position = $this->findSequence($words, $termWords);
                if ($position === null) {
                    continue;
                }

                $phrase = $termWords;
                for ($i = $position + count($termWords); $i < count($words) && count($phrase) < 4; $i++) {
                    if (in_array($words[$i], self::STOPWORDS, true) || mb_strlen($words[$i]) < 3) {
                        break;
                    }
                    $phrase[] = $words[$i];
                }

                if (count($phrase) >= 2) {
                    return implode(' ', $phrase);
                }
            }
        }

        return null;
    }

    /**
     * Detect answers like "saya tidak tahu" or "I'm not sure".
     */
    public function soundsUnsure(string $answer): bool
    {
        return (bool) preg_match('/\b(tidak tahu|nggak tahu|gak tahu|ga tau|nggak tau|kurang tahu|lupa|bingung|i don\'?t know|not sure|no idea)\b/iu', $answer);
    }

    /**
     * Find where a word sequence starts inside a list of words.
     *
     * @param  list<string>  $words
     * @param  list<string>  $sequence
     */
    protected function findSequence(array $words, array $sequence): ?int
    {
        $length = count($sequence);

        for ($i = 0, $limit = count($words) - $length; $i <= $limit; $i++) {
            if (array_slice($words, $i, $length) === $sequence) {
                return $i;
            }
        }

        return null;
    }

    /**
     * Lowercase and collapse whitespace.
     */
    public function normalize(string $text): string
    {
        $text = Str::lower($text);
        $text = preg_replace('/[^\p{L}\p{N}%\s\.,!?\-]/u', ' ', $text) ?? $text;

        return trim(preg_replace('/\s+/u', ' ', $text) ?? $text);
    }

    /**
     * Split normalized text into words.
     *
     * @return list<string>
     */
    public function words(string $normalized): array
    {
        $clean = preg_replace('/[\.,!?]/u', ' ', $normalized) ?? $normalized;

        return array_values(array_filter(preg_split('/\s+/u', $clean) ?: [], fn ($word) => $word !== '' && $word !== '-'));
    }

    /**
     * Count occurrences of whole-word phrases.
     *
     * @param  list<string>  $phrases
     * @return array<string, int>
     */
    protected function countPhrases(string $normalized, array $phrases): array
    {
        $found = [];

        foreach ($phrases as $phrase) {
            $count = preg_match_all('/(?<![\p{L}\p{N}])'.preg_quote(Str::lower($phrase), '/').'(?![\p{L}\p{N}])/u', $normalized);
            if ($count > 0) {
                $found[$phrase] = $count;
            }
        }

        return $found;
    }

    /**
     * Count immediate word repetitions such as "saya saya".
     *
     * @param  list<string>  $words
     */
    protected function countRepetitions(array $words): int
    {
        $repetitions = 0;

        for ($i = 1, $total = count($words); $i < $total; $i++) {
            if ($words[$i] === $words[$i - 1] && mb_strlen($words[$i]) > 1 && ! is_numeric($words[$i])) {
                $repetitions++;
            }
        }

        return $repetitions;
    }

    /**
     * Remove stopwords and very short tokens.
     *
     * @param  list<string>  $words
     * @return list<string>
     */
    protected function contentWords(array $words): array
    {
        return array_values(array_filter($words, fn ($word) => mb_strlen($word) > 2 && ! in_array($word, self::STOPWORDS, true)));
    }

    /**
     * Estimate how relevant the answer is to the question (0-1).
     *
     * @param  list<string>  $answerContent
     * @param  list<string>  $domainTerms
     */
    protected function relevance(string $question, array $answerContent, array $domainTerms): float
    {
        if ($answerContent === []) {
            return 0.0;
        }

        $questionContent = array_unique($this->contentWords($this->words($this->normalize($question))));
        $answerSet = array_unique($answerContent);

        $overlap = 0;
        foreach ($questionContent as $word) {
            foreach ($answerSet as $answerWord) {
                // Share a 5-letter stem to tolerate Indonesian affixes (menggunakan / digunakan).
                if ($word === $answerWord || (mb_strlen($word) >= 5 && mb_strlen($answerWord) >= 5 && str_contains($answerWord, mb_substr($word, -5)))) {
                    $overlap++;
                    break;
                }
            }
        }

        $questionCoverage = count($questionContent) > 0 ? $overlap / count($questionContent) : 0.0;
        $domainSignal = min(1.0, count($domainTerms) / 3);

        return round(min(1.0, 0.35 + 0.4 * $questionCoverage + 0.4 * $domainSignal), 2);
    }

    /**
     * Pace contribution between -1 and +1.
     */
    protected function paceScore(?int $wpm): float
    {
        if ($wpm === null) {
            return 0.0;
        }

        if ($wpm >= self::PACE_MIN && $wpm <= self::PACE_MAX) {
            return 1.0;
        }

        $distance = $wpm < self::PACE_MIN ? self::PACE_MIN - $wpm : $wpm - self::PACE_MAX;

        return max(-1.0, 1.0 - $distance / 30);
    }
}
