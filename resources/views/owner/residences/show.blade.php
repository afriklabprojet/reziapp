@extends('layouts.owner')

@section('title', $residence->name . ' - Rezi Studio Meublé Faya')

@section('owner-content')
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- En-tête -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-8">
            <div>
                <a href="{{ route('owner.residences.index') }}"
                    class="text-blue-600 hover:text-blue-700 flex items-center mb-2">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                    Retour à mes résidences
                </a>
                <h1 class="text-3xl font-bold text-gray-900">{{ $residence->name }}</h1>
                <p class="text-gray-600">{{ $residence->commune }}, {{ $residence->quartier }}</p>
                @if ($residence->listing_score)
                    @php
                        $sc = $residence->listing_score;
                        $scColor = $sc >= 80 ? 'emerald' : ($sc >= 60 ? 'blue' : ($sc >= 40 ? 'amber' : 'red'));
                        $scLabel = $sc >= 80 ? 'Excellent' : ($sc >= 60 ? 'Très bien' : ($sc >= 40 ? 'Bien' : 'À améliorer'));
                    @endphp
                    <a href="{{ route('owner.listing-score.show', $residence) }}"
                        class="inline-flex items-center gap-1.5 mt-2 px-2.5 py-1 rounded-full text-xs font-semibold bg-{{ $scColor }}-100 text-{{ $scColor }}-700 hover:bg-{{ $scColor }}-200 transition-colors">
                        ⭐ Score qualité : {{ $sc }}/100 — {{ $scLabel }}
                    </a>
                @else
                    <a href="{{ route('owner.listing-score.show', $residence) }}"
                        class="inline-flex items-center gap-1 mt-2 text-xs text-gray-400 hover:text-[#F16A00] transition-colors">
                        ⭐ Calculer le score qualité →
                    </a>
                @endif
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('owner.cohosts.index', $residence) }}"
                    class="inline-flex items-center gap-2 px-4 py-2.5 bg-white border border-gray-200 text-gray-700 text-sm font-semibold rounded-xl hover:bg-gray-50 hover:border-gray-300 transition-all duration-150">
                    <svg class="w-4.5 h-4.5 text-gray-500" fill="none" stroke="currentColor" stroke-width="1.8"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z" />
                    </svg>
                    Co-hôtes
                </a>
                <a href="{{ route('owner.channels.index', $residence) }}"
                    class="inline-flex items-center gap-2 px-4 py-2.5 bg-white border border-gray-200 text-gray-700 text-sm font-semibold rounded-xl hover:bg-gray-50 hover:border-gray-300 transition-all duration-150">
                    <svg class="w-4.5 h-4.5 text-gray-500" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M13.19 8.688a4.5 4.5 0 011.242 7.244l-4.5 4.5a4.5 4.5 0 01-6.364-6.364l1.757-1.757m13.35-.622l1.757-1.757a4.5 4.5 0 00-6.364-6.364l-4.5 4.5a4.5 4.5 0 001.242 7.244" />
                    </svg>
                    Channels
                </a>
                <a href="{{ route('owner.residences.edit', $residence) }}"
                    class="inline-flex items-center gap-2 px-4 py-2.5 bg-white border border-gray-200 text-gray-700 text-sm font-semibold rounded-xl hover:bg-gray-50 hover:border-gray-300 transition-all duration-150">
                    <svg class="w-4.5 h-4.5 text-gray-500" fill="none" stroke="currentColor" stroke-width="1.8"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125" />
                    </svg>
                    Modifier
                </a>
                <a href="{{ route('owner.pricing.index', $residence) }}"
                    class="inline-flex items-center gap-2 px-4 py-2.5 bg-white border border-gray-200 text-gray-700 text-sm font-semibold rounded-xl hover:bg-gray-50 hover:border-gray-300 transition-all duration-150">
                    <svg class="w-4.5 h-4.5 text-gray-500" fill="none" stroke="currentColor" stroke-width="1.8"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Tarifs
                </a>
                <form method="POST" action="{{ route('owner.residences.toggle-availability', $residence) }}"
                    class="inline">
                    @csrf
                    @method('PATCH')
                    <button type="submit"
                        class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-semibold rounded-xl transition-all duration-150 {{ $residence->is_available ? 'bg-amber-50 text-amber-700 border border-amber-200 hover:bg-amber-100' : 'bg-emerald-50 text-emerald-700 border border-emerald-200 hover:bg-emerald-100' }}">
                        @if ($residence->is_available)
                            <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" stroke-width="1.8"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25v13.5m-7.5-13.5v13.5" />
                            </svg>
                        @else
                            <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" stroke-width="1.8"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        @endif
                        {{ $residence->is_available ? 'Marquer occupée' : 'Marquer disponible' }}
                    </button>
                </form>
            </div>
        </div>

        <!-- Messages flash -->
        @if (session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6" role="alert">
                {{ session('success') }}
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Colonne principale -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Galerie photos -->
                <div class="card" x-data="{ activePhoto: 0 }">
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">Photos</h2>

                    @if ($residence->photos->count() > 0)
                        <!-- Photo principale -->
                        <div class="relative h-80 bg-gray-200 rounded-lg overflow-hidden mb-4">
                            @foreach ($residence->photos as $index => $photo)
                                <img loading="lazy" src="{{ storage_url($photo->path) }}" alt="Visuel {{ $index + 1 }}"
                                    class="w-full h-full object-cover transition-opacity duration-300"
                                    x-show="activePhoto === {{ $index }}">
                            @endforeach

                            <!-- Navigation -->
                            @if ($residence->photos->count() > 1)
                                <button
                                    @click="activePhoto = (activePhoto - 1 + {{ $residence->photos->count() }}) % {{ $residence->photos->count() }}"
                                    class="absolute left-2 top-1/2 -translate-y-1/2 bg-black/50 text-white p-2 rounded-full hover:bg-black/70">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M15 19l-7-7 7-7" />
                                    </svg>
                                </button>
                                <button @click="activePhoto = (activePhoto + 1) % {{ $residence->photos->count() }}"
                                    class="absolute right-2 top-1/2 -translate-y-1/2 bg-black/50 text-white p-2 rounded-full hover:bg-black/70">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 5l7 7-7 7" />
                                    </svg>
                                </button>
                            @endif
                        </div>

                        <!-- Miniatures -->
                        <div class="grid grid-cols-4 sm:grid-cols-5 gap-2">
                            @foreach ($residence->photos as $index => $photo)
                                <div class="relative">
                                    <button @click="activePhoto = {{ $index }}"
                                        class="w-full h-16 rounded overflow-hidden border-2 transition-colors"
                                        :class="activePhoto === {{ $index }} ? 'border-blue-500' : 'border-transparent'">
                                        <img loading="lazy" src="{{ storage_url($photo->path) }}"
                                            alt="Miniature {{ $index + 1 }}" class="w-full h-full object-cover">
                                    </button>
                                    @if ($photo->is_primary)
                                        <span class="absolute -top-1 -right-1 bg-blue-500 text-white text-xs px-1 rounded">
                                            ★
                                        </span>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="h-48 bg-gray-100 rounded-lg flex items-center justify-center">
                            <div class="text-center text-gray-500">
                                <svg class="w-12 h-12 mx-auto mb-2" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                <p>Aucune photo</p>
                                <a href="{{ route('owner.residences.edit', $residence) }}"
                                    class="text-blue-600 hover:underline text-sm">
                                    Ajouter des photos
                                </a>
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Description -->
                <div class="card">
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">Description</h2>
                    <p class="text-gray-700 whitespace-pre-line">{{ $residence->description }}</p>
                </div>

                <!-- Équipements -->
                <div class="card">
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">Équipements</h2>
                    @if ($residence->amenities->count() > 0)
                        <div class="flex flex-wrap gap-2">
                            @foreach ($residence->amenities as $amenity)
                                <span class="px-3 py-1 bg-gray-100 text-gray-700 rounded-full text-sm">
                                    {{ $amenity->icon ?? '✓' }} {{ $amenity->name }}
                                </span>
                            @endforeach
                        </div>
                    @else
                        <p class="text-gray-500">Aucun équipement renseigné</p>
                    @endif
                </div>

                <!-- Localisation -->
                <div class="card">
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">Localisation</h2>
                    <div class="mb-4">
                        <p class="text-gray-700">
                            <strong>Adresse:</strong> {{ $residence->address }}
                        </p>
                        <p class="text-gray-600">
                            {{ $residence->quartier }}, {{ $residence->commune }}
                        </p>
                    </div>
                    @if($residence->latitude && $residence->longitude)
                        <div
                            x-data="ownerResidenceMap({{ \Illuminate\Support\Js::encode([
                                'lat' => (float) $residence->latitude,
                                'lng' => (float) $residence->longitude,
                                'title' => $residence->name,
                                'address' => $residence->address,
                            ]) }})"
                            x-init="init()"
                        >
                            <div id="residence-map" x-ref="map" class="h-64 rounded-lg overflow-hidden"></div>
                        </div>
                    @else
                        <div class="h-64 bg-gray-200 rounded-lg flex items-center justify-center">
                            <p class="text-gray-500">Coordonnées GPS non renseignées</p>
                        </div>
                    @endif
                </div>

                <!-- Demandes de contact récentes -->
                <div class="card">
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">Demandes de contact récentes</h2>
                    @if ($residence->contacts->count() > 0)
                        <div class="divide-y divide-gray-200">
                            @foreach ($residence->contacts as $contact)
                                <div class="py-3 {{ $loop->first ? '' : 'pt-3' }}">
                                    <div class="flex justify-between items-start">
                                        <div>
                                            <p class="font-medium text-gray-900">{{ $contact->name }}</p>
                                            <p class="text-sm text-gray-600">{{ $contact->email }}</p>
                                            @if ($contact->phone)
                                                <p class="text-sm text-gray-600">{{ $contact->phone }}</p>
                                            @endif
                                        </div>
                                        <span class="text-xs text-gray-500">
                                            {{ $contact->created_at->diffForHumans() }}
                                        </span>
                                    </div>
                                    <p class="text-sm text-gray-700 mt-2">{{ Str::limit($contact->message, 100) }}</p>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-gray-500">Aucune demande de contact pour le moment</p>
                    @endif
                </div>
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                <!-- Statuts -->
                <div class="card">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Statut</h3>

                    <div class="space-y-3">
                        <!-- Statut validation -->
                        <div class="flex items-center justify-between">
                            <span class="text-gray-600">Validation</span>
                            @if (in_array($residence->status, ['active', 'approved']))
                                <span class="px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800">
                                    Approuvée
                                </span>
                            @elseif($residence->status === 'pending')
                                <span class="px-2 py-1 text-xs font-medium rounded-full bg-yellow-100 text-yellow-800">
                                    En attente
                                </span>
                            @else
                                <span class="px-2 py-1 text-xs font-medium rounded-full bg-red-100 text-red-800">
                                    Rejetée
                                </span>
                            @endif
                        </div>

                        <!-- Disponibilité -->
                        <div class="flex items-center justify-between">
                            <span class="text-gray-600">Disponibilité</span>
                            @if ($residence->is_available)
                                <span class="px-2 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-800">
                                    Disponible
                                </span>
                            @else
                                <span class="px-2 py-1 text-xs font-medium rounded-full bg-gray-100 text-gray-800">
                                    Occupée
                                </span>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Prix -->
                <div class="card">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Tarifs</h3>

                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-gray-600">Par jour</span>
                            <span class="font-bold text-blue-600">
                                {{ number_format($residence->price, 0, ',', ' ') }} FCFA
                            </span>
                        </div>

                        @if ($residence->price_per_week)
                            <div class="flex items-center justify-between">
                                <span class="text-gray-600">Par semaine</span>
                                <span class="font-semibold text-gray-900">
                                    {{ number_format($residence->price_per_week, 0, ',', ' ') }} FCFA
                                </span>
                            </div>
                        @endif

                        @if ($residence->price_per_day)
                            <div class="flex items-center justify-between">
                                <span class="text-gray-600">Par jour</span>
                                <span class="font-semibold text-gray-900">
                                    {{ number_format($residence->price_per_day, 0, ',', ' ') }} FCFA
                                </span>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Statistiques -->
                <div class="card">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Statistiques</h3>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="text-center p-3 bg-gray-50 rounded-lg">
                            <div class="text-2xl font-bold text-gray-900">{{ $residence->views_count }}</div>
                            <div class="text-sm text-gray-600">Vues</div>
                        </div>
                        <div class="text-center p-3 bg-gray-50 rounded-lg">
                            <div class="text-2xl font-bold text-gray-900">{{ $residence->contacts_count }}</div>
                            <div class="text-sm text-gray-600">Contacts</div>
                        </div>
                    </div>
                </div>

                <!-- Informations -->
                <div class="card">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Informations</h3>

                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Créée le</span>
                            <span class="text-gray-900">{{ $residence->created_at->format('d/m/Y') }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Modifiée le</span>
                            <span class="text-gray-900">{{ $residence->updated_at->format('d/m/Y') }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Photos</span>
                            <span class="text-gray-900">{{ $residence->photos->count() }}</span>
                        </div>
                    </div>
                </div>

                <!-- Co-hôtes -->
                <div class="card">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-900">Co-hôtes</h3>
                        <a href="{{ route('owner.cohosts.create', $residence) }}"
                            class="inline-flex items-center gap-1 text-xs font-semibold text-[#CC5A00] hover:text-[#A34700] transition-colors">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                            </svg>
                            Inviter
                        </a>
                    </div>

                    @php
                        $cohosts = $residence->coHosts ?? collect();
                    @endphp

                    @if ($cohosts->count() > 0)
                        <div class="space-y-2.5">
                            @foreach ($cohosts->take(3) as $cohost)
                                <div class="flex items-center gap-3">
                                    <div
                                        class="w-8 h-8 rounded-full bg-linear-to-br from-[#FFE7D1] to-amber-100 flex items-center justify-center shrink-0">
                                        <span
                                            class="text-xs font-bold text-[#A34700]">{{ strtoupper(substr($cohost->user->name ?? $cohost->email, 0, 1)) }}</span>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <p class="text-sm font-medium text-gray-900 truncate">
                                            {{ $cohost->user->name ?? $cohost->email }}</p>
                                        <p class="text-[11px] text-gray-500">{{ ucfirst($cohost->status ?? 'pending') }}
                                        </p>
                                    </div>
                                    @if (($cohost->status ?? 'pending') === 'active')
                                        <span class="w-2 h-2 rounded-full bg-emerald-400 shrink-0"></span>
                                    @elseif(($cohost->status ?? 'pending') === 'pending')
                                        <span class="w-2 h-2 rounded-full bg-amber-400 shrink-0"></span>
                                    @endif
                                </div>
                            @endforeach
                        </div>

                        @if ($cohosts->count() > 3)
                            <p class="mt-2 text-xs text-gray-500">+ {{ $cohosts->count() - 3 }} autre(s)</p>
                        @endif

                        <a href="{{ route('owner.cohosts.index', $residence) }}"
                            class="mt-3 inline-flex items-center gap-1 text-sm font-medium text-gray-600 hover:text-gray-900 transition-colors">
                            Voir tous
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                            </svg>
                        </a>
                    @else
                        <div class="text-center py-3">
                            <div class="w-10 h-10 mx-auto mb-2 rounded-full bg-gray-50 flex items-center justify-center">
                                <svg class="w-5 h-5 text-gray-300" fill="none" stroke="currentColor"
                                    stroke-width="1.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z" />
                                </svg>
                            </div>
                            <p class="text-sm text-gray-500 mb-2">Aucun co-hôte</p>
                            <a href="{{ route('owner.cohosts.create', $residence) }}"
                                class="text-sm font-semibold text-[#CC5A00] hover:text-[#A34700] transition-colors">
                                Inviter un co-hôte →
                            </a>
                        </div>
                    @endif
                </div>

                <!-- Actions danger -->
                <div class="card border-red-200">
                    <h3 class="text-lg font-semibold text-red-600 mb-4">Zone de danger</h3>

                    <form method="POST" action="{{ route('owner.residences.destroy', $residence) }}"
                        onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cette résidence ? Cette action est irréversible.')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="w-full btn-danger">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                            Supprimer la résidence
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

<x-google-maps-loader stack="owner-scripts" />
