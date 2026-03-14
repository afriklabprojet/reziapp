@extends('layouts.client', ['sidebarActive' => 'view-history'])

@section('title', 'Historique des visites - REZI')

@section('client-content')
    {{-- En-tête --}}
    <div class="mb-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Historique des visites</h1>
            <p class="text-gray-600">Retrouvez toutes les résidences que vous avez consultées</p>
        </div>
        @if ($views->count() > 0)
            <form action="{{ route('client.view-history.clear') }}" method="POST"
                onsubmit="return confirm('Supprimer tout l\'historique des visites ?')">
                @csrf
                @method('DELETE')
                <button type="submit"
                    class="inline-flex items-center px-3 py-2 text-sm font-medium text-red-600 hover:text-red-700 bg-red-50 hover:bg-red-100 rounded-lg transition">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                    Effacer tout
                </button>
            </form>
        @endif
    </div>

    <div class="grid lg:grid-cols-4 gap-8">
        {{-- Sidebar --}}
        <div class="lg:col-span-1 space-y-6">
            {{-- Statistiques --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <h3 class="font-semibold text-gray-900 mb-4">Statistiques</h3>
                <div class="space-y-4">
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600">Total visites</span>
                        <span class="font-semibold text-gray-900">{{ $viewStats['total'] }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600">Cette semaine</span>
                        <span class="font-semibold text-gray-900">{{ $viewStats['this_week'] }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600">Contactés</span>
                        <span class="font-semibold text-orange-500">{{ $viewStats['contacted'] }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600">Ajoutés aux favoris</span>
                        <span class="font-semibold text-rose-600">{{ $viewStats['favorited'] }}</span>
                    </div>
                </div>
            </div>

            {{-- Communes visitées --}}
            @if ($topViewedCommunes->count() > 0)
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <h3 class="font-semibold text-gray-900 mb-4">Communes explorées</h3>
                    <div class="space-y-3">
                        @foreach ($topViewedCommunes as $commune)
                            <a href="{{ route('residences.index', ['commune' => $commune->commune]) }}"
                                class="flex items-center justify-between p-2 rounded-lg hover:bg-gray-50 transition">
                                <span class="text-gray-700">{{ $commune->commune }}</span>
                                <span class="px-2 py-1 bg-purple-100 text-purple-700 text-xs font-medium rounded-full">
                                    {{ $commune->count }}
                                </span>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Filtrer par source --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <h3 class="font-semibold text-gray-900 mb-4">Filtrer par source</h3>
                <div class="space-y-2">
                    <a href="{{ route('client.view-history') }}"
                        class="block px-3 py-2 rounded-lg {{ !request('source') ? 'bg-orange-100 text-orange-600' : 'text-gray-700 hover:bg-gray-50' }} transition">
                        Toutes les sources
                    </a>
                    <a href="{{ route('client.view-history', ['source' => 'search']) }}"
                        class="block px-3 py-2 rounded-lg {{ request('source') == 'search' ? 'bg-orange-100 text-orange-600' : 'text-gray-700 hover:bg-gray-50' }} transition">
                        Depuis la recherche
                    </a>
                    <a href="{{ route('client.view-history', ['source' => 'map']) }}"
                        class="block px-3 py-2 rounded-lg {{ request('source') == 'map' ? 'bg-orange-100 text-orange-600' : 'text-gray-700 hover:bg-gray-50' }} transition">
                        Depuis la carte
                    </a>
                    <a href="{{ route('client.view-history', ['source' => 'recommendation']) }}"
                        class="block px-3 py-2 rounded-lg {{ request('source') == 'recommendation' ? 'bg-orange-100 text-orange-600' : 'text-gray-700 hover:bg-gray-50' }} transition">
                        Recommandations
                    </a>
                </div>
            </div>
        </div>

        {{-- Liste des visites --}}
        <div class="lg:col-span-3">
            @if ($views->count() > 0)
                <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach ($views as $view)
                        @if ($view->residence)
                            <div
                                class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-md transition group">
                                <a href="{{ route('residences.show', $view->residence) }}" class="block">
                                    <div class="relative aspect-4/3">
                                        @if ($view->residence->photos->count() > 0)
                                            <img loading="lazy"
                                                src="{{ storage_url($view->residence->photos->first()?->path) }}"
                                                alt="{{ $view->residence->name }}"
                                                class="w-full h-full object-cover group-hover:scale-105 transition duration-300">
                                        @else
                                            <div class="w-full h-full bg-gray-200 flex items-center justify-center">
                                                <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                                                </svg>
                                            </div>
                                        @endif

                                        {{-- Badges --}}
                                        <div class="absolute top-2 left-2 flex flex-wrap gap-1">
                                            @if ($view->contacted)
                                                <span
                                                    class="px-2 py-0.5 bg-orange-500 text-white text-xs font-medium rounded">Contacté</span>
                                            @endif
                                            @if ($view->favorited)
                                                <span
                                                    class="px-2 py-0.5 bg-rose-500 text-white text-xs font-medium rounded">Favori</span>
                                            @endif
                                        </div>

                                        {{-- Prix --}}
                                        <div
                                            class="absolute bottom-2 right-2 px-2 py-1 bg-black/70 text-white text-sm font-medium rounded">
                                            {{ number_format($view->residence->price, 0, ',', ' ') }} FCFA
                                        </div>
                                    </div>
                                </a>

                                <div class="p-4">
                                    <a href="{{ route('residences.show', $view->residence) }}" class="block">
                                        <h3 class="font-medium text-gray-900 truncate group-hover:text-orange-500">
                                            {{ $view->residence->title }}</h3>
                                        <p class="text-sm text-gray-500 mt-1">{{ $view->residence->commune }}</p>
                                    </a>

                                    <div class="flex items-center justify-between mt-3 pt-3 border-t border-gray-100">
                                        <div class="flex items-center gap-1 text-xs text-gray-500">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            {{ $view->created_at->diffForHumans() }}
                                        </div>

                                        <div class="flex items-center gap-1">
                                            @if ($view->source == 'search')
                                                <span
                                                    class="px-2 py-0.5 bg-blue-100 text-blue-700 text-xs rounded">Recherche</span>
                                            @elseif($view->source == 'map')
                                                <span
                                                    class="px-2 py-0.5 bg-green-100 text-green-700 text-xs rounded">Carte</span>
                                            @elseif($view->source == 'recommendation')
                                                <span
                                                    class="px-2 py-0.5 bg-purple-100 text-purple-700 text-xs rounded">Suggestion</span>
                                            @else
                                                <span
                                                    class="px-2 py-0.5 bg-gray-100 text-gray-700 text-xs rounded">Direct</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>

                {{-- Pagination --}}
                <div class="mt-6">
                    {{ $views->links() }}
                </div>
            @else
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-12 text-center">
                    <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">Aucune visite enregistrée</h3>
                    <p class="text-gray-600 mb-6">Explorez les résidences pour commencer à constituer votre historique</p>
                    <a href="{{ route('residences.index') }}"
                        class="inline-flex items-center px-4 py-2 bg-orange-500 hover:bg-orange-600 text-white font-medium rounded-lg transition">
                        Explorer les résidences
                    </a>
                </div>
            @endif
        </div>
    </div>
@endsection
