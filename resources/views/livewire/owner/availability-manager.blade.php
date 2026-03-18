<div class="space-y-6">
    {{-- En-tête --}}
    <div class="flex items-center justify-between">
        <h2 class="text-xl font-bold text-gray-900 dark:text-white">
            Calendrier de disponibilité
        </h2>
        <div class="text-sm text-gray-500">
            Prix par défaut: <span class="font-semibold">{{ number_format($residence->price_per_day) }} FCFA/jour</span>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Calendrier --}}
        <div class="lg:col-span-2 bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
            {{-- Navigation mois --}}
            <div class="flex items-center justify-between mb-4">
                <button wire:click="previousMonth" class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                </button>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white capitalize">
                    {{ $this->monthName }}
                </h3>
                <button wire:click="nextMonth" class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </button>
            </div>

            {{-- Jours de la semaine --}}
            <div class="grid grid-cols-7 gap-1 mb-2">
                @foreach(['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'] as $day)
                    <div class="text-center text-xs font-medium text-gray-500 dark:text-gray-400 py-2">
                        {{ $day }}
                    </div>
                @endforeach
            </div>

            {{-- Grille du calendrier --}}
            <div class="grid grid-cols-7 gap-1">
                @php
                    $firstDay = \Carbon\Carbon::parse($currentMonth)->startOfMonth();
                    $startOffset = ($firstDay->dayOfWeek + 6) % 7; // Lundi = 0
                @endphp

                {{-- Cases vides avant le 1er --}}
                @for($i = 0; $i < $startOffset; $i++)
                    <div class="h-16"></div>
                @endfor

                {{-- Jours du mois --}}
                @foreach($calendar as $day)
                    @php
                        $date = \Carbon\Carbon::parse($day['date']);
                        $isBlocked = $day['status'] === 'blocked';
                        $isBooked = $day['status'] === 'booked';
                        $isPast = $date->isPast() && !$date->isToday();
                        $hasCustomPrice = !empty($day['custom_price']);
                    @endphp
                    <div 
                        class="h-16 p-1 rounded-lg border text-xs relative
                            @if($isPast) bg-gray-50 dark:bg-gray-900 border-gray-100 dark:border-gray-800 opacity-50
                            @elseif($isBooked) bg-blue-50 dark:bg-blue-900/30 border-blue-200 dark:border-blue-800
                            @elseif($isBlocked) bg-red-50 dark:bg-red-900/30 border-red-200 dark:border-red-800
                            @elseif($hasCustomPrice) bg-amber-50 dark:bg-amber-900/30 border-amber-200 dark:border-amber-800
                            @else bg-green-50 dark:bg-green-900/30 border-green-200 dark:border-green-800
                            @endif
                        "
                    >
                        <div class="flex items-start justify-between">
                            <span class="font-medium @if($date->isToday()) text-primary-600 @endif">
                                {{ $date->day }}
                            </span>
                            @if($isBlocked && !$isPast)
                                <button 
                                    wire:click="unblockDate('{{ $day['date'] }}')"
                                    class="text-red-500 hover:text-red-700"
                                    title="Débloquer"
                                >
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                            @endif
                        </div>
                        @if($hasCustomPrice)
                            <div class="text-amber-600 dark:text-amber-400 mt-1 truncate">
                                {{ number_format($day['custom_price']) }}
                            </div>
                        @endif
                        @if($isBooked)
                            <div class="text-blue-600 dark:text-blue-400 mt-1 text-[10px]">Réservé</div>
                        @elseif($isBlocked)
                            <div class="text-red-600 dark:text-red-400 mt-1 text-[10px]">Bloqué</div>
                        @endif
                    </div>
                @endforeach
            </div>

            {{-- Légende --}}
            <div class="flex flex-wrap gap-4 mt-4 text-xs">
                <div class="flex items-center gap-1">
                    <span class="w-3 h-3 rounded bg-green-200 dark:bg-green-800"></span>
                    Disponible
                </div>
                <div class="flex items-center gap-1">
                    <span class="w-3 h-3 rounded bg-blue-200 dark:bg-blue-800"></span>
                    Réservé
                </div>
                <div class="flex items-center gap-1">
                    <span class="w-3 h-3 rounded bg-red-200 dark:bg-red-800"></span>
                    Bloqué
                </div>
                <div class="flex items-center gap-1">
                    <span class="w-3 h-3 rounded bg-amber-200 dark:bg-amber-800"></span>
                    Prix custom
                </div>
            </div>
        </div>

        {{-- Panneau de contrôle --}}
        <div class="space-y-4">
            {{-- Bloquer des dates --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
                <h4 class="font-semibold text-gray-900 dark:text-white mb-3">Bloquer des dates</h4>
                <form wire:submit="blockDates" class="space-y-3">
                    <div>
                        <label class="block text-sm text-gray-600 dark:text-gray-400 mb-1">Date début</label>
                        <input type="date" wire:model="blockStartDate" min="{{ now()->format('Y-m-d') }}"
                               class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm">
                    </div>
                    <div>
                        <label class="block text-sm text-gray-600 dark:text-gray-400 mb-1">Date fin</label>
                        <input type="date" wire:model="blockEndDate" min="{{ now()->format('Y-m-d') }}"
                               class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm">
                    </div>
                    <div>
                        <label class="block text-sm text-gray-600 dark:text-gray-400 mb-1">Note (optionnel)</label>
                        <input type="text" wire:model="blockNote" placeholder="Ex: Rénovations..."
                               class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm">
                    </div>
                    <button type="submit" class="w-full bg-red-600 hover:bg-red-700 text-white rounded-lg py-2 text-sm font-medium transition">
                        Bloquer les dates
                    </button>
                </form>
            </div>

            {{-- Prix personnalisé --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
                <h4 class="font-semibold text-gray-900 dark:text-white mb-3">Prix personnalisé</h4>
                <form wire:submit="setCustomPrice" class="space-y-3">
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-sm text-gray-600 dark:text-gray-400 mb-1">Du</label>
                            <input type="date" wire:model="priceStartDate" 
                                   class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm">
                        </div>
                        <div>
                            <label class="block text-sm text-gray-600 dark:text-gray-400 mb-1">Au</label>
                            <input type="date" wire:model="priceEndDate"
                                   class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm text-gray-600 dark:text-gray-400 mb-1">Prix/jour (FCFA)</label>
                        <input type="number" wire:model="customPrice" placeholder="{{ $residence->price_per_day }}"
                               class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm">
                    </div>
                    <button type="submit" class="w-full bg-amber-600 hover:bg-amber-700 text-white rounded-lg py-2 text-sm font-medium transition">
                        Définir le prix
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- Tarifs saisonniers --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
        <div class="flex items-center justify-between mb-4">
            <h4 class="font-semibold text-gray-900 dark:text-white">Tarifs saisonniers</h4>
            <div x-data="{ open: false }" class="relative">
                <button @click="open = !open" class="text-sm text-primary-600 hover:text-primary-700">
                    + Importer template
                </button>
                <div x-show="open" @click.away="open = false" 
                     class="absolute right-0 mt-2 w-56 bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 z-10">
                    @foreach($this->templates as $key => $template)
                        <button wire:click="importTemplate('{{ $key }}')" @click="open = false"
                                class="block w-full text-left px-4 py-2 text-sm hover:bg-gray-100 dark:hover:bg-gray-700">
                            {{ $template['name'] }}
                        </button>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Liste des tarifs existants --}}
        @if(count($seasonalPricing) > 0)
            <div class="space-y-2 mb-4">
                @foreach($seasonalPricing as $season)
                    <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                        <div>
                            <span class="font-medium text-gray-900 dark:text-white">{{ $season['name'] }}</span>
                            <span class="text-sm text-gray-500 dark:text-gray-400 ml-2">
                                {{ \Carbon\Carbon::parse($season['start_date'])->format('d/m/Y') }} - 
                                {{ \Carbon\Carbon::parse($season['end_date'])->format('d/m/Y') }}
                            </span>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="text-sm">
                                @if($season['price_per_day'])
                                    {{ number_format($season['price_per_day']) }} FCFA
                                @else
                                    {{ $season['price_multiplier'] > 1 ? '+' : '' }}{{ round(($season['price_multiplier'] - 1) * 100) }}%
                                @endif
                            </span>
                            <button wire:click="deleteSeasonalPricing({{ $season['id'] }})" 
                                    class="text-red-500 hover:text-red-700">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        {{-- Formulaire nouveau tarif --}}
        <form wire:submit="addSeasonalPricing" class="grid grid-cols-1 md:grid-cols-6 gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
            <div class="md:col-span-2">
                <input type="text" wire:model="seasonName" placeholder="Nom (ex: Haute saison)"
                       class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm">
            </div>
            <div>
                <input type="date" wire:model="seasonStartDate" 
                       class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm">
            </div>
            <div>
                <input type="date" wire:model="seasonEndDate"
                       class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm">
            </div>
            <div>
                <input type="number" wire:model="seasonMultiplier" step="0.1" min="0.1" max="5" placeholder="Mult. (1.0)"
                       class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm">
            </div>
            <div>
                <button type="submit" class="w-full bg-primary-600 hover:bg-primary-700 text-white rounded-lg py-2 text-sm font-medium transition">
                    Ajouter
                </button>
            </div>
        </form>
    </div>
</div>
