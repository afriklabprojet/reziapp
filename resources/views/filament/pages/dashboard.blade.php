<x-filament-panels::page>
    <div class="space-y-6">

        {{-- ═══════════════════════════════════════════════════════
             BANNER — Welcome + Date + Actions rapides
        ═══════════════════════════════════════════════════════ --}}
        <div class="relative overflow-hidden rounded-2xl p-6 text-white shadow-lg"
             style="background: linear-gradient(to right, #e11d48, #db2777, #f43f5e);">
            {{-- Cercles décoratifs --}}
            <div class="pointer-events-none absolute -right-10 -top-10 h-48 w-48 rounded-full" style="background: rgba(255,255,255,0.1);"></div>
            <div class="pointer-events-none absolute -bottom-8 right-24 h-32 w-32 rounded-full" style="background: rgba(255,255,255,0.05);"></div>

            <div class="relative flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-sm font-medium uppercase tracking-widest" style="color: #fecdd3;">
                        {{ now()->locale('fr')->isoFormat('dddd D MMMM YYYY') }}
                    </p>
                    <h1 class="mt-1 text-2xl font-bold" style="color: #ffffff;">
                        Bienvenue, {{ auth()->user()->name }} 👋
                    </h1>
                    <p class="mt-1 text-sm" style="color: #ffe4e6;">
                        Tableau de bord REZI — vue d'ensemble de votre plateforme
                    </p>
                </div>

                {{-- Boutons d'action rapide --}}
                <div class="flex flex-wrap gap-2">
                    <a href="{{ route('filament.admin.resources.residences.index') }}"
                       class="inline-flex items-center gap-1.5 rounded-lg px-3 py-2 text-sm font-medium text-white transition"
                       style="background: rgba(255,255,255,0.2);"
                       onmouseover="this.style.background='rgba(255,255,255,0.3)'"
                       onmouseout="this.style.background='rgba(255,255,255,0.2)'">
                        <x-heroicon-o-home class="h-4 w-4" />
                        Annonces
                    </a>
                    <a href="{{ route('filament.admin.resources.bookings.index') }}"
                       class="inline-flex items-center gap-1.5 rounded-lg px-3 py-2 text-sm font-medium text-white transition"
                       style="background: rgba(255,255,255,0.2);"
                       onmouseover="this.style.background='rgba(255,255,255,0.3)'"
                       onmouseout="this.style.background='rgba(255,255,255,0.2)'">
                        <x-heroicon-o-calendar-days class="h-4 w-4" />
                        Réservations
                    </a>
                    <a href="{{ route('filament.admin.resources.locataires.index') }}"
                       class="inline-flex items-center gap-1.5 rounded-lg px-3 py-2 text-sm font-medium text-white transition"
                       style="background: rgba(255,255,255,0.2);"
                       onmouseover="this.style.background='rgba(255,255,255,0.3)'"
                       onmouseout="this.style.background='rgba(255,255,255,0.2)'">
                        <x-heroicon-o-users class="h-4 w-4" />
                        Utilisateurs
                    </a>
                    <a href="{{ route('filament.admin.resources.payments.index') }}"
                       class="inline-flex items-center gap-1.5 rounded-lg px-3 py-2 text-sm font-medium text-white transition"
                       style="background: rgba(255,255,255,0.2);"
                       onmouseover="this.style.background='rgba(255,255,255,0.3)'"
                       onmouseout="this.style.background='rgba(255,255,255,0.2)'">
                        <x-heroicon-o-banknotes class="h-4 w-4" />
                        Paiements
                    </a>
                </div>
            </div>
        </div>

        {{-- ═══════════════════════════════════════════════════════
             SECTION 1 — KPI Cards (6 métriques clés)
        ═══════════════════════════════════════════════════════ --}}
        @livewire(\App\Filament\Widgets\StatsOverview::class)

        {{-- ═══════════════════════════════════════════════════════
             SECTION 2 — Alertes urgentes
        ═══════════════════════════════════════════════════════ --}}
        @php
            $pendingResidences = \App\Models\Residence::where('status', 'pending')->count();
            $pendingPayouts    = \App\Models\Payout::where('status', 'pending')->count();
            $openTickets       = \App\Models\SupportTicket::whereIn('status', ['open', 'pending'])->count();
            $fraudPending      = \App\Models\FraudReport::where('status', 'pending')->count();
        @endphp

        @if($pendingResidences > 0 || $pendingPayouts > 0 || $openTickets > 0 || $fraudPending > 0)
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
            @if($pendingResidences > 0)
            <a href="{{ route('filament.admin.resources.residences.index', ['tableFilters[status][value]' => 'pending']) }}"
               class="flex items-center gap-3 rounded-xl border border-amber-200 bg-amber-50 p-4 text-amber-800 hover:bg-amber-100 transition dark:border-amber-700 dark:bg-amber-950 dark:text-amber-300">
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
               class="flex items-center gap-3 rounded-xl border border-blue-200 bg-blue-50 p-4 text-blue-800 hover:bg-blue-100 transition dark:border-blue-700 dark:bg-blue-950 dark:text-blue-300">
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
               class="flex items-center gap-3 rounded-xl border border-purple-200 bg-purple-50 p-4 text-purple-800 hover:bg-purple-100 transition dark:border-purple-700 dark:bg-purple-950 dark:text-purple-300">
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
               class="flex items-center gap-3 rounded-xl border border-red-200 bg-red-50 p-4 text-red-800 hover:bg-red-100 transition dark:border-red-700 dark:bg-red-950 dark:text-red-300">
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
             SECTION 6 — Alertes & Signalements
        ═══════════════════════════════════════════════════════ --}}
        @livewire(\App\Filament\Widgets\AlertsWidget::class)

    </div>
</x-filament-panels::page>
