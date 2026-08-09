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

    'riohub' => [
        'api_key' => env('RIOHUB_API_KEY'),
    ],

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI'),
        'jwks_url' => 'https://www.googleapis.com/oauth2/v3/certs',
    ],

    'apple' => [
        'client_ids' => array_values(array_filter(array_map(
            'trim',
            explode(',', (string) env('APPLE_OAUTH_AUDIENCES', ''))
        ))),
        'team_id' => env('APPLE_TEAM_ID'),
        'key_id' => env('APPLE_KEY_ID'),
        'private_key' => env('APPLE_PRIVATE_KEY'),
        'private_key_path' => env('APPLE_PRIVATE_KEY_PATH'),
        'redirect_uri' => env('APPLE_REDIRECT_URI'),
        'jwks_url' => 'https://appleid.apple.com/auth/keys',
        'token_url' => 'https://appleid.apple.com/auth/token',
        'revoke_url' => env('APPLE_REVOKE_URL', 'https://appleid.apple.com/auth/revoke'),
    ],

    'lazada' => [
        'user_token' => env('LAZADA_USER_TOKEN'),
        'api_url' => env('LAZADA_API_URL', 'https://api.lazada.vn/rest'),
        'app_key' => env('LAZADA_APP_KEY'),
        'app_secret' => env('LAZADA_APP_SECRET'),
    ],

];
