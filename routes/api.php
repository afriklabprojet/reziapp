<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BookingApiController;
use App\Http\Controllers\Api\ChatbotController;
use App\Http\Controllers\Api\ContactController;
use App\Http\Controllers\Api\GeoSearchController;
use App\Http\Controllers\Api\LocationController;
use App\Http\Controllers\Api\PaymentApiController;
use App\Http\Controllers\Api\RecommendationController;
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

// Alias pour URL webhook configurée dans Jeko dashboard
Route::post('/webhooks', [JekoWebhookController::class, 'handle'])
    ->name('webhooks.jeko.legacy');

// WhatsApp Business API Webhook
Route::prefix('webhooks/whatsapp')->group(function () {
    Route::get('/', [\App\Http\Controllers\Api\WhatsAppWebhookController::class, 'verify'])
        ->name('webhooks.whatsapp.verify');
    Route::post('/', [\App\Http\Controllers\Api\WhatsAppWebhookController::class, 'handle'])
        ->name('webhooks.whatsapp.handle');
});

/*
|--------------------------------------------------------------------------
| Redirects rétrocompatibilité (routes sans /v1 → /api/v1/*)
| Source : appels anciens, app mobile ou crawlers
|--------------------------------------------------------------------------
*/
Route::get('/communes', fn () => redirect('/api/v1/communes', 301))
    ->name('api.compat.communes');
Route::get('/cities', fn () => redirect('/api/v1/locations/countries/ci/cities', 301))
    ->name('api.compat.cities');
Route::get('/amenities', fn () => redirect('/api/v1/amenities', 301))
    ->name('api.compat.amenities');
Route::get('/residence-types', fn () => redirect('/api/v1/residences', 301))
    ->name('api.compat.residence-types');

Route::prefix('v1')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Health Check (monitoring / uptime)
    |--------------------------------------------------------------------------
    */
    Route::get('/health', function () {
        $health = \Illuminate\Support\Facades\Cache::get('system:health', []);
        $allOk = empty($health) || collect($health)->every(fn ($c) => $c['status'] === 'ok');

        return response()->json([
            'success' => true,
            'status' => $allOk ? 'healthy' : 'degraded',
            'components' => $health,
            'timestamp' => now()->toIso8601String(),
        ], $allOk ? 200 : 503);
    })->name('api.health');

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

        // Résidences similaires (sans auth)
        Route::get('/residences/{residence}/similar', [RecommendationController::class, 'similar'])
            ->name('api.residences.similar');

        // Chatbot IA 24/7 (public, rate-limited dans le controller)
        Route::get('/chatbot/status', [ChatbotController::class, 'status'])->name('api.chatbot.status');
        Route::post('/chatbot/message', [ChatbotController::class, 'message'])->name('api.chatbot.message');

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

        /*
        |----------------------------------------------------------------------
        | Maps API — POI, Directions, Isochrone, Street View, Geocoding
        |----------------------------------------------------------------------
        */
        Route::prefix('maps')->name('api.maps.')->group(function () {
            Route::get('/residences/{residence}/nearby', [\App\Http\Controllers\Api\MapsController::class, 'nearbyPlaces'])
                ->name('nearby');
            Route::get('/residences/{residence}/directions', [\App\Http\Controllers\Api\MapsController::class, 'directions'])
                ->name('directions');
            Route::get('/residences/{residence}/isochrone', [\App\Http\Controllers\Api\MapsController::class, 'isochrone'])
                ->name('isochrone');
            Route::get('/residences/{residence}/streetview', [\App\Http\Controllers\Api\MapsController::class, 'streetView'])
                ->name('streetview');
            Route::post('/reverse-geocode', [\App\Http\Controllers\Api\MapsController::class, 'reverseGeocode'])
                ->name('reverse-geocode');
            Route::post('/validate-address', [\App\Http\Controllers\Api\MapsController::class, 'validateAddress'])
                ->name('validate-address');

            // Sprint 2 — Search-as-I-move : recherche dans la bbox visible de la carte
            Route::get('/search-bounds', \App\Http\Controllers\Api\MapBoundsSearchController::class)
                ->name('search-bounds')
                ->middleware('throttle:60,1');
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

        /*
        |----------------------------------------------------------------------
        | Recommandations IA (matching locataire → résidence)
        |----------------------------------------------------------------------
        */
        Route::prefix('recommendations')->name('api.recommendations.')->group(function () {
            Route::get('/', [RecommendationController::class, 'index'])->name('index');
            Route::get('/profile', [RecommendationController::class, 'profile'])->name('profile');
            Route::post('/invalidate', [RecommendationController::class, 'invalidate'])->name('invalidate');
        });

        // Calculate price before booking
        Route::post('/residences/{residence}/price', [BookingApiController::class, 'calculatePrice'])
            ->name('api.residences.price');

        // Contact propriétaire (rate limited)
        Route::post('/residences/{residence}/contact', [ContactController::class, 'store'])
            ->middleware('throttle:contact')
            ->name('api.residences.contact');

        // Mes contacts
        Route::get('/contacts', [ContactController::class, 'index'])
            ->name('api.contacts.index');

        /*
        |----------------------------------------------------------------------
        | Bookings (mobile flow: create → pay → confirm)
        |----------------------------------------------------------------------
        */
        Route::prefix('bookings')->name('api.bookings.')->group(function () {
            Route::get('/', [BookingApiController::class, 'index'])->name('index');
            Route::post('/', [BookingApiController::class, 'store'])
                ->middleware('throttle:booking')
                ->name('store');
            Route::get('/{booking}', [BookingApiController::class, 'show'])->name('show');
            Route::post('/{booking}/cancel', [BookingApiController::class, 'cancel'])->name('cancel');
        });

        /*
        |----------------------------------------------------------------------
        | Payments (mobile flow: initiate → OTP → status poll)
        |----------------------------------------------------------------------
        */
        Route::prefix('payments')->name('api.payments.')->group(function () {
            Route::get('/', [PaymentApiController::class, 'history'])->name('history');
            Route::post('/initiate/{booking}', [PaymentApiController::class, 'initiate'])
                ->middleware('throttle:payment')
                ->name('initiate');
            Route::post('/verify-otp/{payment}', [PaymentApiController::class, 'verifyOtp'])
                ->middleware('throttle:otp')
                ->name('verify-otp');
            Route::get('/{payment}/status', [PaymentApiController::class, 'status'])->name('status');
            Route::get('/operators', [PaymentApiController::class, 'operators'])->name('operators');
            Route::get('/methods', [PaymentApiController::class, 'methods'])->name('methods');
            Route::post('/methods', [PaymentApiController::class, 'storeMethod'])->name('methods.store');
            Route::delete('/methods/{method}', [PaymentApiController::class, 'deleteMethod'])->name('methods.delete');
        });

        /*
        |----------------------------------------------------------------------
        | Onboarding — checklist for new users (mobile first-run)
        |----------------------------------------------------------------------
        */
        Route::get('/onboarding', function (\Illuminate\Http\Request $request) {
            $user = $request->user();
            $hasBooking = \App\Models\Booking::where('user_id', $user->id)->exists();
            $hasPayment = \App\Models\Payment::where('user_id', $user->id)->where('status', 'completed')->exists();
            $hasProfile = ! empty($user->phone) && ! empty($user->name);
            $hasVerifiedEmail = ! is_null($user->email_verified_at);

            $steps = [
                ['key' => 'profile', 'label' => 'Compléter votre profil', 'done' => $hasProfile],
                ['key' => 'email_verified', 'label' => 'Vérifier votre email', 'done' => $hasVerifiedEmail],
                ['key' => 'first_search', 'label' => 'Rechercher une résidence', 'done' => true], // always true if they reach this
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
