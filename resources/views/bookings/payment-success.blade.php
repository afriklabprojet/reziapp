@extends('layouts.app')

@section('title', 'Paiement confirmé - REZI')

@section('content')
<div class="min-h-screen bg-gray-50 flex items-center justify-center py-12 px-4">
    <div class="max-w-md w-full text-center">
        {{-- Icône succès --}}
        <div class="mx-auto w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mb-6">
            <svg class="w-10 h-10 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
        </div>

        <h1 class="text-2xl font-bold text-gray-900 mb-2">Paiement confirmé !</h1>

        @if($booking->status === 'confirmed')
            <p class="text-gray-600 mb-6">
                Votre réservation pour <strong>{{ $booking->residence?->title ?? $booking->residence?->name }}</strong> est confirmée.
                Vous recevrez un email de confirmation.
            </p>
        @else
            <p class="text-gray-600 mb-6">
                Votre paiement a été reçu. Le propriétaire de <strong>{{ $booking->residence?->title ?? $booking->residence?->name }}</strong> 
                a 48h pour accepter votre demande. Vous serez notifié par email.
            </p>
        @endif

        {{-- Résumé --}}
        <div class="bg-white rounded-xl border border-gray-200 p-6 text-left mb-6">
            <div class="flex items-start gap-4 mb-4">
                @if($booking->residence?->photos?->first())
                    <img src="{{ $booking->residence->photos->first()->url }}" 
                         alt="{{ $booking->residence->name }}"
                         class="w-20 h-16 object-cover rounded-lg">
                @endif
                <div>
                    <h3 class="font-semibold text-gray-900">{{ $booking->residence?->title ?? $booking->residence?->name }}</h3>
                    <p class="text-sm text-gray-500">{{ $booking->residence?->commune ?? $booking->residence?->city }}</p>
                </div>
            </div>

            <div class="space-y-2 text-sm border-t border-gray-100 pt-4">
                <div class="flex justify-between">
                    <span class="text-gray-500">Arrivée</span>
                    <span class="font-medium">{{ $booking->check_in?->translatedFormat('d M Y') }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500">Départ</span>
                    <span class="font-medium">{{ $booking->check_out?->translatedFormat('d M Y') }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500">Voyageurs</span>
                    <span class="font-medium">{{ $booking->guests }} voyageur{{ $booking->guests > 1 ? 's' : '' }}</span>
                </div>
                <div class="flex justify-between pt-2 border-t border-gray-100">
                    <span class="font-semibold text-gray-900">Total payé</span>
                    <span class="font-bold text-green-600">{{ number_format($booking->total_amount, 0, ',', ' ') }} FCFA</span>
                </div>
            </div>
        </div>

        {{-- Statut --}}
        <div class="flex items-center justify-center gap-2 mb-6 px-4 py-3 rounded-lg
            {{ $booking->status === 'confirmed' ? 'bg-green-50 text-green-700' : 'bg-amber-50 text-amber-700' }}">
            @if($booking->status === 'confirmed')
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span class="text-sm font-medium">Réservation confirmée</span>
            @else
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span class="text-sm font-medium">En attente de confirmation du propriétaire (48h max)</span>
            @endif
        </div>

        {{-- Actions --}}
        <div class="space-y-3">
            @auth
                <a href="{{ route('bookings.index') }}"
                    class="block w-full py-3 bg-linear-to-r from-[#E61E4D] to-[#D70466] text-white font-semibold rounded-xl hover:shadow-lg transition-all">
                    Voir mes réservations
                </a>
            @endauth
            <a href="{{ route('residences.index') }}"
                class="block w-full py-3 bg-gray-100 text-gray-700 font-semibold rounded-xl hover:bg-gray-200 transition-colors">
                Continuer à explorer
            </a>
        </div>

        {{-- Info email pour les invités --}}
        @if($booking->user?->is_guest)
            <p class="text-xs text-gray-500 mt-6">
                Un email de confirmation a été envoyé à <strong>{{ $booking->user->email }}</strong>.
                Vérifiez votre boîte de réception et vos spams.
            </p>
        @endif
    </div>
</div>
@endsection
