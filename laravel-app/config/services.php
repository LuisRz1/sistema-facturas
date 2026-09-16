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

    'gmail' => [
        'client_id' => env('GMAIL_CLIENT_ID'),
        'client_secret' => env('GMAIL_CLIENT_SECRET'),
        'refresh_token' => env('GMAIL_REFRESH_TOKEN'),
        'timeout' => (int) env('GMAIL_API_TIMEOUT', 15),
    ],

    'whatsapp' => [
        'gateway_url' => env('WHATSAPP_GATEWAY_URL', 'https://whastapp-production.up.railway.app'),
        'timeout' => (int) env('WHATSAPP_TIMEOUT', 30),
    ],

    'cloudinary' => [
        'cloud_name' => env('CLOUDINARY_CLOUD_NAME'),
        'api_key' => env('CLOUDINARY_API_KEY'),
        'api_secret' => env('CLOUDINARY_API_SECRET'),
        'upload_preset' => env('CLOUDINARY_UPLOAD_PRESET'),
        'folder' => env('CLOUDINARY_FOLDER', 'reportes_financieros'),
        'timeout' => (int) env('CLOUDINARY_TIMEOUT', 60),
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

];
