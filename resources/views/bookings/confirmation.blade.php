@extends('layouts.app')

@section('title', 'Réservation confirmée - REZI')

@section('content')
<div class="min-h-screen bg-gray-50 flex items-center justify-center py-12 px-4">
    <div class="max-w-md w-full">
        {{-- Icône succès animée --}}
        <div class="text-center mb-8">
            <div class="mx-auto w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mb-6 animate-bounce-once">
                <svg class="w-10 h-10 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
            </div>

            <h1 class="text-2xl font-bold text-gray-900 mb-2">Réservation envoyée !</h1>

            @if($booking->status === 'confirmed')
                <p class="text-gray-600">
                    Votre réservation pour <strong>{{ $booking->residence?->name }}</strong> est confirmée.
                    Le propriétaire a été notifié.
                </p>
            @else
                <p class="text-gray-600">
                    Votre demande de réservation pour <strong>{{ $booking->residence?->name }}</strong> 
                    a été envoyée au propriétaire. Il a <strong>48h</strong> pour confirmer.
                </p>
            @endif
        </div>

        {{-- Carte résumé --}}
        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden mb-6">
            {{-- Photo + infos résidence --}}
            <div class="flex gap-4 p-5 border-b border-gray-100">
                @if($booking->residence?->photos?->first())
                    <img src="{{ $booking->residence->photos->first()->url }}" 
                         alt="{{ $booking->residence->name }}"
                         class="w-24 h-20 object-cover rounded-xl">
                @endif
                <div class="flex-1 min-w-0">
                    <h3 class="font-semibold text-gray-900 truncate">{{ $booking->residence?->name }}</h3>
                    <p class="text-sm text-gray-500 mt-0.5">{{ $booking->residence?->commune }}, {{ $booking->residence?->city }}</p>
                    @if($booking->residence?->owner)
                        <p class="text-xs text-gray-400 mt-1">Hôte : {{ $booking->residence->owner->name }}</p>
                    @endif
                </div>
            </div>

            {{-- Détails réservation --}}
            <div class="p-5 space-y-3 text-sm">
                <div class="flex justify-between items-center">
                    <span class="text-gray-500 flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        Arrivée
                    </span>
                    <span class="font-medium text-gray-900">{{ $booking->check_in?->translatedFormat('D d M Y') }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-gray-500 flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        Départ
                    </span>
                    <span class="font-medium text-gray-900">{{ $booking->check_out?->translatedFormat('D d M Y') }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-gray-500 flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        Voyageurs
                    </span>
                    <span class="font-medium text-gray-900">{{ $booking->guests }} voyageur{{ $booking->guests > 1 ? 's' : '' }}</span>
                </div>
                
                @php
                    $nights = $booking->check_in && $booking->check_out 
                        ? $booking->check_in->diffInDays($booking->check_out) 
                        : 0;
                @endphp
                @if($nights > 0)
                <div class="flex justify-between items-center">
                    <span class="text-gray-500 flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
                        </svg>
                        Durée
                    </span>
                    <span class="font-medium text-gray-900">{{ $nights }} nuit{{ $nights > 1 ? 's' : '' }}</span>
                </div>
                @endif

                @if($booking->total_amount > 0)
                <div class="flex justify-between items-center pt-3 border-t border-gray-100">
                    <span class="font-semibold text-gray-900">Total</span>
                    <span class="font-bold text-lg text-gray-900">{{ number_format($booking->total_amount, 0, ',', ' ') }} FCFA</span>
                </div>
                @endif
            </div>
        </div>

        {{-- Statut --}}
        <div class="flex items-center gap-3 mb-6 px-5 py-4 rounded-xl
            {{ $booking->status === 'confirmed' ? 'bg-green-50 border border-green-200' : 'bg-amber-50 border border-amber-200' }}">
            @if($booking->status === 'confirmed')
                <svg class="w-6 h-6 text-green-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <div>
                    <p class="font-semibold text-green-800 text-sm">Réservation confirmée</p>
                    <p class="text-green-700 text-xs mt-0.5">Le propriétaire vous contactera avec les détails d'accès.</p>
                </div>
            @else
                <svg class="w-6 h-6 text-amber-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <div>
                    <p class="font-semibold text-amber-800 text-sm">En attente de confirmation</p>
                    <p class="text-amber-700 text-xs mt-0.5">Le propriétaire a 48h pour accepter. Vous serez notifié par email.</p>
                </div>
            @endif
        </div>

        {{-- Prochaines étapes --}}
        <div class="bg-white rounded-xl border border-gray-200 p-5 mb-6">
            <h3 class="font-semibold text-gray-900 mb-3 text-sm">Prochaines étapes</h3>
            <div class="space-y-3">
                <div class="flex items-start gap-3">
                    <span class="flex items-center justify-center w-6 h-6 rounded-full bg-gray-900 text-white text-xs font-bold shrink-0">1</span>
                    <p class="text-sm text-gray-600">Le propriétaire examine votre demande et vous répond sous 48h.</p>
                </div>
                <div class="flex items-start gap-3">
                    <span class="flex items-center justify-center w-6 h-6 rounded-full bg-gray-200 text-gray-600 text-xs font-bold shrink-0">2</span>
                    <p class="text-sm text-gray-600">Une fois confirmée, vous recevrez les informations de contact et l'adresse exacte.</p>
                </div>
                <div class="flex items-start gap-3">
                    <span class="flex items-center justify-center w-6 h-6 rounded-full bg-gray-200 text-gray-600 text-xs font-bold shrink-0">3</span>
                    <p class="text-sm text-gray-600">Le paiement se fait directement avec le propriétaire le jour de votre arrivée.</p>
                </div>
            </div>
        </div>

        {{-- Contact propriétaire --}}
        @if($booking->residence?->owner?->phone)
        <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 mb-6">
            <div class="flex items-center gap-3">
                <svg class="w-5 h-5 text-blue-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                </svg>
                <div>
                    <p class="text-sm font-medium text-blue-900">Contacter le propriétaire</p>
                    <a href="tel:{{ $booking->residence->owner->phone }}" class="text-blue-700 text-sm font-semibold hover:underline">
                        {{ $booking->residence->owner->phone }}
                    </a>
                </div>
            </div>
        </div>
        @endif

        {{-- Actions --}}
        <div class="space-y-3">
            @auth
                <a href="{{ route('bookings.index') }}"
                    class="block w-full py-3.5 text-center bg-linear-to-r from-[#F16A00] to-[#CC5A00] text-white font-semibold rounded-xl hover:shadow-lg transition-all">
                    Voir mes réservations
                </a>
            @endauth
            <a href="{{ route('residences.show', $booking->residence_id) }}"
                class="block w-full py-3.5 text-center bg-white border border-gray-300 text-gray-700 font-semibold rounded-xl hover:bg-gray-50 transition-colors">
                Retour à la résidence
            </a>
            <a href="{{ route('residences.index') }}"
                class="block w-full py-3.5 text-center text-gray-500 font-medium text-sm hover:text-gray-700 transition-colors">
                Continuer à explorer
            </a>
        </div>

        {{-- Info email pour les invités --}}
        @if($booking->user?->is_guest)
            <p class="text-xs text-gray-400 mt-6 text-center">
                Un email de confirmation a été envoyé à <strong>{{ $booking->user->email }}</strong>.
                Vérifiez votre boîte de réception et vos spams.
            </p>
        @endif
    </div>
</div>

<style>
    @keyframes bounceOnce {
        0% { transform: scale(0); opacity: 0; }
        50% { transform: scale(1.2); }
        70% { transform: scale(0.9); }
        100% { transform: scale(1); opacity: 1; }
    }
    .animate-bounce-once {
        animation: bounceOnce 0.6s ease-out;
    }
</style>
@endsection
