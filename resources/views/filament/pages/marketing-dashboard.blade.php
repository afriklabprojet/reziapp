<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Stats Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            {{-- Campagnes --}}
            <x-filament::section>
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-primary-100 dark:bg-primary-900 rounded-lg">
                        <x-heroicon-o-megaphone class="w-6 h-6 text-primary-600 dark:text-primary-400"/>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Campagnes actives</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['active_campaigns'] }}</p>
                    </div>
                </div>
                <p class="mt-2 text-xs text-gray-500">{{ $stats['total_campaigns'] }} campagnes au total</p>
            </x-filament::section>

            {{-- Coupons --}}
            <x-filament::section>
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-success-100 dark:bg-success-900 rounded-lg">
                        <x-heroicon-o-ticket class="w-6 h-6 text-success-600 dark:text-success-400"/>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Codes promo actifs</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['active_coupons'] }}</p>
                    </div>
                </div>
                <p class="mt-2 text-xs text-gray-500">{{ $stats['total_coupons'] }} codes au total</p>
            </x-filament::section>

            {{-- Parrainages --}}
            <x-filament::section>
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-warning-100 dark:bg-warning-900 rounded-lg">
                        <x-heroicon-o-user-plus class="w-6 h-6 text-warning-600 dark:text-warning-400"/>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Parrainages en attente</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['pending_referrals'] }}</p>
                    </div>
                </div>
                <p class="mt-2 text-xs text-gray-500">{{ $stats['total_referrals'] }} parrainages au total</p>
            </x-filament::section>

            {{-- Sponsorisés --}}
            <x-filament::section>
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-info-100 dark:bg-info-900 rounded-lg">
                        <x-heroicon-o-star class="w-6 h-6 text-info-600 dark:text-info-400"/>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Annonces sponsorisées</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['active_sponsored'] }}</p>
                    </div>
                </div>
                <p class="mt-2 text-xs text-gray-500">{{ $stats['active_promotions'] }} promotions actives</p>
            </x-filament::section>
        </div>

        {{-- Campagnes Section --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <x-filament::section>
                <x-slot name="heading">
                    <div class="flex items-center gap-2">
                        <x-heroicon-o-megaphone class="w-5 h-5"/>
                        Performance des campagnes
                    </div>
                </x-slot>

                <div class="grid grid-cols-3 gap-4 mb-4">
                    <div class="text-center p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                        <p class="text-2xl font-bold text-primary-600">{{ number_format($campaignStats['total_sent']) }}</p>
                        <p class="text-xs text-gray-500">Envoyés</p>
                    </div>
                    <div class="text-center p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                        <p class="text-2xl font-bold text-success-600">{{ $campaignStats['open_rate'] }}%</p>
                        <p class="text-xs text-gray-500">Taux d'ouverture</p>
                    </div>
                    <div class="text-center p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                        <p class="text-2xl font-bold text-info-600">{{ $campaignStats['click_rate'] }}%</p>
                        <p class="text-xs text-gray-500">Taux de clic</p>
                    </div>
                </div>

                <div class="space-y-2">
                    @forelse($recentCampaigns as $campaign)
                        <div class="flex items-center justify-between p-2 bg-gray-50 dark:bg-gray-800 rounded">
                            <div class="flex items-center gap-2">
                                @php
                                    $typeIcon = match($campaign->type) {
                                        'email' => 'heroicon-o-envelope',
                                        'sms' => 'heroicon-o-device-phone-mobile',
                                        'push' => 'heroicon-o-bell',
                                        default => 'heroicon-o-chat-bubble-left',
                                    };
                                @endphp
                                <x-dynamic-component :component="$typeIcon" class="w-4 h-4 text-gray-400"/>
                                <span class="text-sm font-medium">{{ $campaign->name }}</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="text-xs text-gray-500">{{ $campaign->recipients_count ?? 0 }} envois</span>
                                @php
                                    $statusColor = match($campaign->status) {
                                        'sent' => 'success',
                                        'draft' => 'gray',
                                        'scheduled' => 'warning',
                                        'sending' => 'info',
                                        default => 'gray',
                                    };
                                @endphp
                                <x-filament::badge :color="$statusColor" size="sm">
                                    {{ $campaign->status }}
                                </x-filament::badge>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500 text-center py-4">Aucune campagne récente</p>
                    @endforelse
                </div>

                <x-slot name="footerActions">
                    <x-filament::button tag="a" href="{{ route('filament.admin.resources.campaigns.index') }}" size="sm" outlined>
                        Voir toutes les campagnes
                    </x-filament::button>
                </x-slot>
            </x-filament::section>

            {{-- Coupons Section --}}
            <x-filament::section>
                <x-slot name="heading">
                    <div class="flex items-center gap-2">
                        <x-heroicon-o-ticket class="w-5 h-5"/>
                        Codes promo populaires
                    </div>
                </x-slot>

                <div class="grid grid-cols-3 gap-4 mb-4">
                    <div class="text-center p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                        <p class="text-2xl font-bold text-primary-600">{{ $couponStats['active'] }}</p>
                        <p class="text-xs text-gray-500">Actifs</p>
                    </div>
                    <div class="text-center p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                        <p class="text-2xl font-bold text-success-600">{{ number_format($couponStats['total_uses']) }}</p>
                        <p class="text-xs text-gray-500">Utilisations</p>
                    </div>
                    <div class="text-center p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                        <p class="text-2xl font-bold text-warning-600">{{ number_format($couponStats['total_savings']) }}</p>
                        <p class="text-xs text-gray-500">FCFA économisés</p>
                    </div>
                </div>

                <div class="space-y-2">
                    @forelse($topCoupons as $coupon)
                        <div class="flex items-center justify-between p-2 bg-gray-50 dark:bg-gray-800 rounded">
                            <div class="flex items-center gap-2">
                                <x-filament::badge color="primary">{{ $coupon->code }}</x-filament::badge>
                                <span class="text-sm">{{ $coupon->name ?? '-' }}</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="text-xs text-gray-500">
                                    {{ $coupon->uses_count }}{{ $coupon->max_uses ? '/'.$coupon->max_uses : '' }} utilisations
                                </span>
                                <span class="text-xs font-medium text-success-600">
                                    @if($coupon->discount_type === 'percentage')
                                        -{{ $coupon->discount_value }}%
                                    @elseif($coupon->discount_type === 'fixed')
                                        -{{ number_format($coupon->discount_value) }} FCFA
                                    @else
                                        {{ $coupon->discount_value }} nuits
                                    @endif
                                </span>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500 text-center py-4">Aucun code promo</p>
                    @endforelse
                </div>

                <x-slot name="footerActions">
                    <x-filament::button tag="a" href="{{ route('filament.admin.resources.coupons.index') }}" size="sm" outlined>
                        Voir tous les codes
                    </x-filament::button>
                </x-slot>
            </x-filament::section>
        </div>

        {{-- Referrals & Sponsored Section --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {{-- Parrainages --}}
            <x-filament::section>
                <x-slot name="heading">
                    <div class="flex items-center gap-2">
                        <x-heroicon-o-user-plus class="w-5 h-5"/>
                        Programme de parrainage
                    </div>
                </x-slot>

                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div class="text-center p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                        <p class="text-2xl font-bold text-success-600">{{ $referralStats['rewarded'] }}</p>
                        <p class="text-xs text-gray-500">Récompensés</p>
                    </div>
                    <div class="text-center p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                        <p class="text-2xl font-bold text-primary-600">{{ $referralStats['conversion_rate'] }}%</p>
                        <p class="text-xs text-gray-500">Taux de conversion</p>
                    </div>
                </div>

                <div class="mb-4 p-3 bg-warning-50 dark:bg-warning-900/20 rounded-lg">
                    <p class="text-sm text-warning-700 dark:text-warning-300">
                        <x-heroicon-o-exclamation-triangle class="w-4 h-4 inline mr-1"/>
                        <strong>{{ $referralStats['qualified'] }}</strong> parrainages qualifiés en attente de récompense
                    </p>
                </div>

                <p class="text-sm text-gray-500 mb-2">Top parrains</p>
                <div class="space-y-2">
                    @forelse($topReferrers as $referrer)
                        <div class="flex items-center justify-between p-2 bg-gray-50 dark:bg-gray-800 rounded">
                            <div>
                                <p class="text-sm font-medium">{{ $referrer->name }}</p>
                                <p class="text-xs text-gray-500">{{ $referrer->referral_code }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-bold text-primary-600">{{ $referrer->successful_referrals }} réussis</p>
                                <p class="text-xs text-success-600">{{ number_format($referrer->total_earned) }} FCFA gagnés</p>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500 text-center py-4">Aucun parrain actif</p>
                    @endforelse
                </div>

                <x-slot name="footerActions">
                    <x-filament::button tag="a" href="{{ route('filament.admin.resources.referrals.index') }}" size="sm" outlined>
                        Voir les parrainages
                    </x-filament::button>
                </x-slot>
            </x-filament::section>

            {{-- Annonces sponsorisées --}}
            <x-filament::section>
                <x-slot name="heading">
                    <div class="flex items-center gap-2">
                        <x-heroicon-o-star class="w-5 h-5"/>
                        Annonces sponsorisées
                    </div>
                </x-slot>

                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div class="text-center p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                        <p class="text-2xl font-bold text-success-600">{{ number_format($sponsoredStats['total_revenue']) }}</p>
                        <p class="text-xs text-gray-500">FCFA de revenus</p>
                    </div>
                    <div class="text-center p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                        <p class="text-2xl font-bold text-primary-600">{{ $sponsoredStats['ctr'] }}%</p>
                        <p class="text-xs text-gray-500">CTR moyen</p>
                    </div>
                </div>

                <div class="grid grid-cols-3 gap-2 mb-4">
                    <div class="text-center p-2 bg-success-50 dark:bg-success-900/20 rounded">
                        <p class="text-lg font-bold text-success-600">{{ $sponsoredStats['active'] }}</p>
                        <p class="text-xs text-success-700">Actives</p>
                    </div>
                    <div class="text-center p-2 bg-warning-50 dark:bg-warning-900/20 rounded">
                        <p class="text-lg font-bold text-warning-600">{{ $sponsoredStats['pending'] }}</p>
                        <p class="text-xs text-warning-700">En attente</p>
                    </div>
                    <div class="text-center p-2 bg-gray-50 dark:bg-gray-800 rounded">
                        <p class="text-lg font-bold text-gray-600">{{ $sponsoredStats['total'] }}</p>
                        <p class="text-xs text-gray-500">Total</p>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="text-center p-3 border border-gray-200 dark:border-gray-700 rounded-lg">
                        <x-heroicon-o-eye class="w-6 h-6 mx-auto text-gray-400 mb-1"/>
                        <p class="text-xl font-bold">{{ number_format($sponsoredStats['total_impressions']) }}</p>
                        <p class="text-xs text-gray-500">Impressions</p>
                    </div>
                    <div class="text-center p-3 border border-gray-200 dark:border-gray-700 rounded-lg">
                        <x-heroicon-o-cursor-arrow-rays class="w-6 h-6 mx-auto text-gray-400 mb-1"/>
                        <p class="text-xl font-bold">{{ number_format($sponsoredStats['total_clicks']) }}</p>
                        <p class="text-xs text-gray-500">Clics</p>
                    </div>
                </div>

                <x-slot name="footerActions">
                    <x-filament::button tag="a" href="{{ route('filament.admin.resources.sponsored-listings.index') }}" size="sm" outlined>
                        Gérer les annonces
                    </x-filament::button>
                </x-slot>
            </x-filament::section>
        </div>

        {{-- Promotions Summary --}}
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <x-heroicon-o-gift class="w-5 h-5"/>
                    Promotions
                </div>
            </x-slot>

            <div class="grid grid-cols-4 gap-4">
                <div class="text-center p-4 bg-primary-50 dark:bg-primary-900/20 rounded-lg">
                    <p class="text-3xl font-bold text-primary-600">{{ $promotionStats['total'] }}</p>
                    <p class="text-sm text-primary-700 dark:text-primary-300">Total</p>
                </div>
                <div class="text-center p-4 bg-success-50 dark:bg-success-900/20 rounded-lg">
                    <p class="text-3xl font-bold text-success-600">{{ $promotionStats['active'] }}</p>
                    <p class="text-sm text-success-700 dark:text-success-300">Actives</p>
                </div>
                <div class="text-center p-4 bg-warning-50 dark:bg-warning-900/20 rounded-lg">
                    <p class="text-3xl font-bold text-warning-600">{{ $promotionStats['upcoming'] }}</p>
                    <p class="text-sm text-warning-700 dark:text-warning-300">À venir</p>
                </div>
                <div class="text-center p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                    <p class="text-3xl font-bold text-gray-600">{{ $promotionStats['expired'] }}</p>
                    <p class="text-sm text-gray-500">Expirées</p>
                </div>
            </div>

            <x-slot name="footerActions">
                <x-filament::button tag="a" href="{{ route('filament.admin.resources.promotions.index') }}" size="sm" outlined>
                    Gérer les promotions
                </x-filament::button>
            </x-slot>
        </x-filament::section>
    </div>
</x-filament-panels::page>
