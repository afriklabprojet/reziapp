@extends('layouts.owner')

@section('title', 'Score de ' . ($guestScore->user?->name ?? 'Voyageur') . ' — Rezi App')

@section('owner-content')
<div class="space-y-6">
    <div>
        <a href="{{ route('owner.screening.index') }}" class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-700 mb-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" /></svg>
            Scores
        </a>
        <h1 class="text-2xl font-bold text-gray-900">{{ $guestScore->user?->name ?? 'Voyageur inconnu' }}</h1>
        <p class="text-sm text-gray-500 mt-1">{{ $guestScore->user?->email }}</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            {{-- Score Breakdown --}}
            <div class="bg-white rounded-2xl border border-gray-100 p-6">
                <h2 class="text-lg font-bold text-gray-900 mb-4">Détail du score</h2>
                <div class="space-y-4">
                    <div>
                        <div class="flex items-center justify-between text-sm mb-1">
                            <span class="text-gray-600">Identité vérifiée</span>
                            <span class="font-semibold text-gray-900">{{ $guestScore->identity_score }}/25</span>
                        </div>
                        <div class="h-2 bg-gray-100 rounded-full overflow-hidden">
                            <div class="h-full bg-blue-500 rounded-full" style="width: {{ ($guestScore->identity_score / 25) * 100 }}%"></div>
                        </div>
                    </div>
                    <div>
                        <div class="flex items-center justify-between text-sm mb-1">
                            <span class="text-gray-600">Historique réservations</span>
                            <span class="font-semibold text-gray-900">{{ $guestScore->booking_score }}/25</span>
                        </div>
                        <div class="h-2 bg-gray-100 rounded-full overflow-hidden">
                            <div class="h-full bg-green-500 rounded-full" style="width: {{ ($guestScore->booking_score / 25) * 100 }}%"></div>
                        </div>
                    </div>
                    <div>
                        <div class="flex items-center justify-between text-sm mb-1">
                            <span class="text-gray-600">Avis reçus</span>
                            <span class="font-semibold text-gray-900">{{ $guestScore->review_score }}/25</span>
                        </div>
                        <div class="h-2 bg-gray-100 rounded-full overflow-hidden">
                            <div class="h-full bg-amber-500 rounded-full" style="width: {{ ($guestScore->review_score / 25) * 100 }}%"></div>
                        </div>
                    </div>
                    <div>
                        <div class="flex items-center justify-between text-sm mb-1">
                            <span class="text-gray-600">Ancienneté</span>
                            <span class="font-semibold text-gray-900">{{ $guestScore->seniority_score }}/25</span>
                        </div>
                        <div class="h-2 bg-gray-100 rounded-full overflow-hidden">
                            <div class="h-full bg-purple-500 rounded-full" style="width: {{ ($guestScore->seniority_score / 25) * 100 }}%"></div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Factors --}}
            @if($guestScore->factors)
            <div class="bg-white rounded-2xl border border-gray-100 p-6">
                <h2 class="text-lg font-bold text-gray-900 mb-4">Facteurs détaillés</h2>
                <div class="space-y-2">
                    @foreach($guestScore->factors as $key => $value)
                    <div class="flex items-center justify-between text-sm py-2 border-b border-gray-50 last:border-0">
                        <span class="text-gray-600">{{ ucfirst(str_replace('_', ' ', $key)) }}</span>
                        <span class="text-gray-900">{{ is_bool($value) ? ($value ? 'Oui' : 'Non') : $value }}</span>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>

        <div class="space-y-6">
            {{-- Total Score --}}
            <div class="bg-white rounded-2xl border border-gray-100 p-6 text-center">
                <p class="text-sm font-semibold text-gray-500 uppercase mb-2">Score total</p>
                <div class="w-24 h-24 mx-auto rounded-full flex items-center justify-center text-3xl font-bold
                    {{ $guestScore->total_score >= 80 ? 'bg-green-100 text-green-700' : ($guestScore->total_score >= 60 ? 'bg-blue-100 text-blue-700' : ($guestScore->total_score >= 40 ? 'bg-amber-100 text-amber-700' : 'bg-red-100 text-red-700')) }}">
                    {{ $guestScore->total_score }}
                </div>
                <p class="text-sm text-gray-500 mt-3">sur 100</p>
                <p class="text-lg font-semibold mt-2
                    {{ $guestScore->total_score >= 80 ? 'text-green-600' : ($guestScore->total_score >= 60 ? 'text-blue-600' : ($guestScore->total_score >= 40 ? 'text-amber-600' : 'text-red-600')) }}">
                    {{ $guestScore->total_score >= 80 ? 'Excellent' : ($guestScore->total_score >= 60 ? 'Bon' : ($guestScore->total_score >= 40 ? 'Moyen' : 'Risqué')) }}
                </p>
            </div>

            {{-- Meta --}}
            <div class="bg-white rounded-2xl border border-gray-100 p-6">
                <h3 class="text-sm font-semibold text-gray-500 uppercase mb-3">Informations</h3>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-500">Calculé le</span>
                        <span class="text-gray-900">{{ $guestScore->calculated_at?->format('d/m/Y H:i') }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Inscrit depuis</span>
                        <span class="text-gray-900">{{ $guestScore->user?->created_at?->diffForHumans() }}</span>
                    </div>
                </div>
            </div>

            <form action="{{ route('owner.screening.recalculate', $guestScore) }}" method="POST">
                @csrf
                <button type="submit" class="w-full px-4 py-2.5 bg-gray-100 text-gray-700 font-semibold rounded-xl hover:bg-gray-200 text-sm">Recalculer le score</button>
            </form>
        </div>
    </div>
</div>
@endsection
