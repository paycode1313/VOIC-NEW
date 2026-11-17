<?php

namespace App\Services\Study;

use App\Models\PracticeSession;
use App\Models\StudyAttempt;
use App\Models\StudySurvey;

/**
 * User trial ("uji coba pengguna"): participants practise with a code such as P01, every finished
 * exam is logged anonymously, and after the third exam they answer a short questionnaire.
 */
class UserStudy
{
    /** Exams before the questionnaire is shown. */
    public const ATTEMPTS_FOR_SURVEY = 3;

    /**
     * Likert questions (1 = sangat tidak setuju … 5 = sangat setuju).
     *
     * @var array<string, array{id: string, en: string}>
     */
    public const QUESTIONS = [
        'confidence' => ['id' => 'Saya merasa lebih percaya diri menghadapi ujian lisan setelah latihan ini.', 'en' => 'I feel more confident about oral exams after practising.'],
        'coherent' => ['id' => 'Pertanyaan dan tanggapan penguji AI nyambung dengan jawaban saya.', 'en' => "The AI examiner's questions and replies matched my answers."],
        'realistic' => ['id' => 'Latihannya terasa seperti menghadapi penguji sungguhan.', 'en' => 'It felt like facing a real examiner.'],
        'feedback' => ['id' => 'Rapor dan saran perbaikannya jelas dan berguna.', 'en' => 'The report and suggestions were clear and useful.'],
        'speed' => ['id' => 'Penguji AI menjawab cukup cepat, tidak membuat saya menunggu lama.', 'en' => 'The AI examiner answered quickly enough.'],
        'recommend' => ['id' => 'Saya akan memakai VOIC lagi atau merekomendasikannya ke teman.', 'en' => 'I would use VOIC again or recommend it to a friend.'],
    ];

    /**
     * "p 01" → "P01". Null when nothing usable is left.
     */
    public static function normalizeCode(?string $code): ?string
    {
        $code = substr((string) preg_replace('/[^A-Z0-9-]/', '', strtoupper((string) $code)), 0, 20);

        return $code === '' ? null : $code;
    }

    public static function codeOf(PracticeSession $session): ?string
    {
        return self::normalizeCode($session->feedback_notes['study_code'] ?? null);
    }

    /**
     * Log a finished exam of a participant. Idempotent per session (a re-finalized report updates the row).
     *
     * @param  array<string, mixed>  $report
     */
    public function record(PracticeSession $session, array $report): ?StudyAttempt
    {
        $code = self::codeOf($session);
        if ($code === null || ($report['stats']['answers'] ?? 0) === 0) {
            return null;
        }

        $stats = $report['stats'];
        $minutes = ($stats['speaking_seconds'] ?? 0) / 60;

        return StudyAttempt::updateOrCreate(['practice_session_id' => $session->id], [
            'code' => $code,
            'mode' => $report['mode'],
            'language' => $report['language'],
            'overall_band' => $report['overall_band'],
            'criteria' => array_map(fn ($criterion) => $criterion['band'], $report['criteria']),
            'answers' => $stats['answers'],
            'speaking_seconds' => (int) round($stats['speaking_seconds'] ?? 0),
            'fillers_per_minute' => $minutes >= 0.5 ? round(($stats['filler_count'] ?? 0) / $minutes, 2) : null,
            'average_wpm' => $stats['average_wpm'],
            'eye_contact' => $stats['eye_contact'],
        ]);
    }

    /**
     * Which exam of the participant this session was (1-based), or null outside the trial.
     */
    public function attemptNumber(PracticeSession $session): ?int
    {
        $code = self::codeOf($session);
        $attempt = $code ? StudyAttempt::where('practice_session_id', $session->id)->first() : null;

        return $attempt ? StudyAttempt::where('code', $code)->where('id', '<=', $attempt->id)->count() : null;
    }

    public function needsSurvey(PracticeSession $session): bool
    {
        $code = self::codeOf($session);

        return $code !== null
            && ($this->attemptNumber($session) ?? 0) >= self::ATTEMPTS_FOR_SURVEY
            && ! StudySurvey::where('code', $code)->exists();
    }

    /**
     * Per participant: first and third (or last, if more) exam, plus their questionnaire.
     *
     * @return list<array{code: string, attempts: int, first: StudyAttempt, last: StudyAttempt, survey: ?StudySurvey}>
     */
    public function participants(): array
    {
        $surveys = StudySurvey::all()->keyBy('code');

        return StudyAttempt::orderBy('id')->get()->groupBy('code')
            ->map(fn ($attempts, $code) => [
                'code' => (string) $code,
                'attempts' => $attempts->count(),
                'first' => $attempts->first(),
                'last' => $attempts->get(self::ATTEMPTS_FOR_SURVEY - 1) ?? $attempts->last(),
                'survey' => $surveys->get($code),
            ])
            ->values()
            ->all();
    }
}
