<x-filament-panels::page>
    <div class="space-y-6">

        {{-- ═══════════════════════════════════════════════════════
             BANNER — Welcome + Date + Actions rapides
        ═══════════════════════════════════════════════════════ --}}
        <x-filament.admin.hero
            eyebrow="{{ now()->locale('fr')->isoFormat('dddd D MMMM YYYY') }}"
            title="Bienvenue, {{ auth()->user()->name }}"
            subtitle="Tableau de bord Rezi App — vue d'ensemble de la plateforme, des alertes critiques et des leviers d’action prioritaires."
            icon="heroicon-o-home-modern"
            tone="rose"
        >
            <x-slot name="actions">
                     <a href="{{ route('filament.admin.resources.residences.index') }}" class="inline-flex items-center gap-1.5 rounded-xl bg-white/15 px-3.5 py-2 text-sm font-medium text-white transition hover:bg-white/25 focus-visible:bg-white/25 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white/60"
                         onfocus="this.classList.add('bg-white/25')" onblur="this.classList.remove('bg-white/25')">
                    <x-heroicon-o-home class="h-4 w-4" />
                    Annonces
                </a>
                     <a href="{{ route('filament.admin.resources.bookings.index') }}" class="inline-flex items-center gap-1.5 rounded-xl bg-white/15 px-3.5 py-2 text-sm font-medium text-white transition hover:bg-white/25 focus-visible:bg-white/25 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white/60"
                         onfocus="this.classList.add('bg-white/25')" onblur="this.classList.remove('bg-white/25')">
                    <x-heroicon-o-calendar-days class="h-4 w-4" />
                    Réservations
                </a>
                     <a href="{{ route('filament.admin.resources.locataires.index') }}" class="inline-flex items-center gap-1.5 rounded-xl bg-white/15 px-3.5 py-2 text-sm font-medium text-white transition hover:bg-white/25 focus-visible:bg-white/25 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white/60"
                         onfocus="this.classList.add('bg-white/25')" onblur="this.classList.remove('bg-white/25')">
                    <x-heroicon-o-users class="h-4 w-4" />
                    Utilisateurs
                </a>
                     <a href="{{ route('filament.admin.resources.payments.index') }}" class="inline-flex items-center gap-1.5 rounded-xl bg-white/15 px-3.5 py-2 text-sm font-medium text-white transition hover:bg-white/25 focus-visible:bg-white/25 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white/60"
                         onfocus="this.classList.add('bg-white/25')" onblur="this.classList.remove('bg-white/25')">
                    <x-heroicon-o-banknotes class="h-4 w-4" />
                    Paiements
                </a>
            </x-slot>
        </x-filament.admin.hero>

        {{-- ═══════════════════════════════════════════════════════
             SECTION 1 — KPI Cards (6 métriques clés)
        ═══════════════════════════════════════════════════════ --}}
        @livewire(\App\Filament\Widgets\StatsOverview::class)

        {{-- ═══════════════════════════════════════════════════════
             SECTION 2 — Alertes urgentes
        ═══════════════════════════════════════════════════════ --}}
        @php
            $alertCounts = \Illuminate\Support\Facades\Cache::remember('admin.dashboard.alerts', 120, fn () => [
                'residences' => \App\Models\Residence::where('status', 'pending')->count(),
                'payouts'    => \App\Models\Payout::where('status', 'pending')->count(),
                'tickets'    => \App\Models\SupportTicket::whereIn('status', ['open', 'pending'])->count(),
                'fraud'      => \App\Models\FraudReport::where('status', 'pending')->count(),
            ]);
            $pendingResidences = $alertCounts['residences'];
            $pendingPayouts    = $alertCounts['payouts'];
            $openTickets       = $alertCounts['tickets'];
            $fraudPending      = $alertCounts['fraud'];
        @endphp

        @if($pendingResidences > 0 || $pendingPayouts > 0 || $openTickets > 0 || $fraudPending > 0)
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
            @if($pendingResidences > 0)
                <a href="{{ route('filament.admin.resources.residences.index', ['tableFilters[status][value]' => 'pending']) }}"
                    class="flex items-center gap-3 rounded-xl border border-amber-200 bg-amber-50 p-4 text-amber-800 transition hover:bg-amber-100 focus-visible:bg-amber-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-300 dark:border-amber-700 dark:bg-amber-950 dark:text-amber-300"
                    onfocus="this.classList.add('bg-amber-100')" onblur="this.classList.remove('bg-amber-100')">
                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-amber-200 dark:bg-amber-800">
                    <x-heroicon-o-home class="h-5 w-5" />
                </div>
                <div>
                    <p class="text-lg font-bold">{{ $pendingResidences }}</p>
                    <p class="text-xs">Annonce(s) à valider</p>
                </div>
            </a>
            @endif

            @if($pendingPayouts > 0)
                <a href="{{ route('filament.admin.resources.payouts.index') }}"
                    class="flex items-center gap-3 rounded-xl border border-blue-200 bg-blue-50 p-4 text-blue-800 transition hover:bg-blue-100 focus-visible:bg-blue-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-300 dark:border-blue-700 dark:bg-blue-950 dark:text-blue-300"
                    onfocus="this.classList.add('bg-blue-100')" onblur="this.classList.remove('bg-blue-100')">
                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-blue-200 dark:bg-blue-800">
                    <x-heroicon-o-banknotes class="h-5 w-5" />
                </div>
                <div>
                    <p class="text-lg font-bold">{{ $pendingPayouts }}</p>
                    <p class="text-xs">Versement(s) en attente</p>
                </div>
            </a>
            @endif

            @if($openTickets > 0)
                <a href="{{ route('filament.admin.resources.support-tickets.index') }}"
                    class="flex items-center gap-3 rounded-xl border border-purple-200 bg-purple-50 p-4 text-purple-800 transition hover:bg-purple-100 focus-visible:bg-purple-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-purple-300 dark:border-purple-700 dark:bg-purple-950 dark:text-purple-300"
                    onfocus="this.classList.add('bg-purple-100')" onblur="this.classList.remove('bg-purple-100')">
                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-purple-200 dark:bg-purple-800">
                    <x-heroicon-o-chat-bubble-left-right class="h-5 w-5" />
                </div>
                <div>
                    <p class="text-lg font-bold">{{ $openTickets }}</p>
                    <p class="text-xs">Ticket(s) support ouvert(s)</p>
                </div>
            </a>
            @endif

            @if($fraudPending > 0)
                <a href="{{ route('filament.admin.resources.fraud-reports.index') }}"
                    class="flex items-center gap-3 rounded-xl border border-red-200 bg-red-50 p-4 text-red-800 transition hover:bg-red-100 focus-visible:bg-red-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-red-300 dark:border-red-700 dark:bg-red-950 dark:text-red-300"
                    onfocus="this.classList.add('bg-red-100')" onblur="this.classList.remove('bg-red-100')">
                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-red-200 dark:bg-red-800">
                    <x-heroicon-o-exclamation-triangle class="h-5 w-5" />
                </div>
                <div>
                    <p class="text-lg font-bold">{{ $fraudPending }}</p>
                    <p class="text-xs">Signalement(s) à traiter</p>
                </div>
            </a>
            @endif
        </div>
        @endif

        {{-- ═══════════════════════════════════════════════════════
             SECTION 3 — Graphiques (Revenus + Réservations)
        ═══════════════════════════════════════════════════════ --}}
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            @livewire(\App\Filament\Widgets\RevenueChartWidget::class)
            @livewire(\App\Filament\Widgets\BookingsChartWidget::class)
        </div>

        {{-- ═══════════════════════════════════════════════════════
             SECTION 4 — Approbations en attente
        ═══════════════════════════════════════════════════════ --}}
        @livewire(\App\Filament\Widgets\PendingApprovalsWidget::class)

        {{-- ═══════════════════════════════════════════════════════
             SECTION 5 — Activité récente (Réservations + Paiements)
        ═══════════════════════════════════════════════════════ --}}
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            @livewire(\App\Filament\Widgets\RecentBookingsWidget::class)
            @livewire(\App\Filament\Widgets\RecentPaymentsWidget::class)
        </div>

        {{-- ═══════════════════════════════════════════════════════
             SECTION 6 — Statistiques Mise en avant (Marketing)
        ═══════════════════════════════════════════════════════ --}}
        @livewire(\App\Filament\Widgets\SponsoredStatsWidget::class)

        {{-- ═══════════════════════════════════════════════════════
             SECTION 7 — Statistiques Paiements
        ═══════════════════════════════════════════════════════ --}}
        @livewire(\App\Filament\Widgets\PaymentStatsWidget::class)

        {{-- ═══════════════════════════════════════════════════════
             SECTION 8 — Graphiques géo + 12 mois de revenus
        ═══════════════════════════════════════════════════════ --}}
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            @livewire(\App\Filament\Widgets\ResidencesByLocationChart::class)
            @livewire(\App\Filament\Widgets\RevenueChart::class)
        </div>

        {{-- ═══════════════════════════════════════════════════════
             SECTION 9 — Newsletter
        ═══════════════════════════════════════════════════════ --}}
        @php
            $newsletterData    = \Illuminate\Support\Facades\Cache::remember('admin.dashboard.newsletter', 300, fn () => [
                'total'      => \App\Models\NewsletterSubscriber::count(),
                'active'     => \App\Models\NewsletterSubscriber::where('status', 'active')->count(),
                'thisMonth'  => \App\Models\NewsletterSubscriber::where('status', 'active')
                    ->where('created_at', '>=', now()->startOfMonth())->count(),
                'latest'     => \App\Models\NewsletterSubscriber::where('status', 'active')
                    ->latest()->limit(5)->get(),
            ]);
            $newsletterTotal     = $newsletterData['total'];
            $newsletterActive    = $newsletterData['active'];
            $newsletterThisMonth = $newsletterData['thisMonth'];
            $latestSubscribers   = $newsletterData['latest'];
        @endphp

        @if($newsletterTotal > 0)
        @php
            $activeRate = $newsletterTotal > 0 ? round(($newsletterActive / $newsletterTotal) * 100) : 0;
        @endphp
        <div class="overflow-hidden rounded-2xl border border-pink-100 bg-white shadow-sm dark:border-pink-900/30 dark:bg-gray-900">

            {{-- Header gradient --}}
            <div class="bg-linear-to-r from-pink-500 to-rose-500 px-5 py-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-white/20">
                            <x-heroicon-o-envelope class="h-4 w-4 text-white" />
                        </div>
                        <span class="text-sm font-semibold text-white">Newsletter</span>
                    </div>
                    <span class="rounded-full bg-white/20 px-3 py-1 text-xs font-medium text-white backdrop-blur-sm">
                        {{ number_format($newsletterActive) }} actif{{ $newsletterActive > 1 ? 's' : '' }}
                    </span>
                </div>
            </div>

            <div class="p-5">
                {{-- Stats row --}}
                <div class="mb-4 flex items-stretch gap-3">
                    <div class="flex flex-1 flex-col items-center justify-center rounded-xl bg-gray-50 py-3 dark:bg-gray-800">
                        <span class="text-2xl font-bold text-gray-800 dark:text-white">{{ number_format($newsletterTotal) }}</span>
                        <span class="mt-0.5 text-xs text-gray-400 dark:text-gray-500">Total</span>
                    </div>
                    <div class="flex flex-1 flex-col items-center justify-center rounded-xl bg-emerald-50 py-3 dark:bg-emerald-950/40">
                        <span class="text-2xl font-bold text-emerald-600 dark:text-emerald-400">{{ number_format($newsletterActive) }}</span>
                        <span class="mt-0.5 text-xs text-emerald-500 dark:text-emerald-500">Actifs</span>
                    </div>
                    <div class="flex flex-1 flex-col items-center justify-center rounded-xl bg-blue-50 py-3 dark:bg-blue-950/40">
                        <span class="text-2xl font-bold text-blue-600 dark:text-blue-400">+{{ $newsletterThisMonth }}</span>
                        <span class="mt-0.5 text-xs text-blue-500 dark:text-blue-500">Ce mois</span>
                    </div>
                </div>

                {{-- Taux d'engagement --}}
                <div class="mb-4">
                    <div class="mb-1.5 flex items-center justify-between">
                        <span class="text-xs text-gray-400 dark:text-gray-500">Taux d'engagement</span>
                        <span class="text-xs font-semibold text-pink-600 dark:text-pink-400">{{ $activeRate }}%</span>
                    </div>
                    <div class="h-1.5 w-full overflow-hidden rounded-full bg-gray-100 dark:bg-gray-700">
                        <div class="h-full rounded-full bg-linear-to-r from-pink-400 to-rose-500 transition-all duration-500"
                             style="width: {{ $activeRate }}%"></div>
                    </div>
                </div>

                {{-- Derniers abonnés --}}
                @if($latestSubscribers->isNotEmpty())
                <div>
                    <p class="mb-2 text-xs font-medium uppercase tracking-wider text-gray-400 dark:text-gray-500">Derniers abonnés</p>
                    <div class="space-y-1">
                        @foreach($latestSubscribers as $sub)
                        @php
                            $colors = ['bg-pink-100 text-pink-700 dark:bg-pink-900/60 dark:text-pink-300', 'bg-violet-100 text-violet-700 dark:bg-violet-900/60 dark:text-violet-300', 'bg-blue-100 text-blue-700 dark:bg-blue-900/60 dark:text-blue-300', 'bg-amber-100 text-amber-700 dark:bg-amber-900/60 dark:text-amber-300'];
                            $color = $colors[$loop->index % count($colors)];
                        @endphp
                        <div class="flex items-center justify-between rounded-lg px-2 py-1.5 transition hover:bg-gray-50 dark:hover:bg-gray-800">
                            <div class="flex items-center gap-2.5">
                                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-xs font-bold {{ $color }}">
                                    {{ mb_strtoupper(mb_substr($sub->email, 0, 1)) }}
                                </div>
                                <div class="min-w-0">
                                    @if($sub->name && $sub->name !== $sub->email)
                                    <p class="truncate text-sm font-medium text-gray-800 dark:text-white">{{ $sub->name }}</p>
                                    @endif
                                    <p class="truncate text-xs text-gray-400">{{ $sub->email }}</p>
                                </div>
                            </div>
                            <span class="ml-2 shrink-0 text-xs text-gray-300 dark:text-gray-600">{{ $sub->created_at->diffForHumans() }}</span>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>
        </div>
        @endif

        {{-- ═══════════════════════════════════════════════════════
             SECTION 10 — Alertes & Signalements
        ═══════════════════════════════════════════════════════ --}}
        @livewire(\App\Filament\Widgets\AlertsWidget::class)

    </div>
</x-filament-panels::page>
