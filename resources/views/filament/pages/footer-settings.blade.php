<x-filament-panels::page>
    <div class="space-y-6">
        <x-filament.admin.hero
            eyebrow="Contenu"
            title="Paramètres du footer"
            subtitle="Structure la zone footer avec une lecture plus claire des blocs newsletter, statistiques, marque et réseaux sociaux."
            icon="heroicon-o-squares-2x2"
            tone="slate"
        />

        {{-- Newsletter CTA --}}
        <x-filament::section>
            <x-slot name="heading">Newsletter footer</x-slot>
            <x-slot name="description">Travaillez le bloc de capture email visible en pied de page.</x-slot>
            <form wire:submit="saveNewsletter" class="space-y-4">
                {{ $this->newsletterForm }}
                <div class="flex justify-end">
                    <x-filament::button type="submit">
                        Enregistrer la newsletter
                    </x-filament::button>
                </div>
            </form>
        </x-filament::section>

        {{-- Strip de chiffres-clés --}}
        <x-filament::section>
            <x-slot name="heading">Chiffres-clés</x-slot>
            <x-slot name="description">Réglez les libellés et la visibilité du strip de preuves sociales affiché dans le footer.</x-slot>
            <form wire:submit="saveStats" class="space-y-4">
                {{ $this->statsForm }}
                <div class="flex justify-end">
                    <x-filament::button type="submit">
                        Enregistrer les chiffres
                    </x-filament::button>
                </div>
            </form>
        </x-filament::section>

        {{-- Identité de marque --}}
        <x-filament::section>
            <x-slot name="heading">Identité de marque</x-slot>
            <x-slot name="description">Maîtrisez le ton, le texte d’accompagnement et le support visible dans le footer.</x-slot>
            <form wire:submit="saveBrand" class="space-y-4">
                {{ $this->brandForm }}
                <div class="flex justify-end">
                    <x-filament::button type="submit">
                        Enregistrer la marque
                    </x-filament::button>
                </div>
            </form>
        </x-filament::section>

        {{-- Réseaux sociaux --}}
        <x-filament::section>
            <x-slot name="heading">Réseaux sociaux</x-slot>
            <x-slot name="description">Pilotez la présence sociale affichée en pied de page et les destinations de chaque lien.</x-slot>
            <form wire:submit="saveSocial" class="space-y-4">
                {{ $this->socialForm }}
                <div class="flex justify-end">
                    <x-filament::button type="submit">
                        Enregistrer les réseaux sociaux
                    </x-filament::button>
                </div>
            </form>
        </x-filament::section>

    </div>
</x-filament-panels::page>
