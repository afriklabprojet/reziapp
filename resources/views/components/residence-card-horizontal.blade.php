@props(['residence'])

<article
    class="group bg-white rounded-2xl overflow-hidden shadow-sm hover:shadow-lg transition-all duration-300 border border-gray-100">
    <div class="flex flex-col md:flex-row">
        {{-- Image --}}
        <a href="{{ route('residences.show', $residence) }}" class="relative md:w-80 lg:w-96 shrink-0">
            <div class="aspect-4/3 md:aspect-auto md:h-full overflow-hidden">
                @if ($residence->cover_image)
                    <img loading="lazy" src="{{ storage_url($residence->cover_image) }}" alt="{{ $residence->title }}"
                        class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                @elseif($residence->photos && $residence->photos->first())
                    <img loading="lazy" src="{{ storage_url($residence->photos->first()?->path) }}"
                        alt="{{ $residence->title }}"
                        class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                @else
                    <div
                        class="w-full h-full min-h-48 bg-linear-to-br from-gray-100 to-gray-200 flex items-center justify-center">
                        <svg aria-hidden="true" class="w-12 h-12 text-gray-300" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                            <polyline stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                points="9,22 9,12 15,12 15,22" />
                        </svg>
                    </div>
                @endif
            </div>

            {{-- Badges --}}
            <div class="absolute top-3 left-3 flex flex-wrap gap-2">
                @if ($residence->isSponsored())
                    <span
                        class="px-2 py-1 bg-amber-500 text-white text-xs font-semibold rounded-lg flex items-center gap-1 shadow-lg">
                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                            <path
                                d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                        </svg>
                        Sponsorisé
                    </span>
                @endif
                @if ($residence->is_featured ?? false)
                    <span class="px-2 py-1 bg-[#ff385c] text-white text-xs font-semibold rounded-lg">En vedette</span>
                @endif
                @if ($residence->is_new ?? false)
                    <span class="px-2 py-1 bg-emerald-500 text-white text-xs font-semibold rounded-lg">Nouveau</span>
                @endif
            </div>

            {{-- Favorite Button --}}
            @auth
                <form action="{{ route('favorites.toggle', $residence) }}" method="POST" class="absolute top-3 right-3">
                    @csrf
                    <button type="submit"
                        class="w-9 h-9 flex items-center justify-center bg-white/90 backdrop-blur-sm rounded-full shadow-lg hover:scale-110 transition-transform">
                        @if (auth()->user()->favorites()->where('residence_id', $residence->id)->exists())
                            <svg aria-hidden="true" class="w-5 h-5 text-red-500 fill-current" viewBox="0 0 24 24">
                                <path
                                    d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z" />
                            </svg>
                        @else
                            <svg aria-hidden="true" class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                            </svg>
                        @endif
                    </button>
                </form>
            @endauth
        </a>

        {{-- Content --}}
        <div class="grow p-5 flex flex-col">
            {{-- Header --}}
            <div class="flex items-start justify-between mb-3">
                <div>
                    <div class="flex items-center gap-2 text-gray-600 text-sm mb-1">
                        <svg aria-hidden="true" class="w-4 h-4" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                        </svg>
                        <span>{{ $residence->commune ?? ($residence->city ?? '') }}</span>
                        @if ($residence->quartier)
                            <span class="text-gray-300">•</span>
                            <span>{{ $residence->quartier }}</span>
                        @endif
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 group-hover:text-[#e00b41] transition-colors">
                        <a href="{{ route('residences.show', $residence) }}">{{ $residence->title }}</a>
                    </h3>
                </div>
                @if ($residence->average_rating)
                    <div class="flex items-center gap-1 px-2 py-1 bg-yellow-50 rounded-lg shrink-0">
                        <svg aria-hidden="true" class="w-4 h-4 text-yellow-400 fill-current" viewBox="0 0 20 20">
                            <path
                                d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                        </svg>
                        <span
                            class="text-sm font-medium text-gray-900">{{ number_format($residence->average_rating, 1) }}</span>
                        @if ($residence->reviews_count)
                            <span class="text-xs text-gray-600">({{ $residence->reviews_count }})</span>
                        @endif
                    </div>
                @endif
            </div>

            {{-- Description --}}
            @if ($residence->description)
                <p class="text-gray-600 text-sm mb-4 line-clamp-2">{{ $residence->description }}</p>
            @endif

            {{-- Features --}}
            <div class="flex flex-wrap items-center gap-3 text-sm text-gray-600 mb-4">
                @if ($residence->bedrooms)
                    <span class="flex items-center gap-1.5 px-3 py-1 bg-gray-100 rounded-full">
                        <svg aria-hidden="true" class="w-4 h-4" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                        </svg>
                        {{ $residence->bedrooms }} chambre{{ $residence->bedrooms > 1 ? 's' : '' }}
                    </span>
                @endif
                @if ($residence->bathrooms)
                    <span class="flex items-center gap-1.5 px-3 py-1 bg-gray-100 rounded-full">
                        <svg aria-hidden="true" class="w-4 h-4" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17.657 18.657A8 8 0 016.343 7.343S7 9 9 10c0-2 .5-5 2.986-7C14 5 16.09 5.777 17.656 7.343A7.975 7.975 0 0120 13a7.975 7.975 0 01-2.343 5.657z" />
                        </svg>
                        {{ $residence->bathrooms }} sdb
                    </span>
                @endif
                @if ($residence->max_guests)
                    <span class="flex items-center gap-1.5 px-3 py-1 bg-gray-100 rounded-full">
                        <svg aria-hidden="true" class="w-4 h-4" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        {{ $residence->max_guests }} pers.
                    </span>
                @endif
                @if ($residence->surface)
                    <span class="flex items-center gap-1.5 px-3 py-1 bg-gray-100 rounded-full">
                        <svg aria-hidden="true" class="w-4 h-4" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4" />
                        </svg>
                        {{ $residence->surface }} m²
                    </span>
                @endif
            </div>

            {{-- Amenities Preview --}}
            @if ($residence->amenities && $residence->amenities->count() > 0)
                <div class="flex flex-wrap gap-2 mb-4">
                    @foreach ($residence->amenities->take(4) as $amenity)
                        <span class="text-xs text-gray-600 px-2 py-1 bg-gray-50 rounded">
                            {{ $amenity->name }}
                        </span>
                    @endforeach
                    @if ($residence->amenities->count() > 4)
                        <span class="text-xs text-[#e00b41] px-2 py-1">
                            +{{ $residence->amenities->count() - 4 }} autres
                        </span>
                    @endif
                </div>
            @endif

            {{-- Footer --}}
            <div class="mt-auto pt-4 border-t border-gray-100 flex items-center justify-between">
                <div>
                    <span
                        class="text-2xl font-bold text-gray-900">{{ number_format($residence->price, 0, ',', ' ') }}</span>
                    <span class="text-gray-600"> FCFA/{{ $residence->price_label }}</span>
                </div>
                <a href="{{ route('residences.show', $residence) }}"
                    class="px-5 py-2.5 bg-[#ff385c] text-white font-medium rounded-xl hover:bg-[#e00b41] transition-colors">
                    Voir les détails
                </a>
            </div>
        </div>
    </div>
</article>
