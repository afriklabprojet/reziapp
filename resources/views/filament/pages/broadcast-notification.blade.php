<x-filament-panels::page>
    <div class="space-y-6">
        <x-filament.admin.hero
            eyebrow="Administration"
            title="Notification groupée"
            subtitle="Diffuse un message clair au bon segment d’utilisateurs, sur les bons canaux, depuis un écran mieux structuré."
            icon="heroicon-o-megaphone"
            tone="blue"
        />

        <x-filament::section>
            <x-slot name="heading">Composer l’envoi</x-slot>
            <x-slot name="description">Sélectionnez l’audience, rédigez le message et lancez l’envoi depuis cette interface centralisée.</x-slot>

            <form wire:submit="send" class="space-y-6">
                {{ $this->form }}

                <div class="flex justify-end">
                    <x-filament::button type="submit" size="lg" icon="heroicon-o-paper-airplane">
                        Envoyer la notification
                    </x-filament::button>
                </div>
            </form>
        </x-filament::section>
    </div>
</x-filament-panels::page>
