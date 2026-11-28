<?php

namespace App\Services\Exam;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * The examiner's ears and voice: speech to text for recorded answers (local Whisper, or Groq/OpenAI
 * Whisper in online speed mode) and text to speech for every examiner sentence (a cloned ElevenLabs
 * voice when set, otherwise Edge or Piper through the local AI service). Every method fails quietly:
 * without a voice the room falls back to the browser's speech, without ears to typing.
 */
class ExaminerVoice
{
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
     * Voice a fixed line (opening greeting) with the local TTS, if available.
     */
    public function speak(string $text, string $roleType, string $language): ?string
    {
        return $this->synthesize($text, ['role_type' => $roleType, 'language' => $language]);
    }

    /**
     * Playable source for generated audio: an inline data URI, or a proxied file URL.
     */
    public function audioSource(?string $data, ?string $file): ?string
    {
        if ($data !== null && str_starts_with($data, 'data:audio/')) {
            return $data;
        }

        return $file ? $this->audioUrl($file) : null;
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

        if (ExaminerService::onlineFirst()) {
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

        if (! ExaminerService::onlineFirst() && ExaminerService::cloudConfigured()) {
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
        if (ExaminerService::cloudPaused()) {
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
     * Ask the local service for neural TTS audio of the final text.
     *
     * @param  array<string, mixed>  $turn
     */
    public function synthesize(string $text, array $turn, bool $inline = false): ?string
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
            $response = Http::connectTimeout(1.0)->timeout($engine === 'edge' ? 10 : 5)->post($this->baseUrl().'/tts', [
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
            fn (string $line) => $this->synthesize($line, $turn, inline: true),
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
     * Base URL of the local FastAPI service.
     */
    protected function baseUrl(): string
    {
        return rtrim((string) Config::get('services.ai_service.url', 'http://127.0.0.1:8001'), '/');
    }
}
