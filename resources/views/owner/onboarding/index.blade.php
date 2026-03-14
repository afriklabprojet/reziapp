@extends('layouts.owner')

@section('title', 'Démarrage — REZI')

@section('owner-content')
<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Bienvenue sur REZI 🎉</h1>
        <p class="text-sm text-gray-500 mt-1">Complétez ces étapes pour optimiser vos chances de réservation</p>
    </div>

    {{-- Progress --}}
    <div class="bg-white rounded-2xl border border-gray-100 p-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-bold text-gray-900">Votre progression</h2>
            <span class="text-sm font-semibold {{ $progress >= 100 ? 'text-green-600' : 'text-gray-600' }}">{{ $progress }}%</span>
        </div>
        <div class="h-3 bg-gray-100 rounded-full overflow-hidden">
            <div class="h-full bg-gradient-to-r {{ $progress >= 100 ? 'from-green-500 to-emerald-500' : 'from-blue-500 to-indigo-500' }} rounded-full transition-all duration-500" style="width: {{ min(100, $progress) }}%"></div>
        </div>
    </div>

    {{-- Steps --}}
    <div class="space-y-4">
        @foreach($steps as $step)
        <div class="bg-white rounded-2xl border {{ $step['completed'] ? 'border-green-200' : 'border-gray-100' }} p-5">
            <div class="flex items-start gap-4">
                <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0 {{ $step['completed'] ? 'bg-green-100 text-green-600' : 'bg-gray-100 text-gray-400' }}">
                    @if($step['completed'])
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                    @else
                    <span class="text-sm font-bold">{{ $loop->iteration }}</span>
                    @endif
                </div>
                <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <p class="font-semibold text-gray-900 {{ $step['completed'] ? 'line-through text-gray-500' : '' }}">{{ $step['title'] }}</p>
                            <p class="text-sm text-gray-500 mt-1">{{ $step['description'] }}</p>
                        </div>
                        @if(!$step['completed'] && $step['action_url'])
                        <a href="{{ $step['action_url'] }}" class="shrink-0 px-4 py-2 bg-gray-900 text-white rounded-xl text-sm font-semibold hover:bg-gray-800">
                            {{ $step['action_label'] ?? 'Compléter' }}
                        </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    {{-- Tips --}}
    <div class="bg-gradient-to-br from-blue-50 to-indigo-50 rounded-2xl p-6">
        <h2 class="text-lg font-bold text-gray-900 mb-4">💡 Conseils pour réussir</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="flex items-start gap-3">
                <span class="text-xl">📸</span>
                <div>
                    <p class="font-semibold text-gray-900 text-sm">Photos de qualité</p>
                    <p class="text-xs text-gray-600">Les annonces avec +10 photos reçoivent 2x plus de vues</p>
                </div>
            </div>
            <div class="flex items-start gap-3">
                <span class="text-xl">⚡</span>
                <div>
                    <p class="font-semibold text-gray-900 text-sm">Répondez rapidement</p>
                    <p class="text-xs text-gray-600">Un temps de réponse &lt;1h augmente les réservations de 40%</p>
                </div>
            </div>
            <div class="flex items-start gap-3">
                <span class="text-xl">💰</span>
                <div>
                    <p class="font-semibold text-gray-900 text-sm">Prix compétitif</p>
                    <p class="text-xs text-gray-600">Analysez les prix de votre zone pour rester attractif</p>
                </div>
            </div>
            <div class="flex items-start gap-3">
                <span class="text-xl">📝</span>
                <div>
                    <p class="font-semibold text-gray-900 text-sm">Description détaillée</p>
                    <p class="text-xs text-gray-600">Mentionnez les équipements et points forts uniques</p>
                </div>
            </div>
        </div>
    </div>

    @if($progress >= 100)
    <div class="bg-green-50 rounded-2xl border border-green-200 p-6 text-center">
        <span class="text-4xl mb-3 block">🏆</span>
        <h3 class="text-lg font-bold text-green-900">Félicitations !</h3>
        <p class="text-sm text-green-700 mt-1">Votre profil est complet. Vous êtes prêt à recevoir des réservations !</p>
    </div>
    @endif
</div>
@endsection
