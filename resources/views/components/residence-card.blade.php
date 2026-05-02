@props(['residence'])

<div class="group bg-white rounded-[14px] overflow-hidden card-lift border border-[#dddddd] hover:border-[#c1c1c1] transition-all duration-200"
    x-data="{
        isFavorite: {{ auth()->check() && method_exists($residence, 'isFavoritedBy') && $residence->isFavoritedBy(auth()->user()) ? 'true' : 'false' }},
        loading: false
    }">
    <!-- Photo principale -->
    <div class="relative aspect-[4/3] bg-[#f7f7f7] overflow-hidden">
        @if ($residence->photos->isNotEmpty())
            @php $photoPath = $residence->photos->where('is_primary', true)->first()?->path ?? $residence->photos->first()?->path; @endphp
            <img loading="lazy" src="{{ storage_url($photoPath) }}" alt="{{ $residence->name }}"
                class="w-full h-full object-cover object-center group-hover:scale-[1.05] transition-transform duration-500 ease-out">
        @else
            <div class="w-full h-full flex flex-col items-center justify-center gap-2 bg-[#f7f7f7]">
                <svg aria-hidden="true" class="w-12 h-12 text-[#dddddd]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                        d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                </svg>
                <span class="text-xs text-[#929292] font-medium">Aucune photo</span>
            </div>
        @endif

        @auth
        <!-- Bouton favoris AJAX — cœur Airbnb style -->
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
            class="absolute top-3 right-3 w-8 h-8 flex items-center justify-center rounded-full transition-transform active:scale-90"
            :class="{ 'animate-pulse': loading }"
            aria-label="{{ __('Favori') }}">
            <svg aria-hidden="true" class="w-6 h-6 transition-colors drop-shadow"
                :class="isFavorite ? 'text-[#ff385c] fill-[#ff385c]' : 'text-white fill-white/30'"
                fill="none" stroke="white" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
            </svg>
        </button>
        @endauth

        <!-- Badge type -->
        <div class="absolute top-3 left-3 flex flex-col gap-1.5 items-start">
            @if ($residence->isSponsored())
                <span class="px-2.5 py-1 bg-white text-[#222222] text-[11px] font-semibold rounded-full" style="box-shadow: rgba(0,0,0,0.1) 0 1px 2px;">
                    Vedette
                </span>
            @endif
        </div>

        <!-- Prix en overlay bottom -->
        <div class="absolute bottom-3 left-3 right-3 flex items-end justify-between">
            <span class="px-3 py-1.5 bg-white/95 backdrop-blur-sm text-[#222222] font-semibold text-sm rounded-full" style="box-shadow: rgba(0,0,0,0.1) 0 1px 2px;">
                {{ number_format($residence->price, 0, ',', ' ') }} <span class="text-[11px] font-medium text-[#6a6a6a]">FCFA/{{ $residence->price_label }}</span>
            </span>
            @if($residence->rating_avg ?? $residence->average_rating ?? null)
                <span class="flex items-center gap-1 px-2.5 py-1 bg-white/95 backdrop-blur-sm text-[#222222] text-xs font-semibold rounded-full" style="box-shadow: rgba(0,0,0,0.1) 0 1px 2px;">
                    <svg class="w-3 h-3 fill-[#222222]" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                    {{ number_format($residence->rating_avg ?? $residence->average_rating, 1) }}
                </span>
            @endif
        </div>
    </div>

    <!-- Contenu -->
    <div class="p-3 sm:p-4">
        <!-- Titre et localisation -->
        <div class="mb-3">
            <h3 class="font-sans text-base font-semibold text-[#222222] mb-1 line-clamp-1">
                {{ $residence->name }}
            </h3>
            <p class="text-sm text-[#6a6a6a] flex items-center gap-1">
                <svg aria-hidden="true" class="w-3.5 h-3.5 text-[#929292] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                {{ $residence->city }}
                @if (isset($residence->distance))
                    <span class="text-[#ff385c] font-medium">· {{ number_format($residence->distance, 1) }} km</span>
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
                    <span class="px-2 py-0.5 bg-[#f7f7f7] text-gray-600 text-xs rounded-full">
                        {{ $amenity->icon }} {{ $amenity->name }}
                    </span>
                @endforeach
                @if ($residence->amenities->count() > 3)
                    <span class="px-2 py-0.5 bg-[#f7f7f7] text-gray-500 text-xs rounded-full">
                        +{{ $residence->amenities->count() - 3 }} autres
                    </span>
                @endif
            </div>
        @endif

        <!-- Action -->
        <div class="flex gap-2">
            <a href="{{ route('residences.show', $residence) }}"
                class="flex-1 text-center py-2.5 rounded-xl font-semibold text-sm bg-[#ff385c] hover:bg-[#e00b41] text-white transition-colors shadow-sm shadow-orange-200">
                Voir les détails
            </a>
        </div>
    </div>
</div>

