<?php

namespace App\Console\Commands;

use App\Models\PracticeSession;
use App\Models\SessionMessage;
use App\Services\Exam\ConversationSignals;
use App\Services\Exam\ExamBlueprint;
use App\Services\Exam\ExaminerService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

#[Signature('voic:export-training-data
    {--seed=ai_service/datasets/seed/exam_seed_dialogues.json : Handwritten seed dialogues}
    {--manual=ai_service/datasets/kimi : Folder of *.json dialogues written in the seed format (e.g. by Kimi on kimi.com)}
    {--output=ai_service/datasets/sft/voic_exam_sft.jsonl : Training split (chat JSONL)}
    {--eval-output=ai_service/datasets/sft/voic_exam_eval.jsonl : Held-out evaluation split}
    {--eval-ratio=0.12 : Share of examples kept for evaluation}
    {--no-sessions : Only export the seed dialogues}')]
#[Description('Export examiner turns as chat-format SFT data, using exactly the prompt format used at inference time')]
class ExportExamTrainingData extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(ExaminerService $examiner, ExamBlueprint $blueprint): int
    {
        $examples = [];

        $seedPath = base_path((string) $this->option('seed'));
        if (File::exists($seedPath)) {
            foreach ($this->seedTurns($blueprint, $this->loadDialogues($seedPath)) as $turn) {
                $examples[] = $this->example($examiner, $turn, 'seed');
            }
        } else {
            $this->components->warn("Seed file not found: {$seedPath}");
        }
        $seedCount = count($examples);

        $manualDir = base_path((string) $this->option('manual'));
        $manualDialogues = [];
        foreach (File::isDirectory($manualDir) ? File::glob($manualDir.'/*.json') : [] as $file) {
            $manualDialogues = array_merge($manualDialogues, $this->loadDialogues($file));
        }
        foreach ($this->seedTurns($blueprint, $manualDialogues) as $turn) {
            $examples[] = $this->example($examiner, $turn, 'manual');
        }
        $manualCount = count($examples) - $seedCount;
        if ($manualDialogues !== []) {
            $this->line('  <fg=gray>→</> '.count($manualDialogues)." dialog manual dari {$this->option('manual')} ({$manualCount} contoh)");
        }

        if (! $this->option('no-sessions')) {
            foreach ($this->sessionTurns($blueprint) as [$turn, $source]) {
                $examples[] = $this->example($examiner, $turn, $source);
            }
        }

        if ($examples === []) {
            $this->components->warn('No training examples found.');

            return self::FAILURE;
        }

        $ratio = max(0.0, min(0.5, (float) $this->option('eval-ratio')));
        $train = [];
        $eval = [];
        foreach ($examples as $example) {
            // Deterministic split so re-exports keep the same eval set.
            $bucket = hexdec(substr(md5($example['messages'][count($example['messages']) - 2]['content']), 0, 6)) / 0xFFFFFF;
            if ($bucket < $ratio) {
                $eval[] = $example;
            } else {
                $train[] = $example;
            }
        }

        $this->write((string) $this->option('output'), $train);
        $this->write((string) $this->option('eval-output'), $eval);

        $this->components->info(sprintf(
            'Exported %d examples (%d seed, %d manual, %d from sessions): %d train, %d eval.',
            count($examples), $seedCount, $manualCount, count($examples) - $seedCount - $manualCount, count($train), count($eval)
        ));

        return self::SUCCESS;
    }

    /**
     * Read dialogues in the seed format from a JSON file, keeping only well-formed ones.
     * Tolerates what a chat model typically adds around the JSON (``` fences, a sentence before or after).
     *
     * @return list<array<string, mixed>>
     */
    protected function loadDialogues(string $path): array
    {
        $name = basename($path);
        $raw = trim((string) File::get($path));
        $raw = (string) preg_replace('/^ï»¿/', '', $raw);
        $data = json_decode($raw, true);
        if (! is_array($data)) {
            // Cut to the outermost JSON array/object, dropping fences and chatter.
            $start = strcspn($raw, '[{');
            $end = max((int) strrpos($raw, ']'), (int) strrpos($raw, '}'));
            $data = $end > $start ? json_decode(substr($raw, $start, $end - $start + 1), true) : null;
        }
        if (! is_array($data)) {
            $this->components->warn("{$name}: bukan JSON yang valid (".json_last_error_msg().'), dilewati.');

            return [];
        }
        if (isset($data['turns'])) {
            $data = [$data];
        }

        $modes = ['wawancara', 'sidang', 'pitching'];
        $directives = [ExaminerService::DIRECTIVE_REACT, ExaminerService::DIRECTIVE_FOLLOW_UP, ExaminerService::DIRECTIVE_CLARIFY, ExaminerService::DIRECTIVE_NEXT];
        $dialogues = [];
        foreach (array_values($data) as $number => $dialogue) {
            $label = "{$name} dialog #".($number + 1);
            if (! is_array($dialogue) || ! in_array($dialogue['mode'] ?? null, $modes, true) || ! is_array($dialogue['turns'] ?? null)) {
                $this->components->warn("{$label}: mode/turns tidak valid, dilewati.");

                continue;
            }
            $turns = [];
            foreach (array_values($dialogue['turns']) as $turn) {
                $ok = is_array($turn)
                    && in_array((int) ($turn['part'] ?? 0), [1, 2, 3], true)
                    && in_array($turn['directive'] ?? null, $directives, true);
                foreach (['question', 'answer', 'reply'] as $field) {
                    $ok = $ok && is_string($turn[$field] ?? null) && trim($turn[$field]) !== '';
                }
                if (! $ok) {
                    // Stop at the first broken turn: later turns would refer to a history that is no longer there.
                    $this->components->warn("{$label}: giliran ke-".(count($turns) + 1).' tidak lengkap, sisa dialog dipotong.');

                    break;
                }
                $turns[] = ['part' => (int) $turn['part']] + array_map(fn ($value) => is_string($value) ? trim($value) : $value, $turn);
            }
            if ($turns === []) {
                continue;
            }
            $dialogue['language'] = ($dialogue['language'] ?? 'id') === 'en' ? 'en' : 'id';
            $dialogue['turns'] = $turns;
            $dialogues[] = $dialogue;
        }

        return $dialogues;
    }

    /**
     * Expand seed-format dialogues into examiner turns with running history.
     *
     * @param  list<array<string, mixed>>  $dialogues
     * @return list<array<string, mixed>>
     */
    protected function seedTurns(ExamBlueprint $blueprint, array $dialogues): array
    {
        $turns = [];
        $signals = app(ConversationSignals::class);
        foreach ($dialogues as $dialogue) {
            $history = [];
            $previous = null;
            foreach ($dialogue['turns'] ?? [] as $index => $seedTurn) {
                // Rebuild the examiner line as the live flow says it: previous reply + scripted question.
                $examinerLine = $seedTurn['question'];
                if ($previous !== null && str_contains($previous['reply'], $seedTurn['question'])) {
                    $examinerLine = $previous['reply'];
                } elseif ($previous !== null && $previous['directive'] === ExaminerService::DIRECTIVE_REACT) {
                    $examinerLine = $previous['reply'].' '.$seedTurn['question'];
                }
                // A follow-up the seed never answered is left out, so the history stays coherent.

                $history[] = ['role' => 'assistant', 'content' => $examinerLine];

                // A reaction followed by the next question of the same part is exactly what the
                // live flow asks the model to write in one go (directive "next").
                $directive = $seedTurn['directive'];
                $target = $seedTurn['reply'];
                $planned = $seedTurn['planned_question'] ?? null;
                $following = $dialogue['turns'][$index + 1] ?? null;
                if ($directive === ExaminerService::DIRECTIVE_REACT && $following !== null && (int) $following['part'] === (int) $seedTurn['part'] && ! str_contains($seedTurn['reply'], $following['question'])) {
                    $directive = ExaminerService::DIRECTIVE_NEXT;
                    $planned = $following['question'];
                    $target = $seedTurn['reply'].' '.$following['question'];
                }

                $turn = $this->turn($blueprint, $dialogue['mode'], $dialogue['language'] ?? 'id', $dialogue['topic'] ?? null, $dialogue['context_material'] ?? null, (int) $seedTurn['part'], $directive, $examinerLine, $seedTurn['answer'], $history, $target, $index, 0, $planned);
                // Written dialogues (seed, Kimi) were not written against our FOKUS/KAITKAN hints. Keep a hint
                // only where the reply actually follows it, otherwise the model learns that the tags do not matter.
                $turn['skip_focus'] = ! $signals->followsFocus($turn, $target);
                $turn['skip_link'] = ! $signals->followsLink($turn, $target);
                $turns[] = $turn;

                // As at inference time: later turns see this answer exactly as it was sent (answer + turn tag).
                $history[] = ['role' => 'user', 'content' => $seedTurn['answer'], 'prompt' => app(ExaminerService::class)->turnInstruction($turn)];
                $previous = $seedTurn;
            }
        }

        return $turns;
    }

    /**
     * Collect examiner turns from real sessions that are good enough to learn from:
     * replies rated "good" by the candidate, and cloud-model replies (distillation, e.g. Kimi K3
     * as teacher) not rated "bad".
     *
     * @return list<array{0: array<string, mixed>, 1: string}>
     */
    protected function sessionTurns(ExamBlueprint $blueprint): array
    {
        $turns = [];

        PracticeSession::query()
            ->whereNotNull('exam_mode')
            ->with(['messages' => fn ($query) => $query->orderBy('id')])
            ->chunkById(100, function ($sessions) use (&$turns, $blueprint) {
                foreach ($sessions as $session) {
                    $state = $session->exam_state ?? [];
                    $messages = $session->messages->values();

                    foreach ($messages as $position => $message) {
                        $source = $this->sessionSource($message);
                        if ($source === null || $position < 2) {
                            continue;
                        }

                        $answer = $messages[$position - 1];
                        $question = $messages[$position - 2];
                        if ($answer->sender !== 'user' || $question->sender !== 'ai') {
                            continue;
                        }

                        $history = $messages->slice(0, $position - 1)
                            ->map(fn (SessionMessage $item) => array_filter(['role' => $item->sender === 'user' ? 'user' : 'assistant', 'content' => $item->message, 'prompt' => $item->sender === 'user' ? ($item->meta['prompt'] ?? null) : null], fn ($value) => $value !== null))
                            ->values()
                            ->all();

                        $coach = ($state['session_type'] ?? null) === 'coach';
                        $definition = $blueprint->mode($session->exam_mode);
                        $turns[] = [
                            ($coach ? [
                                'session_type' => 'coach',
                                'coach_title' => $blueprint->text($definition['coach']['title'] ?? '', $state['language'] ?? 'id'),
                                'coach_skills' => $blueprint->text($definition['coach']['skills'] ?? '', $state['language'] ?? 'id'),
                                'wrap_up' => false,
                            ] : []) + $this->turn(
                                $blueprint,
                                $session->exam_mode,
                                $state['language'] ?? 'id',
                                $state['topic'] ?? null,
                                $session->feedback_notes['context_material'] ?? null,
                                (int) ($message->meta['answered_part'] ?? $answer->exam_part ?? 1),
                                (string) $message->meta['kind'],
                                $question->message,
                                $answer->message,
                                $history,
                                (string) $message->meta['reply'],
                                $position,
                                $session->id,
                                $message->meta['planned_question'] ?? null,
                                $message->meta['transition'] ?? null,
                            ),
                            $source,
                        ];
                    }
                }
            });

        return $turns;
    }

    /**
     * Decide whether a stored examiner message is a usable target, and label its source.
     */
    protected function sessionSource(SessionMessage $message): ?string
    {
        $meta = $message->meta ?? [];

        if ($message->sender !== 'ai' || ! in_array($meta['kind'] ?? null, [ExaminerService::DIRECTIVE_REACT, ExaminerService::DIRECTIVE_FOLLOW_UP, ExaminerService::DIRECTIVE_NEXT, ExaminerService::DIRECTIVE_CLARIFY, ExaminerService::DIRECTIVE_COACH, ExaminerService::DIRECTIVE_COACH_CLOSE], true) || empty($meta['reply'])) {
            return null;
        }

        $rating = $meta['rating'] ?? null;

        return match (true) {
            $rating === 'bad' => null,
            $rating === 'good' => 'rated_good',
            ($meta['engine'] ?? null) === 'cloud_llm' => 'cloud_distill',
            in_array($meta['engine'] ?? null, ['ollama_cloud', 'openrouter'], true) => 'teacher_distill',
            default => null,
        };
    }

    /**
     * Build a turn array in the exact shape ExaminerService expects.
     *
     * @param  list<array{role: string, content: string}>  $history
     * @return array<string, mixed>
     */
    protected function turn(ExamBlueprint $blueprint, string $mode, string $language, ?string $topic, ?string $context, int $part, string $directive, string $question, string $answer, array $history, string $reply, int $index, int $sessionId = 0, ?string $plannedQuestion = null, ?string $transition = null): array
    {
        $definition = $blueprint->mode($mode);

        return [
            'mode' => $mode,
            'role_type' => $definition['role_type'],
            'examiner_name' => $definition['examiner']['name'],
            'language' => $language,
            'topic' => $topic,
            'context_material' => $context,
            'part' => $part,
            'part_title' => $blueprint->text($blueprint->part($mode, $part)['title'] ?? '', $language),
            'directive' => $directive,
            'question' => $question,
            'answer' => $answer,
            'history' => $history,
            'scripted' => '',
            'planned_question' => $plannedQuestion,
            'transition' => $transition,
            'turn' => $index,
            'session_id' => $sessionId,
            'target' => $reply,
        ];
    }

    /**
     * @param  array<string, mixed>  $turn
     * @return array{messages: list<array{role: string, content: string}>, meta: array<string, mixed>}
     */
    protected function example(ExaminerService $examiner, array $turn, string $source): array
    {
        $messages = $examiner->buildMessages($turn);
        $messages[] = ['role' => 'assistant', 'content' => $turn['target']];

        return [
            'messages' => $messages,
            'meta' => [
                'source' => $source,
                'mode' => $turn['mode'],
                'language' => $turn['language'],
                'directive' => $turn['directive'],
                'part' => $turn['part'],
                'answer' => $turn['answer'],
            ],
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    protected function write(string $relativePath, array $rows): void
    {
        $path = base_path($relativePath);
        File::ensureDirectoryExists(dirname($path));
        File::put($path, implode("\n", array_map(fn ($row) => json_encode($row, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), $rows)).($rows === [] ? '' : "\n"));
        $this->line("  <fg=gray>→</> {$relativePath} (".count($rows).')');
    }
}
