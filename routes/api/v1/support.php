<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Routes API Support / Cancellation / Refund / Dispute
|--------------------------------------------------------------------------
*/

Route::middleware(['auth:sanctum', 'throttle:api'])
    ->name('api.v1.')
    ->group(function () {
        Route::prefix('support')->name('support.')->group(function () {
            Route::get('/', [\App\Http\Controllers\SupportController::class, 'apiIndex'])->name('index');
            Route::post('/', [\App\Http\Controllers\SupportController::class, 'apiStore'])->name('store');
            Route::get('/categories', [\App\Http\Controllers\SupportController::class, 'apiCategories'])->name('categories');
            Route::get('/unread-count', [\App\Http\Controllers\SupportController::class, 'apiUnreadCount'])->name('unread-count');
            Route::get('/{supportTicket}', [\App\Http\Controllers\SupportController::class, 'apiShow'])->name('show');
            Route::post('/{supportTicket}/reply', [\App\Http\Controllers\SupportController::class, 'apiReply'])->name('reply');
        });

        Route::prefix('cancellations')->name('cancellations.')->group(function () {
            Route::get('/reasons', [\App\Http\Controllers\CancellationController::class, 'apiReasons'])->name('reasons');
            Route::get('/{booking}/preview', [\App\Http\Controllers\CancellationController::class, 'apiPreview'])->name('preview');
        });

        Route::prefix('refunds')->name('refunds.')->group(function () {
            Route::get('/', [\App\Http\Controllers\RefundController::class, 'apiIndex'])->name('index');
            Route::get('/methods', [\App\Http\Controllers\RefundController::class, 'apiMethods'])->name('methods');
            Route::get('/{refund}', [\App\Http\Controllers\RefundController::class, 'apiStatus'])->name('status');
        });

        Route::prefix('disputes')->name('disputes.')->group(function () {
            Route::get('/types', [\App\Http\Controllers\DisputeController::class, 'apiTypes'])->name('types');
            Route::get('/{dispute}', [\App\Http\Controllers\DisputeController::class, 'apiStatus'])->name('status');
        });
    });
