<?php

namespace App\Services\Exam;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Chat completions through OpenRouter, e.g. the free "moonshotai/kimi-k2.6:free".
 *
 * Models are written as "openrouter/<vendor>/<model>" (VOIC_AI_MODEL, VOIC_TEACHER_MODEL)
 * so they cannot be mistaken for an Ollama model name.
 */
class OpenRouterClient
{
    public const PREFIX = 'openrouter/';

    /**
     * Why the last call failed, for the teacher command to explain; null after a success.
     */
    protected ?string $lastError = null;

    /**
     * Whether the last failure was a daily/credit limit that waiting a few seconds will not fix.
     */
    protected bool $quotaExhausted = false;

    public static function handles(?string $model): bool
    {
        return str_starts_with((string) $model, self::PREFIX);
    }

    public function configured(): bool
    {
        return (string) Config::get('services.openrouter.key', '') !== '';
    }

    public function lastError(): ?string
    {
        return $this->lastError;
    }

    public function quotaExhausted(): bool
    {
        return $this->quotaExhausted;
    }

    /**
     * One reply, or null when OpenRouter is unreachable, refuses, or stays rate limited.
     * Short rate limits (free models allow a few requests per minute) are waited out.
     *
     * @param  list<array{role: string, content: string}>  $messages
     */
    public function chat(array $messages, string $model, float $temperature = 0.7): ?string
    {
        $this->lastError = null;
        $this->quotaExhausted = false;

        if (! $this->configured()) {
            $this->lastError = 'OPENROUTER_API_KEY belum diisi di .env.';

            return null;
        }

        $retries = (int) Config::get('services.openrouter.retries', 3);
        for ($attempt = 0; $attempt <= $retries; $attempt++) {
            try {
                $response = Http::connectTimeout(5)
                    ->timeout((int) Config::get('services.openrouter.timeout', 120))
                    ->withToken((string) Config::get('services.openrouter.key'))
                    ->withHeaders(['X-Title' => 'VOIC'])
                    ->post(rtrim((string) Config::get('services.openrouter.url', 'https://openrouter.ai/api/v1'), '/').'/chat/completions', [
                        'model' => Str::after($model, self::PREFIX),
                        'messages' => $messages,
                        'temperature' => $temperature,
                        // Kimi thinks before it answers; leave room for that, and do not send the reasoning back.
                        'max_tokens' => 4000,
                        'reasoning' => ['exclude' => true],
                    ]);
            } catch (\Throwable $exception) {
                $this->lastError = 'OpenRouter tidak bisa dihubungi: '.$exception->getMessage();
                Log::info($this->lastError);

                return null;
            }

            $content = trim((string) $response->json('choices.0.message.content', ''));
            if ($response->successful() && $content !== '') {
                return $content;
            }

            $message = (string) ($response->json('error.message') ?? $response->body());
            $this->lastError = "OpenRouter HTTP {$response->status()}: ".Str::limit($message, 300);

            // 402 = no credit; a 429 that mentions a daily/free limit will not clear in seconds.
            if ($response->status() === 402 || ($response->status() === 429 && preg_match('/per[- ]day|daily|free-models-per-day|credits/i', $message))) {
                $this->quotaExhausted = true;

                return null;
            }

            if ($response->status() !== 429 && $response->status() < 500 && $response->successful() === false) {
                return null;
            }

            if ($attempt < $retries) {
                $wait = (int) ($response->header('Retry-After') ?: 0);
                sleep(max(1, min(60, $wait ?: (int) Config::get('services.openrouter.backoff', 15) * ($attempt + 1))));
            }
        }

        if ($this->lastError !== null && str_contains($this->lastError, 'HTTP 429')) {
            $this->quotaExhausted = true;
        }

        return null;
    }
}
