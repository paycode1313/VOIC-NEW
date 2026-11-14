<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'ollama' => [
        'url' => env('OLLAMA_URL', 'http://127.0.0.1:11434'),
    ],

    'ai_service' => [
        'url' => env('AI_SERVICE_URL', 'http://127.0.0.1:8001'),
        'timeout' => (int) env('AI_SERVICE_TIMEOUT', 90),
    ],

    'groq' => [
        'key' => env('GROQ_API_KEY'),
        // gpt-oss-120b: the strongest model on Groq's free tier (89.7% of the eval turns pass the quality gate,
        // qwen3.8-27b 86.2%); the fallback answers when the first model's per-minute quota is used up.
        'model' => env('GROQ_MODEL', 'openai/gpt-oss-120b'),
        'fallback_model' => env('GROQ_FALLBACK_MODEL', 'qwen/qwen3.8-27b'),
    ],

    'openrouter' => [
        'key' => env('OPENROUTER_API_KEY'),
        'url' => env('OPENROUTER_URL', 'https://openrouter.ai/api/v1'),
        'timeout' => (int) env('OPENROUTER_TIMEOUT', 120),
        'retries' => 3,
        'backoff' => 15,
    ],

    'elevenlabs' => [
        'key' => env('ELEVENLABS_API_KEY'),
        // Flash v2.5: lowest latency, speaks Indonesian. eleven_multilingual_v2 sounds a bit richer but is slower.
        'model' => env('ELEVENLABS_MODEL', 'eleven_flash_v2_5'),
    ],

    'openai' => [
        'key' => env('OPENAI_API_KEY'),
        'model' => env('OPENAI_MODEL', 'gpt-4o-mini'),
    ],

];
