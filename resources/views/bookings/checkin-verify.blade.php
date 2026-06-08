@extends('layouts.app')

@section('title', 'Vérification Check-in')

@section('content')
<div class="min-h-screen bg-gray-50 flex items-center justify-center py-12 px-4">
    <div class="max-w-sm w-full">

        @if (session('success'))
            <div class="mb-6 px-4 py-3 rounded-xl bg-green-50 border border-green-200 text-green-700 text-sm">
                {{ session('success') }}
            </div>
        @endif

        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">

            {{-- Status header --}}
            <div class="px-6 py-5 border-b border-gray-100 {{ $checkin->status === 'confirmed' ? 'bg-green-50' : 'bg-yellow-50' }}">
                <p class="font-semibold text-lg {{ $checkin->status === 'confirmed' ? 'text-green-800' : 'text-yellow-800' }}">
                    {{ $checkin->status === 'confirmed' ? '✓ Check-in confirmé' : 'Check-in en attente' }}
                </p>
                @if ($checkin->confirmed_at)
                    <p class="text-sm text-green-600 mt-0.5">
                        Confirmé le {{ $checkin->confirmed_at->translatedFormat('d M Y à H:i') }}
                    </p>
                @endif
            </div>

            {{-- Guest info --}}
            <div class="px-6 py-5 space-y-4">
                <div>
                    <p class="text-xs text-gray-400 uppercase tracking-wide mb-1">Locataire</p>
                    <p class="font-semibold text-gray-900">{{ $checkin->booking?->user?->name }}</p>
                    <p class="text-sm text-gray-500">{{ $checkin->booking?->user?->email }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-400 uppercase tracking-wide mb-1">Résidence</p>
                    <p class="font-semibold text-gray-900">{{ $checkin->booking?->residence?->name }}</p>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-xs text-gray-400 uppercase tracking-wide mb-1">Arrivée</p>
                        <p class="font-medium text-gray-900">
                            {{ $checkin->booking?->check_in?->format('d/m/Y') }}
                        </p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400 uppercase tracking-wide mb-1">Départ</p>
                        <p class="font-medium text-gray-900">
                            {{ $checkin->booking?->check_out?->format('d/m/Y') }}
                        </p>
                    </div>
                </div>
            </div>

            {{-- Confirm button --}}
            @if ($checkin->status !== 'confirmed')
                @auth
                    <div class="px-6 pb-6">
                        <form method="POST" action="{{ route('checkin.confirm', $checkin->qr_token) }}">
                            @csrf
                            <button type="submit"
                                class="w-full py-3 px-4 rounded-xl bg-gray-900 text-white font-semibold hover:bg-gray-800 transition-colors">
                                Confirmer le check-in
                            </button>
                        </form>
                    </div>
                @else
                    <div class="px-6 pb-6">
                        <a href="{{ route('login') }}"
                           class="block w-full text-center py-3 px-4 rounded-xl bg-gray-900 text-white font-semibold hover:bg-gray-800 transition-colors">
                            Se connecter pour confirmer
                        </a>
                    </div>
                @endauth
            @endif

        </div>
    </div>
</div>
@endsection
