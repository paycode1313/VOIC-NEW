<?php

namespace App\Services\Exam;

use Illuminate\Support\Arr;
use InvalidArgumentException;
use Random\Engine\Mt19937;
use Random\Randomizer;

/**
 * Read-only access to the mock exam definitions in config/exam.php.
 */
class ExamBlueprint
{
    /**
     * Get every configured mode keyed by slug.
     *
     * @return array<string, array<string, mixed>>
     */
    public function modes(): array
    {
        return (array) config('exam.modes', []);
    }

    /**
     * Get the slugs of all modes.
     *
     * @return list<string>
     */
    public function modeKeys(): array
    {
        return array_keys($this->modes());
    }

    /**
     * Determine whether the given mode slug exists.
     */
    public function hasMode(string $mode): bool
    {
        return array_key_exists($mode, $this->modes());
    }

    /**
     * Map a legacy AI role type (dosen_penguji, hrd, investor) to an exam mode slug.
     */
    public function modeForRoleType(?string $roleType): ?string
    {
        foreach ($this->modes() as $key => $mode) {
            if ($roleType !== null && ($mode['role_type'] ?? null) === $roleType) {
                return $key;
            }
        }

        return null;
    }

    /**
     * Get a single mode definition.
     *
     * @return array<string, mixed>
     */
    public function mode(string $mode): array
    {
        if (! $this->hasMode($mode)) {
            throw new InvalidArgumentException("Unknown exam mode [{$mode}].");
        }

        return $this->modes()[$mode];
    }

    /**
     * Get the definition of one part of a mode.
     *
     * @return array<string, mixed>
     */
    public function part(string $mode, int $part): array
    {
        return (array) Arr::get($this->mode($mode), "parts.{$part}", []);
    }

    /**
     * Resolve a bilingual config value (an array keyed by language) to plain text.
     *
     * @param  array<string, mixed>|string|null  $value
     */
    public function text(array|string|null $value, string $language, ?string $topic = null): string
    {
        if (is_array($value)) {
            $value = $value[$language] ?? $value['id'] ?? reset($value) ?: '';
        }

        $value = (string) $value;
        $fallbackTopic = $language === 'en' ? 'this' : 'ini';

        return trim(str_replace(':topic', $topic !== null && $topic !== '' ? $topic : $fallbackTopic, $value));
    }

    /**
     * Pick which questions and cue card a new session will use.
     *
     * @param  list<int>  $parts
     * @return array{1?: list<int>, 2?: int, 3?: list<int>}
     */
    public function buildPlan(string $mode, array $parts, ?int $seed = null): array
    {
        $plan = [];
        $random = $seed !== null ? new Randomizer(new Mt19937($seed)) : new Randomizer;

        foreach ($parts as $part) {
            $definition = $this->part($mode, $part);

            if ($part === 2) {
                $cardCount = count($definition['cue_cards'] ?? []);
                $plan[2] = $cardCount > 0 ? $random->getInt(0, $cardCount - 1) : 0;

                continue;
            }

            $questionCount = count($definition['questions'] ?? []);
            $take = min((int) ($definition['question_count'] ?? 3), $questionCount);

            // The first question of each part is the natural opener, keep it first.
            $rest = range(1, max(1, $questionCount - 1));
            $rest = $questionCount > 1 ? $random->shuffleArray($rest) : [];
            $picked = array_merge([0], array_slice($rest, 0, $take - 1));

            $plan[$part] = array_values($picked);
        }

        return $plan;
    }

    /**
     * Get the localized question text for a part and question index.
     */
    public function question(string $mode, int $part, int $index, string $language, ?string $topic = null): string
    {
        $question = Arr::get($this->part($mode, $part), "questions.{$index}");

        return $this->text($question, $language, $topic);
    }

    /**
     * Get the localized cue card for Part 2.
     *
     * @return array{prompt: string, points: list<string>}
     */
    public function cueCard(string $mode, int $index, string $language, ?string $topic = null): array
    {
        $card = (array) Arr::get($this->part($mode, 2), "cue_cards.{$index}", []);
        $points = $card['points'][$language] ?? $card['points']['id'] ?? [];

        return [
            'prompt' => $this->text($card['prompt'] ?? '', $language, $topic),
            'points' => array_values(array_map(fn ($point) => (string) $point, $points)),
        ];
    }
}
