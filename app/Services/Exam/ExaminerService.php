<?php

namespace App\Services\Exam;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Produces what the examiner says on each turn.
 *
 * The exam structure (which question comes next, when a part ends) is
 * scripted by ExamFlowService. This class only writes the human part:
 * a short reaction that proves the examiner listened, or a follow-up
 * probe built from the candidate's own words. It tries the local model
 * first, then an optional cloud model, then a rule-based composer.
 */
class ExaminerService
{
    public const DIRECTIVE_FOLLOW_UP = 'follow_up';

    public const DIRECTIVE_REACT = 'react';

    public function __construct(protected SpeakingAnalyzer $analyzer) {}

    /**
     * Generate the examiner's turn.
     *
     * @param  array{
     *     mode: string,
     *     role_type: string,
     *     examiner_name: string,
     *     language: string,
     *     topic: ?string,
     *     context_material: ?string,
     *     part: int,
     *     part_title: string,
     *     directive: string,
     *     question: string,
     *     answer: string,
     *     history: list<array{role: string, content: string}>,
     *     scripted: string,
     *     turn: int,
     *     session_id: int
     * }  $turn
     * @return array{text: string, reply: string, audio_url: ?string, engine: string}
     */
    public function respond(array $turn): array
    {
        $messages = $this->buildMessages($turn);

        $local = $this->callLocalModel($turn, $messages);
        if ($local !== null) {
            return $local;
        }

        $cloud = $this->callCloudModel($messages);
        if ($cloud !== null) {
            return [
                'text' => $this->compose($cloud, $turn),
                'reply' => $this->finalizeReply($cloud, $turn),
                'audio_url' => null,
                'engine' => 'cloud_llm',
            ];
        }

        $offline = $this->offlineReply($turn);

        return [
            'text' => $this->compose($offline, $turn),
            'reply' => $this->finalizeReply($offline, $turn),
            'audio_url' => null,
            'engine' => 'offline_composer',
        ];
    }

    /**
     * Build the chat messages sent to any language model.
     *
     * @param  array<string, mixed>  $turn
     * @return list<array{role: string, content: string}>
     */
    public function buildMessages(array $turn): array
    {
        $messages = [['role' => 'system', 'content' => $this->systemPrompt($turn)]];

        foreach (array_slice($turn['history'], -10) as $item) {
            $messages[] = [
                'role' => $item['role'] === 'user' ? 'user' : 'assistant',
                'content' => Str::limit($item['content'], 700),
            ];
        }

        $messages[] = ['role' => 'user', 'content' => $this->turnInstruction($turn)];

        return $messages;
    }

    /**
     * Persona and speaking rules for the examiner.
     *
     * @param  array<string, mixed>  $turn
     */
    public function systemPrompt(array $turn): string
    {
        $isEn = $turn['language'] === 'en';
        $name = $turn['examiner_name'];
        $topic = $turn['topic'] ? Str::limit((string) $turn['topic'], 120) : null;
        $context = $turn['context_material'] ? Str::limit((string) $turn['context_material'], 500) : null;

        $persona = match ($turn['role_type']) {
            'hrd' => $isEn
                ? "You are {$name}, a warm and relaxed senior HR recruiter. You make candidates feel at ease, you sound like a real person chatting, and you are curious about their real experiences."
                : "Kamu adalah {$name}, HR recruiter senior yang hangat dan santai. Kamu bikin kandidat nyaman, bicaramu seperti orang sungguhan yang sedang ngobrol, dan kamu penasaran dengan pengalaman nyata mereka. Panggil kandidat dengan 'kamu'.",
            'investor' => $isEn
                ? "You are {$name}, a sharp but friendly venture capital partner. You are direct, you love concrete numbers, and you test every claim."
                : "Kamu adalah {$name}, partner venture capital yang tajam tapi bersahabat. Kamu lugas, suka angka konkret, dan menguji setiap klaim. Panggil founder dengan 'Anda'.",
            default => $isEn
                ? "You are {$name}, a calm and fair lead thesis examiner. You respect the student, you speak formally, and you test methodology and reasoning."
                : "Kamu adalah {$name}, dosen penguji utama yang tenang dan adil. Kamu menghargai mahasiswa, bicara dengan bahasa formal yang santun, dan menguji metodologi serta logika penelitian. Panggil mahasiswa dengan 'Anda'.",
        };

        $rules = $isEn
            ? [
                'You are speaking out loud in a live mock exam. Plain spoken sentences only: no lists, no markdown, no emoji, no speaker labels.',
                'Always react to something specific the candidate just said, in your own words. Never use generic praise alone.',
                'Never repeat a question that was already asked in this conversation.',
                'If the answer is vague or very short, gently ask for a concrete example, a number, or the reason behind it.',
                'If the candidate says they do not know, reassure them in one short sentence and help them reason it out.',
                'Never give the full model answer and never score the candidate during the exam.',
            ]
            : [
                'Kamu sedang berbicara langsung dalam simulasi ujian lisan. Gunakan kalimat lisan biasa: tanpa daftar, tanpa markdown, tanpa emoji, tanpa label nama.',
                'Selalu tanggapi hal spesifik yang baru saja dikatakan peserta dengan kata-katamu sendiri. Jangan hanya memuji secara umum.',
                'Jangan pernah mengulang pertanyaan yang sudah ditanyakan di percakapan ini.',
                'Kalau jawabannya kabur atau sangat singkat, minta contoh konkret, angka, atau alasannya dengan sopan.',
                'Kalau peserta bilang tidak tahu, tenangkan dalam satu kalimat pendek lalu bantu mereka berpikir.',
                'Jangan memberikan contoh jawaban lengkap dan jangan memberi nilai selama ujian berlangsung.',
            ];

        $lines = [$persona];
        if ($topic) {
            $lines[] = ($isEn ? 'Session topic: ' : 'Topik sesi: ').$topic;
        }
        if ($context) {
            $lines[] = ($isEn ? 'Background from the candidate: ' : 'Latar dari peserta: ').$context;
        }
        $lines[] = ($isEn ? 'Current section: Part ' : 'Bagian saat ini: Bagian ').$turn['part'].' - '.$turn['part_title'];
        $lines[] = implode("\n", array_map(fn ($rule, $i) => ($i + 1).'. '.$rule, $rules, array_keys($rules)));

        return implode("\n", $lines);
    }

    /**
     * The final user message: the candidate answer plus what to produce.
     *
     * @param  array<string, mixed>  $turn
     */
    public function turnInstruction(array $turn): string
    {
        $isEn = $turn['language'] === 'en';
        $answer = trim((string) $turn['answer']) !== '' ? trim((string) $turn['answer']) : ($isEn ? '(no answer)' : '(tidak menjawab)');

        $task = $turn['directive'] === self::DIRECTIVE_FOLLOW_UP
            ? ($isEn
                ? 'Reply in at most 2 sentences: a short specific reaction, then exactly ONE follow-up question that digs deeper into this answer (why, how, an example, or a number).'
                : 'Balas maksimal 2 kalimat: tanggapan singkat yang spesifik, lalu tepat SATU pertanyaan lanjutan yang menggali jawaban ini lebih dalam (kenapa, bagaimana, contoh, atau angka).')
            : ($isEn
                ? 'Reply with ONE short sentence reacting specifically to this answer. Do not ask any question, the next question is read by the system.'
                : 'Balas dengan SATU kalimat pendek yang menanggapi jawaban ini secara spesifik. Jangan bertanya apa pun, pertanyaan berikutnya dibacakan oleh sistem.');

        return ($isEn ? 'Question: ' : 'Pertanyaan: ').$turn['question']."\n"
            .($isEn ? 'Candidate answer: ' : 'Jawaban peserta: ').$answer."\n"
            .'['.$task.']';
    }

    /**
     * Append the scripted continuation (next question, part transition, closing).
     *
     * @param  array<string, mixed>  $turn
     */
    public function compose(string $reply, array $turn): string
    {
        $reply = $this->finalizeReply($reply, $turn);
        $scripted = trim((string) ($turn['scripted'] ?? ''));

        return trim($reply.($scripted !== '' ? ' '.$scripted : ''));
    }

    /**
     * Clean a model reply and enforce the directive: no question on a reaction, exactly one on a follow-up.
     *
     * @param  array<string, mixed>  $turn
     */
    public function finalizeReply(string $reply, array $turn): string
    {
        $reply = $this->clean($reply);

        if ($turn['directive'] === self::DIRECTIVE_REACT) {
            // A reaction must not carry its own question; the script supplies it.
            $reply = $this->stripQuestions($reply);
            if ($reply === '') {
                $reply = $this->offlineReaction($turn);
            }
        } elseif (! str_contains($reply, '?')) {
            $reply = trim($reply.' '.$this->offlineProbe($turn));
        }

        return $reply;
    }

    /**
     * Remove markdown, labels and stray whitespace from model output.
     */
    public function clean(string $text): string
    {
        $text = preg_replace('/[\*#_`~]+/u', '', $text) ?? $text;
        $text = preg_replace('/^\s*[\-•\d]+[\.\)]\s*/mu', '', $text) ?? $text;
        $text = preg_replace('/^\s*[\p{L}\.\s]{2,30}:\s+/u', '', trim($text)) ?? $text;
        $text = preg_replace('/\[[^\]]*\]/u', '', $text) ?? $text;
        $text = trim(preg_replace('/\s+/u', ' ', $text) ?? $text, " \"'");

        $sentences = preg_split('/(?<=[.!?])\s+/u', $text) ?: [];

        return trim(implode(' ', array_slice($sentences, 0, 3)));
    }

    /**
     * Drop sentences ending with a question mark.
     */
    protected function stripQuestions(string $text): string
    {
        $sentences = preg_split('/(?<=[.!?])\s+/u', $text) ?: [];
        $kept = array_filter($sentences, fn ($sentence) => ! str_ends_with(trim($sentence), '?'));

        return trim(implode(' ', array_slice(array_values($kept), 0, 2)));
    }

    /**
     * Call the local FastAPI + Ollama service.
     *
     * @param  array<string, mixed>  $turn
     * @param  list<array{role: string, content: string}>  $messages
     * @return array{text: string, reply: string, audio_url: ?string, engine: string}|null
     */
    protected function callLocalModel(array $turn, array $messages): ?array
    {
        $baseUrl = (string) Config::get('services.ai_service.url', 'http://127.0.0.1:8001');

        try {
            $response = Http::connectTimeout(1.5)
                ->timeout((int) Config::get('services.ai_service.timeout', 35))
                ->post("{$baseUrl}/exam/respond", [
                    'session_id' => $turn['session_id'],
                    'mode' => $turn['mode'],
                    'role_type' => $turn['role_type'],
                    'language' => $turn['language'],
                    'directive' => $turn['directive'],
                    'part' => $turn['part'],
                    'question' => $turn['question'],
                    'answer' => $turn['answer'],
                    'messages' => $messages,
                ]);

            $reply = trim((string) $response->json('reply', ''));
            if (! $response->successful() || $reply === '') {
                return null;
            }

            $text = $this->compose($reply, $turn);

            return [
                'text' => $text,
                'reply' => $this->finalizeReply($reply, $turn),
                'audio_url' => $this->synthesize($baseUrl, $text, $turn),
                'engine' => 'local_llm',
            ];
        } catch (\Throwable $exception) {
            Log::info('Local examiner model unavailable: '.$exception->getMessage());

            return null;
        }
    }

    /**
     * Ask the local service for neural TTS audio of the final text.
     *
     * @param  array<string, mixed>  $turn
     */
    protected function synthesize(string $baseUrl, string $text, array $turn): ?string
    {
        try {
            $response = Http::connectTimeout(1.0)->timeout(5)->post("{$baseUrl}/tts", [
                'text' => $text,
                'role_type' => $turn['role_type'],
                'language' => $turn['language'],
            ]);

            $file = $response->json('audio_file');

            return $response->successful() && $file ? "{$baseUrl}/audio/{$file}" : null;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Call Groq or OpenAI when keys are configured.
     *
     * @param  list<array{role: string, content: string}>  $messages
     */
    protected function callCloudModel(array $messages): ?string
    {
        $providers = [
            ['key' => (string) Config::get('services.groq.key', ''), 'url' => 'https://api.groq.com/openai/v1/chat/completions', 'model' => (string) Config::get('services.groq.model', 'llama-3.3-70b-versatile')],
            ['key' => (string) Config::get('services.openai.key', ''), 'url' => 'https://api.openai.com/v1/chat/completions', 'model' => (string) Config::get('services.openai.model', 'gpt-4o-mini')],
        ];

        foreach ($providers as $provider) {
            if ($provider['key'] === '') {
                continue;
            }

            try {
                $response = Http::timeout(12)->withToken($provider['key'])->post($provider['url'], [
                    'model' => $provider['model'],
                    'messages' => $messages,
                    'temperature' => 0.75,
                    'max_tokens' => 140,
                ]);

                $reply = trim((string) $response->json('choices.0.message.content', ''));
                if ($response->successful() && $reply !== '') {
                    return $reply;
                }
            } catch (\Throwable $exception) {
                Log::warning('Cloud examiner model failed: '.$exception->getMessage());
            }
        }

        return null;
    }

    /**
     * Rule-based reply used when no model is reachable.
     *
     * @param  array<string, mixed>  $turn
     */
    public function offlineReply(array $turn): string
    {
        $reaction = $this->offlineReaction($turn);

        if ($turn['directive'] === self::DIRECTIVE_FOLLOW_UP) {
            $phrase = $this->analyzer->keyPhrase((string) $turn['answer'], $turn['mode']);

            return trim($reaction.' '.$this->offlineProbe($turn, $phrase === null || ! str_contains($reaction, $phrase)));
        }

        return $reaction;
    }

    /**
     * A short reaction that echoes the candidate's own words.
     *
     * @param  array<string, mixed>  $turn
     */
    public function offlineReaction(array $turn): string
    {
        $isEn = $turn['language'] === 'en';
        $answer = (string) $turn['answer'];
        $wordCount = count($this->analyzer->words($this->analyzer->normalize($answer)));
        $phrase = $this->analyzer->keyPhrase($answer, $turn['mode']);
        $turnIndex = (int) $turn['turn'];

        if ($this->analyzer->soundsUnsure($answer)) {
            return $isEn
                ? 'That is completely fine, take a breath, there is no wrong answer here.'
                : ($turn['role_type'] === 'hrd' ? 'Santai aja, nggak apa-apa kok, nggak ada jawaban yang salah di sini.' : 'Tidak apa-apa, tenang saja, kita coba pelan-pelan.');
        }

        if ($wordCount < 6) {
            return $isEn ? 'Okay, that was quite brief.' : 'Oke, jawabannya masih cukup singkat ya.';
        }

        $templates = $phrase !== null
            ? $this->reactionTemplates($turn['role_type'], $isEn, true)
            : $this->reactionTemplates($turn['role_type'], $isEn, false);

        $template = $templates[$turnIndex % count($templates)];

        return str_replace(':phrase', (string) $phrase, $template);
    }

    /**
     * A follow-up question chosen from what the answer is missing.
     *
     * @param  array<string, mixed>  $turn
     */
    public function offlineProbe(array $turn, bool $echoPhrase = true): string
    {
        $isEn = $turn['language'] === 'en';
        $metrics = $this->analyzer->analyzeAnswer((string) $turn['answer'], 0, (string) $turn['question'], $turn['mode']);
        $phrase = $echoPhrase ? $this->analyzer->keyPhrase((string) $turn['answer'], $turn['mode']) : null;
        $about = $phrase !== null ? ($isEn ? " about {$phrase}" : " soal {$phrase}") : '';
        $you = $turn['role_type'] === 'hrd' ? 'kamu' : 'Anda';

        $example = match ($turn['role_type']) {
            'investor' => [$isEn ? "Can you give me a real case{$about} from an actual customer?" : "Bisa beri contoh kasus nyata{$about} dari pelanggan sungguhan?"],
            'dosen_penguji' => [$isEn ? "Could you elaborate{$about} with a concrete example from your research?" : "Bisa Anda jelaskan lebih rinci{$about}, dengan contoh konkret dari penelitian Anda?"],
            default => [$isEn ? "Can you walk me through a specific moment{$about}? What exactly did you do?" : "Boleh ceritakan momen spesifiknya{$about}? Apa yang {$you} lakukan persisnya?"],
        };
        $evidence = match ($turn['role_type']) {
            'investor' => [$isEn ? 'What numbers back that up today?' : 'Angka apa yang mendukung klaim itu saat ini?'],
            'dosen_penguji' => [$isEn ? 'How did you measure that, and what were the results?' : 'Bagaimana Anda mengukurnya, dan berapa hasilnya?'],
            default => [$isEn ? 'What was the result in the end, can you put a number on it?' : 'Terus hasil akhirnya gimana, ada angka atau dampak yang bisa disebut?'],
        };
        $depth = match ($turn['role_type']) {
            'investor' => $isEn
                ? ['And what happens to the business if that assumption turns out wrong?', 'Why would a customer pay for this instead of doing nothing?', 'What would you need from an investor to prove that faster?']
                : ['Lalu apa yang terjadi pada bisnis Anda kalau asumsi itu ternyata salah?', 'Kenapa pelanggan mau membayar untuk ini, bukan tetap pakai cara lama?', 'Apa yang Anda butuhkan dari investor untuk membuktikan itu lebih cepat?'],
            'dosen_penguji' => $isEn
                ? ['What is the main limitation of that approach?', 'Why is that approach more suitable than the alternatives?', 'How would your conclusion change if the data were different?']
                : ['Lalu apa keterbatasan utama dari pendekatan tersebut?', 'Mengapa pendekatan itu lebih tepat dibanding alternatif lainnya?', 'Bagaimana kesimpulan Anda berubah jika datanya berbeda?'],
            default => $isEn
                ? ['If you could redo it, what would you do differently?', 'How did the people around you react to that?', 'What did that experience teach you about yourself?']
                : ['Kalau bisa diulang, apa yang bakal kamu lakukan berbeda?', 'Terus gimana respons orang-orang di sekitarmu waktu itu?', 'Dari pengalaman itu, apa yang kamu pelajari tentang dirimu sendiri?'],
        };

        $elaborate = match ($turn['role_type']) {
            'investor' => $isEn
                ? ['Tell me a bit more, why is that?', 'Can you give me one concrete example?']
                : ['Bisa dijelaskan sedikit lebih jauh, kenapa begitu?', 'Bisa beri satu contoh konkretnya?'],
            'dosen_penguji' => $isEn
                ? ['Could you elaborate a little further?', 'What is the reasoning behind that?']
                : ['Bisa Anda uraikan sedikit lebih lengkap?', 'Apa alasan di balik hal tersebut?'],
            default => $isEn
                ? ['Tell me a bit more, I would love to get to know you better.', 'Why is that, is there a small example?']
                : ['Boleh cerita sedikit lebih lengkap? Saya pengen kenal kamu lebih jauh.', 'Kenapa begitu? Ada contoh kecilnya?'],
        };

        // Part 1 is a warm-up: just invite more. Later parts ask for what is missing, then go deeper.
        $candidates = match (true) {
            (int) ($turn['part'] ?? 0) === 1 => [...$elaborate, ...$example],
            $metrics['word_count'] < 12 || ! $metrics['has_example'] => [...$example, ...$evidence, ...$depth],
            ! $metrics['has_numbers'] => [...$evidence, ...$depth, ...$example],
            default => [...$depth, ...$evidence, ...$example],
        };

        $asked = implode(' ', array_map(fn ($item) => $item['content'], array_filter($turn['history'] ?? [], fn ($item) => $item['role'] !== 'user')));

        foreach ($candidates as $candidate) {
            if (! str_contains($asked, $candidate)) {
                return $candidate;
            }
        }

        return end($candidates) ?: '';
    }

    /**
     * Rotating reaction templates per persona.
     *
     * @return list<string>
     */
    protected function reactionTemplates(string $roleType, bool $isEn, bool $withPhrase): array
    {
        if ($isEn) {
            return $withPhrase
                ? ['Thanks, the part about :phrase is clear.', 'Right, :phrase, I see where you are going.', 'Interesting point on :phrase.', 'Okay, I have noted :phrase.']
                : ['Thank you, that is clear.', 'Right, I see.', 'Okay, understood.', 'Thanks for sharing that.'];
        }

        return match ($roleType) {
            'hrd' => $withPhrase
                ? ['Oke, menarik juga soal :phrase.', 'Wah, bagian :phrase itu kebayang banget.', 'Siap, saya catat soal :phrase ya.', 'Oke, :phrase, paham.']
                : ['Oke, makasih ya ceritanya.', 'Siap, kebayang kok.', 'Oke, paham.', 'Makasih sudah cerita.'],
            'investor' => $withPhrase
                ? ['Oke, :phrase. Noted.', 'Baik, poin :phrase saya tangkap.', 'Menarik, :phrase.', 'Oke, saya catat :phrase.']
                : ['Oke, noted.', 'Baik, saya tangkap.', 'Oke, jelas.', 'Baik.'],
            default => $withPhrase
                ? ['Baik, saya catat penjelasan Anda mengenai :phrase.', 'Terima kasih, bagian :phrase cukup jelas.', 'Baik, :phrase, saya pahami.', 'Poin Anda tentang :phrase sudah saya catat.']
                : ['Baik, terima kasih atas penjelasannya.', 'Baik, saya pahami.', 'Terima kasih.', 'Baik, cukup jelas.'],
        };
    }
}
