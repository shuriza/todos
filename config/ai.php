<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Gemini API
    |--------------------------------------------------------------------------
    */

    'gemini' => [
        'api_key'    => env('GEMINI_API_KEY'),
        'model'      => env('GEMINI_MODEL', 'gemini-2.5-flash'),
        'max_tokens' => (int) env('GEMINI_MAX_TOKENS', 2000),
        'base_url'   => env('GEMINI_BASE_URL', 'https://generativelanguage.googleapis.com/v1beta'),
        'timeout'    => (int) env('GEMINI_TIMEOUT', 60),
    ],

    /*
    |--------------------------------------------------------------------------
    | Retry Policy
    |--------------------------------------------------------------------------
    */

    'retry' => [
        'max_attempts' => (int) env('AI_RETRY_MAX', 2),
        'delay_ms'     => (int) env('AI_RETRY_DELAY_MS', 3000),
    ],

    /*
    |--------------------------------------------------------------------------
    | Prompt Context
    |--------------------------------------------------------------------------
    |
    | Jumlah tugas aktif yang disisipkan ke system prompt + TTL cache per user.
    | Cache mencegah re-query setiap request chat.
    |
    */

    'context' => [
        'active_task_limit' => (int) env('AI_CONTEXT_TASK_LIMIT', 30),
        'cache_ttl'         => (int) env('AI_CONTEXT_CACHE_TTL', 300), // 5 menit
    ],

    /*
    |--------------------------------------------------------------------------
    | Conversation History
    |--------------------------------------------------------------------------
    */

    'history' => [
        'max_messages' => (int) env('AI_HISTORY_MAX', 50),
    ],

    /*
    |--------------------------------------------------------------------------
    | Task Preview Validation
    |--------------------------------------------------------------------------
    */

    'task_preview' => [
        'allowed_categories' => ['kuliah', 'pekerjaan', 'daily_activity'],
        'allowed_priorities' => ['high', 'medium', 'low'],
        'max_per_batch'      => 15,
    ],
];
