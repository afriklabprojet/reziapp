<x-app-layout>
    @section('title', 'Historique des vues - REZI')

    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Historique des vues</h1>
                    <p class="text-gray-600 mt-1">Résidences que vous avez consultées récemment</p>
                </div>
                <div class="flex items-center gap-3">
                    <a href="{{ route('history.price-alerts') }}"
                        class="inline-flex items-center gap-2 px-4 py-2 border border-gray-300 rounded-xl hover:bg-gray-50 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                        </svg>
                        Alertes de prix
                    </a>
                    <a href="{{ route('history.saved-searches') }}"
                        class="inline-flex items-center gap-2 px-4 py-2 border border-gray-300 rounded-xl hover:bg-gray-50 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        Recherches sauvegardées
                    </a>
                    @if ($history->isNotEmpty())
                        <form action="{{ route('history.clear') }}" method="POST" class="inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" onclick="return confirm('Effacer tout l\'historique ?')"
                                class="inline-flex items-center gap-2 px-4 py-2 text-red-600 border border-red-200 rounded-xl hover:bg-red-50 transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                                Effacer
                            </button>
                        </form>
                    @endif
                </div>
            </div>

            @if ($history->isEmpty())
                <div class="bg-white rounded-2xl shadow-sm p-8 text-center">
                    <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Aucun historique</h3>
                    <p class="text-gray-500 mb-4">Vous n'avez pas encore consulté de résidences.</p>
                    <a href="{{ route('residences.index') }}"
                        class="inline-flex items-center gap-2 bg-[#CC5A00] text-white px-4 py-2 rounded-xl hover:bg-[#A34700] transition-colors">
                        Parcourir les résidences
                    </a>
                </div>
            @else
                <div class="space-y-4">
                    @php
                        $groupedHistory = $history->groupBy(function ($item) {
                            if ($item->last_viewed_at->isToday()) {
                                return 'Aujourd\'hui';
                            }
                            if ($item->last_viewed_at->isYesterday()) {
                                return 'Hier';
                            }
                            if ($item->last_viewed_at->isCurrentWeek()) {
                                return 'Cette semaine';
                            }
                            return 'Plus ancien';
                        });
                    @endphp

                    @foreach ($groupedHistory as $period => $items)
                        <div>
                            <h2 class="text-lg font-semibold text-gray-900 mb-3">{{ $period }}</h2>
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                @foreach ($items as $item)
                                    @php $residence = $item->residence; @endphp
                                    <a href="{{ route('residences.show', $residence) }}"
                                        class="bg-white rounded-xl shadow-sm overflow-hidden flex hover:shadow-md transition-shadow">
                                        <!-- Image -->
                                        <div class="w-24 h-24 shrink-0">
                                            @if ($residence->photos->first())
                                                <img loading="lazy"
                                                    src="{{ storage_url($residence->photos->first()?->path) }}"
                                                    alt="{{ $residence->title }}" class="w-full h-full object-cover">
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

                                        <div class="flex-1 p-3 min-w-0">
                                            <h3 class="font-medium text-gray-900 truncate">{{ $residence->title }}</h3>
                                            <p class="text-sm text-gray-500">{{ $residence->quartier->name ?? '' }}</p>
                                            <div class="flex items-center justify-between mt-2">
                                                <span class="text-[#CC5A00] font-semibold text-sm">
                                                    {{ number_format($residence->price, 0, ',', ' ') }}
                                                    FCFA/{{ $residence->price_label }}
                                                </span>
                                                <span class="text-xs text-gray-400">
                                                    {{ $item->view_count }} vue(s) •
                                                    {{ $item->getFormattedDuration() }}
                                                </span>
                                            </div>
                                        </div>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
