@extends('layouts.owner')

@section('title', 'Calendrier des prix - ' . $residence->name)

@section('owner-content')
    <div class="min-h-screen bg-gray-50 py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="mb-8">
                <nav class="flex items-center gap-2 text-sm text-gray-500 mb-4">
                    <a href="{{ route('owner.dashboard') }}" class="hover:text-orange-500">Tableau de bord</a>
                    <span>→</span>
                    <a href="{{ route('owner.residences.show', $residence) }}"
                        class="hover:text-orange-500">{{ $residence->name }}</a>
                    <span>→</span>
                    <span class="text-gray-900">Tarification</span>
                </nav>
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900">Calendrier des prix</h1>
                        <p class="mt-1 text-gray-600">Gérez les tarifs saisonniers et les prix spéciaux</p>
                    </div>
                    <div class="flex gap-3">
                        <a href="{{ route('owner.pricing.suggestions', $residence) }}"
                            class="inline-flex items-center gap-2 px-4 py-2 bg-purple-600 text-white rounded-xl hover:bg-purple-700 transition">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                            </svg>
                            Suggestions IA
                        </a>
                        <a href="{{ route('owner.pricing.create-season', $residence) }}"
                            class="inline-flex items-center gap-2 px-4 py-2 bg-orange-500 text-white rounded-xl hover:bg-orange-600 transition">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            Nouvelle saison
                        </a>
                    </div>
                </div>
            </div>

            <!-- Prix de base -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-8">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Prix de base</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="bg-gray-50 rounded-xl p-4">
                        <span class="text-sm text-gray-500">Par nuit</span>
                        <p class="text-2xl font-bold text-orange-500">
                            {{ number_format($residence->price_per_day, 0, ',', ' ') }} FCFA</p>
                    </div>
                    @if ($residence->price_per_week)
                        <div class="bg-gray-50 rounded-xl p-4">
                            <span class="text-sm text-gray-500">Par semaine</span>
                            <p class="text-2xl font-bold text-orange-500">
                                {{ number_format($residence->price_per_week, 0, ',', ' ') }} FCFA</p>
                            <span
                                class="text-xs text-gray-400">{{ number_format($residence->price_per_week / 7, 0, ',', ' ') }}
                                FCFA/nuit</span>
                        </div>
                    @endif
                    @if ($residence->price_per_month)
                        <div class="bg-gray-50 rounded-xl p-4">
                            <span class="text-sm text-gray-500">Par mois</span>
                            <p class="text-2xl font-bold text-orange-500">
                                {{ number_format($residence->price_per_month, 0, ',', ' ') }} FCFA</p>
                            <span
                                class="text-xs text-gray-400">{{ number_format($residence->price_per_month / 30, 0, ',', ' ') }}
                                FCFA/nuit</span>
                        </div>
                    @endif
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Calendrier -->
                <div class="lg:col-span-2">
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                        <div class="flex items-center justify-between mb-6">
                            <h2 class="text-lg font-semibold text-gray-900">Calendrier</h2>
                            <div class="flex items-center gap-4 text-sm">
                                <span class="flex items-center gap-2">
                                    <span class="w-3 h-3 bg-orange-500 rounded"></span>
                                    Disponible
                                </span>
                                <span class="flex items-center gap-2">
                                    <span class="w-3 h-3 bg-red-500 rounded"></span>
                                    Indisponible
                                </span>
                                <span class="flex items-center gap-2">
                                    <span class="w-3 h-3 bg-amber-500 rounded"></span>
                                    Prix spécial
                                </span>
                            </div>
                        </div>

                        <!-- Calendrier interactif -->
                        <div x-data="pricingCalendar(@js(['residenceId' => $residence->id, 'basePrice' => $residence->price_per_day]))" x-init="init()" class="space-y-6">
                            <!-- Navigation mois -->
                            <div class="flex items-center justify-between">
                                <button @click="previousMonth()" class="p-2 hover:bg-gray-100 rounded-lg">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M15 19l-7-7 7-7" />
                                    </svg>
                                </button>
                                <h3 class="text-lg font-semibold" x-text="currentMonthName"></h3>
                                <button @click="nextMonth()" class="p-2 hover:bg-gray-100 rounded-lg">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 5l7 7-7 7" />
                                    </svg>
                                </button>
                            </div>

                            <!-- Jours de la semaine -->
                            <div class="grid grid-cols-7 gap-1 text-center text-sm font-medium text-gray-500">
                                <div>Lun</div>
                                <div>Mar</div>
                                <div>Mer</div>
                                <div>Jeu</div>
                                <div>Ven</div>
                                <div>Sam</div>
                                <div>Dim</div>
                            </div>

                            <!-- Grille du calendrier -->
                            <div class="grid grid-cols-7 gap-1">
                                <template x-for="day in calendarDays" :key="day.date">
                                    <div @click="day.date && selectDate(day)"
                                        :class="{
                                            'bg-gray-50 cursor-not-allowed': !day.date || day.isPast,
                                            'bg-orange-50 border-orange-200 hover:bg-orange-100 cursor-pointer': day
                                                .date && !day.isPast && day.isAvailable,
                                            'bg-red-50 border-red-200 hover:bg-red-100 cursor-pointer': day.date && !day
                                                .isPast && !day.isAvailable,
                                            'bg-amber-50 border-amber-200': day.priceType === 'seasonal' || day
                                                .priceType === 'daily',
                                            'ring-2 ring-orange-500': selectedDates.includes(day.date)
                                        }"
                                        class="aspect-square p-1 border border-gray-200 rounded-lg flex flex-col items-center justify-center text-xs transition">
                                        <span x-text="day.dayNumber"
                                            :class="day.isPast ? 'text-gray-400' : 'text-gray-900 font-medium'"></span>
                                        <span x-show="day.price" x-text="formatPrice(day.price)"
                                            class="text-orange-500 font-semibold truncate w-full text-center"
                                            style="font-size: 10px;"></span>
                                    </div>
                                </template>
                            </div>

                            <!-- Actions sur sélection -->
                            <div x-show="selectedDates.length > 0" x-transition class="bg-orange-50 rounded-xl p-4 mt-4">
                                <p class="text-sm text-orange-700 mb-3">
                                    <span x-text="selectedDates.length"></span> jour(s) sélectionné(s)
                                </p>
                                <div class="flex flex-wrap gap-3">
                                    <button @click="openPriceModal()"
                                        class="px-4 py-2 bg-orange-500 text-white text-sm rounded-lg hover:bg-orange-600">
                                        Modifier le prix
                                    </button>
                                    <button @click="toggleAvailability(false)"
                                        class="px-4 py-2 bg-red-600 text-white text-sm rounded-lg hover:bg-red-700">
                                        Marquer indisponible
                                    </button>
                                    <button @click="toggleAvailability(true)"
                                        class="px-4 py-2 bg-gray-600 text-white text-sm rounded-lg hover:bg-gray-700">
                                        Marquer disponible
                                    </button>
                                    <button @click="clearSelection()"
                                        class="px-4 py-2 bg-gray-200 text-gray-700 text-sm rounded-lg hover:bg-gray-300">
                                        Annuler
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Saisons tarifaires -->
                <div>
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                        <h2 class="text-lg font-semibold text-gray-900 mb-4">Saisons tarifaires</h2>

                        @forelse($seasonalPrices as $season)
                            <div
                                class="border border-gray-200 rounded-xl p-4 mb-4 @if ($season->isCurrentlyActive()) ring-2 ring-orange-500 @endif">
                                <div class="flex items-start justify-between mb-2">
                                    <div>
                                        <h3 class="font-medium text-gray-900">{{ $season->name }}</h3>
                                        <p class="text-sm text-gray-500">
                                            {{ $season->start_date->format('d/m/Y') }} -
                                            {{ $season->end_date->format('d/m/Y') }}
                                        </p>
                                    </div>
                                    @if ($season->isCurrentlyActive())
                                        <span
                                            class="px-2 py-1 bg-orange-100 text-orange-600 text-xs rounded-full">Actif</span>
                                    @endif
                                </div>
                                <div class="text-lg font-bold text-orange-500 mb-3">
                                    {{ number_format($season->price_per_night, 0, ',', ' ') }} FCFA<span
                                        class="text-sm font-normal text-gray-500">/nuit</span>
                                </div>
                                @if ($season->min_nights > 1)
                                    <p class="text-xs text-gray-500 mb-3">Min. {{ $season->min_nights }} nuits</p>
                                @endif
                                <div class="flex gap-2">
                                    <a href="{{ route('owner.pricing.edit-season', [$residence, $season]) }}"
                                        class="text-sm text-orange-500 hover:text-orange-600">Modifier</a>
                                    <form action="{{ route('owner.pricing.destroy-season', [$residence, $season]) }}"
                                        method="POST" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-sm text-red-600 hover:text-red-700"
                                            onclick="return confirm('Supprimer cette saison ?')">
                                            Supprimer
                                        </button>
                                    </form>
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-8">
                                <div
                                    class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                </div>
                                <p class="text-gray-500 mb-4">Aucune saison tarifaire</p>
                                <a href="{{ route('owner.pricing.create-season', $residence) }}"
                                    class="text-orange-500 hover:text-orange-600 font-medium">
                                    + Créer une saison
                                </a>
                            </div>
                        @endforelse
                    </div>

                    <!-- Conseils -->
                    <div class="bg-amber-50 rounded-2xl p-6 mt-6">
                        <h3 class="font-semibold text-amber-800 mb-2">💡 Conseils</h3>
                        <ul class="text-sm text-amber-700 space-y-2">
                            <li>• Augmentez vos prix pendant les fêtes (Noël, Nouvel An)</li>
                            <li>• Offrez des réductions pour les séjours longs</li>
                            <li>• Les week-ends peuvent avoir des tarifs plus élevés</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal modification prix -->
    <div x-data="{ open: false, price: '', reason: '' }" x-show="open"
        x-on:open-price-modal.window="open = true; price = $event.detail.currentPrice"
        x-on:keydown.escape.window="open = false" x-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog"
        aria-modal="true" aria-label="Modifier le prix">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div x-show="open" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100" class="fixed inset-0 bg-black/50" @click="open = false"></div>
            <div x-show="open" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                class="relative bg-white rounded-2xl shadow-xl max-w-md w-full p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Modifier le prix</h3>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nouveau prix (FCFA/nuit)</label>
                        <input type="number" x-model="price"
                            class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-orange-500 focus:border-orange-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Raison (optionnel)</label>
                        <input type="text" x-model="reason" placeholder="Ex: Jour férié, Événement..."
                            class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-orange-500 focus:border-orange-500">
                    </div>
                </div>
                <div class="flex gap-3 mt-6">
                    <button @click="open = false"
                        class="flex-1 px-4 py-2 bg-gray-100 text-gray-700 rounded-xl hover:bg-gray-200">Annuler</button>
                    <button @click="$dispatch('confirm-price', { price, reason }); open = false"
                        class="flex-1 px-4 py-2 bg-orange-500 text-white rounded-xl hover:bg-orange-600">Confirmer</button>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    @endpush
@endsection
