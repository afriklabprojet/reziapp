<?php

use App\Http\Controllers\Auth\SocialAuthController;
use App\Http\Controllers\Client\ClientController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\Owner\AnalyticsController;
use App\Http\Controllers\Owner\AutoReplyController;
use App\Http\Controllers\Owner\CoHostController;
use App\Http\Controllers\Owner\CouponController;
use App\Http\Controllers\Owner\OwnerController;
use App\Http\Controllers\Owner\PricingController;
use App\Http\Controllers\Owner\PromotionController;
use App\Http\Controllers\Owner\ReferralController;
use App\Http\Controllers\Owner\ResidenceController as OwnerResidenceController;
use App\Http\Controllers\Owner\SponsoredController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ResidenceController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\VerificationController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Routes Publiques
|--------------------------------------------------------------------------
| Accessibles sans authentification
*/

Route::get('/', [HomeController::class, 'index'])->name('home');

// Sitemap dynamique (toujours utilise APP_URL)
Route::get('/sitemap.xml', \App\Http\Controllers\SitemapController::class)->name('sitemap');

// Pages statiques (légales, info, support)
Route::get('/conditions-utilisation', [PageController::class, 'cgu'])->name('pages.cgu');
Route::get('/confidentialite', [PageController::class, 'confidentialite'])->name('pages.confidentialite');
Route::get('/mentions-legales', [PageController::class, 'mentionsLegales'])->name('pages.mentions-legales');
Route::get('/faq', [PageController::class, 'faq'])->name('pages.faq');
Route::get('/a-propos', [PageController::class, 'about'])->name('pages.about');
Route::get('/guide-proprietaire', [PageController::class, 'guideProprietaire'])->name('pages.guide-proprietaire');
Route::get('/nous-contacter', [PageController::class, 'contact'])->name('pages.contact');

// Newsletter
Route::post('/newsletter/subscribe', [App\Http\Controllers\NewsletterController::class, 'subscribe'])->name('newsletter.subscribe');
Route::get('/newsletter/unsubscribe/{token}', [App\Http\Controllers\NewsletterController::class, 'unsubscribe'])->name('newsletter.unsubscribe');
Route::post('/newsletter/resubscribe', [App\Http\Controllers\NewsletterController::class, 'resubscribe'])->name('newsletter.resubscribe');

// URLs propres pour les types de location (SEO-friendly)
Route::get('/residences-meublees', [ResidenceController::class, 'index'])
    ->defaults('type_location', 'residence_meublee')
    ->name('residences.meublees');

// Résidences publiques
Route::prefix('residences')->name('residences.')->group(function () {
    Route::get('/', [ResidenceController::class, 'index'])->name('index');
    Route::get('/search', [ResidenceController::class, 'search'])->name('search');
    Route::get('/map', [ResidenceController::class, 'map'])->name('map');
    Route::get('/compare', fn() => view('residences.compare'))->name('compare');
    Route::post('/{residence}/report', [ResidenceController::class, 'report'])
        ->middleware('throttle:5,10')
        ->name('report');
    Route::get('/{residence}', [ResidenceController::class, 'show'])->name('show');
});

/*
|--------------------------------------------------------------------------
| Routes publiques iCal & Guidebook
|--------------------------------------------------------------------------
*/

// Export iCal public (pour synchronisation externe)
Route::get('/ical/{token}.ics', [\App\Http\Controllers\Owner\IcalController::class, 'export'])->name('ical.export');

// Guidebook public (partagé aux voyageurs)
Route::get('/guidebook/{token}', [\App\Http\Controllers\Owner\GuidebookController::class, 'publicShow'])->name('guidebook.public');

/*
|--------------------------------------------------------------------------
| Callbacks Paiement Jeko (authentifié, sans préfixe owner)
|--------------------------------------------------------------------------
*/

Route::middleware(['auth'])->prefix('payment/jeko')->name('payment.jeko.')->group(function () {
    Route::get('/success', [\App\Http\Controllers\Payment\JekoCallbackController::class, 'success'])->name('success');
    Route::get('/error', [\App\Http\Controllers\Payment\JekoCallbackController::class, 'error'])->name('error');
    Route::get('/check/{sponsored}', [\App\Http\Controllers\Payment\JekoCallbackController::class, 'checkStatus'])->name('check');
});

// Callbacks paiement assurance
Route::middleware(['auth'])->prefix('insurance/payment')->name('insurance.payment.')->group(function () {
    Route::get('/success', [\App\Http\Controllers\Payment\InsuranceCallbackController::class, 'success'])->name('success');
    Route::get('/error', [\App\Http\Controllers\Payment\InsuranceCallbackController::class, 'error'])->name('error');
});

/*
|--------------------------------------------------------------------------
| Routes Authentifiées (Tous les utilisateurs connectés)
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'verified'])->group(function () {

    // Dashboard (redirige selon le rôle)
    Route::get('/dashboard', function () {
        $user = auth()->user();

        if ($user->isAdmin()) {
            return redirect('/admin'); // Filament admin panel
        }

        if ($user->isOwner()) {
            return redirect()->route('owner.dashboard');
        }

        // Rediriger les utilisateurs standard vers le dashboard client
        return redirect()->route('client.dashboard');
    })->name('dashboard');

    // Profil utilisateur
    Route::prefix('profile')->name('profile.')->group(function () {
        Route::get('/', [ProfileController::class, 'edit'])->name('edit');
        Route::patch('/', [ProfileController::class, 'update'])->name('update');
        Route::delete('/', [ProfileController::class, 'destroy'])->name('destroy');
    });

    // Contact propriétaire (rate limited)
    Route::middleware('throttle:contact')->group(function () {
        Route::post('/residences/{residence}/contact', [ContactController::class, 'store'])
            ->name('residences.contact');
    });

    // Mes contacts envoyés
    Route::get('/my-contacts', [ContactController::class, 'myContacts'])->name('contacts.mine');

    // Double authentification (2FA)
    Route::prefix('two-factor')->name('two-factor.')->group(function () {
        Route::get('/setup', [\App\Http\Controllers\TwoFactorController::class, 'setup'])->name('setup');
        Route::post('/generate', [\App\Http\Controllers\TwoFactorController::class, 'generate'])->name('generate');
        Route::post('/enable', [\App\Http\Controllers\TwoFactorController::class, 'enable'])->name('enable');
        Route::delete('/disable', [\App\Http\Controllers\TwoFactorController::class, 'disable'])->name('disable');
        Route::get('/challenge', [\App\Http\Controllers\TwoFactorController::class, 'challenge'])->name('challenge');
        Route::post('/verify', [\App\Http\Controllers\TwoFactorController::class, 'verify'])->name('verify');
        Route::post('/verify-recovery', [\App\Http\Controllers\TwoFactorController::class, 'verifyRecovery'])->name('verify-recovery');
        Route::get('/recovery-codes', [\App\Http\Controllers\TwoFactorController::class, 'showRecoveryCodes'])->name('recovery-codes');
        Route::post('/recovery-codes/confirm', [\App\Http\Controllers\TwoFactorController::class, 'confirmRecoveryCodes'])->name('recovery-codes.confirm');
        Route::post('/recovery-codes/regenerate', [\App\Http\Controllers\TwoFactorController::class, 'regenerateRecoveryCodes'])->name('recovery-codes.regenerate');
    });

    // Vérification utilisateur
    Route::prefix('verification')->name('verification.')->group(function () {
        // Dashboard vérification
        Route::get('/', [VerificationController::class, 'dashboard'])->name('dashboard');

        // Vérification identité
        Route::prefix('identity')->name('identity.')->group(function () {
            Route::get('/start', [VerificationController::class, 'identityStart'])->name('start');
            Route::post('/upload', [VerificationController::class, 'identityUpload'])->name('upload');
            Route::get('/selfie', [VerificationController::class, 'identitySelfieForm'])->name('selfie.form');
            Route::post('/selfie', [VerificationController::class, 'identitySelfie'])->name('selfie');
        });

        // Vérification téléphone
        Route::prefix('phone')->name('phone.')->group(function () {
            Route::post('/send', [VerificationController::class, 'phoneSend'])->name('send')->middleware('throttle:3,1');
            Route::get('/verify', [VerificationController::class, 'phoneVerifyForm'])->name('verify.form');
            Route::post('/verify', [VerificationController::class, 'phoneVerify'])->name('verify')->middleware('throttle:10,1');
        });

        // Contacts d'urgence
        Route::prefix('emergency')->name('emergency.')->group(function () {
            Route::get('/contacts', [VerificationController::class, 'emergencyContacts'])->name('contacts');
            Route::post('/contacts', [VerificationController::class, 'emergencyStore'])->name('store');
            Route::delete('/contacts/{contact}', [VerificationController::class, 'emergencyDestroy'])->name('destroy');
            Route::patch('/contacts/{contact}/primary', [VerificationController::class, 'emergencySetPrimary'])->name('set-primary');
            Route::post('/trigger', [VerificationController::class, 'emergencyTrigger'])->name('trigger')->middleware('throttle:5,1');
        });

        // Signalement fraude
        Route::post('/fraud/report', [VerificationController::class, 'reportFraud'])->name('fraud.report')->middleware('throttle:5,1');
    });
});

/*
|--------------------------------------------------------------------------
| Routes Client (Utilisateurs standard)
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'verified'])
    ->prefix('client')
    ->name('client.')
    ->group(function () {

        // Dashboard client
        Route::get('/dashboard', [ClientController::class, 'dashboard'])->name('dashboard');

        // Historique de recherche
        Route::get('/search-history', [ClientController::class, 'searchHistory'])->name('search-history');
        Route::delete('/search-history/clear', [ClientController::class, 'clearSearchHistory'])->name('search-history.clear');
        Route::delete('/search-history/{search}', [ClientController::class, 'deleteSearch'])->name('search-history.delete');

        // Historique des visites
        Route::get('/view-history', [ClientController::class, 'viewHistory'])->name('view-history');
        Route::delete('/view-history/clear', [ClientController::class, 'clearViewHistory'])->name('view-history.clear');

        // Comparateur
        Route::get('/compare', [ClientController::class, 'compare'])->name('compare');

        // Alertes
        Route::get('/alerts', [ClientController::class, 'alerts'])->name('alerts');

        // Mes contacts envoyés
        Route::get('/contacts', [ClientController::class, 'contacts'])->name('contacts');

        // Mes avis
        Route::get('/reviews', [ClientController::class, 'reviews'])->name('reviews');

        // Statistiques personnelles
        Route::get('/statistics', [ClientController::class, 'statistics'])->name('statistics');

        // Contrats / Baux
        Route::get('/contracts', [ClientController::class, 'contracts'])->name('contracts');
        Route::get('/contracts/{leaseContract}', [ClientController::class, 'showContract'])->name('contracts.show');
        Route::post('/contracts/{leaseContract}/sign', [ClientController::class, 'signContract'])->name('contracts.sign');
        Route::get('/contracts/{leaseContract}/download', [ClientController::class, 'downloadContract'])->name('contracts.download');

        // Sauvegarder une recherche comme alerte
        Route::post('/search-history/{search}/save-alert', [ClientController::class, 'saveSearchAsAlert'])->name('search-history.save-alert');

        // Supprimer une alerte sauvegardée
        Route::delete('/alerts/{savedSearch}', [ClientController::class, 'deleteAlert'])->name('alerts.delete');
    });

/*
|--------------------------------------------------------------------------
| Routes Propriétaire (Owner)
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'verified', 'role:owner,admin', '2fa'])
    ->prefix('owner')
    ->name('owner.')
    ->group(function () {

        // Dashboard propriétaire
        Route::get('/dashboard', [OwnerController::class, 'dashboard'])->name('dashboard');

        // Statistiques
        Route::get('/statistics', [OwnerController::class, 'statistics'])->name('statistics');

        // Gestion des résidences
        Route::prefix('residences')->name('residences.')->group(function () {
            Route::get('/', [OwnerResidenceController::class, 'index'])->name('index');
            Route::get('/create', [OwnerResidenceController::class, 'create'])
                ->middleware('identity.verified:strict')
                ->name('create');
            Route::get('/wizard', [OwnerResidenceController::class, 'wizard'])
                ->middleware('identity.verified:strict')
                ->name('wizard');
            Route::post('/', [OwnerResidenceController::class, 'store'])
                ->middleware(['throttle:upload', 'identity.verified:strict'])
                ->name('store');
            Route::get('/{residence}', [OwnerResidenceController::class, 'show'])
                ->middleware('ensure.owner:residence')
                ->name('show');
            Route::get('/{residence}/edit', [OwnerResidenceController::class, 'edit'])
                ->middleware('ensure.owner:residence')
                ->name('edit');
            Route::match(['put', 'patch'], '/{residence}', [OwnerResidenceController::class, 'update'])
                ->middleware('ensure.owner:residence')
                ->name('update');
            Route::delete('/{residence}', [OwnerResidenceController::class, 'destroy'])
                ->middleware('ensure.owner:residence')
                ->name('destroy');

            // Toggle disponibilité
            Route::patch('/{residence}/toggle-availability', [OwnerResidenceController::class, 'toggleAvailability'])
                ->middleware('ensure.owner:residence')
                ->name('toggle-availability');

            // Dupliquer une résidence
            Route::post('/{residence}/duplicate', [OwnerResidenceController::class, 'duplicate'])
                ->middleware('ensure.owner:residence')
                ->name('duplicate');

            // Opérations groupées
            Route::post('/bulk-action', [OwnerResidenceController::class, 'bulkAction'])
                ->name('bulk-action');

            // Photos (rate limited pour upload)
            Route::middleware('throttle:upload')->group(function () {
                Route::post('/{residence}/photos', [OwnerResidenceController::class, 'uploadPhotos'])
                    ->middleware('ensure.owner:residence')
                    ->name('photos.upload');
            });

            Route::patch('/{residence}/photos/{photo}/primary', [OwnerResidenceController::class, 'setPrimaryPhoto'])
                ->middleware('ensure.owner:residence')
                ->name('set-primary-photo');
            Route::delete('/{residence}/photos/{photo}', [OwnerResidenceController::class, 'deletePhoto'])
                ->middleware('ensure.owner:residence')
                ->name('delete-photo');
            Route::post('/{residence}/photos/reorder', [OwnerResidenceController::class, 'reorderPhotos'])
                ->middleware('ensure.owner:residence')
                ->name('reorder-photos');
        });

        // Contacts reçus
        Route::prefix('contacts')->name('contacts.')->group(function () {
            Route::get('/', [OwnerController::class, 'contacts'])->name('index');
            Route::get('/{contact}', [ContactController::class, 'show'])
                ->middleware('ensure.owner:contact')
                ->name('show');
            Route::patch('/{contact}/status', [ContactController::class, 'updateStatus'])
                ->middleware('ensure.owner:contact')
                ->name('status');
            Route::patch('/{contact}/respond', [OwnerController::class, 'markContactAsResponded'])
                ->middleware('ensure.owner:contact')
                ->name('respond');
        });

        // Notifications
        Route::get('/notifications', [OwnerController::class, 'notifications'])->name('notifications');

        // ============================================
        // Analytics & Business
        // ============================================
        Route::prefix('analytics')->name('analytics.')->group(function () {
            Route::get('/', [AnalyticsController::class, 'index'])->name('index');
            Route::get('/revenue', [AnalyticsController::class, 'revenue'])->name('revenue');
            Route::get('/views', [AnalyticsController::class, 'views'])->name('views');
            Route::get('/chart-data', [AnalyticsController::class, 'chartData'])->name('chart-data');
            Route::get('/fiscal', [AnalyticsController::class, 'fiscal'])->name('fiscal');

            // Exports
            Route::get('/export/pdf', [AnalyticsController::class, 'exportPdf'])->name('export.pdf');
            Route::get('/export/excel', [AnalyticsController::class, 'exportExcel'])->name('export.excel');
            Route::get('/export/fiscal-pdf', [AnalyticsController::class, 'exportFiscalPdf'])->name('export.fiscal-pdf');
        });

        // ============================================
        // Réponses automatiques
        // ============================================
        Route::prefix('auto-replies')->name('auto-replies.')->group(function () {
            Route::get('/', [AutoReplyController::class, 'index'])->name('index');
            Route::get('/create', [AutoReplyController::class, 'create'])->name('create');
            Route::post('/', [AutoReplyController::class, 'store'])->name('store');
            Route::get('/{autoReply}/edit', [AutoReplyController::class, 'edit'])->name('edit');
            Route::put('/{autoReply}', [AutoReplyController::class, 'update'])->name('update');
            Route::post('/{autoReply}/toggle', [AutoReplyController::class, 'toggle'])->name('toggle');
            Route::delete('/{autoReply}', [AutoReplyController::class, 'destroy'])->name('destroy');
            Route::post('/{autoReply}/use', [AutoReplyController::class, 'use'])->name('use');
        });

        // ============================================
        // Suspension/Réactivation d'annonces
        // ============================================
        Route::prefix('residences')->name('residences.')->group(function () {
            Route::get('/{residence}/suspend', [OwnerResidenceController::class, 'suspendForm'])
                ->middleware('ensure.owner:residence')
                ->name('suspend-form');
            Route::post('/{residence}/suspend', [OwnerResidenceController::class, 'suspend'])
                ->middleware('ensure.owner:residence')
                ->name('suspend');
            Route::post('/{residence}/resume', [OwnerResidenceController::class, 'resume'])
                ->middleware('ensure.owner:residence')
                ->name('resume');
            Route::post('/{residence}/block-dates', [OwnerResidenceController::class, 'blockDates'])
                ->middleware('ensure.owner:residence')
                ->name('block-dates');
        });

        // ============================================
        // Tarification dynamique (Calendrier des prix)
        // ============================================
        Route::prefix('residences/{residence}/pricing')->name('pricing.')->group(function () {
            Route::get('/', [PricingController::class, 'index'])
                ->middleware('ensure.owner:residence')
                ->name('index');

            // Saisons tarifaires
            Route::get('/season/create', [PricingController::class, 'createSeason'])
                ->middleware('ensure.owner:residence')
                ->name('create-season');
            Route::post('/season', [PricingController::class, 'storeSeason'])
                ->middleware('ensure.owner:residence')
                ->name('store-season');
            Route::get('/season/{season}/edit', [PricingController::class, 'editSeason'])
                ->middleware('ensure.owner:residence')
                ->name('edit-season');
            Route::put('/season/{season}', [PricingController::class, 'updateSeason'])
                ->middleware('ensure.owner:residence')
                ->name('update-season');
            Route::delete('/season/{season}', [PricingController::class, 'destroySeason'])
                ->middleware('ensure.owner:residence')
                ->name('destroy-season');

            // Prix journaliers (AJAX)
            Route::post('/daily', [PricingController::class, 'updateDaily'])
                ->middleware('ensure.owner:residence')
                ->name('update-daily');

            // Données calendrier (AJAX)
            Route::get('/calendar', [PricingController::class, 'calendarData'])
                ->middleware('ensure.owner:residence')
                ->name('calendar');

            // Calcul prix (API publique)
            Route::post('/calculate', [PricingController::class, 'calculatePrice'])
                ->name('calculate');

            // Suggestions de prix IA
            Route::get('/suggestions', [PricingController::class, 'suggestions'])
                ->middleware('ensure.owner:residence')
                ->name('suggestions');
            Route::post('/apply', [PricingController::class, 'applySuggestions'])
                ->middleware('ensure.owner:residence')
                ->name('apply');
            Route::post('/apply-all', [PricingController::class, 'applyAllSuggestions'])
                ->middleware('ensure.owner:residence')
                ->name('apply-all');
        });

        // ============================================
        // Gestion des Co-hôtes
        // ============================================
        Route::prefix('residences/{residence}/cohosts')->name('cohosts.')->group(function () {
            Route::get('/', [CoHostController::class, 'index'])
                ->middleware('ensure.owner:residence')
                ->name('index');
            Route::get('/create', [CoHostController::class, 'create'])
                ->middleware('ensure.owner:residence')
                ->name('create');
            Route::post('/', [CoHostController::class, 'store'])
                ->middleware('ensure.owner:residence')
                ->name('store');
            Route::get('/{cohost}', [CoHostController::class, 'show'])
                ->middleware('ensure.owner:residence')
                ->name('show');
            Route::get('/{cohost}/edit', [CoHostController::class, 'edit'])
                ->middleware('ensure.owner:residence')
                ->name('edit');
            Route::put('/{cohost}', [CoHostController::class, 'update'])
                ->middleware('ensure.owner:residence')
                ->name('update');
            Route::post('/{cohost}/revoke', [CoHostController::class, 'revoke'])
                ->middleware('ensure.owner:residence')
                ->name('revoke');
            Route::post('/{cohost}/resend', [CoHostController::class, 'resend'])
                ->middleware('ensure.owner:residence')
                ->name('resend');
            Route::delete('/{cohost}', [CoHostController::class, 'destroy'])
                ->middleware('ensure.owner:residence')
                ->name('destroy');
        });

        // ============================================
        // Marketing & Croissance
        // ============================================
        Route::prefix('marketing')->name('marketing.')->group(function () {

            // Promotions Flash
            Route::prefix('promotions')->name('promotions.')->group(function () {
                Route::get('/', [PromotionController::class, 'index'])->name('index');
                Route::get('/create', [PromotionController::class, 'create'])->name('create');
                Route::post('/', [PromotionController::class, 'store'])->name('store');
                Route::get('/{promotion}/edit', [PromotionController::class, 'edit'])->name('edit');
                Route::put('/{promotion}', [PromotionController::class, 'update'])->name('update');
                Route::patch('/{promotion}/toggle', [PromotionController::class, 'toggle'])->name('toggle');
                Route::delete('/{promotion}', [PromotionController::class, 'destroy'])->name('destroy');
            });

            // Codes Promo (Coupons)
            Route::prefix('coupons')->name('coupons.')->group(function () {
                Route::get('/', [CouponController::class, 'index'])->name('index');
                Route::get('/create', [CouponController::class, 'create'])->name('create');
                Route::post('/', [CouponController::class, 'store'])->name('store');
                Route::get('/{coupon}', [CouponController::class, 'show'])->name('show');
                Route::get('/{coupon}/edit', [CouponController::class, 'edit'])->name('edit');
                Route::put('/{coupon}', [CouponController::class, 'update'])->name('update');
                Route::patch('/{coupon}/toggle', [CouponController::class, 'toggle'])->name('toggle');
                Route::post('/{coupon}/duplicate', [CouponController::class, 'duplicate'])->name('duplicate');
                Route::delete('/{coupon}', [CouponController::class, 'destroy'])->name('destroy');
            });

            // Programme de Parrainage
            Route::prefix('referrals')->name('referrals.')->group(function () {
                Route::get('/', [ReferralController::class, 'index'])->name('index');
                Route::get('/share', [ReferralController::class, 'share'])->name('share');
                Route::post('/{referral}/claim', [ReferralController::class, 'claim'])->name('claim');
                Route::get('/leaderboard', [ReferralController::class, 'leaderboard'])->name('leaderboard');
            });

            // Mise en avant sponsorisée
            Route::prefix('sponsored')->name('sponsored.')->group(function () {
                Route::get('/', [SponsoredController::class, 'index'])->name('index');
                Route::get('/create', [SponsoredController::class, 'create'])->name('create');
                Route::post('/', [SponsoredController::class, 'store'])->name('store');
                Route::get('/{sponsored}', [SponsoredController::class, 'show'])->name('show');
                Route::get('/{sponsored}/payment', [SponsoredController::class, 'payment'])->name('payment');
                Route::post('/{sponsored}/payment', [SponsoredController::class, 'confirmPayment'])->name('payment.confirm');
                Route::patch('/{sponsored}/pause', [SponsoredController::class, 'pause'])->name('pause');
                Route::patch('/{sponsored}/resume', [SponsoredController::class, 'resume'])->name('resume');
                Route::patch('/{sponsored}/cancel', [SponsoredController::class, 'cancel'])->name('cancel');
            });

            // ============================================
            // Abonnements Propriétaires
            // ============================================
            Route::prefix('subscriptions')->name('subscriptions.')->group(function () {
                Route::get('/', [\App\Http\Controllers\Owner\SubscriptionController::class, 'index'])->name('index');
                Route::post('/subscribe/{plan}', [\App\Http\Controllers\Owner\SubscriptionController::class, 'subscribe'])->name('subscribe');
                Route::post('/change-plan/{plan}', [\App\Http\Controllers\Owner\SubscriptionController::class, 'changePlan'])->name('change-plan');
                Route::post('/cancel', [\App\Http\Controllers\Owner\SubscriptionController::class, 'cancel'])->name('cancel');
                Route::post('/resume', [\App\Http\Controllers\Owner\SubscriptionController::class, 'resume'])->name('resume');
                Route::get('/history', [\App\Http\Controllers\Owner\SubscriptionController::class, 'history'])->name('history');
                Route::get('/payment/success', [\App\Http\Controllers\Owner\SubscriptionController::class, 'paymentSuccess'])->name('payment.success');
                Route::get('/payment/error', [\App\Http\Controllers\Owner\SubscriptionController::class, 'paymentError'])->name('payment.error');
            });
        });

        // ============================================
        // Comparateur de résidences
        // ============================================
        Route::get('/compare', [App\Http\Controllers\Owner\CompareController::class, 'index'])->name('compare.index');

        // ============================================
        // Revenus propriétaire
        // ============================================
        Route::prefix('earnings')->name('earnings.')->group(function () {
            Route::get('/', [App\Http\Controllers\Owner\EarningsController::class, 'index'])->name('index');
            Route::post('/setup-pin', [App\Http\Controllers\Owner\EarningsController::class, 'setupPin'])->name('setup-pin');
            Route::post('/request-payout', [App\Http\Controllers\Owner\EarningsController::class, 'requestPayout'])->name('request-payout');
        });

        // ============================================
        // Assistant IA
        // ============================================
        Route::prefix('ai')->name('ai.')->group(function () {
            Route::post('/generate-description', [\App\Http\Controllers\Owner\AiAssistantController::class, 'generateDescription'])->name('generate-description');
            Route::post('/generate-title', [\App\Http\Controllers\Owner\AiAssistantController::class, 'generateTitle'])->name('generate-title');
            Route::post('/improve-description', [\App\Http\Controllers\Owner\AiAssistantController::class, 'improveDescription'])->name('improve-description');
            Route::post('/generate-clauses', [\App\Http\Controllers\Owner\AiAssistantController::class, 'generateClauses'])->name('generate-clauses');
            Route::post('/suggest-services', [\App\Http\Controllers\Owner\AiAssistantController::class, 'suggestServices'])->name('suggest-services');
        });

        // ============================================
        // Contrats de bail
        // ============================================
        Route::prefix('lease-contracts')->name('lease-contracts.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Owner\LeaseContractController::class, 'index'])->name('index');
            Route::get('/create', [\App\Http\Controllers\Owner\LeaseContractController::class, 'create'])->name('create');
            Route::post('/', [\App\Http\Controllers\Owner\LeaseContractController::class, 'store'])->name('store');
            Route::get('/{leaseContract}', [\App\Http\Controllers\Owner\LeaseContractController::class, 'show'])->name('show');
            Route::post('/{leaseContract}/sign', [\App\Http\Controllers\Owner\LeaseContractController::class, 'sign'])->name('sign');
            Route::post('/{leaseContract}/send', [\App\Http\Controllers\Owner\LeaseContractController::class, 'sendToTenant'])->name('send-to-tenant');
            Route::get('/{leaseContract}/download', [\App\Http\Controllers\Owner\LeaseContractController::class, 'download'])->name('download');
            Route::get('/{leaseContract}/terminate', [\App\Http\Controllers\Owner\LeaseContractController::class, 'terminateForm'])->name('terminate-form');
            Route::post('/{leaseContract}/terminate', [\App\Http\Controllers\Owner\LeaseContractController::class, 'terminate'])->name('terminate');
        });

        // ============================================
        // Dépôts de garantie
        // ============================================
        Route::prefix('security-deposits')->name('security-deposits.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Owner\SecurityDepositController::class, 'index'])->name('index');
            Route::get('/create', [\App\Http\Controllers\Owner\SecurityDepositController::class, 'create'])->name('create');
            Route::post('/', [\App\Http\Controllers\Owner\SecurityDepositController::class, 'store'])->name('store');
            Route::get('/{securityDeposit}', [\App\Http\Controllers\Owner\SecurityDepositController::class, 'show'])->name('show');
            Route::post('/{securityDeposit}/mark-paid', [\App\Http\Controllers\Owner\SecurityDepositController::class, 'markPaid'])->name('mark-paid');
            Route::post('/{securityDeposit}/return-full', [\App\Http\Controllers\Owner\SecurityDepositController::class, 'returnFull'])->name('return-full');
            Route::post('/{securityDeposit}/return-partial', [\App\Http\Controllers\Owner\SecurityDepositController::class, 'returnPartial'])->name('return-partial');
        });

        // ============================================
        // Quittances de loyer
        // ============================================
        Route::prefix('rent-receipts')->name('rent-receipts.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Owner\RentReceiptController::class, 'index'])->name('index');
            Route::get('/create', [\App\Http\Controllers\Owner\RentReceiptController::class, 'create'])->name('create');
            Route::post('/', [\App\Http\Controllers\Owner\RentReceiptController::class, 'store'])->name('store');
            Route::get('/{rentReceipt}', [\App\Http\Controllers\Owner\RentReceiptController::class, 'show'])->name('show');
            Route::get('/{rentReceipt}/download', [\App\Http\Controllers\Owner\RentReceiptController::class, 'download'])->name('download');
            Route::post('/{rentReceipt}/resend', [\App\Http\Controllers\Owner\RentReceiptController::class, 'resend'])->name('resend');
        });

        // ============================================
        // États des lieux
        // ============================================
        Route::prefix('property-inspections')->name('property-inspections.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Owner\PropertyInspectionController::class, 'index'])->name('index');
            Route::get('/create', [\App\Http\Controllers\Owner\PropertyInspectionController::class, 'create'])->name('create');
            Route::post('/', [\App\Http\Controllers\Owner\PropertyInspectionController::class, 'store'])->name('store');
            Route::get('/compare/{residence}', [\App\Http\Controllers\Owner\PropertyInspectionController::class, 'compare'])->name('compare');
            Route::get('/{propertyInspection}', [\App\Http\Controllers\Owner\PropertyInspectionController::class, 'show'])->name('show');
            Route::patch('/{propertyInspection}/items/{item}', [\App\Http\Controllers\Owner\PropertyInspectionController::class, 'updateItem'])->name('items.update');
            Route::post('/{propertyInspection}/items', [\App\Http\Controllers\Owner\PropertyInspectionController::class, 'addItem'])->name('items.add');
            Route::post('/{propertyInspection}/complete', [\App\Http\Controllers\Owner\PropertyInspectionController::class, 'complete'])->name('complete');
            Route::post('/{propertyInspection}/sign', [\App\Http\Controllers\Owner\PropertyInspectionController::class, 'sign'])->name('sign');
            Route::get('/{propertyInspection}/download', [\App\Http\Controllers\Owner\PropertyInspectionController::class, 'download'])->name('download');
        });

        // ============================================
        // Score qualité annonce (listing score)
        // ============================================
        Route::prefix('residences/{residence}/listing-score')->name('listing-score.')->group(function () {
            Route::post('/', [\App\Http\Controllers\Owner\ListingScoreController::class, 'compute'])->name('compute');
            Route::get('/', [\App\Http\Controllers\Owner\ListingScoreController::class, 'show'])->name('show');
        });

        // ============================================
        // Charges & Dépenses
        // ============================================
        Route::prefix('expenses')->name('expenses.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Owner\ExpenseController::class, 'index'])->name('index');
            Route::get('/create', [\App\Http\Controllers\Owner\ExpenseController::class, 'create'])->name('create');
            Route::post('/', [\App\Http\Controllers\Owner\ExpenseController::class, 'store'])->name('store');
            Route::get('/{expense}/edit', [\App\Http\Controllers\Owner\ExpenseController::class, 'edit'])->name('edit');
            Route::put('/{expense}', [\App\Http\Controllers\Owner\ExpenseController::class, 'update'])->name('update');
            Route::delete('/{expense}', [\App\Http\Controllers\Owner\ExpenseController::class, 'destroy'])->name('destroy');
        });

        // ============================================
        // Relances de loyer
        // ============================================
        Route::prefix('rent-reminders')->name('rent-reminders.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Owner\RentReminderController::class, 'index'])->name('index');
            Route::get('/create', [\App\Http\Controllers\Owner\RentReminderController::class, 'create'])->name('create');
            Route::post('/', [\App\Http\Controllers\Owner\RentReminderController::class, 'store'])->name('store');
            Route::post('/{rentReminder}/mark-paid', [\App\Http\Controllers\Owner\RentReminderController::class, 'markPaid'])->name('mark-paid');
            Route::post('/{rentReminder}/send', [\App\Http\Controllers\Owner\RentReminderController::class, 'send'])->name('send');
        });

        // ============================================
        // Maintenance & Incidents
        // ============================================
        Route::prefix('maintenance')->name('maintenance.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Owner\MaintenanceController::class, 'index'])->name('index');
            Route::get('/create', [\App\Http\Controllers\Owner\MaintenanceController::class, 'create'])->name('create');
            Route::post('/', [\App\Http\Controllers\Owner\MaintenanceController::class, 'store'])->name('store');
            Route::get('/{maintenance}', [\App\Http\Controllers\Owner\MaintenanceController::class, 'show'])->name('show');
            Route::patch('/{maintenance}/status', [\App\Http\Controllers\Owner\MaintenanceController::class, 'updateStatus'])->name('status');
            Route::post('/{maintenance}/assign', [\App\Http\Controllers\Owner\MaintenanceController::class, 'assign'])->name('assign');
        });

        // ============================================
        // Documents & Archivage
        // ============================================
        Route::prefix('documents')->name('documents.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Owner\OwnerDocumentController::class, 'index'])->name('index');
            Route::get('/create', [\App\Http\Controllers\Owner\OwnerDocumentController::class, 'create'])->name('create');
            Route::post('/', [\App\Http\Controllers\Owner\OwnerDocumentController::class, 'store'])->name('store');
            Route::get('/{document}/download', [\App\Http\Controllers\Owner\OwnerDocumentController::class, 'download'])->name('download');
            Route::delete('/{document}', [\App\Http\Controllers\Owner\OwnerDocumentController::class, 'destroy'])->name('destroy');
        });

        // ============================================
        // Gestion ménage / Turnover
        // ============================================
        Route::prefix('cleaning')->name('cleaning.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Owner\CleaningTaskController::class, 'index'])->name('index');
            Route::get('/create', [\App\Http\Controllers\Owner\CleaningTaskController::class, 'create'])->name('create');
            Route::post('/', [\App\Http\Controllers\Owner\CleaningTaskController::class, 'store'])->name('store');
            Route::get('/{cleaning}', [\App\Http\Controllers\Owner\CleaningTaskController::class, 'show'])->name('show');
            Route::post('/{cleaning}/complete', [\App\Http\Controllers\Owner\CleaningTaskController::class, 'complete'])->name('complete');
            Route::post('/{cleaning}/verify', [\App\Http\Controllers\Owner\CleaningTaskController::class, 'verify'])->name('verify');
            Route::delete('/{cleaning}', [\App\Http\Controllers\Owner\CleaningTaskController::class, 'destroy'])->name('destroy');
        });

        // ============================================
        // Avis sur locataires
        // ============================================
        Route::prefix('tenant-reviews')->name('tenant-reviews.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Owner\TenantReviewController::class, 'index'])->name('index');
            Route::get('/create', [\App\Http\Controllers\Owner\TenantReviewController::class, 'create'])->name('create');
            Route::post('/', [\App\Http\Controllers\Owner\TenantReviewController::class, 'store'])->name('store');
            Route::get('/{tenantReview}', [\App\Http\Controllers\Owner\TenantReviewController::class, 'show'])->name('show');
            Route::delete('/{tenantReview}', [\App\Http\Controllers\Owner\TenantReviewController::class, 'destroy'])->name('destroy');
        });

        // ============================================
        // Mode vacances
        // ============================================
        Route::prefix('vacation-mode')->name('vacation-mode.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Owner\VacationModeController::class, 'index'])->name('index');
            Route::post('/activate', [\App\Http\Controllers\Owner\VacationModeController::class, 'activate'])->name('activate');
            Route::post('/{vacationMode}/deactivate', [\App\Http\Controllers\Owner\VacationModeController::class, 'deactivate'])->name('deactivate');
        });

        // ============================================
        // Assurance
        // ============================================
        Route::prefix('insurance')->name('insurance.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Owner\InsuranceController::class, 'index'])->name('index');
            Route::get('/create', [\App\Http\Controllers\Owner\InsuranceController::class, 'create'])->name('create');
            Route::post('/', [\App\Http\Controllers\Owner\InsuranceController::class, 'store'])->name('store');
            Route::post('/{insurance}/cancel', [\App\Http\Controllers\Owner\InsuranceController::class, 'cancel'])->name('cancel');
        });

        // ============================================
        // Rapports fiscaux CI
        // ============================================
        Route::prefix('fiscal-reports')->name('fiscal-reports.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Owner\FiscalReportController::class, 'index'])->name('index');
            Route::get('/export-pdf', [\App\Http\Controllers\Owner\FiscalReportController::class, 'exportPdf'])->name('export-pdf');
        });

        // ============================================
        // Calendrier unifié
        // ============================================
        Route::prefix('calendar')->name('calendar.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Owner\UnifiedCalendarController::class, 'index'])->name('index');
            Route::get('/events', [\App\Http\Controllers\Owner\UnifiedCalendarController::class, 'events'])->name('events');
        });

        // ============================================
        // Dashboard multi-résidences (Portfolio)
        // ============================================
        Route::get('/portfolio', [\App\Http\Controllers\Owner\PortfolioController::class, 'index'])->name('portfolio.index');

        // ============================================
        // Séquences de messages automatiques
        // ============================================
        Route::prefix('sequences')->name('sequences.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Owner\MessageSequenceController::class, 'index'])->name('index');
            Route::post('/create-defaults', [\App\Http\Controllers\Owner\MessageSequenceController::class, 'createDefaults'])->name('create-defaults');
            Route::get('/create', [\App\Http\Controllers\Owner\MessageSequenceController::class, 'create'])->name('create');
            Route::post('/', [\App\Http\Controllers\Owner\MessageSequenceController::class, 'store'])->name('store');
            Route::get('/{sequence}', [\App\Http\Controllers\Owner\MessageSequenceController::class, 'show'])->name('show');
            Route::get('/{sequence}/edit', [\App\Http\Controllers\Owner\MessageSequenceController::class, 'edit'])->name('edit');
            Route::put('/{sequence}', [\App\Http\Controllers\Owner\MessageSequenceController::class, 'update'])->name('update');
            Route::patch('/{sequence}/toggle', [\App\Http\Controllers\Owner\MessageSequenceController::class, 'toggle'])->name('toggle');
            Route::delete('/{sequence}', [\App\Http\Controllers\Owner\MessageSequenceController::class, 'destroy'])->name('destroy');
            Route::post('/{sequence}/steps', [\App\Http\Controllers\Owner\MessageSequenceController::class, 'addStep'])->name('add-step');
            Route::delete('/{sequence}/steps/{step}', [\App\Http\Controllers\Owner\MessageSequenceController::class, 'removeStep'])->name('remove-step');
        });

        // ============================================
        // Rapports de dommages
        // ============================================
        Route::prefix('damages')->name('damages.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Owner\DamageReportController::class, 'index'])->name('index');
            Route::get('/create', [\App\Http\Controllers\Owner\DamageReportController::class, 'create'])->name('create');
            Route::post('/', [\App\Http\Controllers\Owner\DamageReportController::class, 'store'])->name('store');
            Route::get('/{damage}', [\App\Http\Controllers\Owner\DamageReportController::class, 'show'])->name('show');
            Route::patch('/{damage}/status', [\App\Http\Controllers\Owner\DamageReportController::class, 'updateStatus'])->name('status');
            Route::delete('/{damage}', [\App\Http\Controllers\Owner\DamageReportController::class, 'destroy'])->name('destroy');
        });

        // ============================================
        // Guides de séjour (Guidebooks)
        // ============================================
        Route::prefix('guidebooks')->name('guidebooks.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Owner\GuidebookController::class, 'index'])->name('index');
            Route::get('/create', [\App\Http\Controllers\Owner\GuidebookController::class, 'create'])->name('create');
            Route::post('/', [\App\Http\Controllers\Owner\GuidebookController::class, 'store'])->name('store');
            Route::get('/{guidebook}', [\App\Http\Controllers\Owner\GuidebookController::class, 'show'])->name('show');
            Route::get('/{guidebook}/edit', [\App\Http\Controllers\Owner\GuidebookController::class, 'edit'])->name('edit');
            Route::put('/{guidebook}', [\App\Http\Controllers\Owner\GuidebookController::class, 'update'])->name('update');
            Route::delete('/{guidebook}', [\App\Http\Controllers\Owner\GuidebookController::class, 'destroy'])->name('destroy');
            Route::post('/{guidebook}/toggle-publish', [\App\Http\Controllers\Owner\GuidebookController::class, 'togglePublish'])->name('toggle-publish');
            Route::post('/{guidebook}/sections', [\App\Http\Controllers\Owner\GuidebookController::class, 'addSection'])->name('add-section');
            Route::delete('/{guidebook}/sections/{section}', [\App\Http\Controllers\Owner\GuidebookController::class, 'removeSection'])->name('remove-section');
            Route::post('/{guidebook}/recommendations', [\App\Http\Controllers\Owner\GuidebookController::class, 'addRecommendation'])->name('add-recommendation');
        });

        // ============================================
        // Synchronisation iCal
        // ============================================
        Route::prefix('ical')->name('ical.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Owner\IcalController::class, 'index'])->name('index');
            Route::post('/', [\App\Http\Controllers\Owner\IcalController::class, 'store'])->name('store');
            Route::post('/{feed}/sync', [\App\Http\Controllers\Owner\IcalController::class, 'sync'])->name('sync');
            Route::delete('/{feed}', [\App\Http\Controllers\Owner\IcalController::class, 'destroy'])->name('destroy');
        });

        // ============================================
        // Serrures connectées
        // ============================================
        Route::prefix('smart-locks')->name('smart-locks.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Owner\SmartLockController::class, 'index'])->name('index');
            Route::post('/', [\App\Http\Controllers\Owner\SmartLockController::class, 'store'])->name('store');
            Route::get('/{smartLock}', [\App\Http\Controllers\Owner\SmartLockController::class, 'show'])->name('show');
            Route::post('/{smartLock}/generate-code', [\App\Http\Controllers\Owner\SmartLockController::class, 'generateCode'])->name('generate-code');
            Route::patch('/codes/{code}/revoke', [\App\Http\Controllers\Owner\SmartLockController::class, 'revokeCode'])->name('revoke-code');
            Route::delete('/{smartLock}', [\App\Http\Controllers\Owner\SmartLockController::class, 'destroy'])->name('destroy');
        });

        // ============================================
        // Relevés de compteurs
        // ============================================
        Route::prefix('utilities')->name('utilities.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Owner\UtilityController::class, 'index'])->name('index');
            Route::post('/', [\App\Http\Controllers\Owner\UtilityController::class, 'store'])->name('store');
            Route::patch('/alerts/{alert}/dismiss', [\App\Http\Controllers\Owner\UtilityController::class, 'acknowledgeAlert'])->name('dismiss-alert');
        });

        // ============================================
        // Performance & KPIs
        // ============================================
        Route::prefix('performance')->name('performance.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Owner\PerformanceController::class, 'index'])->name('index');
            Route::get('/{residence}/benchmark', [\App\Http\Controllers\Owner\PerformanceController::class, 'benchmark'])->name('benchmark');
        });

        // ============================================
        // Yield Management
        // ============================================
        Route::prefix('yield')->name('yield.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Owner\YieldController::class, 'index'])->name('index');
            Route::post('/toggle-auto-pricing', [\App\Http\Controllers\Owner\YieldController::class, 'toggleAutoPricing'])->name('toggle-auto-pricing');
            Route::post('/toggle-gap-night', [\App\Http\Controllers\Owner\YieldController::class, 'toggleGapNight'])->name('toggle-gap-night');
            Route::get('/gaps', [\App\Http\Controllers\Owner\YieldController::class, 'gaps'])->name('gaps');
        });

        // ============================================
        // Screening voyageurs
        // ============================================
        Route::prefix('screening')->name('screening.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Owner\GuestScreeningController::class, 'index'])->name('index');
            Route::get('/{score}', [\App\Http\Controllers\Owner\GuestScreeningController::class, 'show'])->name('show');
            Route::post('/{score}/recalculate', [\App\Http\Controllers\Owner\GuestScreeningController::class, 'recalculate'])->name('recalculate');
        });

        // ============================================
        // Alertes propriétaire
        // ============================================
        Route::prefix('alerts')->name('alerts.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Owner\OwnerAlertController::class, 'index'])->name('index');
            Route::patch('/{alert}/acknowledge', [\App\Http\Controllers\Owner\OwnerAlertController::class, 'acknowledge'])->name('acknowledge');
            Route::patch('/{alert}/resolve', [\App\Http\Controllers\Owner\OwnerAlertController::class, 'resolve'])->name('resolve');
            Route::patch('/{alert}/dismiss', [\App\Http\Controllers\Owner\OwnerAlertController::class, 'dismiss'])->name('dismiss');
        });

        // ============================================
        // Onboarding
        // ============================================
        Route::get('/onboarding', [\App\Http\Controllers\Owner\OnboardingController::class, 'index'])->name('onboarding.index');

        // ============================================
        // Réclamations assurance
        // ============================================
        Route::prefix('insurance-claims')->name('insurance-claims.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Owner\InsuranceClaimController::class, 'index'])->name('index');
            Route::get('/create', [\App\Http\Controllers\Owner\InsuranceClaimController::class, 'create'])->name('create');
            Route::post('/', [\App\Http\Controllers\Owner\InsuranceClaimController::class, 'store'])->name('store');
            Route::get('/{claim}', [\App\Http\Controllers\Owner\InsuranceClaimController::class, 'show'])->name('show');
        });
    });

// ============================================
// Routes publiques Co-hôte (invitation)
// ============================================
Route::prefix('cohost')->name('cohost.')->group(function () {
    Route::get('/invitation/{token}', [CoHostController::class, 'acceptInvitation'])
        ->name('invitation');
    Route::post('/invitation/{token}/accept', [CoHostController::class, 'processAcceptInvitation'])
        ->name('invitation.accept');
    Route::post('/invitation/{token}/decline', [CoHostController::class, 'declineInvitation'])
        ->name('invitation.decline');
});

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
        Route::get('/create/{residence}', [ReviewController::class, 'create'])->name('create');
        Route::post('/{residence}', [ReviewController::class, 'store'])
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

Route::prefix('profile')->group(function () {
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
        Route::get('/create', [App\Http\Controllers\MessageTemplateController::class, 'create'])->name('create');
        Route::post('/', [App\Http\Controllers\MessageTemplateController::class, 'store'])->name('store');
        Route::get('/{template}', [App\Http\Controllers\MessageTemplateController::class, 'show'])->name('show');
        Route::get('/{template}/edit', [App\Http\Controllers\MessageTemplateController::class, 'edit'])->name('edit');
        Route::put('/{template}', [App\Http\Controllers\MessageTemplateController::class, 'update'])->name('update');
        Route::delete('/{template}', [App\Http\Controllers\MessageTemplateController::class, 'destroy'])->name('destroy');
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
        Route::get('/create', [App\Http\Controllers\SharedDocumentController::class, 'create'])->name('create');
        Route::post('/', [App\Http\Controllers\SharedDocumentController::class, 'store'])->name('store');
        Route::get('/{document}', [App\Http\Controllers\SharedDocumentController::class, 'show'])->name('show');
        Route::get('/{document}/download', [App\Http\Controllers\SharedDocumentController::class, 'download'])->name('download');
        Route::put('/{document}', [App\Http\Controllers\SharedDocumentController::class, 'update'])->name('update');
        Route::delete('/{document}', [App\Http\Controllers\SharedDocumentController::class, 'destroy'])->name('destroy');

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
        Route::post('/{residence}/toggle', [FavoriteController::class, 'toggle'])->name('toggle');
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
        Route::get('/create', [App\Http\Controllers\CollectionController::class, 'create'])->name('create');
        Route::post('/', [App\Http\Controllers\CollectionController::class, 'store'])->name('store');
        Route::get('/{collection}', [App\Http\Controllers\CollectionController::class, 'show'])->name('show');
        Route::patch('/{collection}', [App\Http\Controllers\CollectionController::class, 'update'])->name('update');
        Route::delete('/{collection}', [App\Http\Controllers\CollectionController::class, 'destroy'])->name('destroy');
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
        Route::get('/create', [App\Http\Controllers\DisputeController::class, 'create'])->name('create');
        Route::post('/', [App\Http\Controllers\DisputeController::class, 'store'])->name('store');
        Route::get('/{dispute}', [App\Http\Controllers\DisputeController::class, 'show'])->name('show');
        Route::post('/{dispute}/evidence', [App\Http\Controllers\DisputeController::class, 'addEvidence'])->name('add-evidence');
    });

    // === SUPPORT ===
    Route::prefix('support')->name('support.')->group(function () {
        Route::get('/', [App\Http\Controllers\SupportController::class, 'index'])->name('index');
        Route::get('/create', [App\Http\Controllers\SupportController::class, 'create'])->name('create');
        Route::post('/', [App\Http\Controllers\SupportController::class, 'store'])->name('store');
        Route::get('/{ticket}', [App\Http\Controllers\SupportController::class, 'show'])->name('show');
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
    Route::get('/create/{residence}', [App\Http\Controllers\BookingController::class, 'create'])->name('create');
    
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
    });
});

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

/*
|--------------------------------------------------------------------------
| Routes d'authentification Laravel Breeze
|--------------------------------------------------------------------------
*/

require __DIR__.'/auth.php';
