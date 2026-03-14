<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Hero --}}
        <form wire:submit="saveHero">
            {{ $this->heroForm }}
            <div class="mt-4">
                <x-filament::button type="submit">
                    Enregistrer le Hero
                </x-filament::button>
            </div>
        </form>

        {{-- Cards --}}
        <form wire:submit="saveCards">
            {{ $this->cardsForm }}
            <div class="mt-4">
                <x-filament::button type="submit">
                    Enregistrer les Cartes
                </x-filament::button>
            </div>
        </form>

        {{-- FAQ --}}
        <form wire:submit="saveFaq">
            {{ $this->faqForm }}
            <div class="mt-4">
                <x-filament::button type="submit">
                    Enregistrer la FAQ
                </x-filament::button>
            </div>
        </form>

        {{-- Hours --}}
        <form wire:submit="saveHours">
            {{ $this->hoursForm }}
            <div class="mt-4">
                <x-filament::button type="submit">
                    Enregistrer les Horaires
                </x-filament::button>
            </div>
        </form>

        {{-- CTA --}}
        <form wire:submit="saveCta">
            {{ $this->ctaForm }}
            <div class="mt-4">
                <x-filament::button type="submit">
                    Enregistrer le CTA
                </x-filament::button>
            </div>
        </form>

        {{-- SEO --}}
        <form wire:submit="saveSeo">
            {{ $this->seoForm }}
            <div class="mt-4">
                <x-filament::button type="submit">
                    Enregistrer le SEO
                </x-filament::button>
            </div>
        </form>
    </div>
</x-filament-panels::page>
