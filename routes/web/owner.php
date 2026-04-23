<?php

use App\Http\Controllers\Api\ContactController;
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
use Illuminate\Support\Facades\Route;

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
        // ============================================
        // Avis reçus (réponses aux avis des locataires)
        // ============================================
        Route::prefix('received-reviews')->name('received-reviews.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Owner\ReceivedReviewController::class, 'index'])->name('index');
            Route::post('/{review}/respond', [\App\Http\Controllers\Owner\ReceivedReviewController::class, 'respond'])->name('respond');
        });

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
            Route::get('/devis', [\App\Http\Controllers\Owner\InsuranceController::class, 'quote'])->name('quote');
            Route::get('/create', [\App\Http\Controllers\Owner\InsuranceController::class, 'create'])->name('create');
            Route::post('/', [\App\Http\Controllers\Owner\InsuranceController::class, 'store'])->name('store');
            Route::get('/{insurance}', [\App\Http\Controllers\Owner\InsuranceController::class, 'show'])->name('show');
            Route::post('/{insurance}/renew', [\App\Http\Controllers\Owner\InsuranceController::class, 'renew'])->name('renew');
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
            Route::get('/create', [\App\Http\Controllers\Owner\UtilityController::class, 'create'])->name('create');
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
            Route::patch('/update-settings', [\App\Http\Controllers\Owner\YieldController::class, 'updateSettings'])->name('update-settings');
            Route::post('/apply-gap-discount', [\App\Http\Controllers\Owner\YieldController::class, 'applyGapDiscount'])->name('apply-gap-discount');
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
            Route::patch('/{alert}/mark-read', [\App\Http\Controllers\Owner\OwnerAlertController::class, 'markRead'])->name('mark-read');
            Route::patch('/update-settings', [\App\Http\Controllers\Owner\OwnerAlertController::class, 'updateSettings'])->name('update-settings');
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
