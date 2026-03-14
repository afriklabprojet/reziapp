<x-filament-panels::page>
    <form wire:submit="send">
        {{ $this->form }}

        <div class="mt-6 flex justify-end">
            <x-filament::button type="submit" size="lg" icon="heroicon-o-paper-airplane">
                Envoyer la notification
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
