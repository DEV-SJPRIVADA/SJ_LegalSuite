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
    ],

    /*
    | PDF desde HTML (Spatie Browsershot). Siempre tamaño Letter desde HtmlLetterPdfGenerator.
    | Sin NODE_BINARY: en Windows se intenta Laragon (carpeta bin/nodejs, carpetas node-vXX) y PATH.
    | Sin PDF_CHROME_PATH: se intenta Chrome de sistema en Windows; si no, Puppeteer usa su Chromium.
    | PDF_NO_SANDBOX=true: hosting Linux compartido (p. ej. Hostinger) — desactiva sandbox y /dev/shm pequeño.
    | PDF_VIA_ARTISAN_CLI=true: PHP web delega a `php artisan disciplinary:render-pdf` (LiteSpeed no lanza Chrome; CLI sí).
    | PDF_USE_QUEUE=true: la web encola FO-GJ-51 y un worker CLI/cron genera el PDF (Hostinger compartido).
    */
    'pdf' => [
        'chrome_path' => env('PDF_CHROME_PATH'),
        'node_binary' => env('NODE_BINARY'),
        'npm_binary' => env('NPM_BINARY'),
        'cli_php_binary' => env('PDF_CLI_PHP'),
        'timeout' => (int) env('PDF_BROWSER_TIMEOUT', 120),
        'viewport_width' => (int) env('PDF_VIEWPORT_WIDTH', 1280),
        'viewport_height' => (int) env('PDF_VIEWPORT_HEIGHT', 1650),
        'no_sandbox' => filter_var(env('PDF_NO_SANDBOX', false), FILTER_VALIDATE_BOOL),
        'via_artisan_cli' => filter_var(env('PDF_VIA_ARTISAN_CLI', false), FILTER_VALIDATE_BOOL),
        'use_queue' => filter_var(env('PDF_USE_QUEUE', false), FILTER_VALIDATE_BOOL),
    ],

];
