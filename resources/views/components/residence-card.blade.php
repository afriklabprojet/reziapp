@props(['residence'])

<div class="bg-white rounded-xl shadow-md hover:shadow-xl transition-shadow duration-300 overflow-hidden">
    <!-- Photo principale -->
    <div class="relative aspect-4/3 sm:h-48 bg-gray-200">
        @if ($residence->photos->isNotEmpty())
            @php $photoPath = $residence->photos->where('is_primary', true)->first()?->path ?? $residence->photos->first()?->path; @endphp
            <img loading="lazy" src="{{ storage_url($photoPath) }}" alt="{{ $residence->name }}"
                class="w-full h-full object-cover">
        @else
            <div class="w-full h-full flex items-center justify-center text-gray-400">
                <svg aria-hidden="true" class="w-16 h-16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                </svg>
            </div>
        @endif

        <!-- Badge type -->
        <div class="absolute top-3 right-3 flex flex-col gap-1.5 items-end">
            <span class="px-3 py-1 bg-blue-600 text-white text-xs font-semibold rounded-full">
                {{ ucfirst($residence->type) }}
            </span>
            @if ($residence->isSponsored())
                <span
                    class="px-2.5 py-1 bg-amber-500 text-white text-xs font-semibold rounded-full flex items-center gap-1 shadow-lg">
                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                        <path
                            d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                    </svg>
                    Sponsorisé
                </span>
            @endif
        </div>

        <!-- Badge prix -->
        <div class="absolute bottom-3 left-3">
            <span class="px-3 py-2 bg-white/90 backdrop-blur-sm text-blue-600 font-bold rounded-lg">
                {{ number_format($residence->price, 0, ',', ' ') }} FCFA<span
                    class="font-normal text-xs">/{{ $residence->price_label }}</span>
            </span>
        </div>
    </div>

    <!-- Contenu -->
    <div class="p-3 sm:p-4">
        <!-- Titre et localisation -->
        <div class="mb-3">
            <h3 class="text-lg font-semibold text-gray-900 mb-1 line-clamp-1">
                {{ $residence->name }}
            </h3>
            <p class="text-sm text-gray-600 flex items-center">
                <svg aria-hidden="true" class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                {{ $residence->city }}
                @if (isset($residence->distance))
                    <span class="ml-2 text-blue-600 font-medium">
                        • {{ number_format($residence->distance, 1) }} km
                    </span>
                @endif
            </p>
        </div>

        <!-- Caractéristiques -->
        <div class="flex items-center justify-between text-sm text-gray-600 mb-3">
            <div class="flex items-center">
                <svg aria-hidden="true" class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                </svg>
                <span>{{ $residence->bedrooms }} ch.</span>
            </div>

            <div class="flex items-center">
                <svg aria-hidden="true" class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                <span>{{ $residence->bathrooms }} sdb</span>
            </div>

            <div class="flex items-center">
                <svg aria-hidden="true" class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5v-4m0 4h-4m4 0l-5-5" />
                </svg>
                <span>{{ number_format($residence->area, 0) }} m²</span>
            </div>
        </div>

        <!-- Description -->
        <p class="text-sm text-gray-600 mb-4 line-clamp-2">
            {{ $residence->description }}
        </p>

        <!-- Équipements -->
        @if ($residence->amenities->isNotEmpty())
            <div class="flex flex-wrap gap-2 mb-4">
                @foreach ($residence->amenities->take(3) as $amenity)
                    <span class="px-2 py-1 bg-gray-100 text-gray-700 text-xs rounded">
                        {{ $amenity->icon }} {{ $amenity->name }}
                    </span>
                @endforeach
                @if ($residence->amenities->count() > 3)
                    <span class="px-2 py-1 bg-gray-100 text-gray-700 text-xs rounded">
                        +{{ $residence->amenities->count() - 3 }}
                    </span>
                @endif
            </div>
        @endif

        <!-- Action -->
        <a href="{{ route('residences.show', $residence) }}" class="block w-full text-center btn-primary">
            Voir les détails
        </a>
    </div>
</div>
