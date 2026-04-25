@props(['residence'])

<div class="group bg-white rounded-2xl shadow-md overflow-hidden card-lift border border-gray-100 hover:border-orange-100"
    x-data="{
        isFavorite: {{ auth()->check() && method_exists($residence, 'isFavoritedBy') && $residence->isFavoritedBy(auth()->user()) ? 'true' : 'false' }},
        loading: false
    }">
    <!-- Photo principale -->
    <div class="relative aspect-4/3 sm:h-48 bg-sand-100 overflow-hidden">
        @if ($residence->photos->isNotEmpty())
            @php $photoPath = $residence->photos->where('is_primary', true)->first()?->path ?? $residence->photos->first()?->path; @endphp
            <img loading="lazy" src="{{ storage_url($photoPath) }}" alt="{{ $residence->name }}"
                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-700">
        @else
            <div class="w-full h-full flex items-center justify-center text-sand-400">
                <svg aria-hidden="true" class="w-16 h-16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                        d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                </svg>
            </div>
        @endif

        <!-- Gradient overlay -->
        <div class="absolute inset-0 bg-linear-to-t from-black/50 via-transparent to-transparent pointer-events-none"></div>

        @auth
        <!-- Bouton favoris AJAX -->
        <button
            @click.prevent="
                if (loading) return;
                loading = true;
                fetch('{{ route('favorites.toggle', $residence) }}', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
                })
                .then(r => r.json())
                .then(data => { isFavorite = data.is_favorite; loading = false; })
                .catch(() => loading = false);
            "
            class="absolute top-3 right-3 w-9 h-9 flex items-center justify-center rounded-full bg-white/90 shadow-md backdrop-blur-sm transition-transform active:scale-90"
            :class="{ 'animate-pulse': loading }"
            aria-label="{{ __('Favori') }}">
            <svg aria-hidden="true" class="w-5 h-5 transition-colors"
                :class="isFavorite ? 'text-rose-500 fill-rose-500' : 'text-gray-600'"
                fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
            </svg>
        </button>
        @endauth

        <!-- Badge type -->
        <div class="absolute top-3 left-3 flex flex-col gap-1.5 items-start">
            <span class="px-2.5 py-1 bg-white/90 backdrop-blur-sm text-gray-700 text-xs font-semibold rounded-full shadow-sm">
                {{ ucfirst($residence->type) }}
            </span>
            @if ($residence->isSponsored())
                <span class="px-2.5 py-1 bg-terracotta-500 text-white text-xs font-semibold rounded-full flex items-center gap-1 shadow">
                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                    </svg>
                    Vedette
                </span>
            @endif
        </div>

        <!-- Prix en overlay bottom -->
        <div class="absolute bottom-3 left-3 right-3 flex items-end justify-between">
            <span class="px-3 py-1.5 bg-white/95 backdrop-blur-sm text-gray-900 font-bold text-sm rounded-xl shadow">
                {{ number_format($residence->price, 0, ',', ' ') }} FCFA<span class="font-normal text-xs text-gray-500">/{{ $residence->price_label }}</span>
            </span>
        </div>
    </div>

    <!-- Contenu -->
    <div class="p-3 sm:p-4">
        <!-- Titre et localisation -->
        <div class="mb-3">
            <h3 class="font-display text-base font-semibold text-gray-900 mb-1 line-clamp-1 group-hover:text-orange-600 transition-colors">
                {{ $residence->name }}
            </h3>
            <p class="text-xs text-gray-500 flex items-center gap-1">
                <svg aria-hidden="true" class="w-3.5 h-3.5 text-terracotta-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                {{ $residence->city }}
                @if (isset($residence->distance))
                    <span class="text-orange-500 font-medium">· {{ number_format($residence->distance, 1) }} km</span>
                @endif
            </p>
        </div>

        <!-- Caractéristiques -->
        <div class="flex items-center gap-3 text-xs text-gray-500 mb-3">
            <span class="flex items-center gap-1">
                <svg aria-hidden="true" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                </svg>
                {{ $residence->bedrooms }} ch.
            </span>
            <span class="flex items-center gap-1">
                <svg aria-hidden="true" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                {{ $residence->bathrooms }} sdb
            </span>
            <span class="flex items-center gap-1">
                <svg aria-hidden="true" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5v-4m0 4h-4m4 0l-5-5" />
                </svg>
                {{ number_format($residence->area, 0) }} m²
            </span>
        </div>

        <!-- Équipements -->
        @if ($residence->amenities->isNotEmpty())
            <div class="flex flex-wrap gap-1.5 mb-4">
                @foreach ($residence->amenities->take(3) as $amenity)
                    <span class="px-2 py-0.5 bg-sand-100 text-gray-600 text-xs rounded-full">
                        {{ $amenity->icon }} {{ $amenity->name }}
                    </span>
                @endforeach
                @if ($residence->amenities->count() > 3)
                    <span class="px-2 py-0.5 bg-sand-100 text-gray-500 text-xs rounded-full">
                        +{{ $residence->amenities->count() - 3 }} autres
                    </span>
                @endif
            </div>
        @endif

        <!-- Action -->
        <div class="flex gap-2">
            <a href="{{ route('residences.show', $residence) }}"
                class="flex-1 text-center py-2.5 rounded-xl font-semibold text-sm bg-orange-500 hover:bg-orange-600 text-white transition-colors shadow-sm shadow-orange-200">
                Voir les détails
            </a>
        </div>
    </div>
</div>

