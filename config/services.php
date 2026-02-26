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

    'firebase' => [
        'credentials' => env('FIREBASE_CREDENTIALS', storage_path('app/paranakids-b743f-firebase-adminsdk-fbsvc-4e1340d3ce.json')),
        'credentials_base64' => env('FIREBASE_CREDENTIALS_BASE64'),
        'project_id' => env('FIREBASE_PROJECT_ID', 'paranakids-b743f'),
        'delegate_vapid_key' => env('FIREBASE_DELEGATE_VAPID_KEY', 'BH3zykRdN9qD16ZdwHB9A_mNpnVR4iWKbcB049yOLisNUGkKnkeXpEykKK-Za4BMAELHCqGH2qtvscJb6qCQwzg'),
        'delegate_sender_id' => env('FIREBASE_DELEGATE_SENDER_ID', '223597554792'),
        'api_key' => env('FIREBASE_API_KEY', 'AIzaSyAXv3VHE9P1L5i71y4Z20nB-N4tLiA-TrU'),
        'auth_domain' => env('FIREBASE_AUTH_DOMAIN', 'parana-kids.firebaseapp.com'),
        'storage_bucket' => env('FIREBASE_STORAGE_BUCKET', 'parana-kids.firebasestorage.app'),
        'messaging_sender_id' => env('FIREBASE_MESSAGING_SENDER_ID', '130151352064'),
        'app_id' => env('FIREBASE_APP_ID', '1:130151352064:web:42335c43d67f4ac49515e5'),
        'measurement_id' => env('FIREBASE_MEASUREMENT_ID', 'G-HCTDLM0P9Y'),
        'vapid_key' => env('FIREBASE_VAPID_KEY', 'BET5Odck6WkOyun9SwgVCQjxpVcCi7o0WMCyu1vJbsX9K8kdNV-DGM-THOdKWBcXIYvo5rTH4E3cKX2LNmLGYX0'),
    ],

    'telegram' => [
        'bot_token' => env('TELEGRAM_BOT_TOKEN'),
        'webhook_url' => env('TELEGRAM_WEBHOOK_URL'),
    ],

];
