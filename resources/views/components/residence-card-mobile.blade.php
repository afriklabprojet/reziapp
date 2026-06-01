{{-- Carte résidence mobile — style Airbnb (flat, rounded image, rating inline) --}}
@props(['residence', 'showFavorite' => true, 'compact' => false])

<div x-data="{
    isFavorite: {{ auth()->check() && auth()->user()->favorites->contains($residence->id) ? 'true' : 'false' }},
    loading: false,
    currentImage: 0,
    images: {{ json_encode($residence->photos->pluck('url')->take(5)->toArray()) }},
    touchStartX: 0,
    touchStartY: 0,
    touchEndX: 0
}"
    class="group relative">

    {{-- Image carousel — rounded, no card shadow --}}
    <a href="{{ route('residences.show', $residence) }}"
        class="block relative rounded-2xl overflow-hidden"
        style="aspect-ratio: 4/3;"
        @touchstart="touchStartX = $event.touches[0].clientX; touchStartY = $event.touches[0].clientY"
        @touchend="
           touchEndX = $event.changedTouches[0].clientX;
           const diffX = touchStartX - touchEndX;
           const diffY = Math.abs($event.changedTouches[0].clientY - touchStartY);
           if (Math.abs(diffX) > 80 && Math.abs(diffX) > diffY * 1.5) {
               if (diffX > 0 && currentImage < images.length - 1) currentImage++;
               else if (diffX < 0 && currentImage > 0) currentImage--;
           }
       ">

        @if ($residence->photos->count() > 0)
            <div class="relative w-full h-full bg-gray-100">
                <template x-for="(img, index) in images" :key="index">
                    <img loading="lazy" :src="img" :alt="'{{ $residence->title }}'"
                        class="absolute inset-0 w-full h-full object-cover transition-opacity duration-300"
                        :class="currentImage === index ? 'opacity-100' : 'opacity-0'">
                </template>
            </div>

            {{-- Dot indicators --}}
            <div x-show="images.length > 1"
                class="absolute bottom-2.5 left-1/2 -translate-x-1/2 flex items-center gap-1">
                <template x-for="(_, index) in images" :key="index">
                    <div class="h-1.5 rounded-full transition-all duration-200"
                        :class="currentImage === index ? 'w-4 bg-white' : 'w-1.5 bg-white/50'"></div>
                </template>
            </div>
        @else
            <div class="w-full h-full bg-linear-to-br from-[#FFE7D1] to-[#FFD0A3] flex items-center justify-center">
                <svg aria-hidden="true" class="w-12 h-12 text-[#FFB46F]" fill="none" stroke="currentColor"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                        d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                </svg>
            </div>
        @endif

        {{-- Badge type --}}
        @if ($residence->isSponsored())
            <span class="absolute top-3 left-3 px-2.5 py-1 bg-amber-500 text-white text-xs font-semibold rounded-full">
                ⭐ Sponsorisé
            </span>
        @elseif ($residence->is_top_residence)
            <span class="absolute top-3 left-3 px-2.5 py-1 bg-linear-to-r from-[#F16A00] to-[#CC5A00] text-white text-xs font-semibold rounded-full">
                ⭐ Premium
            </span>
        @elseif($residence->created_at?->isAfter(now()->subDays(14)))
            <span class="absolute top-3 left-3 px-2.5 py-1 bg-white text-gray-900 text-xs font-semibold rounded-full shadow-sm">
                Nouveau
            </span>
        @endif

        {{-- Badge instant book --}}
        @if ($residence->instant_book)
            <span class="absolute top-3 {{ ($residence->isSponsored() || $residence->is_top_residence) ? 'left-26' : 'left-3' }} px-2.5 py-1 bg-green-500 text-white text-xs font-semibold rounded-full">
                ⚡ Instant
            </span>
        @endif
    </a>

    {{-- Bouton favori — 44×44 touch target --}}
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
                    .then(data => { isFavorite = data.is_favorite; loading = false; })
                    .catch(() => loading = false);
                "
                class="absolute top-2 right-2 w-11 h-11 flex items-center justify-center rounded-full bg-black/10 active:scale-90 transition-transform"
                :class="{ 'animate-pulse': loading }"
                aria-label="Ajouter aux favoris">
                <svg aria-hidden="true" class="w-6 h-6 drop-shadow-sm transition-colors"
                    :class="isFavorite ? 'text-[#FF385C] fill-[#FF385C]' : 'text-white fill-white/20'"
                    viewBox="0 0 32 32" xmlns="http://www.w3.org/2000/svg">
                    <path d="M16 28c7-4.733 14-10 14-17a6.98 6.98 0 0 0-7-7c-1.8 0-3.58.68-4.95 2.05L16 8.1l-2.05-2.05a6.98 6.98 0 0 0-9.9 0A6.98 6.98 0 0 0 2 11c0 7 7 12.267 14 17z"/>
                </svg>
            </button>
        @endauth
    @endif

    {{-- Info sous l'image — style Airbnb --}}
    <a href="{{ route('residences.show', $residence) }}" class="block mt-2 {{ $compact ? 'pb-2' : 'pb-4' }}">
        {{-- Titre + note --}}
        <div class="flex items-start justify-between gap-2">
            <h3 class="text-sm font-semibold text-gray-900 leading-snug truncate">
                {{ $residence->title }}
            </h3>
            @if ($residence->average_rating)
                <div class="flex items-center gap-0.5 shrink-0">
                    <svg aria-hidden="true" class="w-3.5 h-3.5 text-gray-900" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                    </svg>
                    <span class="text-sm font-medium text-gray-900">{{ number_format($residence->average_rating, 1) }}</span>
                </div>
            @else
                <div class="flex items-center gap-0.5 shrink-0">
                    <svg aria-hidden="true" class="w-3.5 h-3.5 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                    </svg>
                    <span class="text-sm text-gray-400">Nouveau</span>
                </div>
            @endif
        </div>

        {{-- Localisation --}}
        <p class="text-sm text-gray-500 mt-0.5 truncate">
            {{ $residence->commune }}{{ $residence->quartier ? ', ' . $residence->quartier : '' }}
        </p>

        {{-- Prix --}}
        <p class="mt-1 text-sm">
            <span class="font-semibold text-gray-900">{{ number_format($residence->price_per_day, 0, ',', ' ') }} FCFA</span>
            <span class="text-gray-500"> / nuit</span>
        </p>
    </a>
</div>
