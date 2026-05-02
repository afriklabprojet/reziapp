@extends('layouts.owner')

@section('title', 'Ajouter une résidence - REZI')

@section('owner-content')
    <div class="min-h-screen bg-gray-50 py-8">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">

            <div class="mb-8">
                <a href="{{ route('owner.residences.index') }}"
                    class="inline-flex items-center text-[#ff385c] hover:text-[#e00b41] mb-4">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                    Retour à mes résidences
                </a>
                <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">Ajouter une résidence</h1>
                <p class="text-gray-600 mt-2">Remplissez les informations de votre résidence meublée</p>
            </div>

            @if ($errors->any())
                <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 rounded-lg mb-6">
                    <strong>Veuillez corriger les erreurs ci-dessous</strong>
                    <ul class="mt-2 list-disc list-inside text-sm">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('owner.residences.store') }}" enctype="multipart/form-data"
                x-data="{
                    description: '{{ old('description', '') }}',
                    houseRules: '{{ old('house_rules', '') }}',
                    typeLocation: '{{ old('type_location', 'residence_meublee') }}',
                    aiLoading: false,
                    aiTitleLoading: false,
                    aiImproveLoading: false,
                    aiError: '',
                    get pricePeriod() {
                        return 'day';
                    },
                    get priceLabel() {
                        return 'Prix par jour (FCFA)';
                    },
                    get pricePlaceholder() {
                        return '15000';
                    },
                    get priceMin() {
                        return '1000';
                    },
                    get priceFieldName() {
                        return 'price_per_day';
                    },
                    getFormContext() {
                        const form = this.$root;
                        const fd = new FormData(form);
                        return {
                            type: fd.get('type') || '',
                            type_location: fd.get('type_location') || '',
                            commune: fd.get('commune_id') ? (form.querySelector('[name=commune_id] option:checked')?.textContent?.trim() || '') : '',
                            bedrooms: fd.get('bedrooms') || '',
                            bathrooms: fd.get('bathrooms') || '',
                            surface_area: fd.get('surface_area') || '',
                            max_guests: fd.get('max_guests') || '',
                            price: fd.get('price_per_day') || fd.get('price_per_month') || '',
                        };
                    },
                    async generateDescription() {
                        this.aiError = '';
                        const ctx = this.getFormContext();
                        if (!ctx.type) { this.aiError = 'Veuillez d\'abord sélectionner le type de résidence.'; return; }
                        this.aiLoading = true;
                        try {
                            const res = await fetch('{{ route('owner.ai.generate-description') }}', {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                                body: JSON.stringify(ctx),
                            });
                            const data = await res.json();
                            if (data.description) {
                                this.description = data.description;
                            } else {
                                this.aiError = data.error || 'Erreur lors de la génération.';
                            }
                        } catch (e) { this.aiError = 'Erreur de connexion.'; }
                        this.aiLoading = false;
                    },
                    async generateTitle() {
                        this.aiError = '';
                        const ctx = this.getFormContext();
                        if (!ctx.type) { this.aiError = 'Veuillez d\'abord sélectionner le type de résidence.'; return; }
                        this.aiTitleLoading = true;
                        try {
                            const res = await fetch('{{ route('owner.ai.generate-title') }}', {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                                body: JSON.stringify(ctx),
                            });
                            const data = await res.json();
                            if (data.title) {
                                document.getElementById('name').value = data.title;
                            } else {
                                this.aiError = data.error || 'Erreur lors de la génération.';
                            }
                        } catch (e) { this.aiError = 'Erreur de connexion.'; }
                        this.aiTitleLoading = false;
                    },
                    async improveDescription() {
                        if (this.description.length < 10) { this.aiError = 'Écrivez au moins quelques mots avant d\'améliorer.'; return; }
                        this.aiImproveLoading = true;
                        this.aiError = '';
                        try {
                            const ctx = this.getFormContext();
                            ctx.description = this.description;
                            const res = await fetch('{{ route('owner.ai.improve-description') }}', {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                                body: JSON.stringify(ctx),
                            });
                            const data = await res.json();
                            if (data.description) {
                                this.description = data.description;
                            } else {
                                this.aiError = data.error || 'Erreur lors de l\'amélioration.';
                            }
                        } catch (e) { this.aiError = 'Erreur de connexion.'; }
                        this.aiImproveLoading = false;
                    },
                }" class="space-y-6">
                @csrf

                {{-- SECTION 1: INFORMATIONS GÉNÉRALES --}}
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <div class="flex items-center mb-6">
                        <span class="flex items-center justify-center w-8 h-8 bg-[#ffd1da] text-[#ff385c] rounded-full text-sm font-bold mr-3">1</span>
                        <h2 class="text-xl font-semibold text-gray-900">Informations générales</h2>
                        <span class="ml-auto inline-flex items-center gap-1 px-2 py-0.5 bg-violet-100 text-violet-700 rounded-full text-[10px] font-bold uppercase tracking-wide">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                            IA disponible
                        </span>
                    </div>

                    {{-- Erreur IA --}}
                    <div x-show="aiError" x-cloak x-transition class="col-span-full bg-red-50 border border-red-200 text-red-600 text-xs rounded-lg px-3 py-2 md:col-span-2">
                        <span x-text="aiError"></span>
                        <button type="button" @click="aiError=''" class="ml-2 text-red-400 hover:text-red-600">&times;</button>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        {{-- Nom --}}
                        <div class="md:col-span-2">
                            <div class="flex items-center justify-between mb-2">
                                <label for="name" class="block text-sm font-medium text-gray-700">
                                    Nom de la résidence <span class="text-red-500">*</span>
                                </label>
                                <button type="button" @click="generateTitle()" :disabled="aiTitleLoading"
                                    class="inline-flex items-center gap-1 px-2.5 py-1 bg-violet-50 text-violet-700 rounded-lg text-xs font-medium hover:bg-violet-100 transition disabled:opacity-50">
                                    <svg x-show="!aiTitleLoading" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                    <svg x-show="aiTitleLoading" class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                                    <span x-text="aiTitleLoading ? 'Génération...' : 'Titre par IA'"></span>
                                </button>
                            </div>
                            <input type="text" id="name" name="name" value="{{ old('name') }}"
                                placeholder="Ex: Belle Villa à Cocody" required
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#ff385c] focus:border-[#ff385c]">
                            @error('name')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Type de résidence --}}
                        <div>
                            <label for="type" class="block text-sm font-medium text-gray-700 mb-2">
                                Type de résidence <span class="text-red-500">*</span>
                            </label>
                            <select id="type" name="type" required
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#ff385c] focus:border-[#ff385c]">
                                <option value="">Sélectionnez un type</option>
                                <option value="studio" {{ old('type') == 'studio' ? 'selected' : '' }}>Studio</option>
                                <option value="apartment" {{ old('type') == 'apartment' ? 'selected' : '' }}>Appartement</option>
                                <option value="house" {{ old('type') == 'house' ? 'selected' : '' }}>Maison</option>
                                <option value="villa" {{ old('type') == 'villa' ? 'selected' : '' }}>Villa</option>
                                <option value="duplex" {{ old('type') == 'duplex' ? 'selected' : '' }}>Duplex</option>
                                <option value="other" {{ old('type') == 'other' ? 'selected' : '' }}>Autre</option>
                            </select>
                            @error('type')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Type de location --}}
                        <div>
                            <label for="type_location" class="block text-sm font-medium text-gray-700 mb-2">
                                Type de location <span class="text-red-500">*</span>
                            </label>
                            <select id="type_location" name="type_location" required x-model="typeLocation"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#ff385c] focus:border-[#ff385c]">
                                <option value="residence_meublee" {{ old('type_location', 'residence_meublee') == 'residence_meublee' ? 'selected' : '' }}>Résidence meublée</option>
                                <option value="apartment" {{ old('type_location') == 'apartment' ? 'selected' : '' }}>Appartement (location longue durée)</option>
                                <option value="hotel" {{ old('type_location') == 'hotel' ? 'selected' : '' }}>Hôtel</option>
                            </select>
                            <input type="hidden" name="price_period" :value="pricePeriod">
                            @error('type_location')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                            @error('price_period')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Description --}}
                        <div class="md:col-span-2">
                            <div class="flex items-center justify-between mb-2">
                                <label for="description" class="block text-sm font-medium text-gray-700">
                                    Description <span class="text-red-500">*</span>
                                    <span class="text-gray-400">(min. 10 caractères)</span>
                                </label>
                                <div class="flex items-center gap-2">
                                    <button type="button" @click="generateDescription()" :disabled="aiLoading"
                                        class="inline-flex items-center gap-1 px-2.5 py-1 bg-violet-50 text-violet-700 rounded-lg text-xs font-medium hover:bg-violet-100 transition disabled:opacity-50">
                                        <svg x-show="!aiLoading" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                        <svg x-show="aiLoading" class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                                        <span x-text="aiLoading ? 'Génération...' : 'Générer par IA'"></span>
                                    </button>
                                    <button type="button" @click="improveDescription()" :disabled="aiImproveLoading || description.length < 10"
                                        class="inline-flex items-center gap-1 px-2.5 py-1 bg-amber-50 text-amber-700 rounded-lg text-xs font-medium hover:bg-amber-100 transition disabled:opacity-50">
                                        <svg x-show="!aiImproveLoading" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/></svg>
                                        <svg x-show="aiImproveLoading" class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                                        <span x-text="aiImproveLoading ? 'Amélioration...' : 'Améliorer'"></span>
                                    </button>
                                </div>
                            </div>
                            <textarea id="description" name="description" rows="4"
                                placeholder="Décrivez votre résidence en détail : ambiance, voisinage, points forts..."
                                required minlength="50" x-model="description"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#ff385c] focus:border-[#ff385c] resize-none">{{ old('description') }}</textarea>
                            <div class="flex justify-end mt-1">
                                <span class="text-sm" :class="description.length >= 50 ? 'text-[#ff385c]' : 'text-gray-400'">
                                    <span x-text="description.length"></span>/50
                                </span>
                            </div>
                            @error('description')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                {{-- SECTION 2: CARACTÉRISTIQUES --}}
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <div class="flex items-center mb-6">
                        <span class="flex items-center justify-center w-8 h-8 bg-[#ffd1da] text-[#ff385c] rounded-full text-sm font-bold mr-3">2</span>
                        <h2 class="text-xl font-semibold text-gray-900">Caractéristiques</h2>
                    </div>

                    <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                        {{-- Chambres --}}
                        <div>
                            <label for="bedrooms" class="block text-sm font-medium text-gray-700 mb-2">
                                Chambres <span class="text-red-500">*</span>
                            </label>
                            <select id="bedrooms" name="bedrooms" required
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#ff385c] focus:border-[#ff385c]">
                                <option value="0" {{ old('bedrooms') == '0' ? 'selected' : '' }}>Studio</option>
                                @for ($i = 1; $i <= 10; $i++)
                                    <option value="{{ $i }}" {{ old('bedrooms', 1) == $i ? 'selected' : '' }}>{{ $i }}</option>
                                @endfor
                            </select>
                            @error('bedrooms')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Salles de bain --}}
                        <div>
                            <label for="bathrooms" class="block text-sm font-medium text-gray-700 mb-2">
                                Salles de bain <span class="text-red-500">*</span>
                            </label>
                            <select id="bathrooms" name="bathrooms" required
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#ff385c] focus:border-[#ff385c]">
                                @for ($i = 1; $i <= 5; $i++)
                                    <option value="{{ $i }}" {{ old('bathrooms', 1) == $i ? 'selected' : '' }}>{{ $i }}</option>
                                @endfor
                            </select>
                            @error('bathrooms')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Surface --}}
                        <div>
                            <label for="surface_area" class="block text-sm font-medium text-gray-700 mb-2">
                                Surface (m²)
                            </label>
                            <input type="number" id="surface_area" name="surface_area" value="{{ old('surface_area') }}"
                                placeholder="75" min="5" max="10000"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#ff385c] focus:border-[#ff385c]">
                            @error('surface_area')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Capacité --}}
                        <div>
                            <label for="max_guests" class="block text-sm font-medium text-gray-700 mb-2">
                                Capacité <span class="text-red-500">*</span>
                            </label>
                            <select id="max_guests" name="max_guests" required
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#ff385c] focus:border-[#ff385c]">
                                @for ($i = 1; $i <= 20; $i++)
                                    <option value="{{ $i }}" {{ old('max_guests', 2) == $i ? 'selected' : '' }}>{{ $i }} pers.</option>
                                @endfor
                            </select>
                            @error('max_guests')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Étage --}}
                        <div>
                            <label for="floor" class="block text-sm font-medium text-gray-700 mb-2">
                                Étage
                            </label>
                            <select id="floor" name="floor"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#ff385c] focus:border-[#ff385c]">
                                <option value="">Non applicable</option>
                                <option value="-1" {{ old('floor') == '-1' ? 'selected' : '' }}>Sous-sol</option>
                                <option value="0" {{ old('floor') == '0' ? 'selected' : '' }}>Rez-de-chaussée</option>
                                @for ($i = 1; $i <= 20; $i++)
                                    <option value="{{ $i }}" {{ old('floor') == $i ? 'selected' : '' }}>{{ $i }}{{ $i == 1 ? 'er' : 'ème' }} étage</option>
                                @endfor
                            </select>
                            @error('floor')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Ascenseur --}}
                        <div class="flex items-center">
                            <label class="flex items-center gap-3 p-4 border border-gray-200 rounded-lg cursor-pointer hover:border-[#ffb3c1] w-full">
                                <input type="checkbox" name="has_elevator" value="1"
                                    class="w-5 h-5 text-[#ff385c] rounded" {{ old('has_elevator') ? 'checked' : '' }}>
                                <div>
                                    <span class="font-medium text-gray-700">Ascenseur</span>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>

                {{-- SECTION 3: TARIFICATION --}}
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <div class="flex items-center mb-6">
                        <span class="flex items-center justify-center w-8 h-8 bg-[#ffd1da] text-[#ff385c] rounded-full text-sm font-bold mr-3">3</span>
                        <h2 class="text-xl font-semibold text-gray-900">Tarification</h2>
                    </div>

                    {{-- Info dynamique sur le type de facturation --}}
                    <div class="mb-6 p-3 rounded-lg text-sm flex items-center gap-2"
                        :class="{
                            'bg-blue-50 text-blue-700': typeLocation === 'apartment',
                            'bg-[#fff0f3] text-[#b5083a]': typeLocation === 'residence_meublee',
                            'bg-purple-50 text-purple-700': typeLocation === 'hotel'
                        }">
                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" />
                        </svg>
                        <span>Toutes les locations sont facturées <strong>à la journée</strong>. Le prix par jour est requis.</span>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        {{-- Prix principal (dynamique) --}}
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Prix par jour (FCFA) <span class="text-red-500">*</span>
                            </label>
                            <div>
                                <input type="number" name="price_per_day" value="{{ old('price_per_day') }}"
                                    placeholder="15000" min="1000" required
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#ff385c] focus:border-[#ff385c]">
                                @error('price_per_day')
                                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>

                    {{-- Caution --}}
                    <div class="mt-6 pt-6 border-t border-gray-200">
                        <h3 class="text-sm font-semibold text-gray-700 mb-4">Caution</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="flex items-center">
                                <label class="flex items-center gap-3 p-4 border border-gray-200 rounded-lg cursor-pointer hover:border-[#ffb3c1] w-full">
                                    <input type="checkbox" name="deposit_negotiable" value="1"
                                        class="w-5 h-5 text-[#ff385c] rounded" {{ old('deposit_negotiable') ? 'checked' : '' }}>
                                    <div>
                                        <span class="font-medium text-gray-700">Caution négociable</span>
                                        <p class="text-sm text-gray-500">Le montant peut être discuté</p>
                                    </div>
                                </label>
                            </div>
                            <div>
                                <label for="deposit_terms" class="block text-sm font-medium text-gray-700 mb-2">
                                    Conditions de caution
                                </label>
                                <input type="text" id="deposit_terms" name="deposit_terms" value="{{ old('deposit_terms') }}"
                                    placeholder="Ex: 2 mois de loyer, remboursable à la fin du bail"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#ff385c] focus:border-[#ff385c]">
                                @error('deposit_terms')
                                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                {{-- SECTION 4: LOCALISATION --}}
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <div class="flex items-center mb-6">
                        <span class="flex items-center justify-center w-8 h-8 bg-[#ffd1da] text-[#ff385c] rounded-full text-sm font-bold mr-3">4</span>
                        <h2 class="text-xl font-semibold text-gray-900">Localisation</h2>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6" x-data="{
                        selectedCountry: '{{ old('country_code', 'CI') }}',
                        selectedCity: '{{ old('city', '') }}',
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
                        {{-- Pays --}}
                        <div>
                            <label for="country_code" class="block text-sm font-medium text-gray-700 mb-2">
                                Pays <span class="text-red-500">*</span>
                            </label>
                            <select id="country_code" name="country_code" required x-model="selectedCountry"
                                @change="selectedCity = ''"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#ff385c] focus:border-[#ff385c]">
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
                            <label for="city" class="block text-sm font-medium text-gray-700 mb-2">
                                Ville <span class="text-red-500">*</span>
                            </label>
                            <select id="city" name="city" required x-model="selectedCity"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#ff385c] focus:border-[#ff385c]">
                                <option value="">Sélectionnez une ville</option>
                                <template x-for="city in filteredCities" :key="city.name">
                                    <option :value="city.name" x-text="city.name"></option>
                                </template>
                            </select>
                            @error('city')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Commune --}}
                        <div>
                            <label for="commune" class="block text-sm font-medium text-gray-700 mb-2">
                                Commune <span class="text-red-500">*</span>
                            </label>
                            <template x-if="filteredCommunes.length > 0">
                                <select id="commune" name="commune" required
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#ff385c] focus:border-[#ff385c]">
                                    <option value="">Sélectionnez une commune</option>
                                    <template x-for="commune in filteredCommunes" :key="commune">
                                        <option :value="commune" x-text="commune"
                                            :selected="commune === '{{ old('commune') }}'"></option>
                                    </template>
                                </select>
                            </template>
                            <template x-if="filteredCommunes.length === 0">
                                <input type="text" id="commune" name="commune" value="{{ old('commune') }}"
                                    placeholder="Nom de la commune..." required
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#ff385c] focus:border-[#ff385c]">
                            </template>
                            @error('commune')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Quartier --}}
                        <div>
                            <label for="quartier" class="block text-sm font-medium text-gray-700 mb-2">
                                Quartier <span class="text-red-500">*</span>
                            </label>
                            <input type="text" id="quartier" name="quartier" value="{{ old('quartier') }}"
                                placeholder="Ex: Riviera 3, Angré..." required
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#ff385c] focus:border-[#ff385c]">
                            @error('quartier')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Adresse complète avec Google Places Autocomplete --}}
                        <div class="md:col-span-2" x-data="addressAutocomplete()">
                            <label for="address" class="block text-sm font-medium text-gray-700 mb-2">
                                Adresse complète <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <input type="text" id="address" name="address" value="{{ old('address') }}"
                                    x-ref="addressInput"
                                    placeholder="Commencez à taper une adresse..."
                                    autocomplete="off"
                                    required
                                    class="w-full px-4 py-3 pl-10 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#ff385c] focus:border-[#ff385c]">
                                <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    </svg>
                                </div>
                            </div>
                            <p class="text-xs text-gray-500 mt-1">
                                <svg class="inline w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/></svg>
                                Saisissez l'adresse et sélectionnez une suggestion pour positionner automatiquement la résidence sur la carte
                            </p>
                            @error('address')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror

                            {{-- Carte interactive pour positionnement --}}
                            <div class="mt-4">
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Position sur la carte <span class="text-gray-500 font-normal">(Cliquez ou glissez le marqueur pour ajuster)</span>
                                </label>
                                <div x-ref="createMap"
                                    class="h-80 bg-gray-200 rounded-lg border-2 border-dashed border-gray-300"></div>
                                <div class="mt-2 flex items-center text-sm text-gray-600">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                    <span x-text="mapLatitude && mapLongitude ? `Lat: ${mapLatitude.toFixed(6)}, Lng: ${mapLongitude.toFixed(6)}` : 'Sélectionnez une adresse ou cliquez sur la carte'"></span>
                                    <span id="address-validation-badge" class="ml-2" style="display: none;"></span>
                                </div>
                            </div>

                            <input type="hidden" name="latitude" id="latitude" value="{{ old('latitude', '5.3600') }}">
                            <input type="hidden" name="longitude" id="longitude" value="{{ old('longitude', '-4.0083') }}">

                            @error('latitude')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                            @error('longitude')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                {{-- SECTION 5: DISPONIBILITÉ --}}
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <div class="flex items-center mb-6">
                        <span class="flex items-center justify-center w-8 h-8 bg-[#ffd1da] text-[#ff385c] rounded-full text-sm font-bold mr-3">5</span>
                        <h2 class="text-xl font-semibold text-gray-900">Disponibilité</h2>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        {{-- Disponibilité immédiate --}}
                        <div class="flex items-center">
                            <label class="flex items-center gap-3 p-4 border border-gray-200 rounded-lg cursor-pointer hover:border-[#ffb3c1] w-full">
                                <input type="checkbox" name="is_available" value="1"
                                    class="w-5 h-5 text-[#ff385c] rounded" {{ old('is_available', true) ? 'checked' : '' }}>
                                <div>
                                    <span class="font-medium text-gray-700">Disponible immédiatement</span>
                                    <p class="text-sm text-gray-500">La résidence est prête à accueillir des locataires</p>
                                </div>
                            </label>
                        </div>

                        {{-- Date de disponibilité --}}
                        <div>
                            <label for="available_from" class="block text-sm font-medium text-gray-700 mb-2">
                                Disponible à partir du
                            </label>
                            <input type="date" id="available_from" name="available_from" value="{{ old('available_from') }}"
                                min="{{ date('Y-m-d') }}"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#ff385c] focus:border-[#ff385c]">
                            @error('available_from')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Réservation instantanée --}}
                        <div class="flex items-center md:col-span-2">
                            <label class="flex items-center gap-3 p-4 border border-gray-200 rounded-lg cursor-pointer hover:border-[#ffb3c1] w-full">
                                <input type="checkbox" name="instant_book" value="1"
                                    class="w-5 h-5 text-[#ff385c] rounded" {{ old('instant_book') ? 'checked' : '' }}>
                                <div>
                                    <span class="font-medium text-gray-700">Réservation instantanée</span>
                                    <p class="text-sm text-gray-500">Les locataires peuvent réserver sans validation</p>
                                </div>
                            </label>
                        </div>
                    </div>

                    {{-- Durée de séjour --}}
                    <div class="mt-6 pt-6 border-t border-gray-200">
                        <h3 class="text-sm font-semibold text-gray-700 mb-4">Durée de séjour</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="min_nights" class="block text-sm font-medium text-gray-700 mb-2">
                                    Séjour minimum (nuits)
                                </label>
                                <input type="number" id="min_nights" name="min_nights" value="{{ old('min_nights', 1) }}"
                                    placeholder="1" min="1" max="365"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#ff385c] focus:border-[#ff385c]">
                                @error('min_nights')
                                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label for="max_nights" class="block text-sm font-medium text-gray-700 mb-2">
                                    Séjour maximum (nuits)
                                </label>
                                <input type="number" id="max_nights" name="max_nights" value="{{ old('max_nights') }}"
                                    placeholder="Illimité" min="1" max="365"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#ff385c] focus:border-[#ff385c]">
                                @error('max_nights')
                                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>

                    {{-- Horaires --}}
                    <div class="mt-6 pt-6 border-t border-gray-200">
                        <h3 class="text-sm font-semibold text-gray-700 mb-4">Horaires d'arrivée / départ</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="check_in_time" class="block text-sm font-medium text-gray-700 mb-2">
                                    Heure d'arrivée (check-in)
                                </label>
                                <input type="time" id="check_in_time" name="check_in_time" value="{{ old('check_in_time', '14:00') }}"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#ff385c] focus:border-[#ff385c]">
                                @error('check_in_time')
                                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label for="check_out_time" class="block text-sm font-medium text-gray-700 mb-2">
                                    Heure de départ (check-out)
                                </label>
                                <input type="time" id="check_out_time" name="check_out_time" value="{{ old('check_out_time', '11:00') }}"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#ff385c] focus:border-[#ff385c]">
                                @error('check_out_time')
                                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                {{-- SECTION 6: RÈGLES DE LA MAISON --}}
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <div class="flex items-center mb-6">
                        <span class="flex items-center justify-center w-8 h-8 bg-[#ffd1da] text-[#ff385c] rounded-full text-sm font-bold mr-3">6</span>
                        <h2 class="text-xl font-semibold text-gray-900">Règles de la maison</h2>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                        {{-- Animaux --}}
                        <label class="flex items-center p-4 border border-gray-200 rounded-lg cursor-pointer hover:border-[#ffb3c1] has-checked:border-[#ff385c] has-checked:bg-[#fff0f3]">
                            <input type="checkbox" name="pets_allowed" value="1"
                                class="w-5 h-5 text-[#ff385c] rounded" {{ old('pets_allowed') ? 'checked' : '' }}>
                            <div class="ml-3">
                                <span class="font-medium text-gray-700">Animaux acceptés</span>
                                <p class="text-sm text-gray-500">Chiens, chats...</p>
                            </div>
                        </label>

                        {{-- Fumeurs --}}
                        <label class="flex items-center p-4 border border-gray-200 rounded-lg cursor-pointer hover:border-[#ffb3c1] has-checked:border-[#ff385c] has-checked:bg-[#fff0f3]">
                            <input type="checkbox" name="smoking_allowed" value="1"
                                class="w-5 h-5 text-[#ff385c] rounded" {{ old('smoking_allowed') ? 'checked' : '' }}>
                            <div class="ml-3">
                                <span class="font-medium text-gray-700">Fumeurs acceptés</span>
                                <p class="text-sm text-gray-500">Fumer autorisé</p>
                            </div>
                        </label>

                        {{-- Fêtes --}}
                        <label class="flex items-center p-4 border border-gray-200 rounded-lg cursor-pointer hover:border-[#ffb3c1] has-checked:border-[#ff385c] has-checked:bg-[#fff0f3]">
                            <input type="checkbox" name="parties_allowed" value="1"
                                class="w-5 h-5 text-[#ff385c] rounded" {{ old('parties_allowed') ? 'checked' : '' }}>
                            <div class="ml-3">
                                <span class="font-medium text-gray-700">Fêtes autorisées</span>
                                <p class="text-sm text-gray-500">Événements, soirées</p>
                            </div>
                        </label>
                    </div>

                    {{-- Règles personnalisées --}}
                    <div>
                        <label for="house_rules" class="block text-sm font-medium text-gray-700 mb-2">
                            Règles supplémentaires
                        </label>
                        <textarea id="house_rules" name="house_rules" rows="3" x-model="houseRules"
                            placeholder="Ex: Pas de bruit après 22h, respecter le voisinage, enlever les chaussures..."
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#ff385c] focus:border-[#ff385c] resize-none">{{ old('house_rules') }}</textarea>
                        <div class="flex justify-end mt-1">
                            <span class="text-sm text-gray-400"><span x-text="houseRules.length"></span>/2000</span>
                        </div>
                        @error('house_rules')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Profils de locataires cibles --}}
                    <div class="mt-6 pt-6 border-t border-gray-200">
                        <h3 class="text-sm font-semibold text-gray-700 mb-4">Locataires idéaux</h3>
                        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-3">
                            <label class="flex items-center p-3 border border-gray-200 rounded-lg cursor-pointer hover:border-[#ffb3c1] has-checked:border-[#ff385c] has-checked:bg-[#fff0f3]">
                                <input type="checkbox" name="target_tenants[]" value="students"
                                    class="w-4 h-4 text-[#ff385c] rounded" {{ in_array('students', old('target_tenants', [])) ? 'checked' : '' }}>
                                <span class="ml-2 text-sm text-gray-700">Étudiants</span>
                            </label>
                            <label class="flex items-center p-3 border border-gray-200 rounded-lg cursor-pointer hover:border-[#ffb3c1] has-checked:border-[#ff385c] has-checked:bg-[#fff0f3]">
                                <input type="checkbox" name="target_tenants[]" value="families"
                                    class="w-4 h-4 text-[#ff385c] rounded" {{ in_array('families', old('target_tenants', [])) ? 'checked' : '' }}>
                                <span class="ml-2 text-sm text-gray-700">Familles</span>
                            </label>
                            <label class="flex items-center p-3 border border-gray-200 rounded-lg cursor-pointer hover:border-[#ffb3c1] has-checked:border-[#ff385c] has-checked:bg-[#fff0f3]">
                                <input type="checkbox" name="target_tenants[]" value="professionals"
                                    class="w-4 h-4 text-[#ff385c] rounded" {{ in_array('professionals', old('target_tenants', [])) ? 'checked' : '' }}>
                                <span class="ml-2 text-sm text-gray-700">Professionnels</span>
                            </label>
                            <label class="flex items-center p-3 border border-gray-200 rounded-lg cursor-pointer hover:border-[#ffb3c1] has-checked:border-[#ff385c] has-checked:bg-[#fff0f3]">
                                <input type="checkbox" name="target_tenants[]" value="couples"
                                    class="w-4 h-4 text-[#ff385c] rounded" {{ in_array('couples', old('target_tenants', [])) ? 'checked' : '' }}>
                                <span class="ml-2 text-sm text-gray-700">Couples</span>
                            </label>
                            <label class="flex items-center p-3 border border-gray-200 rounded-lg cursor-pointer hover:border-[#ffb3c1] has-checked:border-[#ff385c] has-checked:bg-[#fff0f3]">
                                <input type="checkbox" name="target_tenants[]" value="tourists"
                                    class="w-4 h-4 text-[#ff385c] rounded" {{ in_array('tourists', old('target_tenants', [])) ? 'checked' : '' }}>
                                <span class="ml-2 text-sm text-gray-700">Touristes</span>
                            </label>
                        </div>
                    </div>
                </div>

                {{-- SECTION 7: ACCESSIBILITÉ --}}
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <div class="flex items-center mb-6">
                        <span class="flex items-center justify-center w-8 h-8 bg-[#ffd1da] text-[#ff385c] rounded-full text-sm font-bold mr-3">7</span>
                        <h2 class="text-xl font-semibold text-gray-900">Accessibilité</h2>
                    </div>

                    <div class="space-y-4">
                        <label class="flex items-center gap-3 p-4 border border-gray-200 rounded-lg cursor-pointer hover:border-[#ffb3c1]">
                            <input type="checkbox" name="is_accessible" value="1"
                                class="w-5 h-5 text-[#ff385c] rounded" {{ old('is_accessible') ? 'checked' : '' }}>
                            <div>
                                <span class="font-medium text-gray-700">Accessible aux personnes à mobilité réduite (PMR)</span>
                                <p class="text-sm text-gray-500">Accès fauteuil roulant, plain-pied ou ascenseur</p>
                            </div>
                        </label>

                        <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                            <label class="flex items-center p-3 border border-gray-200 rounded-lg cursor-pointer hover:border-[#ffb3c1] has-checked:border-[#ff385c] has-checked:bg-[#fff0f3]">
                                <input type="checkbox" name="accessibility_features[]" value="wheelchair_ramp"
                                    class="w-4 h-4 text-[#ff385c] rounded" {{ in_array('wheelchair_ramp', old('accessibility_features', [])) ? 'checked' : '' }}>
                                <span class="ml-2 text-sm text-gray-700">Rampe d'accès</span>
                            </label>
                            <label class="flex items-center p-3 border border-gray-200 rounded-lg cursor-pointer hover:border-[#ffb3c1] has-checked:border-[#ff385c] has-checked:bg-[#fff0f3]">
                                <input type="checkbox" name="accessibility_features[]" value="wide_doors"
                                    class="w-4 h-4 text-[#ff385c] rounded" {{ in_array('wide_doors', old('accessibility_features', [])) ? 'checked' : '' }}>
                                <span class="ml-2 text-sm text-gray-700">Portes larges</span>
                            </label>
                            <label class="flex items-center p-3 border border-gray-200 rounded-lg cursor-pointer hover:border-[#ffb3c1] has-checked:border-[#ff385c] has-checked:bg-[#fff0f3]">
                                <input type="checkbox" name="accessibility_features[]" value="accessible_bathroom"
                                    class="w-4 h-4 text-[#ff385c] rounded" {{ in_array('accessible_bathroom', old('accessibility_features', [])) ? 'checked' : '' }}>
                                <span class="ml-2 text-sm text-gray-700">Salle de bain adaptée</span>
                            </label>
                            <label class="flex items-center p-3 border border-gray-200 rounded-lg cursor-pointer hover:border-[#ffb3c1] has-checked:border-[#ff385c] has-checked:bg-[#fff0f3]">
                                <input type="checkbox" name="accessibility_features[]" value="grab_bars"
                                    class="w-4 h-4 text-[#ff385c] rounded" {{ in_array('grab_bars', old('accessibility_features', [])) ? 'checked' : '' }}>
                                <span class="ml-2 text-sm text-gray-700">Barres d'appui</span>
                            </label>
                            <label class="flex items-center p-3 border border-gray-200 rounded-lg cursor-pointer hover:border-[#ffb3c1] has-checked:border-[#ff385c] has-checked:bg-[#fff0f3]">
                                <input type="checkbox" name="accessibility_features[]" value="step_free"
                                    class="w-4 h-4 text-[#ff385c] rounded" {{ in_array('step_free', old('accessibility_features', [])) ? 'checked' : '' }}>
                                <span class="ml-2 text-sm text-gray-700">Sans marches</span>
                            </label>
                            <label class="flex items-center p-3 border border-gray-200 rounded-lg cursor-pointer hover:border-[#ffb3c1] has-checked:border-[#ff385c] has-checked:bg-[#fff0f3]">
                                <input type="checkbox" name="accessibility_features[]" value="accessible_parking"
                                    class="w-4 h-4 text-[#ff385c] rounded" {{ in_array('accessible_parking', old('accessibility_features', [])) ? 'checked' : '' }}>
                                <span class="ml-2 text-sm text-gray-700">Parking PMR</span>
                            </label>
                        </div>
                    </div>
                </div>

                {{-- SECTION 8: ÉQUIPEMENTS --}}
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <div class="flex items-center mb-6">
                        <span class="flex items-center justify-center w-8 h-8 bg-[#ffd1da] text-[#ff385c] rounded-full text-sm font-bold mr-3">8</span>
                        <h2 class="text-xl font-semibold text-gray-900">Équipements</h2>
                    </div>

                    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                        @foreach($amenities ?? [] as $amenity)
                            <label class="flex items-center p-4 border border-gray-200 rounded-lg cursor-pointer hover:border-[#ffb3c1] hover:bg-[#fff0f3] has-checked:border-[#ff385c] has-checked:bg-[#fff0f3]">
                                <input type="checkbox" name="amenities[]" value="{{ $amenity->id }}"
                                    class="w-5 h-5 text-[#ff385c] rounded"
                                    {{ in_array($amenity->id, old('amenities', [])) ? 'checked' : '' }}>
                                <span class="ml-3 text-sm font-medium text-gray-700">{{ $amenity->name }}</span>
                            </label>
                        @endforeach
                    </div>
                    @error('amenities')
                        <p class="text-red-500 text-sm mt-2">{{ $message }}</p>
                    @enderror
                </div>

                {{-- SECTION 9: PHOTOS --}}
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <div class="flex items-center mb-6">
                        <span class="flex items-center justify-center w-8 h-8 bg-[#ffd1da] text-[#ff385c] rounded-full text-sm font-bold mr-3">9</span>
                        <h2 class="text-xl font-semibold text-gray-900">Photos</h2>
                    </div>

                    <div x-data="{
                        previews: [],
                        files: [],
                        isDragging: false,
                        handlePhotos(fileList) {
                            const maxFiles = 10;
                            const maxSize = 5 * 1024 * 1024;

                            for (let i = 0; i < fileList.length && this.files.length < maxFiles; i++) {
                                const file = fileList[i];
                                if (!file.type.startsWith('image/')) continue;
                                if (file.size > maxSize) {
                                    alert('L\'image ' + file.name + ' dépasse 5 Mo');
                                    continue;
                                }

                                this.files.push(file);
                                const reader = new FileReader();
                                reader.onload = (e) => {
                                    this.previews.push(e.target.result);
                                };
                                reader.readAsDataURL(file);
                            }

                            this.updateFileInput();
                        },
                        removePhoto(index) {
                            this.previews.splice(index, 1);
                            this.files.splice(index, 1);
                            this.updateFileInput();
                        },
                        updateFileInput() {
                            const dataTransfer = new DataTransfer();
                            this.files.forEach(file => dataTransfer.items.add(file));
                            this.$refs.photos.files = dataTransfer.files;
                        }
                    }" class="space-y-4">
                        <div class="border-2 border-dashed rounded-xl p-8 text-center cursor-pointer transition"
                            :class="isDragging ? 'border-[#ff385c] bg-[#fff0f3]' : 'border-gray-300 hover:border-[#ff4d6d]'"
                            @dragover.prevent="isDragging = true" @dragleave.prevent="isDragging = false"
                            @drop.prevent="isDragging = false; handlePhotos($event.dataTransfer.files)"
                            @click="$refs.photos.click()">
                            <input type="file" name="photos[]" multiple accept="image/*" class="hidden"
                                x-ref="photos" @change="handlePhotos($event.target.files)">
                            <div class="flex flex-col items-center">
                                <div class="w-16 h-16 bg-[#ffd1da] rounded-full flex items-center justify-center mb-4">
                                    <svg class="w-8 h-8 text-[#ff385c]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                </div>
                                <p class="text-gray-700 font-medium mb-1">Glissez vos photos ici</p>
                                <p class="text-gray-500 text-sm">ou cliquez pour sélectionner</p>
                                <p class="text-xs text-gray-400 mt-2">PNG, JPG jusqu'à 5 Mo • Max 10 photos</p>
                            </div>
                        </div>

                        <div x-show="previews.length > 0" class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <template x-for="(preview, index) in previews" :key="index">
                                <div class="relative aspect-square rounded-lg overflow-hidden border">
                                    <img loading="lazy" :src="preview" alt="Image" class="w-full h-full object-cover">
                                    <button type="button" @click="removePhoto(index)"
                                        class="absolute top-2 right-2 p-1 bg-red-500 text-white rounded-full hover:bg-red-600">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                    <span x-show="index === 0"
                                        class="absolute bottom-2 left-2 px-2 py-1 bg-[#ff385c] text-white text-xs rounded">Principale</span>
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

                {{-- SECTION 10: VISITE VIRTUELLE (Optionnel) --}}
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <div class="flex items-center mb-6">
                        <span class="flex items-center justify-center w-8 h-8 bg-[#ffd1da] text-[#ff385c] rounded-full text-sm font-bold mr-3">10</span>
                        <h2 class="text-xl font-semibold text-gray-900">Visite virtuelle</h2>
                        <span class="ml-2 text-sm text-gray-400">(Optionnel)</span>
                    </div>

                    <div>
                        <label for="virtual_tour_url" class="block text-sm font-medium text-gray-700 mb-2">
                            Lien de visite virtuelle
                        </label>
                        <input type="url" id="virtual_tour_url" name="virtual_tour_url" value="{{ old('virtual_tour_url') }}"
                            placeholder="https://my.matterport.com/... ou lien YouTube 360°"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#ff385c] focus:border-[#ff385c]">
                        <p class="text-sm text-gray-500 mt-1">Matterport, YouTube 360°, ou tout autre service de visite virtuelle</p>
                        @error('virtual_tour_url')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- BOUTONS --}}
                <div class="flex flex-col sm:flex-row gap-4 justify-end">
                    <a href="{{ route('owner.residences.index') }}"
                        class="px-6 py-3 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 text-center font-medium">
                        Annuler
                    </a>
                    <button type="submit"
                        class="px-8 py-3 bg-[#ff385c] text-white rounded-lg hover:bg-[#e00b41] font-medium flex items-center justify-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                        </svg>
                        Publier la résidence
                    </button>
                </div>

                {{-- INFO --}}
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <div class="flex items-start gap-3">
                        <svg class="w-5 h-5 text-blue-600 shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                                clip-rule="evenodd" />
                        </svg>
                        <div class="text-sm text-blue-800">
                            <p class="font-medium">Information</p>
                            <p class="mt-1">Votre résidence sera validée par notre équipe avant publication. Vous recevrez une notification dès qu'elle sera approuvée.</p>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('owner-scripts')
    <script>
        // Callback déclenché par le chargement asynchrone de l'API Google Maps
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
