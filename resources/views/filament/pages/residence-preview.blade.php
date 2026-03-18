<div class="space-y-6">
    {{-- Header avec photos --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Photos Gallery --}}
        <div>
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-3">Photos</h3>
            @if($residence->photos->count() > 0)
                <div class="grid grid-cols-2 gap-2">
                    @foreach($residence->photos->take(6) as $photo)
                        <div class="relative aspect-video bg-gray-100 dark:bg-gray-800 rounded-lg overflow-hidden">
                            <img loading="lazy" src="{{ storage_url($photo->path) }}" 
                                alt="{{ $residence->name }}"
                                class="w-full h-full object-cover"
                            >
                            @if($photo->is_primary)
                                <span class="absolute top-2 left-2 px-2 py-1 text-xs font-medium bg-primary-500 text-white rounded">
                                    Photo principale
                                </span>
                            @endif
                        </div>
                    @endforeach
                </div>
                @if($residence->photos->count() > 6)
                    <p class="mt-2 text-sm text-gray-500">+ {{ $residence->photos->count() - 6 }} autres photos</p>
                @endif
            @else
                <div class="bg-gray-100 dark:bg-gray-800 rounded-lg p-8 text-center">
                    <x-heroicon-o-photo class="w-12 h-12 mx-auto text-gray-400 mb-2"/>
                    <p class="text-sm text-gray-500">Aucune photo</p>
                </div>
            @endif
        </div>

        {{-- Informations principales --}}
        <div>
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-3">Informations</h3>
            <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4 space-y-3">
                <div class="flex justify-between">
                    <span class="text-gray-500">Titre</span>
                    <span class="font-medium text-gray-900 dark:text-white">{{ $residence->name }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500">Type</span>
                    <span class="font-medium">
                        @switch($residence->type)
                            @case('apartment') Appartement @break
                            @case('studio') Studio @break
                            @case('villa') Villa @break
                            @case('house') Maison @break
                            @case('room') Chambre @break
                            @default {{ $residence->type }}
                        @endswitch
                    </span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500">Commune</span>
                    <span class="font-medium">{{ $residence->commune }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500">Quartier</span>
                    <span class="font-medium">{{ $residence->quartier ?? '—' }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500">Adresse</span>
                    <span class="font-medium">{{ $residence->address ?? '—' }}</span>
                </div>
                <hr class="dark:border-gray-700">
                <div class="flex justify-between">
                    <span class="text-gray-500">Prix/jour</span>
                    <span class="font-bold text-primary-600">{{ number_format($residence->price_per_day) }} FCFA</span>
                </div>
                @if($residence->price_per_week)
                <div class="flex justify-between">
                    <span class="text-gray-500">Prix/semaine</span>
                    <span class="font-medium">{{ number_format($residence->price_per_week) }} FCFA</span>
                </div>
                @endif
                @if($residence->price_per_month)
                <div class="flex justify-between">
                    <span class="text-gray-500">Prix/mois (réf.)</span>
                    <span class="font-medium">{{ number_format($residence->price_per_month) }} FCFA</span>
                </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Caractéristiques --}}
    <div>
        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-3">Caractéristiques</h3>
        <div class="grid grid-cols-4 gap-4">
            <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-3 text-center">
                <x-heroicon-o-home class="w-6 h-6 mx-auto text-gray-400 mb-1"/>
                <p class="text-lg font-bold">{{ $residence->bedrooms ?? 0 }}</p>
                <p class="text-xs text-gray-500">Chambres</p>
            </div>
            <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-3 text-center">
                <x-heroicon-o-beaker class="w-6 h-6 mx-auto text-gray-400 mb-1"/>
                <p class="text-lg font-bold">{{ $residence->bathrooms ?? 0 }}</p>
                <p class="text-xs text-gray-500">Salles de bain</p>
            </div>
            <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-3 text-center">
                <x-heroicon-o-users class="w-6 h-6 mx-auto text-gray-400 mb-1"/>
                <p class="text-lg font-bold">{{ $residence->max_guests ?? 0 }}</p>
                <p class="text-xs text-gray-500">Voyageurs max</p>
            </div>
            <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-3 text-center">
                <x-heroicon-o-square-3-stack-3d class="w-6 h-6 mx-auto text-gray-400 mb-1"/>
                <p class="text-lg font-bold">{{ $residence->surface_area ?? '—' }}</p>
                <p class="text-xs text-gray-500">m²</p>
            </div>
        </div>
    </div>

    {{-- Description --}}
    <div>
        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-3">Description</h3>
        <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
            @if($residence->description)
                <p class="text-gray-700 dark:text-gray-300 whitespace-pre-wrap">{{ $residence->description }}</p>
            @else
                <p class="text-gray-500 italic">Aucune description fournie</p>
            @endif
        </div>
    </div>

    {{-- Équipements --}}
    @if($residence->amenities->count() > 0)
    <div>
        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-3">Équipements</h3>
        <div class="flex flex-wrap gap-2">
            @foreach($residence->amenities as $amenity)
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm bg-primary-100 text-primary-700 dark:bg-primary-900 dark:text-primary-300">
                    @if($amenity->icon)
                        <span class="mr-1">{{ $amenity->icon }}</span>
                    @endif
                    {{ $amenity->name }}
                </span>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Propriétaire --}}
    <div>
        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-3">Propriétaire</h3>
        <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-primary-100 dark:bg-primary-900 rounded-full flex items-center justify-center">
                    <span class="text-lg font-bold text-primary-600">
                        {{ strtoupper(substr($residence->owner?->name ?? 'U', 0, 1)) }}
                    </span>
                </div>
                <div>
                    <p class="font-medium text-gray-900 dark:text-white">{{ $residence->owner?->name }}</p>
                    <p class="text-sm text-gray-500">{{ $residence->owner?->email }}</p>
                    <p class="text-sm text-gray-500">{{ $residence->owner?->phone }}</p>
                </div>
                <div class="ml-auto text-right">
                    <p class="text-sm text-gray-500">Membre depuis</p>
                    <p class="font-medium">{{ $residence->owner?->created_at?->format('d/m/Y') }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Localisation --}}
    @if($residence->latitude && $residence->longitude)
    <div>
        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-3">Localisation</h3>
        <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
            <p class="text-sm text-gray-500 mb-2">
                Coordonnées: {{ $residence->latitude }}, {{ $residence->longitude }}
            </p>
            <div class="aspect-video bg-gray-200 dark:bg-gray-700 rounded-lg flex items-center justify-center">
                <a 
                    href="https://www.google.com/maps?q={{ $residence->latitude }},{{ $residence->longitude }}" 
                    target="_blank"
                    class="inline-flex items-center gap-2 text-primary-600 hover:text-primary-700"
                >
                    <x-heroicon-o-map class="w-5 h-5"/>
                    Voir sur Google Maps
                </a>
            </div>
        </div>
    </div>
    @endif
</div>
