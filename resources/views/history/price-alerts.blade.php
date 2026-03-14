<x-app-layout>
    @section('title', 'Alertes de prix - REZI')

    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Alertes de prix</h1>
                    <p class="text-gray-600 mt-1">Soyez notifié quand les prix baissent</p>
                </div>
                <a href="{{ route('history.index') }}" class="inline-flex items-center gap-2 px-4 py-2 border border-gray-300 rounded-xl hover:bg-gray-50 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Historique
                </a>
            </div>

            @if($alerts->isEmpty())
                <div class="bg-white rounded-2xl shadow-sm p-8 text-center">
                    <div class="w-16 h-16 bg-yellow-50 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Aucune alerte de prix</h3>
                    <p class="text-gray-500 mb-4">Activez des alertes sur vos résidences préférées pour être notifié des baisses de prix.</p>
                    <a href="{{ route('residences.index') }}" 
                       class="inline-flex items-center gap-2 bg-orange-600 text-white px-4 py-2 rounded-xl hover:bg-orange-700 transition-colors">
                        Parcourir les résidences
                    </a>
                </div>
            @else
                <div class="space-y-4">
                    @foreach($alerts as $alert)
                    @php $residence = $alert->residence; @endphp
                    <div class="bg-white rounded-xl shadow-sm p-4 flex items-start gap-4 {{ !$alert->is_active ? 'opacity-60' : '' }}">
                        <!-- Image -->
                        <div class="w-20 h-20 rounded-lg overflow-hidden shrink-0">
                            @if($residence->photos->first())
                                <img loading="lazy" src="{{ storage_url($residence->photos->first()?->path) }}" 
                                     alt="{{ $residence->title }}" 
                                     class="w-full h-full object-cover">
                            @else
                                <div class="w-full h-full bg-gray-200 flex items-center justify-center">
                                    <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                                    </svg>
                                </div>
                            @endif
                        </div>

                        <!-- Info -->
                        <div class="flex-1 min-w-0">
                            <a href="{{ route('residences.show', $residence) }}" class="font-semibold text-gray-900 hover:text-orange-600">
                                {{ $residence->title }}
                            </a>
                            <p class="text-sm text-gray-500">{{ $residence->quartier->name ?? '' }}</p>
                            
                            <div class="flex flex-wrap items-center gap-4 mt-2">
                                <!-- Prix original -->
                                <div class="text-sm">
                                    <span class="text-gray-500">Prix initial:</span>
                                    <span class="font-medium">{{ number_format($alert->original_price, 0, ',', ' ') }} FCFA</span>
                                </div>
                                
                                <!-- Prix actuel -->
                                <div class="text-sm">
                                    <span class="text-gray-500">Prix actuel:</span>
                                    <span class="font-medium {{ $alert->hasPriceDropped() ? 'text-green-600' : '' }}">
                                        {{ number_format($alert->current_price, 0, ',', ' ') }} FCFA
                                    </span>
                                </div>
                                
                                <!-- Variation -->
                                @if($alert->price_change != 0)
                                <div class="text-sm">
                                    @if($alert->hasPriceDropped())
                                        <span class="inline-flex items-center gap-1 text-green-600">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3" />
                                            </svg>
                                            {{ abs($alert->getPriceChangePercentage()) }}%
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 text-red-600">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18" />
                                            </svg>
                                            +{{ $alert->getPriceChangePercentage() }}%
                                        </span>
                                    @endif
                                </div>
                                @endif

                                <!-- Type d'alerte -->
                                <span class="text-xs px-2 py-1 rounded-full bg-gray-100 text-gray-600">
                                    @switch($alert->alert_type)
                                        @case('decrease_only') Baisse uniquement @break
                                        @case('any_change') Tout changement @break
                                        @case('target_reached') Prix cible @break
                                    @endswitch
                                </span>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="shrink-0">
                            @if($alert->is_active)
                            <form action="{{ route('history.price-alerts.deactivate', $alert->id) }}" method="POST">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="p-2 text-gray-400 hover:text-red-600 transition-colors" title="Désactiver" aria-label="Désactiver l'alerte prix">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </button>
                            </form>
                            @else
                            <span class="text-xs text-gray-400">Désactivée</span>
                            @endif
                        </div>
                    </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
