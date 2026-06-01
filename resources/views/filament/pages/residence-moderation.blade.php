<x-filament-panels::page>
    <div class="space-y-6">
        <x-filament.admin.hero
            eyebrow="Modération"
            title="Validation des annonces"
            subtitle="Accélérez la revue éditoriale, les demandes de corrections et la mise en ligne des résidences avec une lecture plus nette du flux."
            icon="heroicon-o-home-modern"
            tone="orange"
        />

        {{-- Stats Cards --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
            <x-filament.admin.metric-card label="En attente" :value="$pendingCount" icon="heroicon-o-clock" accent="amber" />
            <x-filament.admin.metric-card label="Approuvées aujourd'hui" :value="$todayApproved" icon="heroicon-o-check-circle" accent="emerald" />
            <x-filament.admin.metric-card label="Refusées aujourd'hui" :value="$todayRejected" icon="heroicon-o-x-circle" accent="rose" />
            <x-filament.admin.metric-card label="Modifications demandées" :value="$changesRequested" icon="heroicon-o-pencil-square" accent="sky" />
        </div>

        {{-- Table --}}
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <x-heroicon-o-home class="w-5 h-5"/>
                    Annonces en attente de validation
                </div>
            </x-slot>

            {{ $this->table }}
        </x-filament::section>
    </div>
</x-filament-panels::page>
