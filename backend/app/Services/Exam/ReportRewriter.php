<?php

namespace App\Services\Exam;

use Illuminate\Support\Str;

/**
 * Writing for the report with the cloud model, when the local AI service is not there to do it:
 * stronger versions of the weakest answers and the coaching notes. Never waits for the cloud quota,
 * so finishing an exam is never held up; null or empty results let the report fall back to its rules.
 */
class ReportRewriter
{
    public function __construct(protected ExaminerService $examiner) {}

    /**
     * How a stronger answer is built, per examiner. Same as IMPROVE_GUIDE in ai_service/exam_engine.py.
     *
     * @var array<string, array{id: string, en: string}>
     */
    public const IMPROVE_GUIDE = [
        'hrd' => ['id' => 'Gunakan pola STAR (situasi, tugas, aksi, hasil terukur) dan nada percaya diri tapi rendah hati.', 'en' => 'Use the STAR pattern (situation, task, action, measurable result) with a confident but humble tone.'],
        'dosen_penguji' => ['id' => 'Gunakan pola klaim, alasan, bukti/data, lalu batasan. Bahasa ilmiah yang lugas.', 'en' => 'Use claim, reason, evidence/data, then limitation. Clear academic language.'],
        'investor' => ['id' => 'Buka dengan angka atau insight paling kuat, lalu keunggulan dan langkah berikutnya. Padat dan meyakinkan.', 'en' => 'Lead with the strongest number or insight, then the edge and next step. Tight and convincing.'],
    ];

    /**
     * Rewrite a weak answer into a stronger spoken answer with the cloud model (the report uses this when the
     * local AI service is not running, e.g. a laptop without a GPU in "online" mode). Keeps the candidate's facts;
     * missing details become [placeholders]. Null when no cloud model answers.
     */
    public function improveWithCloud(string $question, string $answer, string $roleType, string $language): ?string
    {
        if (! ExaminerService::cloudConfigured() || ExaminerService::cloudPaused() || count(preg_split('/\s+/u', trim($answer)) ?: []) < 3) {
            return null;
        }

        $isEn = $language === 'en';
        $raw = $this->examiner->callCloudModel([
            ['role' => 'system', 'content' => $this->improvePrompt($roleType, $isEn)],
            ['role' => 'user', 'content' => ($isEn ? "Question: {$question}\nOriginal answer: " : "Pertanyaan: {$question}\nJawaban asli: ").$answer],
        ], timeout: 10, maxTokens: 320, temperature: 0.5, waitForQuota: false);

        return $this->tidyImproved((string) $raw);
    }

    /**
     * Rewrite several weak answers in ONE cloud request (one round trip instead of three, and the free
     * per-minute token quota pays for the instructions once). Returns key => rewrite for the answers it
     * managed; the caller rewrites the rest one by one.
     *
     * @param  array<int, array{question: string, answer: string}>  $items
     * @return array<int, string>|null null when no cloud model answered
     */
    public function improveManyWithCloud(array $items, string $roleType, string $language): ?array
    {
        $items = array_filter($items, fn ($item) => count(preg_split('/\s+/u', trim((string) $item['answer'])) ?: []) >= 3);
        if ($items === [] || ! ExaminerService::cloudConfigured() || ExaminerService::cloudPaused()) {
            return [];
        }

        $isEn = $language === 'en';
        $numbered = [];
        $blocks = [];
        foreach (array_values(array_keys($items)) as $i => $key) {
            $numbered[(string) ($i + 1)] = $key;
            $blocks[] = '['.($i + 1)."]\n".($isEn ? 'Question: ' : 'Pertanyaan: ').$items[$key]['question']."\n".($isEn ? 'Original answer: ' : 'Jawaban asli: ').$items[$key]['answer'];
        }
        $format = $isEn
            ? ' There are several numbered answers below. Rewrite each one separately and reply with JSON only, mapping each number to its rewrite: {"1": "...", "2": "..."}.'
            : ' Di bawah ada beberapa jawaban bernomor. Tulis ulang masing-masing secara terpisah dan balas HANYA dengan JSON yang memetakan nomor ke hasilnya: {"1": "...", "2": "..."}.';

        $raw = $this->examiner->callCloudModel([
            // The single-answer prompt ends with "reply with the rewritten answer only": here the reply is JSON.
            ['role' => 'system', 'content' => str_replace(['Reply with the rewritten answer only, in English.', 'Balas hanya dengan jawaban hasil tulis ulang, dalam Bahasa Indonesia.'], $isEn ? 'Write in English.' : 'Tulis dalam Bahasa Indonesia.', $this->improvePrompt($roleType, $isEn)).$format],
            ['role' => 'user', 'content' => implode("\n\n", $blocks)],
        ], timeout: 12, maxTokens: 180 * count($items), temperature: 0.5, waitForQuota: false);

        if ($raw === null) {
            return null; // No cloud answer at all (quota, network): the caller does not try each answer either.
        }

        $json = json_decode(trim(preg_replace('/^```(?:json)?|```$/m', '', $raw) ?? $raw), true);
        $result = [];
        foreach (is_array($json) ? $json : [] as $number => $text) {
            if (isset($numbered[(string) $number]) && is_string($text) && ($tidy = $this->tidyImproved($text)) !== null) {
                $result[$numbered[(string) $number]] = $tidy;
            }
        }

        return $result;
    }

    /**
     * Notes for the coaching report from the cloud model, when the local AI service cannot write them: what went
     * well, what to practise, sentences to reuse. Same format as /exam/coach-summary in ai_service/exam_engine.py.
     * Empty lists when no cloud model answers; never waits for the quota.
     *
     * @param  list<array{role: string, content: string}>  $transcript  role "user" or "coach"
     * @return array{strengths: list<string>, practice: list<string>, phrases: list<string>}
     */
    public function coachSummaryWithCloud(array $transcript, string $language): array
    {
        $empty = ['strengths' => [], 'practice' => [], 'phrases' => []];
        if ($transcript === [] || ! ExaminerService::cloudConfigured() || ExaminerService::cloudPaused()) {
            return $empty;
        }

        $isEn = $language === 'en';
        $convo = implode("\n", array_map(fn ($turn) => ($turn['role'] === 'user' ? 'PESERTA' : 'COACH').': '.Str::limit((string) $turn['content'], 600), array_slice($transcript, -30)));
        $instruction = $isEn
            ? "Summarise this coaching chat for the participant. Use exactly this format, max 3 lines each, short and specific to what they said:\nBAGUS:\n- ...\nLATIH:\n- ...\nKALIMAT:\n- (a better sentence they can reuse, built only from their own facts: no invented numbers, say \"a certain amount\" instead)\nWrite in English."
            : "Ringkas sesi coaching ini untuk peserta. Pakai format persis ini, maksimal 3 baris per bagian, singkat dan spesifik pada yang dia katakan:\nBAGUS:\n- ...\nLATIH:\n- ...\nKALIMAT:\n- (contoh kalimat lebih baik yang bisa dia pakai, hanya dari fakta yang dia sebut sendiri: jangan mengarang angka, pakai \"sekian\")\nTulis dalam Bahasa Indonesia, sapa peserta dengan 'kamu'.";

        $raw = $this->examiner->callCloudModel([
            ['role' => 'system', 'content' => $isEn ? 'You are a concise, honest communication coach.' : 'Kamu pelatih komunikasi yang ringkas dan jujur.'],
            ['role' => 'user', 'content' => $convo."\n\n".$instruction],
        ], timeout: 10, maxTokens: 400, temperature: 0.4, waitForQuota: false);
        if ($raw === null) {
            return $empty;
        }

        // "BAGUS:\n- a\n- b\nLATIH:\n- c" -> lists per heading.
        $sections = $empty;
        $current = null;
        foreach (preg_split('/\R/u', $raw) ?: [] as $line) {
            $line = trim(preg_replace('/[\*#_`]+/u', '', $line) ?? $line);
            if (preg_match('/^(BAGUS|LATIH|KALIMAT)\s*:\s*(.*)$/iu', $line, $heading)) {
                $current = ['BAGUS' => 'strengths', 'LATIH' => 'practice', 'KALIMAT' => 'phrases'][mb_strtoupper($heading[1])];
                $line = $heading[2];
            }
            $item = trim(preg_replace('/^[\-•\d\.\)\s]+/u', '', $line) ?? $line, ' "“”');
            if ($current !== null && $item !== '' && mb_strlen($item) <= 240 && count($sections[$current]) < 3) {
                $sections[$current][] = $item;
            }
        }

        return $sections;
    }

    /**
     * Instructions for rewriting an answer (same rules as /exam/improve in ai_service/exam_engine.py).
     */
    protected function improvePrompt(string $roleType, bool $isEn): string
    {
        $guide = (self::IMPROVE_GUIDE[$roleType] ?? self::IMPROVE_GUIDE['hrd'])[$isEn ? 'en' : 'id'];

        return $isEn
            ? "You are a speaking coach. Rewrite the candidate's spoken answer into a stronger answer they could say out loud: 3-4 natural sentences, at most about 70 words, in the first person. Keep every fact the candidate gave, do not invent numbers, names, experiences, projects or achievements they did not mention; where a detail is missing, use a short placeholder in square brackets (for example [your project]). Leave out fillers such as \"um\". {$guide} Never write labels such as \"Situation:\" or \"(Action)\", the pattern should be felt from the order of the sentences. If the question is not about a past experience (a plan, a motivation, an opinion), do not force the pattern: answer directly, give a reason and one example. Reply with the rewritten answer only, in English."
            : "Kamu adalah pelatih public speaking. Tulis ulang jawaban lisan peserta menjadi jawaban yang lebih kuat dan enak diucapkan: 3-4 kalimat lisan yang natural, paling banyak sekitar 70 kata, sebagai orang pertama (saya). Pertahankan semua fakta dari peserta, jangan mengarang angka, nama, pengalaman, proyek, atau pencapaian yang tidak dia sebut; jika ada detail yang kurang, pakai placeholder singkat dalam kurung siku (misalnya [contoh proyekmu]). Buang kata pengisi seperti \"eh\". {$guide} Jangan pernah menulis label seperti \"Situasi:\" atau \"(Aksi)\", polanya cukup terasa dari urutan kalimat. Kalau pertanyaannya bukan soal pengalaman (rencana, motivasi, pendapat), jangan paksakan pola itu: jawab langsung, beri alasan, dan satu contoh. Hindari kata kaku seperti \"menyadari bahwa\", \"mengenai\", \"tersebut\". Balas hanya dengan jawaban hasil tulis ulang, dalam Bahasa Indonesia.";
    }

    /**
     * A rewritten answer ready to show, or null when it is too short or too long to be one.
     */
    public function tidyImproved(string $raw): ?string
    {
        // Placeholders in [brackets] belong here, so not clean(), which removes them.
        $text = trim(trim(preg_replace('/\s+/u', ' ', preg_replace('/[\*#_`~]+/u', '', $raw) ?? '') ?? ''), '"“”');
        // A spoken answer has no "Situasi:" / "(Aksi)" labels, even when the model writes them anyway.
        $labels = 'Situasi|Tugas|Aksi|Tindakan|Hasil|Situation|Task|Action|Result';
        $text = preg_replace(['/\s*\((?:'.$labels.')\)/iu', '/(^|[.!?]\s+)(?:'.$labels.')\s*:\s*/iu'], ['', '$1'], $text) ?? $text;
        $words = count(preg_split('/\s+/u', $text) ?: []);

        return $text !== '' && $words >= 12 && $words <= 120 ? $text : null;
    }
}
