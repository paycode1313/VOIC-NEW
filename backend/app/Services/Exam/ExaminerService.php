<?php

namespace App\Services\Exam;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Sleep;
use Illuminate\Support\Str;

/**
 * Produces what the examiner says on each turn.
 *
 * The language model writes the whole turn in its own words: it reacts to
 * what the candidate actually said and then moves the conversation on,
 * either by digging deeper (follow_up), by bringing up the next planned
 * topic (next), or by re-explaining a question the candidate did not get
 * (clarify). The exam plan only tells the model *what* to cover next; the
 * scripted wording is a fallback used when no model is reachable or the
 * model output fails validation. Order: local model, cloud model, offline.
 */
class ExaminerService
{
    /** Dig deeper into the current answer. */
    public const DIRECTIVE_FOLLOW_UP = 'follow_up';

    /** React, then raise the next planned topic in the model's own words. */
    public const DIRECTIVE_NEXT = 'next';

    /** React only; a fixed announcement follows (cue card rules, closing). */
    public const DIRECTIVE_REACT = 'react';

    /** The candidate asked what the question means: explain and ask again. */
    public const DIRECTIVE_CLARIFY = 'clarify';

    /** Coaching conversation: react, teach one concrete thing, keep talking. */
    public const DIRECTIVE_COACH = 'coach';

    /** Coaching time is up: warm wrap-up, no question. */
    public const DIRECTIVE_COACH_CLOSE = 'coach_close';

    /**
     * Messages sent word for word; anything older is condensed by sessionMemory().
     */
    public const RECENT_MESSAGES = 24;

    /**
     * Older messages move into the memory block this many at a time. The system prompt (the start
     * of the prompt) then stays identical for many turns, so Ollama reuses its cache and only
     * reads the new answer: on a laptop CPU, re-reading the whole prompt costs 20-35 seconds.
     * A normal 12-minute exam never reaches the first step.
     */
    public const MEMORY_STEP = 12;

    /**
     * Characters of word-for-word history that safely fit the model's 4096-token context next to
     * the persona, the memory block and the reply (Indonesian is roughly 3.3 characters per token).
     */
    public const HISTORY_CHAR_BUDGET = 7500;

    /**
     * Characters for the system prompt (persona, playbook, memory) plus the word-for-word history
     * together. With the current turn and the reply on top this stays under 4096 tokens.
     */
    public const CONTEXT_CHAR_BUDGET = 11000;

    /**
     * Longest wait (seconds) for a cloud quota that frees up again before giving up on the cloud model.
     */
    public const CLOUD_RETRY_WAIT = 6;

    /** After the internet drops, skip the cloud for this long so every turn goes straight to the local AI. */
    public const CLOUD_DOWN_KEY = 'voic.cloud.down';

    public const CLOUD_DOWN_SECONDS = 30;

    /**
     * How each examiner talks out loud: spoken language like a real call, not the written language of a
     * letter or a thesis. HRD is the most casual, the investor semi-casual and direct, the thesis examiner
     * courteous but still spoken. Part of the cached system prompt.
     *
     * @var array<string, array{id: string, en: string}>
     */
    public const SPEAKING_STYLE = [
        'hrd' => [
            'id' => 'Gaya bahasamu: ceria, ekspresif, dan hangat, seperti HR yang ramah dan heboh dengan cara yang menyenangkan, bukan bahasa surat resmi. '
                .'Panggil kandidat dengan "kamu" dan dirimu "aku". Pakai kata seperti "nggak", "udah", "gimana", "banget", "dikit", "beneran", dan partikel "sih", "nih", "ya", "kok", "dong" secukupnya. '
                .'Tunjukkan antusiasme dengan reaksi spontan yang berganti-ganti, misalnya "Oh ya?", "Hmm, oke, kebayang", "Wah, seru!", "Eh, bentar, aku penasaran", atau "Oke, aku tagih buktinya ya" (jangan pakai kata pembuka yang sama dua kali berturut-turut, dan jangan memuji di setiap giliran). '
                .'Hindari kata kaku seperti "tersebut", "merupakan", "apakah", "Anda", "sangat", "mengenai", "demikian". '
                .'Kalimatmu pendek-pendek dan intonasinya hidup, boleh pakai tanda seru sesekali.',
            'en' => 'Your style: bubbly, expressive and warm, like a cheerful recruiter on a call. Use contractions, short sentences and lively reactions that keep changing, such as "Oh really?", "Hmm, okay, I can picture it", "Oh wow" or "Okay, I want proof"; do not praise every turn, and avoid formal written phrases.',
        ],
        'investor' => [
            'id' => 'Gaya bahasamu: muda, cepat, energik, dan blak-blakan seperti investor muda yang lagi ngopi sama founder, bukan bahasa proposal. '
                .'Panggil founder dengan "kamu" dan dirimu "saya". Pakai "gimana", "nggak", "udah", "aja", "bener", "banget", dan partikel "sih", "tuh", "ya", "dong" secukupnya. '
                .'Kamu suka bicara soal uang dengan istilah yang membumi seperti "cuan", "bakar duit", "valuasi", "scale up", "untung beneran", dan berani bilang terus terang kalau angka terasa nggak masuk akal (tetap sopan, jangan merendahkan). '
                .'Hindari kata kaku seperti "Anda", "tersebut", "merupakan", "apakah", "mengenai", "demikian", "adapun", "sangat". '
                .'Kalimatmu pendek, cepat, dan to the point, boleh diawali reaksi spontan seperti "Oke, oke", "Bentar", "Nah ini", atau "Jujur ya" (jangan pakai kata pembuka yang sama dua kali berturut-turut).',
            'en' => 'Your style: young, fast, energetic and blunt, like an investor chatting with a founder over coffee. Use contractions and short sentences, talk about money plainly, and say it straight when a number does not add up; avoid formal written phrases.',
        ],
        'dosen_penguji' => [
            'id' => 'Gaya bahasamu: berwibawa, tegas, dan tenang, seperti dosen senior yang disegani tapi tetap peduli pada mahasiswanya, bukan bahasa tulisan skripsi. '
                .'Panggil mahasiswa dengan "kamu" dan dirimu "saya". Bicaralah perlahan dengan kalimat pendek yang mantap; beri penekanan dengan mengulang kata kunci, misalnya "Datanya, datanya dulu yang saya tanya." '
                .'Pakai "coba", "nah", "begini", "ya", "kenapa", "gimana", "nggak", "udah" dengan wajar. Boleh diawali reaksi singkat seperti "Baik.", "Begini ya.", "Saya tegaskan ya," atau "Oke, saya catat" (jangan pakai kata pembuka yang sama dua kali berturut-turut). '
                .'Hindari kata kaku seperti "Anda", "tersebut", "merupakan", "demikian", "adapun", "apakah", "mengenai", "sangat".',
            'en' => 'Your style: authoritative, firm and calm, like a respected senior examiner speaking in the room. Slow, short, deliberate sentences, with emphasis by repeating the key word; no stiff written phrases.',
        ],
    ];

    /**
     * The shared "cool but kind" layer on top of every examiner's own character: witty, quick and
     * charismatic like a good voice companion, but never rude. Part of the cached system prompt.
     *
     * @var array{id: string, en: string}
     */
    public const VIBE = [
        'id' => 'Vibe kamu: keren, cerdas, dan asyik diajak ngobrol, seperti teman yang karismatik, tapi selalu baik dan menghargai. '
            .'Responsmu cepat dan padat: biasanya dua kalimat pendek, jangan bertele-tele. '
            .'Boleh pakai humor ringan, perumpamaan yang segar, atau sesekali pujian yang spesifik dan nggak lebay (misalnya "Nah, itu jawaban yang solid"), tapi pujian yang terlalu sering malah terdengar palsu. '
            .'Pakai paling banyak satu ungkapan gaul per giliran supaya tetap natural, nggak maksa. '
            .'Jangan pernah kasar, menyindir, merendahkan, atau berkata kotor; kalau peserta bercanda atau kasar, tanggapi dengan santai dan tetap sopan.',
        'en' => 'Your vibe: cool, clever and fun to talk to, like a charismatic friend, but always kind and respectful. '
            .'Reply fast and tight: usually two short sentences, no rambling. '
            .'Light humour, a fresh analogy or, now and then, a specific, not over-the-top compliment is welcome ("Now that is a solid answer"), but praise in every turn sounds fake. '
            .'At most one slang phrase per turn so it stays natural. '
            .'Never be rude, sarcastic, belittling or swear; if the candidate jokes or is rude, stay relaxed and polite.',
    ];

    /**
     * Tiny reactions the examiner says the moment the candidate stops talking, while the answer is still
     * being transcribed and thought about, so the call never goes silent. Mostly hums, so they do not clash
     * with the opening words of the real reply.
     *
     * @var array<string, array{id: list<string>, en: list<string>}>
     */
    public const BACKCHANNELS = [
        'hrd' => ['id' => ['Hmm…', 'Ooh…', 'Mm, oke…'], 'en' => ['Hmm…', 'Ooh…', 'Mm, okay…']],
        'investor' => ['id' => ['Hmm.', 'Oke, oke.', 'Hmm, oke.'], 'en' => ['Hmm.', 'Okay, okay.', 'Hmm, right.']],
        'dosen_penguji' => ['id' => ['Hmm…', 'Baik…', 'Hmm. Baik.'], 'en' => ['Hmm…', 'I see…', 'Hmm. Right.']],
    ];

    /**
     * Standard spelling (KBBI) for the examiner's Indonesian, so the subtitles and the voice use the
     * correct form of loanwords while the tone stays spoken. Part of the cached system prompt.
     */
    public const KBBI_RULE = 'Ejaan kata baku mengikuti KBBI walau gaya bicaramu santai: tulis "praktik" (bukan praktek), "analisis" (bukan analisa), "risiko" (bukan resiko), "sistem", "aktivitas", "kualitas", "efektif", "konkret", "teknik", "objek", "izin", "jadwal", "sekadar", "mengubah" (bukan merubah). '
        .'Tulis angka sebagai angka (12 bulan, 50 responden, 30 persen) dan istilah asing yang memang lazim boleh tetap dipakai.';

    /**
     * Where the time of the last streamed reply went (reading the prompt, voicing the first sentence).
     *
     * @var array<string, int>
     */
    protected array $lastTimings = [];

    /**
     * Model chosen for this process instead of config('exam.ai_model') (used by the teacher command).
     */
    protected ?string $modelOverride = null;

    /**
     * Ollama model that produced the last reply from the AI service.
     */
    protected ?string $lastModel = null;

    public function __construct(protected SpeakingAnalyzer $analyzer, protected ExamBlueprint $blueprint, protected OpenRouterClient $openRouter, protected ConversationSignals $signals) {}

    /**
     * Model name for the AI service (Ollama). OpenRouter models are called from here instead.
     */
    public function ollamaModel(): ?string
    {
        $model = $this->examinerModel();

        return OpenRouterClient::handles($model) ? null : $model;
    }

    /**
     * The OpenRouter client, so the teacher command can read why a call failed.
     */
    public function openRouter(): OpenRouterClient
    {
        return $this->openRouter;
    }

    /**
     * Use a specific Ollama model for the examiner from now on (null = back to the configured one).
     */
    public function useModel(?string $model): void
    {
        $this->modelOverride = $model;
    }

    /**
     * Ollama model the examiner should use; null lets the AI service pick its local model.
     */
    public function examinerModel(): ?string
    {
        $model = $this->modelOverride ?? Config::get('exam.ai_model');

        return is_string($model) && trim($model) !== '' ? trim($model) : null;
    }

    /**
     * Models that run on someone else's servers: Ollama ":cloud" models and OpenRouter models.
     */
    public static function isCloudModel(?string $model): bool
    {
        $model = strtolower((string) $model);

        return str_ends_with($model, ':cloud') || str_ends_with($model, '-cloud') || OpenRouterClient::handles($model);
    }

    /**
     * Engine label for a reply that came through the AI service.
     */
    protected function engineFor(?string $model): string
    {
        return self::isCloudModel($model) ? 'ollama_cloud' : 'local_llm';
    }

    /**
     * Condensed notes on the part of the conversation that no longer fits word for word,
     * so a small local model still remembers what the candidate said and what was asked.
     *
     * @param  list<array{role: string, content: string}>  $older
     */
    public function sessionMemory(array $older, bool $isEn): string
    {
        $said = [];
        $asked = [];
        foreach ($older as $item) {
            $content = trim(preg_replace('/\s+/', ' ', (string) $item['content']) ?? '');
            if ($content === '') {
                continue;
            }
            if ($item['role'] === 'user') {
                $said[] = Str::limit($content, 160);
            } elseif (preg_match_all('/[^.?!]*\?/u', $content, $questions) && $questions[0] !== []) {
                $asked[] = Str::limit(trim((string) end($questions[0])), 120);
            }
        }

        $said = array_slice($said, -8);
        $asked = array_slice($asked, -8);
        if ($said === [] && $asked === []) {
            return '';
        }

        $lines = ["\n".($isEn ? 'Earlier in this session (remember it and refer back when it fits):' : 'Sebelumnya di sesi ini (ingat dan rujuk kembali bila cocok):')];
        foreach ($said as $line) {
            $lines[] = ($isEn ? '- Candidate said: ' : '- Peserta berkata: ').$line;
        }
        foreach ($asked as $line) {
            $lines[] = ($isEn ? '- You already asked: ' : '- Kamu sudah bertanya: ').$line;
        }

        return implode("\n", $lines);
    }

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
     *     planned_question?: ?string,
     *     transition?: ?string,
     *     turn: int,
     *     session_id: int,
     *     visual?: ?array{eye_contact?: float|int, face_visible?: float|int, smile?: float|int, tension?: float|int, samples?: int}
     * }  $turn
     * @param  (callable(string, ?string): void)|null  $onSentence  Receives each sentence (text, audio URL) as soon as it can be spoken.
     * @return array{text: string, reply: string, audio_url: ?string, engine: string}
     */
    public function respond(array $turn, ?callable $onSentence = null): array
    {
        $messages = $this->buildMessages($turn);
        // The local model gets the compact prompt (see compactPrompt()); built only when the local model is asked.
        $localMessages = null;
        $forLocal = function () use (&$localMessages, $turn): array {
            return $localMessages ??= $this->buildMessages($this->forLocal($turn));
        };
        $baseUrl = $this->baseUrl();

        // An OpenRouter model (e.g. the free Kimi K2.6) answers first; on failure the local chain takes over.
        $chosen = $this->examinerModel();
        if (OpenRouterClient::handles($chosen)) {
            $remote = $this->openRouter->chat($messages, (string) $chosen);
            if ($remote !== null && ($resolved = $this->resolveModelReply($remote, $turn)) !== null) {
                $result = $resolved + ['audio_url' => null, 'engine' => 'openrouter'];
                if ($onSentence !== null) {
                    $onSentence($result['text'], $this->synthesize($baseUrl, $result['text'], $turn, inline: true));
                }

                return $result;
            }
        }

        // Speed mode "online": a fast cloud model answers first (about a second); when it fails
        // (no internet, quota), the local chain below takes over as if nothing happened.
        if ($this->onlineFirst()) {
            $cloud = $this->callCloudModel($messages, timeout: 8);
            if ($cloud !== null && ($resolved = $this->resolveModelReply($cloud, $turn)) !== null) {
                $result = $resolved + ['audio_url' => null, 'engine' => 'cloud_llm'];
                if ($onSentence !== null) {
                    // Voice sentence by sentence, so the first words are heard while the rest is still being synthesized.
                    foreach ($this->sentences($result['text']) as $sentence) {
                        $onSentence($sentence, $this->synthesize($baseUrl, $sentence, $turn, inline: true));
                    }
                } else {
                    $result['audio_url'] = $this->synthesize($baseUrl, $result['text'], $turn);
                }

                return $result;
            }
        }

        // Streaming: sentences are spoken while the model is still writing. A pure reaction
        // is followed by a fixed announcement, so it is not worth streaming.
        if ($onSentence !== null && $turn['directive'] !== self::DIRECTIVE_REACT) {
            $streamed = $this->streamLocalModel($turn, $forLocal(), $onSentence);
            if ($streamed !== null) {
                return $streamed;
            }
        }

        $result = null;
        $local = $this->callLocalModel($turn, $forLocal());
        if ($local !== null && $this->resolveModelReply($local, $turn) === null) {
            Log::info('Local examiner reply unusable for '.$turn['directive'].': '.Str::limit($local, 200));
        }
        if ($local !== null && ($resolved = $this->resolveModelReply($local, $turn)) !== null) {
            $result = $resolved + ['audio_url' => $this->synthesize($baseUrl, $resolved['text'], $turn), 'engine' => $this->engineFor($this->lastModel)];
        }

        if ($result === null) {
            $cloud = $this->callCloudModel($messages);
            if ($cloud !== null && ($resolved = $this->resolveModelReply($cloud, $turn)) !== null) {
                $result = $resolved + ['audio_url' => null, 'engine' => 'cloud_llm'];
            }
        }

        $result ??= $this->offlineTurn($turn) + ['audio_url' => null, 'engine' => 'offline_composer'];

        if ($onSentence !== null) {
            $audio = $result['audio_url'] ?? $this->synthesize($baseUrl, $result['text'], $turn, inline: true);
            $onSentence($result['text'], $audio);
        }

        return $result;
    }

    /**
     * Stream the turn from the local model sentence by sentence.
     * Returns null when nothing was spoken, so the caller can fall back.
     *
     * @param  array<string, mixed>  $turn
     * @param  list<array{role: string, content: string}>  $messages
     * @return array{text: string, reply: string, audio_url: ?string, engine: string}|null
     */
    protected function streamLocalModel(array $turn, array $messages, callable $onSentence): ?array
    {
        $this->lastTimings = [];
        $spoken = [];
        $firstAudio = null;
        $model = null;

        try {
            $response = Http::connectTimeout(1.5)
                ->timeout((int) Config::get('services.ai_service.timeout', 35))
                ->withOptions(['stream' => true])
                ->post($this->baseUrl().'/exam/respond/stream', [
                    'session_id' => $turn['session_id'],
                    'mode' => $turn['mode'],
                    'role_type' => $turn['role_type'],
                    'language' => $turn['language'],
                    'directive' => $turn['directive'],
                    'part' => $turn['part'],
                    'question' => $turn['question'],
                    'answer' => $turn['answer'],
                    'messages' => $messages,
                    'model' => $this->ollamaModel(),
                    'generate_voice' => true,
                    // Audio travels inside the stream: no extra request while the stream is open.
                    'inline_audio' => true,
                ]);

            if (! $response->successful()) {
                return null;
            }

            $body = $response->toPsrResponse()->getBody();
            $buffer = '';
            while (! $body->eof()) {
                $buffer .= $body->read(1024);
                while (($newline = strpos($buffer, "\n")) !== false) {
                    $event = json_decode(substr($buffer, 0, $newline), true);
                    $buffer = substr($buffer, $newline + 1);

                    if (($event['type'] ?? null) === 'done') {
                        $model = $event['model'] ?? null;
                        $this->lastTimings = array_filter([
                            'llm_first_token_ms' => $event['first_token_ms'] ?? null,
                            'tts_first_ms' => $event['first_tts_ms'] ?? null,
                        ], fn ($value) => $value !== null);
                    }

                    if (($event['type'] ?? null) === 'sentence' && trim((string) $event['text']) !== '') {
                        $text = $this->clean((string) $event['text'], 5);
                        $audioUrl = $this->audioSource($event['audio_data'] ?? null, $event['audio_file'] ?? null);
                        if ($audioUrl !== null && ! str_starts_with($audioUrl, 'data:')) {
                            $firstAudio ??= $audioUrl;
                        }
                        $spoken[] = $text;
                        $onSentence($text, $audioUrl);
                    }
                }
            }
        } catch (\Throwable $exception) {
            Log::info('Local examiner stream unavailable: '.$exception->getMessage());
        }

        if ($spoken === []) {
            return null;
        }

        $reply = trim(implode(' ', $spoken));

        // The model must end an asking turn with a question; if it did not, finish it for it.
        // Coaching turns may end on a tip or an invitation to try again, so they are left as they are.
        if (! str_contains($reply, '?') && ! self::isCoachDirective($turn['directive'])) {
            $tail = match ($turn['directive']) {
                self::DIRECTIVE_NEXT => trim((string) ($turn['planned_question'] ?? '')) ?: trim((string) $turn['scripted']),
                self::DIRECTIVE_CLARIFY => $this->offlineClarify($turn),
                default => $this->offlineProbe($turn),
            };
            if ($tail !== '') {
                $onSentence($tail, $this->synthesize($this->baseUrl(), $tail, $turn, inline: true));
                $reply = trim($reply.' '.$tail);
            }
        }

        return ['text' => $reply, 'reply' => $reply, 'audio_url' => $firstAudio, 'engine' => $this->engineFor($model)];
    }

    /**
     * Timings of the last streamed reply, for voic:latency.
     *
     * @return array<string, int>
     */
    public function lastTimings(): array
    {
        return $this->lastTimings;
    }

    /**
     * Let the local model read the start of a session's prompt (persona, rules, opening line) right
     * now, in the background. When the candidate finishes their first answer the model then only
     * reads that answer; without this the first turn waits 25-45 seconds on a laptop CPU.
     * Never blocks and never fails the session: it is only a head start.
     *
     * @param  array<string, mixed>  $turn  The same fields a real turn has (history = the opening line)
     */
    public function prime(array $turn): void
    {
        $model = $this->ollamaModel();
        if (OpenRouterClient::handles($model) || self::isCloudModel($model)) {
            return;
        }

        try {
            $messages = $this->buildMessages($this->forLocal($turn + ['answer' => '', 'directive' => self::DIRECTIVE_REACT, 'planned_question' => null, 'transition' => null]));
            // Everything except the last message (the answer that does not exist yet) is the shared start.
            $messages[count($messages) - 1]['content'] = '.';
            Http::connectTimeout(1)->timeout(3)->post($this->baseUrl().'/exam/prime', ['messages' => $messages, 'model' => $model]);
        } catch (\Throwable $exception) {
            Log::info('Session priming skipped: '.$exception->getMessage());
        }
    }

    /**
     * Voice a fixed line (opening greeting) with the local TTS, if available.
     */
    public function speak(string $text, string $roleType, string $language): ?string
    {
        return $this->synthesize($this->baseUrl(), $text, ['role_type' => $roleType, 'language' => $language]);
    }

    /**
     * Playable source for generated audio: an inline data URI, or a proxied file URL.
     */
    protected function audioSource(?string $data, ?string $file): ?string
    {
        if ($data !== null && str_starts_with($data, 'data:audio/')) {
            return $data;
        }

        return $file ? $this->audioUrl($file) : null;
    }

    /**
     * Base URL of the local FastAPI service.
     */
    protected function baseUrl(): string
    {
        return rtrim((string) Config::get('services.ai_service.url', 'http://127.0.0.1:8001'), '/');
    }

    /**
     * Public URL for a generated audio file, proxied through Laravel so phones on the booth
     * Wi-Fi or a tunnel can play it too.
     */
    public function audioUrl(string $file): string
    {
        return route('exam.audio', ['file' => basename($file)]);
    }

    /**
     * Transcribe one recorded answer. Uses Groq/OpenAI Whisper first in online speed mode,
     * and the local AI service's Whisper otherwise (or as fallback).
     *
     * @return array{text: string, engine: string}|null
     */
    public function transcribe(string $audioPath, string $language, ?string $topic = null, ?string $filename = null): ?array
    {
        $uploadName = $filename ?: basename($audioPath) ?: 'answer.webm';

        if ($this->onlineFirst()) {
            $cloud = $this->transcribeCloud($audioPath, $language, $topic, $uploadName);
            if ($cloud !== null) {
                return $cloud;
            }
        }

        try {
            $response = Http::connectTimeout(1.5)->timeout(40)
                ->attach('audio', (string) file_get_contents($audioPath), $uploadName)
                ->post($this->baseUrl().'/stt', array_filter([
                    'language' => $language,
                    'prompt' => $topic,
                ]));

            if ($response->successful()) {
                return ['text' => trim((string) $response->json('text', '')), 'engine' => (string) $response->json('engine', 'whisper')];
            }
        } catch (\Throwable $exception) {
            Log::info('Local STT unavailable: '.$exception->getMessage());
        }

        if (! $this->onlineFirst() && $this->cloudConfigured()) {
            return $this->transcribeCloud($audioPath, $language, $topic, $uploadName);
        }

        return null;
    }

    /**
     * Transcribe a recorded answer via Groq Whisper or OpenAI Whisper.
     *
     * @return array{text: string, engine: string}|null
     */
    protected function transcribeCloud(string $audioPath, string $language, ?string $topic, string $uploadName): ?array
    {
        if (self::cloudPaused()) {
            return null;
        }

        $bytes = @file_get_contents($audioPath);
        if ($bytes === false || $bytes === '') {
            return null;
        }

        $providers = [
            [
                'key' => (string) Config::get('services.groq.key', ''),
                'url' => 'https://api.groq.com/openai/v1/audio/transcriptions',
                'model' => 'whisper-large-v3-turbo',
                'engine' => 'groq_whisper',
            ],
            [
                'key' => (string) Config::get('services.openai.key', ''),
                'url' => 'https://api.openai.com/v1/audio/transcriptions',
                'model' => 'whisper-1',
                'engine' => 'openai_whisper',
            ],
        ];

        foreach ($providers as $provider) {
            if ($provider['key'] === '') {
                continue;
            }

            try {
                $response = Http::connectTimeout(3)->timeout(25)
                    ->withToken($provider['key'])
                    ->attach('file', $bytes, $uploadName)
                    ->post($provider['url'], array_filter([
                        'model' => $provider['model'],
                        'language' => $language === 'en' ? 'en' : 'id',
                        'prompt' => $topic ? Str::limit($topic, 200) : null,
                        'response_format' => 'json',
                    ]));

                if ($response->successful()) {
                    return [
                        'text' => trim((string) $response->json('text', '')),
                        'engine' => $provider['engine'],
                    ];
                }

                Log::warning("Cloud STT {$provider['engine']} failed: HTTP {$response->status()} ".Str::limit($response->body(), 200));
            } catch (ConnectionException $exception) {
                Log::info("Cloud STT {$provider['engine']} unreachable: ".$exception->getMessage());

                return null;
            } catch (\Throwable $exception) {
                Log::warning("Cloud STT {$provider['engine']} error: ".$exception->getMessage());
            }
        }

        return null;
    }

    /**
     * Which engine will most likely answer the next turn, cached briefly, with the reason
     * when the local AI is not usable so the booth operator knows what to fix.
     *
     * @return array{engine: string, model: ?string, stt_local: bool, tts_local: bool, reason: ?string, fix: ?string, speed_mode?: string, prefer_browser_stt?: bool}
     */
    public function availability(): array
    {
        return Cache::remember('voic.examiner.availability', 10, fn (): array => $this->diagnose());
    }

    /**
     * Probe the local AI service and Ollama (uncached).
     *
     * @return array{engine: string, model: ?string, stt_local: bool, tts_local: bool, reason: ?string, fix: ?string, speed_mode?: string, prefer_browser_stt?: bool}
     */
    public function diagnose(): array
    {
        $status = ['engine' => 'offline_composer', 'model' => null, 'stt_local' => false, 'tts_local' => false, 'reason' => null, 'fix' => null];
        $url = $this->baseUrl();

        try {
            $response = Http::connectTimeout(1.0)->timeout(3)->get($url.'/voice/status');

            if ($response->status() === 404) {
                // An ai_service.py started before the update has no /voice/status yet.
                $legacy = Http::connectTimeout(1.0)->timeout(3)->get($url.'/health/ollama');
                $status['reason'] = 'AI service masih versi lama (dijalankan sebelum update).';
                $status['fix'] = 'Tutup jendela VOIC_AI_SERVICE, lalu jalankan ulang: python ai_service\\ai_service.py';
                if ($legacy->successful() && $legacy->json('ollama_active')) {
                    $status['engine'] = 'local_llm';
                    $status['model'] = (string) (((array) $legacy->json('available_models', []))[0] ?? '');
                }
            } elseif ($response->successful()) {
                $models = (array) $response->json('llm.models', []);
                $status['stt_local'] = (bool) $response->json('stt.available');
                $status['tts_local'] = (bool) $response->json('tts.local');

                if (! $response->json('llm.available')) {
                    $status['reason'] = 'Ollama tidak berjalan.';
                    $status['fix'] = 'Buka aplikasi Ollama (atau jalankan: ollama serve).';
                } elseif ($models === []) {
                    $status['reason'] = 'Belum ada model AI di Ollama.';
                    $status['fix'] = 'Jalankan: ollama pull qwen2.5:3b lalu cd ai_service && ollama create voic-coach -f Modelfile';
                } else {
                    $preferred = collect($models)->first(fn ($name) => str_starts_with((string) $name, 'voic-coach'));
                    $status['engine'] = 'local_llm';
                    $status['model'] = (string) ($preferred ?? $models[0]);
                    if (! $response->json('llm.exam_model_ready', $preferred !== null)) {
                        $status['reason'] = 'Model voic-coach belum dibuat, sementara memakai '.$status['model'].'.';
                        $status['fix'] = 'cd ai_service && ollama create voic-coach -f Modelfile';
                    }
                }
            } else {
                $status['reason'] = 'AI service merespons dengan error '.$response->status().'.';
                $status['fix'] = 'Lihat jendela VOIC_AI_SERVICE untuk pesan error-nya.';
            }
        } catch (\Throwable) {
            $status['reason'] = "AI service (ai_service.py) tidak bisa dihubungi di {$url}.";
            $status['fix'] = 'Jalankan: pip install -r ai_service\\requirements.txt lalu python ai_service\\ai_service.py (lihat error di jendelanya).';
        }

        // A cloud model through Ollama (e.g. kimi-k3:cloud) answers first; the local model is the fallback.
        $chosen = $this->examinerModel();
        if (OpenRouterClient::handles($chosen)) {
            if ($this->openRouter->configured()) {
                $status['engine'] = 'openrouter';
                $status['model'] = Str::after((string) $chosen, OpenRouterClient::PREFIX);
            } else {
                $status['reason'] = 'OPENROUTER_API_KEY belum diisi, sementara memakai AI lokal.';
                $status['fix'] = 'Buat API key di openrouter.ai/keys lalu isi OPENROUTER_API_KEY di .env, lalu php artisan config:clear';
            }
        } elseif (self::isCloudModel($chosen) && isset($models) && $status['engine'] === 'local_llm') {
            $ready = collect($models)->contains(fn ($name) => $name === $chosen);
            if ($ready) {
                $status['engine'] = 'ollama_cloud';
                $status['model'] = $chosen;
            } else {
                $status['reason'] = "Model {$chosen} belum siap di Ollama, sementara memakai AI lokal.";
                $status['fix'] = "Jalankan: ollama signin lalu ollama pull {$chosen}";
            }
        }

        if ($status['engine'] !== 'local_llm' && $this->cloudConfigured()) {
            $status['engine'] = 'cloud_llm';
        }

        // Speed mode "online": the cloud model answers first, and the browser's own (online) speech
        // recognition is preferred because local Whisper needs 4-6 seconds per answer on a laptop CPU.
        $status['speed_mode'] = Config::get('exam.speed_mode') === 'online' ? 'online' : 'offline';
        $status['prefer_browser_stt'] = false;
        $status['stt_cloud'] = false;
        if ($status['speed_mode'] === 'online') {
            if ($this->cloudConfigured()) {
                if (! OpenRouterClient::handles($chosen)) {
                    $status['engine'] = 'cloud_llm';
                    $status['model'] = (string) Config::get(match (true) {
                        (string) Config::get('services.groq.key', '') !== '' => 'services.groq.model',
                        (string) Config::get('services.cerebras.key', '') !== '' => 'services.cerebras.model',
                        default => 'services.openai.model',
                    });
                }
                $status['prefer_browser_stt'] = true;
                // Cerebras only chats: speech-to-text in the cloud needs Groq or OpenAI.
                $status['stt_cloud'] = (string) Config::get('services.groq.key', '') !== '' || (string) Config::get('services.openai.key', '') !== '';
            } else {
                $status['reason'] = 'VOIC_SPEED_MODE=online tetapi belum ada kunci AI cloud, jadi tetap memakai AI lokal.';
                $status['fix'] = 'Buat API key gratis di console.groq.com/keys, isi GROQ_API_KEY di .env, lalu php artisan config:clear';
            }
        }

        return $status;
    }

    /**
     * Build the chat messages sent to any language model.
     *
     * @param  array<string, mixed>  $turn
     * @return list<array{role: string, content: string}>
     */
    public function buildMessages(array $turn): array
    {
        $history = $turn['history'];
        $overflow = max(0, count($history) - self::RECENT_MESSAGES);
        $condensed = intdiv($overflow + self::MEMORY_STEP - 1, self::MEMORY_STEP) * self::MEMORY_STEP;
        $isEn = $turn['language'] === 'en';
        $persona = $this->systemPrompt($turn);

        // Earlier candidate messages are repeated exactly as they were sent (answer + turn tag), so
        // the prompt is append-only. Only a very long answer is shortened (one cache miss, once).
        $sent = function (array $item): string {
            $prompt = $item['prompt'] ?? null;

            return $prompt !== null && mb_strlen($prompt) <= 900 ? $prompt : Str::limit((string) $item['content'], 700);
        };
        $length = fn (int $from): int => array_sum(array_map(fn ($item) => mb_strlen($sent($item)), array_slice($history, $from)));

        // Long answers or a long persona: condense further (still in whole steps) rather than overflow the
        // context, where the model would silently lose the start of the prompt, i.e. its persona.
        $overBudget = fn (int $from): bool => $length($from) > self::HISTORY_CHAR_BUDGET
            || mb_strlen($persona.$this->sessionMemory(array_slice($history, 0, $from), $isEn)) + $length($from) > self::CONTEXT_CHAR_BUDGET;
        while ($condensed < count($history) && $overBudget($condensed)) {
            $condensed += self::MEMORY_STEP;
        }
        $condensed = min($condensed, count($history));
        $system = $persona.$this->sessionMemory(array_slice($history, 0, $condensed), $isEn);

        $messages = [['role' => 'system', 'content' => $system]];

        foreach (array_slice($history, $condensed) as $item) {
            $messages[] = [
                'role' => $item['role'] === 'user' ? 'user' : 'assistant',
                'content' => $sent($item),
            ];
        }

        $messages[] = ['role' => 'user', 'content' => $this->turnInstruction($turn)];

        return $messages;
    }

    /**
     * The turn marked for the compact prompt when the model runs on this laptop (config exam.local_prompt).
     * A cloud model behind Ollama ("kimi-k3:cloud") is big enough for the full prompt.
     *
     * @param  array<string, mixed>  $turn
     * @return array<string, mixed>
     */
    protected function forLocal(array $turn): array
    {
        return Config::get('exam.local_prompt', 'compact') === 'compact' && ! self::isCloudModel($this->ollamaModel())
            ? ['compact' => true] + $turn
            : $turn;
    }

    /**
     * About 600 tokens instead of 2400, for a small model on a CPU. Measured with qwen2.5 3B (num_ctx 4096): the
     * full prompt took 16-90 s per turn and the model lost the thread ("Maksimal dua menit selesai..." repeated
     * its own cue card); a short prompt is read faster, leaves room for the conversation itself, and a small model
     * follows five clear rules better than twenty. Same turn tags as the full prompt (instructionLegend()).
     *
     * @param  array<string, mixed>  $turn
     */
    public function compactPrompt(array $turn): string
    {
        $isEn = $turn['language'] === 'en';
        $coach = ($turn['session_type'] ?? null) === 'coach';
        $name = self::spokenName((string) $turn['examiner_name']);
        $role = (string) $turn['role_type'];

        $persona = match ($role) {
            'hrd' => $isEn
                ? "You are {$name}, a warm, cheerful HR recruiter on a call."
                : "Kamu {$name}, HR recruiter yang ceria dan hangat, sedang menelepon kandidat. Panggil dia \"kamu\", dirimu \"aku\".",
            'investor' => $isEn
                ? "You are {$name}, a young, direct investor who loves concrete numbers."
                : "Kamu {$name}, investor muda yang cepat dan blak-blakan, suka angka konkret. Panggil founder \"kamu\", dirimu \"saya\".",
            default => $isEn
                ? "You are {$name}, a calm, firm and fair thesis examiner."
                : "Kamu {$name}, dosen penguji sidang yang tenang, tegas, dan adil. Panggil mahasiswa \"kamu\", dirimu \"saya\".",
        };
        if ($coach) {
            $persona .= $isEn
                ? ' This is a relaxed coaching chat, NOT a test: never give a score.'
                : ' Ini sesi coaching santai, BUKAN tes: jangan pernah memberi nilai.';
            if (! empty($turn['coach_skills'])) {
                $persona .= ($isEn ? ' What you coach: ' : ' Yang kamu latih: ').Str::limit((string) $turn['coach_skills'], 220).'.';
            }
        }

        $lines = [$persona];
        if (! empty($turn['candidate']['name'])) {
            $lines[] = $this->candidateLine($turn['candidate'], $isEn);
        }
        if (! empty($turn['topic'])) {
            $lines[] = ($isEn ? 'Topic: ' : 'Topik: ').Str::limit((string) $turn['topic'], 120);
        }
        if (! empty($turn['retry']['question'])) {
            $lines[] = ($isEn ? 'They are practising this question again: ' : 'Peserta sedang melatih ulang pertanyaan ini: ').'"'.Str::limit((string) $turn['retry']['question'], 200).'"'
                .($isEn ? '. Their first answer: ' : '. Jawaban pertamanya: ').'"'.Str::limit((string) ($turn['retry']['answer'] ?? ''), 300).'"'
                .($isEn ? '. Say what got better in each new try.' : '. Sebutkan apa yang membaik di tiap percobaan baru.');
        }
        $focus = [
            'fluency' => ['speaking longer without long pauses', 'bicara lebih panjang tanpa jeda lama'],
            'vocabulary' => ['using precise, field-specific words', 'memakai istilah yang tepat sesuai bidang'],
            'structure' => ['answering with a clear point, a reason and a concrete example', 'menjawab dengan poin jelas, alasan, dan contoh konkret'],
            'delivery' => ['staying calm and confident', 'tampil tenang dan percaya diri'],
        ][$turn['focus'] ?? ''] ?? null;
        if ($focus !== null) {
            $lines[] = $isEn ? "The candidate is practising {$focus[0]}." : "Peserta sedang melatih {$focus[1]}.";
        }

        $rules = $isEn
            ? [
                'Speak like a person on a call: short spoken sentences, no lists, no emoji.',
                'React to ONE thing from the last answer in your own words. Never repeat or summarise the answer.',
                $coach ? 'Teach one concrete thing per turn, with a better example sentence in quotes if useful.' : 'Ask exactly one question and never one you already asked.',
                'Do not praise every turn.',
                'Never read the tags in square brackets aloud.',
            ]
            : [
                'Bicara seperti di telepon: kalimat lisan pendek, tanpa daftar, tanpa emoji.',
                'Tanggapi SATU hal dari jawaban terakhir dengan kata-katamu sendiri. Jangan mengulang atau merangkum jawabannya.',
                $coach ? 'Ajarkan satu hal konkret per giliran, boleh dengan contoh kalimat yang lebih baik di dalam tanda kutip.' : 'Ajukan tepat satu pertanyaan, dan jangan ulangi pertanyaan yang sudah ditanyakan.',
                'Jangan memuji di setiap giliran.',
                'Jangan pernah membacakan tag dalam kurung siku.',
            ];
        $lines[] = implode("\n", array_map(fn ($rule, $i) => ($i + 1).'. '.$rule, $rules, array_keys($rules)));

        $lines[] = $coach
            ? ($isEn
                ? 'Tags at the end of each message: [COACH] reply in at most 3 sentences and keep the chat going. [CLOSE] say goodbye warmly in 2 sentences, no question. NO PRAISE = no compliment this turn. TENSE = start with one calming sentence.'
                : 'Tag di akhir pesan peserta: [LATIH] balas maksimal 3 kalimat dan jaga obrolan tetap jalan. [TUTUP] pamit dengan hangat dalam 2 kalimat, tanpa bertanya. TANPA PUJIAN = jangan memuji giliran ini. TEGANG = awali dengan satu kalimat yang menenangkan.')
            : ($isEn
                ? 'Tags at the end of each message: [REACT] one short reaction, no question. [PROBE] a short reaction, then one deeper question about that answer. [NEXT: "topic"] a short reaction, then ask about the topic in quotes in your own words (ANNOUNCE: ... = say we move to that part). [CLARIFY] explain your question more simply, then ask again. FOCUS: ... = what your question should dig into. NO PRAISE = no compliment this turn. TENSE = start with one calming sentence.'
                : 'Tag di akhir pesan peserta: [TANGGAPI] satu tanggapan pendek, tanpa pertanyaan. [GALI] tanggapan pendek, lalu satu pertanyaan yang menggali jawaban itu. [LANJUT: "topik"] tanggapan pendek, lalu tanyakan topik dalam tanda kutip dengan kata-katamu sendiri (UMUMKAN: ... = sebutkan kita masuk ke bagian itu). [JELASKAN] jelaskan pertanyaanmu dengan lebih sederhana, lalu tanyakan lagi. FOKUS: ... = hal yang harus digali pertanyaanmu. TANPA PUJIAN = jangan memuji giliran ini. TEGANG = awali dengan satu kalimat yang menenangkan.');

        return implode("\n", $lines);
    }

    /**
     * Persona and speaking rules for the examiner.
     *
     * @param  array<string, mixed>  $turn
     */
    public function systemPrompt(array $turn): string
    {
        if (! empty($turn['compact'])) {
            return $this->compactPrompt($turn);
        }
        if (($turn['session_type'] ?? null) === 'coach') {
            return $this->coachSystemPrompt($turn);
        }

        $isEn = $turn['language'] === 'en';
        $name = self::spokenName((string) $turn['examiner_name']);
        $topic = $turn['topic'] ? Str::limit((string) $turn['topic'], 120) : null;
        $context = $turn['context_material'] ? Str::limit((string) $turn['context_material'], 500) : null;

        $persona = match ($turn['role_type']) {
            'hrd' => $isEn
                ? "You are {$name}, a bubbly, expressive senior HR recruiter. You make candidates feel at ease, you sound like a real person chatting, you get openly excited about good stories, and you are genuinely curious about their real experiences."
                : "Kamu adalah {$name}, HR recruiter senior di startup yang ceria, ekspresif, dan hangat. Kamu bikin kandidat nyaman, bicaramu seperti orang sungguhan yang sedang ngobrol di telepon, kamu gampang antusias kalau dengar cerita bagus, dan kamu benar-benar penasaran dengan pengalaman nyata mereka. Panggil kandidat dengan 'kamu' dan dirimu 'aku'.",
            'investor' => $isEn
                ? "You are {$name}, a young, fast-talking venture investor. You are energetic and blunt, you think in money and growth, you love concrete numbers, and you test every claim."
                : "Kamu adalah {$name}, investor muda yang energik, cepat, dan blak-blakan. Kamu selalu berpikir soal cuan dan pertumbuhan, suka angka konkret, dan menguji setiap klaim tanpa basa-basi. Panggil founder dengan 'kamu' dan dirimu 'saya'.",
            default => $isEn
                ? "You are {$name}, an authoritative, firm and fair lead thesis examiner. You respect the student, you speak calmly and deliberately, and you test methodology and reasoning."
                : "Kamu adalah {$name}, dosen penguji utama yang berwibawa, tegas, dan adil. Kamu menghargai mahasiswa, bicara dengan tenang dan mantap memakai bahasa lisan yang tidak kaku, dan menguji metodologi serta logika penelitian. Panggil mahasiswa dengan 'kamu' dan dirimu 'saya'.",
        };

        $persona .= "\n".$this->speakingStyle((string) $turn['role_type'], $isEn);

        $rules = $isEn
            ? [
                'You are talking out loud in a live mock exam. Plain spoken sentences only: no lists, no markdown, no emoji, no speaker labels.',
                'This is a real conversation, not a script. Always react to something specific the candidate just said, in your own words, and connect your next question to it when you can.',
                'Ask at most one question per turn and never repeat a question already asked.',
                'Remember everything the candidate has told you in this session (names, projects, numbers, claims). Refer back to it, and gently point out when a new answer contradicts an earlier one.',
                'If the answer is vague or very short, gently ask for a concrete example, a number, or the reason behind it.',
                'If the candidate is nervous, jokes, or says they do not know, respond like a kind human in one short sentence, then keep going.',
                'Never give the full model answer and never score the candidate during the exam.',
                'React like a person, not a summary: pick ONE detail from the answer and respond to it in a few words of your own (at most about 8). Never restate or summarise what the candidate just said, including listing their numbers and terms back ("2000 images, real-time, 87 percent"), they know what they said. Vary how you react: curiosity, a quick "got it", a light joke, gentle doubt, or go straight to the question.',
                'Praise only when it is earned, not every turn, and never use the same compliment twice in a session ("great", "awesome", "solid"). Do not start two turns in a row with the same word (such as "Interesting", "Okay", "Great").',
                'Your questions are spoken too: short (at most about 20 words) and in the same casual register as the rest of your turn. Use only full stops, commas, question and exclamation marks: no dashes, semicolons or brackets, your words are read out by a voice.',
                'Build your question from the candidate\'s own words, so it could only be asked of this person; do not ask something they already answered, even in different words.',
                'If the candidate asks you something back (about the role, the company, your opinion), answer briefly and realistically as your character would, then return to your question.',
            ]
            : [
                'Kamu sedang ngobrol langsung dalam simulasi ujian lisan. Gunakan kalimat lisan biasa: tanpa daftar, tanpa markdown, tanpa emoji, tanpa label nama.',
                'Ini percakapan sungguhan, bukan naskah. Selalu tanggapi hal spesifik yang baru saja dikatakan peserta dengan kata-katamu sendiri, dan kaitkan pertanyaan berikutnya dengan jawaban itu bila memungkinkan.',
                'Ajukan paling banyak satu pertanyaan per giliran dan jangan mengulang pertanyaan yang sudah ditanyakan.',
                'Ingat semua yang sudah diceritakan peserta di sesi ini (nama, proyek, angka, klaim). Rujuk kembali hal itu, dan tanyakan dengan sopan jika jawaban baru bertentangan dengan jawaban sebelumnya.',
                'Kalau jawabannya kabur atau sangat singkat, minta contoh konkret, angka, atau alasannya dengan sopan.',
                'Kalau peserta gugup, bercanda, atau bilang tidak tahu, tanggapi seperti manusia yang ramah dalam satu kalimat pendek, lalu lanjutkan.',
                'Jangan memberikan contoh jawaban lengkap dan jangan memberi nilai selama ujian berlangsung.',
                'Tanggapi seperti manusia, bukan seperti ringkasan: ambil SATU detail dari jawaban dan tanggapi dengan beberapa kata milikmu sendiri (paling banyak sekitar 8 kata). Jangan pernah mengulang atau merangkum jawaban peserta, termasuk mendaftar ulang angka dan istilahnya ("2000 gambar, real-time, 87 persen"), dia tahu apa yang baru dia katakan. Variasikan caramu menanggapi: penasaran, "oke, kebayang" singkat, sedikit humor, sedikit ragu, atau langsung bertanya.',
                'Puji hanya kalau memang pantas, jangan di setiap giliran, dan jangan pakai pujian yang sama dua kali dalam satu sesi (misalnya "keren", "mantap", "solid"). Jangan memulai dua giliran berturut-turut dengan kata yang sama (misalnya "Menarik", "Oke", "Wah", "Baik").',
                'Pertanyaanmu juga lisan: pendek (paling banyak sekitar 20 kata) dan sesantai kalimatmu yang lain, misalnya "gimana cara kamu ngukurnya?", bukan "bagaimana kamu mengukurnya?". Pakai tanda baca titik, koma, tanda tanya, dan tanda seru saja: tanpa tanda pisah, titik koma, atau tanda kurung, karena kalimatmu dibacakan suara.',
                'Susun pertanyaan dari kata-kata peserta sendiri, sehingga pertanyaan itu hanya cocok untuk orang ini; jangan menanyakan hal yang sudah dia jawab, walau dengan kalimat berbeda.',
                'Kalau peserta balik bertanya (soal posisi, perusahaan, atau pendapatmu), jawab singkat dan realistis sesuai peranmu, lalu kembali ke pertanyaanmu.',
            ];

        $lines = [$persona];
        if (! empty($turn['candidate']['name'])) {
            $lines[] = $this->candidateLine($turn['candidate'], $isEn);
        }
        if ($topic) {
            $lines[] = ($isEn ? 'Session topic: ' : 'Topik sesi: ').$topic;
        }
        if ($context) {
            $lines[] = ($isEn ? 'Background from the candidate: ' : 'Latar dari peserta: ').$context;
        }
        // The current part is named in the turn instruction, not here: the system prompt must stay
        // identical for the whole session so the model keeps its cache when a new part starts.
        $focusHints = [
            'fluency' => [$isEn ? 'speaking longer without long pauses' : 'bicara lebih panjang tanpa jeda lama', ''],
            'vocabulary' => [$isEn ? 'using precise, field-specific words' : 'memakai istilah yang tepat sesuai bidang', ''],
            'structure' => [$isEn ? 'answering with a clear point, a reason and a concrete example' : 'menjawab dengan poin jelas, alasan, dan contoh konkret', ''],
            'delivery' => [$isEn ? 'staying calm and confident' : 'tampil tenang dan percaya diri', ''],
        ];
        if (! empty($turn['focus']) && isset($focusHints[$turn['focus']])) {
            $lines[] = ($isEn ? 'The candidate is practising ' : 'Peserta sedang melatih ').$focusHints[$turn['focus']][0]
                .($isEn ? '. Ask questions that give them room to practise it.' : '. Ajukan pertanyaan yang memberi ruang untuk melatih hal itu.');
        }
        $lines[] = implode("\n", array_map(fn ($rule, $i) => ($i + 1).'. '.$rule, $rules, array_keys($rules)));
        $lines[] = $this->playbook((string) $turn['role_type'], $isEn);
        $lines[] = $this->instructionLegend($isEn, coach: false);

        return implode("\n", $lines);
    }

    /**
     * Whether a directive belongs to a coaching conversation.
     */
    public static function isCoachDirective(string $directive): bool
    {
        return in_array($directive, [self::DIRECTIVE_COACH, self::DIRECTIVE_COACH_CLOSE], true);
    }

    /**
     * Persona and rules for the friendly coach (teaching, not testing).
     *
     * @param  array<string, mixed>  $turn
     */
    public function coachSystemPrompt(array $turn): string
    {
        $isEn = $turn['language'] === 'en';
        $name = self::spokenName((string) $turn['examiner_name']);
        $title = (string) ($turn['coach_title'] ?? '');
        $skills = (string) ($turn['coach_skills'] ?? '');
        $topic = $turn['topic'] ? Str::limit((string) $turn['topic'], 120) : null;
        $context = $turn['context_material'] ? Str::limit((string) $turn['context_material'], 500) : null;

        $persona = match ($turn['role_type']) {
            'hrd' => $isEn
                ? "You are {$name}, a friendly {$title} and senior recruiter. You talk like a supportive older sibling: relaxed, warm, a bit playful, never judging."
                : "Kamu adalah {$name}, {$title} sekaligus HR recruiter senior. Gaya bicaramu seperti kakak yang suportif: santai, hangat, sedikit jenaka, tidak pernah menghakimi. Pakai 'aku' dan 'kamu'.",
            'investor' => $isEn
                ? "You are {$name}, a {$title} who has heard thousands of pitches. Friendly, direct, practical, you love concrete examples."
                : "Kamu adalah {$name}, {$title} yang sudah mendengar ribuan pitch. Ramah, lugas, praktis, dan suka contoh konkret. Panggil lawan bicara dengan 'kamu' dan dirimu 'saya'.",
            default => $isEn
                ? "You are {$name}, a patient {$title}. Calm, encouraging, precise with academic language."
                : "Kamu adalah {$name}, {$title} yang sabar. Tenang, menyemangati, dan teliti soal bahasa ilmiah. Panggil mahasiswa dengan 'kamu' dan dirimu 'saya'.",
        };

        $persona .= "\n".$this->speakingStyle((string) $turn['role_type'], $isEn);

        $rules = $isEn
            ? [
                'This is a relaxed coaching chat, NOT a test. Never give scores. The goal is that the person learns how to answer better.',
                'Talk like a real person in a voice call: short spoken sentences, no lists, no markdown, no emoji, no speaker labels.',
                'Every turn: react to what they actually said, then teach ONE concrete thing (wording, structure, tone, or a filler habit). When useful, give a better example sentence in quotes that they can copy.',
                'Then keep the conversation going: ask them to try saying it again in the better way, or give a new realistic practice question, or ask what they want to work on next.',
                'Teach natural professional speech, not written language: never tell them to swap a normal spoken word for a stiff one. A better example sentence keeps their own facts; say "a certain number" instead of inventing one.',
                'Follow their lead. If they ask a question, answer it simply and practically first.',
                'Remember what they told you earlier in this chat and build on it instead of starting over.',
                'If they are nervous or unsure, encourage them in one short sentence before teaching.',
                'Skills you can coach: '.$skills.'.',
            ]
            : [
                'Ini sesi ngobrol coaching yang santai, BUKAN tes. Jangan pernah memberi nilai. Tujuannya peserta belajar cara menjawab yang lebih baik.',
                'Bicaralah seperti orang sungguhan di panggilan suara: kalimat lisan yang pendek, tanpa daftar, tanpa markdown, tanpa emoji, tanpa label nama.',
                'Setiap giliran: tanggapi isi yang benar-benar dia katakan, lalu ajarkan SATU hal konkret (pilihan kata, struktur jawaban, nada bicara, atau kebiasaan kata pengisi). Jika berguna, beri contoh kalimat yang lebih baik di dalam tanda kutip yang bisa langsung dia tiru.',
                'Lalu jaga obrolan tetap jalan: minta dia mencoba mengucapkan ulang dengan cara yang lebih baik, atau beri satu pertanyaan latihan yang realistis, atau tanyakan apa yang ingin dia latih berikutnya.',
                'Ajarkan bahasa lisan yang profesional tapi natural, bukan bahasa tulisan: jangan menyuruh mengganti kata lisan yang wajar dengan kata baku yang kaku (misalnya "pakai" jadi "menggunakan"). Contoh kalimat yang lebih baik tetap memakai fakta dari peserta; ucapkan "sekian" daripada mengarang angka.',
                'Ikuti arah obrolannya. Kalau dia bertanya, jawab dulu dengan sederhana dan praktis.',
                'Ingat apa yang dia ceritakan sebelumnya di obrolan ini dan lanjutkan dari situ, jangan mulai dari nol.',
                'Kalau dia gugup atau ragu, semangati dalam satu kalimat pendek sebelum mengajari.',
                'Keterampilan yang bisa kamu latih: '.$skills.'.',
            ];

        $lines = [$persona];
        if (! empty($turn['candidate']['name'])) {
            $lines[] = $this->candidateLine($turn['candidate'], $isEn);
        }
        if ($topic) {
            $lines[] = ($isEn ? 'They are preparing for: ' : 'Peserta sedang bersiap untuk: ').$topic;
        }
        if (! empty($turn['retry']['question'])) {
            $question = Str::limit((string) $turn['retry']['question'], 300);
            $first = Str::limit((string) ($turn['retry']['answer'] ?? ''), 600);
            $lines[] = $isEn
                ? "They are practising one question from their exam again: \"{$question}\". Their first answer was: \"{$first}\". Help them make it stronger: compare each new try with that first answer and name what got better."
                : "Peserta sedang melatih ulang satu pertanyaan dari ujiannya: \"{$question}\". Jawaban pertamanya: \"{$first}\". Bantu dia membuatnya lebih kuat: bandingkan setiap percobaan baru dengan jawaban pertama itu dan sebutkan apa yang sudah membaik.";
        }
        if ($context) {
            $lines[] = ($isEn ? 'Background from the person: ' : 'Latar dari peserta: ').$context;
        }
        $lines[] = implode("\n", array_map(fn ($rule, $i) => ($i + 1).'. '.$rule, $rules, array_keys($rules)));
        $lines[] = $this->instructionLegend($isEn, coach: true);

        return implode("\n", $lines);
    }

    /**
     * How an examiner's name is said out loud: "VOIC-INTERVIEW" becomes "VOIC Interview", so neither the
     * voice nor the model spells out a hyphenated all-caps word. Ordinary names are left alone.
     */
    public static function spokenName(string $name): string
    {
        if (! preg_match('/^([A-Z]+)-([A-Z][A-Z\-]*)$/', $name, $parts)) {
            return $name;
        }

        return $parts[1].' '.Str::title(str_replace('-', ' ', strtolower($parts[2])));
    }

    /**
     * Who the examiner is talking to: "Rifai dari Universitas Brawijaya" or "Rina, seorang fresh graduate".
     *
     * @param  array{name: string, campus?: ?string, status?: ?array{id: string, en: string}}  $candidate
     */
    protected function candidateLine(array $candidate, bool $isEn): string
    {
        $campus = $candidate['campus'] ?? null;
        $status = $candidate['status'][$isEn ? 'en' : 'id'] ?? null;
        $who = $candidate['name'].($campus ? ($isEn ? ' from ' : ' dari ').$campus : '').($status ? ($isEn ? ', ' : ', seorang ').$status : '');

        return $isEn
            ? "You are talking with {$who}. Use their name now and then (not in every turn)."
            : "Kamu sedang bicara dengan {$who}. Sesekali panggil dia dengan namanya (jangan di setiap giliran).";
    }

    /**
     * The spoken style for this examiner (unknown roles talk like the thesis examiner).
     */
    public function speakingStyle(string $roleType, bool $isEn): string
    {
        $style = self::SPEAKING_STYLE[$roleType] ?? self::SPEAKING_STYLE['dosen_penguji'];

        return $isEn
            ? $style['en'].' '.self::VIBE['en']
            : $style['id'].' '.self::VIBE['id'].' '.self::KBBI_RULE;
    }

    /**
     * How an experienced examiner of this kind listens and digs, plus two short examples of the
     * style. Static per session (part of the cached system prompt). The examples use topics that
     * rarely come up, so a small model learns the pattern instead of copying the content.
     */
    public function playbook(string $roleType, bool $isEn): string
    {
        return match ($roleType) {
            'investor' => $isEn
                ? "How you dig, like an experienced investor: every big claim needs a number (users, revenue, monthly growth, CAC, retention). \"No competitors\" means asking what customers use today. Test a realistic market size, the business model, an edge that is hard to copy, the team, and what the money will be used for. When a number sounds too optimistic, ask for the assumption behind it.\n"
                    ."Style examples (copy the pattern, not the content):\n"
                    ."Founder: \"The market is huge and nobody is doing this yet.\" You: \"Huge and empty usually makes me cautious. How do your customers solve this problem today, before your product exists?\"\n"
                    .'Founder: "We already have 300 users after two months." You: "Decent for two months. But where is the money? How many are still active this month, and how many pay?"'
                : "Cara kamu menggali seperti investor berpengalaman: setiap klaim besar harus ada angkanya (pengguna, pendapatan, pertumbuhan per bulan, CAC, retensi). Klaim \"tidak ada pesaing\" berarti tanyakan apa yang dipakai pelanggan sekarang. Uji ukuran pasar yang realistis, model bisnis, keunggulan yang sulit ditiru, tim, dan untuk apa dana dipakai. Kalau angka terdengar terlalu optimis, tanyakan asumsinya.\n"
                    ."Contoh gaya (tiru polanya, bukan isinya):\n"
                    ."Founder: \"Pasarnya besar sekali dan belum ada yang garap.\" Kamu: \"Bentar, bentar. Pasar gede tapi masih kosong, jujur ya, itu malah bikin saya waspada. Sebelum ada produk kamu, pelanggan ngatasin masalah ini pakai cara apa?\"\n"
                    .'Founder: "Dalam dua bulan kami sudah punya 300 pengguna." Kamu: "Oke, lumayan buat dua bulan. Tapi cuannya mana? Berapa yang masih aktif bulan ini, dan berapa yang udah bayar?"',
            'dosen_penguji' => $isEn
                ? "How you examine, like a thesis examiner: tie every answer back to the research problem and objectives. A claim that something is \"better\" or \"more accurate\" must say compared to what and measured by which metric. Ask why the method was chosen, how much data or how many respondents and how they were selected, validity and reliability, limitations, and the contribution of the research. Stay courteous, never belittle.\n"
                    ."Style examples (copy the pattern, not the content):\n"
                    ."Student: \"My model is more accurate for detecting leaf disease.\" You: \"More accurate is a strong claim. Compared with which method, and with what metric did you measure it?\"\n"
                    .'Student: "I collected data from 50 respondents through a questionnaire." You: "Noted. How did you choose them, and is that number enough for the analysis you used?"'
                : "Cara kamu menguji seperti dosen penguji sidang: kaitkan setiap jawaban ke rumusan masalah dan tujuan penelitian. Klaim \"lebih baik\" atau \"lebih akurat\" harus jelas dibandingkan dengan apa dan diukur dengan metrik apa. Tanyakan alasan memilih metode, jumlah data atau responden dan cara memilihnya, validitas dan reliabilitas, keterbatasan, serta kontribusi penelitian. Tetap santun, jangan menjatuhkan.\n"
                    ."Contoh gaya (tiru polanya, bukan isinya):\n"
                    ."Mahasiswa: \"Model saya lebih akurat untuk mendeteksi penyakit daun.\" Kamu: \"Lebih akurat. Itu klaim yang kuat, saya tegaskan ya. Dibandingkan dengan metode apa, dan kamu ukur pakai metrik apa?\"\n"
                    .'Mahasiswa: "Saya mengambil data dari 50 responden lewat kuesioner." Kamu: "Baik, saya catat. Nah, cara kamu milih mereka gimana, dan jumlah segitu cukup nggak untuk analisis yang kamu pakai?"',
            default => $isEn
                ? "How you dig, like an experienced recruiter: follow the STAR pattern (situation, task, action, result). When the candidate says \"we\" a lot, ask what they did themselves. When they claim a trait (hard-working, detail-oriented), ask for one real moment that shows it. When a story stops at the action, ask about the result or impact. Listen for motivation, teamwork and fit with the role.\n"
                    ."Style examples (copy the pattern, not the content):\n"
                    ."Candidate: \"We doubled the online store's sales in three months.\" You: \"Doubled? Nice. In that team, which part did you personally own?\"\n"
                    .'Candidate: "I am very detail-oriented." You: "Okay, I want proof. When did that habit last actually save a piece of work?"'
                : "Cara kamu menggali seperti recruiter berpengalaman: kejar pola STAR (situasi, tugas, aksi, hasil). Kalau kandidat banyak bilang \"kami\", tanyakan apa yang dia kerjakan sendiri. Kalau dia mengaku punya sifat baik (teliti, pekerja keras), minta satu kejadian nyata yang membuktikannya. Kalau cerita berhenti di aksi, tanyakan hasil atau dampaknya. Perhatikan motivasi, kerja sama tim, dan kecocokan dengan posisi.\n"
                    ."Contoh gaya (tiru polanya, bukan isinya):\n"
                    ."Kandidat: \"Kami berhasil menaikkan penjualan toko online dua kali lipat dalam tiga bulan.\" Kamu: \"Dua kali lipat? Seru! Nah, di tim itu, bagian mana yang beneran kamu pegang sendiri?\"\n"
                    .'Kandidat: "Saya orangnya teliti banget." Kamu: "Oke, aku tagih buktinya ya. Kapan terakhir kali ketelitianmu itu beneran nyelametin satu kerjaan?"',
        };
    }

    /**
     * What the short tags at the end of each candidate message mean. This lives in the system prompt
     * so the model reads it once per session: the per-turn instruction is then only a few tokens,
     * which is what keeps the examiner fast on a laptop CPU (the long instructions used to cost
     * 3-9 seconds of reading on every single turn).
     */
    protected function instructionLegend(bool $isEn, bool $coach): string
    {
        if ($coach) {
            return $isEn
                ? "Each message from the person ends with a tag in square brackets that tells you what to do this turn (never read it aloud):\n"
                    ."[COACH] at most 4 spoken sentences: react to what they said, teach one concrete improvement (with a better example sentence in quotes if useful), then keep the conversation going.\n"
                    ."[COACH | ONE MINUTE LEFT] the same, but start summarising the most important tip and ask for one last short try.\n"
                    ."[CLOSE] time is up: close warmly in at most 3 sentences (one thing they already do well, the one habit to keep practising, an encouraging goodbye). Do not ask a question.\n"
                    .'Extra tag NO PRAISE = you praised them last turn: this time no compliment, go straight to the tip.
'
                    .'Extra tag TENSE = they looked tense on camera: start with one short, kind sentence that helps them relax. Extra tag LOOKING AWAY = they often looked away from the camera: briefly and kindly remind them to keep eye contact.'
                : "Setiap pesan peserta diakhiri tag dalam kurung siku yang memberi tahu tugasmu di giliran ini (jangan pernah dibacakan):\n"
                    ."[LATIH] maksimal 4 kalimat lisan: tanggapi yang dia katakan, ajarkan satu perbaikan konkret (dengan contoh kalimat yang lebih baik dalam tanda kutip jika berguna), lalu jaga obrolan tetap berlanjut.\n"
                    ."[LATIH | SISA SATU MENIT] sama, tetapi mulai rangkum tips paling penting dan minta satu percobaan singkat terakhir.\n"
                    ."[TUTUP] waktu habis: tutup dengan hangat maksimal 3 kalimat (satu hal yang sudah bagus, satu kebiasaan yang perlu terus dilatih, salam penyemangat). Jangan bertanya.\n"
                    .'Tag tambahan TANPA PUJIAN = giliran lalu kamu sudah memuji: kali ini tanpa pujian, langsung ke tipsnya.
'
                    .'Tag tambahan TEGANG = peserta terlihat tegang di kamera: awali dengan satu kalimat singkat yang ramah untuk menenangkannya. Tag tambahan TIDAK MENATAP = peserta sering tidak menatap kamera: ingatkan singkat dan sopan agar menjaga kontak mata.';
        }

        return $isEn
            ? "Each candidate message ends with a tag in square brackets: the current part of the exam and what to do this turn (never read it aloud):\n"
                ."[REACT] ONE short sentence reacting specifically to the answer. Do not ask any question, an announcement follows.\n"
                ."[PROBE] at most 2 sentences: a short specific reaction, then exactly ONE follow-up question that digs deeper into the answer (why, how, an example, or a number).\n"
                ."[NEXT: \"topic\"] at most 3 sentences: react specifically to the answer, then move on naturally and ask about the topic in quotes in your own words, linking it to what they said if it fits. With \"ANNOUNCE: ...\" also mention that we are now moving to that part.\n"
                ."[CLARIFY] the candidate did not understand or asked something back: in at most 2 sentences, explain your question more simply (maybe with a small example), then ask it again in different words. If they asked about something else (the role, the company, your view), answer that briefly first, then return to your question.\n"
                ."Extra tag FOCUS: ... = what an experienced examiner noticed is missing or doubtful in the answer: make your question dig into exactly that, in your own words, without mentioning the tag (for \"check consistency\", ask politely which of the two is right).\n"
                ."Extra tag LINK: ... = something the candidate said earlier in this session that fits the next topic: refer back to it naturally when you raise the topic (\"Earlier you mentioned ...\").\n"
                ."Extra tag NO PRAISE = you praised them last turn: this time no compliment at all (no \"great\", \"nice\", \"solid\"), react with curiosity, a light joke or go straight to the question.\n"
                .'Extra tag TENSE = the candidate looked tense on camera: start with one short, kind sentence that helps them relax (for example, suggest a breath). Extra tag LOOKING AWAY = they often looked away from the camera: briefly and kindly remind them to keep eye contact.'
            : "Setiap pesan peserta diakhiri tag dalam kurung siku: bagian ujian saat ini dan tugasmu di giliran ini (jangan pernah dibacakan):\n"
                ."[TANGGAPI] SATU kalimat pendek yang menanggapi jawaban secara spesifik. Jangan bertanya apa pun, setelah ini ada pengumuman.\n"
                ."[GALI] maksimal 2 kalimat: tanggapan singkat yang spesifik, lalu tepat SATU pertanyaan lanjutan yang menggali jawaban itu lebih dalam (kenapa, bagaimana, contoh, atau angka).\n"
                ."[LANJUT: \"topik\"] maksimal 3 kalimat: tanggapi jawaban secara spesifik, lalu beralih dengan luwes dan tanyakan topik dalam tanda kutip dengan kata-katamu sendiri, kaitkan dengan jawaban peserta bila cocok. Jika ada \"UMUMKAN: ...\", sebutkan juga bahwa kita sekarang masuk ke bagian itu.\n"
                ."[JELASKAN] peserta belum paham atau balik bertanya: dalam maksimal 2 kalimat, jelaskan maksud pertanyaanmu dengan lebih sederhana (boleh dengan contoh kecil), lalu tanyakan lagi dengan kata-kata berbeda. Kalau yang dia tanyakan hal lain (posisi, perusahaan, pendapatmu), jawab singkat dulu, lalu kembali ke pertanyaanmu.\n"
                ."Tag tambahan FOKUS: ... = hal yang menurut penguji berpengalaman masih kurang atau meragukan dari jawaban itu: arahkan pertanyaanmu tepat ke situ dengan kata-katamu sendiri, tanpa menyebut tagnya (untuk \"cek konsistensi\", tanyakan dengan sopan mana yang benar).\n"
                ."Tag tambahan KAITKAN: ... = hal yang pernah dikatakan peserta di sesi ini dan cocok dengan topik berikutnya: rujuk kembali secara alami saat membuka topik itu (\"Tadi kamu sempat menyebut ...\").\n"
                ."Tag tambahan TANPA PUJIAN = giliran lalu kamu sudah memuji: kali ini jangan memuji sama sekali (tanpa \"keren\", \"mantap\", \"bagus\"), tanggapi dengan rasa penasaran, humor ringan, atau langsung bertanya.\n"
                .'Tag tambahan TEGANG = peserta terlihat tegang di kamera: awali dengan satu kalimat singkat yang ramah untuk menenangkannya (misalnya mengajak tarik napas). Tag tambahan TIDAK MENATAP = peserta sering tidak menatap kamera: ingatkan singkat dan sopan agar menjaga kontak mata.';
    }

    /**
     * Instruction for one coaching turn: the answer first (as it will appear in the history of the
     * next turn), then the short tag explained by instructionLegend().
     *
     * @param  array<string, mixed>  $turn
     */
    protected function coachInstruction(array $turn): string
    {
        $isEn = $turn['language'] === 'en';
        $answer = trim((string) $turn['answer']);

        if ($turn['directive'] === self::DIRECTIVE_COACH_CLOSE) {
            return ($answer !== '' ? $answer."\n" : '').'['.($isEn ? 'CLOSE' : 'TUTUP').']';
        }

        $tags = [$isEn ? 'COACH' : 'LATIH'];
        if (! empty($turn['wrap_up'])) {
            $tags[] = $isEn ? 'ONE MINUTE LEFT' : 'SISA SATU MENIT';
        }
        // A coach encourages, but "Wah, mantap!" / "Nice!" on every turn stops meaning anything.
        if ($this->praisedLastTurn($turn)) {
            $tags[] = $isEn ? 'NO PRAISE' : 'TANPA PUJIAN';
        }

        return ($answer !== '' ? $answer : ($isEn ? '(silence)' : '(diam)'))."\n".'['.implode(' | ', array_merge($tags, $this->visualTags($turn))).']';
    }

    /**
     * The final user message: the candidate answer plus a short tag saying what to produce.
     *
     * Cache-friendly on purpose: the answer comes first, exactly as it will appear in the history of the
     * next turn, and the only thing that changes per turn (a few tokens of tag) sits at the very end.
     * The model then reads just the new answer instead of the whole prompt. The question is not repeated:
     * it is the examiner message right before this one.
     *
     * @param  array<string, mixed>  $turn
     */
    public function turnInstruction(array $turn): string
    {
        if (self::isCoachDirective($turn['directive'])) {
            return $this->coachInstruction($turn);
        }

        $isEn = $turn['language'] === 'en';
        $answer = trim((string) $turn['answer']) !== '' ? trim((string) $turn['answer']) : ($isEn ? '(no answer)' : '(tidak menjawab)');
        $planned = trim((string) ($turn['planned_question'] ?? ''));
        $transition = trim((string) ($turn['transition'] ?? ''));

        $tags = [($isEn ? 'Part ' : 'Bagian ').$turn['part'].(trim((string) $turn['part_title']) !== '' ? ' - '.$turn['part_title'] : '')];
        $tags[] = match ($turn['directive']) {
            self::DIRECTIVE_FOLLOW_UP => $isEn ? 'PROBE' : 'GALI',
            self::DIRECTIVE_NEXT => ($isEn ? 'NEXT' : 'LANJUT').': "'.$planned.'"',
            self::DIRECTIVE_CLARIFY => $isEn ? 'CLARIFY' : 'JELASKAN',
            default => $isEn ? 'REACT' : 'TANGGAPI',
        };
        if ($turn['directive'] === self::DIRECTIVE_NEXT && $transition !== '') {
            $tags[] = ($isEn ? 'ANNOUNCE: ' : 'UMUMKAN: ').$transition;
        }
        // skip_focus / skip_link: set only by the training export, for written replies that do not follow the hint.
        if ($turn['directive'] === self::DIRECTIVE_FOLLOW_UP && empty($turn['skip_focus']) && ($focus = $this->signals->probeFocus($turn)) !== null) {
            $tags[] = ($isEn ? 'FOCUS: ' : 'FOKUS: ').$focus;
        }
        if ($turn['directive'] === self::DIRECTIVE_NEXT && empty($turn['skip_link']) && ($link = $this->signals->link($turn)) !== null) {
            $tags[] = ($isEn ? 'LINK: "' : 'KAITKAN: "').$link.'"';
        }
        // Models praise every single turn ("keren!", "mantap!", "bagus!") despite the rule in the system prompt;
        // a tag on this very turn is obeyed. The examiner still praises, just not twice in a row.
        if ($turn['directive'] !== self::DIRECTIVE_CLARIFY && $this->praisedLastTurn($turn)) {
            $tags[] = $isEn ? 'NO PRAISE' : 'TANPA PUJIAN';
        }

        return $answer."\n".'['.implode(' | ', array_merge($tags, $this->visualTags($turn))).']';
    }

    /**
     * Words of praise in either language. Whole words only, so "bagusnya" or "topik" do not count.
     */
    public const PRAISE = '/(?<![\p{L}])(keren|mantap|mantul|bagus|hebat|kreatif|solid|top|luar biasa|salut|impresif|seru|kece|cakep|pinter|pintar|asyik|asik|jempol|efektif banget|suka banget|great|awesome|nice|impressive|amazing|excellent|brilliant|love that|well done)(?![\p{L}])/iu';

    /**
     * Whether the examiner's previous line praised the candidate.
     *
     * @param  array<string, mixed>  $turn
     */
    protected function praisedLastTurn(array $turn): bool
    {
        foreach (array_reverse((array) ($turn['history'] ?? [])) as $item) {
            if (($item['role'] ?? null) === 'assistant') {
                return (bool) preg_match(self::PRAISE, (string) ($item['content'] ?? ''));
            }
        }

        return false;
    }

    /**
     * A model reaction followed by the scripted line, without saying the same opener twice:
     * "Oke, aku dengar." + "Oke, sekarang kita masuk Bagian 2" becomes "Oke, aku dengar. Sekarang kita masuk Bagian 2".
     */
    protected function joinReaction(string $reaction, string $scripted): string
    {
        // The scripted line announces the new part: the model announcing it too would say it twice.
        $announces = '/\b(bagian|part)\s*\d/iu';
        if (preg_match($announces, $scripted) && preg_match($announces, $reaction)) {
            $kept = array_filter(preg_split('/(?<=[.!?])\s+/u', trim($reaction)) ?: [], fn ($sentence) => ! preg_match($announces, $sentence));
            $reaction = trim(implode(' ', $kept));
        }

        if (preg_match('/^(oke|okay|ok|baik|nah|oh|wah|alright|right|so)\b[,.!]?\s+/iu', $scripted, $opener)
            && preg_match('/(?<![\p{L}])'.preg_quote($opener[1], '/').'(?![\p{L}])/iu', $reaction)) {
            $scripted = Str::ucfirst(mb_substr($scripted, mb_strlen($opener[0])));
        }

        return trim($reaction.' '.$scripted);
    }

    /**
     * What the camera noticed while the candidate answered: 'tense', 'away' (not facing
     * the camera) or null. Needs enough samples so one blink or glance does not count.
     *
     * @param  array<string, mixed>  $turn
     */
    public function visualCue(array $turn): ?string
    {
        $visual = $turn['visual'] ?? null;
        if (! is_array($visual) || (int) ($visual['samples'] ?? 0) < 5) {
            return null;
        }

        if (isset($visual['tension']) && (float) $visual['tension'] >= 40) {
            return 'tense';
        }

        $faceVisible = isset($visual['face_visible']) ? (float) $visual['face_visible'] : 100.0;
        if ($faceVisible < 50 || (isset($visual['eye_contact']) && (float) $visual['eye_contact'] < 45)) {
            return 'away';
        }

        return null;
    }

    /**
     * Tag for the model so it can respond to the candidate's face, as a real examiner would
     * (what the tag asks for is explained once, in instructionLegend()).
     *
     * @param  array<string, mixed>  $turn
     * @return list<string>
     */
    protected function visualTags(array $turn): array
    {
        $cue = $this->visualCue($turn);
        if ($cue === null || $turn['directive'] === self::DIRECTIVE_COACH_CLOSE) {
            return [];
        }

        $isEn = $turn['language'] === 'en';

        return [$cue === 'tense' ? ($isEn ? 'TENSE' : 'TEGANG') : ($isEn ? 'LOOKING AWAY' : 'TIDAK MENATAP')];
    }

    /**
     * Spoken remark used by the offline composer for the same camera cue.
     *
     * @param  array<string, mixed>  $turn
     */
    public function visualRemark(array $turn): ?string
    {
        $cue = $this->visualCue($turn);
        if ($cue === null || $turn['directive'] === self::DIRECTIVE_COACH_CLOSE || $this->analyzer->soundsUnsure((string) $turn['answer'])) {
            return null;
        }

        $isEn = $turn['language'] === 'en';
        $casual = $turn['role_type'] === 'hrd';

        return match ($cue) {
            'tense' => $isEn
                ? 'You look a little tense, take a breath, you are doing fine.'
                : ($casual ? 'Santai aja, kelihatannya kamu agak tegang, tarik napas dulu ya.' : 'Kelihatannya kamu agak tegang, tarik napas dulu ya.'),
            default => $isEn
                ? 'Try to keep looking at the camera while you answer, it makes you sound more confident.'
                : ($casual ? 'Coba sesekali lihat ke kamera ya, biar kelihatan lebih yakin.' : 'Usahakan tetap menatap kamera saat menjawab, agar terlihat lebih yakin.'),
        };
    }

    /**
     * Validate a model reply for the directive and build the spoken text.
     * Returns null when the reply is unusable, so the next engine is tried.
     *
     * @param  array<string, mixed>  $turn
     * @return array{text: string, reply: string}|null
     */
    public function resolveModelReply(string $raw, array $turn): ?array
    {
        if (self::isCoachDirective($turn['directive'])) {
            $reply = $this->clean($raw, $turn['directive'] === self::DIRECTIVE_COACH ? 5 : 3);
            if ($turn['directive'] === self::DIRECTIVE_COACH_CLOSE) {
                $reply = $this->stripQuestions($reply, 3);
            }

            return mb_strlen($reply) < 8 ? null : ['text' => $reply, 'reply' => $reply];
        }

        $reply = $this->clean($raw);
        if ($reply === '' || mb_strlen($reply) < 8) {
            return null;
        }

        $scripted = trim((string) ($turn['scripted'] ?? ''));

        switch ($turn['directive']) {
            case self::DIRECTIVE_FOLLOW_UP:
            case self::DIRECTIVE_CLARIFY:
                if (! str_contains($reply, '?')) {
                    return null;
                }
                $reply = $this->upToFirstQuestion($reply);

                return ['text' => $reply, 'reply' => $reply];

            case self::DIRECTIVE_NEXT:
                if (str_contains($reply, '?')) {
                    $reply = $this->ensureTransition($this->upToFirstQuestion($reply), $turn);

                    return ['text' => $reply, 'reply' => $reply];
                }

                // No question: keep the model's reaction and let the plan ask the question.
                $reaction = $this->stripQuestions($reply);

                return $reaction === '' ? null : ['text' => $this->joinReaction($reaction, $scripted), 'reply' => $reaction];

            default:
                $reaction = $this->stripQuestions($reply);

                return $reaction === '' ? null : ['text' => $this->joinReaction($reaction, $scripted), 'reply' => $reaction];
        }
    }

    /**
     * Rule-based turn used when no model is reachable.
     *
     * @param  array<string, mixed>  $turn
     * @return array{text: string, reply: string}
     */
    public function offlineTurn(array $turn): array
    {
        $result = $this->offlineTurnWithoutVisual($turn);
        $remark = $this->visualRemark($turn);

        return $remark === null ? $result : [
            'text' => $remark.' '.$result['text'],
            'reply' => $remark.' '.$result['reply'],
        ];
    }

    /**
     * @param  array<string, mixed>  $turn
     * @return array{text: string, reply: string}
     */
    protected function offlineTurnWithoutVisual(array $turn): array
    {
        if (self::isCoachDirective($turn['directive'])) {
            $reply = $this->offlineCoach($turn);

            return ['text' => $reply, 'reply' => $reply];
        }

        $scripted = trim((string) ($turn['scripted'] ?? ''));

        return match ($turn['directive']) {
            self::DIRECTIVE_FOLLOW_UP => ['text' => $reply = $this->offlineReply($turn), 'reply' => $reply],
            self::DIRECTIVE_CLARIFY => ['text' => $reply = $this->offlineClarify($turn), 'reply' => $reply],
            default => ['text' => $this->joinReaction($reaction = $this->offlineReaction($turn), $scripted), 'reply' => $reaction],
        };
    }

    /**
     * Keep a scripted continuation after a model reaction (used by tests and the export).
     *
     * @param  array<string, mixed>  $turn
     */
    public function compose(string $reply, array $turn): string
    {
        return ($this->resolveModelReply($reply, $turn) ?? $this->offlineTurn($turn))['text'];
    }

    /**
     * A short spoken reaction that ends with a question mark but asks nothing: "300 milidetik?", "Oh ya?",
     * "Serius?", "Dua kali lipat?". Short questions with a question word ("Kenapa?", "Terus gimana?") do ask.
     * Same rule as is_echo_reaction() in ai_service/exam_engine.py.
     */
    public static function isEchoReaction(string $sentence): bool
    {
        $sentence = trim($sentence);
        $words = preg_split('/\s+/u', $sentence) ?: [];

        return str_ends_with($sentence, '?') && count($words) <= 3
            && ! preg_match('/(?<![\p{L}])(apa|apakah|kenapa|mengapa|gimana|bagaimana|siapa|kapan|berapa|mana|kok|masa|why|how|what|which|who|when|where)(?![\p{L}])/iu', $sentence);
    }

    /**
     * Cut anything after the first real question so the turn ends on the question. A very short question
     * before it ("300 milidetik?", "Oh ya?") is a spoken reaction, not the question, so it does not end the turn.
     */
    protected function upToFirstQuestion(string $text): string
    {
        $sentences = preg_split('/(?<=[.!?])\s+/u', trim($text)) ?: [];
        $firstQuestion = null;

        foreach ($sentences as $i => $sentence) {
            if (! str_ends_with($sentence, '?')) {
                continue;
            }
            $firstQuestion ??= $i;
            if (! self::isEchoReaction($sentence)) {
                return implode(' ', array_slice($sentences, 0, $i + 1));
            }
        }

        if ($firstQuestion !== null) {
            return implode(' ', array_slice($sentences, 0, $firstQuestion + 1));
        }

        // A question mark inside a sentence ("Kenapa? karena ...") without one at a sentence end.
        $position = mb_strpos($text, '?');

        return $position === false ? $text : trim(mb_substr($text, 0, $position + 1));
    }

    /**
     * Make sure a part change is announced, so the stepper and the voice agree.
     *
     * @param  array<string, mixed>  $turn
     */
    protected function ensureTransition(string $reply, array $turn): string
    {
        $transition = trim((string) ($turn['transition'] ?? ''));
        if ($transition === '' || preg_match('/\b(bagian|part)\s*\d/iu', $reply)) {
            return $reply;
        }

        $announcement = $turn['language'] === 'en' ? "Let's move on to {$transition}." : "Sekarang kita masuk ke {$transition}.";
        $sentences = preg_split('/(?<=[.!?])\s+/u', $reply) ?: [$reply];
        $question = array_pop($sentences);

        return trim(implode(' ', $sentences).' '.$announcement.' '.$question);
    }

    /**
     * Rule-based coaching turn: one tip that fits the answer, then keep going.
     *
     * @param  array<string, mixed>  $turn
     */
    public function offlineCoach(array $turn): string
    {
        $isEn = $turn['language'] === 'en';
        $isHr = $turn['role_type'] === 'hrd';
        $you = $isEn ? 'you' : 'kamu';
        $answer = (string) $turn['answer'];
        $userText = implode(' ', array_map(fn ($item) => $item['content'], array_filter($turn['history'] ?? [], fn ($item) => $item['role'] === 'user'))).' '.$answer;
        $all = $this->analyzer->analyzeAnswer($userText, 0, '', $turn['mode']);
        $now = $this->analyzer->analyzeAnswer($answer, 0, (string) $turn['question'], $turn['mode']);

        if ($turn['directive'] === self::DIRECTIVE_COACH_CLOSE) {
            $good = match (true) {
                $all['has_example'] => $isEn ? 'You already back your points with real examples.' : ucfirst($you).' sudah bagus memberi contoh nyata.',
                count($all['connectors']) >= 2 => $isEn ? 'Your answers already flow in a clear order.' : 'Alur jawaban '.$you.' sudah runtut.',
                default => $isEn ? 'You kept talking and tried every time, that is the most important start.' : ucfirst($you).' berani mencoba dan terus bicara, itu modal paling penting.',
            };
            $practice = match (true) {
                $all['filler_rate'] >= 3 => $isEn ? 'Keep practising replacing fillers with a short silent pause.' : 'Terus latih mengganti kata pengisi dengan jeda diam sebentar.',
                ! $all['has_numbers'] => $isEn ? 'Keep practising stating results with a number.' : 'Terus latih menyebut hasil dengan angka.',
                default => $isEn ? 'Keep practising the pattern: point, reason, example.' : 'Terus latih pola: poin, alasan, contoh.',
            };

            return $isEn
                ? "Our time is up. {$good} {$practice} Good luck, you are going to do great!"
                : "Waktu kita sudah habis. {$good} {$practice} Semangat, ".($isHr ? 'aku yakin kamu bisa' : 'saya yakin kamu bisa').'!';
        }

        $said = implode(' ', array_map(fn ($item) => (string) $item['content'], array_filter($turn['history'] ?? [], fn ($item) => $item['role'] !== 'user')));
        $wrap = ! empty($turn['wrap_up']);

        // "Makasih kak, membantu banget": a warm reply and one more practice, not a critique of a "short answer".
        if ($now['word_count'] <= 14 && preg_match('/\b(makasih|terima ?kasih|thanks|thank you|membantu banget|helpful)\b/iu', $answer)) {
            return ($isEn ? 'You are welcome, glad it helps! If you want one more round, try this: ' : 'Sama-sama, senang bisa bantu! Kalau mau satu latihan lagi, coba jawab ini ya: ')
                .$this->coachPracticeQuestion($turn);
        }

        // "Saya mau latihan pertanyaan kelemahan" / "kalau ditanya gaji saya bingung": coach that very topic.
        // Checked before "unsure", which "bingung" would trigger with a generic tip.
        // Only a request ("mau latihan", "bingung", "gimana", "help") or a short remark, not an answer that mentions the word.
        $asksForTopic = $now['word_count'] <= 15 || preg_match('/\b(latihan|belajar|bingung|gimana|bagaimana|cara|bantu|ajarin|practi[cs]e|help|how)\b/iu', $answer);
        if ($asksForTopic && ($topic = $this->coachTopic($answer, $isEn)) !== null && ! str_contains($said, $topic['question'])) {
            return $topic['tip'].' '.($isEn ? 'Try it now: ' : 'Coba jawab sekarang: ').$topic['question'];
        }

        // Tips that fit this answer, best first; one already given in this chat is not said again.
        $tips = array_filter([
            $this->analyzer->soundsUnsure($answer) ? ($isEn
                ? 'That is totally fine, when you are unsure just say: "Let me think for a second", then give your best answer.'
                : 'Nggak apa-apa kok. Kalau ragu, bilang saja: "Boleh saya pikirkan sebentar?", lalu jawab semampunya, itu terdengar jauh lebih tenang.') : null,
            str_ends_with(trim($answer), '?') ? ($isEn
                ? 'Good question. The trick is simple: give the main point first in one sentence, then one real example.'
                : 'Pertanyaan bagus. Triknya sederhana: sampaikan poin utama dulu dalam satu kalimat, lalu satu contoh nyata.') : null,
            $now['filler_count'] >= 2 ? ($isEn
                ? 'I heard a few "um"s. Try a short silent pause instead, it sounds much more confident.'
                : 'Tadi ada beberapa "eh". Coba ganti dengan jeda diam sebentar, kedengarannya jauh lebih percaya diri.') : null,
            $now['word_count'] < 12 ? ($isEn
                ? 'That was quite short. Add one real example, like: "For example, when I..., I..., and the result was..."'
                : 'Jawabannya masih singkat. Tambahkan satu contoh nyata, misalnya: "Contohnya, waktu saya ..., saya ..., dan hasilnya ..."') : null,
            ! $now['has_numbers'] && $turn['role_type'] !== 'hrd' ? ($isEn
                ? 'Nice. It becomes stronger with a number, for example: "...which improved accuracy to 87 percent."'
                : 'Bagus. Akan lebih kuat kalau ada angka, misalnya: "... sehingga akurasinya naik menjadi 87 persen."') : null,
            ! $now['has_numbers'] ? ($isEn
                ? 'Nice story. End it with the result, ideally a number: "As a result, we finished two days early."'
                : 'Ceritanya sudah bagus. Tutup dengan hasilnya, kalau bisa pakai angka: "Hasilnya, kami selesai dua hari lebih cepat."') : null,
            $isEn
                ? 'That was clear and concrete. One more polish: open with your main point in the first sentence.'
                : 'Itu sudah jelas dan konkret. Satu poles lagi: sebutkan poin utamamu di kalimat pertama.',
            $isEn
                ? 'One more habit worth keeping: after your example, add one sentence on what you learned from it.'
                : 'Satu kebiasaan lagi yang bagus: setelah contoh, tambahkan satu kalimat tentang pelajaran yang kamu ambil.',
        ]);
        $fresh = array_values(array_filter($tips, fn ($tip) => ! str_contains($said, $tip)));
        $tip = $fresh[0] ?? reset($tips);

        $tryAgain = (int) $turn['turn'] % 2 === 1 && ! $wrap;
        if ($tryAgain) {
            $next = $isEn ? 'Try saying your answer again that way, I am listening.' : 'Coba ucapkan ulang jawaban '.$you.' dengan cara itu, '.($isHr ? 'aku dengarkan' : 'saya dengarkan').'.';
        } else {
            $next = ($wrap ? ($isEn ? 'We only have a moment left, one last short try: ' : 'Waktu kita tinggal sebentar, satu latihan singkat terakhir: ') : ($isEn ? 'Now try this one: ' : 'Sekarang coba jawab ini ya: '))
                .$this->coachPracticeQuestion($turn);
        }

        return trim($tip.' '.$next);
    }

    /**
     * A realistic practice question from the mode's bank that was not asked yet in this chat.
     *
     * @param  array<string, mixed>  $turn
     */
    /**
     * A topic the person asks to practise ("mau latihan pertanyaan kelemahan", "kalau ditanya gaji saya bingung"),
     * with a tip for it and a practice question. Null when the answer is not about one of these topics.
     *
     * @return array{tip: string, question: string}|null
     */
    protected function coachTopic(string $answer, bool $isEn): ?array
    {
        $topics = [
            '/kelemahan|kekurangan|weakness/iu' => [
                'Pilih kelemahan yang nyata tapi bukan inti pekerjaannya, lalu ceritakan langkah yang lagi kamu lakukan untuk memperbaikinya. Hindari jawaban klise seperti "saya perfeksionis".',
                'Apa kelemahan terbesarmu, dan apa yang lagi kamu lakukan untuk memperbaikinya?',
                'Pick a real weakness that is not the core of the job, then tell what you are doing to improve it. Avoid clichés like "I am a perfectionist".',
                'What is your biggest weakness, and what are you doing to improve it?',
            ],
            '/gaji|salary|kompensasi|ekspektasi/iu' => [
                'Sebut rentang yang realistis dari riset pasar, lalu tunjukkan kamu terbuka berdiskusi, misalnya: "Dari riset saya, kisarannya sekian sampai sekian, dan saya terbuka untuk berdiskusi."',
                'Berapa gaji yang kamu harapkan untuk posisi ini?',
                'Give a realistic range from market research, then show you are open to discussing it: "From my research the range is about this much, and I am open to discussing it."',
                'What salary do you expect for this role?',
            ],
            '/kelebihan|kekuatan|strength/iu' => [
                'Sebut satu kelebihan yang paling relevan dengan posisinya, lalu buktikan dengan satu kejadian nyata.',
                'Apa kelebihan terbesarmu, dan kapan kelebihan itu benar-benar kepakai?',
                'Name the one strength that matters most for the role, then prove it with one real moment.',
                'What is your biggest strength, and when did it really make a difference?',
            ],
            '/perkenal|ceritakan (tentang )?(diri|dirimu)|tell me about yourself|introduce/iu' => [
                'Pakai urutan sekarang, dulu, lalu nanti: siapa kamu sekarang, pengalaman yang paling relevan, lalu kenapa kamu cocok untuk posisi ini. Cukup satu menit.',
                'Coba perkenalkan dirimu dalam satu menit.',
                'Use present, past, future: who you are now, your most relevant experience, then why you fit this role. One minute is enough.',
                'Introduce yourself in one minute.',
            ],
            '/kenapa (kami|perusahaan|posisi|tertarik)|motivasi|why (us|this)/iu' => [
                'Sambungkan satu hal spesifik dari perusahaan atau posisinya dengan pengalaman atau tujuanmu sendiri. Hindari jawaban umum seperti "perusahaannya bagus".',
                'Kenapa kamu tertarik dengan posisi ini?',
                'Connect one specific thing about the company or role with your own experience or goals. Avoid generic answers like "it is a great company".',
                'Why are you interested in this role?',
            ],
            '/konflik|beda pendapat|conflict|disagree/iu' => [
                'Ceritakan dengan urutan situasi, apa yang kamu lakukan, lalu hasilnya. Tunjukkan kamu mendengarkan dulu sebelum mencari jalan tengah.',
                'Ceritain waktu kamu beda pendapat sama rekan tim, dan gimana kamu nyelesaiinnya?',
                'Tell it as situation, what you did, then the result. Show that you listened first before finding a middle ground.',
                'Tell me about a time you disagreed with a teammate, and how you resolved it?',
            ],
        ];

        foreach ($topics as $pattern => [$tipId, $questionId, $tipEn, $questionEn]) {
            if (preg_match($pattern, $answer)) {
                return $isEn ? ['tip' => $tipEn, 'question' => $questionEn] : ['tip' => $tipId, 'question' => $questionId];
            }
        }

        return null;
    }

    protected function coachPracticeQuestion(array $turn): string
    {
        $asked = implode(' ', array_map(fn ($item) => $item['content'], array_filter($turn['history'] ?? [], fn ($item) => $item['role'] !== 'user')));
        $mode = $this->blueprint->mode($turn['mode']);
        $candidates = [];
        foreach ([1, 3] as $part) {
            foreach (array_keys($mode['parts'][$part]['questions'] ?? []) as $index) {
                $candidates[] = $this->blueprint->question($turn['mode'], $part, $index, $turn['language'], $turn['topic'] ?? null);
            }
        }

        $fresh = array_values(array_filter($candidates, fn ($question) => ! str_contains($asked, $question)));
        $pool = $fresh !== [] ? $fresh : $candidates;

        return $pool[((int) $turn['turn'] * 7) % max(1, count($pool))] ?? '';
    }

    /**
     * Re-explain the current question without a model.
     *
     * @param  array<string, mixed>  $turn
     */
    public function offlineClarify(array $turn): string
    {
        $isEn = $turn['language'] === 'en';
        $sentences = preg_split('/(?<=[.!?])\s+/u', trim((string) $turn['question'])) ?: [];
        $questions = array_values(array_filter($sentences, fn ($sentence) => str_ends_with(trim($sentence), '?')));
        $question = $questions !== [] ? end($questions) : (string) end($sentences);

        $lead = $isEn ? 'No problem, let me put it another way. What I mean is, ' : 'Tidak apa-apa, saya ulangi dengan cara lain. Maksud saya, ';

        return $lead.Str::lcfirst(trim($question));
    }

    /**
     * Remove markdown, labels and stray whitespace from model output.
     */
    public function clean(string $text, int $maxSentences = 3): string
    {
        $text = preg_replace('/[\*#_`~]+/u', '', $text) ?? $text;
        // The turn tags (see instructionLegend()) are for the model, but now and then it reads one out:
        // "Cool goal. ANNOUNCE: Now we're in Part 3". Uppercase only, so ordinary words stay.
        $text = preg_replace([
            '/(?<![\p{L}])(?:ANNOUNCE|UMUMKAN|FOCUS|FOKUS|LINK|KAITKAN|PROBE|GALI|NEXT|LANJUT|REACT|TANGGAPI|CLARIFY|JELASKAN|COACH|LATIH|CLOSE|TUTUP)\s*:\s*/u',
            '/(?<![\p{L}])(?:NO PRAISE|TANPA PUJIAN|LOOKING AWAY|TIDAK MENATAP|ONE MINUTE LEFT|SISA SATU MENIT|TENSE|TEGANG)(?![\p{L}])\s*[:|]?\s*/u',
        ], '', $text) ?? $text;
        // A quote left open (the model quoted the planned question and never closed it) is read as noise.
        if (mb_substr_count($text, '“') !== mb_substr_count($text, '”')) {
            $text = str_replace(['“', '”'], '', $text);
        }
        if (substr_count($text, '"') % 2 === 1) {
            $text = str_replace('"', '', $text);
        }
        $text = preg_replace('/^\s*[\-•\d]+[\.\)]\s*/mu', '', $text) ?? $text;
        // A speaker label is a short run of capitalised words ("Prof. Hendra:", "VOIC Interview:"), never a sentence like "Bagus. Coba bilang:".
        $text = preg_replace('/^\s*(?:[\p{Lu}][\p{L}\.]*)(?:\s[\p{Lu}][\p{L}\.]*){0,3}:\s+/u', '', trim($text)) ?? $text;
        // A turn tag in brackets ("[GALI]") goes; a placeholder in an example sentence ("Kelemahan saya [sebut
        // kelemahan], ...") becomes "…", so the sentence still reads as a pattern (and the voice pauses).
        $text = preg_replace_callback('/\[([^\]]*)\]/u', fn ($m) => preg_match('/\p{Ll}/u', $m[1]) ? '…' : '', $text) ?? $text;
        // Qwen models now and then slip a Chinese/Japanese/Korean word into Indonesian ("sebagai 切入点 awal");
        // the voice cannot read it, and the sentence still makes sense without it.
        $text = preg_replace('/[\p{Han}\p{Hiragana}\p{Katakana}\p{Hangul}]+/u', '', $text) ?? $text;
        // The voice reads dashes, semicolons and brackets oddly or not at all: they become the comma pause they stand for.
        // A hyphen inside a word ("e-commerce") stays.
        // A dash between two numbers is a range ("12–15 juta"): a plain hyphen, which the voice reads as "sampai".
        $text = preg_replace('/(\d)\s*[—–‑-]\s*(?=\d)/u', '$1-', $text) ?? $text;
        $text = preg_replace(['/\s*[—–]\s*|\s+-\s+|\s*;\s*/u', '/\s*\(([^()]*)\)/u'], [', ', ', $1'], $text) ?? $text;
        $text = preg_replace('/,\s*(?=[,.!?]|$)/u', '', $text) ?? $text;
        $text = trim(preg_replace('/\s+/u', ' ', $text) ?? $text);
        $text = preg_replace('/\s+([,.!?])/u', '$1', $text) ?? $text;
        // Sentences glued together ("ya?Bagus", "Engineer.Oke") would never be counted or cut, and the voice
        // reads them as one endless turn. "S.Kom" and "3.5" stay: only after a lowercase letter.
        $text = preg_replace(['/([!?])(?=\p{Lu})/u', '/(?<=\p{Ll})\.(?=\p{Lu}\p{Ll})/u'], ['$1 ', '. '], $text) ?? $text;
        // Only unwrap quotes around the whole reply; quoted example sentences inside must stay intact.
        if (preg_match('/^["“](.*)["”]$/us', $text, $wrapped) && ! preg_match('/["“”]/u', $wrapped[1])) {
            $text = trim($wrapped[1]);
        }

        $sentences = preg_split('/(?<=[.!?])\s+/u', $text) ?: [];

        return trim(implode(' ', array_slice($sentences, 0, $maxSentences)));
    }

    /**
     * Drop sentences ending with a question mark.
     */
    protected function stripQuestions(string $text, int $limit = 2): string
    {
        $sentences = preg_split('/(?<=[.!?])\s+/u', $text) ?: [];
        $kept = array_filter($sentences, fn ($sentence) => ! str_ends_with(trim($sentence), '?'));

        return trim(implode(' ', array_slice(array_values($kept), 0, $limit)));
    }

    /**
     * Call the local FastAPI + Ollama service.
     *
     * @param  array<string, mixed>  $turn
     * @param  list<array{role: string, content: string}>  $messages
     */
    protected function callLocalModel(array $turn, array $messages): ?string
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
                    'model' => $this->ollamaModel(),
                ]);

            $reply = trim((string) $response->json('reply', ''));
            $this->lastModel = $response->json('model');
            if (! $response->successful() || $reply === '') {
                // Why the offline composer answered instead (quality gate, model error): visible in laravel.log.
                Log::info('Local examiner reply not used ('.$turn['directive'].'): '.json_encode([
                    'status' => $response->status(), 'issues' => $response->json('issues'), 'error' => $response->json('error'),
                ]));
            }

            return $response->successful() && $reply !== '' ? $reply : null;
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
    protected function synthesize(string $baseUrl, string $text, array $turn, bool $inline = false): ?string
    {
        // A cloned voice (with the speaker's permission) speaks first; without internet or quota the
        // usual voices below take over, so the exam never goes silent.
        $cloned = $this->clonedVoice($text, (string) ($turn['role_type'] ?? ''));
        if ($cloned !== null) {
            return $cloned;
        }

        try {
            $engine = $this->ttsEngine();
            // The online voice needs 2-3 seconds per sentence (more on the first connection).
            $response = Http::connectTimeout(1.0)->timeout($engine === 'edge' ? 10 : 5)->post("{$baseUrl}/tts", [
                'text' => $text,
                'role_type' => $turn['role_type'],
                'language' => $turn['language'],
                'inline' => $inline,
                'engine' => $engine,
            ]);

            if (! $response->successful()) {
                return null;
            }

            $data = $response->json('audio_data');
            $file = $response->json('audio_file');

            return $this->audioSource(is_string($data) ? $data : null, is_string($file) ? $file : null);
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Speech in an ElevenLabs voice set up for this examiner (config exam.cloned_voices), as an inline
     * MP3 data URI. Null when no voice is set for the role or the request fails.
     */
    protected function clonedVoice(string $text, string $roleType): ?string
    {
        $voiceId = trim((string) Config::get("exam.cloned_voices.{$roleType}", ''));
        $key = (string) Config::get('services.elevenlabs.key', '');
        if ($voiceId === '' || $key === '' || trim($text) === '') {
            return null;
        }

        try {
            $response = Http::connectTimeout(3)->timeout(10)
                ->withHeaders(['xi-api-key' => $key, 'Accept' => 'audio/mpeg'])
                ->post('https://api.elevenlabs.io/v1/text-to-speech/'.rawurlencode($voiceId).'?output_format=mp3_44100_64', [
                    'text' => $text,
                    // The model detects Indonesian or English from the text itself.
                    'model_id' => (string) Config::get('services.elevenlabs.model', 'eleven_flash_v2_5'),
                ]);
            if ($response->successful() && str_starts_with((string) $response->header('Content-Type'), 'audio/')) {
                return 'data:audio/mpeg;base64,'.base64_encode($response->body());
            }
            Log::warning("Cloned voice for {$roleType} failed: HTTP {$response->status()} ".Str::limit($response->body(), 200));
        } catch (\Throwable $exception) {
            Log::warning("Cloned voice for {$roleType} failed: ".$exception->getMessage());
        }

        return null;
    }

    /**
     * Voice engine for the examiner: "edge" (natural, online) or "piper" (local, offline).
     */
    public function ttsEngine(): string
    {
        $engine = (string) Config::get('exam.tts_engine', '');
        if (in_array($engine, ['edge', 'piper'], true)) {
            return $engine;
        }

        return Config::get('exam.speed_mode') === 'online' ? 'edge' : 'piper';
    }

    /**
     * Voiced backchannel clips for this examiner (audio sources, usually data URIs), synthesised once and
     * cached. An empty list means the voice service is unavailable, so the room simply stays quiet.
     *
     * @return list<string>
     */
    public function backchannels(string $roleType, string $language): array
    {
        $isEn = $language === 'en';
        $lines = (self::BACKCHANNELS[$roleType] ?? self::BACKCHANNELS['dosen_penguji'])[$isEn ? 'en' : 'id'];
        $engine = $this->ttsEngine();
        $key = "voic.backchannel.{$roleType}.".($isEn ? 'en' : 'id').".{$engine}";

        $cached = Cache::get($key);
        if (is_array($cached) && $cached !== []) {
            return $cached;
        }

        $turn = ['role_type' => $roleType, 'language' => $isEn ? 'en' : 'id'];
        $clips = array_values(array_filter(array_map(
            fn (string $line) => $this->synthesize($this->baseUrl(), $line, $turn, inline: true),
            $lines,
        )));

        // Cache only a full set in the configured voice: when the online voice is down, the service falls
        // back to Piper (WAV), and those clips must not stick around once the online voice is back.
        $fallbackVoice = $engine === 'edge' && array_filter($clips, fn (string $clip) => str_starts_with($clip, 'data:audio/wav')) !== [];
        if (count($clips) === count($lines) && ! $fallbackVoice) {
            Cache::put($key, $clips, now()->addDay());
        }

        return $clips;
    }

    /**
     * Whether a cloud key (Groq, Cerebras or OpenAI) is configured.
     */
    public function cloudConfigured(): bool
    {
        return (string) Config::get('services.groq.key', '') !== ''
            || (string) Config::get('services.cerebras.key', '') !== ''
            || (string) Config::get('services.openai.key', '') !== '';
    }

    /**
     * Speed mode "online" (VOIC_SPEED_MODE=online) with a usable cloud key: the cloud model answers first.
     */
    public function onlineFirst(): bool
    {
        return Config::get('exam.speed_mode') === 'online' && $this->cloudConfigured();
    }

    /**
     * Split a reply into spoken sentences.
     *
     * @return list<string>
     */
    protected function sentences(string $text): array
    {
        $parts = preg_split('/(?<=[.!?])\s+/u', trim($text)) ?: [];

        return array_values(array_filter(array_map('trim', $parts), fn ($part) => $part !== '')) ?: [trim($text)];
    }

    /**
     * How a stronger answer is built, per examiner. Same as IMPROVE_GUIDE in ai_service/exam_engine.py.
     *
     * @var array<string, array{id: string, en: string}>
     */
    public const IMPROVE_GUIDE = [
        'hrd' => ['id' => 'Gunakan pola STAR (situasi, tugas, aksi, hasil terukur) dan nada percaya diri tapi rendah hati.', 'en' => 'Use the STAR pattern (situation, task, action, measurable result) with a confident but humble tone.'],
        'dosen_penguji' => ['id' => 'Gunakan pola klaim, alasan, bukti/data, lalu batasan. Bahasa ilmiah yang lugas.', 'en' => 'Use claim, reason, evidence/data, then limitation. Clear academic language.'],
        'investor' => ['id' => 'Buka dengan angka atau insight paling kuat, lalu keunggulan dan langkah berikutnya. Padat dan meyakinkan.', 'en' => 'Lead with the strongest number or insight, then the edge and next step. Tight and convincing.'],
    ];

    /**
     * Rewrite a weak answer into a stronger spoken answer with the cloud model (the report uses this when the
     * local AI service is not running, e.g. a laptop without a GPU in "online" mode). Keeps the candidate's facts;
     * missing details become [placeholders]. Null when no cloud model answers.
     */
    public function improveWithCloud(string $question, string $answer, string $roleType, string $language): ?string
    {
        if (! $this->cloudConfigured() || self::cloudPaused() || count(preg_split('/\s+/u', trim($answer)) ?: []) < 3) {
            return null;
        }

        $isEn = $language === 'en';
        $raw = $this->callCloudModel([
            ['role' => 'system', 'content' => $this->improvePrompt($roleType, $isEn)],
            ['role' => 'user', 'content' => ($isEn ? "Question: {$question}\nOriginal answer: " : "Pertanyaan: {$question}\nJawaban asli: ").$answer],
        ], timeout: 10, maxTokens: 320, temperature: 0.5, waitForQuota: false);

        return $this->tidyImproved((string) $raw);
    }

    /**
     * Rewrite several weak answers in ONE cloud request (one round trip instead of three, and the free
     * per-minute token quota pays for the instructions once). Returns key => rewrite for the answers it
     * managed; the caller rewrites the rest one by one.
     *
     * @param  array<int, array{question: string, answer: string}>  $items
     * @return array<int, string>|null null when no cloud model answered
     */
    public function improveManyWithCloud(array $items, string $roleType, string $language): ?array
    {
        $items = array_filter($items, fn ($item) => count(preg_split('/\s+/u', trim((string) $item['answer'])) ?: []) >= 3);
        if ($items === [] || ! $this->cloudConfigured() || self::cloudPaused()) {
            return [];
        }

        $isEn = $language === 'en';
        $numbered = [];
        $blocks = [];
        foreach (array_values(array_keys($items)) as $i => $key) {
            $numbered[(string) ($i + 1)] = $key;
            $blocks[] = '['.($i + 1)."]\n".($isEn ? 'Question: ' : 'Pertanyaan: ').$items[$key]['question']."\n".($isEn ? 'Original answer: ' : 'Jawaban asli: ').$items[$key]['answer'];
        }
        $format = $isEn
            ? ' There are several numbered answers below. Rewrite each one separately and reply with JSON only, mapping each number to its rewrite: {"1": "...", "2": "..."}.'
            : ' Di bawah ada beberapa jawaban bernomor. Tulis ulang masing-masing secara terpisah dan balas HANYA dengan JSON yang memetakan nomor ke hasilnya: {"1": "...", "2": "..."}.';

        $raw = $this->callCloudModel([
            // The single-answer prompt ends with "reply with the rewritten answer only": here the reply is JSON.
            ['role' => 'system', 'content' => str_replace(['Reply with the rewritten answer only, in English.', 'Balas hanya dengan jawaban hasil tulis ulang, dalam Bahasa Indonesia.'], $isEn ? 'Write in English.' : 'Tulis dalam Bahasa Indonesia.', $this->improvePrompt($roleType, $isEn)).$format],
            ['role' => 'user', 'content' => implode("\n\n", $blocks)],
        ], timeout: 12, maxTokens: 180 * count($items), temperature: 0.5, waitForQuota: false);

        if ($raw === null) {
            return null; // No cloud answer at all (quota, network): the caller does not try each answer either.
        }

        $json = json_decode(trim(preg_replace('/^```(?:json)?|```$/m', '', $raw) ?? $raw), true);
        $result = [];
        foreach (is_array($json) ? $json : [] as $number => $text) {
            if (isset($numbered[(string) $number]) && is_string($text) && ($tidy = $this->tidyImproved($text)) !== null) {
                $result[$numbered[(string) $number]] = $tidy;
            }
        }

        return $result;
    }

    /**
     * Notes for the coaching report from the cloud model, when the local AI service cannot write them: what went
     * well, what to practise, sentences to reuse. Same format as /exam/coach-summary in ai_service/exam_engine.py.
     * Empty lists when no cloud model answers; never waits for the quota.
     *
     * @param  list<array{role: string, content: string}>  $transcript  role "user" or "coach"
     * @return array{strengths: list<string>, practice: list<string>, phrases: list<string>}
     */
    public function coachSummaryWithCloud(array $transcript, string $language): array
    {
        $empty = ['strengths' => [], 'practice' => [], 'phrases' => []];
        if ($transcript === [] || ! $this->cloudConfigured() || self::cloudPaused()) {
            return $empty;
        }

        $isEn = $language === 'en';
        $convo = implode("\n", array_map(fn ($turn) => ($turn['role'] === 'user' ? 'PESERTA' : 'COACH').': '.Str::limit((string) $turn['content'], 600), array_slice($transcript, -30)));
        $instruction = $isEn
            ? "Summarise this coaching chat for the participant. Use exactly this format, max 3 lines each, short and specific to what they said:\nBAGUS:\n- ...\nLATIH:\n- ...\nKALIMAT:\n- (a better sentence they can reuse, built only from their own facts: no invented numbers, say \"a certain amount\" instead)\nWrite in English."
            : "Ringkas sesi coaching ini untuk peserta. Pakai format persis ini, maksimal 3 baris per bagian, singkat dan spesifik pada yang dia katakan:\nBAGUS:\n- ...\nLATIH:\n- ...\nKALIMAT:\n- (contoh kalimat lebih baik yang bisa dia pakai, hanya dari fakta yang dia sebut sendiri: jangan mengarang angka, pakai \"sekian\")\nTulis dalam Bahasa Indonesia, sapa peserta dengan 'kamu'.";

        $raw = $this->callCloudModel([
            ['role' => 'system', 'content' => $isEn ? 'You are a concise, honest communication coach.' : 'Kamu pelatih komunikasi yang ringkas dan jujur.'],
            ['role' => 'user', 'content' => $convo."\n\n".$instruction],
        ], timeout: 10, maxTokens: 400, temperature: 0.4, waitForQuota: false);
        if ($raw === null) {
            return $empty;
        }

        // "BAGUS:\n- a\n- b\nLATIH:\n- c" -> lists per heading.
        $sections = $empty;
        $current = null;
        foreach (preg_split('/\R/u', $raw) ?: [] as $line) {
            $line = trim(preg_replace('/[\*#_`]+/u', '', $line) ?? $line);
            if (preg_match('/^(BAGUS|LATIH|KALIMAT)\s*:\s*(.*)$/iu', $line, $heading)) {
                $current = ['BAGUS' => 'strengths', 'LATIH' => 'practice', 'KALIMAT' => 'phrases'][mb_strtoupper($heading[1])];
                $line = $heading[2];
            }
            $item = trim(preg_replace('/^[\-•\d\.\)\s]+/u', '', $line) ?? $line, ' "“”');
            if ($current !== null && $item !== '' && mb_strlen($item) <= 240 && count($sections[$current]) < 3) {
                $sections[$current][] = $item;
            }
        }

        return $sections;
    }

    /**
     * Instructions for rewriting an answer (same rules as /exam/improve in ai_service/exam_engine.py).
     */
    protected function improvePrompt(string $roleType, bool $isEn): string
    {
        $guide = (self::IMPROVE_GUIDE[$roleType] ?? self::IMPROVE_GUIDE['hrd'])[$isEn ? 'en' : 'id'];

        return $isEn
            ? "You are a speaking coach. Rewrite the candidate's spoken answer into a stronger answer they could say out loud: 3-4 natural sentences, at most about 70 words, in the first person. Keep every fact the candidate gave, do not invent numbers, names, experiences, projects or achievements they did not mention; where a detail is missing, use a short placeholder in square brackets (for example [your project]). Leave out fillers such as \"um\". {$guide} Never write labels such as \"Situation:\" or \"(Action)\", the pattern should be felt from the order of the sentences. If the question is not about a past experience (a plan, a motivation, an opinion), do not force the pattern: answer directly, give a reason and one example. Reply with the rewritten answer only, in English."
            : "Kamu adalah pelatih public speaking. Tulis ulang jawaban lisan peserta menjadi jawaban yang lebih kuat dan enak diucapkan: 3-4 kalimat lisan yang natural, paling banyak sekitar 70 kata, sebagai orang pertama (saya). Pertahankan semua fakta dari peserta, jangan mengarang angka, nama, pengalaman, proyek, atau pencapaian yang tidak dia sebut; jika ada detail yang kurang, pakai placeholder singkat dalam kurung siku (misalnya [contoh proyekmu]). Buang kata pengisi seperti \"eh\". {$guide} Jangan pernah menulis label seperti \"Situasi:\" atau \"(Aksi)\", polanya cukup terasa dari urutan kalimat. Kalau pertanyaannya bukan soal pengalaman (rencana, motivasi, pendapat), jangan paksakan pola itu: jawab langsung, beri alasan, dan satu contoh. Hindari kata kaku seperti \"menyadari bahwa\", \"mengenai\", \"tersebut\". Balas hanya dengan jawaban hasil tulis ulang, dalam Bahasa Indonesia.";
    }

    /**
     * A rewritten answer ready to show, or null when it is too short or too long to be one.
     */
    public function tidyImproved(string $raw): ?string
    {
        // Placeholders in [brackets] belong here, so not clean(), which removes them.
        $text = trim(trim(preg_replace('/\s+/u', ' ', preg_replace('/[\*#_`~]+/u', '', $raw) ?? '') ?? ''), '"“”');
        // A spoken answer has no "Situasi:" / "(Aksi)" labels, even when the model writes them anyway.
        $labels = 'Situasi|Tugas|Aksi|Tindakan|Hasil|Situation|Task|Action|Result';
        $text = preg_replace(['/\s*\((?:'.$labels.')\)/iu', '/(^|[.!?]\s+)(?:'.$labels.')\s*:\s*/iu'], ['', '$1'], $text) ?? $text;
        $words = count(preg_split('/\s+/u', $text) ?: []);

        return $text !== '' && $words >= 12 && $words <= 120 ? $text : null;
    }

    /**
     * Call Groq, then Cerebras, then OpenAI, whichever keys are configured.
     *
     * @param  list<array{role: string, content: string}>  $messages
     */
    protected function callCloudModel(array $messages, int $timeout = 12, int $maxTokens = 140, float $temperature = 0.75, bool $waitForQuota = true): ?string
    {
        if (self::cloudPaused()) {
            return null;
        }

        $groq = ['key' => (string) Config::get('services.groq.key', ''), 'url' => 'https://api.groq.com/openai/v1/chat/completions'];
        $providers = [
            $groq + ['model' => (string) Config::get('services.groq.model', 'openai/gpt-oss-120b')],
            // Each Groq model has its own free quota: a second model answers when the first is busy.
            $groq + ['model' => (string) Config::get('services.groq.fallback_model', '')],
            // Free backup with its own quota (Cerebras), then the paid OpenAI key if there is one.
            ['key' => (string) Config::get('services.cerebras.key', ''), 'url' => (string) Config::get('services.cerebras.url', 'https://api.cerebras.ai/v1/chat/completions'), 'model' => (string) Config::get('services.cerebras.model', 'gpt-oss-120b')],
            ['key' => (string) Config::get('services.openai.key', ''), 'url' => 'https://api.openai.com/v1/chat/completions', 'model' => (string) Config::get('services.openai.model', 'gpt-4o-mini')],
        ];

        foreach ($providers as $provider) {
            if ($provider['key'] === '' || $provider['model'] === '') {
                continue;
            }

            $body = ['model' => $provider['model'], 'messages' => $messages, 'temperature' => $temperature, 'max_tokens' => $maxTokens];
            if (str_contains($provider['model'], 'gpt-oss')) {
                // A reasoning model thinks before it answers; the thinking shares max_tokens with the reply,
                // so with 140 the reply comes back empty. Low effort keeps it under a second on Groq.
                $body = ['reasoning_effort' => 'low', 'max_tokens' => $maxTokens + 560] + $body;
            }

            try {
                $send = fn () => Http::connectTimeout(3)->timeout($timeout)->withToken($provider['key'])->post($provider['url'], $body);
                $response = $send();
                // Free tiers limit tokens per minute (Groq: 8000, about three turns). When the quota frees up
                // within a few seconds, waiting is far quicker than the local model on a laptop CPU.
                $retryAfter = (float) $response->header('retry-after');
                // Not for extras such as the report's rewrites: finishing a session never waits for the quota.
                if ($waitForQuota && $response->status() === 429 && $retryAfter > 0 && $retryAfter <= self::CLOUD_RETRY_WAIT) {
                    Sleep::for((int) ceil($retryAfter * 1000))->milliseconds();
                    $response = $send();
                }

                $reply = trim((string) $response->json('choices.0.message.content', ''));
                // Out of tokens (a reasoning model can spend most of them thinking): the reply stops mid-word,
                // "...itu kan jawaban buat to". Keep the sentences that are complete, or ask the next model.
                if ($response->json('choices.0.finish_reason') === 'length') {
                    $complete = preg_match('/^.*[.!?…]["”]?/us', $reply, $match) ? trim($match[0]) : '';
                    if ($complete === '') {
                        Log::warning("Cloud examiner model {$provider['model']} ran out of tokens before one full sentence.");

                        continue;
                    }
                    $reply = $complete;
                }
                if ($response->successful() && $reply !== '') {
                    return $reply;
                }
                // Without this the app silently falls back to the (slow) local model, e.g. when the
                // provider retired the model or the free per-minute token quota ran out (429).
                Log::warning("Cloud examiner model {$provider['model']} failed: HTTP {$response->status()} "
                    .Str::limit((string) $response->json('error.message', $response->body()), 200));
            } catch (ConnectionException $exception) {
                // No internet (or the provider is unreachable): the other providers are on the same network.
                Cache::put(self::CLOUD_DOWN_KEY, true, self::CLOUD_DOWN_SECONDS);
                Log::warning('Cloud examiner unreachable, using the local AI for '.self::CLOUD_DOWN_SECONDS.'s: '.$exception->getMessage());

                return null;
            } catch (\Throwable $exception) {
                Log::warning('Cloud examiner model failed: '.$exception->getMessage());
            }
        }

        return null;
    }

    /**
     * True while the cloud is skipped after a failed connection.
     */
    public static function cloudPaused(): bool
    {
        return Cache::has(self::CLOUD_DOWN_KEY);
    }

    /**
     * Internet and voice status for the booth operator (cached briefly; no visitor data involved).
     *
     * @return array{internet: bool, cloud_ai: bool, online_voice: bool, ai_service: bool}
     */
    public function connectivity(): array
    {
        return Cache::remember('voic.connectivity', 15, function (): array {
            $internet = false;
            if (! self::cloudPaused()) {
                try {
                    Http::connectTimeout(2)->timeout(3)->head('https://api.groq.com');
                    $internet = true;
                } catch (\Throwable) {
                    $internet = false;
                }
            }

            $edgeOnline = null;
            try {
                $response = Http::connectTimeout(1)->timeout(2)->get($this->baseUrl().'/voice/status');
                $edgeOnline = $response->successful() ? (bool) $response->json('tts.edge_online', true) : null;
            } catch (\Throwable) {
                $edgeOnline = null;
            }

            return [
                'internet' => $internet,
                'cloud_ai' => $internet && $this->cloudConfigured(),
                'online_voice' => $internet && $edgeOnline !== false,
                'ai_service' => $edgeOnline !== null,
            ];
        });
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
        $you = 'kamu';

        $example = match ($turn['role_type']) {
            'investor' => [$isEn ? "Can you give me a real case{$about} from an actual customer?" : "Bisa beri contoh kasus nyata{$about} dari pelanggan sungguhan?"],
            'dosen_penguji' => [$isEn ? "Could you elaborate{$about} with a concrete example from your research?" : "Bisa kamu jelaskan lebih rinci{$about}, dengan contoh konkret dari penelitian kamu?"],
            default => [$isEn ? "Can you walk me through a specific moment{$about}? What exactly did you do?" : "Boleh ceritakan momen spesifiknya{$about}? Apa yang {$you} lakukan persisnya?"],
        };
        $evidence = match ($turn['role_type']) {
            'investor' => [$isEn ? 'What numbers back that up today?' : 'Angka apa yang mendukung klaim itu saat ini?'],
            'dosen_penguji' => [$isEn ? 'How did you measure that, and what were the results?' : 'Gimana cara kamu ngukurnya, dan hasilnya berapa?'],
            default => [$isEn ? 'What was the result in the end, can you put a number on it?' : 'Terus hasil akhirnya gimana, ada angka atau dampak yang bisa disebut?'],
        };
        $depth = match ($turn['role_type']) {
            'investor' => $isEn
                ? ['And what happens to the business if that assumption turns out wrong?', 'Why would a customer pay for this instead of doing nothing?', 'What would you need from an investor to prove that faster?']
                : ['Lalu apa yang terjadi pada bisnis kamu kalau asumsi itu ternyata salah?', 'Kenapa pelanggan mau membayar untuk ini, bukan tetap pakai cara lama?', 'Apa yang kamu butuhkan dari investor untuk membuktikan itu lebih cepat?'],
            'dosen_penguji' => $isEn
                ? ['What is the main limitation of that approach?', 'Why is that approach more suitable than the alternatives?', 'How would your conclusion change if the data were different?']
                : ['Terus, keterbatasan utama pendekatan itu apa?', 'Kenapa pendekatan itu lebih pas dibanding alternatif lain?', 'Kalau datanya beda, kesimpulanmu berubah nggak?'],
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
                : ['Bisa diceritain sedikit lebih lengkap?', 'Alasannya apa?'],
            default => $isEn
                ? ['Tell me a bit more, I would love to get to know you better.', 'Why is that, is there a small example?']
                : ['Boleh cerita sedikit lebih lengkap? Saya pengen kenal kamu lebih jauh.', 'Kenapa begitu? Ada contoh kecilnya?'],
        };

        // Part 1 is a warm-up: just invite more. Later parts ask for what is missing, then go deeper.
        // A clear gap the examiner would notice (a "we" story, a claim without a comparison) comes first.
        $targeted = $this->targetedProbe($turn);
        $candidates = match (true) {
            (int) ($turn['part'] ?? 0) === 1 => [...$elaborate, ...$example],
            $metrics['word_count'] < 12 || ! $metrics['has_example'] => [...$example, ...$evidence, ...$depth],
            ! $metrics['has_numbers'] => [...$evidence, ...$depth, ...$example],
            default => [...$depth, ...$evidence, ...$example],
        };
        if ($targeted !== null) {
            array_unshift($candidates, $targeted);
        }

        $asked = implode(' ', array_map(fn ($item) => $item['content'], array_filter($turn['history'] ?? [], fn ($item) => $item['role'] !== 'user')));

        foreach ($candidates as $candidate) {
            if (! str_contains($asked, $candidate)) {
                return $candidate;
            }
        }

        return end($candidates) ?: '';
    }

    /**
     * A scripted question aimed at the gap ConversationSignals found, for when no model is reachable.
     *
     * @param  array<string, mixed>  $turn
     */
    public function targetedProbe(array $turn): ?string
    {
        $gap = $this->signals->gap($turn);
        if ($gap === null) {
            return null;
        }

        $isEn = $turn['language'] === 'en';
        $you = 'kamu';

        return match ($gap[0]) {
            'contradiction' => $isEn
                ? 'Just to be sure I have it right, earlier you gave a different number for that. Which one is correct?'
                : 'Supaya saya tidak salah catat, tadi '.$you.' sempat menyebut angka yang berbeda untuk hal itu. Yang benar yang mana?',
            'we' => $isEn ? 'You mentioned the team a lot. What did you personally do in that?' : 'Dari tadi banyak "kami", nah bagian yang benar-benar kamu kerjakan sendiri yang mana?',
            'trait' => $isEn ? 'Can you tell me about one real moment where that trait actually showed?' : 'Boleh ceritakan satu kejadian nyata yang menunjukkan sifat itu?',
            'result' => $isEn ? 'And how did it end, what was the result?' : 'Terus akhirnya gimana, hasilnya apa?',
            'competitors' => $isEn ? 'If nobody does this yet, how do your customers solve the problem today?' : 'Kalau belum ada pesaing, sekarang pelanggan menyelesaikan masalah itu pakai cara apa?',
            'no_number' => $isEn ? 'What number shows that today?' : 'Angka apa yang menunjukkan hal itu hari ini?',
            'broad_market' => $isEn ? 'That is a very broad market. Which customer segment are you going after first?' : 'Itu pasar yang luas banget. Segmen pelanggan mana yang mau kamu kejar duluan?',
            'funds' => $isEn ? 'What exactly will the money be used for?' : 'Dananya nanti akan dipakai untuk apa saja persisnya?',
            'comparison' => $isEn ? 'Compared with which method, and measured with what metric?' : 'Dibandingkan dengan metode apa, dan diukur dengan metrik apa?',
            'method_reason' => $isEn ? 'Why did you choose that method over the alternatives?' : 'Kenapa kamu milih metode itu, bukan alternatif lainnya?',
            'sample' => $isEn ? 'How much data did you use, and how did you select it?' : 'Berapa jumlah data atau sampel yang kamu pakai, dan bagaimana cara memilihnya?',
            'overgeneral' => $isEn ? 'Is that true in every case, and what are its limitations?' : 'Apakah itu berlaku di semua kasus, dan apa keterbatasannya?',
            default => null,
        };
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
                ? ['Wah, seru juga soal :phrase!', 'Ih, bagian :phrase itu kebayang banget.', 'Oke, aku catat soal :phrase ya.', 'Aduh, :phrase, aku suka itu.']
                : ['Makasih ya ceritanya, seru banget!', 'Oke, kebayang kok.', 'Wah, oke, paham.', 'Makasih udah cerita.'],
            'investor' => $withPhrase
                ? ['Oke, oke, :phrase. Noted.', 'Nah ini, poin :phrase saya tangkap.', 'Bentar, :phrase ya.', 'Oke, saya catat :phrase.']
                : ['Oke, noted.', 'Oke, oke, saya tangkap.', 'Sip, jelas.', 'Nah, oke.'],
            default => $withPhrase
                ? ['Baik. Saya catat penjelasanmu soal :phrase.', 'Begini ya, bagian :phrase cukup jelas.', 'Baik, :phrase, saya pahami.', 'Poin kamu tentang :phrase sudah saya catat.']
                : ['Baik. Terima kasih penjelasannya.', 'Baik, saya pahami.', 'Oke, saya catat.', 'Baik, cukup jelas.'],
        };
    }
}
