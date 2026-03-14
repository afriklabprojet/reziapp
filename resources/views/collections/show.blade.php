<x-app-layout>
    @section('title', $collection->name . ' - REZI')

    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="bg-white rounded-2xl shadow-sm p-6 mb-6">
                <div class="flex flex-col md:flex-row md:items-start gap-6">
                    <!-- Image -->
                    <div
                        class="w-full md:w-48 aspect-video rounded-xl overflow-hidden bg-linear-to-br from-orange-100 to-orange-200">
                        @if ($collection->getCoverImageUrl())
                            <img loading="lazy" src="{{ $collection->getCoverImageUrl() }}" alt="{{ $collection->name }}"
                                class="w-full h-full object-cover">
                        @else
                            <div class="w-full h-full flex items-center justify-center">
                                <svg class="w-12 h-12 text-primary-300" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                </svg>
                            </div>
                        @endif
                    </div>

                    <!-- Info -->
                    <div class="flex-1">
                        <div class="flex items-start justify-between">
                            <div>
                                <h1 class="text-2xl font-bold text-gray-900">{{ $collection->name }}</h1>
                                @if ($collection->description)
                                    <p class="text-gray-600 mt-1">{{ $collection->description }}</p>
                                @endif
                                <div class="flex items-center gap-4 mt-3 text-sm text-gray-500">
                                    <span>{{ $favorites->count() }} favori(s)</span>
                                    @if ($collection->is_public)
                                        <span class="flex items-center gap-1 text-green-600">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            Collection publique
                                        </span>
                                    @endif
                                </div>
                            </div>

                            @if ($collection->user_id === auth()->id())
                                <div class="flex items-center gap-2" x-data="{ open: false }">
                                    <button @click="open = !open" class="p-2 hover:bg-gray-100 rounded-lg"
                                        aria-label="Options de la collection" :aria-expanded="open">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                            aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z" />
                                        </svg>
                                    </button>
                                    <div x-show="open" @click.away="open = false"
                                        class="absolute right-0 mt-32 w-48 bg-white rounded-xl shadow-lg border z-10">
                                        <button
                                            onclick="document.getElementById('editModal').classList.remove('hidden')"
                                            class="w-full text-left px-4 py-2 hover:bg-gray-50 text-sm">
                                            Modifier
                                        </button>
                                        <button onclick="copyShareLink('{{ $collection->getShareUrl() }}')"
                                            class="w-full text-left px-4 py-2 hover:bg-gray-50 text-sm">
                                            Partager
                                        </button>
                                        <form action="{{ route('collections.destroy', $collection) }}" method="POST">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                onclick="return confirm('Supprimer cette collection ?')"
                                                class="w-full text-left px-4 py-2 hover:bg-red-50 text-sm text-red-600">
                                                Supprimer
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            @endif
                        </div>

                        @if ($collection->is_public)
                            <div class="mt-4 p-3 bg-gray-50 rounded-xl">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Lien de partage</label>
                                <div class="flex items-center gap-2">
                                    <input type="text" readonly value="{{ $collection->getShareUrl() }}"
                                        class="flex-1 text-sm rounded-lg border-gray-300 bg-white" id="shareUrl">
                                    <button onclick="copyShareLink('{{ $collection->getShareUrl() }}')"
                                        class="p-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700"
                                        aria-label="Copier le lien de partage">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                            aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            @if ($favorites->isEmpty())
                <div class="bg-white rounded-2xl shadow-sm p-8 text-center">
                    <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Collection vide</h3>
                    <p class="text-gray-500 mb-4">Ajoutez des favoris à cette collection.</p>
                    <a href="{{ route('residences.index') }}"
                        class="inline-flex items-center gap-2 bg-orange-600 text-white px-4 py-2 rounded-xl hover:bg-orange-700 transition-colors">
                        Parcourir les résidences
                    </a>
                </div>
            @else
                <!-- Grille des favoris -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach ($favorites as $favorite)
                        @php $residence = $favorite->residence; @endphp
                        <div class="bg-white rounded-2xl shadow-sm overflow-hidden group">
                            <!-- Image -->
                            <div class="relative aspect-4/3">
                                @if ($residence->photos->first())
                                    <img loading="lazy" src="{{ storage_url($residence->photos->first()?->path) }}"
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
                            </div>

                            <div class="p-4">
                                <a href="{{ route('residences.show', $residence) }}" class="hover:text-orange-600">
                                    <h3 class="font-semibold text-gray-900">{{ $residence->title }}</h3>
                                </a>
                                <p class="text-sm text-gray-500 mt-1">{{ $residence->quartier->name ?? '' }}</p>
                                <p class="text-orange-600 font-semibold mt-2">
                                    {{ number_format($residence->price, 0, ',', ' ') }} FCFA<span
                                        class="text-gray-400 font-normal">/{{ $residence->price_label }}</span>
                                </p>

                                @if ($favorite->notes)
                                    <p class="text-sm text-gray-600 mt-2 p-2 bg-yellow-50 rounded-lg">
                                        📝 {{ $favorite->notes }}
                                    </p>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

</x-app-layout>
