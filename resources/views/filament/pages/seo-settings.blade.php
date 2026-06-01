<x-filament-panels::page>
    <div class="space-y-6">
        <x-filament.admin.hero
            eyebrow="Paramètres"
            title="Données SEO"
            subtitle="Pilotez le titre, la description, le domaine canonique et les métadonnées globales depuis un écran plus lisible."
            icon="heroicon-o-magnifying-glass"
            tone="emerald"
        />

        <x-filament::section>
            <x-slot name="heading">Configuration SEO globale</x-slot>
            <x-slot name="description">Assurez la cohérence du référencement, du partage social et de la télémétrie sur l’ensemble du site.</x-slot>

            <form wire:submit="save" class="space-y-6">
                {{ $this->seoForm }}
                <div class="flex justify-end">
                    <x-filament::button type="submit" icon="heroicon-o-check">
                        Enregistrer les paramètres SEO
                    </x-filament::button>
                </div>
            </form>
        </x-filament::section>
    </div>
</x-filament-panels::page>
