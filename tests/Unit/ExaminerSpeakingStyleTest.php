<?php

namespace Tests\Unit;

use App\Services\Exam\ExaminerService;
use Tests\TestCase;

class ExaminerSpeakingStyleTest extends TestCase
{
    /**
     * @return array<string, mixed>
     */
    private function turn(string $roleType, string $name, string $language = 'id', ?string $sessionType = null): array
    {
        return [
            'mode' => match ($roleType) {
                'hrd' => 'wawancara',
                'investor' => 'pitching',
                default => 'sidang',
            },
            'role_type' => $roleType,
            'examiner_name' => $name,
            'language' => $language,
            'topic' => null,
            'context_material' => null,
            'session_type' => $sessionType,
            'coach_title' => 'Coach',
            'coach_skills' => 'struktur jawaban',
        ];
    }

    public function test_every_examiner_gets_a_spoken_style_in_exam_and_coach_prompts(): void
    {
        $examiner = app(ExaminerService::class);

        foreach (['hrd' => 'Kak Nadia', 'investor' => 'Pak William', 'dosen_penguji' => 'Prof. Hendra'] as $role => $name) {
            foreach ([null, 'coach'] as $sessionType) {
                foreach (['id', 'en'] as $language) {
                    $prompt = $examiner->systemPrompt($this->turn($role, $name, $language, $sessionType));
                    $this->assertStringContainsString($examiner->speakingStyle($role, $language === 'en'), $prompt, "{$role} {$sessionType} {$language}");
                }
            }
        }
    }

    public function test_styles_keep_each_examiner_in_character(): void
    {
        $examiner = app(ExaminerService::class);

        // Every examiner says "kamu" and avoids stiff written words.
        $this->assertStringContainsString('"Anda"', $examiner->speakingStyle('hrd', false));
        $this->assertStringContainsString('Hindari kata kaku seperti "tersebut"', $examiner->speakingStyle('hrd', false));
        $this->assertStringContainsString('dengan "kamu"', $examiner->speakingStyle('investor', false));
        $this->assertStringContainsString('Hindari kata kaku seperti "Anda"', $examiner->speakingStyle('investor', false));
        $this->assertStringContainsString('dengan "kamu"', $examiner->speakingStyle('dosen_penguji', false));
        $this->assertStringContainsString('Hindari kata kaku seperti "Anda"', $examiner->speakingStyle('dosen_penguji', false));
        $this->assertStringNotContainsString('bahasa formal', $examiner->systemPrompt($this->turn('dosen_penguji', 'Prof. Hendra')));
        // An unknown role talks like the thesis examiner instead of failing.
        $this->assertSame($examiner->speakingStyle('dosen_penguji', false), $examiner->speakingStyle('lainnya', false));
    }

    public function test_chinese_words_from_the_model_are_removed_before_speaking(): void
    {
        $examiner = app(ExaminerService::class);

        $this->assertSame(
            'Oke, fokus ke startup Jaksel itu masuk akal sebagai awal. Model bisnisnya gimana?',
            $examiner->clean('Oke, fokus ke startup Jaksel itu masuk akal sebagai 切入点 awal. Model bisnisnya gimana?'),
        );
        $this->assertSame('Bagus sekali.', $examiner->clean('Bagus sekali 好的.'));
    }

    public function test_brand_names_are_spoken_as_words(): void
    {
        $this->assertSame('VOIC Interview', ExaminerService::spokenName('VOIC-INTERVIEW'));
        $this->assertSame('VOIC Sidang', ExaminerService::spokenName('VOIC-SIDANG'));
        $this->assertSame('VOIC Bisnis', ExaminerService::spokenName('VOIC-BISNIS'));
        // Ordinary names stay as they are.
        $this->assertSame('Kak Nadia', ExaminerService::spokenName('Kak Nadia'));
        $this->assertSame('Prof. Hendra', ExaminerService::spokenName('Prof. Hendra'));

        $prompt = app(ExaminerService::class)->systemPrompt($this->turn('investor', 'VOIC-BISNIS'));
        $this->assertStringStartsWith('Kamu adalah VOIC Bisnis,', $prompt);
    }
}
