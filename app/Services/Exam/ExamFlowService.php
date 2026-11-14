<?php

namespace App\Services\Exam;

use App\Models\PracticeSession;
use App\Models\SessionMessage;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

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

    public const TYPE_EXAM = 'exam';

    /** Free, time-boxed coaching conversation (no parts, no scripted questions). */
    public const TYPE_COACH = 'coach';

    /** Seconds before the end when the coach starts wrapping up. */
    public const COACH_WRAP_UP_SECONDS = 90;

    public function __construct(
        protected ExamBlueprint $blueprint,
        protected ExaminerService $examiner,
        protected SpeakingAnalyzer $analyzer,
    ) {}

    /**
     * Where the time of the last examiner reply went (see ExaminerService::lastTimings()).
     *
     * @return array<string, int>
     */
    public function lastTimings(): array
    {
        return $this->examiner->lastTimings();
    }

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
        // Spoken form: "VOIC-INTERVIEW" is read aloud as "VOIC Interview".
        $examinerName = ExaminerService::spokenName($definition['examiner']['name']);
        // "Halo Rifai dari Universitas Brawijaya, aku ...": the call starts with the candidate's own name.
        $candidate = $this->candidate($session);
        $who = $candidate ? ' '.$candidate['name'].($candidate['campus'] ? ($isEn ? ' from ' : ' dari ').$candidate['campus'] : '') : '';
        $partCount = count($parts);
        $minutes = max(3, $partCount * 4);

        // Spoken, in each examiner's own register (see ExaminerService::SPEAKING_STYLE).
        $greeting = $isEn
            ? match ($definition['role_type']) {
                'hrd' => "Hi{$who}, I'm {$examinerName}, your HR interviewer today. We'll chat for about {$minutes} minutes in {$partCount} ".($partCount === 1 ? 'part' : 'parts').'. Just relax and answer as naturally as you can.',
                'investor' => "Hi{$who}, I'm {$examinerName}, the investor hearing your pitch today. We have about {$minutes} minutes in {$partCount} ".($partCount === 1 ? 'part' : 'parts').'. Relax, just tell me about your startup.',
                default => "Welcome{$who}, I'm {$examinerName}, your thesis examiner today. This mock defense has {$partCount} ".($partCount === 1 ? 'part' : 'parts')." and takes about {$minutes} minutes. Take your time and answer in your own words.",
            }
        : match ($definition['role_type']) {
            'hrd' => "Halo{$who}, aku {$examinerName}, HRD yang bakal ngobrol sama kamu hari ini. Kita ngobrol sekitar {$minutes} menit, ada {$partCount} bagian. Santai aja ya, jawab senatural mungkin, anggap lagi ngobrol biasa.",
            'investor' => "Halo{$who}, saya {$examinerName}, investor yang bakal dengerin pitch kamu hari ini. Kita punya sekitar {$minutes} menit, dibagi {$partCount} bagian. Santai aja, anggap kita lagi ngopi bareng dan kamu cerita soal startup-mu.",
            default => "Halo{$who}, selamat datang. Saya {$examinerName}, dosen penguji kamu hari ini. Sidang simulasinya ada {$partCount} bagian, kira-kira {$minutes} menit. Tenang aja, jawab pakai bahasamu sendiri.",
        };

        $opening = trim($greeting.' '.$this->partIntro($state));

        $session->forceFill(['exam_state' => $state])->save();

        $audioUrl = $this->examiner->speak($opening, $definition['role_type'], $language);
        $message = $this->recordExaminerMessage($session, $opening, $audioUrl, $state, 'opening');
        $this->primeModel($session);

        return $this->payload($session->refresh(), $message);
    }

    /**
     * Start a coaching conversation: the coach greets and asks what to work on.
     *
     * @return array<string, mixed>
     */
    public function startCoach(PracticeSession $session, string $mode, string $language, ?string $topic): array
    {
        $state = [
            'session_type' => self::TYPE_COACH,
            'mode' => $mode,
            'language' => $language,
            'topic' => $topic,
            'parts' => [],
            'plan' => [],
            'part' => 1,
            'question_index' => 0,
            'followups_used' => 0,
            'phase' => self::PHASE_ANSWERING,
            'turn' => 0,
            'focus' => null,
            'started_at' => now()->timestamp,
            'limit_seconds' => max(60, (int) config('exam.coach_minutes', 10) * 60),
        ];

        $definition = $this->blueprint->mode($mode);
        $opening = $this->blueprint->text($definition['coach']['opening'] ?? '', $language);
        if ($candidate = $this->candidate($session)) {
            $who = $candidate['name'].($candidate['campus'] ? ($language === 'en' ? ' from ' : ' dari ').$candidate['campus'] : '');
            $opening = preg_replace('/^(Halo|Hi|Hello),/u', '$1 '.addcslashes($who, '\\$').',', $opening, 1) ?? $opening;
        }

        $session->forceFill(['exam_state' => $state])->save();

        $audioUrl = $this->examiner->speak($opening, $definition['role_type'], $language);
        $message = $this->recordExaminerMessage($session, $opening, $audioUrl, $state, 'opening');
        $this->primeModel($session);

        return $this->payload($session->refresh(), $message);
    }

    /**
     * Whether a session is a coaching conversation.
     *
     * @param  array<string, mixed>  $state
     */
    public static function isCoach(array $state): bool
    {
        return ($state['session_type'] ?? self::TYPE_EXAM) === self::TYPE_COACH;
    }

    /**
     * Seconds left in a coaching conversation (never negative).
     *
     * @param  array<string, mixed>  $state
     */
    public static function remainingSeconds(array $state): int
    {
        $elapsed = now()->timestamp - (int) ($state['started_at'] ?? now()->timestamp);

        return max(0, (int) ($state['limit_seconds'] ?? 600) - $elapsed);
    }

    /**
     * One coaching turn: react, teach one concrete thing, keep the conversation going.
     * An empty answer with $timeUp closes the session without recording a user turn.
     *
     * @param  array<string, mixed>|null  $facial
     * @return array<string, mixed>
     */
    protected function answerCoach(PracticeSession $session, string $answer, int $durationSeconds, ?array $facial, int $timestampSeconds, ?callable $onSentence, bool $timeUp): array
    {
        $state = $session->exam_state;
        $mode = $state['mode'];
        $question = $this->lastExaminerQuestion($session);

        if (trim($answer) !== '') {
            $session->messages()->create([
                'sender' => 'user',
                'message' => $answer,
                'facial_status' => $facial,
                'timestamp_seconds' => $timestampSeconds,
                'exam_part' => 1,
                'meta' => ['question' => $question, 'duration_seconds' => $durationSeconds, 'kind' => 'answer'],
            ]);
        }

        $remaining = self::remainingSeconds($state);
        $closing = $timeUp || $remaining <= 0;
        $state['turn'] = (int) $state['turn'] + 1;
        if ($closing) {
            $state['phase'] = self::PHASE_COMPLETED;
        }
        $directive = $closing ? ExaminerService::DIRECTIVE_COACH_CLOSE : ExaminerService::DIRECTIVE_COACH;

        $definition = $this->blueprint->mode($mode);
        $turn = [
            'session_type' => self::TYPE_COACH,
            'mode' => $mode,
            'role_type' => $definition['role_type'],
            'examiner_name' => $definition['examiner']['name'],
            'coach_title' => $this->blueprint->text($definition['coach']['title'] ?? '', $state['language']),
            'coach_skills' => $this->blueprint->text($definition['coach']['skills'] ?? '', $state['language']),
            'language' => $state['language'],
            'topic' => $state['topic'],
            'context_material' => $session->feedback_notes['context_material'] ?? null,
            'candidate' => $this->candidate($session),
            'part' => 1,
            'part_title' => $this->blueprint->text($definition['coach']['title'] ?? '', $state['language']),
            'directive' => $directive,
            'question' => $question,
            'answer' => $answer,
            'history' => $this->history($session, includeLast: trim($answer) === ''),
            'scripted' => '',
            'planned_question' => null,
            'transition' => null,
            'remaining_seconds' => $remaining,
            'wrap_up' => ! $closing && $remaining <= self::COACH_WRAP_UP_SECONDS,
            'turn' => (int) $state['turn'],
            'session_id' => $session->id,
            'focus' => null,
            'visual' => $facial,
        ];
        $this->rememberPrompt($session, $turn);
        $reply = $this->examiner->respond($turn, $onSentence);

        $session->forceFill(['exam_state' => $state])->save();

        $message = $this->recordExaminerMessage($session, $reply['text'], $reply['audio_url'], $state, $directive, $timestampSeconds + 1, $reply['engine'], $reply['reply'], 1);

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
    public function answer(PracticeSession $session, string $answer, int $durationSeconds, ?array $facial, int $timestampSeconds, ?callable $onSentence = null, bool $timeUp = false): array
    {
        $state = $session->exam_state;
        if (self::isCoach($state)) {
            return $this->answerCoach($session, $answer, $durationSeconds, $facial, $timestampSeconds, $onSentence, $timeUp);
        }

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
        $turn = [
            'mode' => $mode,
            'role_type' => $definition['role_type'],
            'examiner_name' => $definition['examiner']['name'],
            'language' => $state['language'],
            'topic' => $state['topic'],
            'context_material' => $session->feedback_notes['context_material'] ?? null,
            'candidate' => $this->candidate($session),
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
            'visual' => $facial,
        ];
        $this->rememberPrompt($session, $turn);
        $reply = $this->examiner->respond($turn, $onSentence);

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

        if (self::isCoach($state)) {
            $limit = (int) ($state['limit_seconds'] ?? 600);
            $remaining = self::remainingSeconds($state);

            return [
                'session_id' => $session->id,
                'session_type' => self::TYPE_COACH,
                'phase' => $state['phase'],
                'completed' => $state['phase'] === self::PHASE_COMPLETED,
                'part' => 1,
                'part_title' => $this->blueprint->text($this->blueprint->mode($mode)['coach']['title'] ?? '', $language),
                'part_subtitle' => '',
                'parts' => [],
                'cue_card' => null,
                'limit_seconds' => $limit,
                'remaining_seconds' => $remaining,
                'progress' => ['answered' => (int) $state['turn'], 'total' => 0, 'percent' => (int) round(($limit - $remaining) / max(1, $limit) * 100)],
                'examiner_message' => $message ? [
                    'id' => $message->id,
                    'text' => $message->message,
                    'audio_url' => $message->audio_url,
                    'engine' => $message->meta['engine'] ?? null,
                ] : null,
            ];
        }

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
            'session_type' => self::TYPE_EXAM,
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
                    : 'Oke, itu tadi pertanyaan terakhir. Makasih ya, sesi latihannya udah selesai. Rapormu udah siap.',
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

        $isHr = ($this->blueprint->mode($state['mode'])['role_type'] ?? '') === 'hrd';

        if ($part === 2) {
            $card = $this->blueprint->cueCard($state['mode'], (int) ($state['plan'][2] ?? 0), $state['language'], $state['topic']);

            return $isEn
                ? "Now let's move to Part 2, {$title}. I will give you a topic card. You have one minute to prepare and you can take notes, then speak for up to two minutes. I will not interrupt you. Your topic is: {$card['prompt']}"
                : ($isHr
                    ? "Oke, sekarang kita masuk Bagian 2, {$title}. Nanti aku kasih satu topik. Kamu punya semenit buat siap-siap, boleh sambil nyatat, terus ngomong sampai dua menit, aku nggak bakal motong. Topiknya: {$card['prompt']}"
                    : "Oke, sekarang kita masuk Bagian 2, {$title}. Saya kasih satu topik. Kamu punya semenit buat siap-siap, boleh sambil nyatat, terus ngomong sampai dua menit, saya nggak bakal motong. Topiknya: {$card['prompt']}");
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
    protected function history(PracticeSession $session, bool $includeLast = false): array
    {
        $history = $session->messages()
            ->orderBy('id')
            ->get(['sender', 'message', 'meta'])
            ->map(fn (SessionMessage $message) => array_filter([
                'role' => $message->sender === 'user' ? 'user' : 'assistant',
                'content' => $message->message,
                // The message exactly as the model saw it (answer + turn tag), see rememberPrompt().
                'prompt' => $message->sender === 'user' ? ($message->meta['prompt'] ?? null) : null,
            ], fn ($value) => $value !== null));

        // The answer just recorded is sent separately, unless no answer was recorded (time up).
        return ($includeLast ? $history : $history->slice(0, -1))->values()->all();
    }

    /**
     * Give the model a head start: read this session's persona, rules and opening line now, while
     * the candidate is still listening to the greeting (see ExaminerService::prime()).
     */
    protected function primeModel(PracticeSession $session): void
    {
        $state = $session->exam_state;
        $definition = $this->blueprint->mode($state['mode']);
        $coach = self::isCoach($state);

        $this->examiner->prime([
            'session_type' => $coach ? self::TYPE_COACH : null,
            'mode' => $state['mode'],
            'role_type' => $definition['role_type'],
            'examiner_name' => $definition['examiner']['name'],
            'coach_title' => $this->blueprint->text($definition['coach']['title'] ?? '', $state['language']),
            'coach_skills' => $this->blueprint->text($definition['coach']['skills'] ?? '', $state['language']),
            'language' => $state['language'],
            'topic' => $state['topic'],
            'context_material' => $session->feedback_notes['context_material'] ?? null,
            'candidate' => $this->candidate($session),
            'part' => (int) $state['part'],
            'part_title' => '',
            'focus' => $state['focus'] ?? null,
            'history' => $this->history($session, includeLast: true),
            'turn' => 0,
            'session_id' => $session->id,
        ]);
    }

    /**
     * Keep the candidate message exactly as it is sent to the model (answer + turn tag). The next
     * turns repeat it word for word in their history, so the prompt only ever grows at the end and
     * the model reuses its cache instead of re-reading the conversation (seconds per turn on a CPU).
     *
     * @param  array<string, mixed>  $turn
     */
    protected function rememberPrompt(PracticeSession $session, array $turn): void
    {
        if (trim((string) $turn['answer']) === '') {
            return;
        }

        $answer = $session->messages()->where('sender', 'user')->latest('id')->first();
        if ($answer !== null) {
            $answer->update(['meta' => array_merge($answer->meta ?? [], ['prompt' => $this->examiner->turnInstruction($turn)])]);
        }
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
    /**
     * Who is on the call: first name (guest name, or the account name) and campus. Null without a real name.
     *
     * @return array{name: string, campus: ?string}|null
     */
    public function candidate(PracticeSession $session): ?array
    {
        $notes = $session->feedback_notes ?? [];
        $name = trim((string) ($notes['guest_name'] ?? $session->user?->name ?? ''));
        if ($name === '' || in_array(mb_strtolower($name), ['pengunjung', 'pengunjung booth'], true)) {
            return null;
        }
        $campus = trim((string) ($notes['campus'] ?? ''));

        return ['name' => Str::limit(Str::of($name)->explode(' ')->first(), 30, ''), 'campus' => $campus !== '' ? Str::limit($campus, 80, '') : null];
    }

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
