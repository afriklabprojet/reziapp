@extends('layouts.app')

@section('title', 'Check-in — ' . ($booking->residence?->name ?? 'Réservation'))

@section('content')
<div class="min-h-screen bg-gray-50 flex items-center justify-center py-12 px-4">
    <div class="max-w-sm w-full">

        {{-- Header --}}
        <div class="text-center mb-8">
            <h1 class="text-2xl font-bold text-gray-900">Votre QR Check-in</h1>
            <p class="text-gray-500 mt-2">Présentez ce code au propriétaire à votre arrivée</p>
        </div>

        {{-- Status badge --}}
        <div class="text-center mb-6">
            @if ($checkin->status === 'confirmed')
                <span class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-green-100 text-green-700 font-medium">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                    </svg>
                    Check-in confirmé
                </span>
            @else
                <span class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-yellow-100 text-yellow-700 font-medium">
                    En attente de confirmation
                </span>
            @endif
        </div>

        {{-- QR Code --}}
        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-8 text-center mb-6">
            <div class="mx-auto w-56 h-56 flex items-center justify-center">
                <img
                    src="https://api.qrserver.com/v1/create-qr-code/?size=220x220&data={{ urlencode(route('checkin.verify', $checkin->qr_token)) }}"
                    alt="QR Code Check-in"
                    class="w-full h-full"
                >
            </div>
        </div>

        {{-- Résidence info --}}
        <div class="bg-white rounded-xl border border-gray-200 p-4 flex items-center gap-4">
            @if ($booking->residence?->photos?->first())
                <img src="{{ $booking->residence->photos->first()->url }}"
                     alt="{{ $booking->residence->name }}"
                     class="w-16 h-12 object-cover rounded-lg shrink-0">
            @endif
            <div class="min-w-0">
                <p class="font-semibold text-gray-900 truncate">{{ $booking->residence?->name }}</p>
                <p class="text-sm text-gray-500">
                    Arrivée : {{ \Carbon\Carbon::parse($booking->check_in)->translatedFormat('D d M Y') }}
                </p>
            </div>
        </div>

        {{-- Back --}}
        <div class="mt-6 text-center">
            <a href="{{ route('bookings.show', $booking) }}"
               class="text-sm text-gray-500 underline hover:text-gray-700">
                Retour à la réservation
            </a>
        </div>

    </div>
</div>
@endsection
