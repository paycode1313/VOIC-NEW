<?php

namespace App\Services\Exam;

use Illuminate\Support\Str;

/**
 * Reads the candidate's answer the way an experienced examiner listens, and turns it into one short
 * hint for the language model: what to dig into (FOKUS) when probing, or which earlier detail to tie
 * the next topic to (KAITKAN). This is what makes a follow-up feel pointed ("you said 'we' a lot,
 * what was your own part?") instead of generic ("can you tell me more?").
 *
 * Everything is derived from the turn data alone (answer + history), so a training example exported
 * later gets exactly the same hint as the live turn did.
 */
class ConversationSignals
{
    /** Hints are spoken to the model in a tag, keep them short. */
    protected const MAX_HINT_LENGTH = 90;

    /** Units that change naturally between stories, so a different number is not a contradiction. */
    protected const UNSTABLE_UNITS = [
        'tahun', 'bulan', 'minggu', 'hari', 'jam', 'menit', 'detik', 'kali', 'persen', 'kak', 'pak', 'bu',
        'years', 'year', 'months', 'month', 'weeks', 'week', 'days', 'day', 'hours', 'hour', 'minutes', 'times', 'percent',
        'yang', 'dan', 'atau', 'dari', 'ke', 'di', 'untuk', 'the', 'and', 'of', 'to', 'in',
    ];

    /** Words that only scale the number before the noun ("50 juta pengguna"). */
    protected const MULTIPLIERS = ['juta', 'ribu', 'miliar', 'triliun', 'rb', 'jt', 'million', 'thousand', 'billion', 'k'];

    public function __construct(protected SpeakingAnalyzer $analyzer) {}

    /**
     * What the examiner should dig into on a follow-up, or null when the answer has no clear gap.
     *
     * @param  array<string, mixed>  $turn
     */
    public function probeFocus(array $turn): ?string
    {
        $gap = $this->gap($turn);

        return $gap === null ? null : Str::limit($gap[1], self::MAX_HINT_LENGTH, '');
    }

    /**
     * The most important gap in the answer as [kind, hint], e.g. ['we', 'peran pribadinya belum jelas'].
     * Kinds: contradiction, we, trait, result, competitors, no_number, broad_market, funds,
     * comparison, method_reason, sample, overgeneral, short, no_example.
     *
     * @param  array<string, mixed>  $turn
     * @return array{0: string, 1: string}|null
     */
    public function gap(array $turn): ?array
    {
        $answer = trim((string) ($turn['answer'] ?? ''));
        if ($answer === '' || $this->analyzer->soundsUnsure($answer)) {
            return null;
        }

        $isEn = ($turn['language'] ?? 'id') === 'en';
        $text = $this->analyzer->normalize($answer);
        $wordCount = count($this->analyzer->words($text));

        return $this->contradiction($turn, $isEn)
            ?? match ($turn['role_type'] ?? '') {
                'investor' => $this->investorGap($text, $isEn),
                'dosen_penguji' => $this->examinerGap($text, $isEn, $wordCount),
                default => $this->recruiterGap($text, $isEn, $wordCount),
            }
        ?? $this->genericGap($text, $isEn, $wordCount, (int) ($turn['part'] ?? 1));
    }

    /**
     * An earlier detail from the candidate that the next planned topic can build on, or null.
     *
     * @param  array<string, mixed>  $turn
     */
    public function link(array $turn): ?string
    {
        $planned = trim((string) ($turn['planned_question'] ?? ''));
        if ($planned === '') {
            return null;
        }

        $earlier = array_values(array_filter($turn['history'] ?? [], fn ($item) => ($item['role'] ?? '') === 'user'));
        if ($earlier === []) {
            return null;
        }

        $topicStems = $this->stems($this->analyzer->normalize($planned));
        if ($topicStems === []) {
            return null;
        }

        $best = null;
        $bestScore = 0;
        foreach ($earlier as $item) {
            foreach ($this->clauses((string) $item['content']) as $clause) {
                // Only details that share a word stem with the next topic: a forced link sounds robotic.
                $shared = $this->sharedStems($this->stems($this->analyzer->normalize($clause)), $topicStems);
                // Later answers win a tie: they are fresher in the candidate's mind.
                if (count($shared) > 0 && count($shared) >= $bestScore) {
                    $best = $this->excerpt($clause, $shared[0]);
                    $bestScore = count($shared);
                }
            }
        }

        return $best === null ? null : Str::limit($best, 70, '');
    }

    /**
     * The clauses of a spoken answer, with the speaker's own pronoun at the start removed.
     *
     * @return list<string>
     */
    protected function clauses(string $text): array
    {
        $parts = preg_split('/[.;!?]+|,\s+|\s+(?:dan|lalu|terus|tapi|tetapi|karena|sehingga|and|then|but|because)\s+/iu', $text) ?: [];
        $clauses = [];
        foreach ($parts as $part) {
            $part = trim((string) preg_replace('/^(?:jadi|nah|eh|ehm|oke|ya|saya|aku|kami|gue|i|we)\s+/iu', '', trim($part)));
            if (count(preg_split('/\s+/u', $part) ?: []) >= 3) {
                $clauses[] = $part;
            }
        }

        return $clauses;
    }

    /**
     * At most nine words of a clause, kept around the word that links it to the next topic.
     */
    protected function excerpt(string $clause, string $stem): string
    {
        $words = preg_split('/\s+/u', trim($clause)) ?: [];
        if (count($words) > 9) {
            $anchor = 0;
            foreach ($words as $index => $word) {
                if (str_starts_with(mb_strtolower($word), $stem)) {
                    $anchor = $index;
                    break;
                }
            }
            $words = array_slice($words, max(0, min($anchor - 2, count($words) - 9)), 9);
        }

        // A window can end on a dangling word ("... 40 toko retail di"); drop it.
        $dangling = ['di', 'ke', 'dari', 'untuk', 'yang', 'dan', 'dengan', 'pada', 'atau', 'the', 'of', 'in', 'at', 'for', 'and', 'with', 'to'];
        while (count($words) > 3 && in_array(mb_strtolower((string) end($words)), $dangling, true)) {
            array_pop($words);
        }

        return implode(' ', $words);
    }

    /**
     * A number the candidate gives now for the same thing they counted differently before.
     *
     * @param  array<string, mixed>  $turn
     */
    protected function contradiction(array $turn, bool $isEn): ?array
    {
        $now = $this->quantities((string) $turn['answer']);
        if ($now === []) {
            return null;
        }

        // Change words ("naik dari 10 jadi 40") mean the candidate is describing growth, not contradicting.
        if (preg_match('/\b(naik|turun|tumbuh|bertambah|berkurang|menjadi|jadi|awalnya|sekarang|kini|grew|increased|dropped|from|now|became)\b/u', $this->analyzer->normalize((string) $turn['answer']))) {
            return null;
        }

        $before = [];
        foreach ($turn['history'] ?? [] as $item) {
            if (($item['role'] ?? '') !== 'user') {
                continue;
            }
            foreach ($this->quantities((string) $item['content']) as $noun => $values) {
                $before[$noun] = array_values(array_unique([...($before[$noun] ?? []), ...$values]));
            }
        }

        foreach ($now as $noun => $values) {
            if (count($values) !== 1 || count($before[$noun] ?? []) !== 1 || $before[$noun][0] === $values[0]) {
                continue;
            }

            return ['contradiction', $isEn
                ? "check consistency: earlier \"{$before[$noun][0]} {$noun}\", now \"{$values[0]} {$noun}\""
                : "cek konsistensi: tadi \"{$before[$noun][0]} {$noun}\", sekarang \"{$values[0]} {$noun}\""];
        }

        return null;
    }

    /**
     * Number + noun pairs in a text, e.g. ["toko" => ["40"], "pengguna" => ["50 juta"]].
     *
     * @return array<string, list<string>>
     */
    public function quantities(string $text): array
    {
        $normalized = $this->analyzer->normalize($text);
        $multipliers = implode('|', self::MULTIPLIERS);
        preg_match_all('/(?<![\p{L}\p{N}.,])(\d+(?:[.,]\d+)?)(?:\s*('.$multipliers.'))?\s+(\p{L}{3,})/u', $normalized, $matches, PREG_SET_ORDER);

        $found = [];
        foreach ($matches as $match) {
            $noun = $match[3];
            if (in_array($noun, self::UNSTABLE_UNITS, true) || in_array($noun, self::MULTIPLIERS, true)) {
                continue;
            }
            $value = trim($match[1].' '.($match[2] ?? ''));
            $found[$noun] = array_values(array_unique([...($found[$noun] ?? []), $value]));
        }

        return $found;
    }

    /**
     * HR recruiter: STAR completeness, personal contribution, and claims about oneself.
     */
    protected function recruiterGap(string $text, bool $isEn, int $wordCount): ?array
    {
        $we = preg_match_all('/\b(kami|tim kami|we|our team)\b/u', $text);
        $me = preg_match_all('/\b(saya|aku|gue|i|my)\b/u', $text);
        if ($wordCount >= 15 && $we >= 2 && $we > $me) {
            return ['we', $isEn ? 'their own role is unclear (lots of "we")' : 'peran pribadinya belum jelas (banyak "kami")'];
        }

        if (preg_match('/\b(pekerja keras|disiplin|teliti|sabar|jujur|kreatif|inisiatif|proaktif|mandiri|tekun|ulet|rajin|cepat belajar|bertanggung jawab|perfeksionis|mudah beradaptasi|komunikatif|hard ?working|fast learner|team player|detail oriented|perfectionist|patient|creative|proactive|independent)\b/u', $text, $claim)
            && ! $this->hasExample($text)) {
            return ['trait', $isEn ? "claims \"{$claim[1]}\" without a real example" : "klaim \"{$claim[1]}\" tanpa kejadian nyata"];
        }

        // Not "dulu": in "ngobrol dulu" it means "first", not "back then".
        $isStory = (bool) preg_match('/\b(waktu itu|saat itu|pernah|ketika|when i|once|back then)\b/u', $text);
        if ($isStory && $wordCount >= 20 && ! preg_match('/\b(hasil\w*|akhirnya|berhasil|sehingga|dampak\w*|selesai|tercapai|result\w*|outcome|in the end|finally|succeeded)\b/u', $text)) {
            return ['result', $isEn ? 'the result of the story is missing' : 'hasil akhir ceritanya belum disebut'];
        }

        return null;
    }

    /**
     * Venture investor: numbers behind claims, competition, and optimistic assumptions.
     */
    protected function investorGap(string $text, bool $isEn): ?array
    {
        if (preg_match('/\b(tidak ada|belum ada|nggak ada|gak ada|tanpa) (pesaing|kompetitor|kompetisi)|\bno (competitors?|competition)\b/u', $text)) {
            return ['competitors', $isEn ? 'claims no competitors (ask what customers use today)' : 'klaim tanpa pesaing (tanyakan apa yang dipakai pelanggan sekarang)'];
        }

        $hasNumbers = (bool) preg_match('/\d|persen|juta|miliar|ribu|percent|million|billion|thousand/u', $text);
        if (! $hasNumbers && preg_match('/\b(pesat|sangat besar|besar sekali|banyak sekali|potensial|meledak|viral|menjanjikan|huge|massive|fast[- ]growing|explosive|promising)\b/u', $text, $claim)) {
            return ['no_number', $isEn ? "says \"{$claim[1]}\" without a number" : "bilang \"{$claim[1]}\" tanpa angka"];
        }

        if (preg_match('/\b(semua orang|setiap orang|seluruh indonesia|semua umkm|everyone|every business)\b/u', $text, $claim)) {
            return ['broad_market', $isEn ? "market \"{$claim[1]}\" is too broad (ask for the first segment)" : "pasar \"{$claim[1]}\" terlalu luas (tanyakan segmen pertama)"];
        }

        // "untuk 10 persen saham" is the deal, not the use of the money: only real spending words count.
        $spending = '/\b(dipakai|digunakan|dialokasikan|alokasi\w*|untuk (?:biaya|membangun|membuka|merekrut|rekrut|ekspansi|pemasaran|marketing|operasional|pengembangan|gudang|tim|produk|riset|hiring)|use of funds|to (?:hire|build|expand|grow|fund)|spend\w*|allocat\w*)\b/u';
        if (preg_match('/\b(pendanaan|investasi|dana|funding|raise|raising)\b/u', $text) && $hasNumbers && ! preg_match($spending, $text)) {
            return ['funds', $isEn ? 'the use of funds is not explained' : 'penggunaan dana belum dijelaskan'];
        }

        return null;
    }

    /**
     * Thesis examiner: comparisons, method justification, data size, and over-general claims.
     */
    protected function examinerGap(string $text, bool $isEn, int $wordCount): ?array
    {
        if (preg_match('/\b(lebih (?:baik|akurat|efektif|efisien|cepat|unggul|optimal)|better|more accurate|more effective|outperform\w*)\b/u', $text, $claim)
            && ! preg_match('/\b(dibanding\w*|daripada|ketimbang|than|compared)\b/u', $text)) {
            return ['comparison', $isEn ? "\"{$claim[1]}\" without saying compared to what" : "klaim \"{$claim[1]}\" tanpa pembanding"];
        }

        // Only when a method is actually being chosen ("saya menggunakan metode ..."), not any mention of "model".
        if (preg_match('/\b(metode|algoritma|model|pendekatan|teknik|method|algorithm|approach)\b/u', $text)
            && preg_match('/\b(menggunakan|memakai|pakai|memilih|dipilih|menerapkan|use|using|used|chose|applied)\b/u', $text)
            && ! preg_match('/\b(karena|sebab|alasan\w*|agar|supaya|because|since|so that)\b/u', $text) && $wordCount >= 8) {
            return ['method_reason', $isEn ? 'the reason for choosing the method is missing' : 'alasan memilih metode belum dijelaskan'];
        }

        if (preg_match('/\b(data\w*|sampel\w*|responden\w*|populasi\w*|samples?|respondents?)\b/u', $text) && ! preg_match('/\d|ratus|ribu|puluh|hundred|thousand/u', $text)) {
            return ['sample', $isEn ? 'the amount of data or sample is not stated' : 'jumlah data atau sampel belum disebut'];
        }

        if (preg_match('/\b(pasti|selalu|semua kasus|seluruh|terbukti|always|proves?|all cases)\b/u', $text, $claim)) {
            return ['overgeneral', $isEn ? "over-general claim (\"{$claim[1]}\"), ask for limitations" : "klaim terlalu umum (\"{$claim[1]}\"), tanyakan keterbatasannya"];
        }

        return null;
    }

    /**
     * Gaps every examiner notices.
     */
    protected function genericGap(string $text, bool $isEn, int $wordCount, int $part): ?array
    {
        if ($wordCount < 12) {
            return ['short', $isEn ? 'the answer is still short, invite a concrete detail' : 'jawaban masih singkat, minta satu detail konkret'];
        }

        if ($part !== 1 && ! $this->hasExample($text) && ! preg_match('/\d/u', $text)) {
            return ['no_example', $isEn ? 'no concrete example or number yet' : 'belum ada contoh konkret atau angka'];
        }

        return null;
    }

    protected function hasExample(string $text): bool
    {
        return (bool) preg_match('/\b(contoh\w*|misal\w*|seperti saat|waktu itu|saat itu|pernah|ketika|for example|for instance|once|when i)\b/u', $text);
    }

    /**
     * Word stems (at most five letters) without stopwords, tolerant to Indonesian suffixes in comparisons.
     * Four-letter words count too, so short key terms like "data", "toko" or "dana" can link.
     *
     * @return list<string>
     */
    protected function stems(string $normalized): array
    {
        $stop = [
            'yang', 'dengan', 'untuk', 'dalam', 'tentang', 'bagaimana', 'menurut', 'kenapa', 'mengapa', 'apakah', 'kalau', 'anda', 'kamu',
            'saya', 'kami', 'kita', 'akan', 'bisa', 'juga', 'lagi', 'baru', 'cara', 'saat', 'hari', 'kali', 'sama', 'lain', 'para', 'atau',
            'oleh', 'pada', 'dari', 'ingin', 'pernah', 'sudah', 'belum', 'tidak', 'bukan', 'masih', 'seperti', 'karena', 'tersebut', 'menjadi',
            'what', 'would', 'about', 'your', 'have', 'that', 'this', 'with', 'from', 'they', 'were', 'been', 'when', 'which', 'there',
        ];
        $stems = [];
        foreach ($this->analyzer->words($normalized) as $word) {
            if (mb_strlen($word) >= 4 && ! in_array($word, $stop, true) && ! preg_match('/^\d/u', $word)) {
                $stems[] = mb_substr($word, 0, 5);
            }
        }

        return array_values(array_unique($stems));
    }

    /**
     * Stems of $clause that match a topic stem, where a short stem matches a longer one it starts
     * ("data" matches "datan" from "datanya").
     *
     * @param  list<string>  $clause
     * @param  list<string>  $topic
     * @return list<string>
     */
    protected function sharedStems(array $clause, array $topic): array
    {
        return array_values(array_filter($clause, function (string $stem) use ($topic): bool {
            foreach ($topic as $other) {
                if (str_starts_with($stem, $other) || str_starts_with($other, $stem)) {
                    return true;
                }
            }

            return false;
        }));
    }
}
