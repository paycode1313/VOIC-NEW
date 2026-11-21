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

    public function test_drawn_out_hesitations_count_as_fillers(): void
    {
        // Speech-to-text writes hesitations the way they sound: "eee", "emmm", "hmmm", "uhhh".
        $metrics = $this->analyzer->analyzeAnswer(
            'Eee jadi waktu magang itu, emmm, saya profiling query-nya. Hmmm, terus eeeh hasilnya turun, uhhh, ke 300 milidetik. Ehmmm gitu.',
            12,
            'Ceritakan pengalamanmu.',
            'wawancara'
        );

        $this->assertSame(['eh' => 2, 'ehm' => 1, 'em' => 1, 'hmm' => 1, 'uh' => 1], $metrics['fillers']);

        // Real words that merely contain these letters are left alone.
        $clean = $this->analyzer->analyzeAnswer('Saya ahli membuat menu hemat untuk umkm, emas dan hemat energi.', 5, 'Ceritakan dirimu.', 'wawancara');
        $this->assertSame([], $clean['fillers']);
    }

    public function test_key_phrase_keeps_the_candidates_own_capitals(): void
    {
        // Seen live (offline composer): "Oke, aku catat soal masalah api-nya lambat banget ya."
        $this->assertSame('masalah API-nya lambat banget', $this->analyzer->keyPhrase('Eee jadi waktu magang itu, ada masalah API-nya lambat banget pas jam sibuk.', 'wawancara'));
        $this->assertSame('2000 gambar', $this->analyzer->keyPhrase('Datanya 2000 gambar dari satu pabrik.', 'sidang'));
        // A phrase never ends on a dangling word ("Oke, oke, 2 miliar buat. Noted.").
        $this->assertSame('2 miliar', $this->analyzer->keyPhrase('Kami butuh dana 2 miliar buat ekspansi ke lima kota.', 'pitching'));
    }

    public function test_only_the_cue_card_talk_is_held_to_the_long_turn_length(): void
    {
        // A 40-word answer: short for the two-minute cue card talk, fine for the follow-up question after it.
        $metrics = $this->analyzer->analyzeAnswer(
            'Prototipe kedua yang kami pilih karena query-nya lebih cepat sekitar tiga puluh persen saat diuji dengan data asli dari gudang. Dampaknya, timeline proyek maju satu minggu dan tim backend bisa langsung lanjut ke fitur pelacakan berikutnya tanpa menunggu lagi.',
            20,
            'Prototipe mana yang dipilih?',
            'wawancara'
        );

        $longTurn = $this->analyzer->scoreAnswer($metrics, 2, longTurn: true);
        $followUp = $this->analyzer->scoreAnswer($metrics, 2, longTurn: false);

        $this->assertGreaterThan($longTurn['fluency'], $followUp['fluency']);
        // Without the flag (sessions recorded before it existed) part 2 counts as the long turn, as before.
        $this->assertSame($longTurn, $this->analyzer->scoreAnswer($metrics, 2));
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
