<?php

namespace App\Console\Commands;

use App\Models\PracticeSession;
use App\Models\SessionMessage;
use App\Services\Exam\ExamBlueprint;
use App\Services\Exam\ExaminerService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

#[Signature('voic:export-training-data
    {--seed=ai_service/datasets/seed/exam_seed_dialogues.json : Handwritten seed dialogues}
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

        foreach ($this->seedTurns($blueprint) as $turn) {
            $examples[] = $this->example($examiner, $turn, 'seed');
        }
        $seedCount = count($examples);

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
            'Exported %d examples (%d seed, %d from sessions): %d train, %d eval.',
            count($examples), $seedCount, count($examples) - $seedCount, count($train), count($eval)
        ));

        return self::SUCCESS;
    }

    /**
     * Expand the seed file into examiner turns with running history.
     *
     * @return list<array<string, mixed>>
     */
    protected function seedTurns(ExamBlueprint $blueprint): array
    {
        $path = base_path((string) $this->option('seed'));
        if (! File::exists($path)) {
            $this->components->warn("Seed file not found: {$path}");

            return [];
        }

        $turns = [];
        foreach ((array) json_decode(File::get($path), true) as $dialogue) {
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

                $turns[] = $this->turn($blueprint, $dialogue['mode'], $dialogue['language'] ?? 'id', $dialogue['topic'] ?? null, $dialogue['context_material'] ?? null, (int) $seedTurn['part'], $directive, $examinerLine, $seedTurn['answer'], $history, $target, $index, 0, $planned);

                $history[] = ['role' => 'user', 'content' => $seedTurn['answer']];
                $previous = $seedTurn;
            }
        }

        return $turns;
    }

    /**
     * Collect examiner turns from real sessions that are good enough to learn from:
     * replies rated "good" by the candidate, and cloud-model replies (distillation) not rated "bad".
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
                            ->map(fn (SessionMessage $item) => ['role' => $item->sender === 'user' ? 'user' : 'assistant', 'content' => $item->message])
                            ->values()
                            ->all();

                        $turns[] = [
                            $this->turn(
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

        if ($message->sender !== 'ai' || ! in_array($meta['kind'] ?? null, [ExaminerService::DIRECTIVE_REACT, ExaminerService::DIRECTIVE_FOLLOW_UP, ExaminerService::DIRECTIVE_NEXT, ExaminerService::DIRECTIVE_CLARIFY], true) || empty($meta['reply'])) {
            return null;
        }

        $rating = $meta['rating'] ?? null;

        return match (true) {
            $rating === 'bad' => null,
            $rating === 'good' => 'rated_good',
            ($meta['engine'] ?? null) === 'cloud_llm' => 'cloud_distill',
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
