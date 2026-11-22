<?php

namespace Tests\Unit;

use App\Services\Exam\ExaminerService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
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
        // A dash between numbers is a range, left for the voice to read as "sampai".
        $this->assertSame('Targetnya 12-15 juta, periode 2025-2026.', $examiner->clean('Targetnya 12–15 juta, periode 2025 – 2026.'));
    }

    public function test_instruction_tags_read_aloud_by_the_model_are_removed(): void
    {
        $examiner = app(ExaminerService::class);

        // Seen live (cloud model, English session): the tag name was spoken.
        $this->assertSame(
            "Cool goal, Rina. Now we're in Part 3, Deep Discussion. If a teammate kept missing deadlines, what would you do?",
            $examiner->clean("Cool goal, Rina. ANNOUNCE: Now we're in Part 3, Deep Discussion. If a teammate kept missing deadlines, what would you do?"),
        );
        // An unclosed quote around the planned question goes too.
        $this->assertSame(
            'Nice start, Rina. Now we will move to What weakness are you currently working on?',
            $examiner->clean('Nice start, Rina. ANNOUNCE: Now we will move to “What weakness are you currently working on?'),
        );
        $this->assertSame('Oke. Kenapa kamu pilih metode itu?', $examiner->clean('Oke. FOKUS: Kenapa kamu pilih metode itu?'));
        $this->assertSame('Oke, kebayang. Terus gimana?', $examiner->clean('TANPA PUJIAN Oke, kebayang. Terus gimana?'));
        // Ordinary capitals are not tags.
        $this->assertSame('Pakai metrik AI, NLP: itu jelas.', $examiner->clean('Pakai metrik AI, NLP: itu jelas.'));
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

        // Coaching too: the coach praised every turn in a live session ("Wah, mantap", "Nice").
        $coach = fn (string $line) => ['directive' => ExaminerService::DIRECTIVE_COACH, 'session_type' => 'coach'] + $turn($line);
        $this->assertStringContainsString('[LATIH | TANPA PUJIAN]', $examiner->turnInstruction($coach('Wah, mantap udah pakai contoh.')));
        $this->assertStringContainsString('TANPA PUJIAN = ', $examiner->systemPrompt($coach('x')));
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

    public function test_the_examiner_does_not_answer_its_own_next_question(): void
    {
        $examiner = app(ExaminerService::class);
        $planned = 'Kalau ada temen setim yang bagiannya telat terus, kamu bakal ngapain?';
        $next = ['directive' => ExaminerService::DIRECTIVE_NEXT, 'language' => 'id', 'transition' => 'Bagian 3, Diskusi Mendalam',
            'planned_question' => $planned, 'scripted' => 'Sekarang kita lanjut ke Bagian 3, Diskusi Mendalam. '.$planned];

        // Seen live (gpt-oss): the examiner answered the question itself, then asked about something else.
        $this->assertSame(
            'Oke, target lima tahunmu jelas. Sekarang kita lanjut ke Bagian 3, Diskusi Mendalam. '.$planned,
            $examiner->resolveModelReply('Oke, target lima tahunmu jelas. Kalau temen setim telat, biasanya aku cek dulu apa hambatannya, terus bantu atur prioritas. Sekarang masuk Bagian 3, Diskusi Mendalam. Gimana cara kamu cari jalan tengah itu?', $next)['text'],
        );
        // An ordinary reaction in the first person stays the model's own turn.
        $this->assertSame(
            'Oke, aku catat soal mentoring. Sekarang kita lanjut ke Bagian 3, Diskusi Mendalam. Kalau ada temen setim yang sering telat, kamu bakal gimana?',
            $examiner->resolveModelReply('Oke, aku catat soal mentoring. Sekarang kita lanjut ke Bagian 3, Diskusi Mendalam. Kalau ada temen setim yang sering telat, kamu bakal gimana?', $next)['text'],
        );
    }

    public function test_a_longer_echo_before_the_real_question_does_not_end_the_turn(): void
    {
        $examiner = app(ExaminerService::class);
        $method = new \ReflectionMethod($examiner, 'upToFirstQuestion');

        // Seen live: the turn ended on "Wah, API pelacakan pakai Laravel?" and the real question was cut.
        $this->assertSame(
            'Wah, API pelacakan pakai Laravel? Keren. Bulan pertama mau belajar apa dulu?',
            $method->invoke($examiner, 'Wah, API pelacakan pakai Laravel? Keren. Bulan pertama mau belajar apa dulu? Aku penasaran.'),
        );
        // A short yes/no question with nothing after it is the question.
        $this->assertSame('Kamu yang pimpin timnya?', $method->invoke($examiner, 'Kamu yang pimpin timnya?'));
    }

    public function test_the_cloud_prompt_explains_only_the_tags_of_this_turn(): void
    {
        $examiner = app(ExaminerService::class);
        $turn = $this->turn('hrd', 'VOIC Interview') + [
            'answer' => 'Saya magang enam bulan sebagai backend developer.', 'directive' => ExaminerService::DIRECTIVE_NEXT,
            'planned_question' => 'Apa kelebihanmu?', 'transition' => '', 'part' => 1, 'part_title' => 'Perkenalan', 'skip_link' => true,
            'history' => [], 'question' => 'Boleh kenalan dulu?', 'scripted' => 'Apa kelebihanmu?', 'candidate' => ['name' => 'Rina', 'campus' => null, 'status' => null],
            'session_id' => 1, 'turn' => 1, 'visual' => null,
        ];

        $cloud = $examiner->buildMessages(['cloud' => true] + $turn)[0]['content'];
        $this->assertStringContainsString('[LANJUT: "topik"]', $cloud);
        $this->assertStringNotContainsString('[GALI]', $cloud);
        $this->assertStringNotContainsString('TEGANG', $cloud);
        // The model on this laptop keeps the same system prompt on every turn (its prompt cache).
        $local = $examiner->buildMessages($turn)[0]['content'];
        $this->assertStringContainsString('[GALI]', $local);
        $this->assertStringContainsString('TEGANG', $local);
        $this->assertLessThan(mb_strlen($local) - 800, mb_strlen($cloud));
    }

    public function test_the_local_model_gets_a_compact_prompt_and_the_cloud_the_full_one(): void
    {
        Cache::flush();
        config(['services.groq.key' => 'test-key', 'exam.speed_mode' => 'online', 'exam.local_prompt' => 'compact']);
        $systems = [];
        Http::fake(function ($request) use (&$systems) {
            if (str_contains($request->url(), 'api.groq.com') || str_contains($request->url(), '/exam/respond')) {
                $systems[str_contains($request->url(), 'api.groq.com') ? 'cloud' : 'local'] = (string) ($request->data()['messages'][0]['content'] ?? '');
            }

            // The cloud is out of quota, so the local model answers too.
            return str_contains($request->url(), 'api.groq.com')
                ? Http::response(['error' => ['message' => 'quota']], 503)
                : Http::response(['reply' => 'Magang enam bulan, pengalaman langsung. Sekarang lagi belajar skill apa?', 'passed_quality' => true, 'model' => 'voic-coach']);
        });

        $turn = $this->turn('hrd', 'VOIC-INTERVIEW') + [
            'answer' => 'Saya magang enam bulan sebagai backend developer.', 'directive' => ExaminerService::DIRECTIVE_FOLLOW_UP,
            'planned_question' => null, 'transition' => '', 'part' => 1, 'part_title' => 'Perkenalan', 'skip_focus' => true,
            'history' => [], 'question' => 'Boleh kenalan dulu?', 'scripted' => '', 'candidate' => ['name' => 'Rina', 'campus' => null, 'status' => null],
            'session_id' => 1, 'turn' => 1, 'visual' => null,
        ];
        app(ExaminerService::class)->respond($turn);

        // Full prompt (playbook with style examples) for the cloud, about a sixth of it for the laptop's model.
        $this->assertStringContainsString('Contoh gaya', $systems['cloud']);
        $this->assertStringNotContainsString('Contoh gaya', $systems['local']);
        $this->assertStringContainsString('[GALI]', $systems['local']);
        $this->assertLessThan(mb_strlen($systems['cloud']) / 3, mb_strlen($systems['local']));

        // "full" gives the local model the cloud's prompt again.
        config(['exam.local_prompt' => 'full']);
        app(ExaminerService::class)->respond($turn);
        $this->assertStringContainsString('Contoh gaya', $systems['local']);
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
