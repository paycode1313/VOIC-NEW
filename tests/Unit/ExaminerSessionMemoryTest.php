<?php

namespace Tests\Unit;

use App\Services\Exam\ExaminerService;
use Tests\TestCase;

class ExaminerSessionMemoryTest extends TestCase
{
    /**
     * @param  list<array{role: string, content: string}>  $history
     * @return array<string, mixed>
     */
    private function turn(array $history): array
    {
        return [
            'mode' => 'wawancara',
            'role_type' => 'hrd',
            'examiner_name' => 'Kak Nadia',
            'language' => 'id',
            'topic' => 'Frontend Developer',
            'context_material' => null,
            'part' => 3,
            'part_title' => 'Diskusi',
            'directive' => ExaminerService::DIRECTIVE_FOLLOW_UP,
            'question' => 'Apa rencanamu lima tahun ke depan?',
            'answer' => 'Saya ingin jadi tech lead.',
            'history' => $history,
            'scripted' => '',
            'turn' => 20,
            'session_id' => 1,
        ];
    }

    /**
     * A long conversation: the first answer mentions a project and a number.
     *
     * @return list<array{role: string, content: string}>
     */
    private function longHistory(): array
    {
        $history = [
            ['role' => 'assistant', 'content' => 'Halo, aku Kak Nadia. Boleh perkenalkan diri dulu?'],
            ['role' => 'user', 'content' => 'Saya Rina, saya membangun dashboard penjualan React untuk 40 toko retail di Malang.'],
        ];
        for ($i = 1; $i <= 16; $i++) {
            $history[] = ['role' => 'assistant', 'content' => "Menarik. Pertanyaan lanjutan nomor {$i}?"];
            $history[] = ['role' => 'user', 'content' => "Jawaban ke-{$i} tentang pengalaman kerja tim."];
        }

        return $history;
    }

    public function test_early_answers_are_remembered_after_they_leave_the_window(): void
    {
        $messages = app(ExaminerService::class)->buildMessages($this->turn($this->longHistory()));

        $system = $messages[0]['content'];
        $this->assertStringContainsString('Sebelumnya di sesi ini', $system);
        $this->assertStringContainsString('dashboard penjualan React untuk 40 toko retail', $system);
        $this->assertStringContainsString('Kamu sudah bertanya: Boleh perkenalkan diri dulu?', $system);

        // System prompt + at most RECENT_MESSAGES recent messages (word for word) + the current instruction.
        $this->assertLessThanOrEqual(1 + ExaminerService::RECENT_MESSAGES + 1, count($messages));
        $this->assertSame('Jawaban ke-16 tentang pengalaman kerja tim.', $messages[count($messages) - 2]['content']);
    }

    public function test_a_normal_exam_keeps_the_whole_conversation_word_for_word(): void
    {
        // 22 messages: about a full three-part exam. Nothing is condensed, so the prompt only ever grows at the end.
        $history = array_slice($this->longHistory(), 0, 22);
        $messages = app(ExaminerService::class)->buildMessages($this->turn($history));

        $this->assertStringNotContainsString('Sebelumnya di sesi ini', $messages[0]['content']);
        $this->assertCount(1 + 22 + 1, $messages);
        $this->assertSame($history[0]['content'], $messages[1]['content']);
    }

    public function test_very_long_answers_are_condensed_before_the_context_overflows(): void
    {
        $history = [];
        for ($i = 1; $i <= 12; $i++) {
            $history[] = ['role' => 'assistant', 'content' => "Pertanyaan nomor {$i}?"];
            $history[] = ['role' => 'user', 'content' => "Jawaban panjang {$i}. ".str_repeat('Saya menjelaskan proyek ini dengan sangat rinci. ', 20)];
        }
        $messages = app(ExaminerService::class)->buildMessages($this->turn($history));

        $verbatim = array_sum(array_map(fn ($message) => mb_strlen($message['content']), array_slice($messages, 1, -1)));
        $this->assertLessThanOrEqual(ExaminerService::HISTORY_CHAR_BUDGET + 24 * 3, $verbatim);
        $this->assertStringContainsString('Sebelumnya di sesi ini', $messages[0]['content']);
        $this->assertStringContainsString('Jawaban panjang 12.', $messages[count($messages) - 2]['content']);
    }

    public function test_system_prompt_stays_identical_for_several_turns_so_the_model_cache_is_reused(): void
    {
        $examiner = app(ExaminerService::class);
        $full = array_merge($this->longHistory(), $this->longHistory());
        $systemAt = fn (int $length) => $examiner->buildMessages($this->turn(array_slice($full, 0, $length)))[0]['content'];

        // Within one block of MEMORY_STEP messages the start of the prompt does not change...
        $first = ExaminerService::RECENT_MESSAGES + 1;
        $block = array_map($systemAt, range($first, $first + ExaminerService::MEMORY_STEP - 1));
        $this->assertCount(1, array_unique($block));

        // ...and it only moves on at the next block.
        $this->assertNotSame($block[0], $systemAt($first + ExaminerService::MEMORY_STEP));
    }

    public function test_short_conversation_has_no_memory_block(): void
    {
        $history = array_slice($this->longHistory(), 0, 4);

        $system = app(ExaminerService::class)->buildMessages($this->turn($history))[0]['content'];

        $this->assertStringNotContainsString('Sebelumnya di sesi ini', $system);
        $this->assertStringContainsString('Ingat semua yang sudah diceritakan peserta', $system);
    }
}
