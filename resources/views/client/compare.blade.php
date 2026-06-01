@extends('layouts.client', ['sidebarActive' => 'compare'])

@section('title', 'Comparer les résidences - REZI')

@section('client-content')
    {{-- En-tête --}}
    <div class="mb-8">
        <h1 class="text-2xl font-bold text-gray-900">Comparer les résidences</h1>
        <p class="text-gray-600">Comparez jusqu'à 4 résidences côte à côte</p>
    </div>

    @if ($residences->count() > 0)
        {{-- Tableau de comparaison --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-gray-200">
                            <th class="py-4 px-6 text-left text-sm font-semibold text-gray-900 bg-gray-50 w-48">
                                Caractéristique
                            </th>
                            @foreach ($residences as $residence)
                                <th class="py-4 px-4 text-center min-w-64">
                                    <div class="relative">
                                        <a href="{{ route('residences.show', $residence) }}" class="block group">
                                            <div class="relative aspect-video rounded-lg overflow-hidden mb-3">
                                                @if ($residence->photos->count() > 0)
                                                    <img loading="lazy"
                                                        src="{{ storage_url($residence->photos->first()?->path) }}"
                                                        alt="{{ $residence->name }}"
                                                        class="w-full h-full object-cover group-hover:scale-105 transition duration-300">
                                                @else
                                                    <div class="w-full h-full bg-gray-200 flex items-center justify-center">
                                                        <svg class="w-8 h-8 text-gray-400" fill="none"
                                                            stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                                                        </svg>
                                                    </div>
                                                @endif
                                            </div>
                                            <h3 class="font-semibold text-gray-900 truncate group-hover:text-[#F16A00]">
                                                {{ $residence->title }}</h3>
                                        </a>
                                    </div>
                                </th>
                            @endforeach
                            @for ($i = $residences->count(); $i < 4; $i++)
                                <th class="py-4 px-4 text-center min-w-64">
                                    <a href="{{ route('residences.index') }}"
                                        class="border-2 border-dashed border-gray-200 hover:border-[#FFD0A3] rounded-lg p-8 h-full flex flex-col items-center justify-center transition-colors group">
                                        <svg class="w-8 h-8 text-gray-300 group-hover:text-[#FF8A1F] mb-2 transition-colors" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                        </svg>
                                        <span class="text-sm text-gray-400 group-hover:text-[#F16A00] transition-colors">Ajouter une résidence</span>
                                        <span class="text-xs text-gray-300 mt-1">Parcourir les annonces</span>
                                    </a>
                                </th>
                            @endfor
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        {{-- Prix --}}
                        <tr class="hover:bg-gray-50">
                            <td class="py-4 px-6 font-medium text-gray-900 bg-gray-50">
                                <div class="flex items-center gap-2">
                                    <span class="text-xl">💰</span>
                                    Prix / mois
                                </div>
                            </td>
                            @foreach ($residences as $residence)
                                <td class="py-4 px-4 text-center">
                                    <span
                                        class="text-xl font-bold text-[#F16A00]">{{ number_format($residence->price, 0, ',', ' ') }}</span>
                                    <span class="text-sm text-gray-500">FCFA</span>
                                </td>
                            @endforeach
                            @for ($i = $residences->count(); $i < 4; $i++)
                                <td class="py-4 px-4 text-center text-gray-300">-</td>
                            @endfor
                        </tr>

                        {{-- Commune --}}
                        <tr class="hover:bg-gray-50">
                            <td class="py-4 px-6 font-medium text-gray-900 bg-gray-50">
                                <div class="flex items-center gap-2">
                                    <span class="text-xl">📍</span>
                                    Localisation
                                </div>
                            </td>
                            @foreach ($residences as $residence)
                                <td class="py-4 px-4 text-center text-gray-700">
                                    {{ $residence->commune }}
                                </td>
                            @endforeach
                            @for ($i = $residences->count(); $i < 4; $i++)
                                <td class="py-4 px-4 text-center text-gray-300">-</td>
                            @endfor
                        </tr>

                        {{-- Type --}}
                        <tr class="hover:bg-gray-50">
                            <td class="py-4 px-6 font-medium text-gray-900 bg-gray-50">
                                <div class="flex items-center gap-2">
                                    <span class="text-xl">🏠</span>
                                    Type
                                </div>
                            </td>
                            @foreach ($residences as $residence)
                                <td class="py-4 px-4 text-center">
                                    <span class="px-3 py-1 bg-blue-100 text-blue-700 text-sm font-medium rounded-full">
                                        {{ ucfirst($residence->type) }}
                                    </span>
                                </td>
                            @endforeach
                            @for ($i = $residences->count(); $i < 4; $i++)
                                <td class="py-4 px-4 text-center text-gray-300">-</td>
                            @endfor
                        </tr>

                        {{-- Chambres --}}
                        <tr class="hover:bg-gray-50">
                            <td class="py-4 px-6 font-medium text-gray-900 bg-gray-50">
                                <div class="flex items-center gap-2">
                                    <span class="text-xl">🛏️</span>
                                    Chambres
                                </div>
                            </td>
                            @foreach ($residences as $residence)
                                <td class="py-4 px-4 text-center">
                                    <span class="text-lg font-semibold text-gray-900">{{ $residence->bedrooms }}</span>
                                </td>
                            @endforeach
                            @for ($i = $residences->count(); $i < 4; $i++)
                                <td class="py-4 px-4 text-center text-gray-300">-</td>
                            @endfor
                        </tr>

                        {{-- Superficie --}}
                        <tr class="hover:bg-gray-50">
                            <td class="py-4 px-6 font-medium text-gray-900 bg-gray-50">
                                <div class="flex items-center gap-2">
                                    <span class="text-xl">📐</span>
                                    Superficie
                                </div>
                            </td>
                            @foreach ($residences as $residence)
                                <td class="py-4 px-4 text-center">
                                    @if ($residence->surface)
                                        <span class="text-gray-900">{{ $residence->surface }} m²</span>
                                    @else
                                        <span class="text-gray-400">Non spécifié</span>
                                    @endif
                                </td>
                            @endforeach
                            @for ($i = $residences->count(); $i < 4; $i++)
                                <td class="py-4 px-4 text-center text-gray-300">-</td>
                            @endfor
                        </tr>

                        {{-- Équipements --}}
                        <tr class="hover:bg-gray-50">
                            <td class="py-4 px-6 font-medium text-gray-900 bg-gray-50">
                                <div class="flex items-center gap-2">
                                    <span class="text-xl">✨</span>
                                    Équipements
                                </div>
                            </td>
                            @foreach ($residences as $residence)
                                <td class="py-4 px-4">
                                    <div class="flex flex-wrap justify-center gap-1">
                                        @forelse($residence->amenities->take(5) as $amenity)
                                            <span class="px-2 py-1 bg-gray-100 text-gray-700 text-xs rounded">
                                                {{ $amenity->icon }} {{ $amenity->name }}
                                            </span>
                                        @empty
                                            <span class="text-gray-400 text-sm">Aucun</span>
                                        @endforelse
                                        @if ($residence->amenities->count() > 5)
                                            <span class="px-2 py-1 bg-[#FFE7D1] text-[#CC5A00] text-xs rounded">
                                                +{{ $residence->amenities->count() - 5 }}
                                            </span>
                                        @endif
                                    </div>
                                </td>
                            @endforeach
                            @for ($i = $residences->count(); $i < 4; $i++)
                                <td class="py-4 px-4 text-center text-gray-300">-</td>
                            @endfor
                        </tr>

                        {{-- Note --}}
                        <tr class="hover:bg-gray-50">
                            <td class="py-4 px-6 font-medium text-gray-900 bg-gray-50">
                                <div class="flex items-center gap-2">
                                    <span class="text-xl">⭐</span>
                                    Note moyenne
                                </div>
                            </td>
                            @foreach ($residences as $residence)
                                <td class="py-4 px-4 text-center">
                                    @if ($residence->reviews_count > 0)
                                        <div class="flex items-center justify-center gap-1">
                                            <span
                                                class="text-lg font-semibold text-amber-500">{{ number_format($residence->average_rating, 1) }}</span>
                                            <svg class="w-5 h-5 text-amber-400 fill-current" viewBox="0 0 24 24">
                                                <path
                                                    d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z" />
                                            </svg>
                                            <span class="text-sm text-gray-500">({{ $residence->reviews_count }})</span>
                                        </div>
                                    @else
                                        <span class="text-gray-400 text-sm">Pas d'avis</span>
                                    @endif
                                </td>
                            @endforeach
                            @for ($i = $residences->count(); $i < 4; $i++)
                                <td class="py-4 px-4 text-center text-gray-300">-</td>
                            @endfor
                        </tr>

                        {{-- Disponibilité --}}
                        <tr class="hover:bg-gray-50">
                            <td class="py-4 px-6 font-medium text-gray-900 bg-gray-50">
                                <div class="flex items-center gap-2">
                                    <span class="text-xl">📅</span>
                                    Disponibilité
                                </div>
                            </td>
                            @foreach ($residences as $residence)
                                <td class="py-4 px-4 text-center">
                                    @if ($residence->is_available)
                                        <span
                                            class="px-3 py-1 bg-green-100 text-green-700 text-sm font-medium rounded-full">
                                            Disponible
                                        </span>
                                    @else
                                        <span class="px-3 py-1 bg-red-100 text-red-700 text-sm font-medium rounded-full">
                                            Indisponible
                                        </span>
                                    @endif
                                </td>
                            @endforeach
                            @for ($i = $residences->count(); $i < 4; $i++)
                                <td class="py-4 px-4 text-center text-gray-300">-</td>
                            @endfor
                        </tr>

                        {{-- Actions --}}
                        <tr class="bg-gray-50">
                            <td class="py-4 px-6 font-medium text-gray-900">
                                Actions
                            </td>
                            @foreach ($residences as $residence)
                                <td class="py-4 px-4 text-center">
                                    <div class="flex flex-col gap-2">
                                        <a href="{{ route('residences.show', $residence) }}"
                                            class="block w-full px-4 py-2 bg-[#F16A00] hover:bg-[#CC5A00] text-white text-sm font-medium rounded-lg transition">
                                            Voir détails
                                        </a>
                                        @auth
                                            <button onclick="toggleFavorite({{ $residence->id }})"
                                                class="w-full px-4 py-2 border border-rose-300 text-rose-600 hover:bg-rose-50 text-sm font-medium rounded-lg transition">
                                                ❤️ Favoris
                                            </button>
                                        @endauth
                                    </div>
                                </td>
                            @endforeach
                            @for ($i = $residences->count(); $i < 4; $i++)
                                <td class="py-4 px-4 text-center text-gray-300">-</td>
                            @endfor
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Ajouter depuis les favoris --}}
        @if ($allFavorites->count() > 0)
            <div class="mt-8 bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <h3 class="font-semibold text-gray-900 mb-4">Ajouter depuis vos favoris</h3>
                <div class="flex flex-wrap gap-3">
                    @foreach ($allFavorites as $favorite)
                        @if (!$residences->contains('id', $favorite->residence_id))
                            <a href="{{ route('client.compare', ['residences' => array_merge($residences->pluck('id')->toArray(), [$favorite->residence_id])]) }}"
                                class="inline-flex items-center gap-2 px-4 py-2 bg-gray-100 hover:bg-[#FFE7D1] text-gray-700 hover:text-[#CC5A00] rounded-lg transition">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                </svg>
                                {{ Str::limit($favorite->residence->title, 25) }}
                            </a>
                        @endif
                    @endforeach
                </div>
            </div>
        @endif
    @else
        {{-- État vide --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-12 text-center">
            <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                </svg>
            </div>
            <h3 class="text-lg font-medium text-gray-900 mb-2">Aucune résidence à comparer</h3>
            <p class="text-gray-600 mb-6">Ajoutez des résidences à vos favoris pour pouvoir les comparer</p>
            <a href="{{ route('residences.index') }}"
                class="inline-flex items-center px-4 py-2 bg-[#F16A00] hover:bg-[#CC5A00] text-white font-medium rounded-lg transition">
                Explorer les résidences
            </a>
        </div>
    @endif
@endsection
