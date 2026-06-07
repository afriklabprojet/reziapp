<?php

use App\Http\Controllers\Api\BookingApiController;
use App\Http\Controllers\Api\ContactController;
use App\Http\Controllers\Api\PaymentApiController;
use App\Http\Controllers\Api\RecommendationController;
use App\Http\Controllers\Api\ResidenceEngagementController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Routes Authentifiées (Sanctum)
|--------------------------------------------------------------------------
*/

Route::middleware(['auth:sanctum', 'throttle:api'])->group(function () {
    $residenceRoute = '/residences/{residence}';
    $paymentMethodsRoute = '/methods';

    Route::post("{$residenceRoute}/view", [ResidenceEngagementController::class, 'recordView'])
        ->middleware('throttle:20,1')
        ->name('api.residences.view');

    Route::prefix('recommendations')->name('api.recommendations.')->group(function () {
        Route::get('/', [RecommendationController::class, 'index'])->name('index');
        Route::get('/profile', [RecommendationController::class, 'profile'])->name('profile');
        Route::post('/invalidate', [RecommendationController::class, 'invalidate'])
            ->middleware('throttle:3,60')
            ->name('invalidate');
    });

    Route::post("{$residenceRoute}/price", [BookingApiController::class, 'calculatePrice'])
        ->name('api.residences.price');
    Route::post("{$residenceRoute}/contact", [ContactController::class, 'store'])
        ->middleware('throttle:contact')
        ->name('api.residences.contact');
    Route::get('/contacts', [ContactController::class, 'index'])
        ->name('api.contacts.index');

    Route::prefix('bookings')->name('api.bookings.')->group(function () {
        Route::get('/', [BookingApiController::class, 'index'])->name('index');
        Route::post('/', [BookingApiController::class, 'store'])
            ->middleware('throttle:booking')
            ->name('store');
        Route::get('/{booking}', [BookingApiController::class, 'show'])->name('show');
        Route::post('/{booking}/cancel', [BookingApiController::class, 'cancel'])->name('cancel');
    });

    Route::prefix('payments')->name('api.payments.')->group(function () use ($paymentMethodsRoute) {
        Route::get('/', [PaymentApiController::class, 'history'])->name('history');
        Route::post('/initiate/{booking}', [PaymentApiController::class, 'initiate'])
            ->middleware('throttle:payment')
            ->name('initiate');
        Route::post('/verify-otp/{payment}', [PaymentApiController::class, 'verifyOtp'])
            ->middleware('throttle:otp')
            ->name('verify-otp');
        Route::get('/{payment}/status', [PaymentApiController::class, 'status'])->name('status');
        Route::get('/operators', [PaymentApiController::class, 'operators'])->name('operators');
        Route::get($paymentMethodsRoute, [PaymentApiController::class, 'methods'])->name('methods');
        Route::post($paymentMethodsRoute, [PaymentApiController::class, 'storeMethod'])->name('methods.store');
        Route::delete("{$paymentMethodsRoute}/{method}", [PaymentApiController::class, 'deleteMethod'])->name('methods.delete');
    });

    Route::get('/onboarding', function (Request $request) {
        $user = $request->user();
        $hasBooking = \App\Models\Booking::where('user_id', $user->id)->exists();
        $hasPayment = \App\Models\Payment::where('user_id', $user->id)->where('status', 'completed')->exists();
        $hasProfile = ! empty($user->phone) && ! empty($user->name);
        $hasVerifiedEmail = ! is_null($user->email_verified_at);

        $steps = [
            ['key' => 'profile', 'label' => 'Compléter votre profil', 'done' => $hasProfile],
            ['key' => 'email_verified', 'label' => 'Vérifier votre email', 'done' => $hasVerifiedEmail],
            ['key' => 'first_search', 'label' => 'Rechercher une résidence', 'done' => true],
            ['key' => 'first_booking', 'label' => 'Faire votre première réservation', 'done' => $hasBooking],
            ['key' => 'first_payment', 'label' => 'Effectuer un paiement', 'done' => $hasPayment],
        ];

        $completedCount = collect($steps)->where('done', true)->count();

        return response()->json([
            'success' => true,
            'data' => [
                'steps' => $steps,
                'progress' => round(($completedCount / count($steps)) * 100),
                'completed' => $completedCount,
                'total' => count($steps),
                'all_done' => $completedCount === count($steps),
            ],
        ]);
    })->name('api.onboarding');
});

Route::middleware('auth:sanctum')->prefix('push')->name('api.push.')->group(function () {
    Route::post('/subscribe', [\App\Http\Controllers\NotificationController::class, 'subscribePush'])
        ->name('subscribe');
    Route::post('/unsubscribe', [\App\Http\Controllers\NotificationController::class, 'unsubscribePush'])
        ->name('unsubscribe');
    Route::post('/test', [\App\Http\Controllers\NotificationController::class, 'testPush'])
        ->name('test');
});
