@extends('layouts.app')

@section('title', 'Annuler la réservation')

@section('content')
<div class="max-w-2xl mx-auto px-4 py-8">
    <!-- Header -->
    <div class="mb-8">
        <a href="{{ url()->previous() }}" class="inline-flex items-center text-gray-600 hover:text-gray-900 mb-4">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Retour
        </a>
        <h1 class="text-2xl font-bold text-gray-900">Annuler la réservation</h1>
    </div>

    @if(!$preview['can_cancel'])
        <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
            <div class="flex">
                <svg class="w-5 h-5 text-red-400 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <div>
                    <h3 class="text-red-800 font-medium">Annulation impossible</h3>
                    <p class="text-red-700 text-sm mt-1">Cette réservation ne peut plus être annulée.</p>
                </div>
            </div>
        </div>
    @else
        <!-- Booking Summary -->
        <div class="bg-white rounded-lg shadow-sm border p-6 mb-6">
            <h2 class="font-semibold text-gray-900 mb-4">Détails de la réservation</h2>
            
            <div class="flex items-start space-x-4">
                @if($booking->residence->mainPhoto)
                    <img loading="lazy" src="{{ storage_url($booking->residence->mainPhoto->path) }}" 
                         alt="{{ $booking->residence->name }}"
                         class="w-24 h-24 object-cover rounded-lg">
                @endif
                <div>
                    <h3 class="font-medium text-gray-900">{{ $booking->residence->title }}</h3>
                    <p class="text-gray-600 text-sm">{{ $booking->residence->commune }}, {{ $booking->residence->city }}</p>
                    <div class="mt-2 text-sm text-gray-600">
                        <p>{{ $booking->check_in->format('d M Y') }} → {{ $booking->check_out->format('d M Y') }}</p>
                        <p>{{ $booking->nights_count }} nuit(s) • {{ $booking->guests_count }} voyageur(s)</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Cancellation Policy Info -->
        @if($preview['policy'])
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                <div class="flex items-start">
                    <svg class="w-5 h-5 text-blue-500 mr-3 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <div>
                        <h3 class="text-blue-900 font-medium">Politique d'annulation : {{ $preview['policy']['name'] }}</h3>
                        <p class="text-blue-800 text-sm mt-1 whitespace-pre-line">{{ $preview['policy']['formatted_description'] }}</p>
                    </div>
                </div>
            </div>
        @endif

        <!-- Timing Info -->
        <div class="bg-gray-50 rounded-lg p-4 mb-6">
            <div class="flex items-center justify-between">
                <span class="text-gray-600">Temps avant l'arrivée</span>
                <span class="font-medium">
                    @if($preview['timing']['days_until_checkin'] > 0)
                        {{ $preview['timing']['days_until_checkin'] }} jour(s)
                    @else
                        {{ $preview['timing']['hours_until_checkin'] }} heure(s)
                    @endif
                </span>
            </div>
            @if($preview['timing']['is_free_cancellation'])
                <div class="mt-2 text-green-600 text-sm flex items-center">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    Annulation gratuite disponible
                </div>
            @endif
        </div>

        <!-- Refund Summary -->
        <div class="bg-white rounded-lg shadow-sm border p-6 mb-6">
            <h2 class="font-semibold text-gray-900 mb-4">Récapitulatif financier</h2>
            
            <div class="space-y-3">
                <div class="flex justify-between">
                    <span class="text-gray-600">Montant total payé</span>
                    <span class="font-medium">{{ number_format($preview['booking']['total_amount'], 0, ',', ' ') }} FCFA</span>
                </div>
                
                <div class="flex justify-between text-green-600">
                    <span>Remboursement ({{ $preview['amounts']['refund_percentage'] }}%)</span>
                    <span class="font-medium">{{ $preview['amounts']['formatted_refund'] }}</span>
                </div>
                
                @if($preview['amounts']['non_refundable_amount'] > 0)
                    <div class="flex justify-between text-red-600">
                        <span>Non remboursable</span>
                        <span class="font-medium">{{ $preview['amounts']['formatted_non_refundable'] }}</span>
                    </div>
                @endif

                @if(isset($preview['amounts']['penalty_amount']) && $preview['amounts']['penalty_amount'] > 0)
                    <div class="flex justify-between text-orange-600">
                        <span>Pénalité propriétaire</span>
                        <span class="font-medium">{{ number_format($preview['amounts']['penalty_amount'], 0, ',', ' ') }} FCFA</span>
                    </div>
                @endif
            </div>

            <div class="mt-4 pt-4 border-t">
                <p class="text-sm text-gray-600">{{ $preview['message'] }}</p>
            </div>
        </div>

        <!-- Cancellation Form -->
        <form action="{{ $isOwner ? route('cancellations.cancel-owner', $booking) : route('cancellations.cancel-guest', $booking) }}" 
              method="POST" 
              class="bg-white rounded-lg shadow-sm border p-6">
            @csrf
            
            <h2 class="font-semibold text-gray-900 mb-4">Raison de l'annulation</h2>

            <div class="space-y-3 mb-6">
                @foreach($reasons as $value => $label)
                    <label class="flex items-center p-3 border rounded-lg cursor-pointer hover:bg-gray-50 transition-colors">
                        <input type="radio" 
                               name="reason" 
                               value="{{ $value }}" 
                               class="w-4 h-4 text-orange-600 border-gray-300 focus:ring-orange-500"
                               required>
                        <span class="ml-3 text-gray-700">{{ $label }}</span>
                    </label>
                @endforeach
            </div>

            <div class="mb-6">
                <label for="detailed_reason" class="block text-sm font-medium text-gray-700 mb-2">
                    Détails supplémentaires (optionnel)
                </label>
                <textarea name="detailed_reason" 
                          id="detailed_reason" 
                          rows="3"
                          class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500"
                          placeholder="Expliquez-nous pourquoi vous annulez..."></textarea>
            </div>

            @if($preview['amounts']['non_refundable_amount'] > 0)
                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
                    <label class="flex items-start">
                        <input type="checkbox" 
                               required 
                               class="mt-1 w-4 h-4 text-orange-600 border-gray-300 rounded focus:ring-orange-500">
                        <span class="ml-3 text-sm text-yellow-800">
                            Je comprends que <strong>{{ $preview['amounts']['formatted_non_refundable'] }}</strong> 
                            ne seront pas remboursés selon la politique d'annulation.
                        </span>
                    </label>
                </div>
            @endif

            <div class="flex space-x-4">
                <a href="{{ url()->previous() }}" 
                   class="flex-1 px-6 py-3 border border-gray-300 text-gray-700 rounded-lg font-medium hover:bg-gray-50 transition-colors text-center">
                    Annuler
                </a>
                <button type="submit" 
                        class="flex-1 px-6 py-3 bg-red-600 text-white rounded-lg font-medium hover:bg-red-700 transition-colors">
                    Confirmer l'annulation
                </button>
            </div>
        </form>
    @endif
</div>
@endsection
