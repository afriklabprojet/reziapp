<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Welcome Banner --}}
        <div class="p-6 bg-linear-to-r from-rose-500 to-pink-600 rounded-xl text-white">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold">Bienvenue sur REZI Admin 👋</h2>
                    <p class="mt-1 opacity-90">Gérez vos résidences, réservations et utilisateurs depuis ce tableau de bord.</p>
                </div>
                <div class="hidden md:block">
                    <svg class="w-20 h-20 opacity-20" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"/>
                    </svg>
                </div>
            </div>
        </div>
        
        {{-- Widgets --}}
        @livewire(\App\Filament\Widgets\StatsOverview::class)
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            @livewire(\App\Filament\Widgets\BookingsChartWidget::class)
            @livewire(\App\Filament\Widgets\RevenueChartWidget::class)
        </div>
        
        @livewire(\App\Filament\Widgets\PendingApprovalsWidget::class)
        
        @livewire(\App\Filament\Widgets\RecentBookingsWidget::class)
    </div>
</x-filament-panels::page>
