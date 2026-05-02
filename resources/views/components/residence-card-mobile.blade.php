{{-- Carte résidence optimisée mobile avec swipe --}}
@props(['residence', 'showFavorite' => true, 'compact' => false])

<div x-data="{
    isFavorite: {{ auth()->check() && auth()->user()->favorites->contains($residence->id) ? 'true' : 'false' }},
    loading: false,
    currentImage: 0,
    images: {{ json_encode($residence->photos->pluck('url')->take(5)->toArray()) }},
    touchStartX: 0,
    touchEndX: 0
}"
    class="group relative bg-white rounded-2xl overflow-hidden shadow-sm hover:shadow-lg transition-shadow duration-300">

    {{-- Carousel d'images --}}
    <a href="{{ route('residences.show', $residence) }}" class="block relative aspect-4/3 overflow-hidden"
        @touchstart="touchStartX = $event.touches[0].clientX"
        @touchend="
           touchEndX = $event.changedTouches[0].clientX;
           const diff = touchStartX - touchEndX;
           if (Math.abs(diff) > 50) {
               if (diff > 0 && currentImage < images.length - 1) currentImage++;
               else if (diff < 0 && currentImage > 0) currentImage--;
           }
       ">

        @if ($residence->photos->count() > 0)
            <div class="relative w-full h-full">
                <template x-for="(img, index) in images" :key="index">
                    <img loading="lazy" :src="img" :alt="'{{ $residence->title }}'"
                        class="absolute inset-0 w-full h-full object-cover transition-opacity duration-300"
                        :class="currentImage === index ? 'opacity-100' : 'opacity-0'">
                </template>
            </div>

            {{-- Indicateurs de slide --}}
            <div x-show="images.length > 1"
                class="absolute bottom-3 left-1/2 -translate-x-1/2 flex items-center gap-1.5">
                <template x-for="(_, index) in images" :key="index">
                    <button @click.prevent="currentImage = index" class="w-2 h-2 rounded-full transition-all p-0"
                        :class="currentImage === index ? 'bg-white w-4' : 'bg-white/60'"></button>
                </template>
            </div>
        @else
            <div class="w-full h-full bg-linear-to-br from-[#ffd1da] to-[#ffb3c1] flex items-center justify-center">
                <svg aria-hidden="true" class="w-12 h-12 text-[#ff7a96]" fill="none" stroke="currentColor"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                        d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                </svg>
            </div>
        @endif

        {{-- Badge type --}}
        @if ($residence->isSponsored())
            <span
                class="absolute top-3 left-3 px-2 py-1 bg-amber-500 text-white text-xs font-medium rounded-full shadow-lg flex items-center gap-1">
                ⭐ Sponsorisé
            </span>
        @elseif ($residence->is_top_residence)
            <span
                class="absolute top-3 left-3 px-2 py-1 bg-linear-to-r from-[#ff385c] to-[#e00b41] text-white text-xs font-medium rounded-full shadow-lg">
                ⭐ Premium
            </span>
        @endif

        {{-- Badge réservation instantanée --}}
        @if ($residence->instant_book)
            <span
                class="absolute top-3 left-3 {{ $residence->is_top_residence ? 'left-24' : '' }} px-2 py-1 bg-green-500 text-white text-xs font-medium rounded-full shadow-lg">
                ⚡ Instant
            </span>
        @endif
    </a>

    {{-- Bouton favori --}}
    @if ($showFavorite)
        @auth
            <button
                @click.prevent="
                    if (loading) return;
                    loading = true;
                    fetch('{{ route('favorites.toggle', $residence) }}', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        }
                    })
                    .then(r => r.json())
                    .then(data => {
                        isFavorite = data.is_favorite;
                        loading = false;
                    })
                    .catch(() => loading = false);
                "
                class="absolute top-3 right-3 w-10 h-10 flex items-center justify-center rounded-full bg-white/90 shadow-lg backdrop-blur-sm active:scale-90 transition-transform"
                :class="{ 'animate-pulse': loading }">
                <svg aria-hidden="true" class="w-5 h-5 transition-colors"
                    :class="isFavorite ? 'text-red-500 fill-red-500' : 'text-gray-700'" fill="none" stroke="currentColor"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                </svg>
            </button>
        @endauth
    @endif

    {{-- Informations --}}
    <a href="{{ route('residences.show', $residence) }}" class="block p-3 {{ $compact ? 'p-2' : 'p-3' }}">
        <div class="flex items-start justify-between gap-2">
            <div class="min-w-0 flex-1">
                <h3 class="font-semibold text-gray-900 truncate {{ $compact ? 'text-sm' : '' }}">
                    {{ $residence->title }}
                </h3>
                <p class="text-gray-600 text-sm truncate mt-0.5">
                    {{ $residence->commune }}{{ $residence->quartier ? ', ' . $residence->quartier : '' }}
                </p>
            </div>

            @if ($residence->average_rating)
                <div class="flex items-center gap-1 shrink-0">
                    <svg aria-hidden="true" class="w-4 h-4 text-[#ff385c]" fill="currentColor" viewBox="0 0 20 20">
                        <path
                            d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                    </svg>
                    <span
                        class="text-sm font-medium text-gray-900">{{ number_format($residence->average_rating, 1) }}</span>
                </div>
            @endif
        </div>

        @unless ($compact)
            {{-- Caractéristiques --}}
            <div class="flex items-center gap-3 mt-2 text-sm text-gray-600">
                <span class="flex items-center gap-1">
                    <svg aria-hidden="true" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                            d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                    </svg>
                    {{ $residence->bedrooms }} ch.
                </span>
                <span class="flex items-center gap-1">
                    <svg aria-hidden="true" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                            d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                    {{ $residence->max_guests }} pers.
                </span>
            </div>
        @endunless

        {{-- Prix --}}
        <div class="mt-2 flex items-baseline gap-1">
            <span class="text-lg font-bold text-gray-900">{{ number_format($residence->price_per_day, 0, ',', ' ') }}
                FCFA</span>
            <span class="text-sm text-gray-600">/ nuit</span>
        </div>
    </a>
</div>
