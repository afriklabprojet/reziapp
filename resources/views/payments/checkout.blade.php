@extends('layouts.app')

@section('title', 'Paiement - ' . $booking->residence->title)

@section('content')
<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">

        <!-- En-tête -->
        <div class="mb-8">
            <a href="{{ route('bookings.show', $booking) }}" class="inline-flex items-center text-sm text-gray-500 hover:text-gray-700">
                <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
                Retour à la réservation
            </a>
            <h1 class="mt-4 text-2xl font-bold text-gray-900">Finaliser votre paiement</h1>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

            <!-- Formulaire de paiement -->
            <div class="lg:col-span-2 order-2 lg:order-1">
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">

                    <!-- Méthodes de paiement -->
                    <div class="p-6 border-b border-gray-200">
                        <h2 class="text-lg font-semibold text-gray-900 mb-4">Choisissez votre mode de paiement</h2>

                        <!-- Opérateurs Mobile Money -->
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3" x-data="{ selected: 'orange_money' }">
                            @foreach($operators as $code => $operator)
                            <label class="relative cursor-pointer">
                                <input type="radio" name="operator" value="{{ $code }}"
                                       x-model="selected"
                                       class="sr-only peer">
                                <div class="flex flex-col items-center p-4 border-2 rounded-lg peer-checked:border-[#F16A00] peer-checked:bg-[#FFF4EB] hover:bg-gray-50 transition-colors">
                                    <img loading="lazy" src="{{ $operator['logo'] }}" alt="{{ $operator['name'] }}" class="h-10 w-auto mb-2">
                                    <span class="text-xs font-medium text-gray-700">{{ $operator['name'] }}</span>
                                </div>
                            </label>
                            @endforeach
                        </div>
                    </div>

                    <!-- Formulaire -->
                    <form id="payment-form" class="p-6 space-y-6" x-data="paymentForm({{ \Illuminate\Support\Js::encode([
                        'phone' => Auth::user()->phone ?? '',
                        'initiateUrl' => route('payments.initiate', $booking),
                        'returnUrl' => route('payments.return', ':uuid'),
                        'csrfToken' => csrf_token(),
                    ]) }})">

                        <!-- Numéro de téléphone -->
                        <div>
                            <label for="phone_number" class="block text-sm font-medium text-gray-700 mb-2">
                                Numéro de téléphone Mobile Money
                            </label>
                            <div class="flex">
                                <span class="inline-flex items-center px-4 rounded-l-lg border border-r-0 border-gray-300 bg-gray-50 text-gray-500 text-sm">
                                    +225
                                </span>
                                <input type="tel"
                                       id="phone_number"
                                       name="phone_number"
                                       x-model="phoneNumber"
                                       placeholder="07 00 00 00 00"
                                       inputmode="numeric"
                                       class="flex-1 block w-full rounded-none rounded-r-lg border-gray-300 focus:ring-[#F16A00] focus:border-[#F16A00] py-3"
                                       maxlength="10"
                                       required>
                            </div>
                            <p class="mt-1 text-xs text-gray-500">Entrez le numéro associé à votre compte Mobile Money</p>
                        </div>

                        <!-- Méthodes sauvegardées -->
                        @if($savedMethods->count() > 0)
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Ou utilisez une méthode enregistrée
                            </label>
                            <div class="space-y-2">
                                @foreach($savedMethods as $method)
                                <label class="flex items-center p-3 border rounded-lg cursor-pointer hover:bg-gray-50">
                                    <input type="radio" name="saved_method" value="{{ $method->id }}"
                                           @change="phoneNumber = '{{ $method->phone_number }}'"
                                           class="text-[#CC5A00] focus:ring-[#F16A00]">
                                    <div class="ml-3 flex items-center">
                                        <img loading="lazy" src="{{ $method->provider->logo ?? '/images/payment/mobile.png' }}"
                                             alt="{{ $method->provider->name ?? 'Mobile Money' }}"
                                             class="h-6 w-auto mr-2">
                                        <span class="text-sm text-gray-900">{{ $method->display_name }}</span>
                                        @if($method->is_default)
                                        <span class="ml-2 px-2 py-0.5 text-xs bg-[#FFE7D1] text-[#A34700] rounded-full">Par défaut</span>
                                        @endif
                                    </div>
                                </label>
                                @endforeach
                            </div>
                        </div>
                        @endif

                        <!-- Sauvegarder la méthode -->
                        <div class="flex items-center">
                            <input type="checkbox" id="save_method" name="save_method" x-model="saveMethod"
                                   class="h-4 w-4 text-[#CC5A00] focus:ring-[#F16A00] border-gray-300 rounded">
                            <label for="save_method" class="ml-2 text-sm text-gray-600">
                                Enregistrer cette méthode pour mes prochains paiements
                            </label>
                        </div>

                        <!-- Bouton de paiement -->
                        <button type="submit"
                                class="w-full flex items-center justify-center px-6 py-4 bg-[#CC5A00] text-white font-semibold rounded-lg hover:bg-[#A34700] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#F16A00] disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                                :disabled="loading"
                                @click.prevent="initiatePayment">
                            <span x-show="!loading">
                                Payer {{ number_format($booking->total_amount, 0, ',', ' ') }} FCFA
                            </span>
                            <span x-show="loading" class="flex items-center">
                                <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Traitement en cours...
                            </span>
                        </button>

                        <!-- Message d'erreur -->
                        <div x-show="error" x-cloak
                             class="p-4 bg-red-50 border border-red-200 rounded-lg">
                            <p class="text-sm text-red-700" x-text="error"></p>
                        </div>
                    </form>

                    <!-- Modal OTP -->
                    <div x-show="showOtpModal" x-cloak
                         class="fixed inset-0 z-50 overflow-y-auto"
                         role="dialog" aria-modal="true" aria-label="Confirmation de paiement"
                         x-transition:enter="ease-out duration-300"
                         x-transition:enter-start="opacity-0"
                         x-transition:enter-end="opacity-100"
                         x-transition:leave="ease-in duration-200"
                         x-transition:leave-start="opacity-100"
                         x-transition:leave-end="opacity-0">
                        <div class="flex items-center justify-center min-h-screen px-4">
                            <div class="fixed inset-0 bg-gray-500/75" @click="showOtpModal = false"></div>

                            <div class="relative bg-white rounded-xl shadow-xl max-w-md w-full p-6 z-10">
                                <div class="text-center">
                                    <div class="mx-auto w-16 h-16 bg-[#FFE7D1] rounded-full flex items-center justify-center mb-4">
                                        <svg class="w-8 h-8 text-[#CC5A00]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                        </svg>
                                    </div>
                                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Confirmation de paiement</h3>
                                    <p class="text-sm text-gray-600 mb-6">
                                        Un code de confirmation a été envoyé au numéro <strong x-text="phoneNumber"></strong>
                                    </p>
                                </div>

                                <form @submit.prevent="verifyOtp">
                                    <div class="mb-6">
                                        <label for="otp" class="block text-sm font-medium text-gray-700 mb-2">Code de confirmation</label>
                                        <input type="text"
                                               id="otp"
                                               x-model="otp"
                                               placeholder="000000"
                                               inputmode="numeric"
                                               pattern="[0-9]*"
                                               class="block w-full text-center text-2xl tracking-widest border-gray-300 rounded-lg focus:ring-[#F16A00] focus:border-[#F16A00]"
                                               maxlength="6"
                                               autocomplete="one-time-code"
                                               required>
                                    </div>

                                    <button type="submit"
                                            class="w-full py-3 bg-[#CC5A00] text-white font-semibold rounded-lg hover:bg-[#A34700] disabled:opacity-50"
                                            :disabled="loading || otp.length !== 6">
                                        <span x-show="!loading">Confirmer le paiement</span>
                                        <span x-show="loading">Vérification...</span>
                                    </button>

                                    <p x-show="otpError" class="mt-4 text-sm text-red-600 text-center" x-text="otpError"></p>
                                </form>

                                <div class="mt-4 text-center">
                                    <button type="button" @click="showOtpModal = false" class="text-sm text-gray-500 hover:text-gray-700">
                                        Annuler
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- Sécurité -->
                <div class="mt-6 flex items-center justify-center space-x-6 text-sm text-gray-500">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 mr-1 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M2.166 4.999A11.954 11.954 0 0010 1.944 11.954 11.954 0 0017.834 5c.11.65.166 1.32.166 2.001 0 5.225-3.34 9.67-8 11.317C5.34 16.67 2 12.225 2 7c0-.682.057-1.35.166-2.001zm11.541 3.708a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        Paiement sécurisé
                    </div>
                    <div class="flex items-center">
                        <svg class="w-5 h-5 mr-1 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/>
                        </svg>
                        Données chiffrées
                    </div>
                </div>
            </div>

            <!-- Récapitulatif — affiché en premier sur mobile -->
            <div class="lg:col-span-1 order-1 lg:order-2">
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 lg:sticky lg:top-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Récapitulatif</h3>

                    <!-- Résidence -->
                    <div class="flex items-start space-x-4 pb-4 border-b border-gray-200">
                        @if($booking->residence->photos->first())
                        <img loading="lazy" src="{{ $booking->residence->photos->first()?->url }}"
                             alt="{{ $booking->residence->name }}"
                             class="w-20 h-20 object-cover rounded-lg">
                        @endif
                        <div>
                            <h4 class="font-medium text-gray-900">{{ $booking->residence->title }}</h4>
                            <p class="text-sm text-gray-500">{{ $booking->residence->city }}</p>
                        </div>
                    </div>

                    <!-- Dates -->
                    <div class="py-4 border-b border-gray-200">
                        <div class="flex justify-between text-sm mb-2">
                            <span class="text-gray-600">Arrivée</span>
                            <span class="font-medium">{{ $booking->check_in->format('d M Y') }}</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600">Départ</span>
                            <span class="font-medium">{{ $booking->check_out->format('d M Y') }}</span>
                        </div>
                    </div>

                    <!-- Détails du prix -->
                    <div class="py-4 border-b border-gray-200 space-y-2">
                        @php
                            $nights = $booking->check_in->diffInDays($booking->check_out);
                            $pricePerNight = $booking->price_per_night ?? ($booking->total_amount / max($nights, 1));
                        @endphp
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600">{{ number_format($pricePerNight, 0, ',', ' ') }} FCFA × {{ $nights }} nuit(s)</span>
                            <span>{{ number_format($pricePerNight * $nights, 0, ',', ' ') }} FCFA</span>
                        </div>
                        @if(($booking->cleaning_fee ?? 0) > 0)
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600">Frais de ménage</span>
                            <span>{{ number_format($booking->cleaning_fee, 0, ',', ' ') }} FCFA</span>
                        </div>
                        @endif
                        @if(($booking->service_fee ?? 0) > 0)
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600">Frais de service</span>
                            <span>{{ number_format($booking->service_fee, 0, ',', ' ') }} FCFA</span>
                        </div>
                        @endif
                    </div>

                    <!-- Total -->
                    <div class="pt-4">
                        <div class="flex justify-between items-center">
                            <span class="text-lg font-semibold text-gray-900">Total</span>
                            <span class="text-xl font-bold text-[#CC5A00]">{{ number_format($booking->total_amount, 0, ',', ' ') }} FCFA</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
</script>
@endsection
