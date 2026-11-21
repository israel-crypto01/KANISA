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

    /* DarSMS bulk SMS */
    'darsms' => [
        'api_key' => env('DARSMS_API_KEY'),
        'sender_id' => env('DARSMS_SENDER_ID', 'DARSMS'),
        'base_url' => env('DARSMS_BASE_URL', 'https://dev.darsms.co.tz/api/v1'),
    ],

    /* Legacy Beem configuration remains available during provider transition. */
    'beem' => [
        'api_key'    => env('BEEM_API_KEY'),
        'secret_key' => env('BEEM_SECRET_KEY'),
        'sender_id'  => env('BEEM_SENDER_ID', 'KANISA'),
    ],

];
