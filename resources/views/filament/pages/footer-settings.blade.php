<x-filament-panels::page>
    <div class="space-y-6">

        {{-- Newsletter CTA --}}
        <form wire:submit="saveNewsletter">
            {{ $this->newsletterForm }}
            <div class="mt-4">
                <x-filament::button type="submit">
                    Enregistrer la newsletter
                </x-filament::button>
            </div>
        </form>

        {{-- Strip de chiffres-clés --}}
        <form wire:submit="saveStats">
            {{ $this->statsForm }}
            <div class="mt-4">
                <x-filament::button type="submit">
                    Enregistrer les chiffres
                </x-filament::button>
            </div>
        </form>

        {{-- Identité de marque --}}
        <form wire:submit="saveBrand">
            {{ $this->brandForm }}
            <div class="mt-4">
                <x-filament::button type="submit">
                    Enregistrer la marque
                </x-filament::button>
            </div>
        </form>

        {{-- Réseaux sociaux --}}
        <form wire:submit="saveSocial">
            {{ $this->socialForm }}
            <div class="mt-4">
                <x-filament::button type="submit">
                    Enregistrer les réseaux sociaux
                </x-filament::button>
            </div>
        </form>

    </div>
</x-filament-panels::page>
