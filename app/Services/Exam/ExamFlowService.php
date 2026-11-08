<?php

namespace App\Services\Exam;

use App\Models\PracticeSession;
use App\Models\SessionMessage;
use Illuminate\Support\Facades\Cache;

/**
 * Drives a three-part mock exam turn by turn.
 *
 * State lives in practice_sessions.exam_state:
 *   mode, language, topic, parts (selected), plan, part, question_index,
 *   followups_used, phase (answering|cue_card|completed), turn.
 */
class ExamFlowService
{
    public const PHASE_ANSWERING = 'answering';

    public const PHASE_CUE_CARD = 'cue_card';

    public const PHASE_COMPLETED = 'completed';

    public function __construct(
        protected ExamBlueprint $blueprint,
        protected ExaminerService $examiner,
        protected SpeakingAnalyzer $analyzer,
    ) {}

    /**
     * Initialise the exam and record the examiner's opening line.
     *
     * @param  list<int>  $parts
     * @return array<string, mixed>
     */
    public function start(PracticeSession $session, string $mode, array $parts, string $language, ?string $topic, ?int $seed = null, ?string $focus = null): array
    {
        sort($parts);
        $parts = array_values(array_unique(array_filter($parts, fn ($part) => in_array($part, [1, 2, 3], true))));
        $parts = $parts === [] ? [1, 2, 3] : $parts;

        $state = [
            'mode' => $mode,
            'language' => $language,
            'topic' => $topic,
            'parts' => $parts,
            'plan' => $plan = $this->blueprint->buildPlan($mode, $parts, $seed, Cache::get("voic.exam.recent.{$mode}", [])),
            'part' => $parts[0],
            'question_index' => 0,
            'followups_used' => 0,
            'phase' => $parts[0] === 2 ? self::PHASE_CUE_CARD : self::PHASE_ANSWERING,
            'turn' => 0,
            'focus' => $focus,
        ];

        $definition = $this->blueprint->mode($mode);
        $isEn = $language === 'en';
        $examinerName = $definition['examiner']['name'];
        $examinerTitle = $this->blueprint->text($definition['examiner']['title'], $language);
        $partCount = count($parts);
        $minutes = max(3, $partCount * 4);

        $greeting = $isEn
            ? "Hi, I am {$examinerName}, {$examinerTitle}. This session has {$partCount} ".($partCount === 1 ? 'part' : 'parts')." and takes about {$minutes} minutes. Just relax and answer as naturally as you can."
            : "Halo, saya {$examinerName}, {$examinerTitle}. Sesi ini terdiri dari {$partCount} bagian dan berlangsung sekitar {$minutes} menit. Santai saja, jawab senatural mungkin, anggap kita sedang ngobrol.";

        $opening = trim($greeting.' '.$this->partIntro($state));

        $session->forceFill(['exam_state' => $state])->save();

        $audioUrl = $this->examiner->speak($opening, $definition['role_type'], $language);
        $message = $this->recordExaminerMessage($session, $opening, $audioUrl, $state, 'opening');

        return $this->payload($session->refresh(), $message);
    }

    /**
     * Remember which questions the last visitors got (about two sessions' worth per part).
     *
     * @param  array<int, list<int>|int>  $plan
     */
    protected function rememberRecent(string $mode, array $plan): void
    {
        $key = "voic.exam.recent.{$mode}";
        $recent = Cache::get($key, []);

        foreach ($plan as $part => $items) {
            $items = array_values(array_filter((array) $items, fn ($index) => $part === 2 || $index !== 0));
            $keep = $part === 2 ? 2 : 6;
            $recent[$part] = array_slice(array_values(array_unique([...$items, ...($recent[$part] ?? [])])), 0, $keep);
        }

        Cache::put($key, $recent, now()->addHours(12));
    }

    /**
     * Record a candidate answer and produce the examiner's next turn.
     *
     * @param  array<string, mixed>|null  $facial
     * @param  (callable(string, ?string): void)|null  $onSentence  Called with each examiner sentence as soon as it is ready.
     * @return array<string, mixed>
     */
    public function answer(PracticeSession $session, string $answer, int $durationSeconds, ?array $facial, int $timestampSeconds, ?callable $onSentence = null): array
    {
        $state = $session->exam_state;
        $mode = $state['mode'];
        $part = (int) $state['part'];
        $question = $this->lastExaminerQuestion($session);
        $isLongTurn = $state['phase'] === self::PHASE_CUE_CARD;

        $isClarification = ! $isLongTurn && $this->asksForClarification($answer);

        $session->messages()->create([
            'sender' => 'user',
            'message' => $answer,
            'facial_status' => $facial,
            'timestamp_seconds' => $timestampSeconds,
            'exam_part' => $part,
            'meta' => [
                'question' => $question,
                'duration_seconds' => $durationSeconds,
                // Clarification requests are conversation, not answers, so they are not scored.
                'kind' => $isLongTurn ? 'long_turn' : ($isClarification ? 'clarification' : 'answer'),
            ],
        ]);

        $state['turn'] = (int) $state['turn'] + 1;
        $partDefinition = $this->blueprint->part($mode, $part);
        $maxFollowups = (int) ($partDefinition['followups'] ?? 1);
        $step = ['scripted' => '', 'planned_question' => null, 'transition' => null];

        if ($isClarification) {
            // Explain and re-ask; the exam does not move on.
            $directive = ExaminerService::DIRECTIVE_CLARIFY;
        } elseif ($isLongTurn) {
            // After the uninterrupted talk, ask one rounding-off question.
            $state['phase'] = self::PHASE_ANSWERING;
            $state['followups_used'] = 1;
            $directive = ExaminerService::DIRECTIVE_FOLLOW_UP;
        } elseif ($this->shouldFollowUp($answer, $part, (int) $state['followups_used'], $maxFollowups, $mode)) {
            $state['followups_used'] = (int) $state['followups_used'] + 1;
            $directive = ExaminerService::DIRECTIVE_FOLLOW_UP;
        } else {
            $step = $this->advance($state);
            $directive = $step['directive'];
        }

        $definition = $this->blueprint->mode($mode);
        $reply = $this->examiner->respond([
            'mode' => $mode,
            'role_type' => $definition['role_type'],
            'examiner_name' => $definition['examiner']['name'],
            'language' => $state['language'],
            'topic' => $state['topic'],
            'context_material' => $session->feedback_notes['context_material'] ?? null,
            'part' => $part,
            'part_title' => $this->blueprint->text($partDefinition['title'] ?? '', $state['language']),
            'directive' => $directive,
            'question' => $question,
            'answer' => $answer,
            'history' => $this->history($session),
            'scripted' => $step['scripted'],
            'planned_question' => $step['planned_question'],
            'transition' => $step['transition'],
            'turn' => (int) $state['turn'],
            'session_id' => $session->id,
            'focus' => $state['focus'] ?? null,
        ], $onSentence);

        $session->forceFill(['exam_state' => $state])->save();

        $message = $this->recordExaminerMessage($session, $reply['text'], $reply['audio_url'], $state, $directive, $timestampSeconds + 1, $reply['engine'], $reply['reply'], $part, [
            'planned_question' => $step['planned_question'],
            'transition' => $step['transition'],
        ]);

        return $this->payload($session->refresh(), $message);
    }

    /**
     * Build the response payload the exam room UI consumes.
     *
     * @return array<string, mixed>
     */
    public function payload(PracticeSession $session, ?SessionMessage $message = null): array
    {
        $state = $session->exam_state;
        $mode = $state['mode'];
        $part = (int) $state['part'];
        $language = $state['language'];
        $partDefinition = $this->blueprint->part($mode, $part);

        $cueCard = null;
        if ($state['phase'] === self::PHASE_CUE_CARD) {
            $cueCard = $this->blueprint->cueCard($mode, (int) ($state['plan'][2] ?? 0), $language, $state['topic']) + [
                'prep_seconds' => (int) config('exam.prep_seconds', 60),
                'talk_seconds' => (int) config('exam.long_turn_seconds', 120),
            ];
        }

        $parts = array_map(fn (int $number) => [
            'number' => $number,
            'title' => $this->blueprint->text($this->blueprint->part($mode, $number)['title'] ?? '', $language),
            'status' => $state['phase'] === self::PHASE_COMPLETED || $number < $part ? 'done' : ($number === $part ? 'current' : 'upcoming'),
        ], $state['parts']);

        return [
            'session_id' => $session->id,
            'phase' => $state['phase'],
            'completed' => $state['phase'] === self::PHASE_COMPLETED,
            'part' => $part,
            'part_title' => $this->blueprint->text($partDefinition['title'] ?? '', $language),
            'part_subtitle' => $this->blueprint->text($partDefinition['subtitle'] ?? '', $language),
            'parts' => $parts,
            'cue_card' => $cueCard,
            'progress' => $this->progress($session),
            'examiner_message' => $message ? [
                'id' => $message->id,
                'text' => $message->message,
                'audio_url' => $message->audio_url,
                'engine' => $message->meta['engine'] ?? null,
            ] : null,
        ];
    }

    /**
     * Decide whether the examiner probes deeper before moving on.
     */
    protected function shouldFollowUp(string $answer, int $part, int $used, int $max, string $mode): bool
    {
        if ($used >= $max) {
            return false;
        }

        // An unsure candidate gets reassurance and a fresh question, not more pressure.
        if ($this->analyzer->soundsUnsure($answer)) {
            return false;
        }

        $wordCount = count(preg_split('/\s+/u', trim($answer)) ?: []);

        // Very short answers always earn a gentle "tell me more".
        if ($wordCount < 12) {
            return true;
        }

        // Part 3 is a real discussion: probe once on every topic.
        return $part === 3 && $used === 0;
    }

    /**
     * Move to the next planned question or part.
     *
     * Returns what the examiner should cover next. For a normal next question
     * the model asks it in its own words (directive "next"); the scripted
     * wording is only the fallback. Cue-card rules and the closing are fixed
     * announcements (directive "react").
     *
     * @param  array<string, mixed>  $state
     * @return array{directive: string, scripted: string, planned_question: ?string, transition: ?string}
     */
    protected function advance(array &$state): array
    {
        $part = (int) $state['part'];
        $state['followups_used'] = 0;

        if ($part !== 2) {
            $planned = $state['plan'][$part] ?? [];
            $nextIndex = (int) $state['question_index'] + 1;

            if ($nextIndex < count($planned)) {
                $state['question_index'] = $nextIndex;

                return [
                    'directive' => ExaminerService::DIRECTIVE_NEXT,
                    'scripted' => $this->bridge($state).' '.$this->currentQuestion($state),
                    'planned_question' => $this->currentQuestion($state),
                    'transition' => null,
                ];
            }
        }

        $position = array_search($part, $state['parts'], true);
        $nextPart = $state['parts'][$position + 1] ?? null;

        if ($nextPart === null) {
            $state['phase'] = self::PHASE_COMPLETED;

            return [
                'directive' => ExaminerService::DIRECTIVE_REACT,
                'scripted' => $state['language'] === 'en'
                    ? 'That brings us to the end of the session. Thank you, you did well to get through all of it. Your feedback report is ready.'
                    : 'Baik, itu tadi pertanyaan terakhir. Terima kasih, sesi latihan sudah selesai. Laporan evaluasi Anda sudah siap.',
                'planned_question' => null,
                'transition' => null,
            ];
        }

        $state['part'] = $nextPart;
        $state['question_index'] = 0;
        $state['phase'] = $nextPart === 2 ? self::PHASE_CUE_CARD : self::PHASE_ANSWERING;

        if ($nextPart === 2) {
            return ['directive' => ExaminerService::DIRECTIVE_REACT, 'scripted' => $this->partIntro($state), 'planned_question' => null, 'transition' => null];
        }

        $title = $this->blueprint->text($this->blueprint->part($state['mode'], $nextPart)['title'] ?? '', $state['language']);

        return [
            'directive' => ExaminerService::DIRECTIVE_NEXT,
            'scripted' => $this->partIntro($state),
            'planned_question' => $this->currentQuestion($state),
            'transition' => ($state['language'] === 'en' ? 'Part ' : 'Bagian ').$nextPart.', '.$title,
        ];
    }

    /**
     * Detect the candidate asking back ("maksudnya gimana?", "bisa diulang?").
     */
    protected function asksForClarification(string $answer): bool
    {
        $text = trim(mb_strtolower($answer));
        $wordCount = count(preg_split('/\s+/u', $text) ?: []);

        if (preg_match('/\b(maksudnya|maksud(nya)? (bapak|ibu|kakak|kak|prof|pak|anda)|bisa diulang|tolong diulang|ulangi (lagi )?pertanyaan|kurang paham|nggak paham|tidak paham|gak paham|belum paham|what do you mean|can you repeat|could you repeat|pardon|sorry\?|i don\'?t understand)\b/u', $text)) {
            return $wordCount <= 25;
        }

        // A short reply that is itself a question is usually a question back.
        return str_ends_with($text, '?') && $wordCount <= 12;
    }

    /**
     * Opening line for the current part.
     *
     * @param  array<string, mixed>  $state
     */
    protected function partIntro(array $state): string
    {
        $isEn = $state['language'] === 'en';
        $part = (int) $state['part'];
        $title = $this->blueprint->text($this->blueprint->part($state['mode'], $part)['title'] ?? '', $state['language']);

        $you = ($this->blueprint->mode($state['mode'])['role_type'] ?? '') === 'hrd' ? 'kamu' : 'Anda';

        if ($part === 2) {
            $card = $this->blueprint->cueCard($state['mode'], (int) ($state['plan'][2] ?? 0), $state['language'], $state['topic']);

            return $isEn
                ? "Now let's move to Part 2, {$title}. I will give you a topic card. You have one minute to prepare and you can take notes, then speak for up to two minutes. I will not interrupt you. Your topic is: {$card['prompt']}"
                : "Sekarang kita masuk ke Bagian 2, {$title}. Saya akan memberikan kartu topik. ".ucfirst($you)." punya satu menit untuk bersiap dan boleh membuat catatan, lalu bicara sampai dua menit tanpa saya sela. Topiknya: {$card['prompt']}";
        }

        $prefix = $isEn ? "Let's start Part {$part}, {$title}." : "Kita mulai Bagian {$part}, {$title}.";
        if ($part === 3) {
            $prefix = $isEn
                ? "Let's move on to Part 3, {$title}, where we will go a bit deeper."
                : "Sekarang kita lanjut ke Bagian 3, {$title}, di sini kita bahas lebih dalam.";
        }

        return $prefix.' '.$this->currentQuestion($state);
    }

    /**
     * Localized text of the current planned question.
     *
     * @param  array<string, mixed>  $state
     */
    protected function currentQuestion(array $state): string
    {
        $part = (int) $state['part'];
        $questionIndex = (int) (($state['plan'][$part] ?? [0])[$state['question_index']] ?? 0);

        return $this->blueprint->question($state['mode'], $part, $questionIndex, $state['language'], $state['topic']);
    }

    /**
     * Short varied transition before the next question.
     *
     * @param  array<string, mixed>  $state
     */
    protected function bridge(array $state): string
    {
        $options = $state['language'] === 'en'
            ? ['Next question.', 'Let me ask you something else.', 'Moving on.', 'Okay, next one.']
            : (($this->blueprint->mode($state['mode'])['role_type'] ?? '') === 'hrd'
                ? ['Lanjut ya.', 'Oke, pertanyaan berikutnya.', 'Nah, sekarang saya mau tanya hal lain.', 'Kita lanjut.']
                : ['Pertanyaan berikutnya.', 'Baik, kita lanjut.', 'Saya ingin bertanya hal lain.', 'Selanjutnya.']);

        return $options[(int) $state['turn'] % count($options)];
    }

    /**
     * Find the examiner's most recent message, used as the question being answered.
     */
    protected function lastExaminerQuestion(PracticeSession $session): string
    {
        return (string) $session->messages()->where('sender', 'ai')->latest('id')->value('message');
    }

    /**
     * Conversation history formatted for language models.
     *
     * @return list<array{role: string, content: string}>
     */
    protected function history(PracticeSession $session): array
    {
        return $session->messages()
            ->orderBy('id')
            ->get(['sender', 'message'])
            ->map(fn (SessionMessage $message) => [
                'role' => $message->sender === 'user' ? 'user' : 'assistant',
                'content' => $message->message,
            ])
            ->slice(0, -1) // the answer just recorded is sent separately
            ->values()
            ->all();
    }

    /**
     * Answered vs planned main questions.
     *
     * @return array{answered: int, total: int, percent: int}
     */
    protected function progress(PracticeSession $session): array
    {
        $state = $session->exam_state;
        $total = 0;
        foreach ($state['parts'] as $part) {
            $total += $part === 2 ? 1 : count($state['plan'][$part] ?? []);
        }

        $done = 0;
        foreach ($state['parts'] as $part) {
            if ($state['phase'] === self::PHASE_COMPLETED || $part < $state['part']) {
                $done += $part === 2 ? 1 : count($state['plan'][$part] ?? []);
            } elseif ($part === (int) $state['part']) {
                $done += $part === 2 ? ($state['phase'] === self::PHASE_CUE_CARD ? 0 : 1) : (int) $state['question_index'];
            }
        }

        return [
            'answered' => $done,
            'total' => max(1, $total),
            'percent' => (int) round($done / max(1, $total) * 100),
        ];
    }

    /**
     * Persist an examiner line.
     *
     * @param  array<string, mixed>  $state
     */
    /**
     * @param  array<string, mixed>  $state
     * @param  array<string, mixed>  $extra
     */
    protected function recordExaminerMessage(PracticeSession $session, string $text, ?string $audioUrl, array $state, string $kind, int $timestampSeconds = 0, ?string $engine = null, ?string $reply = null, ?int $answeredPart = null, array $extra = []): SessionMessage
    {
        return $session->messages()->create([
            'sender' => 'ai',
            'message' => $text,
            // Inline (data:) audio was already played from the stream and is too large to store.
            'audio_url' => $audioUrl !== null && ! str_starts_with($audioUrl, 'data:') ? $audioUrl : null,
            'timestamp_seconds' => $timestampSeconds,
            'exam_part' => (int) $state['part'],
            'meta' => array_filter([
                'kind' => $kind,
                'engine' => $engine,
                // The model-written part only (without the scripted question), used as a training target.
                'reply' => $reply,
                // The part the candidate was answering, which differs from exam_part after a transition.
                'answered_part' => $answeredPart,
            ] + $extra, fn ($value) => $value !== null),
        ]);
    }
}
