<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
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
        'webhook_url' => env('SLACK_WEBHOOK_URL'),
    ],

    'discord' => [
        'webhook_url' => env('DISCORD_WEBHOOK_URL'),
    ],

    'telegram' => [
        'bot_token' => env('TELEGRAM_BOT_TOKEN'),
        'webhook_secret' => env('TELEGRAM_WEBHOOK_SECRET'),
        'default_chat_id' => env('TELEGRAM_DEFAULT_CHAT_ID'),
        'dashboard_url' => env('APP_URL'),
    ],

    'n8n' => [
        'default_webhook_url' => env('N8N_WEBHOOK_URL'),
        'webhook_headers' => [
            'Authorization' => env('N8N_WEBHOOK_AUTH_HEADER'),
        ],
    ],

    'ifttt' => [
        'webhook_key' => env('IFTTT_WEBHOOK_KEY'),
        'event_name' => env('IFTTT_EVENT_NAME', 'appswatch_alert'),
    ],

    'prometheus' => [
        'enabled' => env('PROMETHEUS_EXPORTER_ENABLED', false),
        'api_key' => env('PROMETHEUS_EXPORTER_API_KEY'),
    ],

];
