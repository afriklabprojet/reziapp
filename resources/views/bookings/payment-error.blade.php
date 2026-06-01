@extends('layouts.app')

@section('title', 'Paiement échoué - REZI')

@section('content')
<div class="min-h-screen bg-gray-50 flex items-center justify-center py-12 px-4">
    <div class="max-w-md w-full text-center">
        {{-- Icône erreur --}}
        <div class="mx-auto w-20 h-20 bg-red-100 rounded-full flex items-center justify-center mb-6">
            <svg class="w-10 h-10 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </div>

        <h1 class="text-2xl font-bold text-gray-900 mb-2">Paiement échoué</h1>
        <p class="text-gray-600 mb-8">
            Le paiement pour votre réservation n'a pas pu être traité. 
            Votre réservation est sauvegardée — vous pouvez réessayer.
        </p>

        {{-- Résumé --}}
        @if($booking->residence)
        <div class="bg-white rounded-xl border border-gray-200 p-6 text-left mb-6">
            <div class="flex items-start gap-4">
                @if($booking->residence->photos?->first())
                    <img src="{{ $booking->residence->photos->first()->url }}" 
                         alt="{{ $booking->residence->name }}"
                         class="w-20 h-16 object-cover rounded-lg">
                @endif
                <div>
                    <h3 class="font-semibold text-gray-900">{{ $booking->residence->title ?? $booking->residence->name }}</h3>
                    <p class="text-sm text-gray-500">
                        {{ $booking->check_in?->translatedFormat('d M') }} – {{ $booking->check_out?->translatedFormat('d M Y') }}
                    </p>
                    <p class="text-sm font-semibold text-gray-900 mt-1">
                        {{ number_format($booking->total_amount, 0, ',', ' ') }} FCFA
                    </p>
                </div>
            </div>
        </div>
        @endif

        {{-- Actions --}}
        <div class="space-y-3">
            <a href="{{ route('bookings.create', $booking->residence_id) }}?check_in={{ $booking->check_in?->format('Y-m-d') }}&check_out={{ $booking->check_out?->format('Y-m-d') }}&guests={{ $booking->guests }}"
                class="block w-full py-3 bg-linear-to-r from-[#F16A00] to-[#CC5A00] text-white font-semibold rounded-xl hover:shadow-lg transition-all">
                Réessayer le paiement
            </a>
            <a href="{{ route('residences.show', $booking->residence_id) }}"
                class="block w-full py-3 bg-gray-100 text-gray-700 font-semibold rounded-xl hover:bg-gray-200 transition-colors">
                Retour à la résidence
            </a>
        </div>

        <p class="text-xs text-gray-400 mt-6">
            Si le problème persiste, contactez notre support à 
            <a href="mailto:support@reziapp.ci" class="text-pink-600 hover:underline">support@reziapp.ci</a>
        </p>
    </div>
</div>
@endsection
