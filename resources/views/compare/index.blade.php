<x-app-layout>
    @section('title', 'Comparer les résidences - REZI')

    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Comparer les résidences</h1>
                    <p class="text-gray-600 mt-1">{{ $residences->count() }} résidence(s) à comparer</p>
                </div>
                <div class="flex items-center gap-3">
                    @if ($residences->isNotEmpty())
                        <button onclick="copyToClipboard('{{ $comparison->getShareUrl() }}')"
                            class="inline-flex items-center gap-2 px-4 py-2 border border-gray-300 rounded-xl hover:bg-gray-50 transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z" />
                            </svg>
                            Partager
                        </button>
                        <form action="{{ route('compare.clear') }}" method="POST" class="inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                class="inline-flex items-center gap-2 px-4 py-2 text-red-600 border border-red-200 rounded-xl hover:bg-red-50 transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                                Vider
                            </button>
                        </form>
                    @endif
                </div>
            </div>

            @if ($residences->isEmpty())
                <div class="bg-white rounded-2xl shadow-sm p-8 text-center">
                    <div class="w-16 h-16 bg-[#fff0f3] rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-[#ff4d6d]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Aucune résidence à comparer</h3>
                    <p class="text-gray-500 mb-4">Ajoutez des résidences à la comparaison depuis leur page de détail.
                    </p>
                    <a href="{{ route('residences.index') }}"
                        class="inline-flex items-center gap-2 bg-[#e00b41] text-white px-4 py-2 rounded-xl hover:bg-[#b5083a] transition-colors">
                        Parcourir les résidences
                    </a>
                </div>
            @else
                <!-- Table de comparaison -->
                <div class="bg-white rounded-2xl shadow-sm overflow-hidden">
                    <p class="sm:hidden text-xs text-gray-400 px-4 pt-3 mb-0">← Faites défiler pour comparer →</p>
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[640px]">
                            <!-- Header avec images -->
                            <thead>
                                <tr>
                                    <th class="w-32 sm:w-48 p-2 sm:p-4 text-left bg-gray-50 sticky left-0"></th>
                                    @foreach ($residences as $residence)
                                        <th class="p-2 sm:p-4 text-center min-w-[160px]">
                                            <div class="relative">
                                                <!-- Image -->
                                                <div class="aspect-4/3 rounded-xl overflow-hidden mb-3">
                                                    @if ($residence->photos->first())
                                                        <img loading="lazy"
                                                            src="{{ storage_url($residence->photos->first()?->path) }}"
                                                            alt="{{ $residence->title }}"
                                                            class="w-full h-full object-cover">
                                                    @else
                                                        <div
                                                            class="w-full h-full bg-gray-200 flex items-center justify-center">
                                                            <svg class="w-8 h-8 text-gray-400" fill="none"
                                                                stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    stroke-width="2"
                                                                    d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                                                            </svg>
                                                        </div>
                                                    @endif
                                                </div>
                                                <!-- Remove button -->
                                                <form action="{{ route('compare.remove', $residence->id) }}"
                                                    method="POST" class="absolute top-2 right-2">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit"
                                                        class="p-1 bg-white rounded-full shadow hover:bg-red-50">
                                                        <svg class="w-4 h-4 text-red-500" fill="none"
                                                            stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                        </svg>
                                                    </button>
                                                </form>
                                            </div>
                                            <a href="{{ route('residences.show', $residence) }}"
                                                class="font-semibold text-gray-900 hover:text-[#e00b41]">
                                                {{ Str::limit($residence->title, 30) }}
                                            </a>
                                        </th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <!-- Prix -->
                                <tr>
                                    <td class="p-4 font-medium text-gray-700 bg-gray-50 sticky left-0">Prix / jour</td>
                                    @foreach ($residences as $residence)
                                        <td class="p-4 text-center">
                                            <span class="text-lg font-bold text-[#e00b41]">
                                                {{ number_format($residence->price, 0, ',', ' ') }}
                                                FCFA/{{ $residence->price_label }}
                                            </span>
                                        </td>
                                    @endforeach
                                </tr>

                                <!-- Localisation -->
                                <tr>
                                    <td class="p-4 font-medium text-gray-700 bg-gray-50 sticky left-0">Localisation</td>
                                    @foreach ($residences as $residence)
                                        <td class="p-4 text-center text-gray-600">
                                            {{ $residence->quartier->name ?? '-' }}
                                        </td>
                                    @endforeach
                                </tr>

                                <!-- Type -->
                                <tr>
                                    <td class="p-4 font-medium text-gray-700 bg-gray-50 sticky left-0">Type</td>
                                    @foreach ($residences as $residence)
                                        <td class="p-4 text-center text-gray-600">
                                            {{ $residence->type ?? '-' }}
                                        </td>
                                    @endforeach
                                </tr>

                                <!-- Capacité -->
                                <tr>
                                    <td class="p-4 font-medium text-gray-700 bg-gray-50 sticky left-0">Capacité</td>
                                    @foreach ($residences as $residence)
                                        <td class="p-4 text-center text-gray-600">
                                            {{ $residence->max_guests ?? '-' }} voyageur(s)
                                        </td>
                                    @endforeach
                                </tr>

                                <!-- Chambres -->
                                <tr>
                                    <td class="p-4 font-medium text-gray-700 bg-gray-50 sticky left-0">Chambres</td>
                                    @foreach ($residences as $residence)
                                        <td class="p-4 text-center text-gray-600">
                                            {{ $residence->bedrooms ?? '-' }}
                                        </td>
                                    @endforeach
                                </tr>

                                <!-- Salles de bain -->
                                <tr>
                                    <td class="p-4 font-medium text-gray-700 bg-gray-50 sticky left-0">Salles de bain
                                    </td>
                                    @foreach ($residences as $residence)
                                        <td class="p-4 text-center text-gray-600">
                                            {{ $residence->bathrooms ?? '-' }}
                                        </td>
                                    @endforeach
                                </tr>

                                <!-- Note -->
                                <tr>
                                    <td class="p-4 font-medium text-gray-700 bg-gray-50 sticky left-0">Note</td>
                                    @foreach ($residences as $residence)
                                        <td class="p-4 text-center">
                                            @if ($residence->average_rating)
                                                <span class="inline-flex items-center gap-1">
                                                    <svg class="w-4 h-4 text-yellow-400" fill="currentColor"
                                                        viewBox="0 0 20 20">
                                                        <path
                                                            d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                                    </svg>
                                                    {{ number_format($residence->average_rating, 1) }}
                                                </span>
                                            @else
                                                <span class="text-gray-400">-</span>
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>

                                <!-- Équipements -->
                                <tr>
                                    <td class="p-4 font-medium text-gray-700 bg-gray-50 sticky left-0">Équipements</td>
                                    @foreach ($residences as $residence)
                                        <td class="p-4 text-center">
                                            <div class="flex flex-wrap justify-center gap-1">
                                                @foreach ($residence->amenities->take(5) as $amenity)
                                                    <span
                                                        class="text-xs px-2 py-1 bg-gray-100 rounded-full">{{ $amenity->name }}</span>
                                                @endforeach
                                                @if ($residence->amenities->count() > 5)
                                                    <span
                                                        class="text-xs px-2 py-1 bg-gray-100 rounded-full">+{{ $residence->amenities->count() - 5 }}</span>
                                                @endif
                                            </div>
                                        </td>
                                    @endforeach
                                </tr>

                                <!-- Actions -->
                                <tr>
                                    <td class="p-4 bg-gray-50 sticky left-0"></td>
                                    @foreach ($residences as $residence)
                                        <td class="p-4 text-center">
                                            <a href="{{ route('residences.show', $residence) }}"
                                                class="inline-flex items-center gap-2 px-4 py-2 bg-[#e00b41] text-white rounded-lg hover:bg-[#b5083a] text-sm">
                                                Voir détails
                                            </a>
                                        </td>
                                    @endforeach
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>
    </div>

</x-app-layout>
