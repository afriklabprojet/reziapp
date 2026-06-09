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
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->registerPolicies();
        $this->registerGates();
    }

    private function registerPolicies(): void
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

    private function registerGates(): void
    {
        Gate::define('access-admin', fn ($user) => $user->isAdmin());
        Gate::define('access-owner-dashboard', fn ($user) => $user->isOwner() || $user->isAdmin());
        Gate::define('moderate-residences', fn ($user) => $user->isAdmin());
        Gate::define('manage-users', fn ($user) => $user->isAdmin());
        Gate::define('view-global-statistics', fn ($user) => $user->isAdmin());
        Gate::define('create-residence', fn ($user) => $user->isOwner() || $user->isAdmin());
        Gate::define('send-contact', fn ($user) => $user !== null);
        Gate::define('export-data', fn ($user) => $user->isAdmin());
    }
}
