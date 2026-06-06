@extends('layouts.client', ['sidebarActive' => 'alerts'])

@section('title', 'Mes alertes - ReziApp')

@section('client-content')
    {{-- En-tête --}}
    <div class="mb-8">
        <h1 class="text-2xl font-bold text-gray-900">Mes alertes</h1>
        <p class="text-gray-600">Restez informé des nouvelles résidences et changements de prix</p>
    </div>

    <div class="grid lg:grid-cols-3 gap-8">
        {{-- Colonne principale --}}
        <div class="lg:col-span-2 space-y-8">

            {{-- Recherches sauvegardées avec alertes --}}
            @if (isset($savedSearches) && $savedSearches->count() > 0)
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center">
                                <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                                </svg>
                            </div>
                            <div>
                                <h2 class="font-semibold text-gray-900">Mes alertes de recherche</h2>
                                <p class="text-sm text-gray-500">Soyez notifié quand de nouvelles résidences correspondent à vos critères</p>
                            </div>
                        </div>
                    </div>
                    <div class="divide-y divide-gray-100">
                        @foreach ($savedSearches as $savedSearch)
                            <div class="flex items-center justify-between p-4">
                                <div class="flex items-center gap-3 min-w-0 flex-1">
                                    <div class="w-8 h-8 bg-purple-50 rounded-lg flex items-center justify-center shrink-0">
                                        <svg class="w-4 h-4 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
                                        </svg>
                                    </div>
                                    <div class="min-w-0">
                                        <p class="text-sm font-medium text-gray-900 truncate">{{ $savedSearch->name }}</p>
                                        <p class="text-xs text-gray-500">
                                            {{ $savedSearch->location ?? 'Toute zone' }}
                                            @if ($savedSearch->min_price || $savedSearch->max_price)
                                                • {{ $savedSearch->min_price ? number_format($savedSearch->min_price, 0, ',', ' ') : '—' }}
                                                – {{ $savedSearch->max_price ? number_format($savedSearch->max_price, 0, ',', ' ') : '—' }} FCFA
                                            @endif
                                            • {{ ucfirst($savedSearch->alert_frequency ?? 'quotidien') }}
                                        </p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2 shrink-0">
                                    @if ($savedSearch->new_results_count > 0)
                                        <span class="min-w-5 h-5 bg-purple-500 text-white text-[10px] font-bold rounded-full flex items-center justify-center px-1">{{ $savedSearch->new_results_count }}</span>
                                    @endif
                                    <button type="button"
                                        x-data
                                        @click="$dispatch('open-alert-edit', {
                                            id: {{ $savedSearch->id }},
                                            name: {{ json_encode($savedSearch->name) }},
                                            location: {{ json_encode($savedSearch->location ?? '') }},
                                            minPrice: '{{ $savedSearch->min_price ?? '' }}',
                                            maxPrice: '{{ $savedSearch->max_price ?? '' }}',
                                            type: {{ json_encode($savedSearch->type ?? '') }},
                                            frequency: {{ json_encode($savedSearch->alert_frequency ?? 'daily') }},
                                            actionUrl: '{{ route('client.alerts.update', $savedSearch) }}'
                                        })"
                                        class="p-1.5 text-gray-400 hover:text-purple-600 transition rounded-lg hover:bg-purple-50"
                                        title="Modifier cette alerte">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z" />
                                        </svg>
                                    </button>
                                    <form action="{{ route('client.alerts.delete', $savedSearch) }}" method="POST" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-1.5 text-gray-400 hover:text-red-500 transition rounded-lg hover:bg-red-50" title="Supprimer cette alerte" onclick="return confirm('Supprimer cette alerte ?')">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Alertes de prix réelles --}}
            @if ($priceAlerts->count() > 0)
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-amber-100 rounded-lg flex items-center justify-center">
                                <span class="text-xl">📉</span>
                            </div>
                            <div>
                                <h2 class="font-semibold text-gray-900">Alertes de prix</h2>
                                <p class="text-sm text-gray-500">Changements de prix sur vos résidences suivies</p>
                            </div>
                        </div>
                    </div>
                    <div class="divide-y divide-gray-100">
                        @foreach ($priceAlerts as $alert)
                            <a href="{{ route('residences.show', $alert->residence) }}"
                                class="flex items-center gap-4 p-4 hover:bg-gray-50 transition">
                                <div class="w-20 h-16 rounded-lg overflow-hidden bg-gray-200 shrink-0">
                                    @if ($alert->residence->photos->count() > 0)
                                        <img loading="lazy" src="{{ storage_url($alert->residence->photos->first()?->path) }}"
                                            alt="{{ $alert->residence->title }}" class="w-full h-full object-cover">
                                    @endif
                                </div>
                                <div class="flex-1 min-w-0">
                                    <h3 class="font-medium text-gray-900 truncate">{{ $alert->residence->title }}</h3>
                                    <div class="flex items-center gap-2 mt-1">
                                        <span class="text-sm text-gray-400 line-through">{{ number_format($alert->original_price, 0, ',', ' ') }} FCFA</span>
                                        <span class="text-sm font-semibold {{ $alert->price_change < 0 ? 'text-green-600' : 'text-red-600' }}">
                                            {{ number_format($alert->current_price, 0, ',', ' ') }} FCFA
                                        </span>
                                        <span class="px-1.5 py-0.5 rounded text-xs font-medium {{ $alert->price_change < 0 ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                                            {{ $alert->price_change < 0 ? '' : '+' }}{{ number_format(($alert->price_change / $alert->original_price) * 100, 0) }}%
                                        </span>
                                    </div>
                                </div>
                                <svg class="w-5 h-5 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif

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
                        <div class="w-10 h-10 bg-[#FFE7D1] rounded-lg flex items-center justify-center">
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
                                            class="px-2 py-0.5 bg-[#FFE7D1] text-[#CC5A00] text-xs font-medium rounded">Disponible</span>
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
            <div class="bg-[#FFF4EB] rounded-xl p-6 border border-[#FFE7D1]">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-10 h-10 bg-[#FFE7D1] rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-[#F16A00]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                        </svg>
                    </div>
                    <h3 class="font-semibold text-[#A34700]">Notifications push</h3>
                </div>
                <p class="text-sm text-[#CC5A00] mb-4">
                    Activez les notifications pour être alerté en temps réel des nouvelles résidences correspondant
                    à vos critères.
                </p>
                <button onclick="requestNotificationPermission()"
                    class="w-full px-4 py-2 bg-[#F16A00] hover:bg-[#CC5A00] text-white font-medium rounded-lg transition">
                    Activer les notifications
                </button>
            </div>

            {{-- Conseils --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <h3 class="font-semibold text-gray-900 mb-4">💡 Conseils</h3>
                <ul class="space-y-3 text-sm text-gray-600">
                    <li class="flex items-start gap-2">
                        <svg class="w-5 h-5 text-[#F16A00] shrink-0 mt-0.5" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        <span>Ajoutez des résidences en favoris pour être alerté des changements de prix</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <svg class="w-5 h-5 text-[#F16A00] shrink-0 mt-0.5" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        <span>Explorez différentes communes pour élargir vos possibilités</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <svg class="w-5 h-5 text-[#F16A00] shrink-0 mt-0.5" fill="none" stroke="currentColor"
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
                    @if (isset($savedSearches))
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">Alertes recherche</span>
                            <span class="font-semibold text-purple-600">{{ $savedSearches->count() }}</span>
                        </div>
                    @endif
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600">Nouvelles résidences</span>
                        <span class="font-semibold text-green-600">{{ $newListings->count() }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600">Favoris disponibles</span>
                        <span class="font-semibold text-[#F16A00]">{{ $availabilityAlerts->count() }}</span>
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

{{-- Modal édition alerte de recherche --}}
<div
    x-data="{
        open: false,
        alertId: null,
        name: '',
        location: '',
        minPrice: '',
        maxPrice: '',
        type: '',
        frequency: 'daily',
        actionUrl: '',
        submitting: false,
    }"
    @open-alert-edit.window="
        alertId = $event.detail.id;
        name = $event.detail.name;
        location = $event.detail.location;
        minPrice = $event.detail.minPrice;
        maxPrice = $event.detail.maxPrice;
        type = $event.detail.type;
        frequency = $event.detail.frequency;
        actionUrl = $event.detail.actionUrl;
        open = true;
    "
    x-show="open"
    x-cloak
    class="fixed inset-0 z-50 flex items-center justify-center p-4"
    style="display: none;"
>
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="open = false"></div>

    <div class="relative w-full max-w-md bg-white rounded-2xl shadow-xl p-6 z-10" @click.stop>
        <div class="flex items-center justify-between mb-5">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-lg bg-purple-100 flex items-center justify-center">
                    <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z" />
                    </svg>
                </div>
                <h3 class="text-base font-semibold text-gray-900">Modifier l'alerte</h3>
            </div>
            <button @click="open = false" class="p-1.5 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <form :action="actionUrl" method="POST" @submit="submitting = true" class="space-y-4">
            @csrf
            @method('PATCH')

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nom de l'alerte</label>
                <input type="text" name="name" x-model="name" required maxlength="100"
                    class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm focus:ring-2 focus:ring-purple-300 focus:border-purple-400" />
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Zone / Commune</label>
                <input type="text" name="location" x-model="location" maxlength="100" placeholder="Ex: Cocody, Plateau…"
                    class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm focus:ring-2 focus:ring-purple-300 focus:border-purple-400" />
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Budget min (FCFA)</label>
                    <input type="number" name="min_price" x-model="minPrice" min="0" step="1000"
                        class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm focus:ring-2 focus:ring-purple-300 focus:border-purple-400" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Budget max (FCFA)</label>
                    <input type="number" name="max_price" x-model="maxPrice" min="0" step="1000"
                        class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm focus:ring-2 focus:ring-purple-300 focus:border-purple-400" />
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Type de logement</label>
                <select name="type" x-model="type"
                    class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm focus:ring-2 focus:ring-purple-300 focus:border-purple-400">
                    <option value="">Tous types</option>
                    <option value="studio">Studio</option>
                    <option value="appartement">Appartement</option>
                    <option value="villa">Villa</option>
                    <option value="chambre">Chambre</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Fréquence de notification</label>
                <select name="alert_frequency" x-model="frequency"
                    class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm focus:ring-2 focus:ring-purple-300 focus:border-purple-400">
                    <option value="instant">Instantanée</option>
                    <option value="daily">Quotidienne</option>
                    <option value="weekly">Hebdomadaire</option>
                </select>
            </div>

            <div class="flex gap-3 pt-1">
                <button type="button" @click="open = false"
                    class="flex-1 px-4 py-2.5 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-xl transition">
                    Annuler
                </button>
                <button type="submit" :disabled="submitting || !name.trim()"
                    class="flex-1 px-4 py-2.5 text-sm font-medium text-white bg-purple-600 hover:bg-purple-700 disabled:bg-purple-300 disabled:cursor-not-allowed rounded-xl transition">
                    <span x-show="!submitting">Enregistrer</span>
                    <span x-show="submitting">Enregistrement…</span>
                </button>
            </div>
        </form>
    </div>
</div>
