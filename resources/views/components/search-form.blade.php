@props(['filters' => []])

<div x-data="searchForm({{ \Illuminate\Support\Js::encode(['latitude' => $filters['latitude'] ?? null, 'longitude' => $filters['longitude'] ?? null, 'radius' => $filters['radius'] ?? 5]) }})" class="bg-white rounded-lg shadow-lg p-4 sm:p-6">
    <form method="GET" action="{{ route('residences.search') }}" class="space-y-4">
        <!-- Recherche par adresse (avec autocomplete Google Maps) -->
        <div>
            <label for="location" class="block text-sm font-medium text-gray-700 mb-2">
                📍 Localisation
            </label>
            <input type="text" id="location-search" placeholder="Entrez une adresse..." class="input-field"
                x-ref="locationInput">
            <input type="hidden" name="latitude" x-model="latitude">
            <input type="hidden" name="longitude" x-model="longitude">
        </div>

        <!-- Rayon de recherche -->
        <div>
            <label for="radius" class="block text-sm font-medium text-gray-700 mb-2">
                📏 Rayon de recherche: <span x-text="radius + ' km'"></span>
            </label>
            <input type="range" name="radius" min="1" max="20" step="1" x-model="radius"
                class="w-full h-3 bg-gray-200 rounded-lg appearance-none cursor-pointer accent-blue-600">
            <div class="flex justify-between text-xs text-gray-500 mt-1">
                <span>1 km</span>
                <span>20 km</span>
            </div>
        </div>

        <!-- Filtres avancés (toggle) -->
        <div>
            <button type="button" @click="showFilters = !showFilters"
                class="text-blue-600 font-medium text-sm flex items-center">
                <svg aria-hidden="true" class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4" />
                </svg>
                <span x-text="showFilters ? 'Masquer les filtres' : 'Plus de filtres'"></span>
            </button>
        </div>

        <!-- Filtres avancés -->
        <div x-show="showFilters" x-collapse class="space-y-4 pt-4 border-t">
            <!-- Prix -->
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="price_min" class="block text-sm font-medium text-gray-700 mb-2">
                        Prix minimum
                    </label>
                    <input type="number" name="price_min" id="price_min" placeholder="0" class="input-field"
                        value="{{ $filters['price_min'] ?? '' }}">
                </div>
                <div>
                    <label for="price_max" class="block text-sm font-medium text-gray-700 mb-2">
                        Prix maximum
                    </label>
                    <input type="number" name="price_max" id="price_max" placeholder="1000000" class="input-field"
                        value="{{ $filters['price_max'] ?? '' }}">
                </div>
            </div>

            <!-- Type de logement -->
            <div>
                <p class="block text-sm font-medium text-gray-700 mb-2">
                    Type de logement
                </p>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                    @foreach (['studio', 'apartment', 'house', 'villa'] as $type)
                        <label class="flex items-center space-x-2 cursor-pointer min-h-11 py-2">
                            <input type="radio" name="type" value="{{ $type }}"
                                class="w-5 h-5 text-blue-600 focus:ring-blue-500"
                                {{ ($filters['type'] ?? '') === $type ? 'checked' : '' }}>
                            <span class="text-sm">{{ ucfirst($type) }}</span>
                        </label>
                    @endforeach
                </div>
            </div>

            <!-- Chambres et salles de bain -->
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="bedrooms" class="block text-sm font-medium text-gray-700 mb-2">
                        Chambres min.
                    </label>
                    <select name="bedrooms" id="bedrooms" class="input-field">
                        <option value="">Toutes</option>
                        @for ($i = 1; $i <= 5; $i++)
                            <option value="{{ $i }}"
                                {{ ($filters['bedrooms'] ?? '') == $i ? 'selected' : '' }}>
                                {{ $i }}+
                            </option>
                        @endfor
                    </select>
                </div>
                <div>
                    <label for="bathrooms" class="block text-sm font-medium text-gray-700 mb-2">
                        Salles de bain min.
                    </label>
                    <select name="bathrooms" id="bathrooms" class="input-field">
                        <option value="">Toutes</option>
                        @for ($i = 1; $i <= 3; $i++)
                            <option value="{{ $i }}"
                                {{ ($filters['bathrooms'] ?? '') == $i ? 'selected' : '' }}>
                                {{ $i }}+
                            </option>
                        @endfor
                    </select>
                </div>
            </div>

            <!-- Meublé -->
            <div class="flex items-center min-h-11">
                <input type="checkbox" name="furnished" id="furnished" value="1"
                    class="w-5 h-5 text-blue-600 focus:ring-blue-500 rounded"
                    {{ $filters['furnished'] ?? false ? 'checked' : '' }}>
                <label for="furnished" class="ml-2 text-sm text-gray-700">
                    Meublé uniquement
                </label>
            </div>
        </div>

        <!-- Bouton de recherche -->
        <button type="submit" class="w-full btn-primary">
            🔍 Rechercher
        </button>
    </form>
</div>

@push('scripts')
@endpush

<x-google-maps-loader />
