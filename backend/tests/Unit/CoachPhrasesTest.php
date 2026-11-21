<?php

namespace Tests\Unit;

use App\Services\Exam\ExaminerService;
use App\Services\Exam\ExamReportService;
use Tests\TestCase;

class CoachPhrasesTest extends TestCase
{
    /**
     * @param  list<string>  $lines
     * @return list<string>
     */
    private function phrases(array $lines): array
    {
        $method = new \ReflectionMethod(ExamReportService::class, 'taughtPhrases');

        return $method->invoke(app(ExamReportService::class), $lines);
    }

    public function test_only_whole_quoted_example_sentences_are_collected(): void
    {
        // A real coaching line: two quotes, the text between them used to be "collected" too.
        $line = 'Hindari kata “saya terlalu perfeksionis” tanpa contoh, pakai struktur: “Saya dulu sering kesulitan mengatur prioritas, jadi saya mulai pakai teknik Pomodoro.” Coba ulangi jawabannya pakai pola itu, ya?';

        $this->assertSame(['Saya dulu sering kesulitan mengatur prioritas, jadi saya mulai pakai teknik Pomodoro.'], $this->phrases([$line]));
    }

    public function test_straight_quotes_pair_up_too(): void
    {
        $line = 'Coba bilang "Saya terbuka negosiasi berdasarkan tanggung jawab dan pasar." lalu "Boleh saya tahu kisaran dari perusahaan?"';

        $this->assertSame([
            'Saya terbuka negosiasi berdasarkan tanggung jawab dan pasar.',
            'Boleh saya tahu kisaran dari perusahaan?',
        ], $this->phrases([$line]));
    }

    public function test_sentences_glued_together_by_the_model_are_split(): void
    {
        $examiner = app(ExaminerService::class);

        // Seen live: "ya?Bagus", "Engineer.Oke", "coding.Wah" made one endless turn.
        $this->assertSame(
            'Coba ucapkan ulang, ya? Bagus, sekarang beri contoh. Oke, coba lagi.',
            $examiner->clean('Coba ucapkan ulang, ya?Bagus, sekarang beri contoh.Oke, coba lagi.Wah, mantap!', 3),
        );
        // Abbreviations and decimals stay as they are.
        $this->assertSame('Gelar S.Kom dan IPK 3.5 itu bagus.', $examiner->clean('Gelar S.Kom dan IPK 3.5 itu bagus.'));
    }
}
