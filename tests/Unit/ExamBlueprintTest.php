<?php

namespace Tests\Unit;

use App\Services\Exam\ExamBlueprint;
use Tests\TestCase;

class ExamBlueprintTest extends TestCase
{
    public function test_consecutive_visitors_get_different_questions(): void
    {
        $blueprint = new ExamBlueprint;
        $first = $blueprint->buildPlan('wawancara', [1, 2, 3], seed: 1);

        $recent = [
            1 => array_slice($first[1], 1),
            2 => [$first[2]],
            3 => array_slice($first[3], 1),
        ];
        $second = $blueprint->buildPlan('wawancara', [1, 2, 3], seed: 2, recent: $recent);

        $this->assertSame(0, $second[1][0], 'The opener stays first.');
        $this->assertEmpty(array_intersect(array_slice($second[1], 1), $recent[1]));
        $this->assertEmpty(array_intersect(array_slice($second[3], 1), $recent[3]));
        $this->assertNotSame($first[2], $second[2]);
    }

    public function test_every_mode_has_a_deep_enough_bank(): void
    {
        $blueprint = new ExamBlueprint;

        foreach ($blueprint->modes() as $key => $mode) {
            $this->assertGreaterThanOrEqual(10, count($mode['parts'][1]['questions']), "{$key} part 1");
            $this->assertGreaterThanOrEqual(10, count($mode['parts'][3]['questions']), "{$key} part 3");
            $this->assertGreaterThanOrEqual(4, count($mode['parts'][2]['cue_cards']), "{$key} cue cards");

            foreach ([1, 3] as $part) {
                foreach ($mode['parts'][$part]['questions'] as $question) {
                    $this->assertNotEmpty($question['id']);
                    $this->assertNotEmpty($question['en']);
                }
            }
        }
    }
}
