<?php

use App\Http\Controllers\Api\ChatbotController;
use App\Http\Controllers\Api\GeoSearchController;
use App\Http\Controllers\Api\LocationController;
use App\Http\Controllers\Api\RecommendationController;
use App\Http\Controllers\Api\ResidenceController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Health Check (monitoring / uptime)
|--------------------------------------------------------------------------
*/

Route::get('/health', function () {
    $health = \Illuminate\Support\Facades\Cache::get('system:health', []);
    $allOk = empty($health) || collect($health)->every(fn ($component) => $component['status'] === 'ok');

    return response()->json([
        'success' => true,
        'status' => $allOk ? 'healthy' : 'degraded',
        'components' => $health,
        'timestamp' => now()->toIso8601String(),
    ], $allOk ? 200 : 503);
})
    ->middleware('throttle:60,1')
    ->name('api.health');

/*
|--------------------------------------------------------------------------
| Routes Publiques (throttle: 60/min)
|--------------------------------------------------------------------------
*/

Route::middleware('throttle:api')->group(function () {
    $residencesRoute = '/residences';
    $residenceRoute = '/residences/{residence}';

    Route::middleware('throttle:geo-search')->group(function () use ($residencesRoute) {
        Route::get("{$residencesRoute}/search", [ResidenceController::class, 'search'])
            ->name('api.residences.search');
        Route::post("{$residencesRoute}/nearby", [ResidenceController::class, 'nearby'])
            ->name('api.residences.nearby');
    });

    Route::get($residencesRoute, [ResidenceController::class, 'index'])
        ->name('api.residences.index');
    Route::get($residenceRoute, [ResidenceController::class, 'show'])
        ->name('api.residences.show');
    Route::get("{$residenceRoute}/similar", [RecommendationController::class, 'similar'])
        ->name('api.residences.similar');

    Route::get('/chatbot/status', [ChatbotController::class, 'status'])->name('api.chatbot.status');
    Route::post('/chatbot/message', [ChatbotController::class, 'message'])->name('api.chatbot.message');

    Route::get("{$residenceRoute}/availability", [\App\Http\Controllers\Api\AvailabilityController::class, 'index'])
        ->name('api.residences.availability');
    Route::post("{$residenceRoute}/check-availability", [\App\Http\Controllers\Api\AvailabilityController::class, 'checkAvailability'])
        ->name('api.residences.check-availability');

    Route::get('/amenities', [ResidenceController::class, 'amenities'])
        ->name('api.amenities.index');

    Route::prefix('locations')->name('api.locations.')->group(function () {
        Route::get('/countries', [LocationController::class, 'countries'])
            ->name('countries');
        Route::get('/countries/{code}/cities', [LocationController::class, 'cities'])
            ->name('cities');
        Route::get('/cities/{slug}/communes', [LocationController::class, 'communes'])
            ->name('communes');
        Route::post('/detect', [LocationController::class, 'detect'])
            ->name('detect');
        Route::post('/set', [LocationController::class, 'setLocation'])
            ->name('set');
    });

    Route::get('/communes', [ResidenceController::class, 'communes'])
        ->name('api.communes.index');
    Route::get('/communes/{commune}/quartiers', [ResidenceController::class, 'quartiers'])
        ->name('api.quartiers.index');
    Route::get('/cancellation-policies', [ResidenceController::class, 'cancellationPolicies'])
        ->name('api.cancellation-policies.index');

    Route::prefix('geo')->name('api.geo.')->middleware('throttle:geo-search')->group(function () {
        Route::post('/search', [GeoSearchController::class, 'search'])
            ->name('search');
        Route::get('/nearby', [GeoSearchController::class, 'nearby'])
            ->name('nearby');
        Route::get('/radius-counts', [GeoSearchController::class, 'radiusCounts'])
            ->name('radius-counts');
        Route::get('/autocomplete', [GeoSearchController::class, 'autocomplete'])
            ->name('autocomplete');
        Route::get('/trending', [GeoSearchController::class, 'trending'])
            ->name('trending');
        Route::get('/zones/{zone}/stats', [GeoSearchController::class, 'zoneStats'])
            ->name('zones.stats');
    });

    Route::prefix('maps')->name('api.maps.')->group(function () use ($residenceRoute) {
        Route::get("{$residenceRoute}/nearby", [\App\Http\Controllers\Api\MapsController::class, 'nearbyPlaces'])
            ->name('nearby');
        Route::get("{$residenceRoute}/directions", [\App\Http\Controllers\Api\MapsController::class, 'directions'])
            ->name('directions');
        Route::get("{$residenceRoute}/isochrone", [\App\Http\Controllers\Api\MapsController::class, 'isochrone'])
            ->name('isochrone');
        Route::get("{$residenceRoute}/streetview", [\App\Http\Controllers\Api\MapsController::class, 'streetView'])
            ->name('streetview');
        Route::post('/reverse-geocode', [\App\Http\Controllers\Api\MapsController::class, 'reverseGeocode'])
            ->name('reverse-geocode');
        Route::post('/validate-address', [\App\Http\Controllers\Api\MapsController::class, 'validateAddress'])
            ->name('validate-address');
        Route::get('/search-bounds', \App\Http\Controllers\Api\MapBoundsSearchController::class)
            ->name('search-bounds')
            ->middleware('throttle:60,1');
    });

    Route::get('/push/vapid-key', function () {
        return response()->json([
            'publicKey' => config('services.webpush.public_key'),
        ]);
    })->name('api.push.vapid-key');
});
