<?php

namespace Tests\Unit;

use App\Services\Exam\ExamBlueprint;
use App\Services\Exam\SpeakingAnalyzer;
use Tests\TestCase;

class SpeakingAnalyzerTest extends TestCase
{
    private SpeakingAnalyzer $analyzer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->analyzer = new SpeakingAnalyzer(new ExamBlueprint);
    }

    public function test_it_measures_pace_fillers_and_evidence_from_the_transcript(): void
    {
        $metrics = $this->analyzer->analyzeAnswer(
            'Eh jadi saya saya memimpin proyek aplikasi, ehm, misalnya waktu itu tim kami berhasil meningkatkan penjualan 30 persen karena kolaborasi yang baik.',
            10,
            'Ceritakan pengalaman memimpin tim.',
            'wawancara'
        );

        $this->assertSame(22, $metrics['word_count']);
        $this->assertSame(132, $metrics['wpm']);
        $this->assertSame(['eh' => 1, 'ehm' => 1], $metrics['fillers']);
        $this->assertSame(1, $metrics['repetitions']);
        $this->assertTrue($metrics['has_numbers']);
        $this->assertTrue($metrics['has_example']);
        $this->assertContains('karena', $metrics['connectors']);
        $this->assertContains('proyek', $metrics['domain_terms']);
    }

    public function test_a_rich_answer_scores_higher_than_a_vague_one(): void
    {
        $rich = $this->analyzer->analyzeAnswer(
            'Saya memilih YOLOv8 karena dataset kami berisi 2000 gambar dan butuh inferensi real-time. Sebagai contoh, pada pengujian di pabrik akurasi model mencapai 87 persen, sehingga lebih baik dari baseline YOLOv5. Namun keterbatasannya data hanya dari satu pabrik, oleh karena itu validasi lanjutan masih diperlukan.',
            25,
            'Kenapa Anda memilih metode tersebut?',
            'sidang'
        );
        $vague = $this->analyzer->analyzeAnswer('Eh karena bagus aja sih.', 4, 'Kenapa Anda memilih metode tersebut?', 'sidang');

        $richBand = array_sum($this->analyzer->scoreAnswer($rich, 3)) / 4;
        $vagueBand = array_sum($this->analyzer->scoreAnswer($vague, 3)) / 4;

        $this->assertGreaterThan($vagueBand + 2, $richBand);
    }

    public function test_key_phrase_uses_multi_word_phrases_only(): void
    {
        $this->assertSame('proyek klien e-commerce', $this->analyzer->keyPhrase('Saya banyak memegang proyek klien e-commerce.', 'wawancara'));
        $this->assertSame('300 pelanggan aktif', $this->analyzer->keyPhrase('Saat ini kami punya 300 pelanggan aktif.', 'pitching'));
        $this->assertNull($this->analyzer->keyPhrase('Saya suka membaca buku di akhir pekan.', 'wawancara'));
        $this->assertNull($this->analyzer->keyPhrase('Saya terbuka soal masalah.', 'wawancara'));
    }

    public function test_bands_are_clamped_to_half_steps(): void
    {
        $this->assertSame(6.5, $this->analyzer->toBand(6.4));
        $this->assertSame(9.0, $this->analyzer->toBand(12));
        $this->assertSame(1.0, $this->analyzer->toBand(-3));
    }
}
