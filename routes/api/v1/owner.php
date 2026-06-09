<?php

use App\Http\Controllers\Api\ContactController;
use App\Http\Controllers\Api\ResidenceController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Routes Propriétaire (Owner)
|--------------------------------------------------------------------------
*/

Route::middleware(['auth:sanctum', 'role:owner,admin', '2fa.required', 'throttle:api'])
    ->prefix('owner')
    ->name('api.owner.')
    ->group(function () {
        $ownerResidencesRoute = '/residences';
        $ownerResidenceRoute = '/residences/{residence}';

        Route::get($ownerResidencesRoute, [ResidenceController::class, 'ownerResidences'])
            ->name('residences.index');

        Route::post($ownerResidencesRoute, [ResidenceController::class, 'store'])
            ->middleware('throttle:upload')
            ->name('residences.store');

        Route::put($ownerResidenceRoute, [ResidenceController::class, 'update'])
            ->middleware('ensure.owner:residence')
            ->name('residences.update');

        Route::delete($ownerResidenceRoute, [ResidenceController::class, 'destroy'])
            ->middleware('ensure.owner:residence')
            ->name('residences.destroy');

        Route::post("{$ownerResidenceRoute}/photos", [ResidenceController::class, 'uploadPhotos'])
            ->middleware(['ensure.owner:residence', 'throttle:upload'])
            ->name('residences.photos.upload');

        Route::get('/statistics', [ResidenceController::class, 'ownerStatistics'])
            ->name('statistics');

        Route::get('/contacts', [ContactController::class, 'ownerContacts'])
            ->name('contacts.index');
        Route::patch('/contacts/{contact}/status', [ContactController::class, 'updateStatus'])
            ->middleware('ensure.owner:contact')
            ->name('contacts.status');

        Route::prefix('residences/{residence}/availability')
            ->middleware('ensure.owner:residence')
            ->name('availability.')
            ->group(function () {
                Route::post('/block', [\App\Http\Controllers\Api\AvailabilityController::class, 'blockDates'])
                    ->name('block');
                Route::post('/unblock', [\App\Http\Controllers\Api\AvailabilityController::class, 'unblockDates'])
                    ->name('unblock');
                Route::post('/price', [\App\Http\Controllers\Api\AvailabilityController::class, 'setCustomPrice'])
                    ->name('price');
                Route::match(['get', 'post'], '/seasonal', [\App\Http\Controllers\Api\AvailabilityController::class, 'seasonalPricing'])
                    ->name('seasonal');
                Route::delete('/seasonal/{pricing}', [\App\Http\Controllers\Api\AvailabilityController::class, 'deleteSeasonalPricing'])
                    ->name('seasonal.delete');
                Route::post('/seasonal/template', [\App\Http\Controllers\Api\AvailabilityController::class, 'importSeasonTemplate'])
                    ->name('seasonal.template');
            });
    });
