<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Commission Settings --}}
        <form wire:submit="saveCommission">
            {{ $this->commissionForm }}
            
            <div class="mt-4">
                <x-filament::button type="submit">
                    Enregistrer les commissions
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
