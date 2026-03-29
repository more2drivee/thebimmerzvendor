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

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'epush' => [
        'username' => env('EPUSH_USERNAME'),
        'password' => env('EPUSH_PASSWORD'),
        'api_key' => env('EPUSH_API_KEY'),
        'sender_id' => env('EPUSH_SENDER_ID'),
    ],

    'firebase' => [
        'enabled' => env('FIREBASE_ENABLED', false),
        'project_id' => env('FIREBASE_PROJECT_ID'),
        'client_email' => env('FIREBASE_CLIENT_EMAIL'),
        'private_key' => str_replace('\\n', "\n", env('FIREBASE_PRIVATE_KEY')),
        'vapid_public_key' => env('FIREBASE_VAPID_PUBLIC_KEY'),
    ],

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'social_login_enabled' => env('GOOGLE_SOCIAL_LOGIN_ENABLED', true),
    ],

    'apple' => [
        'team_id'      => env('APPLE_TEAM_ID'),
        'key_id'       => env('APPLE_KEY_ID'),
        'client_id'    => env('APPLE_CLIENT_ID'),
        'service_file' => env('APPLE_SERVICE_FILE'),       // e.g. AuthKey_XXXXXXXXXX.p8
        'redirect_url' => env('APPLE_REDIRECT_URL', 'https://www.example.com/apple-callback'),
        'social_login_enabled' => env('APPLE_SOCIAL_LOGIN_ENABLED', true),
    ],

];
