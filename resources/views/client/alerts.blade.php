@extends('layouts.client', ['sidebarActive' => 'alerts'])

@section('title', 'Mes alertes - REZI')

@section('client-content')
    {{-- En-tête --}}
    <div class="mb-8">
        <h1 class="text-2xl font-bold text-gray-900">Mes alertes</h1>
        <p class="text-gray-600">Restez informé des nouvelles résidences et changements de prix</p>
    </div>

    <div class="grid lg:grid-cols-3 gap-8">
        {{-- Colonne principale --}}
        <div class="lg:col-span-2 space-y-8">
            {{-- Nouvelles résidences dans vos zones --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                            <span class="text-xl">🆕</span>
                        </div>
                        <div>
                            <h2 class="font-semibold text-gray-900">Nouvelles résidences</h2>
                            <p class="text-sm text-gray-500">Dans vos zones favorites</p>
                        </div>
                    </div>
                </div>

                @if ($newListings->count() > 0)
                    <div class="divide-y divide-gray-100">
                        @foreach ($newListings as $residence)
                            <a href="{{ route('residences.show', $residence) }}"
                                class="flex items-center gap-4 p-4 hover:bg-gray-50 transition">
                                <div class="w-20 h-16 rounded-lg overflow-hidden bg-gray-200 shrink-0">
                                    @if ($residence->photos->count() > 0)
                                        <img loading="lazy" src="{{ storage_url($residence->photos->first()?->path) }}"
                                            alt="{{ $residence->name }}" class="w-full h-full object-cover">
                                    @else
                                        <div class="w-full h-full flex items-center justify-center">
                                            <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                                            </svg>
                                        </div>
                                    @endif
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2 mb-1">
                                        <span
                                            class="px-2 py-0.5 bg-green-100 text-green-700 text-xs font-medium rounded">Nouveau</span>
                                        <span
                                            class="text-xs text-gray-500">{{ $residence->created_at->diffForHumans() }}</span>
                                    </div>
                                    <h3 class="font-medium text-gray-900 truncate">{{ $residence->title }}</h3>
                                    <p class="text-sm text-gray-500">{{ $residence->commune }} •
                                        {{ number_format($residence->price, 0, ',', ' ') }}
                                        FCFA/{{ $residence->price_label }}</p>
                                </div>
                                <svg class="w-5 h-5 text-gray-400 shrink-0" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 5l7 7-7 7" />
                                </svg>
                            </a>
                        @endforeach
                    </div>
                @else
                    <div class="p-8 text-center text-gray-500">
                        <svg class="w-12 h-12 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                        </svg>
                        <p>Aucune nouvelle résidence pour le moment</p>
                        <p class="text-sm mt-1">Ajoutez des favoris pour être alerté des nouveautés dans vos zones
                            préférées</p>
                    </div>
                @endif
            </div>

            {{-- Disponibilité des favoris --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-orange-100 rounded-lg flex items-center justify-center">
                            <span class="text-xl">✅</span>
                        </div>
                        <div>
                            <h2 class="font-semibold text-gray-900">Favoris disponibles</h2>
                            <p class="text-sm text-gray-500">Vos favoris actuellement libres</p>
                        </div>
                    </div>
                </div>

                @if ($availabilityAlerts->count() > 0)
                    <div class="divide-y divide-gray-100">
                        @foreach ($availabilityAlerts as $favorite)
                            <a href="{{ route('residences.show', $favorite->residence) }}"
                                class="flex items-center gap-4 p-4 hover:bg-gray-50 transition">
                                <div class="w-20 h-16 rounded-lg overflow-hidden bg-gray-200 shrink-0">
                                    @if ($favorite->residence->photos->count() > 0)
                                        <img loading="lazy"
                                            src="{{ storage_url($favorite->residence->photos->first()?->path) }}"
                                            alt="{{ $favorite->residence->name }}" class="w-full h-full object-cover">
                                    @endif
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2 mb-1">
                                        <span
                                            class="px-2 py-0.5 bg-orange-100 text-orange-600 text-xs font-medium rounded">Disponible</span>
                                    </div>
                                    <h3 class="font-medium text-gray-900 truncate">
                                        {{ $favorite->residence->title }}</h3>
                                    <p class="text-sm text-gray-500">{{ $favorite->residence->commune }} •
                                        {{ number_format($favorite->residence->price, 0, ',', ' ') }}
                                        FCFA/{{ $favorite->residence->price_label }}</p>
                                </div>
                                <svg class="w-5 h-5 text-gray-400 shrink-0" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 5l7 7-7 7" />
                                </svg>
                            </a>
                        @endforeach
                    </div>
                @else
                    <div class="p-8 text-center text-gray-500">
                        <p>Aucun favori disponible actuellement</p>
                    </div>
                @endif
            </div>
        </div>

        {{-- Sidebar --}}
        <div class="space-y-6">
            {{-- Activer les notifications --}}
            <div class="bg-orange-50 rounded-xl p-6 border border-orange-100">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-10 h-10 bg-orange-100 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                        </svg>
                    </div>
                    <h3 class="font-semibold text-orange-700">Notifications push</h3>
                </div>
                <p class="text-sm text-orange-600 mb-4">
                    Activez les notifications pour être alerté en temps réel des nouvelles résidences correspondant
                    à vos critères.
                </p>
                <button onclick="requestNotificationPermission()"
                    class="w-full px-4 py-2 bg-orange-500 hover:bg-orange-600 text-white font-medium rounded-lg transition">
                    Activer les notifications
                </button>
            </div>

            {{-- Conseils --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <h3 class="font-semibold text-gray-900 mb-4">💡 Conseils</h3>
                <ul class="space-y-3 text-sm text-gray-600">
                    <li class="flex items-start gap-2">
                        <svg class="w-5 h-5 text-orange-500 shrink-0 mt-0.5" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        <span>Ajoutez des résidences en favoris pour être alerté des changements de prix</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <svg class="w-5 h-5 text-orange-500 shrink-0 mt-0.5" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        <span>Explorez différentes communes pour élargir vos possibilités</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <svg class="w-5 h-5 text-orange-500 shrink-0 mt-0.5" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        <span>Contactez rapidement les propriétaires des nouvelles annonces</span>
                    </li>
                </ul>
            </div>

            {{-- Statistiques alertes --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <h3 class="font-semibold text-gray-900 mb-4">Résumé</h3>
                <div class="space-y-3">
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600">Nouvelles résidences</span>
                        <span class="font-semibold text-green-600">{{ $newListings->count() }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600">Favoris disponibles</span>
                        <span class="font-semibold text-orange-500">{{ $availabilityAlerts->count() }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600">Alertes prix</span>
                        <span class="font-semibold text-amber-600">{{ $priceAlerts->count() }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection
