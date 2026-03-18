@extends('layouts.app')

@section('title', $residence->name . ' - Location à ' . $residence->commune)

@section('meta')
    <meta name="description" content="{{ Str::limit(strip_tags($residence->description), 160) }}">
    <meta property="og:title" content="{{ $residence->name }} - Location {{ $residence->commune }}">
    <meta property="og:description" content="{{ Str::limit(strip_tags($residence->description), 160) }}">
    @if ($residence->photos->isNotEmpty())
        <meta property="og:image" content="{{ $residence->photos->first()->url }}">
    @endif
@endsection

@section('content')
    <div class="min-h-screen bg-gray-50" x-data="locationPage()">

        {{-- Breadcrumb --}}
        <div class="bg-white border-b">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3">
                <nav class="flex items-center gap-2 text-sm text-gray-500">
                    <a href="{{ route('home') }}" class="hover:text-gray-900">Accueil</a>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                    <a href="{{ route('residences.locations') }}" class="hover:text-gray-900">Locations</a>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                    <span class="text-gray-900">{{ $residence->commune }}</span>
                </nav>
            </div>
        </div>

        {{-- Hero Section with Gallery --}}
        <div class="bg-white">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
                {{-- Title & Quick Info --}}
                <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4 mb-6">
                    <div>
                        <div class="flex items-center gap-2 mb-2">
                            <span class="px-3 py-1 bg-emerald-100 text-emerald-700 text-xs font-semibold rounded-full">
                                🏠 Location longue durée
                            </span>
                            @if ($residence->is_available)
                                <span class="px-3 py-1 bg-green-100 text-green-700 text-xs font-semibold rounded-full">
                                    Disponible
                                </span>
                            @endif
                        </div>
                        <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">{{ $residence->name }}</h1>
                        <p class="text-gray-600 mt-1 flex items-center gap-2">
                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            {{ $residence->quartier }}, {{ $residence->commune }} - {{ $residence->city }}
                        </p>
                    </div>
                    <div class="text-right">
                        <div class="text-3xl font-bold text-emerald-600">
                            {{ number_format($residence->price, 0, ',', ' ') }} <span class="text-lg font-normal">FCFA</span>
                        </div>
                        <p class="text-gray-500 text-sm">par jour</p>
                    </div>
                </div>

                {{-- Photo Gallery --}}
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-3 rounded-2xl overflow-hidden">
                    @if ($residence->photos->isNotEmpty())
                        {{-- Main Photo --}}
                        <div class="relative aspect-4/3 lg:aspect-16/10 cursor-pointer group" @click="openGallery(0)">
                            <img src="{{ $residence->photos->first()->url }}" alt="{{ $residence->name }}"
                                class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-105">
                            <div class="absolute inset-0 bg-black/0 group-hover:bg-black/10 transition-colors"></div>
                        </div>
                        {{-- Secondary Photos --}}
                        <div class="grid grid-cols-2 gap-3">
                            @foreach ($residence->photos->skip(1)->take(4) as $index => $photo)
                                <div class="relative aspect-4/3 cursor-pointer group" @click="openGallery({{ $index + 1 }})">
                                    <img src="{{ $photo->url }}" alt="{{ $residence->name }}"
                                        class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-105">
                                    <div class="absolute inset-0 bg-black/0 group-hover:bg-black/10 transition-colors"></div>
                                    @if ($index === 3 && $residence->photos->count() > 5)
                                        <div class="absolute inset-0 bg-black/50 flex items-center justify-center">
                                            <span class="text-white font-semibold">+{{ $residence->photos->count() - 5 }} photos</span>
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="aspect-video bg-gray-200 flex items-center justify-center col-span-2 rounded-2xl">
                            <span class="text-gray-400">Aucune photo disponible</span>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Main Content --}}
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

                {{-- Left Column - Details --}}
                <div class="lg:col-span-2 space-y-8">

                    {{-- Key Features --}}
                    <div class="bg-white rounded-2xl p-6 shadow-sm">
                        <h2 class="text-xl font-bold text-gray-900 mb-6">Caractéristiques</h2>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-6">
                            @if ($residence->surface_area)
                                <div class="text-center p-4 bg-gray-50 rounded-xl">
                                    <div class="w-12 h-12 mx-auto mb-3 bg-emerald-100 rounded-full flex items-center justify-center">
                                        <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4" />
                                        </svg>
                                    </div>
                                    <div class="text-2xl font-bold text-gray-900">{{ $residence->surface_area }}</div>
                                    <div class="text-sm text-gray-500">m²</div>
                                </div>
                            @endif
                            <div class="text-center p-4 bg-gray-50 rounded-xl">
                                <div class="w-12 h-12 mx-auto mb-3 bg-blue-100 rounded-full flex items-center justify-center">
                                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                                    </svg>
                                </div>
                                <div class="text-2xl font-bold text-gray-900">{{ $residence->bedrooms ?? 1 }}</div>
                                <div class="text-sm text-gray-500">Chambre(s)</div>
                            </div>
                            <div class="text-center p-4 bg-gray-50 rounded-xl">
                                <div class="w-12 h-12 mx-auto mb-3 bg-cyan-100 rounded-full flex items-center justify-center">
                                    <svg class="w-6 h-6 text-cyan-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z" />
                                    </svg>
                                </div>
                                <div class="text-2xl font-bold text-gray-900">{{ $residence->bathrooms ?? 1 }}</div>
                                <div class="text-sm text-gray-500">Salle(s) de bain</div>
                            </div>
                            <div class="text-center p-4 bg-gray-50 rounded-xl">
                                <div class="w-12 h-12 mx-auto mb-3 bg-amber-100 rounded-full flex items-center justify-center">
                                    <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                    </svg>
                                </div>
                                <div class="text-2xl font-bold text-gray-900">{{ $residence->max_guests ?? 4 }}</div>
                                <div class="text-sm text-gray-500">Occupants max</div>
                            </div>
                        </div>
                    </div>

                    {{-- Description --}}
                    <div class="bg-white rounded-2xl p-6 shadow-sm">
                        <h2 class="text-xl font-bold text-gray-900 mb-4">Description</h2>
                        <div class="prose prose-gray max-w-none text-gray-600 leading-relaxed">
                            {!! nl2br(e($residence->description)) !!}
                        </div>
                    </div>

                    {{-- Amenities --}}
                    @if ($residence->amenities->isNotEmpty())
                        <div class="bg-white rounded-2xl p-6 shadow-sm">
                            <h2 class="text-xl font-bold text-gray-900 mb-6">Équipements inclus</h2>
                            <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
                                @foreach ($residence->amenities as $amenity)
                                    <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-xl">
                                        <div class="w-10 h-10 bg-emerald-100 rounded-lg flex items-center justify-center shrink-0">
                                            @if ($amenity->icon)
                                                <span class="text-xl">{{ $amenity->icon }}</span>
                                            @else
                                                <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                                </svg>
                                            @endif
                                        </div>
                                        <span class="text-sm font-medium text-gray-700">{{ $amenity->name }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- House Rules --}}
                    @if ($residence->house_rules)
                        <div class="bg-white rounded-2xl p-6 shadow-sm">
                            <h2 class="text-xl font-bold text-gray-900 mb-4">Conditions de location</h2>
                            <div class="prose prose-gray max-w-none text-gray-600 leading-relaxed">
                                {!! nl2br(e($residence->house_rules)) !!}
                            </div>
                        </div>
                    @endif

                    {{-- Location Map --}}
                    <div class="bg-white rounded-2xl p-6 shadow-sm">
                        <h2 class="text-xl font-bold text-gray-900 mb-4">Localisation</h2>
                        <p class="text-gray-600 mb-4">
                            <svg class="w-5 h-5 inline-block mr-1 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                            </svg>
                            {{ $residence->address }}, {{ $residence->quartier }}, {{ $residence->commune }}
                        </p>
                        @if ($residence->latitude && $residence->longitude)
                            <div class="aspect-video rounded-xl overflow-hidden bg-gray-100">
                                <iframe
                                    width="100%"
                                    height="100%"
                                    style="border:0"
                                    loading="lazy"
                                    allowfullscreen
                                    referrerpolicy="no-referrer-when-downgrade"
                                    src="https://www.google.com/maps/embed/v1/place?key={{ config('services.google_maps.key') }}&q={{ $residence->latitude }},{{ $residence->longitude }}&zoom=15">
                                </iframe>
                            </div>
                        @endif
                    </div>

                </div>

                {{-- Right Column - Contact Card --}}
                <div class="lg:col-span-1">
                    <div class="sticky top-24 space-y-6">

                        {{-- Price Card --}}
                        <div class="bg-white rounded-2xl p-6 shadow-lg border-2 border-emerald-100">
                            <div class="text-center mb-6">
                                <div class="text-sm text-gray-500 mb-1">Tarif journalier</div>
                                <div class="text-4xl font-bold text-emerald-600">
                                    {{ number_format($residence->price, 0, ',', ' ') }}
                                </div>
                                <div class="text-gray-500">FCFA / jour</div>
                            </div>

                            <div class="space-y-3 mb-6 text-sm">
                                <div class="flex justify-between py-2 border-b border-gray-100">
                                    <span class="text-gray-500">Type</span>
                                    <span class="font-medium text-gray-900">Location longue durée</span>
                                </div>
                                <div class="flex justify-between py-2 border-b border-gray-100">
                                    <span class="text-gray-500">Surface</span>
                                    <span class="font-medium text-gray-900">{{ $residence->surface_area ?? 'N/A' }} m²</span>
                                </div>
                                <div class="flex justify-between py-2 border-b border-gray-100">
                                    <span class="text-gray-500">Chambres</span>
                                    <span class="font-medium text-gray-900">{{ $residence->bedrooms ?? 1 }}</span>
                                </div>
                                <div class="flex justify-between py-2">
                                    <span class="text-gray-500">Disponibilité</span>
                                    <span class="font-medium {{ $residence->is_available ? 'text-green-600' : 'text-red-600' }}">
                                        {{ $residence->is_available ? 'Disponible' : 'Non disponible' }}
                                    </span>
                                </div>
                            </div>

                            {{-- Contact Buttons --}}
                            <div class="space-y-3">
                                <a href="tel:{{ $ownerPhone }}"
                                    class="flex items-center justify-center gap-2 w-full py-3.5 bg-emerald-600 text-white font-semibold rounded-xl hover:bg-emerald-700 transition-colors">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                    </svg>
                                    Appeler le propriétaire
                                </a>

                                <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $ownerPhone) }}?text={{ urlencode('Bonjour, je suis intéressé par votre bien "' . $residence->name . '" à ' . $residence->commune . '. Est-il toujours disponible ?') }}"
                                    target="_blank"
                                    class="flex items-center justify-center gap-2 w-full py-3.5 bg-green-500 text-white font-semibold rounded-xl hover:bg-green-600 transition-colors">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                                    </svg>
                                    Contacter sur WhatsApp
                                </a>

                                @auth
                                    <button @click="showContactForm = true"
                                        class="flex items-center justify-center gap-2 w-full py-3.5 bg-gray-100 text-gray-700 font-semibold rounded-xl hover:bg-gray-200 transition-colors">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                        </svg>
                                        Envoyer un message
                                    </button>
                                @else
                                    <a href="{{ route('login', ['redirect' => url()->current()]) }}"
                                        class="flex items-center justify-center gap-2 w-full py-3.5 bg-gray-100 text-gray-700 font-semibold rounded-xl hover:bg-gray-200 transition-colors">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                        </svg>
                                        Connectez-vous pour contacter
                                    </a>
                                @endauth
                            </div>
                        </div>

                        {{-- Owner Info --}}
                        <div class="bg-white rounded-2xl p-6 shadow-sm">
                            <div class="flex items-center gap-4 mb-4">
                                @if ($residence->owner->avatar)
                                    <img src="{{ $residence->owner->avatar }}" alt="{{ $residence->owner->name }}"
                                        class="w-14 h-14 rounded-full object-cover">
                                @else
                                    <div class="w-14 h-14 rounded-full bg-emerald-100 flex items-center justify-center">
                                        <span class="text-xl font-bold text-emerald-600">{{ substr($residence->owner->name, 0, 1) }}</span>
                                    </div>
                                @endif
                                <div>
                                    <h3 class="font-semibold text-gray-900">{{ $residence->owner->name }}</h3>
                                    <p class="text-sm text-gray-500">Propriétaire</p>
                                </div>
                            </div>
                            <div class="space-y-2 text-sm text-gray-600">
                                @if ($residence->owner->identity_verified)
                                    <div class="flex items-center gap-2 text-emerald-600">
                                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                        </svg>
                                        Identité vérifiée
                                    </div>
                                @endif
                                <div class="flex items-center gap-2">
                                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                    </svg>
                                    {{ $ownerResidencesCount }} bien(s) en location
                                </div>
                                <div class="flex items-center gap-2">
                                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                    Membre depuis {{ $residence->owner->created_at->format('M Y') }}
                                </div>
                            </div>
                        </div>

                        {{-- Share & Save --}}
                        <div class="bg-white rounded-2xl p-4 shadow-sm">
                            <div class="flex items-center justify-around">
                                <button @click="shareProperty()" class="flex flex-col items-center gap-1 p-2 text-gray-600 hover:text-emerald-600 transition-colors">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z" />
                                    </svg>
                                    <span class="text-xs">Partager</span>
                                </button>
                                <button @click="toggleFavorite()" class="flex flex-col items-center gap-1 p-2 text-gray-600 hover:text-red-500 transition-colors">
                                    <svg class="w-6 h-6" :fill="isFavorite ? 'currentColor' : 'none'" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                                    </svg>
                                    <span class="text-xs">Sauvegarder</span>
                                </button>
                                <button onclick="window.print()" class="flex flex-col items-center gap-1 p-2 text-gray-600 hover:text-emerald-600 transition-colors">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                                    </svg>
                                    <span class="text-xs">Imprimer</span>
                                </button>
                            </div>
                        </div>

                    </div>
                </div>

            </div>
        </div>

        {{-- Similar Properties --}}
        @if ($similarResidences->isNotEmpty())
            <div class="bg-white border-t">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
                    <h2 class="text-2xl font-bold text-gray-900 mb-8">Biens similaires</h2>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                        @foreach ($similarResidences as $similar)
                            <a href="{{ route('residences.show', $similar) }}"
                                class="group bg-white rounded-2xl overflow-hidden border border-gray-100 hover:shadow-lg transition-shadow">
                                <div class="aspect-4/3 relative overflow-hidden">
                                    @if ($similar->photos->isNotEmpty())
                                        <img src="{{ $similar->photos->first()->url }}" alt="{{ $similar->name }}"
                                            class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                                    @else
                                        <div class="w-full h-full bg-gray-200 flex items-center justify-center">
                                            <span class="text-gray-400">Aucune photo</span>
                                        </div>
                                    @endif
                                </div>
                                <div class="p-4">
                                    <h3 class="font-semibold text-gray-900 truncate">{{ $similar->name }}</h3>
                                    <p class="text-sm text-gray-500 mb-2">{{ $similar->commune }}</p>
                                    <p class="text-lg font-bold text-emerald-600">
                                        {{ number_format($similar->price, 0, ',', ' ') }} FCFA
                                        <span class="text-sm font-normal text-gray-500">/jour</span>
                                    </p>
                                </div>
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif

        {{-- Photo Gallery Modal --}}
        <div x-show="showGallery" x-cloak
            class="fixed inset-0 z-50 bg-black/95 flex items-center justify-center"
            @keydown.escape.window="showGallery = false">
            <button @click="showGallery = false" class="absolute top-4 right-4 text-white hover:text-gray-300 z-50">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>

            <button @click="prevPhoto()" class="absolute left-4 text-white hover:text-gray-300">
                <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
            </button>

            <img :src="photos[currentPhotoIndex]" class="max-w-full max-h-[90vh] object-contain">

            <button @click="nextPhoto()" class="absolute right-4 text-white hover:text-gray-300">
                <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </button>

            <div class="absolute bottom-4 text-white text-sm">
                <span x-text="currentPhotoIndex + 1"></span> / <span x-text="photos.length"></span>
            </div>
        </div>

    </div>

    @push('scripts')
        <script>
            function locationPage() {
                return {
                    showGallery: false,
                    showContactForm: false,
                    currentPhotoIndex: 0,
                    isFavorite: false,
                    photos: @json($residence->photos->pluck('url')),

                    openGallery(index) {
                        this.currentPhotoIndex = index;
                        this.showGallery = true;
                    },

                    nextPhoto() {
                        this.currentPhotoIndex = (this.currentPhotoIndex + 1) % this.photos.length;
                    },

                    prevPhoto() {
                        this.currentPhotoIndex = (this.currentPhotoIndex - 1 + this.photos.length) % this.photos.length;
                    },

                    shareProperty() {
                        if (navigator.share) {
                            navigator.share({
                                title: '{{ $residence->name }}',
                                text: 'Découvrez ce bien à louer : {{ $residence->name }} à {{ $residence->commune }}',
                                url: window.location.href
                            });
                        } else {
                            navigator.clipboard.writeText(window.location.href);
                            alert('Lien copié dans le presse-papiers !');
                        }
                    },

                    toggleFavorite() {
                        this.isFavorite = !this.isFavorite;

                        @auth
                        fetch('{{ route("favorites.toggle", $residence) }}', {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'Accept': 'application/json',
                                'Content-Type': 'application/json',
                            },
                        })
                        .then(res => res.json())
                        .then(data => {
                            this.isFavorite = data.favorited ?? this.isFavorite;
                        })
                        .catch(() => {
                            // Revert on failure
                            this.isFavorite = !this.isFavorite;
                        });
                        @else
                        // Visiteur non connecté — stocker en localStorage
                        let favs = JSON.parse(localStorage.getItem('rezi_favorites') || '[]');
                        const id = {{ $residence->id }};
                        if (this.isFavorite) {
                            if (!favs.includes(id)) favs.push(id);
                        } else {
                            favs = favs.filter(f => f !== id);
                        }
                        localStorage.setItem('rezi_favorites', JSON.stringify(favs));
                        @endauth
                    }
                }
            }
        </script>
    @endpush
@endsection
