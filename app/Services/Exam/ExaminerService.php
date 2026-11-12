<?php

namespace App\Services\Exam;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
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
            $streamed = $this->streamLocalModel($turn, $messages, $onSentence);
            if ($streamed !== null) {
                return $streamed;
            }
        }

        $result = null;
        $local = $this->callLocalModel($turn, $messages);
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
            $messages = $this->buildMessages($turn + ['answer' => '', 'directive' => self::DIRECTIVE_REACT, 'planned_question' => null, 'transition' => null]);
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
     * Transcribe a recorded answer with the local Whisper model.
     *
     * @return array{text: string, engine: string}|null
     */
    public function transcribe(string $audioPath, string $language, ?string $topic = null): ?array
    {
        try {
            $response = Http::connectTimeout(1.5)->timeout(40)
                ->attach('audio', (string) file_get_contents($audioPath), 'answer.webm')
                ->post($this->baseUrl().'/stt', array_filter([
                    'language' => $language,
                    'prompt' => $topic,
                ]));

            if (! $response->successful()) {
                return null;
            }

            return ['text' => trim((string) $response->json('text', '')), 'engine' => (string) $response->json('engine', 'whisper')];
        } catch (\Throwable $exception) {
            Log::info('Local STT unavailable: '.$exception->getMessage());

            return null;
        }
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
        if ($status['speed_mode'] === 'online') {
            if ($this->cloudConfigured()) {
                if (! OpenRouterClient::handles($chosen)) {
                    $status['engine'] = 'cloud_llm';
                    $status['model'] = (string) Config::get((string) Config::get('services.groq.key', '') !== '' ? 'services.groq.model' : 'services.openai.model');
                }
                $status['prefer_browser_stt'] = true;
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
     * Persona and speaking rules for the examiner.
     *
     * @param  array<string, mixed>  $turn
     */
    public function systemPrompt(array $turn): string
    {
        if (($turn['session_type'] ?? null) === 'coach') {
            return $this->coachSystemPrompt($turn);
        }

        $isEn = $turn['language'] === 'en';
        $name = $turn['examiner_name'];
        $topic = $turn['topic'] ? Str::limit((string) $turn['topic'], 120) : null;
        $context = $turn['context_material'] ? Str::limit((string) $turn['context_material'], 500) : null;

        $persona = match ($turn['role_type']) {
            'hrd' => $isEn
                ? "You are {$name}, a warm and relaxed senior HR recruiter. You make candidates feel at ease, you sound like a real person chatting, and you are genuinely curious about their real experiences."
                : "Kamu adalah {$name}, HR recruiter senior yang hangat dan santai. Kamu bikin kandidat nyaman, bicaramu seperti orang sungguhan yang sedang ngobrol santai, dan kamu benar-benar penasaran dengan pengalaman nyata mereka. Panggil kandidat dengan 'kamu'.",
            'investor' => $isEn
                ? "You are {$name}, a sharp but friendly venture capital partner. You are direct, a bit playful, you love concrete numbers, and you test every claim."
                : "Kamu adalah {$name}, partner venture capital yang tajam tapi bersahabat dan sedikit jenaka. Kamu lugas, suka angka konkret, dan menguji setiap klaim. Panggil founder dengan 'Anda'.",
            default => $isEn
                ? "You are {$name}, a calm and fair lead thesis examiner. You respect the student, you speak formally but warmly, and you test methodology and reasoning."
                : "Kamu adalah {$name}, dosen penguji utama yang tenang, adil, dan tetap hangat. Kamu menghargai mahasiswa, bicara dengan bahasa formal yang santun, dan menguji metodologi serta logika penelitian. Panggil mahasiswa dengan 'Anda'.",
        };

        $rules = $isEn
            ? [
                'You are talking out loud in a live mock exam. Plain spoken sentences only: no lists, no markdown, no emoji, no speaker labels.',
                'This is a real conversation, not a script. Always react to something specific the candidate just said, in your own words, and connect your next question to it when you can.',
                'Ask at most one question per turn and never repeat a question already asked.',
                'Remember everything the candidate has told you in this session (names, projects, numbers, claims). Refer back to it, and gently point out when a new answer contradicts an earlier one.',
                'If the answer is vague or very short, gently ask for a concrete example, a number, or the reason behind it.',
                'If the candidate is nervous, jokes, or says they do not know, respond like a kind human in one short sentence, then keep going.',
                'Never give the full model answer and never score the candidate during the exam.',
                'Open your reply with a concrete detail from the last answer (a name, number, term or event) instead of generic praise, and do not start two turns in a row with the same word (such as "Interesting", "Okay", "Great").',
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
                'Buka tanggapan dengan detail konkret dari jawaban terakhir (nama, angka, istilah, atau kejadian), bukan pujian umum, dan jangan memulai dua giliran berturut-turut dengan kata yang sama (misalnya "Menarik", "Oke", "Wah", "Baik").',
                'Susun pertanyaan dari kata-kata peserta sendiri, sehingga pertanyaan itu hanya cocok untuk orang ini; jangan menanyakan hal yang sudah dia jawab, walau dengan kalimat berbeda.',
                'Kalau peserta balik bertanya (soal posisi, perusahaan, atau pendapatmu), jawab singkat dan realistis sesuai peranmu, lalu kembali ke pertanyaanmu.',
            ];

        $lines = [$persona];
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
        $name = $turn['examiner_name'];
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
                : "Kamu adalah {$name}, {$title} yang sudah mendengar ribuan pitch. Ramah, lugas, praktis, dan suka contoh konkret. Panggil lawan bicara dengan 'Anda'.",
            default => $isEn
                ? "You are {$name}, a patient {$title}. Calm, encouraging, precise with academic language."
                : "Kamu adalah {$name}, {$title} yang sabar. Tenang, menyemangati, dan teliti soal bahasa ilmiah. Panggil mahasiswa dengan 'Anda'.",
        };

        $rules = $isEn
            ? [
                'This is a relaxed coaching chat, NOT a test. Never give scores. The goal is that the person learns how to answer better.',
                'Talk like a real person in a voice call: short spoken sentences, no lists, no markdown, no emoji, no speaker labels.',
                'Every turn: react to what they actually said, then teach ONE concrete thing (wording, structure, tone, or a filler habit). When useful, give a better example sentence in quotes that they can copy.',
                'Then keep the conversation going: ask them to try saying it again in the better way, or give a new realistic practice question, or ask what they want to work on next.',
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
                'Ikuti arah obrolannya. Kalau dia bertanya, jawab dulu dengan sederhana dan praktis.',
                'Ingat apa yang dia ceritakan sebelumnya di obrolan ini dan lanjutkan dari situ, jangan mulai dari nol.',
                'Kalau dia gugup atau ragu, semangati dalam satu kalimat pendek sebelum mengajari.',
                'Keterampilan yang bisa kamu latih: '.$skills.'.',
            ];

        $lines = [$persona];
        if ($topic) {
            $lines[] = ($isEn ? 'They are preparing for: ' : 'Peserta sedang bersiap untuk: ').$topic;
        }
        if ($context) {
            $lines[] = ($isEn ? 'Background from the person: ' : 'Latar dari peserta: ').$context;
        }
        $lines[] = implode("\n", array_map(fn ($rule, $i) => ($i + 1).'. '.$rule, $rules, array_keys($rules)));
        $lines[] = $this->instructionLegend($isEn, coach: true);

        return implode("\n", $lines);
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
                    .'Founder: "We already have 300 users after two months." You: "Three hundred in two months is a decent start. How many of them are still active this month, and how many pay?"'
                : "Cara Anda menggali seperti investor berpengalaman: setiap klaim besar harus ada angkanya (pengguna, pendapatan, pertumbuhan per bulan, CAC, retensi). Klaim \"tidak ada pesaing\" berarti tanyakan apa yang dipakai pelanggan sekarang. Uji ukuran pasar yang realistis, model bisnis, keunggulan yang sulit ditiru, tim, dan untuk apa dana dipakai. Kalau angka terdengar terlalu optimis, tanyakan asumsinya.\n"
                    ."Contoh gaya (tiru polanya, bukan isinya):\n"
                    ."Founder: \"Pasarnya besar sekali dan belum ada yang garap.\" Kamu: \"Pasar besar yang masih kosong biasanya justru bikin saya waspada. Sebelum ada produk Anda, pelanggan menyelesaikan masalah ini pakai cara apa?\"\n"
                    .'Founder: "Dalam dua bulan kami sudah punya 300 pengguna." Kamu: "Tiga ratus pengguna dalam dua bulan itu awal yang lumayan. Berapa yang masih aktif bulan ini, dan berapa yang sudah membayar?"',
            'dosen_penguji' => $isEn
                ? "How you examine, like a thesis examiner: tie every answer back to the research problem and objectives. A claim that something is \"better\" or \"more accurate\" must say compared to what and measured by which metric. Ask why the method was chosen, how much data or how many respondents and how they were selected, validity and reliability, limitations, and the contribution of the research. Stay courteous, never belittle.\n"
                    ."Style examples (copy the pattern, not the content):\n"
                    ."Student: \"My model is more accurate for detecting leaf disease.\" You: \"More accurate is a strong claim. Compared with which method, and with what metric did you measure it?\"\n"
                    .'Student: "I collected data from 50 respondents through a questionnaire." You: "Fifty respondents through a questionnaire, noted. How did you choose them, and is that number enough for the analysis you used?"'
                : "Cara Anda menguji seperti dosen penguji sidang: kaitkan setiap jawaban ke rumusan masalah dan tujuan penelitian. Klaim \"lebih baik\" atau \"lebih akurat\" harus jelas dibandingkan dengan apa dan diukur dengan metrik apa. Tanyakan alasan memilih metode, jumlah data atau responden dan cara memilihnya, validitas dan reliabilitas, keterbatasan, serta kontribusi penelitian. Tetap santun, jangan menjatuhkan.\n"
                    ."Contoh gaya (tiru polanya, bukan isinya):\n"
                    ."Mahasiswa: \"Model saya lebih akurat untuk mendeteksi penyakit daun.\" Kamu: \"Lebih akurat itu klaim yang kuat. Dibandingkan dengan metode apa, dan dengan metrik apa Anda mengukurnya?\"\n"
                    .'Mahasiswa: "Saya mengambil data dari 50 responden lewat kuesioner." Kamu: "Lima puluh responden lewat kuesioner, saya catat. Bagaimana Anda memilih mereka, dan apakah jumlah itu cukup untuk analisis yang Anda pakai?"',
            default => $isEn
                ? "How you dig, like an experienced recruiter: follow the STAR pattern (situation, task, action, result). When the candidate says \"we\" a lot, ask what they did themselves. When they claim a trait (hard-working, detail-oriented), ask for one real moment that shows it. When a story stops at the action, ask about the result or impact. Listen for motivation, teamwork and fit with the role.\n"
                    ."Style examples (copy the pattern, not the content):\n"
                    ."Candidate: \"We doubled the online store's sales in three months.\" You: \"Doubling sales in three months is a big jump. In that team, which part did you personally own?\"\n"
                    .'Candidate: "I am very detail-oriented." You: "Detail-oriented, nice. Can you tell me about a time that habit actually saved a piece of work?"'
                : "Cara kamu menggali seperti recruiter berpengalaman: kejar pola STAR (situasi, tugas, aksi, hasil). Kalau kandidat banyak bilang \"kami\", tanyakan apa yang dia kerjakan sendiri. Kalau dia mengaku punya sifat baik (teliti, pekerja keras), minta satu kejadian nyata yang membuktikannya. Kalau cerita berhenti di aksi, tanyakan hasil atau dampaknya. Perhatikan motivasi, kerja sama tim, dan kecocokan dengan posisi.\n"
                    ."Contoh gaya (tiru polanya, bukan isinya):\n"
                    ."Kandidat: \"Kami berhasil menaikkan penjualan toko online dua kali lipat dalam tiga bulan.\" Kamu: \"Dua kali lipat dalam tiga bulan itu lompatan besar. Di tim itu, bagian mana yang benar-benar kamu pegang sendiri?\"\n"
                    .'Kandidat: "Saya orangnya teliti banget." Kamu: "Teliti, oke. Kapan terakhir kali ketelitianmu itu benar-benar menyelamatkan sebuah pekerjaan?"',
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
                    .'Extra tag TENSE = they looked tense on camera: start with one short, kind sentence that helps them relax. Extra tag LOOKING AWAY = they often looked away from the camera: briefly and kindly remind them to keep eye contact.'
                : "Setiap pesan peserta diakhiri tag dalam kurung siku yang memberi tahu tugasmu di giliran ini (jangan pernah dibacakan):\n"
                    ."[LATIH] maksimal 4 kalimat lisan: tanggapi yang dia katakan, ajarkan satu perbaikan konkret (dengan contoh kalimat yang lebih baik dalam tanda kutip jika berguna), lalu jaga obrolan tetap berlanjut.\n"
                    ."[LATIH | SISA SATU MENIT] sama, tetapi mulai rangkum tips paling penting dan minta satu percobaan singkat terakhir.\n"
                    ."[TUTUP] waktu habis: tutup dengan hangat maksimal 3 kalimat (satu hal yang sudah bagus, satu kebiasaan yang perlu terus dilatih, salam penyemangat). Jangan bertanya.\n"
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
                .'Extra tag TENSE = the candidate looked tense on camera: start with one short, kind sentence that helps them relax (for example, suggest a breath). Extra tag LOOKING AWAY = they often looked away from the camera: briefly and kindly remind them to keep eye contact.'
            : "Setiap pesan peserta diakhiri tag dalam kurung siku: bagian ujian saat ini dan tugasmu di giliran ini (jangan pernah dibacakan):\n"
                ."[TANGGAPI] SATU kalimat pendek yang menanggapi jawaban secara spesifik. Jangan bertanya apa pun, setelah ini ada pengumuman.\n"
                ."[GALI] maksimal 2 kalimat: tanggapan singkat yang spesifik, lalu tepat SATU pertanyaan lanjutan yang menggali jawaban itu lebih dalam (kenapa, bagaimana, contoh, atau angka).\n"
                ."[LANJUT: \"topik\"] maksimal 3 kalimat: tanggapi jawaban secara spesifik, lalu beralih dengan luwes dan tanyakan topik dalam tanda kutip dengan kata-katamu sendiri, kaitkan dengan jawaban peserta bila cocok. Jika ada \"UMUMKAN: ...\", sebutkan juga bahwa kita sekarang masuk ke bagian itu.\n"
                ."[JELASKAN] peserta belum paham atau balik bertanya: dalam maksimal 2 kalimat, jelaskan maksud pertanyaanmu dengan lebih sederhana (boleh dengan contoh kecil), lalu tanyakan lagi dengan kata-kata berbeda. Kalau yang dia tanyakan hal lain (posisi, perusahaan, pendapatmu), jawab singkat dulu, lalu kembali ke pertanyaanmu.\n"
                ."Tag tambahan FOKUS: ... = hal yang menurut penguji berpengalaman masih kurang atau meragukan dari jawaban itu: arahkan pertanyaanmu tepat ke situ dengan kata-katamu sendiri, tanpa menyebut tagnya (untuk \"cek konsistensi\", tanyakan dengan sopan mana yang benar).\n"
                ."Tag tambahan KAITKAN: ... = hal yang pernah dikatakan peserta di sesi ini dan cocok dengan topik berikutnya: rujuk kembali secara alami saat membuka topik itu (\"Tadi Anda sempat menyebut ...\").\n"
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
        if ($turn['directive'] === self::DIRECTIVE_FOLLOW_UP && ($focus = $this->signals->probeFocus($turn)) !== null) {
            $tags[] = ($isEn ? 'FOCUS: ' : 'FOKUS: ').$focus;
        }
        if ($turn['directive'] === self::DIRECTIVE_NEXT && ($link = $this->signals->link($turn)) !== null) {
            $tags[] = ($isEn ? 'LINK: "' : 'KAITKAN: "').$link.'"';
        }

        return $answer."\n".'['.implode(' | ', array_merge($tags, $this->visualTags($turn))).']';
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
                : ($casual ? 'Santai aja, kelihatannya kamu agak tegang, tarik napas dulu ya.' : 'Saya lihat Anda sedikit tegang, coba tarik napas sebentar.'),
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

                return $reaction === '' ? null : ['text' => trim($reaction.' '.$scripted), 'reply' => $reaction];

            default:
                $reaction = $this->stripQuestions($reply);

                return $reaction === '' ? null : ['text' => trim($reaction.' '.$scripted), 'reply' => $reaction];
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
            default => ['text' => trim(($reaction = $this->offlineReaction($turn)).' '.$scripted), 'reply' => $reaction],
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
     * Cut anything after the first question so the turn ends on the question.
     */
    protected function upToFirstQuestion(string $text): string
    {
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
        $you = $isEn ? 'you' : ($isHr ? 'kamu' : 'Anda');
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
                : "Waktu kita sudah habis. {$good} {$practice} Semangat, ".($isHr ? 'aku yakin kamu bisa' : 'saya yakin Anda bisa').'!';
        }

        if ($this->analyzer->soundsUnsure($answer)) {
            $tip = $isEn
                ? 'That is totally fine, when you are unsure just say: "Let me think for a second", then give your best answer.'
                : 'Nggak apa-apa kok. Kalau ragu, bilang saja: "Boleh saya pikirkan sebentar?", lalu jawab semampunya, itu terdengar jauh lebih tenang.';
        } elseif (str_ends_with(trim($answer), '?')) {
            $tip = $isEn
                ? 'Good question. The trick is simple: give the main point first in one sentence, then one real example.'
                : 'Pertanyaan bagus. Triknya sederhana: sampaikan poin utama dulu dalam satu kalimat, lalu satu contoh nyata.';
        } elseif ($now['filler_count'] >= 2) {
            $tip = $isEn
                ? 'I heard a few "um"s. Try a short silent pause instead, it sounds much more confident.'
                : 'Tadi ada beberapa "eh". Coba ganti dengan jeda diam sebentar, kedengarannya jauh lebih percaya diri.';
        } elseif ($now['word_count'] < 12) {
            $tip = $isEn
                ? 'That was quite short. Add one real example, like: "For example, when I..., I..., and the result was..."'
                : 'Jawabannya masih singkat. Tambahkan satu contoh nyata, misalnya: "Contohnya, waktu saya ..., saya ..., dan hasilnya ..."';
        } elseif (! $now['has_numbers'] && $turn['role_type'] !== 'hrd') {
            $tip = $isEn
                ? 'Nice. It becomes stronger with a number, for example: "...which improved accuracy to 87 percent."'
                : 'Bagus. Akan lebih kuat kalau ada angka, misalnya: "... sehingga akurasinya naik menjadi 87 persen."';
        } elseif (! $now['has_numbers']) {
            $tip = $isEn
                ? 'Nice story. End it with the result, ideally a number: "As a result, we finished two days early."'
                : 'Ceritanya sudah bagus. Tutup dengan hasilnya, kalau bisa pakai angka: "Hasilnya, kami selesai dua hari lebih cepat."';
        } else {
            $tip = $isEn
                ? 'That was clear and concrete. One more polish: open with your main point in the first sentence.'
                : 'Itu sudah jelas dan konkret. Satu poles lagi: sebutkan poin utamamu di kalimat pertama.';
        }

        $wrap = ! empty($turn['wrap_up']);
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
        $text = preg_replace('/^\s*[\-•\d]+[\.\)]\s*/mu', '', $text) ?? $text;
        // A speaker label is a short run of capitalised words ("Prof. Hendra:", "Kak Nadia:"), never a sentence like "Bagus. Coba bilang:".
        $text = preg_replace('/^\s*(?:[\p{Lu}][\p{L}\.]*)(?:\s[\p{Lu}][\p{L}\.]*){0,3}:\s+/u', '', trim($text)) ?? $text;
        $text = preg_replace('/\[[^\]]*\]/u', '', $text) ?? $text;
        $text = trim(preg_replace('/\s+/u', ' ', $text) ?? $text);
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
        try {
            $response = Http::connectTimeout(1.0)->timeout(5)->post("{$baseUrl}/tts", [
                'text' => $text,
                'role_type' => $turn['role_type'],
                'language' => $turn['language'],
                'inline' => $inline,
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
     * Whether a cloud key (Groq or OpenAI) is configured.
     */
    public function cloudConfigured(): bool
    {
        return (string) Config::get('services.groq.key', '') !== '' || (string) Config::get('services.openai.key', '') !== '';
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
     * Call Groq or OpenAI when keys are configured.
     *
     * @param  list<array{role: string, content: string}>  $messages
     */
    protected function callCloudModel(array $messages, int $timeout = 12): ?string
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
                $response = Http::connectTimeout(3)->timeout($timeout)->withToken($provider['key'])->post($provider['url'], [
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
        $you = $turn['role_type'] === 'hrd' ? 'kamu' : 'Anda';

        return match ($gap[0]) {
            'contradiction' => $isEn
                ? 'Just to be sure I have it right, earlier you gave a different number for that. Which one is correct?'
                : 'Supaya saya tidak salah catat, tadi '.$you.' sempat menyebut angka yang berbeda untuk hal itu. Yang benar yang mana?',
            'we' => $isEn ? 'You mentioned the team a lot. What did you personally do in that?' : 'Dari tadi banyak "kami", nah bagian yang benar-benar kamu kerjakan sendiri yang mana?',
            'trait' => $isEn ? 'Can you tell me about one real moment where that trait actually showed?' : 'Boleh ceritakan satu kejadian nyata yang menunjukkan sifat itu?',
            'result' => $isEn ? 'And how did it end, what was the result?' : 'Terus akhirnya gimana, hasilnya apa?',
            'competitors' => $isEn ? 'If nobody does this yet, how do your customers solve the problem today?' : 'Kalau belum ada pesaing, sekarang pelanggan menyelesaikan masalah itu pakai cara apa?',
            'no_number' => $isEn ? 'What number shows that today?' : 'Angka apa yang menunjukkan hal itu hari ini?',
            'broad_market' => $isEn ? 'That is a very broad market. Which customer segment are you going after first?' : 'Itu pasar yang sangat luas. Segmen pelanggan mana yang Anda kejar pertama kali?',
            'funds' => $isEn ? 'What exactly will the money be used for?' : 'Dananya nanti akan dipakai untuk apa saja persisnya?',
            'comparison' => $isEn ? 'Compared with which method, and measured with what metric?' : 'Dibandingkan dengan metode apa, dan diukur dengan metrik apa?',
            'method_reason' => $isEn ? 'Why did you choose that method over the alternatives?' : 'Mengapa Anda memilih metode itu dibanding alternatif lainnya?',
            'sample' => $isEn ? 'How much data did you use, and how did you select it?' : 'Berapa jumlah data atau sampel yang Anda pakai, dan bagaimana cara memilihnya?',
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
