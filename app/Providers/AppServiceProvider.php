<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
            $this->validateProductionConfig();
        }

        $this->registerViewComposers();
        $this->registerEventListeners();
    }

    private function validateProductionConfig(): void
    {
        $missing = [];

        if (empty(config('sentry.dsn'))) {
            $missing[] = 'SENTRY_LARAVEL_DSN';
        }
        if (empty(config('services.jeko.secret'))) {
            $missing[] = 'JEKO_SECRET';
        }

        if ($missing !== []) {
            Log::channel('critical')->warning('Production config missing', ['keys' => $missing]);
        }
    }

    private function registerEventListeners(): void
    {
        Event::listen(
            \App\Events\LeaseContractSigned::class,
            \App\Listeners\SendLeaseContractSignedNotification::class,
        );

        Event::listen(
            \App\Events\PaymentCompleted::class,
            \App\Listeners\SendPaymentConfirmationNotification::class,
        );
    }

    private function registerViewComposers(): void
    {
        View::composer('layouts.owner', function ($view) {
            $route = request()->route()?->getName() ?? '';

            $sidebarActive = match (true) {
                str_starts_with($route, 'owner.dashboard')           => 'dashboard',
                str_starts_with($route, 'owner.residences')          => 'residences',
                str_starts_with($route, 'owner.bookings')            => 'bookings',
                str_starts_with($route, 'owner.contacts')            => 'contacts',
                str_starts_with($route, 'chat.')                     => 'messages',
                str_starts_with($route, 'owner.marketing.promotions') => 'promotions',
                str_starts_with($route, 'owner.marketing.coupons')   => 'coupons',
                str_starts_with($route, 'owner.marketing.sponsored') => 'sponsored',
                str_starts_with($route, 'owner.marketing.referrals') => 'referrals',
                str_starts_with($route, 'owner.analytics')           => 'analytics',
                str_starts_with($route, 'owner.compare')             => 'compare',
                str_starts_with($route, 'owner.earnings')            => 'earnings',
                str_starts_with($route, 'owner.auto-replies')        => 'auto-replies',
                str_starts_with($route, 'owner.pricing')             => 'pricing',
                str_starts_with($route, 'owner.statistics')          => 'analytics',
                str_starts_with($route, 'owner.notifications')       => 'notifications',
                str_starts_with($route, 'owner.cancellations')       => 'cancellations',
                str_starts_with($route, 'owner.disputes')            => 'disputes',
                str_starts_with($route, 'verification.')             => 'verification',
                str_starts_with($route, 'profile.')                  => 'profile',
                str_starts_with($route, 'owner.lease-contracts')     => 'lease-contracts',
                str_starts_with($route, 'owner.security-deposits')   => 'security-deposits',
                str_starts_with($route, 'owner.rent-receipts')       => 'rent-receipts',
                str_starts_with($route, 'owner.property-inspections') => 'property-inspections',
                default                                               => '',
            };

            $view->with('sidebarActive', $view->getData()['sidebarActive'] ?? $sidebarActive);
        });
    }
}
