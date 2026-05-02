<div>
    {{-- Compare Bar (floating) --}}
    @if (count($residenceIds) > 0)
        <div class="fixed bottom-0 left-0 right-0 z-50 md:bottom-4 md:left-4 md:right-4 md:max-w-2xl md:mx-auto">
            <div
                class="bg-white rounded-t-2xl md:rounded-2xl shadow-2xl border border-gray-200 p-4 transition-all duration-300">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="font-semibold text-gray-900">
                        Comparer ({{ count($residenceIds) }}/{{ $maxCompare }})
                    </h3>
                    <div class="flex items-center gap-2">
                        <a href="{{ route('residences.compare') }}"
                            class="px-4 py-2 bg-[#e00b41] text-white text-sm font-medium rounded-lg hover:bg-[#b5083a] transition-colors">
                            Voir la comparaison
                        </a>
                        <button wire:click="clearComparison"
                            class="p-2 text-gray-500 hover:text-red-600 transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                        </button>
                    </div>
                </div>

                {{-- Thumbnails --}}
                <div class="flex items-center gap-2 overflow-x-auto pb-1">
                    @foreach ($residences as $residence)
                        <div class="relative shrink-0 group">
                            <img src="{{ $residence->photos->first()?->thumbnail_url ?? asset('images/placeholder.jpg') }}"
                                alt="{{ $residence->title }}"
                                class="w-16 h-16 object-cover rounded-lg border-2 border-gray-200">
                            <button wire:click="removeFromCompare({{ $residence->id }})"
                                class="absolute -top-2 -right-2 w-6 h-6 bg-red-500 text-white rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    @endforeach

                    @for ($i = count($residenceIds); $i < $maxCompare; $i++)
                        <div
                            class="w-16 h-16 border-2 border-dashed border-gray-300 rounded-lg flex items-center justify-center shrink-0">
                            <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                            </svg>
                        </div>
                    @endfor
                </div>
            </div>
        </div>
    @endif

    {{-- Full Comparison Page Content (when on compare page) --}}
    @if (request()->routeIs('residences.compare'))
        <div class="bg-gray-50 min-h-screen py-6">
            <div class="max-w-7xl mx-auto px-4">
                {{-- Header --}}
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <a href="{{ route('residences.index') }}"
                            class="inline-flex items-center text-gray-600 hover:text-[#e00b41] mb-2">
                            <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15 19l-7-7 7-7" />
                            </svg>
                            Retour aux résidences
                        </a>
                        <h1 class="text-2xl font-bold text-gray-900">Comparaison de résidences</h1>
                    </div>

                    @if (count($residenceIds) > 0)
                        <button wire:click="clearComparison"
                            class="px-4 py-2 border border-red-300 text-red-600 rounded-lg hover:bg-red-50 transition-colors">
                            Tout effacer
                        </button>
                    @endif
                </div>

                @if ($residences->isEmpty())
                    {{-- Empty State --}}
                    <div class="bg-white rounded-2xl shadow-sm p-12 text-center">
                        <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                        <h2 class="text-xl font-semibold text-gray-900 mb-2">Aucune résidence à comparer</h2>
                        <p class="text-gray-600 mb-6">Ajoutez des résidences à la comparaison depuis la page de
                            recherche.</p>
                        <a href="{{ route('residences.index') }}"
                            class="inline-flex items-center px-6 py-3 bg-[#e00b41] text-white font-medium rounded-lg hover:bg-[#b5083a] transition-colors">
                            Parcourir les résidences
                        </a>
                    </div>
                @else
                    {{-- Comparison Table --}}
                    <div class="bg-white rounded-2xl shadow-sm overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                {{-- Header with Residence Cards --}}
                                <thead>
                                    <tr class="border-b border-gray-200">
                                        <th class="p-4 text-left bg-gray-50 w-48 sticky left-0 z-10">
                                            <span class="text-sm font-medium text-gray-600">Critère</span>
                                        </th>
                                        @foreach ($residences as $residence)
                                            <th class="p-4 min-w-64">
                                                <div class="relative group">
                                                    <button wire:click="removeFromCompare({{ $residence->id }})"
                                                        class="absolute -top-2 -right-2 w-8 h-8 bg-red-500 text-white rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity z-10">
                                                        <svg class="w-5 h-5" fill="none" stroke="currentColor"
                                                            viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                        </svg>
                                                    </button>

                                                    <a href="{{ route('residences.show', $residence) }}"
                                                        class="block">
                                                        <img src="{{ $residence->photos->first()?->url ?? asset('images/placeholder.jpg') }}"
                                                            alt="{{ $residence->title }}"
                                                            class="w-full h-40 object-cover rounded-lg mb-3">
                                                        <h3 class="font-semibold text-gray-900 line-clamp-2 mb-1">
                                                            {{ $residence->title }}
                                                        </h3>
                                                        <p class="text-[#e00b41] font-bold">
                                                            {{ number_format($residence->price_per_night, 0, ',', ' ') }}
                                                            FCFA/jour
                                                        </p>
                                                    </a>
                                                </div>
                                            </th>
                                        @endforeach
                                    </tr>
                                </thead>

                                <tbody>
                                    @foreach ($comparisonData as $sectionKey => $section)
                                        {{-- Section Header --}}
                                        <tr class="bg-[#fff0f3]">
                                            <td colspan="{{ count($residences) + 1 }}"
                                                class="px-4 py-3 font-semibold text-[#8e0730]">
                                                {{ $section['label'] }}
                                            </td>
                                        </tr>

                                        @foreach ($section['rows'] as $row)
                                            <tr class="border-b border-gray-100 hover:bg-gray-50">
                                                <td
                                                    class="p-4 text-sm font-medium text-gray-700 bg-gray-50/50 sticky left-0">
                                                    @if (isset($row['icon']))
                                                        <span class="mr-2">{{ $row['icon'] }}</span>
                                                    @endif
                                                    {{ $row['label'] }}
                                                </td>
                                                @foreach ($row['values'] as $value)
                                                    <td class="p-4 text-center">
                                                        @switch($row['format'])
                                                            @case('currency')
                                                                @if ($value)
                                                                    <span class="font-medium text-gray-900">
                                                                        {{ number_format($value, 0, ',', ' ') }} FCFA
                                                                    </span>
                                                                @else
                                                                    <span class="text-gray-400">-</span>
                                                                @endif
                                                            @break

                                                            @case('rating')
                                                                @if ($value > 0)
                                                                    <div class="flex items-center justify-center gap-1">
                                                                        <svg class="w-5 h-5 text-yellow-400" fill="currentColor"
                                                                            viewBox="0 0 20 20">
                                                                            <path
                                                                                d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                                                        </svg>
                                                                        <span
                                                                            class="font-medium">{{ number_format($value, 1) }}</span>
                                                                    </div>
                                                                @else
                                                                    <span class="text-gray-400">Pas d'avis</span>
                                                                @endif
                                                            @break

                                                            @case('boolean')
                                                                @if ($value)
                                                                    <svg class="w-6 h-6 text-green-500 mx-auto" fill="none"
                                                                        stroke="currentColor" viewBox="0 0 24 24">
                                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                                            stroke-width="2" d="M5 13l4 4L19 7" />
                                                                    </svg>
                                                                @else
                                                                    <svg class="w-6 h-6 text-red-400 mx-auto" fill="none"
                                                                        stroke="currentColor" viewBox="0 0 24 24">
                                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                                            stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                                    </svg>
                                                                @endif
                                                            @break

                                                            @case('surface')
                                                                @if ($value)
                                                                    <span>{{ $value }} m²</span>
                                                                @else
                                                                    <span class="text-gray-400">-</span>
                                                                @endif
                                                            @break

                                                            @case('persons')
                                                                @if ($value)
                                                                    <span>{{ $value }}
                                                                        {{ $value > 1 ? 'personnes' : 'personne' }}</span>
                                                                @else
                                                                    <span class="text-gray-400">-</span>
                                                                @endif
                                                            @break

                                                            @case('nights')
                                                                @if ($value)
                                                                    <span>{{ $value }}
                                                                        {{ $value > 1 ? 'nuits' : 'nuit' }}</span>
                                                                @else
                                                                    <span class="text-gray-400">-</span>
                                                                @endif
                                                            @break

                                                            @default
                                                                @if ($value !== null && $value !== '')
                                                                    <span>{{ $value }}</span>
                                                                @else
                                                                    <span class="text-gray-400">-</span>
                                                                @endif
                                                        @endswitch
                                                    </td>
                                                @endforeach
                                            </tr>
                                        @endforeach
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {{-- Quick Actions --}}
                    <div class="mt-6 grid grid-cols-1 md:grid-cols-{{ count($residences) }} gap-4">
                        @foreach ($residences as $residence)
                            <div class="bg-white rounded-xl p-4 shadow-sm">
                                <h4 class="font-medium text-gray-900 mb-3 truncate">{{ $residence->title }}</h4>
                                <div class="flex flex-col gap-2">
                                    <a href="{{ route('residences.show', $residence) }}"
                                        class="w-full px-4 py-2.5 bg-gray-100 text-gray-700 text-center text-sm font-medium rounded-lg hover:bg-gray-200 transition-colors">
                                        Voir les détails
                                    </a>
                                    <a href="{{ route('bookings.create', ['residence' => $residence->id]) }}"
                                        class="w-full px-4 py-2.5 bg-[#e00b41] text-white text-center text-sm font-medium rounded-lg hover:bg-[#b5083a] transition-colors">
                                        Réserver
                                    </a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    @endif
</div>
