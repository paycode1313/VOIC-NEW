<?php

namespace Tests\Unit;

use App\Services\Exam\ConversationSignals;
use App\Services\Exam\ExaminerService;
use Tests\TestCase;

class ConversationSignalsTest extends TestCase
{
    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function turn(array $overrides = []): array
    {
        return $overrides + [
            'mode' => 'wawancara',
            'role_type' => 'hrd',
            'examiner_name' => 'Kak Nadia',
            'language' => 'id',
            'topic' => 'Frontend Developer',
            'context_material' => null,
            'part' => 3,
            'part_title' => 'Diskusi Mendalam',
            'directive' => ExaminerService::DIRECTIVE_FOLLOW_UP,
            'question' => 'Ceritakan pengalaman kerja tim.',
            'answer' => '',
            'history' => [],
            'scripted' => '',
            'planned_question' => null,
            'transition' => null,
            'turn' => 3,
            'session_id' => 1,
        ];
    }

    private function signals(): ConversationSignals
    {
        return app(ConversationSignals::class);
    }

    public function test_recruiter_asks_for_the_candidates_own_part_in_a_we_story(): void
    {
        $gap = $this->signals()->gap($this->turn([
            'answer' => 'Waktu itu kami membuat aplikasi kasir, kami bagi tugas, lalu kami presentasikan ke klien dan kami dapat proyek lanjutan.',
        ]));

        $this->assertSame('we', $gap[0]);
        $this->assertStringContainsString('kami', $gap[1]);
    }

    public function test_recruiter_asks_for_proof_of_a_claimed_trait(): void
    {
        $gap = $this->signals()->gap($this->turn(['answer' => 'Saya orangnya pekerja keras dan selalu menyelesaikan tugas tepat waktu di kantor.']));

        $this->assertSame('trait', $gap[0]);
        $this->assertStringContainsString('pekerja keras', $gap[1]);
    }

    public function test_recruiter_notices_a_story_without_a_result(): void
    {
        $gap = $this->signals()->gap($this->turn([
            'answer' => 'Dulu saya pernah memimpin acara kampus, saya mengatur jadwal rapat, membagi panitia ke divisi, dan menghubungi sponsor satu per satu setiap minggu.',
        ]));

        $this->assertSame('result', $gap[0]);
    }

    public function test_investor_challenges_no_competitors_and_numberless_hype(): void
    {
        $investor = ['mode' => 'pitching', 'role_type' => 'investor'];

        $this->assertSame('competitors', $this->signals()->gap($this->turn($investor + ['answer' => 'Produk kami unik dan sampai sekarang belum ada pesaing di Indonesia.']))[0]);
        $this->assertSame('no_number', $this->signals()->gap($this->turn($investor + ['answer' => 'Pertumbuhan pengguna kami pesat sekali sejak peluncuran bulan lalu.']))[0]);
        $this->assertSame('broad_market', $this->signals()->gap($this->turn($investor + ['answer' => 'Target pasar kami semua umkm, ada 64 juta usaha.']))[0]);
    }

    public function test_thesis_examiner_asks_for_a_comparison_a_reason_and_the_sample(): void
    {
        $examiner = ['mode' => 'sidang', 'role_type' => 'dosen_penguji', 'examiner_name' => 'Prof. Hendra'];

        $this->assertSame('comparison', $this->signals()->gap($this->turn($examiner + ['answer' => 'Hasilnya model saya lebih akurat untuk mendeteksi cacat kain.']))[0]);
        $this->assertNull($this->signals()->gap($this->turn($examiner + ['answer' => 'Model saya lebih akurat 6 persen dibanding Faster R-CNN pada 2000 gambar uji karena anchor-free.'])));
        $this->assertSame('method_reason', $this->signals()->gap($this->turn($examiner + ['answer' => 'Saya menggunakan metode regresi linier berganda untuk menganalisis pengaruh layanan.']))[0]);
        $this->assertSame('sample', $this->signals()->gap($this->turn($examiner + ['answer' => 'Data saya ambil dari responden pelanggan kedai kopi karena mereka pengguna langsung.']))[0]);
    }

    public function test_detectors_found_in_live_scenarios(): void
    {
        // "untuk 10 persen saham" is the deal, not how the money is spent.
        $this->assertSame('funds', $this->signals()->gap($this->turn(['mode' => 'pitching', 'role_type' => 'investor', 'answer' => 'Kami sedang mencari pendanaan 1,5 miliar untuk 10 persen saham.']))[0]);
        $this->assertNull($this->signals()->gap($this->turn(['mode' => 'pitching', 'role_type' => 'investor', 'answer' => 'Kami mencari pendanaan 1,5 miliar, 60 persen dipakai untuk merekrut tim penjualan di tiga kota.'])));

        // "Datanya" is a data mention too.
        $this->assertSame('sample', $this->signals()->gap($this->turn(['mode' => 'sidang', 'role_type' => 'dosen_penguji', 'answer' => 'Datanya saya ambil dari sistem akademik fakultas, berisi IPK per semester dan status kelulusan.']))[0]);

        // A trait that is not in the first version of the list.
        $this->assertStringContainsString('sabar', $this->signals()->gap($this->turn(['part' => 1, 'answer' => 'Saya orangnya sabar dan komunikatif, jadi gampang akrab sama pelanggan.']))[1]);

        // "dulu" as "first" is not a story.
        $this->assertNotSame('result', $this->signals()->gap($this->turn(['answer' => 'Saya akan ajak dia ngobrol empat mata dulu, cari tahu kenapa telat, mungkin bebannya terlalu banyak atau requirement-nya belum jelas.']))[0] ?? null);

        // Four-letter key words link the next topic to an earlier answer.
        $link = $this->signals()->link($this->turn([
            'mode' => 'sidang', 'role_type' => 'dosen_penguji', 'directive' => ExaminerService::DIRECTIVE_NEXT,
            'planned_question' => 'Apa keterbatasan data yang Anda gunakan?',
            'history' => [
                ['role' => 'assistant', 'content' => 'Dari mana data penelitian Anda?'],
                ['role' => 'user', 'content' => 'Data saya dari sistem akademik fakultas teknik, 1.200 mahasiswa angkatan 2017 sampai 2019, tanpa data ekonomi keluarga.'],
            ],
        ]));
        $this->assertNotNull($link);
        $this->assertStringContainsString('data', $link);
    }

    public function test_a_different_number_for_the_same_thing_is_flagged_once(): void
    {
        $history = [
            ['role' => 'assistant', 'content' => 'Boleh perkenalkan diri?'],
            ['role' => 'user', 'content' => 'Saya membangun dashboard penjualan untuk 40 toko retail di Malang.'],
            ['role' => 'assistant', 'content' => 'Tantangan terbesarnya apa?'],
        ];

        $gap = $this->signals()->gap($this->turn(['history' => $history, 'answer' => 'Paling sulit itu menyamakan format data dari 25 toko yang sistemnya beda-beda.']));
        $this->assertSame('contradiction', $gap[0]);
        $this->assertStringContainsString('40 toko', $gap[1]);
        $this->assertStringContainsString('25 toko', $gap[1]);

        // Describing growth is not a contradiction.
        $this->assertNotSame('contradiction', $this->signals()->gap($this->turn(['history' => $history, 'answer' => 'Awalnya 40 toko, sekarang sudah naik menjadi 55 toko di Jawa Timur.']))[0] ?? null);
    }

    public function test_quantities_understand_multipliers_and_ignore_time_units(): void
    {
        $found = $this->signals()->quantities('Kami punya 50 juta pengguna, 2.000 gambar, selama 3 bulan dan 12 persen.');

        $this->assertSame(['50 juta'], $found['pengguna']);
        $this->assertSame(['2.000'], $found['gambar']);
        $this->assertArrayNotHasKey('bulan', $found);
        $this->assertArrayNotHasKey('persen', $found);
    }

    public function test_unsure_answers_get_no_pressure(): void
    {
        $this->assertNull($this->signals()->gap($this->turn(['answer' => 'Jujur saya nggak tahu, kami belum pernah menghitungnya.'])));
    }

    public function test_next_topic_links_back_to_an_earlier_related_detail(): void
    {
        $history = [
            ['role' => 'assistant', 'content' => 'Boleh perkenalkan diri?'],
            ['role' => 'user', 'content' => 'Saya Rina, saya membangun dashboard penjualan React untuk 40 toko retail di Malang.'],
            ['role' => 'assistant', 'content' => 'Kenapa melamar posisi ini?'],
            ['role' => 'user', 'content' => 'Karena saya suka produk yang dipakai setiap hari.'],
        ];

        $link = $this->signals()->link($this->turn([
            'directive' => ExaminerService::DIRECTIVE_NEXT,
            'history' => $history,
            'answer' => 'Saya suka tim yang terbuka.',
            'planned_question' => 'Bagaimana kamu memastikan dashboard penjualan tetap cepat saat datanya besar?',
        ]));

        $this->assertSame('membangun dashboard penjualan React untuk 40 toko retail', $link);

        $this->assertNull($this->signals()->link($this->turn([
            'directive' => ExaminerService::DIRECTIVE_NEXT,
            'history' => $history,
            'planned_question' => 'Siapa orang yang paling memengaruhi cara kerjamu?',
        ])));
    }

    public function test_examiner_turn_carries_the_focus_and_link_tags(): void
    {
        $examiner = app(ExaminerService::class);

        $probe = $examiner->turnInstruction($this->turn(['answer' => 'Kami membuat aplikasi, kami uji ke pengguna, kami perbaiki, dan kami rilis bulan lalu ke publik.']));
        $this->assertStringContainsString('| GALI | FOKUS: peran pribadinya belum jelas', $probe);
        $this->assertStringEndsWith(']', $probe);

        $history = [
            ['role' => 'assistant', 'content' => 'Boleh perkenalkan diri?'],
            ['role' => 'user', 'content' => 'Saya membangun dashboard penjualan React untuk 40 toko retail di Malang.'],
        ];
        $next = $examiner->turnInstruction($this->turn([
            'directive' => ExaminerService::DIRECTIVE_NEXT,
            'history' => $history,
            'answer' => 'Saya suka kerja tim.',
            'planned_question' => 'Bagaimana kamu menjaga dashboard penjualan tetap cepat?',
        ]));
        $this->assertStringContainsString('KAITKAN: "membangun dashboard penjualan React untuk 40 toko retail"', $next);

        // Reactions and clarifications stay plain: no hint the model could misread.
        $this->assertStringNotContainsString('FOKUS', $examiner->turnInstruction($this->turn(['directive' => ExaminerService::DIRECTIVE_REACT, 'answer' => 'Kami membuat aplikasi, kami uji, kami rilis, kami evaluasi.'])));
    }

    public function test_system_prompt_teaches_each_persona_how_to_dig(): void
    {
        $examiner = app(ExaminerService::class);

        $hr = $examiner->systemPrompt($this->turn());
        $this->assertStringContainsString('STAR', $hr);
        $this->assertStringContainsString('FOKUS', $hr);
        $this->assertStringContainsString('KAITKAN', $hr);

        $investor = $examiner->systemPrompt($this->turn(['mode' => 'pitching', 'role_type' => 'investor', 'examiner_name' => 'Bu Sarah']));
        $this->assertStringContainsString('tidak ada pesaing', $investor);

        $thesis = $examiner->systemPrompt($this->turn(['mode' => 'sidang', 'role_type' => 'dosen_penguji', 'examiner_name' => 'Prof. Hendra', 'language' => 'en']));
        $this->assertStringContainsString('compared to what', $thesis);
        $this->assertStringContainsString('FOCUS', $thesis);

        // The system prompt must not depend on the turn, or the model loses its cache every turn.
        $this->assertSame($hr, $examiner->systemPrompt($this->turn(['answer' => 'lain', 'part' => 1, 'directive' => ExaminerService::DIRECTIVE_NEXT])));
    }

    public function test_long_sessions_stay_inside_the_model_context(): void
    {
        $history = [];
        for ($i = 1; $i <= 11; $i++) {
            $history[] = ['role' => 'assistant', 'content' => str_repeat("Pertanyaan {$i} tentang pengalaman. ", 8).'?'];
            $history[] = ['role' => 'user', 'content' => str_repeat("Jawaban panjang ke-{$i} tentang proyek dan tim. ", 14)];
        }

        $messages = app(ExaminerService::class)->buildMessages($this->turn(['history' => $history, 'answer' => 'Jawaban terakhir.']));
        $total = array_sum(array_map(fn ($message) => mb_strlen($message['content']), array_slice($messages, 0, -1)));

        $this->assertLessThanOrEqual(ExaminerService::CONTEXT_CHAR_BUDGET, $total);
        $this->assertStringContainsString('Sebelumnya di sesi ini', $messages[0]['content']);
    }

    public function test_offline_fallback_asks_the_targeted_question(): void
    {
        $examiner = app(ExaminerService::class);

        $reply = $examiner->offlineReply($this->turn(['answer' => 'Kami membuat aplikasi kasir, kami uji ke 20 toko, kami perbaiki bug, lalu kami rilis bulan lalu.']));
        $this->assertStringContainsString('kamu kerjakan sendiri', $reply);
        $this->assertSame(1, substr_count($reply, '?'));

        $thesis = $examiner->offlineReply($this->turn(['mode' => 'sidang', 'role_type' => 'dosen_penguji', 'examiner_name' => 'Prof. Hendra', 'answer' => 'Hasilnya model saya lebih akurat untuk mendeteksi cacat kain di pabrik.']));
        $this->assertStringContainsString('Dibandingkan dengan metode apa', $thesis);
    }
}
