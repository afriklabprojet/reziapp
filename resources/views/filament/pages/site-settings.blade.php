<x-filament-panels::page>
    <div class="space-y-6">

        {{-- Pays & Localisation --}}
        <form wire:submit="saveCountry">
            {{ $this->countryForm }}
            <div class="mt-4">
                <x-filament::button type="submit" icon="heroicon-o-check">
                    Enregistrer les paramètres pays
                </x-filament::button>
            </div>
        </form>

        {{-- SEO --}}
        <form wire:submit="saveSeo">
            {{ $this->seoForm }}
            <div class="mt-4">
                <x-filament::button type="submit" icon="heroicon-o-check">
                    Enregistrer les paramètres SEO
                </x-filament::button>
            </div>
        </form>

    </div>
</x-filament-panels::page>
