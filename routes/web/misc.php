<?php

use App\Http\Controllers\Auth\SocialAuthController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ReviewController;
use Illuminate\Support\Facades\Route;

defined('REZI_ROUTE_COLLECTION') || define('REZI_ROUTE_COLLECTION', '/{collection}');
defined('REZI_ROUTE_CREATE') || define('REZI_ROUTE_CREATE', '/create');
defined('REZI_ROUTE_DOCUMENT') || define('REZI_ROUTE_DOCUMENT', '/{document}');
defined('REZI_ROUTE_RESIDENCE_PARAM') || define('REZI_ROUTE_RESIDENCE_PARAM', '/{residence}');
defined('REZI_ROUTE_TEMPLATE') || define('REZI_ROUTE_TEMPLATE', '/{template}');

/*
|--------------------------------------------------------------------------
| Routes Administrateur (Admin) - MIGRÉ VERS FILAMENT /admin
|--------------------------------------------------------------------------
| Les anciennes routes admin custom ont été supprimées.
| L'administration se fait maintenant via Filament sur /admin
| Voir: app/Filament/Resources/ pour les ressources admin
|--------------------------------------------------------------------------
*/

/*
|--------------------------------------------------------------------------
| Routes d'authentification sociale (Google, Facebook)
|--------------------------------------------------------------------------
*/

Route::prefix('auth')->group(function () {
    Route::get('/{provider}', [SocialAuthController::class, 'redirect'])
        ->where('provider', 'google|facebook')
        ->name('socialite.redirect');
    Route::get('/{provider}/callback', [SocialAuthController::class, 'callback'])
        ->where('provider', 'google|facebook')
        ->name('socialite.callback');
});

/*
|--------------------------------------------------------------------------
| Routes Avis / Reviews
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'verified'])
    ->prefix('reviews')
    ->name('reviews.')
    ->group(function () {
        Route::get('/my', [ReviewController::class, 'myReviews'])->name('my');
        Route::get(REZI_ROUTE_CREATE.REZI_ROUTE_RESIDENCE_PARAM, [ReviewController::class, 'create'])->name('create');
        Route::post(REZI_ROUTE_RESIDENCE_PARAM, [ReviewController::class, 'store'])
            ->middleware('throttle:5,60')
            ->name('store');
        Route::get('/{review}', [ReviewController::class, 'show'])->name('show');
        Route::post('/{review}/respond', [ReviewController::class, 'respond'])
            ->middleware('throttle:10,60')
            ->name('respond');
        Route::post('/{review}/guest-review', [ReviewController::class, 'reviewGuest'])
            ->middleware('throttle:10,60')
            ->name('guest-review');
        Route::post('/{review}/helpful', [ReviewController::class, 'voteHelpful'])->name('helpful');
        Route::post('/{review}/report', [ReviewController::class, 'report'])
            ->middleware('throttle:3,60')
            ->name('report');
    });

// Reviews par résidence (public)
Route::get('/residences/{residence}/reviews', [ReviewController::class, 'index'])->name('reviews.index');

/*
|--------------------------------------------------------------------------
| Routes Profils Publics
|--------------------------------------------------------------------------
*/

use App\Http\Controllers\PublicProfileController;

Route::middleware(['auth', 'verified'])->prefix('profile')->group(function () {
    Route::get('/u/{user}', [PublicProfileController::class, 'show'])->name('profile.public');
    Route::get('/u/{user}/badges', [PublicProfileController::class, 'badges'])->name('profile.badges');
    Route::get('/u/{user}/reviews-received', [PublicProfileController::class, 'receivedReviews'])->name('profile.received-reviews');
    Route::get('/u/{user}/reviews-given', [PublicProfileController::class, 'givenReviews'])->name('profile.given-reviews');
});

Route::middleware(['auth', 'verified'])->prefix('profile')->group(function () {
    Route::get('/public/edit', [PublicProfileController::class, 'edit'])->name('profile.public.edit');
    Route::match(['put', 'patch'], '/public', [PublicProfileController::class, 'update'])->name('profile.public.update');
    Route::post('/badges/refresh', [PublicProfileController::class, 'refreshBadges'])->name('profile.badges.refresh');
});

/*
|--------------------------------------------------------------------------
| Routes Notifications
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'verified'])
    ->prefix('notifications')
    ->name('notifications.')
    ->group(function () {
        Route::get('/', [NotificationController::class, 'index'])->name('index');
        Route::post('/{notification}/read', [NotificationController::class, 'markAsRead'])->name('read');
        Route::post('/read-all', [NotificationController::class, 'markAllAsRead'])->name('read-all');
        Route::delete('/{notification}', [NotificationController::class, 'destroy'])->name('destroy');

        // Préférences de notifications
        Route::get('/preferences', [NotificationController::class, 'preferences'])->name('preferences');
        Route::post('/preferences', [NotificationController::class, 'updatePreferences'])->name('preferences.update');

        // Push notifications
        Route::get('/vapid', [NotificationController::class, 'getVapidKey'])->name('vapid');
        Route::post('/push/subscribe', [NotificationController::class, 'subscribePush'])->name('push.subscribe');
        Route::post('/push/unsubscribe', [NotificationController::class, 'unsubscribePush'])->name('push.unsubscribe');

        // Notification logs
        Route::post('/logs/{notificationLog}/read', [NotificationController::class, 'markLogAsRead'])->name('logs.read');

        // Broadcast admin only
        Route::post('/broadcast', [NotificationController::class, 'broadcast'])
            ->name('broadcast')
            ->middleware('role:admin');
    });

// API Notifications (pour les requêtes AJAX)
Route::middleware(['auth'])
    ->prefix('api/notifications')
    ->name('api.notifications.')
    ->group(function () {
        Route::get('/unread-count', [NotificationController::class, 'unreadCount'])->name('unread-count');
        Route::get('/latest', [NotificationController::class, 'latest'])->name('latest');
    });

/*
|--------------------------------------------------------------------------
| Routes Chat / Messagerie
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'verified'])
    ->prefix('chat')
    ->name('chat.')
    ->group(function () {
        // Liste des conversations
        Route::get('/', [App\Http\Controllers\ChatController::class, 'index'])->name('index');

        // Démarrer une conversation (AVANT la route wildcard {conversation})
        Route::post('/start', [App\Http\Controllers\ChatController::class, 'start'])->name('start');

        // Recherche (AVANT la route wildcard {conversation})
        Route::get('/search', [App\Http\Controllers\ChatController::class, 'search'])->name('search');

        // Templates (AVANT la route wildcard {conversation})
        Route::get('/templates/list', [App\Http\Controllers\ChatController::class, 'getTemplates'])->name('templates');

        // Afficher une conversation
        Route::get('/{conversation}', [App\Http\Controllers\ChatController::class, 'show'])->name('show');

        // Charger les messages (pagination)
        Route::get('/{conversation}/messages', [App\Http\Controllers\ChatController::class, 'loadMessages'])->name('messages');

        // Envoyer un message (rate limited: 60/min)
        Route::post('/{conversation}/send', [App\Http\Controllers\ChatController::class, 'sendMessage'])->name('send')->middleware('throttle:60,1');

        // Envoyer une pièce jointe (rate limited: 20/min)
        Route::post('/{conversation}/attachment', [App\Http\Controllers\ChatController::class, 'sendAttachment'])->name('attachment')->middleware('throttle:20,1');

        // Obtenir nouveaux messages (polling)
        Route::get('/{conversation}/new', [App\Http\Controllers\ChatController::class, 'getNewMessages'])->name('new');

        // Marquer comme lu
        Route::post('/{conversation}/read', [App\Http\Controllers\ChatController::class, 'markAsRead'])->name('read');

        // Indicateur de frappe (rate limited: 30/min)
        Route::post('/{conversation}/typing', [App\Http\Controllers\ChatController::class, 'typing'])->name('typing')->middleware('throttle:30,1');

        // Actions sur la conversation
        Route::post('/{conversation}/archive', [App\Http\Controllers\ChatController::class, 'archive'])->name('archive');
        Route::post('/{conversation}/unarchive', [App\Http\Controllers\ChatController::class, 'unarchive'])->name('unarchive');
        Route::post('/{conversation}/pin', [App\Http\Controllers\ChatController::class, 'pin'])->name('pin');
        Route::post('/{conversation}/unpin', [App\Http\Controllers\ChatController::class, 'unpin'])->name('unpin');
        Route::post('/{conversation}/mute', [App\Http\Controllers\ChatController::class, 'mute'])->name('mute');
        Route::post('/{conversation}/unmute', [App\Http\Controllers\ChatController::class, 'unmute'])->name('unmute');
        Route::post('/{conversation}/block', [App\Http\Controllers\ChatController::class, 'block'])->name('block');
        Route::delete('/{conversation}', [App\Http\Controllers\ChatController::class, 'destroy'])->name('destroy');

        // Thème de couleur
        Route::post('/{conversation}/theme', [App\Http\Controllers\ChatController::class, 'changeTheme'])->name('theme');

        // Recherche dans une conversation
        Route::get('/{conversation}/search', [App\Http\Controllers\ChatController::class, 'searchInConversation'])->name('search-in');

        // Message vocal
        Route::post('/{conversation}/voice', [App\Http\Controllers\ChatController::class, 'sendVoice'])->name('voice')->middleware('throttle:20,1');

        // Envoyer un GIF
        Route::post('/{conversation}/gif', [App\Http\Controllers\ChatController::class, 'sendGif'])->name('gif')->middleware('throttle:30,1');

        // Utiliser un template
        Route::post('/{conversation}/template/{template}', [App\Http\Controllers\ChatController::class, 'useTemplate'])->name('template');

        // Partager un document
        Route::post('/{conversation}/document', [App\Http\Controllers\ChatController::class, 'shareDocument'])->name('document');

        // Télécharger une pièce jointe
        Route::get('/message/{message}/download/{index}', [App\Http\Controllers\ChatController::class, 'downloadAttachment'])->name('download');
    });

// Actions sur les messages
Route::middleware(['auth', 'verified'])
    ->prefix('messages')
    ->name('messages.')
    ->group(function () {
        Route::put('/{message}', [App\Http\Controllers\ChatController::class, 'editMessage'])->name('edit');
        Route::delete('/{message}', [App\Http\Controllers\ChatController::class, 'deleteMessage'])->name('delete');
        Route::post('/{message}/reaction', [App\Http\Controllers\ChatController::class, 'toggleReaction'])->name('reaction');
        Route::get('/{message}/voice-stream', [App\Http\Controllers\ChatController::class, 'streamVoice'])->name('voice-stream');
        Route::get('/{message}/image/{index}', [App\Http\Controllers\ChatController::class, 'streamImage'])->name('image');
    });

// API utilitaires chat (GIF search, link preview)
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/api/gifs/search', [App\Http\Controllers\ChatController::class, 'searchGifs'])->name('gifs.search')->middleware('throttle:30,1');
    Route::post('/api/link-preview', [App\Http\Controllers\ChatController::class, 'linkPreview'])->name('link.preview')->middleware('throttle:20,1');
});

/*
|--------------------------------------------------------------------------
| Routes Templates de messages
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'verified'])
    ->prefix('templates')
    ->name('templates.')
    ->group(function () {
        Route::get('/', [App\Http\Controllers\MessageTemplateController::class, 'index'])->name('index');
        Route::get(REZI_ROUTE_CREATE, [App\Http\Controllers\MessageTemplateController::class, 'create'])->name('create');
        Route::post('/', [App\Http\Controllers\MessageTemplateController::class, 'store'])->name('store');
        Route::get(REZI_ROUTE_TEMPLATE, [App\Http\Controllers\MessageTemplateController::class, 'show'])->name('show');
        Route::get('/{template}/edit', [App\Http\Controllers\MessageTemplateController::class, 'edit'])->name('edit');
        Route::put(REZI_ROUTE_TEMPLATE, [App\Http\Controllers\MessageTemplateController::class, 'update'])->name('update');
        Route::delete(REZI_ROUTE_TEMPLATE, [App\Http\Controllers\MessageTemplateController::class, 'destroy'])->name('destroy');
        Route::post('/{template}/duplicate', [App\Http\Controllers\MessageTemplateController::class, 'duplicate'])->name('duplicate');
        Route::post('/{template}/preview', [App\Http\Controllers\MessageTemplateController::class, 'preview'])->name('preview');
        Route::get('/shortcut/search', [App\Http\Controllers\MessageTemplateController::class, 'byShortcut'])->name('shortcut');
    });

/*
|--------------------------------------------------------------------------
| Routes Documents partagés
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'verified'])
    ->prefix('documents')
    ->name('documents.')
    ->group(function () {
        Route::get('/', [App\Http\Controllers\SharedDocumentController::class, 'index'])->name('index');
        Route::get(REZI_ROUTE_CREATE, [App\Http\Controllers\SharedDocumentController::class, 'create'])->name('create');
        Route::post('/', [App\Http\Controllers\SharedDocumentController::class, 'store'])->name('store');
        Route::get(REZI_ROUTE_DOCUMENT, [App\Http\Controllers\SharedDocumentController::class, 'show'])->name('show');
        Route::get('/{document}/download', [App\Http\Controllers\SharedDocumentController::class, 'download'])->name('download');
        Route::put(REZI_ROUTE_DOCUMENT, [App\Http\Controllers\SharedDocumentController::class, 'update'])->name('update');
        Route::delete(REZI_ROUTE_DOCUMENT, [App\Http\Controllers\SharedDocumentController::class, 'destroy'])->name('destroy');

        // API pour récupérer les documents
        Route::get('/residence/{residence}', [App\Http\Controllers\SharedDocumentController::class, 'forResidence'])->name('residence');
        Route::get('/conversation/{conversation}', [App\Http\Controllers\SharedDocumentController::class, 'forConversation'])->name('conversation');
    });

/*
|--------------------------------------------------------------------------
| Routes Favoris & Intentions
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'verified'])
    ->prefix('favorites')
    ->name('favorites.')
    ->group(function () {
        Route::get('/', [FavoriteController::class, 'index'])->name('index');
        Route::post('/store', [FavoriteController::class, 'store'])->name('store');
        Route::post(REZI_ROUTE_RESIDENCE_PARAM.'/toggle', [FavoriteController::class, 'toggle'])->name('toggle');
        Route::patch('/{favorite}/note', [FavoriteController::class, 'updateNote'])->name('note');
        Route::patch('/{residenceId}/move', [FavoriteController::class, 'moveToCollection'])->name('move');
        Route::get('/{residenceId}/check', [FavoriteController::class, 'check'])->name('check');
        Route::delete('/{favorite}', [FavoriteController::class, 'destroy'])->name('destroy');
    });

// Collections
Route::middleware(['auth', 'verified'])
    ->prefix('collections')
    ->name('collections.')
    ->group(function () {
        Route::get('/', [App\Http\Controllers\CollectionController::class, 'index'])->name('index');
        Route::get(REZI_ROUTE_CREATE, [App\Http\Controllers\CollectionController::class, 'create'])->name('create');
        Route::post('/', [App\Http\Controllers\CollectionController::class, 'store'])->name('store');
        Route::get(REZI_ROUTE_COLLECTION, [App\Http\Controllers\CollectionController::class, 'show'])->name('show');
        Route::patch(REZI_ROUTE_COLLECTION, [App\Http\Controllers\CollectionController::class, 'update'])->name('update');
        Route::delete(REZI_ROUTE_COLLECTION, [App\Http\Controllers\CollectionController::class, 'destroy'])->name('destroy');
        Route::post('/{collection}/regenerate-token', [App\Http\Controllers\CollectionController::class, 'regenerateToken'])->name('regenerate-token');
    });

// Collection partagée (publique)
Route::get('/c/{token}', [App\Http\Controllers\CollectionController::class, 'shared'])->name('collections.shared');

// Historique & Alertes
Route::middleware(['auth', 'verified'])
    ->prefix('history')
    ->name('history.')
    ->group(function () {
        // Historique des vues
        Route::get('/', [App\Http\Controllers\HistoryController::class, 'index'])->name('index');
        Route::post('/record', [App\Http\Controllers\HistoryController::class, 'recordView'])->name('record');
        Route::delete('/clear', [App\Http\Controllers\HistoryController::class, 'clear'])->name('clear');

        // Alertes de prix
        Route::get('/price-alerts', [App\Http\Controllers\HistoryController::class, 'priceAlerts'])->name('price-alerts');
        Route::post('/price-alerts', [App\Http\Controllers\HistoryController::class, 'createPriceAlert'])->name('price-alerts.create');
        Route::patch('/price-alerts/{alertId}/deactivate', [App\Http\Controllers\HistoryController::class, 'deactivatePriceAlert'])->name('price-alerts.deactivate');

        // Recherches sauvegardées
        Route::get('/saved-searches', [App\Http\Controllers\HistoryController::class, 'savedSearches'])->name('saved-searches');
        Route::post('/saved-searches', [App\Http\Controllers\HistoryController::class, 'saveSearch'])->name('saved-searches.store');
        Route::get('/saved-searches/{search}/execute', [App\Http\Controllers\HistoryController::class, 'executeSearch'])->name('saved-searches.execute');
        Route::patch('/saved-searches/{search}', [App\Http\Controllers\HistoryController::class, 'updateSearch'])->name('saved-searches.update');
        Route::delete('/saved-searches/{search}', [App\Http\Controllers\HistoryController::class, 'deleteSearch'])->name('saved-searches.delete');
    });

// Partage & Comparaison
Route::middleware(['auth', 'verified'])
    ->prefix('share')
    ->name('share.')
    ->group(function () {
        Route::post('/residence/{residenceId}', [App\Http\Controllers\ShareController::class, 'create'])->name('create');
        Route::get('/residence/{residenceId}/links', [App\Http\Controllers\ShareController::class, 'getShareLinks'])->name('links');
        Route::get('/residence/{residenceId}/stats', [App\Http\Controllers\ShareController::class, 'ownerStats'])->name('stats');
    });

// Lien partagé (publique)
Route::get('/s/{token}', [App\Http\Controllers\ShareController::class, 'handleSharedLink'])->name('shared.residence');

// Comparaison
Route::middleware(['auth', 'verified'])
    ->prefix('compare')
    ->name('compare.')
    ->group(function () {
        Route::get('/', [App\Http\Controllers\ShareController::class, 'compareIndex'])->name('index');
        Route::post('/{residenceId}', [App\Http\Controllers\ShareController::class, 'addToCompare'])->name('add');
        Route::delete('/{residenceId}', [App\Http\Controllers\ShareController::class, 'removeFromCompare'])->name('remove');
        Route::delete('/', [App\Http\Controllers\ShareController::class, 'clearCompare'])->name('clear');
    });

// Comparaison partagée (publique)
Route::get('/compare/shared/{token}', [App\Http\Controllers\ShareController::class, 'sharedCompare'])->name('compare.shared');

/*
|--------------------------------------------------------------------------
| Routes Annulation & Litiges
|--------------------------------------------------------------------------
*/

// Routes publiques
Route::get('/cancellation-policies', [App\Http\Controllers\CancellationController::class, 'policies'])
    ->name('cancellations.policies');

Route::middleware(['auth', 'verified'])->group(function () {

    // === CANCELLATIONS ===
    Route::prefix('cancellations')->name('cancellations.')->group(function () {
        // Historique utilisateur
        Route::get('/history', [App\Http\Controllers\CancellationController::class, 'history'])->name('history');

        // Prévisualisation annulation
        Route::get('/booking/{booking}/preview', [App\Http\Controllers\CancellationController::class, 'preview'])->name('preview');

        // Annuler en tant que voyageur
        Route::post('/booking/{booking}/cancel-guest', [App\Http\Controllers\CancellationController::class, 'cancelAsGuest'])->name('cancel-guest');

        // Annuler en tant que propriétaire
        Route::post('/booking/{booking}/cancel-owner', [App\Http\Controllers\CancellationController::class, 'cancelAsOwner'])->name('cancel-owner');

        // Détails annulation
        Route::get('/{cancellation}', [App\Http\Controllers\CancellationController::class, 'show'])->name('show');
    });

    // === REFUNDS ===
    Route::prefix('refunds')->name('refunds.')->group(function () {
        Route::get('/', [App\Http\Controllers\RefundController::class, 'index'])->name('index');
        Route::get('/{refund}', [App\Http\Controllers\RefundController::class, 'show'])->name('show');
    });

    // === DISPUTES ===
    Route::prefix('disputes')->name('disputes.')->group(function () {
        Route::get('/', [App\Http\Controllers\DisputeController::class, 'index'])->name('index');
        Route::get(REZI_ROUTE_CREATE, [App\Http\Controllers\DisputeController::class, 'create'])->name('create');
        Route::post('/', [App\Http\Controllers\DisputeController::class, 'store'])->name('store');
        Route::get('/{dispute}', [App\Http\Controllers\DisputeController::class, 'show'])->name('show');
        Route::get('/{dispute}/evidence/{index}', [App\Http\Controllers\DisputeController::class, 'downloadEvidence'])
            ->whereNumber('index')
            ->name('evidence.download');
        Route::post('/{dispute}/evidence', [App\Http\Controllers\DisputeController::class, 'addEvidence'])->name('add-evidence');
    });

    // === SUPPORT ===
    Route::prefix('support')->name('support.')->group(function () {
        Route::get('/', [App\Http\Controllers\SupportController::class, 'index'])->name('index');
        Route::get(REZI_ROUTE_CREATE, [App\Http\Controllers\SupportController::class, 'create'])->name('create');
        Route::post('/', [App\Http\Controllers\SupportController::class, 'store'])->name('store');
        Route::get('/{ticket}', [App\Http\Controllers\SupportController::class, 'show'])->name('show');
        Route::get('/{ticket}/messages/{message}/attachments/{index}', [App\Http\Controllers\SupportController::class, 'downloadAttachment'])
            ->whereNumber('index')
            ->name('attachments.download');
        Route::post('/{ticket}/reply', [App\Http\Controllers\SupportController::class, 'reply'])->name('reply');
        Route::post('/{ticket}/close', [App\Http\Controllers\SupportController::class, 'close'])->name('close');
        Route::post('/{ticket}/reopen', [App\Http\Controllers\SupportController::class, 'reopen'])->name('reopen');
        Route::post('/{ticket}/rate', [App\Http\Controllers\SupportController::class, 'rate'])->name('rate');
    });
});

// === OWNER ROUTES FOR CANCELLATIONS & DISPUTES ===
Route::middleware(['auth', 'verified', 'role:owner,admin', '2fa'])
    ->prefix('owner')
    ->name('owner.')
    ->group(function () {
        // Cancellations
        Route::prefix('cancellations')->name('cancellations.')->group(function () {
            Route::get('/', [App\Http\Controllers\CancellationController::class, 'ownerIndex'])->name('index');
            Route::post('/policy', [App\Http\Controllers\CancellationController::class, 'updateResidencePolicy'])->name('update-policy');
        });

        // Disputes
        Route::prefix('disputes')->name('disputes.')->group(function () {
            Route::get('/', [App\Http\Controllers\DisputeController::class, 'ownerIndex'])->name('index');
        });
    });

/*
|--------------------------------------------------------------------------
| ADMIN ROUTES - MIGRÉ VERS FILAMENT /admin
|--------------------------------------------------------------------------
| Toutes les routes admin (Cancellations, Refunds, Disputes, Support,
| Payments, Invoices) sont maintenant gérées par Filament.
| Voir: app/Filament/Resources/
|--------------------------------------------------------------------------
*/

/*
|--------------------------------------------------------------------------
| Routes Paiement (adapté Afrique - Jeko API)
|--------------------------------------------------------------------------
*/

// Webhook Jeko (public, sans auth)
Route::post('/payments/webhook', [App\Http\Controllers\PaymentController::class, 'webhook'])
    ->name('payments.webhook')
    ->withoutMiddleware(['csrf']);

Route::middleware(['auth', 'verified'])->group(function () {

    // === PAYMENTS ===
    Route::prefix('payments')->name('payments.')->group(function () {
        // Checkout et processus de paiement
        Route::get('/checkout/{booking}', [App\Http\Controllers\PaymentController::class, 'checkout'])->name('checkout');
        Route::post('/initiate/{booking}', [App\Http\Controllers\PaymentController::class, 'initiate'])
            ->middleware('throttle:5,1') // 5 tentatives / minute
            ->name('initiate');
        Route::post('/{payment}/verify-otp', [App\Http\Controllers\PaymentController::class, 'verifyOtp'])
            ->middleware('throttle:5,1') // 5 tentatives / minute (anti brute-force OTP)
            ->name('verify-otp');

        // Pages de résultat
        Route::get('/success/{uuid}', [App\Http\Controllers\PaymentController::class, 'success'])->name('success');
        Route::get('/failed/{uuid}', [App\Http\Controllers\PaymentController::class, 'failed'])->name('failed');
        Route::get('/return/{uuid}', [App\Http\Controllers\PaymentController::class, 'return'])->name('return');
        Route::get('/callback/{uuid}', [App\Http\Controllers\PaymentController::class, 'return'])->name('callback');

        // Historique et détails
        Route::get('/history', [App\Http\Controllers\PaymentController::class, 'history'])->name('history');
        Route::get('/{payment}', [App\Http\Controllers\PaymentController::class, 'show'])->name('show');
        Route::get('/{payment}/status', [App\Http\Controllers\PaymentController::class, 'checkStatus'])->name('status');
        Route::post('/{payment}/cancel', [App\Http\Controllers\PaymentController::class, 'cancel'])
            ->middleware('throttle:3,1') // 3 tentatives / minute
            ->name('cancel');

        // Méthodes de paiement
        Route::get('/methods/list', [App\Http\Controllers\PaymentController::class, 'methods'])->name('methods');
        Route::post('/methods/store', [App\Http\Controllers\PaymentController::class, 'storeMethod'])->name('methods.store');
        Route::delete('/methods/{method}', [App\Http\Controllers\PaymentController::class, 'deleteMethod'])->name('methods.delete');
        Route::post('/methods/{method}/default', [App\Http\Controllers\PaymentController::class, 'setDefaultMethod'])->name('methods.default');
    });

    // === INVOICES ===
    Route::prefix('invoices')->name('invoices.')->group(function () {
        Route::get('/', [App\Http\Controllers\InvoiceController::class, 'index'])->name('index');
        Route::get('/{invoice}', [App\Http\Controllers\InvoiceController::class, 'show'])->name('show');
        Route::get('/{invoice}/download', [App\Http\Controllers\InvoiceController::class, 'download'])->name('download');
        Route::get('/{invoice}/view', [App\Http\Controllers\InvoiceController::class, 'view'])->name('view');
        Route::post('/{invoice}/regenerate', [App\Http\Controllers\InvoiceController::class, 'regenerate'])->name('regenerate');
        Route::post('/{invoice}/send', [App\Http\Controllers\InvoiceController::class, 'sendEmail'])->name('send');
    });
});

// === OWNER EARNINGS ROUTES: moved into the main owner group above ===

/*
|--------------------------------------------------------------------------
| Routes de Réservation (Module Réservation)
|--------------------------------------------------------------------------
*/

// API de calcul de prix (AJAX) - accessible aux invités avec throttle
Route::middleware(['throttle:30,1'])->group(function () {
    Route::post('/residences/{residence}/calculate-price', [App\Http\Controllers\BookingController::class, 'calculatePrice'])
        ->name('residences.calculate-price');
    Route::post('/residences/{residence}/check-availability', [App\Http\Controllers\BookingController::class, 'checkAvailability'])
        ->name('residences.check-availability');
});

// Routes de réservation pour invités (sans auth)
Route::middleware(['throttle:10,1'])->prefix('bookings')->name('bookings.')->group(function () {
    // Formulaire de réservation (accessible aux invités)
    Route::get(REZI_ROUTE_CREATE.REZI_ROUTE_RESIDENCE_PARAM, [App\Http\Controllers\BookingController::class, 'create'])->name('create');

    // Soumission réservation invité
    Route::post('/store/guest/{residence}', [App\Http\Controllers\BookingController::class, 'storeGuestRequest'])->name('store.guest');

    // Page de confirmation après réservation (accessible invités + auth)
    Route::get('/confirmation/{booking:uuid}', [App\Http\Controllers\BookingController::class, 'confirmation'])->name('confirmation');

    // Callbacks paiement Jeko (pas d'auth - les invités arrivent ici après redirect)
    Route::get('/payment/success', [App\Http\Controllers\Payment\BookingPaymentCallbackController::class, 'success'])->name('payment.success');
    Route::get('/payment/error', [App\Http\Controllers\Payment\BookingPaymentCallbackController::class, 'error'])->name('payment.error');
});

// Route pour définir mot de passe invité
Route::get('/guest/set-password', [App\Http\Controllers\Auth\GuestPasswordController::class, 'show'])->name('guest.set-password');
Route::post('/guest/set-password', [App\Http\Controllers\Auth\GuestPasswordController::class, 'store'])->name('guest.set-password.store');

// Routes de réservation utilisateur connecté
Route::middleware(['auth', 'verified'])->group(function () {
    // === BOOKINGS CLIENT ===
    Route::prefix('bookings')->name('bookings.')->group(function () {
        // Liste des réservations
        Route::get('/', [App\Http\Controllers\BookingController::class, 'index'])->name('index');

        // Soumission réservation (utilisateur connecté)
        Route::post('/store/instant/{residence}', [App\Http\Controllers\BookingController::class, 'storeInstant'])->name('store.instant');
        Route::post('/store/request/{residence}', [App\Http\Controllers\BookingController::class, 'storeRequest'])->name('store.request');

        // Détails et annulation
        Route::get('/{booking}', [App\Http\Controllers\BookingController::class, 'show'])->name('show');
        Route::put('/{booking}/cancel', [App\Http\Controllers\BookingController::class, 'cancel'])->name('cancel');

        // Demandes de réservation
        Route::get('/requests/{bookingRequest}', [App\Http\Controllers\BookingController::class, 'showRequest'])->name('requests.show');

        // Sprint 3 — Modification de réservation post-booking
        Route::get('/{booking}/modify', [App\Http\Controllers\BookingModificationController::class, 'create'])->name('modify');
        Route::post('/{booking}/modify', [App\Http\Controllers\BookingModificationController::class, 'store'])->name('modify.store');

        // Digital check-in (guest side)
        Route::get('/{booking}/checkin', [\App\Http\Controllers\DigitalCheckinController::class, 'show'])
            ->name('checkin');

        // Téléchargement du reçu (réservations terminées uniquement)
        Route::get('/{booking}/receipt/download', [App\Http\Controllers\BookingController::class, 'downloadReceipt'])
            ->name('receipt.download');
    });
});

/*
|--------------------------------------------------------------------------
| Digital Check-in QR Verification (owner scan)
|--------------------------------------------------------------------------
*/

// Verify QR (accessible without auth so owner sees login button if not logged in)
Route::get('/checkin/verify/{token}', [\App\Http\Controllers\DigitalCheckinController::class, 'verify'])
    ->middleware(['throttle:30,1'])
    ->name('checkin.verify');

// Confirm check-in (POST, requires auth)
Route::post('/checkin/confirm/{token}', [\App\Http\Controllers\DigitalCheckinController::class, 'confirm'])
    ->middleware(['auth', 'verified'])
    ->name('checkin.confirm');

// Routes propriétaire pour les réservations
Route::middleware(['auth', 'verified', 'role:owner,admin', '2fa'])
    ->prefix('owner/bookings')
    ->name('owner.bookings.')
    ->group(function () {
        // Liste des réservations
        Route::get('/', [App\Http\Controllers\BookingController::class, 'ownerIndex'])->name('index');

        // Demandes en attente
        Route::get('/requests', [App\Http\Controllers\BookingController::class, 'ownerRequests'])->name('requests');
        Route::post('/requests/{bookingRequest}/approve', [App\Http\Controllers\BookingController::class, 'approveRequest'])->name('requests.approve');
        Route::post('/requests/{bookingRequest}/reject', [App\Http\Controllers\BookingController::class, 'rejectRequest'])->name('requests.reject');

        // Détails et gestion
        Route::get('/{booking}', [App\Http\Controllers\BookingController::class, 'ownerShow'])->name('show');
        Route::patch('/{booking}/confirm', [App\Http\Controllers\BookingController::class, 'ownerConfirm'])->name('confirm');
        Route::put('/{booking}/cancel', [App\Http\Controllers\BookingController::class, 'ownerCancel'])->name('cancel');

        // Calendrier
        Route::get('/calendar/{residence}', [App\Http\Controllers\BookingController::class, 'calendar'])->name('calendar');

        // Sprint 3 — Réponse aux demandes de modification
        Route::post('/modifications/{modification}/approve', [App\Http\Controllers\BookingModificationController::class, 'approve'])->name('modifications.approve');
        Route::post('/modifications/{modification}/reject', [App\Http\Controllers\BookingModificationController::class, 'reject'])->name('modifications.reject');
    });

// === PRICING API ===
Route::middleware(['auth'])->prefix('api')->group(function () {
    Route::post('/pricing/calculate', [App\Http\Controllers\PricingController::class, 'calculate'])->name('api.pricing.calculate');
    Route::post('/promo-codes/validate', [App\Http\Controllers\PricingController::class, 'validatePromoCode'])->name('api.promo-codes.validate');
    Route::post('/coupons/validate', [App\Http\Controllers\PricingController::class, 'validateCoupon'])->name('api.coupons.validate');
    Route::post('/codes/validate', [App\Http\Controllers\PricingController::class, 'validateCode'])->name('api.codes.validate');
    Route::get('/residences/{residence}/long-stay-discounts', [App\Http\Controllers\PricingController::class, 'getLongStayDiscounts'])->name('api.long-stay-discounts');
    Route::get('/fees/explanation', [App\Http\Controllers\PricingController::class, 'getFeeExplanation'])->name('api.fees.explanation');
    Route::get('/residences/{residence}/price-preview', [App\Http\Controllers\PricingController::class, 'preview'])->name('api.price-preview');
    Route::get('/residences/{residence}/special-prices', [App\Http\Controllers\PricingController::class, 'getSpecialPrices'])->name('api.special-prices');
});

// === PROMO CODES - MIGRÉ VERS FILAMENT /admin/promo-codes ===

// === PROMO CODES PUBLIC API ===
Route::get('/api/promo-codes/public', [App\Http\Controllers\PromoCodeController::class, 'getPublicCodes'])->name('api.promo-codes.public');
