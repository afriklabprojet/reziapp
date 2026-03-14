<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\AutoReply;
use App\Models\Booking;
use App\Models\Campaign;
use App\Models\CoHost;
use App\Models\Contact;
use App\Models\Coupon;
use App\Models\LeaseContract;
use App\Models\Photo;
use App\Models\Promotion;
use App\Models\PropertyInspection;
use App\Models\Residence;
use App\Models\SecurityDeposit;
use App\Models\SponsoredListing;
use App\Policies\AutoReplyPolicy;
use App\Policies\BookingPolicy;
use App\Policies\CampaignPolicy;
use App\Policies\CoHostPolicy;
use App\Policies\ContactPolicy;
use App\Policies\CouponPolicy;
use App\Policies\LeaseContractPolicy;
use App\Policies\PhotoPolicy;
use App\Policies\PromotionPolicy;
use App\Policies\PropertyInspectionPolicy;
use App\Policies\ResidencePolicy;
use App\Policies\SecurityDepositPolicy;
use App\Policies\SponsoredListingPolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->registerPolicies();
        $this->registerGates();
        $this->configureRateLimiting();
        $this->registerViewComposers();
        $this->registerEventListeners();
    }

    /**
     * Enregistrer les listeners d'événements métier
     */
    protected function registerEventListeners(): void
    {
        Event::listen(
            \App\Events\LeaseContractSigned::class,
            \App\Listeners\SendLeaseContractSignedNotification::class,
        );
    }

    /**
     * Enregistrer les View Composers pour les sidebars
     */
    protected function registerViewComposers(): void
    {
        // Owner sidebar: détecte automatiquement la page active à partir de la route
        View::composer('layouts.owner', function ($view) {
            $route = request()->route()?->getName() ?? '';

            $sidebarActive = match (true) {
                str_starts_with($route, 'owner.dashboard') => 'dashboard',
                str_starts_with($route, 'owner.residences') => 'residences',
                str_starts_with($route, 'owner.bookings') => 'bookings',
                str_starts_with($route, 'owner.contacts') => 'contacts',
                str_starts_with($route, 'chat.') => 'messages',
                str_starts_with($route, 'owner.marketing.promotions') => 'promotions',
                str_starts_with($route, 'owner.marketing.coupons') => 'coupons',
                str_starts_with($route, 'owner.marketing.sponsored') => 'sponsored',
                str_starts_with($route, 'owner.marketing.referrals') => 'referrals',
                str_starts_with($route, 'owner.analytics') => 'analytics',
                str_starts_with($route, 'owner.compare') => 'compare',
                str_starts_with($route, 'owner.earnings') => 'earnings',
                str_starts_with($route, 'owner.auto-replies') => 'auto-replies',
                str_starts_with($route, 'owner.pricing') => 'pricing',
                str_starts_with($route, 'owner.statistics') => 'analytics',
                str_starts_with($route, 'owner.notifications') => 'notifications',
                str_starts_with($route, 'owner.cancellations') => 'cancellations',
                str_starts_with($route, 'owner.disputes') => 'disputes',
                str_starts_with($route, 'verification.') => 'verification',
                str_starts_with($route, 'profile.') => 'profile',
                str_starts_with($route, 'owner.lease-contracts') => 'lease-contracts',
                str_starts_with($route, 'owner.security-deposits') => 'security-deposits',
                str_starts_with($route, 'owner.rent-receipts') => 'rent-receipts',
                str_starts_with($route, 'owner.property-inspections') => 'property-inspections',
                default => '',
            };

            $view->with('sidebarActive', $view->getData()['sidebarActive'] ?? $sidebarActive);
        });
    }

    /**
     * Enregistrer les Policies
     */
    protected function registerPolicies(): void
    {
        Gate::policy(Residence::class, ResidencePolicy::class);
        Gate::policy(Contact::class, ContactPolicy::class);
        Gate::policy(Photo::class, PhotoPolicy::class);
        Gate::policy(Campaign::class, CampaignPolicy::class);
        Gate::policy(Booking::class, BookingPolicy::class);
        Gate::policy(Promotion::class, PromotionPolicy::class);
        Gate::policy(Coupon::class, CouponPolicy::class);
        Gate::policy(SponsoredListing::class, SponsoredListingPolicy::class);
        Gate::policy(CoHost::class, CoHostPolicy::class);
        Gate::policy(AutoReply::class, AutoReplyPolicy::class);
        Gate::policy(LeaseContract::class, LeaseContractPolicy::class);
        Gate::policy(SecurityDeposit::class, SecurityDepositPolicy::class);
        Gate::policy(PropertyInspection::class, PropertyInspectionPolicy::class);
    }

    /**
     * Définir les Gates globaux
     */
    protected function registerGates(): void
    {
        // Accès au dashboard admin
        Gate::define('access-admin', function ($user) {
            return $user->isAdmin();
        });

        // Accès au dashboard propriétaire
        Gate::define('access-owner-dashboard', function ($user) {
            return $user->isOwner() || $user->isAdmin();
        });

        // Modération des résidences
        Gate::define('moderate-residences', function ($user) {
            return $user->isAdmin();
        });

        // Gestion des utilisateurs
        Gate::define('manage-users', function ($user) {
            return $user->isAdmin();
        });

        // Voir les statistiques globales
        Gate::define('view-global-statistics', function ($user) {
            return $user->isAdmin();
        });

        // Créer une résidence
        Gate::define('create-residence', function ($user) {
            return $user->isOwner() || $user->isAdmin();
        });

        // Envoyer un contact
        Gate::define('send-contact', function ($user) {
            return $user !== null; // Tout utilisateur connecté
        });

        // Exporter les données
        Gate::define('export-data', function ($user) {
            return $user->isAdmin();
        });
    }

    /**
     * Configurer le Rate Limiting
     */
    protected function configureRateLimiting(): void
    {
        // Limite pour l'API générale
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        // Limite pour les recherches géolocalisées (plus permissive)
        RateLimiter::for('geo-search', function (Request $request) {
            return Limit::perMinute(120)->by($request->user()?->id ?: $request->ip());
        });

        // Limite stricte pour la création de contacts (anti-spam)
        RateLimiter::for('contact', function (Request $request) {
            return Limit::perHour(10)->by($request->user()?->id ?: $request->ip());
        });

        // Limite pour l'authentification (anti-brute force)
        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });

        // Limite pour l'inscription
        RateLimiter::for('register', function (Request $request) {
            return Limit::perHour(3)->by($request->ip());
        });

        // Limite pour l'upload de photos
        RateLimiter::for('upload', function (Request $request) {
            return Limit::perMinute(20)->by($request->user()?->id ?: $request->ip());
        });

        // Limite pour les réservations (anti-fraude)
        RateLimiter::for('booking', function (Request $request) {
            return Limit::perHour(10)->by($request->user()?->id ?: $request->ip());
        });

        // Limite pour les avis (anti-spam)
        RateLimiter::for('review', function (Request $request) {
            return Limit::perDay(5)->by($request->user()?->id ?: $request->ip());
        });

        // Limite pour les signalements
        RateLimiter::for('report', function (Request $request) {
            return Limit::perHour(5)->by($request->user()?->id ?: $request->ip());
        });

        // Limite pour les webhooks externes
        RateLimiter::for('webhook', function (Request $request) {
            return Limit::perMinute(100)->by($request->ip());
        });

        // Limite pour les exports de données
        RateLimiter::for('export', function (Request $request) {
            return Limit::perHour(10)->by($request->user()?->id ?: $request->ip());
        });

        // Limite pour les notifications push
        RateLimiter::for('push', function (Request $request) {
            return Limit::perMinute(30)->by($request->user()?->id ?: $request->ip());
        });

        // Limite pour les messages chat
        RateLimiter::for('chat', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        // Limite pour la création de résidences
        RateLimiter::for('residence-create', function (Request $request) {
            return Limit::perDay(5)->by($request->user()?->id ?: $request->ip());
        });

        // Limite pour les paiements
        RateLimiter::for('payment', function (Request $request) {
            return Limit::perHour(20)->by($request->user()?->id ?: $request->ip());
        });
    }
}
