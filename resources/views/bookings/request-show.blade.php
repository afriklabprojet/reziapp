@extends('layouts.app')

@section('title', 'Demande de réservation #' . $bookingRequest->id)

@section('content')
    @php
        $residence = $bookingRequest->residence;
        $owner = $residence->owner;
        $photo = $residence->photos->first();
        $statusColors = [
            'pending' => ['bg' => 'bg-yellow-50', 'text' => 'text-yellow-800', 'border' => 'border-yellow-200', 'dot' => 'bg-yellow-400', 'label' => 'En attente'],
            'approved' => ['bg' => 'bg-green-50', 'text' => 'text-green-800', 'border' => 'border-green-200', 'dot' => 'bg-green-400', 'label' => 'Approuvée'],
            'rejected' => ['bg' => 'bg-red-50', 'text' => 'text-red-800', 'border' => 'border-red-200', 'dot' => 'bg-red-400', 'label' => 'Refusée'],
            'expired' => ['bg' => 'bg-gray-50', 'text' => 'text-gray-600', 'border' => 'border-gray-200', 'dot' => 'bg-gray-400', 'label' => 'Expirée'],
            'converted' => ['bg' => 'bg-blue-50', 'text' => 'text-blue-800', 'border' => 'border-blue-200', 'dot' => 'bg-blue-400', 'label' => 'Convertie en réservation'],
        ];
        $s = $statusColors[$bookingRequest->status] ?? $statusColors['pending'];
    @endphp

    <div class="min-h-screen bg-gray-50 py-8">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">

            {{-- Header --}}
            <div class="mb-6">
                <a href="{{ route('bookings.index') }}"
                   class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-700 mb-4">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                    Mes réservations
                </a>
                <h1 class="text-2xl font-semibold text-gray-900">Demande de réservation</h1>
            </div>

            {{-- Statut --}}
            <div class="rounded-xl border {{ $s['border'] }} {{ $s['bg'] }} p-5 mb-6">
                <div class="flex items-center gap-3">
                    <span class="w-3 h-3 rounded-full {{ $s['dot'] }} shrink-0"></span>
                    <div>
                        <p class="font-semibold {{ $s['text'] }}">{{ $s['label'] }}</p>
                        @if ($bookingRequest->status === 'pending' && $bookingRequest->expires_at)
                            @php
                                $remaining = now()->diff($bookingRequest->expires_at);
                                $hoursLeft = $remaining->h + ($remaining->days * 24);
                            @endphp
                            @if ($bookingRequest->expires_at->isFuture())
                                <p class="text-sm {{ $s['text'] }} opacity-75 mt-0.5">
                                    Le propriétaire a encore {{ $hoursLeft }}h pour répondre
                                </p>
                            @else
                                <p class="text-sm text-gray-500 mt-0.5">La demande a expiré</p>
                            @endif
                        @endif
                        @if ($bookingRequest->status === 'rejected' && $bookingRequest->rejected_reason)
                            <p class="text-sm mt-1">Raison : {{ $bookingRequest->rejected_reason }}</p>
                        @endif
                        @if ($bookingRequest->status === 'approved' && $bookingRequest->owner_response)
                            <p class="text-sm mt-1">{{ $bookingRequest->owner_response }}</p>
                        @endif
                    </div>
                </div>

                {{-- CTA si approuvée → payer --}}
                @if ($bookingRequest->status === 'approved' && $bookingRequest->booking)
                    <div class="mt-4">
                        <a href="{{ route('payments.checkout', $bookingRequest->booking) }}"
                           class="inline-flex items-center gap-2 px-5 py-2.5 bg-linear-to-r from-[#F16A00] to-[#CC5A00] text-white font-semibold rounded-xl hover:from-[#B85100] hover:to-[#A34700] transition-all shadow-sm">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                            </svg>
                            Procéder au paiement
                        </a>
                    </div>
                @endif

                {{-- Lien si convertie --}}
                @if ($bookingRequest->status === 'converted' && $bookingRequest->booking)
                    <div class="mt-4">
                        <a href="{{ route('bookings.show', $bookingRequest->booking) }}"
                           class="inline-flex items-center gap-2 text-sm font-medium text-blue-700 underline hover:text-blue-900">
                            Voir la réservation
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </a>
                    </div>
                @endif
            </div>

            {{-- Résidence --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden mb-6">
                <div class="flex gap-4 p-5">
                    @if ($photo)
                        <img src="{{ $photo->url }}"
                             alt="{{ $residence->title }}"
                             class="w-28 h-20 rounded-lg object-cover shrink-0">
                    @endif
                    <div class="flex-1 min-w-0">
                        <h2 class="font-semibold text-gray-900 line-clamp-1">{{ $residence->title }}</h2>
                        <p class="text-sm text-gray-500 mt-0.5">{{ $residence->commune ?? $residence->city }}</p>
                        @if ($owner)
                            <div class="flex items-center gap-2 mt-2">
                                <img src="{{ $owner->getAvatarUrl() }}"
                                     alt="{{ $owner->name }}"
                                     class="w-6 h-6 rounded-full object-cover">
                                <span class="text-sm text-gray-600">{{ $owner->name }}</span>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Détails du séjour --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 mb-6">
                <h3 class="font-semibold text-gray-900 mb-4">Détails du séjour</h3>
                <div class="grid grid-cols-2 gap-y-4 gap-x-8 text-sm">
                    <div>
                        <p class="text-gray-500">Arrivée</p>
                        <p class="font-medium text-gray-900">{{ $bookingRequest->check_in->translatedFormat('D j M Y') }}</p>
                    </div>
                    <div>
                        <p class="text-gray-500">Départ</p>
                        <p class="font-medium text-gray-900">{{ $bookingRequest->check_out->translatedFormat('D j M Y') }}</p>
                    </div>
                    <div>
                        <p class="text-gray-500">Durée</p>
                        <p class="font-medium text-gray-900">{{ $bookingRequest->total_nights ?? $bookingRequest->check_in->diffInDays($bookingRequest->check_out) }} nuit(s)</p>
                    </div>
                    <div>
                        <p class="text-gray-500">Voyageurs</p>
                        <p class="font-medium text-gray-900">
                            {{ ($bookingRequest->adults ?? 0) + ($bookingRequest->children ?? 0) }} voyageur(s)
                            @if (($bookingRequest->infants ?? 0) > 0)
                                , {{ $bookingRequest->infants }} bébé(s)
                            @endif
                        </p>
                    </div>
                </div>

                @if ($bookingRequest->message)
                    <div class="mt-5 pt-5 border-t border-gray-100">
                        <p class="text-gray-500 text-sm mb-1">Votre message</p>
                        <p class="text-sm text-gray-700">{{ $bookingRequest->message }}</p>
                    </div>
                @endif

                @if ($bookingRequest->special_requests && count($bookingRequest->special_requests) > 0)
                    <div class="mt-4 pt-4 border-t border-gray-100">
                        <p class="text-gray-500 text-sm mb-2">Demandes spéciales</p>
                        <ul class="list-disc list-inside text-sm text-gray-700 space-y-1">
                            @foreach ($bookingRequest->special_requests as $req)
                                <li>{{ $req }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>

            {{-- Détail du prix --}}
            @if ($bookingRequest->total_amount && $bookingRequest->total_amount > 0)
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 mb-6">
                    <h3 class="font-semibold text-gray-900 mb-4">Détail du prix</h3>
                    <div class="space-y-3 text-sm">
                        @if ($bookingRequest->price_per_night && $bookingRequest->total_nights)
                            <div class="flex justify-between">
                                <span class="text-gray-600">{{ number_format($bookingRequest->price_per_night, 0, ',', ' ') }} FCFA × {{ $bookingRequest->total_nights }} nuit(s)</span>
                                <span>{{ number_format($bookingRequest->subtotal, 0, ',', ' ') }} FCFA</span>
                            </div>
                        @endif

                        @if (($bookingRequest->cleaning_fee ?? 0) > 0)
                            <div class="flex justify-between">
                                <span class="text-gray-600">Frais de ménage</span>
                                <span>{{ number_format($bookingRequest->cleaning_fee, 0, ',', ' ') }} FCFA</span>
                            </div>
                        @endif

                        @if (($bookingRequest->service_fee ?? 0) > 0)
                            <div class="flex justify-between">
                                <span class="text-gray-600">Frais de service</span>
                                <span>{{ number_format($bookingRequest->service_fee, 0, ',', ' ') }} FCFA</span>
                            </div>
                        @endif

                        @if (($bookingRequest->long_stay_discount ?? 0) > 0)
                            <div class="flex justify-between text-green-600">
                                <span>Réduction long séjour</span>
                                <span>-{{ number_format($bookingRequest->long_stay_discount, 0, ',', ' ') }} FCFA</span>
                            </div>
                        @endif

                        @if (($bookingRequest->promo_discount ?? 0) > 0)
                            <div class="flex justify-between text-green-600">
                                <span>Code promo</span>
                                <span>-{{ number_format($bookingRequest->promo_discount, 0, ',', ' ') }} FCFA</span>
                            </div>
                        @endif

                        <div class="flex justify-between items-center pt-3 mt-3 border-t border-gray-200 font-semibold text-base">
                            <span>Total</span>
                            <span>{{ number_format($bookingRequest->total_amount, 0, ',', ' ') }} FCFA</span>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Infos --}}
            <div class="text-center text-xs text-gray-400 mt-8">
                <p>Demande envoyée le {{ $bookingRequest->created_at->translatedFormat('j F Y à H:i') }}</p>
                @if ($bookingRequest->responded_at)
                    <p class="mt-1">Réponse reçue le {{ $bookingRequest->responded_at->translatedFormat('j F Y à H:i') }}</p>
                @endif
            </div>
        </div>
    </div>
@endsection
