<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ContactController;
use App\Http\Controllers\Api\GeoSearchController;
use App\Http\Controllers\Api\LocationController;
use App\Http\Controllers\Api\ResidenceController;
use App\Http\Controllers\Webhook\JekoWebhookController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
| Routes pour l'API REST (application mobile, intégrations tierces)
| Préfixe: /api/v1
*/

/*
|--------------------------------------------------------------------------
| Webhooks (hors préfixe v1, sans auth)
|--------------------------------------------------------------------------
*/

Route::post('/webhooks/jeko', [JekoWebhookController::class, 'handle'])
    ->name('webhooks.jeko');

// WhatsApp Business API Webhook
Route::prefix('webhooks/whatsapp')->group(function () {
    Route::get('/', [\App\Http\Controllers\Api\WhatsAppWebhookController::class, 'verify'])
        ->name('webhooks.whatsapp.verify');
    Route::post('/', [\App\Http\Controllers\Api\WhatsAppWebhookController::class, 'handle'])
        ->name('webhooks.whatsapp.handle');
});

Route::prefix('v1')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Routes Publiques (throttle: 60/min)
    |--------------------------------------------------------------------------
    */

    Route::middleware('throttle:api')->group(function () {

        // Recherche géolocalisée (throttle plus permissif)
        Route::middleware('throttle:geo-search')->group(function () {
            Route::get('/residences/search', [ResidenceController::class, 'search'])
                ->name('api.residences.search');
            Route::post('/residences/nearby', [ResidenceController::class, 'nearby'])
                ->name('api.residences.nearby');
        });

        // Liste et détails des résidences
        Route::get('/residences', [ResidenceController::class, 'index'])
            ->name('api.residences.index');
        Route::get('/residences/{residence}', [ResidenceController::class, 'show'])
            ->name('api.residences.show');

        // Calendrier de disponibilité (public)
        Route::get('/residences/{residence}/availability', [\App\Http\Controllers\Api\AvailabilityController::class, 'index'])
            ->name('api.residences.availability');
        Route::post('/residences/{residence}/check-availability', [\App\Http\Controllers\Api\AvailabilityController::class, 'checkAvailability'])
            ->name('api.residences.check-availability');

        // Équipements
        Route::get('/amenities', [ResidenceController::class, 'amenities'])
            ->name('api.amenities.index');

        /*
        |----------------------------------------------------------------------
        | Données de localisation (pays, villes, communes)
        |----------------------------------------------------------------------
        */
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

        // Communes (legacy — rétrocompatibilité)
        Route::get('/communes', [ResidenceController::class, 'communes'])
            ->name('api.communes.index');

        // Quartiers par commune
        Route::get('/communes/{commune}/quartiers', [ResidenceController::class, 'quartiers'])
            ->name('api.quartiers.index');

        // Politiques d'annulation
        Route::get('/cancellation-policies', [ResidenceController::class, 'cancellationPolicies'])
            ->name('api.cancellation-policies.index');

        /*
        |--------------------------------------------------------------------------
        | API Recherche Géolocalisée v2 (optimisée avec cache)
        |--------------------------------------------------------------------------
        | Routes dédiées pour la recherche par rayon avec cache géohash
        | Cœur de REZI : recherche ≤ 500m
        */
        Route::prefix('geo')->name('api.geo.')->middleware('throttle:geo-search')->group(function () {
            // Recherche principale avec filtres
            Route::post('/search', [GeoSearchController::class, 'search'])
                ->name('search');

            // Recherche rapide à proximité
            Route::get('/nearby', [GeoSearchController::class, 'nearby'])
                ->name('nearby');

            // Comptage par rayons (pour UI de sélection)
            Route::get('/radius-counts', [GeoSearchController::class, 'radiusCounts'])
                ->name('radius-counts');

            // Autocomplétion des communes
            Route::get('/autocomplete', [GeoSearchController::class, 'autocomplete'])
                ->name('autocomplete');

            // Zones populaires (trending)
            Route::get('/trending', [GeoSearchController::class, 'trending'])
                ->name('trending');

            // Statistiques par zone
            Route::get('/zones/{zone}/stats', [GeoSearchController::class, 'zoneStats'])
                ->name('zones.stats');
        });
    });

    /*
    |--------------------------------------------------------------------------
    | Authentification API (Sanctum)
    |--------------------------------------------------------------------------
    */

    Route::prefix('auth')->name('api.auth.')->group(function () {
        // Login avec rate limiting strict
        Route::post('/login', [AuthController::class, 'login'])
            ->middleware('throttle:login')
            ->name('login');

        // Register avec rate limiting strict
        Route::post('/register', [AuthController::class, 'register'])
            ->middleware('throttle:register')
            ->name('register');

        // Logout (authentifié)
        Route::post('/logout', [AuthController::class, 'logout'])
            ->middleware('auth:sanctum')
            ->name('logout');

        // User actuel
        Route::get('/user', [AuthController::class, 'user'])
            ->middleware('auth:sanctum')
            ->name('user');
    });

    /*
    |--------------------------------------------------------------------------
    | Routes Authentifiées (Sanctum)
    |--------------------------------------------------------------------------
    */

    Route::middleware(['auth:sanctum', 'throttle:api'])->group(function () {

        // Enregistrer une vue
        Route::post('/residences/{residence}/view', [ResidenceController::class, 'recordView'])
            ->name('api.residences.view');

        // Contact propriétaire (rate limited)
        Route::post('/residences/{residence}/contact', [ContactController::class, 'store'])
            ->middleware('throttle:contact')
            ->name('api.residences.contact');

        // Mes contacts
        Route::get('/contacts', [ContactController::class, 'index'])
            ->name('api.contacts.index');
    });

    /*
    |--------------------------------------------------------------------------
    | Routes Propriétaire (Owner)
    |--------------------------------------------------------------------------
    */

    Route::middleware(['auth:sanctum', 'role:owner,admin', 'throttle:api'])
        ->prefix('owner')
        ->name('api.owner.')
        ->group(function () {

            // Mes résidences
            Route::get('/residences', [ResidenceController::class, 'ownerResidences'])
                ->name('residences.index');

            // CRUD Résidences
            Route::post('/residences', [ResidenceController::class, 'store'])
                ->middleware('throttle:upload')
                ->name('residences.store');

            Route::put('/residences/{residence}', [ResidenceController::class, 'update'])
                ->middleware('ensure.owner:residence')
                ->name('residences.update');

            Route::delete('/residences/{residence}', [ResidenceController::class, 'destroy'])
                ->middleware('ensure.owner:residence')
                ->name('residences.destroy');

            // Photos
            Route::post('/residences/{residence}/photos', [ResidenceController::class, 'uploadPhotos'])
                ->middleware(['ensure.owner:residence', 'throttle:upload'])
                ->name('residences.photos.upload');

            // Statistiques de mes résidences
            Route::get('/statistics', [ResidenceController::class, 'ownerStatistics'])
                ->name('statistics');

            // Mes contacts reçus
            Route::get('/contacts', [ContactController::class, 'ownerContacts'])
                ->name('contacts.index');
            Route::patch('/contacts/{contact}/status', [ContactController::class, 'updateStatus'])
                ->middleware('ensure.owner:contact')
                ->name('contacts.status');

            // Calendrier de disponibilité
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

    /*
    |--------------------------------------------------------------------------
    | Routes Push Notifications
    |--------------------------------------------------------------------------
    */

    // Route publique pour la clé VAPID
    Route::get('/push/vapid-key', function () {
        return response()->json([
            'publicKey' => config('services.webpush.public_key'),
        ]);
    })->name('api.push.vapid-key');

    // Routes authentifiées pour la gestion des push
    Route::middleware('auth:sanctum')->prefix('push')->name('api.push.')->group(function () {
        Route::post('/subscribe', [\App\Http\Controllers\NotificationController::class, 'subscribePush'])
            ->name('subscribe');
        Route::post('/unsubscribe', [\App\Http\Controllers\NotificationController::class, 'unsubscribePush'])
            ->name('unsubscribe');
        Route::post('/test', [\App\Http\Controllers\NotificationController::class, 'testPush'])
            ->name('test');
    });

    /*
    |--------------------------------------------------------------------------
    | Routes Admin
    |--------------------------------------------------------------------------
    */

    Route::middleware(['auth:sanctum', 'role:admin', 'throttle:api'])
        ->prefix('admin')
        ->name('api.admin.')
        ->group(function () {

            // Statistiques globales
            Route::get('/statistics', [ResidenceController::class, 'adminStatistics'])
                ->name('statistics');

            // Modération
            Route::get('/residences/pending', [ResidenceController::class, 'pendingResidences'])
                ->name('residences.pending');
            Route::post('/residences/{residence}/approve', [ResidenceController::class, 'approve'])
                ->name('residences.approve');
            Route::post('/residences/{residence}/reject', [ResidenceController::class, 'reject'])
                ->name('residences.reject');

            // Gestion utilisateurs
            Route::get('/users', [AuthController::class, 'users'])
                ->name('users.index');
            Route::patch('/users/{user}/role', [AuthController::class, 'updateRole'])
                ->name('users.role');
        });

    /*
    |--------------------------------------------------------------------------
    | Routes API Support / Cancellation / Refund / Dispute
    |--------------------------------------------------------------------------
    */

    Route::middleware(['auth:sanctum', 'throttle:api'])
        ->prefix('v1')
        ->name('api.v1.')
        ->group(function () {
            // Support tickets
            Route::prefix('support')->name('support.')->group(function () {
                Route::get('/', [\App\Http\Controllers\SupportController::class, 'apiIndex'])->name('index');
                Route::post('/', [\App\Http\Controllers\SupportController::class, 'apiStore'])->name('store');
                Route::get('/categories', [\App\Http\Controllers\SupportController::class, 'apiCategories'])->name('categories');
                Route::get('/unread-count', [\App\Http\Controllers\SupportController::class, 'apiUnreadCount'])->name('unread-count');
                Route::get('/{supportTicket}', [\App\Http\Controllers\SupportController::class, 'apiShow'])->name('show');
                Route::post('/{supportTicket}/reply', [\App\Http\Controllers\SupportController::class, 'apiReply'])->name('reply');
            });

            // Cancellations
            Route::prefix('cancellations')->name('cancellations.')->group(function () {
                Route::get('/reasons', [\App\Http\Controllers\CancellationController::class, 'apiReasons'])->name('reasons');
                Route::get('/{booking}/preview', [\App\Http\Controllers\CancellationController::class, 'apiPreview'])->name('preview');
            });

            // Refunds
            Route::prefix('refunds')->name('refunds.')->group(function () {
                Route::get('/', [\App\Http\Controllers\RefundController::class, 'apiIndex'])->name('index');
                Route::get('/methods', [\App\Http\Controllers\RefundController::class, 'apiMethods'])->name('methods');
                Route::get('/{refund}', [\App\Http\Controllers\RefundController::class, 'apiStatus'])->name('status');
            });

            // Disputes
            Route::prefix('disputes')->name('disputes.')->group(function () {
                Route::get('/types', [\App\Http\Controllers\DisputeController::class, 'apiTypes'])->name('types');
                Route::get('/{dispute}', [\App\Http\Controllers\DisputeController::class, 'apiStatus'])->name('status');
            });
        });
});
