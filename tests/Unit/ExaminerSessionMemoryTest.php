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
        for ($i = 1; $i <= 10; $i++) {
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

        // System prompt + 12 recent messages + the current instruction.
        $this->assertCount(1 + ExaminerService::RECENT_MESSAGES + 1, $messages);
    }

    public function test_short_conversation_has_no_memory_block(): void
    {
        $history = array_slice($this->longHistory(), 0, 4);

        $system = app(ExaminerService::class)->buildMessages($this->turn($history))[0]['content'];

        $this->assertStringNotContainsString('Sebelumnya di sesi ini', $system);
        $this->assertStringContainsString('Ingat semua yang sudah diceritakan peserta', $system);
    }
}
