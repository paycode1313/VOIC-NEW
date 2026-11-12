<?php

namespace Tests\Unit;

use App\Services\Exam\ExaminerService;
use Tests\TestCase;

class ExaminerVisualCueTest extends TestCase
{
    /**
     * @param  array<string, mixed>|null  $visual
     * @return array<string, mixed>
     */
    private function turn(?array $visual, string $directive = ExaminerService::DIRECTIVE_FOLLOW_UP): array
    {
        return [
            'mode' => 'wawancara',
            'role_type' => 'hrd',
            'examiner_name' => 'Kak Nadia',
            'language' => 'id',
            'topic' => null,
            'context_material' => null,
            'part' => 1,
            'part_title' => 'Perkenalan',
            'directive' => $directive,
            'question' => 'Ceritakan tentang dirimu.',
            'answer' => 'Saya lulusan informatika dari Malang.',
            'history' => [],
            'scripted' => '',
            'turn' => 1,
            'session_id' => 1,
            'visual' => $visual,
        ];
    }

    public function test_cue_needs_enough_camera_samples(): void
    {
        $examiner = app(ExaminerService::class);

        $this->assertNull($examiner->visualCue($this->turn(null)));
        $this->assertNull($examiner->visualCue($this->turn(['tension' => 90, 'samples' => 2])));
        $this->assertSame('tense', $examiner->visualCue($this->turn(['tension' => 90, 'samples' => 10])));
        $this->assertSame('away', $examiner->visualCue($this->turn(['eye_contact' => 30, 'tension' => 0, 'samples' => 10])));
        $this->assertSame('away', $examiner->visualCue($this->turn(['face_visible' => 20, 'samples' => 10])));
        $this->assertNull($examiner->visualCue($this->turn(['eye_contact' => 90, 'tension' => 10, 'smile' => 50, 'samples' => 10])));
    }

    public function test_model_is_told_what_the_camera_saw(): void
    {
        $examiner = app(ExaminerService::class);

        $instruction = $examiner->turnInstruction($this->turn(['tension' => 80, 'samples' => 10]));
        $this->assertStringEndsWith('| TEGANG]', $instruction);

        $away = $examiner->turnInstruction($this->turn(['eye_contact' => 20, 'tension' => 0, 'samples' => 10]));
        $this->assertStringEndsWith('| TIDAK MENATAP]', $away);

        $calm = $examiner->turnInstruction($this->turn(['tension' => 0, 'eye_contact' => 95, 'samples' => 10]));
        $this->assertStringNotContainsString('TEGANG', $calm);
        $this->assertStringNotContainsString('TIDAK MENATAP', $calm);

        // What the tags ask for is explained once, in the system prompt.
        $system = $examiner->systemPrompt($this->turn(null));
        $this->assertStringContainsString('TEGANG = peserta terlihat tegang di kamera', $system);
        $this->assertStringContainsString('TIDAK MENATAP = peserta sering tidak menatap kamera', $system);
    }

    public function test_closing_coach_turn_has_no_camera_remark(): void
    {
        $examiner = app(ExaminerService::class);

        $turn = $this->turn(['tension' => 80, 'samples' => 10], ExaminerService::DIRECTIVE_COACH_CLOSE);

        $this->assertNull($examiner->visualRemark($turn));
        $this->assertStringEndsWith('[TUTUP]', $examiner->turnInstruction($turn));
    }
}
