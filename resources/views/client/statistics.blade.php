@extends('layouts.client', ['sidebarActive' => 'statistics'])

@section('title', 'Mes statistiques - REZI')

@push('styles')
    @vite('resources/js/chart.js')
@endpush

@section('client-content')
    {{-- En-tête --}}
    <div class="mb-8">
        <h1 class="text-2xl font-bold text-gray-900">Mes statistiques</h1>
        <p class="text-gray-600">Analysez votre activité sur REZI</p>
    </div>

    {{-- Infos membre --}}
    <div class="bg-gradient-to-r from-[#ff385c] to-[#e00b41] rounded-xl p-6 text-white mb-8">
        <div class="flex items-center gap-4">
            <div class="w-16 h-16 rounded-full overflow-hidden bg-white/20 flex items-center justify-center">
                @if ($user->profile_photo || $user->avatar)
                    <img loading="lazy" src="{{ $user->getAvatarUrl() }}" alt=""
                        class="w-full h-full object-cover">
                @else
                    <span class="text-2xl font-bold">{{ substr($user->name, 0, 1) }}</span>
                @endif
            </div>
            <div>
                <h2 class="text-xl font-bold">{{ $user->name }}</h2>
                <p class="text-[#ffd1da]">Membre depuis {{ $globalStats['member_since']->translatedFormat('d F Y') }}</p>
            </div>
        </div>
    </div>

    {{-- Statistiques globales --}}
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-8">
        <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100 text-center">
            <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center mx-auto mb-2">
                <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                </svg>
            </div>
            <p class="text-2xl font-bold text-gray-900">{{ $globalStats['total_views'] }}</p>
            <p class="text-xs text-gray-500">Visites</p>
        </div>

        <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100 text-center">
            <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center mx-auto mb-2">
                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </div>
            <p class="text-2xl font-bold text-gray-900">{{ $globalStats['total_searches'] }}</p>
            <p class="text-xs text-gray-500">Recherches</p>
        </div>

        <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100 text-center">
            <div class="w-10 h-10 bg-amber-100 rounded-lg flex items-center justify-center mx-auto mb-2">
                <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                </svg>
            </div>
            <p class="text-2xl font-bold text-gray-900">{{ $globalStats['total_contacts'] }}</p>
            <p class="text-xs text-gray-500">Contacts</p>
        </div>

        <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100 text-center">
            <div class="w-10 h-10 bg-rose-100 rounded-lg flex items-center justify-center mx-auto mb-2">
                <svg class="w-5 h-5 text-rose-600" fill="currentColor" viewBox="0 0 24 24">
                    <path
                        d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z" />
                </svg>
            </div>
            <p class="text-2xl font-bold text-gray-900">{{ $globalStats['total_favorites'] }}</p>
            <p class="text-xs text-gray-500">Favoris</p>
        </div>

        <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100 text-center">
            <div class="w-10 h-10 bg-[#ffd1da] rounded-lg flex items-center justify-center mx-auto mb-2">
                <svg class="w-5 h-5 text-[#ff385c]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                </svg>
            </div>
            <p class="text-2xl font-bold text-gray-900">{{ $globalStats['total_reviews'] }}</p>
            <p class="text-xs text-gray-500">Avis</p>
        </div>

        <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100 text-center">
            <div class="w-10 h-10 bg-[#ffd1da] rounded-lg flex items-center justify-center mx-auto mb-2">
                <svg class="w-5 h-5 text-[#ff385c]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
            </div>
            <p class="text-sm font-bold text-gray-900">
                {{ (int) $globalStats['member_since']->diffInDays(now()) }}</p>
            <p class="text-xs text-gray-500">Jours actif</p>
        </div>
    </div>

    <div class="grid lg:grid-cols-2 gap-8">
        {{-- Graphique d'activité --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h3 class="font-semibold text-gray-900 mb-4">Activité des 6 derniers mois</h3>
            <div class="h-64">
                <canvas id="activityChart"></canvas>
            </div>
        </div>

        {{-- Types de logement préférés --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h3 class="font-semibold text-gray-900 mb-4">Types de logement consultés</h3>
            @if ($preferredTypes->count() > 0)
                <div class="h-64">
                    <canvas id="typesChart"></canvas>
                </div>
            @else
                <div class="h-64 flex items-center justify-center text-gray-500">
                    Pas assez de données
                </div>
            @endif
        </div>

        {{-- Communes explorées --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h3 class="font-semibold text-gray-900 mb-4">Communes les plus explorées</h3>
            @if ($topCommunes->count() > 0)
                <div class="space-y-3">
                    @foreach ($topCommunes as $index => $commune)
                        @php
                            $maxCount = $topCommunes->first()->views_count;
                            $percentage = $maxCount > 0 ? ($commune->views_count / $maxCount) * 100 : 0;
                        @endphp
                        <div>
                            <div class="flex justify-between items-center mb-1">
                                <span class="text-sm font-medium text-gray-700">{{ $commune->commune }}</span>
                                <span class="text-sm text-gray-500">{{ $commune->views_count }} visites</span>
                            </div>
                            <div class="h-2 bg-gray-100 rounded-full overflow-hidden">
                                <div class="h-full bg-[#ff385c] rounded-full transition-all"
                                    style="width: {{ $percentage }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-8 text-gray-500">
                    Aucune donnée disponible
                </div>
            @endif
        </div>

        {{-- Budget recherché --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h3 class="font-semibold text-gray-900 mb-4">Budget moyen recherché</h3>
            @if ($budgetStats && ($budgetStats->avg_min || $budgetStats->avg_max))
                <div class="flex items-center justify-center h-48">
                    <div class="text-center">
                        <div class="flex items-center justify-center gap-4">
                            @if ($budgetStats->avg_min)
                                <div class="text-center">
                                    <p class="text-sm text-gray-500 mb-1">Minimum</p>
                                    <p class="text-2xl font-bold text-[#ff385c]">
                                        {{ number_format($budgetStats->avg_min, 0, ',', ' ') }}</p>
                                    <p class="text-xs text-gray-500">FCFA</p>
                                </div>
                            @endif
                            @if ($budgetStats->avg_min && $budgetStats->avg_max)
                                <div class="text-2xl text-gray-300">→</div>
                            @endif
                            @if ($budgetStats->avg_max)
                                <div class="text-center">
                                    <p class="text-sm text-gray-500 mb-1">Maximum</p>
                                    <p class="text-2xl font-bold text-[#ff385c]">
                                        {{ number_format($budgetStats->avg_max, 0, ',', ' ') }}</p>
                                    <p class="text-xs text-gray-500">FCFA</p>
                                </div>
                            @endif
                        </div>
                        <p class="mt-4 text-sm text-gray-500">Basé sur vos recherches récentes</p>
                    </div>
                </div>
            @else
                <div class="flex items-center justify-center h-48 text-gray-500">
                    <div class="text-center">
                        <svg class="w-12 h-12 text-gray-300 mx-auto mb-2" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <p>Effectuez des recherches avec des filtres de prix</p>
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- Conseils personnalisés --}}
    <div class="mt-8 bg-gradient-to-br from-[#fff0f3] to-amber-50 rounded-xl p-6 border border-[#ffd1da]">
        <h3 class="font-semibold text-gray-900 mb-4 flex items-center gap-2">
            <span class="text-xl">💡</span>
            Conseils personnalisés
        </h3>
        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
            @if ($globalStats['total_favorites'] == 0)
                <div class="bg-white/70 backdrop-blur rounded-lg p-4">
                    <h4 class="font-medium text-gray-900 mb-1">Utilisez les favoris</h4>
                    <p class="text-sm text-gray-600">Ajoutez des résidences en favoris pour les retrouver facilement et les
                        comparer.</p>
                </div>
            @endif

            @if ($globalStats['total_contacts'] == 0)
                <div class="bg-white/70 backdrop-blur rounded-lg p-4">
                    <h4 class="font-medium text-gray-900 mb-1">Contactez les propriétaires</h4>
                    <p class="text-sm text-gray-600">N'hésitez pas à contacter les propriétaires pour obtenir plus
                        d'informations.</p>
                </div>
            @endif

            @if ($globalStats['total_reviews'] == 0)
                <div class="bg-white/70 backdrop-blur rounded-lg p-4">
                    <h4 class="font-medium text-gray-900 mb-1">Laissez des avis</h4>
                    <p class="text-sm text-gray-600">Partagez votre expérience en laissant des avis sur les résidences
                        visitées.</p>
                </div>
            @endif

            @if ($topCommunes->count() <= 2)
                <div class="bg-white/70 backdrop-blur rounded-lg p-4">
                    <h4 class="font-medium text-gray-900 mb-1">Explorez plus de communes</h4>
                    <p class="text-sm text-gray-600">Découvrez d'autres quartiers pour trouver la perle rare.</p>
                </div>
            @endif
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                window.clientStatisticsCharts(@json([
                    'monthlyActivity' => $monthlyActivity,
                    'preferredTypes' => $preferredTypes,
                ]));
            });
        </script>
    @endpush
@endsection
