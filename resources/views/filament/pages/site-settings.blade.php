<x-filament-panels::page>
    <div class="space-y-6">
        <x-filament.admin.hero
            eyebrow="Paramètres"
            title="Localisation du site"
            subtitle="Définissez le pays, la devise, le fuseau et les conventions régionales qui cadrent l’expérience Rezi App."
            icon="heroicon-o-map-pin"
            tone="blue"
        />

        <x-filament::section>
            <x-slot name="heading">Réglages régionaux</x-slot>
            <x-slot name="description">Conservez ici la source de vérité pour le pays, la devise et la langue par défaut du produit.</x-slot>

            <form wire:submit="saveCountry" class="space-y-6">
                {{ $this->countryForm }}

                <div class="flex justify-end">
                    <x-filament::button type="submit" icon="heroicon-o-check">
                        Enregistrer les paramètres localisation
                    </x-filament::button>
                </div>
            </form>
        </x-filament::section>
    </div>
</x-filament-panels::page>
