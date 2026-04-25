@extends('layouts.owner')

@section('title', 'Modifier - ' . $residence->name . ' - REZI')

@section('owner-content')
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- En-tête -->
        <div class="mb-8">
            <a href="{{ route('owner.residences.show', $residence) }}"
                class="text-blue-600 hover:text-blue-700 flex items-center mb-4">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
                Retour à la résidence
            </a>
            <h1 class="text-3xl font-bold text-gray-900">Modifier la résidence</h1>
            <p class="text-gray-600 mt-1">{{ $residence->name }}</p>
        </div>

        <!-- Messages flash -->
        @if (session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6" role="alert">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6" role="alert">
                <strong>Erreur!</strong> Veuillez corriger les erreurs ci-dessous.
            </div>
        @endif

        <!-- Formulaire d'édition -->
        <form method="POST" action="{{ route('owner.residences.update', $residence) }}" enctype="multipart/form-data"
            x-data="{
                ...residenceEditForm(@js(['latitude' => old('latitude', $residence->latitude), 'longitude' => old('longitude', $residence->longitude)])),
                typeLocation: '{{ old('type_location', $residence->type_location ?? 'residence_meublee') }}',
                get pricePeriod() {
                    return 'day';
                },
                get priceLabel() {
                    return 'Prix par jour (FCFA)';
                }
            }">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Colonne principale -->
                <div class="lg:col-span-2 space-y-6">

                    <!-- Informations générales -->
                    <div class="card">
                        <h2 class="text-xl font-semibold text-gray-900 mb-4">Informations générales</h2>

                        <div class="space-y-4">
                            <div>
                                <label for="name" class="block text-sm font-medium text-gray-700 mb-1">
                                    Nom de la résidence *
                                </label>
                                <input type="text" id="name" name="name" required
                                    class="input-field @error('name') border-red-500 @enderror"
                                    value="{{ old('name', $residence->name) }}">
                                @error('name')
                                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="description" class="block text-sm font-medium text-gray-700 mb-1">
                                    Description * (minimum 50 caractères)
                                </label>
                                <textarea id="description" name="description" required rows="5"
                                    class="input-field @error('description') border-red-500 @enderror">{{ old('description', $residence->description) }}</textarea>
                                @error('description')
                                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Localisation -->
                    <div class="card">
                        <h2 class="text-xl font-semibold text-gray-900 mb-4">Localisation</h2>

                        <div class="space-y-4" x-data="{
                            selectedCountry: '{{ old('country_code', $residence->country_code ?? 'CI') }}',
                            selectedCity: '{{ old('city', $residence->city ?? '') }}',
                            countries: @js($countries->map(fn($c) => ['code' => $c->code, 'name' => $c->name])),
                            allCities: @js($cities->map(fn($c) => ['id' => $c->id, 'name' => $c->name, 'country_id' => $c->country_id, 'country_code' => $c->country?->code, 'communes' => $c->communes->pluck('name')])),
                            get filteredCities() {
                                return this.allCities.filter(c => c.country_code === this.selectedCountry);
                            },
                            get filteredCommunes() {
                                const city = this.allCities.find(c => c.name === this.selectedCity && c.country_code === this.selectedCountry);
                                return city ? city.communes : [];
                            }
                        }">
                            <div x-data="addressAutocomplete()">
                                <label for="address" class="block text-sm font-medium text-gray-700 mb-1">
                                    Adresse complète *
                                </label>
                                <div class="relative">
                                    <input type="text" id="address" name="address" required
                                        x-ref="addressInput"
                                        class="input-field @error('address') border-red-500 @enderror"
                                        value="{{ old('address', $residence->address) }}"
                                        placeholder="Ex: Rue des Jardins, Cocody, Abidjan"
                                        autocomplete="new-password">
                                    <div class="absolute inset-y-0 right-3 flex items-center pointer-events-none">
                                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        </svg>
                                    </div>
                                </div>
                                @error('address')
                                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                {{-- Pays --}}
                                <div>
                                    <label for="country_code" class="block text-sm font-medium text-gray-700 mb-1">
                                        Pays *
                                    </label>
                                    <select id="country_code" name="country_code" required x-model="selectedCountry"
                                        @change="selectedCity = ''"
                                        class="input-field @error('country_code') border-red-500 @enderror">
                                        <template x-for="country in countries" :key="country.code">
                                            <option :value="country.code" x-text="country.name"></option>
                                        </template>
                                    </select>
                                    @error('country_code')
                                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                    @enderror
                                </div>

                                {{-- Ville --}}
                                <div>
                                    <label for="city" class="block text-sm font-medium text-gray-700 mb-1">
                                        Ville *
                                    </label>
                                    <select id="city" name="city" required x-model="selectedCity"
                                        class="input-field @error('city') border-red-500 @enderror">
                                        <option value="">-- Sélectionnez --</option>
                                        <template x-for="city in filteredCities" :key="city.name">
                                            <option :value="city.name" x-text="city.name"></option>
                                        </template>
                                    </select>
                                    @error('city')
                                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                {{-- Commune --}}
                                <div>
                                    <label for="commune" class="block text-sm font-medium text-gray-700 mb-1">
                                        Commune *
                                    </label>
                                    <template x-if="filteredCommunes.length > 0">
                                        <select id="commune" name="commune" required
                                            class="input-field @error('commune') border-red-500 @enderror">
                                            <option value="">-- Sélectionnez --</option>
                                            <template x-for="commune in filteredCommunes" :key="commune">
                                                <option :value="commune" x-text="commune"
                                                    :selected="commune === '{{ old('commune', $residence->commune) }}'">
                                                </option>
                                            </template>
                                        </select>
                                    </template>
                                    <template x-if="filteredCommunes.length === 0">
                                        <input type="text" id="commune" name="commune" required
                                            class="input-field @error('commune') border-red-500 @enderror"
                                            value="{{ old('commune', $residence->commune) }}"
                                            placeholder="Nom de la commune">
                                    </template>
                                    @error('commune')
                                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label for="quartier" class="block text-sm font-medium text-gray-700 mb-1">
                                        Quartier *
                                    </label>
                                    <input type="text" id="quartier" name="quartier" required
                                        class="input-field @error('quartier') border-red-500 @enderror"
                                        value="{{ old('quartier', $residence->quartier) }}"
                                        placeholder="Ex: Angré, Riviera 2, etc.">
                                    @error('quartier')
                                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <!-- Carte pour positionnement -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Position sur la carte * <span class="text-gray-500 font-normal">(Cliquez pour
                                        positionner)</span>
                                </label>
                                <div id="location-map"
                                    class="h-80 bg-gray-200 rounded-lg border-2 border-dashed border-gray-300"
                                    x-ref="map"></div>
                                <div class="mt-2 flex items-center text-sm text-gray-600">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                    <span
                                        x-text="latitude && longitude ? `Lat: ${latitude.toFixed(6)}, Lng: ${longitude.toFixed(6)}` : 'Position actuelle'"></span>
                                </div>
                            </div>

                            <!-- Champs cachés pour lat/lng -->
                            <input type="hidden" name="latitude" x-model="latitude">
                            <input type="hidden" name="longitude" x-model="longitude">

                            @error('latitude')
                                <p class="text-red-500 text-sm">{{ $message }}</p>
                            @enderror
                            @error('longitude')
                                <p class="text-red-500 text-sm">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <!-- Tarifs -->
                    <div class="card">
                        <h2 class="text-xl font-semibold text-gray-900 mb-4">Tarifs</h2>

                        {{-- Type de location --}}
                        <div class="mb-4">
                            <label for="type_location" class="block text-sm font-medium text-gray-700 mb-1">
                                Type de location *
                            </label>
                            <select id="type_location" name="type_location" required x-model="typeLocation"
                                class="input-field @error('type_location') border-red-500 @enderror">
                                <option value="residence_meublee">Résidence meublée</option>
                                <option value="apartment">Appartement (location longue durée)</option>
                                <option value="hotel">Hôtel</option>
                            </select>
                            <input type="hidden" name="price_period" :value="pricePeriod">
                            @error('type_location')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Info dynamique --}}
                        <div class="mb-4 p-3 rounded-lg text-sm flex items-center gap-2"
                            :class="{
                                'bg-blue-50 text-blue-700': typeLocation === 'apartment',
                                'bg-orange-50 text-orange-700': typeLocation === 'residence_meublee',
                                'bg-purple-50 text-purple-700': typeLocation === 'hotel'
                            }">
                            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" />
                            </svg>
                            <span>Facturation <strong>journalière</strong> — prix par jour requis.</span>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            {{-- Prix journalier --}}
                            <div>
                                <label for="price_per_day" class="block text-sm font-medium text-gray-700 mb-1">
                                    Prix par jour (FCFA) *
                                </label>
                                <input type="number" id="price_per_day" name="price_per_day"
                                    min="1000" step="500" required
                                    class="input-field @error('price_per_day') border-red-500 @enderror"
                                    value="{{ old('price_per_day', $residence->price_per_day) }}">
                                @error('price_per_day')
                                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Caractéristiques -->
                    <div class="card">
                        <h2 class="text-xl font-semibold text-gray-900 mb-4">Caractéristiques</h2>

                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <div>
                                <label for="bedrooms" class="block text-sm font-medium text-gray-700 mb-1">
                                    Chambres
                                </label>
                                <input type="number" id="bedrooms" name="bedrooms" min="0" max="20"
                                    class="input-field @error('bedrooms') border-red-500 @enderror"
                                    value="{{ old('bedrooms', $residence->bedrooms) }}">
                                @error('bedrooms')
                                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="bathrooms" class="block text-sm font-medium text-gray-700 mb-1">
                                    Salles de bain
                                </label>
                                <input type="number" id="bathrooms" name="bathrooms" min="1" max="10"
                                    class="input-field @error('bathrooms') border-red-500 @enderror"
                                    value="{{ old('bathrooms', $residence->bathrooms) }}">
                                @error('bathrooms')
                                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="max_guests" class="block text-sm font-medium text-gray-700 mb-1">
                                    Capacité (pers.)
                                </label>
                                <input type="number" id="max_guests" name="max_guests" min="1" max="50"
                                    class="input-field @error('max_guests') border-red-500 @enderror"
                                    value="{{ old('max_guests', $residence->max_guests) }}">
                                @error('max_guests')
                                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="surface_area" class="block text-sm font-medium text-gray-700 mb-1">
                                    Surface (m²)
                                </label>
                                <input type="number" id="surface_area" name="surface_area" min="5" max="10000"
                                    class="input-field @error('surface_area') border-red-500 @enderror"
                                    value="{{ old('surface_area', $residence->surface_area) }}">
                                @error('surface_area')
                                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-4">
                            <div>
                                <label for="floor" class="block text-sm font-medium text-gray-700 mb-1">
                                    Étage
                                </label>
                                <input type="number" id="floor" name="floor" min="-5" max="100"
                                    class="input-field @error('floor') border-red-500 @enderror"
                                    value="{{ old('floor', $residence->floor) }}">
                                @error('floor')
                                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="flex items-end pb-1">
                                <label class="flex items-center">
                                    <input type="checkbox" name="has_elevator" value="1"
                                        {{ old('has_elevator', $residence->has_elevator) ? 'checked' : '' }}
                                        class="form-checkbox h-5 w-5 text-blue-600">
                                    <span class="ml-2 text-sm text-gray-700">Ascenseur</span>
                                </label>
                            </div>
                        </div>

                        {{-- Sprint 3 — Instant Book --}}
                        <div class="mt-6 p-4 bg-linear-to-r from-emerald-50 to-teal-50 rounded-xl ring-1 ring-emerald-100">
                            <label class="flex items-start gap-3 cursor-pointer">
                                <input type="checkbox" name="instant_book" value="1"
                                    {{ old('instant_book', $residence->instant_book) ? 'checked' : '' }}
                                    class="mt-1 form-checkbox h-5 w-5 text-emerald-600 rounded">
                                <span class="flex-1">
                                    <span class="flex text-sm font-semibold text-gray-900 items-center gap-1.5">
                                        ⚡ Réservation instantanée
                                    </span>
                                    <span class="block text-xs text-gray-600 mt-1">
                                        Les voyageurs peuvent réserver immédiatement sans attendre votre validation.
                                        Votre logement apparaît plus haut dans les résultats et reçoit jusqu'à <strong>3× plus de demandes</strong>.
                                    </span>
                                </span>
                            </label>
                        </div>
                    </div>

                    <!-- Horaires et séjour -->
                    <div class="card">
                        <h2 class="text-xl font-semibold text-gray-900 mb-4">Horaires & séjour</h2>

                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <div>
                                <label for="check_in_time" class="block text-sm font-medium text-gray-700 mb-1">
                                    Heure d'arrivée
                                </label>
                                <input type="time" id="check_in_time" name="check_in_time"
                                    class="input-field @error('check_in_time') border-red-500 @enderror"
                                    value="{{ old('check_in_time', $residence->check_in_time ? \Carbon\Carbon::parse($residence->check_in_time)->format('H:i') : '') }}">
                                @error('check_in_time')
                                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="check_out_time" class="block text-sm font-medium text-gray-700 mb-1">
                                    Heure de départ
                                </label>
                                <input type="time" id="check_out_time" name="check_out_time"
                                    class="input-field @error('check_out_time') border-red-500 @enderror"
                                    value="{{ old('check_out_time', $residence->check_out_time ? \Carbon\Carbon::parse($residence->check_out_time)->format('H:i') : '') }}">
                                @error('check_out_time')
                                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="min_nights" class="block text-sm font-medium text-gray-700 mb-1">
                                    Séjour min. (nuits)
                                </label>
                                <input type="number" id="min_nights" name="min_nights" min="1" max="365"
                                    class="input-field @error('min_nights') border-red-500 @enderror"
                                    value="{{ old('min_nights', $residence->min_nights) }}">
                                @error('min_nights')
                                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="max_nights" class="block text-sm font-medium text-gray-700 mb-1">
                                    Séjour max. (nuits)
                                </label>
                                <input type="number" id="max_nights" name="max_nights" min="1" max="365"
                                    class="input-field @error('max_nights') border-red-500 @enderror"
                                    value="{{ old('max_nights', $residence->max_nights) }}">
                                @error('max_nights')
                                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div class="mt-4">
                            <label class="flex items-center">
                                <input type="checkbox" name="instant_book" value="1"
                                    {{ old('instant_book', $residence->instant_book) ? 'checked' : '' }}
                                    class="form-checkbox h-5 w-5 text-blue-600">
                                <span class="ml-2 text-sm text-gray-700">Réservation instantanée (sans validation manuelle)</span>
                            </label>
                        </div>
                    </div>

                    <!-- Règles de la maison -->
                    <div class="card">
                        <h2 class="text-xl font-semibold text-gray-900 mb-4">Règles de la maison</h2>

                        <div class="space-y-4">
                            <div>
                                <label for="house_rules" class="block text-sm font-medium text-gray-700 mb-1">
                                    Règlement intérieur
                                </label>
                                <textarea id="house_rules" name="house_rules" rows="3"
                                    class="input-field @error('house_rules') border-red-500 @enderror"
                                    placeholder="Ex: Pas de bruit après 22h, pas de visiteurs non déclarés...">{{ old('house_rules', $residence->house_rules) }}</textarea>
                                @error('house_rules')
                                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <label class="flex items-center p-3 border border-gray-200 rounded-lg hover:bg-gray-50 cursor-pointer">
                                    <input type="checkbox" name="pets_allowed" value="1"
                                        {{ old('pets_allowed', $residence->pets_allowed) ? 'checked' : '' }}
                                        class="form-checkbox h-5 w-5 text-blue-600">
                                    <span class="ml-2 text-sm text-gray-700">🐾 Animaux autorisés</span>
                                </label>

                                <label class="flex items-center p-3 border border-gray-200 rounded-lg hover:bg-gray-50 cursor-pointer">
                                    <input type="checkbox" name="smoking_allowed" value="1"
                                        {{ old('smoking_allowed', $residence->smoking_allowed) ? 'checked' : '' }}
                                        class="form-checkbox h-5 w-5 text-blue-600">
                                    <span class="ml-2 text-sm text-gray-700">🚬 Fumeurs autorisés</span>
                                </label>

                                <label class="flex items-center p-3 border border-gray-200 rounded-lg hover:bg-gray-50 cursor-pointer">
                                    <input type="checkbox" name="parties_allowed" value="1"
                                        {{ old('parties_allowed', $residence->parties_allowed) ? 'checked' : '' }}
                                        class="form-checkbox h-5 w-5 text-blue-600">
                                    <span class="ml-2 text-sm text-gray-700">🎉 Fêtes autorisées</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Équipements -->
                    <div class="card">
                        <h2 class="text-xl font-semibold text-gray-900 mb-4">Équipements</h2>

                        <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                            @foreach ($amenities as $amenity)
                                <label
                                    class="flex items-center p-3 border border-gray-200 rounded-lg hover:bg-gray-50 cursor-pointer">
                                    <input type="checkbox" name="amenities[]" value="{{ $amenity->id }}"
                                        {{ in_array($amenity->id, old('amenities', $residence->amenities->pluck('id')->toArray())) ? 'checked' : '' }}
                                        class="form-checkbox h-5 w-5 text-blue-600">
                                    <span class="ml-2 text-sm text-gray-700">
                                        {{ $amenity->icon ?? '✓' }} {{ $amenity->name }}
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <!-- Photos existantes -->
                    <div class="card">
                        <h2 class="text-xl font-semibold text-gray-900 mb-4">Photos actuelles</h2>

                        @if ($residence->photos->count() > 0)
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4" x-data="{ dragging: null }">
                                @foreach ($residence->photos as $photo)
                                    <div class="relative group">
                                        <img loading="lazy" src="{{ storage_url($photo->path) }}"
                                            alt="Photo {{ $loop->iteration }}"
                                            class="w-full h-32 object-cover rounded-lg">

                                        <!-- Badge photo principale -->
                                        @if ($photo->is_primary)
                                            <span
                                                class="absolute top-2 left-2 bg-blue-500 text-white text-xs px-2 py-1 rounded-full">
                                                Principale
                                            </span>
                                        @endif

                                        <!-- Actions -->
                                        <div
                                            class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity rounded-lg flex items-center justify-center space-x-2">
                                            @if (!$photo->is_primary)
                                                <form method="POST"
                                                    action="{{ route('owner.residences.set-primary-photo', [$residence, $photo]) }}"
                                                    class="inline">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit"
                                                        class="p-2 bg-blue-500 text-white rounded-full hover:bg-blue-600"
                                                        title="Définir comme principale"
                                                        aria-label="Définir comme photo principale">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                            viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                                                        </svg>
                                                    </button>
                                                </form>
                                            @endif

                                            <form method="POST"
                                                action="{{ route('owner.residences.delete-photo', [$residence, $photo]) }}"
                                                class="inline" onsubmit="return confirm('Supprimer cette photo ?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                    class="p-2 bg-red-500 text-white rounded-full hover:bg-red-600"
                                                    title="Supprimer" aria-label="Supprimer la photo">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                    </svg>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-gray-500">Aucune photo pour le moment</p>
                        @endif
                    </div>

                    <!-- Ajouter des photos -->
                    <div class="card">
                        <h2 class="text-xl font-semibold text-gray-900 mb-4">Ajouter des photos</h2>

                        <div class="border-2 border-dashed border-gray-300 rounded-lg p-8 text-center hover:border-blue-400 transition-colors"
                            x-data="photoUploader()" @drop.prevent="handleDrop($event)"
                            @dragover.prevent="isDragging = true" @dragleave.prevent="isDragging = false"
                            :class="{ 'border-blue-400 bg-blue-50': isDragging }">
                            <input type="file" name="photos[]" id="photos" multiple
                                accept="image/jpeg,image/png,image/webp" class="hidden" @change="handleFiles($event)"
                                x-ref="fileInput">

                            <svg class="w-12 h-12 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>

                            <p class="text-gray-600 mb-2">Glissez-déposez vos photos ici</p>
                            <p class="text-gray-500 text-sm mb-4">ou</p>

                            <button type="button" @click="$refs.fileInput.click()" class="btn-secondary">
                                Parcourir les fichiers
                            </button>

                            <p class="text-xs text-gray-500 mt-4">
                                JPEG, PNG ou WEBP • Max 5 MB par photo • Max 10 photos au total
                            </p>

                            <!-- Prévisualisation -->
                            <div x-show="previews.length > 0" class="mt-6 grid grid-cols-4 gap-4">
                                <template x-for="(preview, index) in previews" :key="index">
                                    <div class="relative">
                                        <img loading="lazy" :src="preview" alt="Image"
                                            class="w-full h-20 object-cover rounded-lg">
                                        <button type="button" @click="removePreview(index)"
                                            class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center">
                                            ×
                                        </button>
                                    </div>
                                </template>
                            </div>
                        </div>

                        @error('photos')
                            <p class="text-red-500 text-sm mt-2">{{ $message }}</p>
                        @enderror
                        @error('photos.*')
                            <p class="text-red-500 text-sm mt-2">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="space-y-6">
                    <!-- Disponibilité -->
                    <div class="card">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Disponibilité</h3>

                        <label class="flex items-center">
                            <input type="checkbox" name="is_available" value="1"
                                {{ old('is_available', $residence->is_available) ? 'checked' : '' }}
                                class="form-checkbox h-5 w-5 text-blue-600">
                            <span class="ml-2 text-gray-700">Résidence disponible</span>
                        </label>
                        <p class="text-sm text-gray-500 mt-2">
                            Décochez si la résidence est actuellement occupée
                        </p>
                    </div>

                    <!-- Statut actuel -->
                    <div class="card">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Statut de validation</h3>

                        @if (in_array($residence->status, ['active', 'approved']))
                            <div class="flex items-center text-green-600">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                Résidence approuvée
                            </div>
                            <p class="text-sm text-green-600 mt-2">
                                <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M13 10V3L4 14h7v7l9-11h-7z" />
                                </svg>
                                Vos modifications seront appliquées immédiatement, sans nouvelle approbation.
                            </p>
                        @elseif($residence->status === 'pending')
                            <div class="flex items-center text-yellow-600">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                En attente de validation
                            </div>
                            <p class="text-sm text-gray-500 mt-2">
                                Notre équipe examine votre annonce
                            </p>
                        @else
                            <div class="flex items-center text-red-600">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                Résidence rejetée
                            </div>
                            <p class="text-sm text-gray-500 mt-2">
                                Modifiez votre annonce et elle sera réexaminée
                            </p>
                        @endif
                    </div>

                    <!-- Actions -->
                    <div class="card">
                        <button type="submit" class="w-full btn-primary mb-3">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M5 13l4 4L19 7" />
                            </svg>
                            Enregistrer les modifications
                        </button>

                        <a href="{{ route('owner.residences.show', $residence) }}"
                            class="w-full btn-secondary block text-center">
                            Annuler
                        </a>
                    </div>
                </div>
            </div>
        </form>
    </div>

    @push('owner-scripts')
        <script>
            function __addressAutocompleteCallback() {
                if (typeof window.__addressAutocompleteInit === 'function') {
                    window.__addressAutocompleteInit();
                }
            }
        </script>
        <script
            src="https://maps.googleapis.com/maps/api/js?key={{ config('services.google_maps.key') }}&libraries=places&callback=__addressAutocompleteCallback"
            async
            defer
        ></script>
    @endpush
@endsection
