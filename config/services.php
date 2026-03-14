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
    |--------------------------------------------------------------------------
    | REZI - Services tiers
    |--------------------------------------------------------------------------
    */

    'google_maps' => [
        'key' => env('GOOGLE_MAPS_API_KEY'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Google Cloud Vision (KYC automatique)
    |--------------------------------------------------------------------------
    | Activer l'API Cloud Vision dans Google Cloud Console :
    | https://console.cloud.google.com/apis/library/vision.googleapis.com
    | Utilise une clé API (pas de service account requis pour les appels REST).
    */

    'google_cloud_vision' => [
        'api_key' => env('GOOGLE_CLOUD_VISION_API_KEY', env('GOOGLE_MAPS_API_KEY')),
    ],

    'mapbox' => [
        'access_token' => env('MAPBOX_ACCESS_TOKEN'),
        'style' => env('MAPBOX_STYLE', 'mapbox://styles/mapbox/streets-v12'),
    ],

    'rezi' => [
        'default_latitude' => env('DEFAULT_LATITUDE', 5.3600),
        'default_longitude' => env('DEFAULT_LONGITUDE', -4.0083),
        'default_radius' => env('DEFAULT_SEARCH_RADIUS_KM', 5),
        'max_photos' => env('MAX_PHOTOS_PER_RESIDENCE', 10),
        'max_photo_size' => env('MAX_PHOTO_SIZE_MB', 5),
        'service_fee_percent' => env('PAYMENT_SERVICE_FEE_PERCENT', 10),
        'cleaning_fee_default' => env('PAYMENT_CLEANING_FEE_DEFAULT', 5000),
    ],

    /*
    |--------------------------------------------------------------------------
    | Web Push (VAPID)
    |--------------------------------------------------------------------------
    | Pour générer les clés VAPID :
    | composer require minishlink/web-push
    | php artisan vapid:generate (ou utiliser web-push-libs.com/vapid)
    */

    'webpush' => [
        'public_key' => env('VAPID_PUBLIC_KEY'),
        'private_key' => env('VAPID_PRIVATE_KEY'),
    ],

    /*
    |--------------------------------------------------------------------------
    | SMS (Twilio)
    |--------------------------------------------------------------------------
    */

    'sms' => [
        'provider' => env('SMS_PROVIDER', 'log'), // twilio, orange, log
    ],

    'twilio' => [
        'sid' => env('TWILIO_SID'),
        'token' => env('TWILIO_AUTH_TOKEN'),
        'from' => env('TWILIO_FROM_NUMBER'),
    ],

    'orange_sms' => [
        'client_id' => env('ORANGE_SMS_CLIENT_ID'),
        'client_secret' => env('ORANGE_SMS_CLIENT_SECRET'),
        'sender_address' => env('ORANGE_SMS_SENDER_ADDRESS', 'tel:+2250000'),
        'sender_name' => env('ORANGE_SMS_SENDER_NAME', 'REZI'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Jeko Mobile Money Payment
    |--------------------------------------------------------------------------
    */

    'jeko' => [
        'enabled' => env('JEKO_ENABLED', false),
        'api_key' => env('JEKO_API_KEY'),
        'api_key_id' => env('JEKO_API_KEY_ID'),
        'store_id' => env('JEKO_STORE_ID'),
        'base_url' => env('JEKO_BASE_URL', 'https://api.jeko.africa'),
        'currency' => env('JEKO_CURRENCY', 'XOF'),
        'webhook_secret' => env('JEKO_WEBHOOK_SECRET'),
        'sandbox' => env('JEKO_SANDBOX', false),
        'callback_base_url' => env('JEKO_CALLBACK_BASE_URL'),
    ],

    /*
    |--------------------------------------------------------------------------
    | WhatsApp Business API (Meta Cloud API)
    |--------------------------------------------------------------------------
    | Documentation: https://developers.facebook.com/docs/whatsapp/cloud-api
    */

    'whatsapp' => [
        'enabled' => env('WHATSAPP_ENABLED', false),
        'api_url' => env('WHATSAPP_API_URL', 'https://graph.facebook.com/v18.0'),
        'token' => env('WHATSAPP_API_TOKEN'),
        'phone_number_id' => env('WHATSAPP_PHONE_NUMBER_ID'),
        'business_account_id' => env('WHATSAPP_BUSINESS_ACCOUNT_ID'),
        'verify_token' => env('WHATSAPP_VERIFY_TOKEN', 'rezi_whatsapp_verify'),
        'webhook_secret' => env('WHATSAPP_WEBHOOK_SECRET'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Analytics & Tracking
    |--------------------------------------------------------------------------
    */

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI', '/auth/google/callback'),
        'analytics_id' => env('GOOGLE_ANALYTICS_ID'),
    ],

    'facebook' => [
        'client_id' => env('FACEBOOK_CLIENT_ID'),
        'client_secret' => env('FACEBOOK_CLIENT_SECRET'),
        'redirect' => env('FACEBOOK_REDIRECT_URI', '/auth/facebook/callback'),
        'pixel_id' => env('FACEBOOK_PIXEL_ID'),
    ],

    'hotjar' => [
        'id' => env('HOTJAR_ID'),
    ],

    'clarity' => [
        'id' => env('CLARITY_ID'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Cloudinary (Image Optimization)
    |--------------------------------------------------------------------------
    */

    'cloudinary' => [
        'cloud_name' => env('CLOUDINARY_CLOUD_NAME'),
        'api_key' => env('CLOUDINARY_API_KEY'),
        'api_secret' => env('CLOUDINARY_API_SECRET'),
    ],

];
