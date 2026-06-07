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

        {{-- Mission --}}
        <form wire:submit="saveMission">
            {{ $this->missionForm }}
            <div class="mt-4">
                <x-filament::button type="submit">
                    Enregistrer la Mission
                </x-filament::button>
            </div>
        </form>

        {{-- Steps --}}
        <form wire:submit="saveSteps">
            {{ $this->stepsForm }}
            <div class="mt-4">
                <x-filament::button type="submit">
                    Enregistrer les Étapes
                </x-filament::button>
            </div>
        </form>

        {{-- Values --}}
        <form wire:submit="saveValues">
            {{ $this->valuesForm }}
            <div class="mt-4">
                <x-filament::button type="submit">
                    Enregistrer les Valeurs
                </x-filament::button>
            </div>
        </form>

        {{-- Why --}}
        <form wire:submit="saveWhy">
            {{ $this->whyForm }}
            <div class="mt-4">
                <x-filament::button type="submit">
                    Enregistrer Pourquoi Rezi Studio Meublé Faya
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
