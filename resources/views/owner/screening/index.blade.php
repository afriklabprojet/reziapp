@extends('layouts.owner')

@section('title', 'Score des voyageurs — Rezi Studio Meublé Faya')

@section('owner-content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Score des voyageurs</h1>
            <p class="text-sm text-gray-500 mt-1">Évaluation automatique de la fiabilité des voyageurs</p>
        </div>
    </div>

    {{-- Score Legend --}}
    <div class="bg-white rounded-2xl border border-gray-100 p-4">
        <div class="flex flex-wrap items-center gap-4 text-sm">
            <span class="inline-flex items-center gap-1"><span class="w-3 h-3 rounded-full bg-green-500"></span> Excellent (80+)</span>
            <span class="inline-flex items-center gap-1"><span class="w-3 h-3 rounded-full bg-blue-500"></span> Bon (60-79)</span>
            <span class="inline-flex items-center gap-1"><span class="w-3 h-3 rounded-full bg-amber-500"></span> Moyen (40-59)</span>
            <span class="inline-flex items-center gap-1"><span class="w-3 h-3 rounded-full bg-red-500"></span> Risqué (&lt;40)</span>
        </div>
    </div>

    {{-- Guests List --}}
    <div class="space-y-3">
        @forelse($guests as $score)
        <a href="{{ route('owner.screening.show', $score) }}" class="block bg-white rounded-2xl border border-gray-100 p-5 hover:shadow-sm transition-shadow">
            <div class="flex items-center justify-between gap-4">
                <div class="flex items-center gap-4 min-w-0">
                    <div class="w-12 h-12 rounded-xl flex items-center justify-center text-xl font-bold shrink-0
                        {{ $score->total_score >= 80 ? 'bg-green-100 text-green-700' : ($score->total_score >= 60 ? 'bg-blue-100 text-blue-700' : ($score->total_score >= 40 ? 'bg-amber-100 text-amber-700' : 'bg-red-100 text-red-700')) }}">
                        {{ $score->total_score }}
                    </div>
                    <div class="min-w-0">
                        <p class="font-semibold text-gray-900 truncate">{{ $score->user?->name ?? 'Voyageur inconnu' }}</p>
                        <p class="text-sm text-gray-500">{{ $score->user?->email }}</p>
                        <p class="text-xs text-gray-400 mt-1">Calculé {{ $score->calculated_at?->diffForHumans() }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <div class="text-right text-xs text-gray-500 hidden sm:block">
                        <p>Identité: {{ $score->identity_score }}/25</p>
                        <p>Réservations: {{ $score->booking_score }}/25</p>
                        <p>Avis: {{ $score->review_score }}/25</p>
                        <p>Ancienneté: {{ $score->seniority_score }}/25</p>
                    </div>
                    <svg class="w-5 h-5 text-gray-300" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" /></svg>
                </div>
            </div>
        </a>
        @empty
        <div class="bg-white rounded-2xl border border-gray-100 p-12 text-center">
            <svg class="w-12 h-12 mx-auto text-gray-300 mb-3" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" /></svg>
            <p class="text-gray-400 font-medium">Aucun score de voyageur calculé</p>
        </div>
        @endforelse
    </div>

    @if($guests->hasPages())
    <div>{{ $guests->withQueryString()->links() }}</div>
    @endif
</div>
@endsection
