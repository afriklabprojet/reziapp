<x-app-layout>
    @section('title', 'Recherches sauvegardées - Rezi App')

    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Recherches sauvegardées</h1>
                    <p class="text-gray-600 mt-1">Retrouvez rapidement vos critères de recherche</p>
                </div>
                <a href="{{ route('history.index') }}" class="inline-flex items-center gap-2 px-4 py-2 border border-gray-300 rounded-xl hover:bg-gray-50 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Historique
                </a>
            </div>

            @if($searches->isEmpty())
                <div class="bg-white rounded-2xl shadow-sm p-8 text-center">
                    <div class="w-16 h-16 bg-blue-50 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Aucune recherche sauvegardée</h3>
                    <p class="text-gray-500 mb-4">Sauvegardez vos recherches pour les retrouver facilement.</p>
                    <a href="{{ route('residences.index') }}" 
                       class="inline-flex items-center gap-2 bg-[#CC5A00] text-white px-4 py-2 rounded-xl hover:bg-[#A34700] transition-colors">
                        Lancer une recherche
                    </a>
                </div>
            @else
                <div class="space-y-4">
                    @foreach($searches as $search)
                    <div class="bg-white rounded-xl shadow-sm p-4" x-data="{ showEdit: false }">
                        <div class="flex items-start justify-between gap-4">
                            <div class="flex-1">
                                <div class="flex items-center gap-3">
                                    <h3 class="font-semibold text-gray-900">{{ $search->name }}</h3>
                                    @if($search->has_alerts)
                                        <span class="inline-flex items-center gap-1 text-xs px-2 py-1 bg-green-100 text-green-700 rounded-full">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                                            </svg>
                                            Alertes {{ $search->alert_frequency }}
                                        </span>
                                    @endif
                                    @if($search->new_results_count > 0)
                                        <span class="inline-flex items-center px-2 py-1 bg-[#FFE7D1] text-[#A34700] text-xs rounded-full">
                                            {{ $search->new_results_count }} nouveau(x)
                                        </span>
                                    @endif
                                </div>
                                
                                <p class="text-sm text-gray-600 mt-1">{{ $search->getFiltersDescription() }}</p>
                                
                                <div class="flex flex-wrap items-center gap-4 mt-3 text-xs text-gray-500">
                                    @if($search->check_in && $search->check_out)
                                        <span class="flex items-center gap-1">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                            </svg>
                                            {{ $search->check_in->format('d/m') }} - {{ $search->check_out->format('d/m/Y') }}
                                        </span>
                                    @endif
                                    @if($search->last_searched_at)
                                        <span>Dernière recherche: {{ $search->last_searched_at->diffForHumans() }}</span>
                                    @endif
                                </div>
                            </div>

                            <div class="flex items-center gap-2 shrink-0">
                                <a href="{{ route('history.saved-searches.execute', $search) }}" 
                                   class="inline-flex items-center gap-2 px-4 py-2 bg-[#CC5A00] text-white rounded-lg hover:bg-[#A34700] transition-colors text-sm">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                    </svg>
                                    Rechercher
                                </a>
                                
                                <button @click="showEdit = !showEdit" class="p-2 text-gray-400 hover:text-gray-600 transition-colors">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z" />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <!-- Edit form -->
                        <div x-show="showEdit" x-collapse class="mt-4 pt-4 border-t">
                            <form action="{{ route('history.saved-searches.update', $search) }}" method="POST" class="space-y-4">
                                @csrf
                                @method('PATCH')
                                
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Nom</label>
                                        <input type="text" name="name" value="{{ $search->name }}" required
                                               class="w-full rounded-lg border-gray-300 focus:border-[#F16A00] focus:ring-[#F16A00]">
                                    </div>
                                    <div>
                                        <label class="flex items-center gap-2">
                                            <input type="checkbox" name="has_alerts" value="1" {{ $search->has_alerts ? 'checked' : '' }}
                                                   class="rounded border-gray-300 text-[#CC5A00] focus:ring-[#F16A00]">
                                            <span class="text-sm text-gray-700">Recevoir des alertes</span>
                                        </label>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Fréquence</label>
                                        <select name="alert_frequency" class="w-full rounded-lg border-gray-300 focus:border-[#F16A00] focus:ring-[#F16A00]">
                                            <option value="instant" {{ $search->alert_frequency === 'instant' ? 'selected' : '' }}>Instantané</option>
                                            <option value="daily" {{ $search->alert_frequency === 'daily' ? 'selected' : '' }}>Quotidien</option>
                                            <option value="weekly" {{ $search->alert_frequency === 'weekly' ? 'selected' : '' }}>Hebdomadaire</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="flex items-center justify-between">
                                    <form action="{{ route('history.saved-searches.delete', $search) }}" method="POST" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"  data-confirm='Supprimer cette recherche ?'
                                                class="text-sm text-red-600 hover:text-red-700">
                                            Supprimer
                                        </button>
                                    </form>
                                    <button type="submit" class="px-4 py-2 bg-gray-900 text-white rounded-lg hover:bg-gray-800 text-sm">
                                        Enregistrer
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
