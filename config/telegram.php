<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Bot Credentials
    |--------------------------------------------------------------------------
    |
    | webhook_secret WAJIB di-set di production. TelegramBotController akan
    | menolak semua request bila secret kosong (kecuali env=local/testing).
    |
    */

    'bot_token'      => env('TELEGRAM_BOT_TOKEN'),
    'bot_username'   => env('TELEGRAM_BOT_USERNAME'),
    'webhook_secret' => env('TELEGRAM_WEBHOOK_SECRET'),

    /*
    |--------------------------------------------------------------------------
    | Webhook Enforcement
    |--------------------------------------------------------------------------
    |
    | Environment yang BOLEH menjalankan webhook tanpa secret.
    | Di production, secret kosong = 403.
    |
    */

    'webhook_secret_optional_envs' => ['local', 'testing'],

    /*
    |--------------------------------------------------------------------------
    | Reminder Settings
    |--------------------------------------------------------------------------
    */

    'reminders' => [
        'default_minutes_before' => (int) env('TELEGRAM_REMINDER_MINUTES', 120),
        'daily_summary_time'     => env('TELEGRAM_DAILY_TIME', '07:00'),
        'task_list_limit'        => 20,
        'pagination_page_size'   => 5,
    ],

    /*
    |--------------------------------------------------------------------------
    | API
    |--------------------------------------------------------------------------
    */

    'api' => [
        'base_url' => 'https://api.telegram.org/bot',
        'timeout'  => 15,
    ],
];
