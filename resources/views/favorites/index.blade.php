@extends('layouts.client', ['sidebarActive' => 'favorites'])

@section('title', 'Mes favoris - Rezi Studio Meublé Faya')

@section('client-content')
    <div>
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Mes favoris</h1>
                <p class="text-gray-600 mt-1">{{ $favorites->count() }} résidence(s) sauvegardée(s)</p>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('collections.index') }}"
                    class="inline-flex items-center gap-2 px-4 py-2 border border-gray-300 rounded-xl hover:bg-gray-50 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                    </svg>
                    Mes collections
                </a>
                <a href="{{ route('history.index') }}"
                    class="inline-flex items-center gap-2 px-4 py-2 border border-gray-300 rounded-xl hover:bg-gray-50 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Historique
                </a>
            </div>
        </div>

        <!-- Filtres par collection -->
        @if ($collections->isNotEmpty())
            <div class="flex flex-wrap gap-2 mb-6">
                <a href="{{ route('favorites.index') }}"
                    class="px-4 py-2 rounded-full text-sm font-medium transition-colors {{ !$collectionId ? 'bg-[#CC5A00] text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                    Tous
                </a>
                @foreach ($collections as $collection)
                    <a href="{{ route('favorites.index', ['collection' => $collection->id]) }}"
                        class="px-4 py-2 rounded-full text-sm font-medium transition-colors {{ $collectionId == $collection->id ? 'bg-[#CC5A00] text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                        {{ $collection->name }} ({{ $collection->favorites_count }})
                    </a>
                @endforeach
            </div>
        @endif

        @if ($favorites->isEmpty())
            <!-- État vide -->
            <div class="bg-white rounded-2xl shadow-sm p-8 text-center">
                <div class="w-16 h-16 bg-red-50 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">Aucun favori</h3>
                <p class="text-gray-500 mb-4">Vous n'avez pas encore enregistré de résidence en favori.</p>
                <a href="{{ route('residences.index') }}"
                    class="inline-flex items-center gap-2 bg-[#CC5A00] text-white px-4 py-2 rounded-xl hover:bg-[#A34700] transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    Parcourir les résidences
                </a>
            </div>
        @else
            <!-- Grille des favoris -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach ($favorites as $favorite)
                    @php $residence = $favorite->residence; @endphp
                    <div class="bg-white rounded-2xl shadow-sm overflow-hidden group" x-data="{ showNotes: false }">
                        <!-- Image -->
                        <div class="relative aspect-4/3">
                            @if ($residence->primaryPhoto)
                                <img loading="lazy" src="{{ storage_url($residence->primaryPhoto->path) }}"
                                    alt="{{ $residence->title }}"
                                    class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                            @else
                                <div class="w-full h-full bg-gray-200 flex items-center justify-center">
                                    <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                                    </svg>
                                </div>
                            @endif

                            <!-- Badge favori -->
                            <form action="{{ route('favorites.toggle', $residence) }}" method="POST"
                                class="absolute top-3 right-3">
                                @csrf
                                <button type="submit"
                                    class="p-2 bg-white/90 rounded-full shadow-sm hover:bg-white transition-colors">
                                    <svg class="w-5 h-5 text-red-500" fill="currentColor" viewBox="0 0 24 24">
                                        <path
                                            d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                                    </svg>
                                </button>
                            </form>

                            <!-- Prix -->
                            <div class="absolute bottom-3 left-3">
                                <span
                                    class="bg-white/90 backdrop-blur-sm px-3 py-1 rounded-full text-sm font-semibold text-gray-900">
                                    {{ number_format($residence->price, 0, ',', ' ') }} FCFA/{{ $residence->price_label }}
                                </span>
                            </div>
                        </div>

                        <!-- Contenu -->
                        <div class="p-4">
                            <a href="{{ route('residences.show', $residence) }}" class="block">
                                <h3
                                    class="font-semibold text-gray-900 group-hover:text-[#CC5A00] transition-colors line-clamp-1">
                                    {{ $residence->title }}
                                </h3>
                            </a>

                            <p class="text-sm text-gray-500 mt-1 flex items-center gap-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                </svg>
                                {{ $residence->commune }}, {{ $residence->quartier }}
                            </p>

                            <!-- Caractéristiques -->
                            <div class="flex items-center gap-4 mt-3 text-sm text-gray-600">
                                <span class="flex items-center gap-1">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                                    </svg>
                                    {{ $residence->bedrooms }} ch.
                                </span>
                                <span class="flex items-center gap-1">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    {{ $residence->bathrooms }} sdb.
                                </span>
                                @if ($residence->surface)
                                    <span>{{ $residence->surface }} m²</span>
                                @endif
                            </div>

                            <!-- Notes personnelles -->
                            <div class="mt-3 pt-3 border-t border-gray-100">
                                <button @click="showNotes = !showNotes"
                                    class="text-sm text-[#CC5A00] hover:text-[#A34700] flex items-center gap-1">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                    {{ $favorite->notes ? 'Modifier ma note' : 'Ajouter une note' }}
                                </button>

                                <div x-show="showNotes" x-transition class="mt-2">
                                    <form action="{{ route('favorites.note', $favorite) }}" method="POST">
                                        @csrf
                                        @method('PATCH')
                                        <textarea name="notes" rows="2"
                                            class="w-full text-sm border-gray-200 rounded-lg focus:border-[#F16A00] focus:ring-[#F16A00]"
                                            placeholder="Ajouter une note personnelle...">{{ $favorite->notes }}</textarea>
                                        <button type="submit"
                                            class="mt-2 text-sm bg-[#CC5A00] text-white px-3 py-1 rounded-lg hover:bg-[#A34700] transition-colors">
                                            Enregistrer
                                        </button>
                                    </form>
                                </div>

                                @if ($favorite->notes && !isset($showNotes))
                                    <p class="text-sm text-gray-600 mt-2 italic">"{{ Str::limit($favorite->notes, 100) }}"
                                    </p>
                                @endif
                            </div>

                            <!-- Date d'ajout -->
                            <p class="text-xs text-gray-400 mt-2">
                                Ajouté {{ $favorite->created_at->diffForHumans() }}
                            </p>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Pagination -->
            <div class="mt-6">
                {{ $favorites->links() }}
            </div>
        @endif
    </div>
@endsection
