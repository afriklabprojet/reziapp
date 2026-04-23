<x-filament-panels::page>
    <form wire:submit="save">
        {{ $this->seoForm }}
        <div class="mt-6">
            <x-filament::button type="submit" icon="heroicon-o-check">
                Enregistrer les paramètres SEO
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
