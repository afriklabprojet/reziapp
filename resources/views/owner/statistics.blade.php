@extends('layouts.owner')

@section('title', 'Statistiques détaillées - Rezi Studio Meublé Faya')

@section('owner-content')
    <div class="max-w-7xl mx-auto space-y-6">
        {{-- En-tête --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-xl font-extrabold text-gray-900 flex items-center gap-2">
                    <div class="w-8 h-8 bg-[#FFE7D1] rounded-lg flex items-center justify-center">
                        <svg class="w-4 h-4 text-[#F16A00]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                    </div>
                    Statistiques détaillées
                </h1>
                <p class="text-sm text-gray-500 mt-1">Performances de vos {{ $globalStats['total_residences'] }}
                    annonce{{ $globalStats['total_residences'] > 1 ? 's' : '' }}</p>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('owner.analytics.index') }}"
                    class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-semibold text-[#CC5A00] bg-[#FFF4EB] rounded-xl hover:bg-[#FFE7D1] transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                    </svg>
                    Analytics avancées
                </a>
                <a href="{{ route('owner.dashboard') }}"
                    class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-semibold text-gray-600 bg-gray-100 rounded-xl hover:bg-gray-200 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                    Dashboard
                </a>
            </div>
        </div>

        {{-- KPIs principaux --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            {{-- Vues totales --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-10 h-10 bg-purple-100 rounded-xl flex items-center justify-center">
                        <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        </svg>
                    </div>
                    @if (($globalStats['views_change'] ?? 0) != 0)
                        <span
                            class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-bold {{ $globalStats['views_change'] > 0 ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                            {{ $globalStats['views_change'] > 0 ? '+' : '' }}{{ $globalStats['views_change'] }}%
                        </span>
                    @endif
                </div>
                <p class="text-[11px] font-semibold text-gray-500 uppercase tracking-wider">Vues totales</p>
                <p class="text-xl font-extrabold text-gray-900 mt-1">
                    {{ number_format($globalStats['total_views'] ?? 0, 0, ',', ' ') }}</p>
                <p class="text-[11px] text-gray-400 mt-1">
                    {{ number_format($globalStats['views_this_month'] ?? 0, 0, ',', ' ') }} ce mois</p>
            </div>

            {{-- Contacts reçus --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-10 h-10 bg-green-100 rounded-xl flex items-center justify-center">
                        <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                    </div>
                </div>
                <p class="text-[11px] font-semibold text-gray-500 uppercase tracking-wider">Contacts reçus</p>
                <p class="text-xl font-extrabold text-gray-900 mt-1">
                    {{ number_format($globalStats['total_contacts'] ?? 0, 0, ',', ' ') }}</p>
                <p class="text-[11px] text-gray-400 mt-1">
                    {{ number_format($globalStats['contacts_this_month'] ?? 0, 0, ',', ' ') }} ce mois</p>
            </div>

            {{-- Taux de conversion --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-10 h-10 bg-blue-100 rounded-xl flex items-center justify-center">
                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                        </svg>
                    </div>
                </div>
                <p class="text-[11px] font-semibold text-gray-500 uppercase tracking-wider">Taux de conversion</p>
                <p class="text-xl font-extrabold text-gray-900 mt-1">
                    {{ number_format($globalStats['avg_conversion'] ?? 0, 1) }}%</p>
                <p class="text-[11px] text-gray-400 mt-1">Moyenne sur toutes vos annonces</p>
            </div>

            {{-- Annonces actives --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-10 h-10 bg-[#FFE7D1] rounded-xl flex items-center justify-center">
                        <svg class="w-5 h-5 text-[#F16A00]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                        </svg>
                    </div>
                </div>
                <p class="text-[11px] font-semibold text-gray-500 uppercase tracking-wider">Annonces actives</p>
                <p class="text-xl font-extrabold text-gray-900 mt-1">{{ $globalStats['active_residences'] ?? 0 }} <span
                        class="text-sm font-normal text-gray-400">/ {{ $globalStats['total_residences'] ?? 0 }}</span></p>
                <p class="text-[11px] text-gray-400 mt-1">
                    {{ number_format($globalStats['views_this_month'] ?? 0, 0, ',', ' ') }} vues ce mois</p>
            </div>
        </div>

        {{-- Graphique d'évolution 30 jours --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
            <div class="flex items-center justify-between mb-5">
                <h2 class="text-sm font-bold text-gray-900">Évolution sur 30 jours</h2>
                <div class="flex items-center gap-4 text-[11px] text-gray-400">
                    <span class="flex items-center gap-1.5">
                        <span class="w-2.5 h-2.5 bg-[#F16A00] rounded-full"></span>
                        Vues
                    </span>
                    <span class="flex items-center gap-1.5">
                        <span class="w-2.5 h-2.5 bg-blue-500 rounded-full"></span>
                        Contacts
                    </span>
                </div>
            </div>

            @if ($dailyStats->count() > 0)
                @php
                    $maxViews = $dailyStats->max('views') ?: 1;
                @endphp
                <div class="flex items-end gap-0.75 h-44 overflow-x-auto pb-2">
                    @foreach ($dailyStats as $stat)
                        <div class="flex-1 min-w-2 flex flex-col items-center justify-end gap-px group relative">
                            {{-- Barre contacts (bleu, dessus) --}}
                            @if ($stat->contacts > 0)
                                <div class="w-full bg-blue-500 rounded-t transition-all"
                                    style="height: {{ max(($stat->contacts / $maxViews) * 100, 4) }}%"
                                    title="{{ \Carbon\Carbon::parse($stat->stat_date)->locale('fr')->isoFormat('D MMM') }}: {{ $stat->contacts }} contact{{ $stat->contacts > 1 ? 's' : '' }}">
                                </div>
                            @endif
                            {{-- Barre vues (orange, dessous) --}}
                            <div class="w-full bg-[#F16A00]/80 hover:bg-[#F16A00] rounded-t transition-all cursor-pointer"
                                style="height: {{ max(($stat->views / $maxViews) * 100, $stat->views > 0 ? 4 : 0) }}%"
                                title="{{ \Carbon\Carbon::parse($stat->stat_date)->locale('fr')->isoFormat('D MMM') }}: {{ $stat->views }} vue{{ $stat->views > 1 ? 's' : '' }}">
                            </div>
                        </div>
                    @endforeach
                </div>
                <div class="flex justify-between mt-2 text-[10px] text-gray-400">
                    <span>{{ $dailyStats->first()? \Carbon\Carbon::parse($dailyStats->first()->stat_date)->locale('fr')->isoFormat('D MMM'): '' }}</span>
                    <span>{{ $dailyStats->last()? \Carbon\Carbon::parse($dailyStats->last()->stat_date)->locale('fr')->isoFormat('D MMM'): '' }}</span>
                </div>
            @else
                <div class="h-44 flex items-center justify-center text-gray-400 text-sm">
                    Aucune donnée sur les 30 derniers jours
                </div>
            @endif
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {{-- Performance par annonce --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="p-5 border-b border-gray-100">
                    <h2 class="text-sm font-bold text-gray-900">Performance par annonce</h2>
                    <p class="text-[11px] text-gray-400 mt-0.5">Classement par nombre de vues</p>
                </div>

                @if ($residenceStats->count() > 0)
                    <div class="divide-y divide-gray-100 max-h-100 overflow-y-auto">
                        @foreach ($residenceStats as $residence)
                            <div class="p-4 hover:bg-gray-50/50 transition">
                                <div class="flex items-start justify-between gap-3 mb-3">
                                    <div class="min-w-0 flex-1">
                                        <h4 class="text-sm font-semibold text-gray-900 truncate">
                                            {{ $residence->name }}</h4>
                                        <p class="text-[11px] text-gray-400">{{ $residence->commune }}</p>
                                    </div>
                                    <span
                                        class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold shrink-0
                                    {{ in_array($residence->status, ['active', 'approved']) ? 'bg-green-100 text-green-700' : '' }}
                                    {{ $residence->status === 'pending' ? 'bg-amber-100 text-amber-700' : '' }}
                                    {{ $residence->status === 'rejected' ? 'bg-red-100 text-red-700' : '' }}
                                    {{ !in_array($residence->status, ['active', 'approved', 'pending', 'rejected']) ? 'bg-gray-100 text-gray-600' : '' }}">
                                        {{ match ($residence->status) {'active', 'approved' => 'Actif','pending' => 'En attente','rejected' => 'Rejeté',default => ucfirst($residence->status)} }}
                                    </span>
                                </div>

                                <div class="grid grid-cols-3 gap-2">
                                    <div class="bg-purple-50 rounded-xl p-2.5 text-center">
                                        <p class="text-sm font-bold text-gray-900">
                                            {{ number_format($residence->views_count, 0, ',', ' ') }}</p>
                                        <p class="text-[10px] text-purple-600 font-semibold">Vues</p>
                                    </div>
                                    <div class="bg-green-50 rounded-xl p-2.5 text-center">
                                        <p class="text-sm font-bold text-gray-900">
                                            {{ number_format($residence->contacts_count, 0, ',', ' ') }}</p>
                                        <p class="text-[10px] text-green-600 font-semibold">Contacts</p>
                                    </div>
                                    <div
                                        class="rounded-xl p-2.5 text-center {{ $residence->conversion_rate >= 5 ? 'bg-green-50' : ($residence->conversion_rate >= 2 ? 'bg-amber-50' : 'bg-gray-50') }}">
                                        <p
                                            class="text-sm font-bold {{ $residence->conversion_rate >= 5 ? 'text-green-700' : ($residence->conversion_rate >= 2 ? 'text-amber-700' : 'text-gray-700') }}">
                                            {{ $residence->conversion_rate }}%
                                        </p>
                                        <p
                                            class="text-[10px] font-semibold {{ $residence->conversion_rate >= 5 ? 'text-green-600' : ($residence->conversion_rate >= 2 ? 'text-amber-600' : 'text-gray-500') }}">
                                            Conversion</p>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="p-8 text-center">
                        <div class="w-12 h-12 bg-gray-100 rounded-xl flex items-center justify-center mx-auto mb-3">
                            <svg class="w-6 h-6 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                            </svg>
                        </div>
                        <p class="text-sm text-gray-500">Aucune annonce pour le moment</p>
                    </div>
                @endif
            </div>

            {{-- Performance par commune --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <div class="flex items-center justify-between mb-5">
                    <h2 class="text-sm font-bold text-gray-900">Performance par commune</h2>
                    <span class="text-[11px] text-gray-400">{{ $communeStats->count() }}
                        commune{{ $communeStats->count() > 1 ? 's' : '' }}</span>
                </div>

                @if ($communeStats->count() > 0)
                    <div class="space-y-4">
                        @php $maxViews = $communeStats->max('views') ?: 1; @endphp
                        @foreach ($communeStats as $commune)
                            @php
                                $pct = ($commune->views / $maxViews) * 100;
                                $dotColor = ['bg-[#F16A00]', 'bg-[#FF8A1F]', 'bg-amber-500', 'bg-[#FFB46F]', 'bg-amber-400'][$loop->index % 5];
                            @endphp
                            <div>
                                <div class="flex items-center justify-between mb-1.5">
                                    <div class="flex items-center gap-2">
                                        <div class="w-2 h-2 rounded-full {{ $dotColor }}">
                                        </div>
                                        <span class="text-sm font-medium text-gray-700">{{ $commune->commune }}</span>
                                        <span class="text-[11px] text-gray-400">({{ $commune->count }})</span>
                                    </div>
                                    <div class="flex items-center gap-3">
                                        <span
                                            class="text-sm font-bold text-gray-900">{{ number_format($commune->views, 0, ',', ' ') }}</span>
                                        <span class="text-[11px] text-gray-400">vues</span>
                                    </div>
                                </div>
                                <div class="w-full bg-gray-100 rounded-full h-2.5 overflow-hidden">
                                    <div class="h-full bg-linear-to-r from-[#FF8A1F] to-[#F16A00] rounded-full transition-all duration-700"
                                        style="width: {{ max($pct, 3) }}%"></div>
                                </div>
                                @if ($commune->contacts > 0)
                                    <p class="text-[11px] text-gray-400 mt-0.5">
                                        {{ number_format($commune->contacts, 0, ',', ' ') }} contacts</p>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="py-8 text-center">
                        <p class="text-sm text-gray-500">Aucune donnée disponible</p>
                    </div>
                @endif
            </div>
        </div>

        {{-- Top 5 jours + Conseils --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {{-- Top 5 jours --}}
            @if ($topDays->count() > 0)
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <div class="flex items-center justify-between mb-5">
                        <h2 class="text-sm font-bold text-gray-900">Meilleurs jours</h2>
                        <span class="text-[11px] text-gray-400">Top {{ $topDays->count() }}</span>
                    </div>
                    <div class="space-y-3">
                        @foreach ($topDays as $day)
                            <div class="flex items-center gap-3">
                                <div
                                    class="w-8 h-8 rounded-lg flex items-center justify-center text-sm font-bold shrink-0
                                {{ $loop->index === 0 ? 'bg-[#F16A00] text-white' : ($loop->index === 1 ? 'bg-[#FFE7D1] text-[#A34700]' : 'bg-gray-100 text-gray-600') }}">
                                    {{ $loop->iteration }}
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-semibold text-gray-900">
                                        {{ \Carbon\Carbon::parse($day->stat_date)->locale('fr')->isoFormat('dddd D MMMM YYYY') }}
                                    </p>
                                </div>
                                <span
                                    class="text-sm font-bold text-purple-600 shrink-0">{{ number_format($day->total_views, 0, ',', ' ') }}
                                    vues</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Conseils d'optimisation --}}
            <div
                class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 {{ $topDays->count() === 0 ? 'lg:col-span-2' : '' }}">
                <div class="flex items-center gap-2 mb-5">
                    <div class="w-8 h-8 bg-amber-100 rounded-lg flex items-center justify-center">
                        <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                        </svg>
                    </div>
                    <h2 class="text-sm font-bold text-gray-900">Conseils pour améliorer vos performances</h2>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div class="flex items-start gap-3 p-3 bg-gray-50 rounded-xl">
                        <div class="w-8 h-8 bg-[#FFE7D1] rounded-lg flex items-center justify-center shrink-0">
                            <svg class="w-4 h-4 text-[#CC5A00]" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-xs font-bold text-gray-900">Photos de qualité</p>
                            <p class="text-[11px] text-gray-500 mt-0.5">8-10 photos lumineuses et nettes = +60% de vues</p>
                        </div>
                    </div>

                    <div class="flex items-start gap-3 p-3 bg-gray-50 rounded-xl">
                        <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center shrink-0">
                            <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-xs font-bold text-gray-900">Description complète</p>
                            <p class="text-[11px] text-gray-500 mt-0.5">Décrivez le quartier, les transports et équipements
                            </p>
                        </div>
                    </div>

                    <div class="flex items-start gap-3 p-3 bg-gray-50 rounded-xl">
                        <div class="w-8 h-8 bg-purple-100 rounded-lg flex items-center justify-center shrink-0">
                            <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-xs font-bold text-gray-900">Réponse rapide</p>
                            <p class="text-[11px] text-gray-500 mt-0.5">Répondez sous 24h = meilleur taux de conversion</p>
                        </div>
                    </div>

                    <div class="flex items-start gap-3 p-3 bg-gray-50 rounded-xl">
                        <div class="w-8 h-8 bg-green-100 rounded-lg flex items-center justify-center shrink-0">
                            <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-xs font-bold text-gray-900">Prix compétitif</p>
                            <p class="text-[11px] text-gray-500 mt-0.5">Vérifiez les prix du marché dans votre commune</p>
                        </div>
                    </div>

                    <div class="flex items-start gap-3 p-3 bg-gray-50 rounded-xl">
                        <div class="w-8 h-8 bg-cyan-100 rounded-lg flex items-center justify-center shrink-0">
                            <svg class="w-4 h-4 text-cyan-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-xs font-bold text-gray-900">Mise à jour régulière</p>
                            <p class="text-[11px] text-gray-500 mt-0.5">Actualisez chaque mois = haut des résultats</p>
                        </div>
                    </div>

                    <div class="flex items-start gap-3 p-3 bg-gray-50 rounded-xl">
                        <div class="w-8 h-8 bg-rose-100 rounded-lg flex items-center justify-center shrink-0">
                            <svg class="w-4 h-4 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-xs font-bold text-gray-900">Équipements clés</p>
                            <p class="text-[11px] text-gray-500 mt-0.5">WiFi, clim et groupe électrogène très recherchés
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
