<x-filament-panels::page>
    <div class="space-y-6">
        <x-filament::section>
            <div class="space-y-2">
                <h2 class="text-base font-semibold text-gray-950 dark:text-white">Où régler les pourcentages</h2>
                <p class="text-sm text-gray-600 dark:text-gray-300">
                    Le pourcentage appliqué au propriétaire se règle dans le bloc <strong>Commissions</strong>, champ <strong>Taux de commission propriétaire (%)</strong>.
                </p>
            </div>
        </x-filament::section>

        {{-- Commission Settings --}}
        <form wire:submit="saveCommission">
            {{ $this->commissionForm }}

            <div class="mt-4">
                <x-filament::button type="submit">
                    Enregistrer la commission propriétaire
                </x-filament::button>
            </div>
        </form>

        {{-- Payment Settings --}}
        <form wire:submit="savePayment">
            {{ $this->paymentForm }}

            <div class="mt-4">
                <x-filament::button type="submit">
                    Enregistrer les paiements
                </x-filament::button>
            </div>
        </form>

        {{-- Booking Settings --}}
        <form wire:submit="saveBooking">
            {{ $this->bookingForm }}

            <div class="mt-4">
                <x-filament::button type="submit">
                    Enregistrer les réservations
                </x-filament::button>
            </div>
        </form>

        {{-- Pricing Settings --}}
        <form wire:submit="savePricing">
            {{ $this->pricingForm }}

            <div class="mt-4">
                <x-filament::button type="submit">
                    Enregistrer la tarification
                </x-filament::button>
            </div>
        </form>

        {{-- General Settings --}}
        <form wire:submit="saveGeneral">
            {{ $this->generalForm }}

            <div class="mt-4">
                <x-filament::button type="submit">
                    Enregistrer les paramètres généraux
                </x-filament::button>
            </div>
        </form>
    </div>
</x-filament-panels::page>
