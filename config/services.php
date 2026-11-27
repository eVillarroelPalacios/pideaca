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

    /*
    |--------------------------------------------------------------------------
    | Mercado Pago (Fase 3)
    |--------------------------------------------------------------------------
    |
    | Checkout Pro con cuenta única recaudadora (escrow manual): el pago
    | entra a la cuenta de PideAca y se liquida por Liquidaciones. Sin SDK:
    | HTTP directo + verificación HMAC del webhook.
    |
    */

    'mercadopago' => [
        'access_token' => env('MP_ACCESS_TOKEN'),
        'public_key' => env('MP_PUBLIC_KEY'),
        'webhook_secret' => env('MP_WEBHOOK_SECRET'),
        'base_url' => env('MP_BASE_URL', 'https://api.mercadopago.com'),
        // OAuth Marketplace (split automático): credenciales de LA APP de
        // PideAca en developers.mercadopago.com (no del vendedor).
        'app_id' => env('MP_APP_ID'),
        'client_secret' => env('MP_CLIENT_SECRET'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

];
