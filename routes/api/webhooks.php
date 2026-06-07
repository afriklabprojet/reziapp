<?php

use App\Http\Controllers\Webhook\JekoWebhookController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Webhooks (hors préfixe v1, sans auth)
|--------------------------------------------------------------------------
*/

Route::post('/webhooks/jeko', [JekoWebhookController::class, 'handle'])
    ->middleware('throttle:webhook')
    ->name('webhooks.jeko');

// Alias pour URL webhook configurée dans Jeko dashboard
Route::post('/webhooks', [JekoWebhookController::class, 'handle'])
    ->middleware('throttle:webhook')
    ->name('webhooks.jeko.legacy');

// WhatsApp Business API Webhook
Route::prefix('webhooks/whatsapp')->group(function () {
    Route::get('/', [\App\Http\Controllers\Api\WhatsAppWebhookController::class, 'verify'])
        ->name('webhooks.whatsapp.verify');
    Route::post('/', [\App\Http\Controllers\Api\WhatsAppWebhookController::class, 'handle'])
        ->middleware('throttle:webhook')
        ->name('webhooks.whatsapp.handle');
});
