@extends('layouts.client', ['sidebarActive' => 'search-history'])

@section('title', 'Historique de recherche - Rezi App')

@section('client-content')
    {{-- En-tête --}}
    <div class="mb-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Historique de recherche</h1>
            <p class="text-gray-600">Retrouvez et relancez vos recherches précédentes</p>
        </div>
        @if ($searches->count() > 0)
            <form action="{{ route('client.search-history.clear') }}" method="POST"
                 data-confirm='Supprimer tout l\'historique ?'>
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
        {{-- Sidebar statistiques --}}
        <div class="lg:col-span-1 space-y-6">
            {{-- Stats --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <h3 class="font-semibold text-gray-900 mb-4">Statistiques</h3>
                <div class="space-y-4">
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600">Total recherches</span>
                        <span class="font-semibold text-gray-900">{{ $searchStats['total'] }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600">Ce mois</span>
                        <span class="font-semibold text-gray-900">{{ $searchStats['this_month'] }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600">Moy. résultats</span>
                        <span class="font-semibold text-gray-900">{{ $searchStats['avg_results'] }}</span>
                    </div>
                </div>
            </div>

            {{-- Communes populaires --}}
            @if ($topCommunes->count() > 0)
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <h3 class="font-semibold text-gray-900 mb-4">Communes recherchées</h3>
                    <div class="space-y-3">
                        @foreach ($topCommunes as $commune)
                            <a href="{{ route('residences.index', ['commune' => $commune->commune]) }}"
                                class="flex items-center justify-between p-2 rounded-lg hover:bg-gray-50 transition">
                                <span class="text-gray-700">{{ $commune->commune }}</span>
                                <span class="px-2 py-1 bg-[#FFE7D1] text-[#CC5A00] text-xs font-medium rounded-full">
                                    {{ $commune->count }}
                                </span>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        {{-- Liste des recherches --}}
        <div class="lg:col-span-3">
            @if ($searches->count() > 0)
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="divide-y divide-gray-100">
                        @foreach ($searches as $search)
                            <div class="p-4 hover:bg-gray-50 transition">
                                <div class="flex items-center justify-between gap-4">
                                    <div class="flex items-center gap-4 flex-1 min-w-0">
                                        <div
                                            class="w-12 h-12 bg-[#FFE7D1] rounded-lg flex items-center justify-center shrink-0">
                                            <svg class="w-6 h-6 text-[#F16A00]" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                            </svg>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <h3 class="font-medium text-gray-900">{{ $search->description }}</h3>
                                            <div class="flex flex-wrap gap-2 mt-1">
                                                @if ($search->commune)
                                                    <span
                                                        class="px-2 py-0.5 bg-blue-100 text-blue-700 text-xs rounded">{{ $search->commune }}</span>
                                                @endif
                                                @if ($search->type)
                                                    <span
                                                        class="px-2 py-0.5 bg-purple-100 text-purple-700 text-xs rounded">{{ ucfirst($search->type) }}</span>
                                                @endif
                                                @if ($search->bedrooms)
                                                    <span
                                                        class="px-2 py-0.5 bg-amber-100 text-amber-700 text-xs rounded">{{ $search->bedrooms }}
                                                        ch.</span>
                                                @endif
                                            </div>
                                            <p class="text-sm text-gray-500 mt-1">
                                                {{ $search->results_count }} résultats •
                                                {{ $search->created_at->diffForHumans() }}
                                            </p>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <a href="{{ $search->search_url }}"
                                            class="inline-flex items-center px-4 py-2 bg-[#F16A00] hover:bg-[#CC5A00] text-white text-sm font-medium rounded-lg transition">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                            </svg>
                                            Relancer
                                        </a>
                                        <form action="{{ route('client.search-history.save-alert', $search) }}" method="POST" class="inline">
                                            @csrf
                                            <button type="submit"
                                                class="inline-flex items-center p-2 text-purple-500 hover:text-purple-700 hover:bg-purple-50 rounded-lg transition"
                                                title="Créer une alerte">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
                                                </svg>
                                            </button>
                                        </form>
                                        <form action="{{ route('client.search-history.delete', $search) }}" method="POST"
                                            class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="p-2 text-gray-400 hover:text-red-600 transition">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Pagination --}}
                <div class="mt-6">
                    {{ $searches->links() }}
                </div>
            @else
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-12 text-center">
                    <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">Aucune recherche enregistrée</h3>
                    <p class="text-gray-600 mb-6">Vos recherches seront automatiquement sauvegardées ici</p>
                    <a href="{{ route('residences.index') }}"
                        class="inline-flex items-center px-4 py-2 bg-[#F16A00] hover:bg-[#CC5A00] text-white font-medium rounded-lg transition">
                        Faire une recherche
                    </a>
                </div>
            @endif
        </div>
    </div>
@endsection
