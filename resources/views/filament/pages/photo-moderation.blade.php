<x-filament-panels::page>
    <div class="space-y-6">
        <x-filament.admin.hero
            eyebrow="Modération"
            title="Modération des photos"
            subtitle="Priorisez les images à vérifier, surveillez les rejets IA et gardez une vision nette du flux de validation."
            icon="heroicon-o-photo"
            tone="slate"
        />

        {{-- Stats Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <x-filament.admin.metric-card label="À vérifier" :value="\App\Models\Photo::where('moderation_status', 'review')->count()" icon="heroicon-o-eye" accent="amber" />
            <x-filament.admin.metric-card label="Rejetées IA" :value="\App\Models\Photo::where('moderation_status', 'rejected')->count()" icon="heroicon-o-x-circle" accent="rose" />
            <x-filament.admin.metric-card label="Approuvées" :value="\App\Models\Photo::where('moderation_status', 'approved')->count()" icon="heroicon-o-check-circle" accent="emerald" />
            <x-filament.admin.metric-card label="Total photos" :value="\App\Models\Photo::count()" icon="heroicon-o-photo" accent="slate" />
        </div>

        {{-- Table --}}
        {{ $this->table }}
    </div>
</x-filament-panels::page>
