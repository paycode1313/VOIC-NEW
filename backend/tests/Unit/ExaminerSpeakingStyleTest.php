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
        // Every examiner is cool but kind, and writes Indonesian loanwords the KBBI way.
        foreach (['hrd', 'investor', 'dosen_penguji'] as $role) {
            $this->assertStringContainsString('Jangan pernah kasar', $examiner->speakingStyle($role, false));
            $this->assertStringContainsString('Never be rude', $examiner->speakingStyle($role, true));
            $this->assertStringContainsString('"praktik" (bukan praktek)', $examiner->speakingStyle($role, false));
        }
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

    public function test_dashes_and_semicolons_become_spoken_pauses(): void
    {
        $examiner = app(ExaminerService::class);

        // The voice reads a dash oddly (or not at all); a comma is the pause the model meant.
        $this->assertSame(
            'Jadi kamu bikin prototipe, mantap! Apa yang bikin lebih cepat, dan gimana ngukurnya?',
            $examiner->clean('Jadi kamu bikin prototipe—mantap! Apa yang bikin lebih cepat; dan gimana ngukurnya?'),
        );
        $this->assertSame('Oke, pasar e-commerce, gede juga.', $examiner->clean('Oke – pasar e-commerce - gede juga.'));
        $this->assertSame('Dua kali lipat, itu lompatan besar.', $examiner->clean('Dua kali lipat (itu lompatan besar).'));
    }

    public function test_the_prompt_asks_for_human_reactions_instead_of_echoing_the_answer(): void
    {
        $examiner = app(ExaminerService::class);

        foreach (['id' => 'Jangan pernah mengulang atau merangkum jawaban peserta', 'en' => 'Never restate or summarise what the candidate just said'] as $language => $rule) {
            foreach (['hrd', 'investor', 'dosen_penguji'] as $role) {
                $prompt = $examiner->systemPrompt($this->turn($role, 'VOIC-INTERVIEW', $language));
                $this->assertStringContainsString($rule, $prompt, "{$role} {$language}");
                // The style examples must not teach the old "repeat the number, then praise" pattern.
                $this->assertDoesNotMatchRegularExpression('/tiga ratus pengguna dalam dua bulan, lumayan|Lima puluh responden lewat kuesioner, saya catat|Three hundred in two months is/u', $prompt);
            }
        }
    }

    public function test_after_a_compliment_the_next_turn_is_asked_not_to_praise(): void
    {
        $examiner = app(ExaminerService::class);
        $turn = fn (string $lastExaminerLine, string $language = 'id') => $this->turn('hrd', 'VOIC-INTERVIEW', $language) + [
            'answer' => 'Saya pakai eager loading dan index.',
            'directive' => ExaminerService::DIRECTIVE_NEXT,
            'planned_question' => 'Apa hobimu?',
            'transition' => '',
            'part' => 1,
            'part_title' => 'Perkenalan',
            'skip_link' => true,
            'history' => [
                ['role' => 'assistant', 'content' => $lastExaminerLine],
                ['role' => 'user', 'content' => 'Jawaban sebelumnya.'],
            ],
        ];

        $this->assertStringContainsString('TANPA PUJIAN', $examiner->turnInstruction($turn('300 milidetik, mantap! Terus gimana?')));
        $this->assertStringContainsString('TANPA PUJIAN', $examiner->turnInstruction($turn('Wah, keren banget. Lanjut ya?')));
        $this->assertStringContainsString('TANPA PUJIAN', $examiner->turnInstruction($turn('Wah, backend pake Laravel pasti seru! Terus?')));
        // Words that only contain a compliment do not count.
        $this->assertStringNotContainsString('TANPA PUJIAN', $examiner->turnInstruction($turn('Topiknya soal bagusnya hasil. Kenapa?')));
        $this->assertStringNotContainsString('TANPA PUJIAN', $examiner->turnInstruction($turn('Oke, kebayang. Terus gimana?')));
        $this->assertStringContainsString('NO PRAISE', $examiner->turnInstruction($turn('Doubled? Nice. Which part was yours?', 'en')));

        // The tag is explained once, in the system prompt.
        $this->assertStringContainsString('TANPA PUJIAN', $examiner->systemPrompt($turn('x')));
    }

    public function test_a_reaction_and_the_scripted_line_do_not_say_the_same_opener_twice(): void
    {
        $examiner = app(ExaminerService::class);
        $turn = ['directive' => ExaminerService::DIRECTIVE_REACT, 'language' => 'id', 'scripted' => 'Oke, sekarang kita masuk Bagian 2, Cerita Pengalaman.'];

        $this->assertSame(
            'Oke, aku dengar. Sekarang kita masuk Bagian 2, Cerita Pengalaman.',
            $examiner->resolveModelReply('Oke, aku dengar.', $turn)['text'],
        );
        // Seen live: the model announced the part itself, then the scripted line announced it again.
        $next = ['directive' => ExaminerService::DIRECTIVE_NEXT, 'language' => 'id', 'transition' => 'Bagian 3, Tanya Jawab Penguji',
            'scripted' => 'Sekarang kita lanjut ke Bagian 3, Tanya Jawab Penguji, di sini kita bahas lebih dalam. Kenapa kamu pilih metode itu?'];
        $this->assertSame(
            'Oke, pengurangan 40 persen waktu, angka yang menarik. Sekarang kita lanjut ke Bagian 3, Tanya Jawab Penguji, di sini kita bahas lebih dalam. Kenapa kamu pilih metode itu?',
            $examiner->resolveModelReply('Oke, pengurangan 40 persen waktu, angka yang menarik. Kita masuk Bagian 3, Tanya Jawab Penguji.', $next)['text'],
        );

        // A different opener is kept.
        $this->assertSame(
            'Timeboxing, masuk akal. Oke, sekarang kita masuk Bagian 2, Cerita Pengalaman.',
            $examiner->resolveModelReply('Timeboxing, masuk akal.', $turn)['text'],
        );
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
