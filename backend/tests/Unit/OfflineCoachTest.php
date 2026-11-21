<?php

namespace Tests\Unit;

use App\Services\Exam\ExaminerService;
use Tests\TestCase;

/**
 * The coach without any AI (no internet, no local model): seen live, it asked people to "say your answer again"
 * before they had answered, gave the generic "unsure" tip to a salary question, repeated tips and told a
 * "thank you" that the answer was too short.
 */
class OfflineCoachTest extends TestCase
{
    /**
     * @param  list<array{role: string, content: string}>  $history
     */
    private function coach(string $answer, array $history = [], int $turn = 1): string
    {
        return app(ExaminerService::class)->offlineCoach([
            'language' => 'id', 'role_type' => 'hrd', 'mode' => 'wawancara', 'topic' => 'Software Engineer',
            'directive' => ExaminerService::DIRECTIVE_COACH, 'question' => 'Mau latihan apa?', 'answer' => $answer,
            'history' => $history, 'turn' => $turn,
        ]);
    }

    public function test_asking_to_practise_a_topic_gets_that_topic(): void
    {
        $reply = $this->coach('Saya mau latihan jawab pertanyaan kelemahan, soalnya saya suka bingung jawabnya.');
        $this->assertStringContainsString('kelemahan', $reply);
        $this->assertStringNotContainsString('ucapkan ulang', $reply);
        $this->assertStringEndsWith('?', $reply);

        $salary = $this->coach('Hmm kalau ditanya gaji saya bingung harus jawab berapa.', turn: 4);
        $this->assertStringContainsString('rentang', $salary);
        $this->assertStringNotContainsString('Boleh saya pikirkan sebentar', $salary);
    }

    public function test_thanks_gets_a_warm_reply_not_a_critique(): void
    {
        $reply = $this->coach('Oke makasih kak, membantu banget.', turn: 5);
        $this->assertStringStartsWith('Sama-sama', $reply);
        $this->assertStringNotContainsString('masih singkat', $reply);
    }

    public function test_a_tip_already_given_is_not_repeated(): void
    {
        $first = $this->coach('Saya nggak tahu sih.', turn: 1);
        $this->assertStringContainsString('Boleh saya pikirkan sebentar', $first);

        $second = $this->coach('Saya nggak tahu juga sih.', [
            ['role' => 'assistant', 'content' => $first],
            ['role' => 'user', 'content' => 'Saya nggak tahu sih.'],
        ], turn: 2);
        $this->assertStringNotContainsString('Boleh saya pikirkan sebentar', $second);
    }
}
