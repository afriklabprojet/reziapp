@extends('layouts.owner')

@section('title', 'Créer une annonce - REZI')

@section('owner-content')
    <div x-data="residenceWizard(@js([
    'amenities' => $amenities,
    'storeUrl' => route('owner.residences.store'),
    'indexUrl' => route('owner.residences.index'),
    'csrfToken' => csrf_token(),
    'aiUrls' => [
        'generateDescription' => route('owner.ai.generate-description'),
        'generateTitle' => route('owner.ai.generate-title'),
        'improveDescription' => route('owner.ai.improve-description'),
    ],
]))" x-init="init()" class="min-h-screen bg-gray-50">
        <!-- Header avec progression -->
        <div class="bg-white border-b border-gray-200 sticky top-0 z-40">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
                <div class="flex items-center justify-between mb-4">
                    <a href="{{ route('owner.residences.index') }}"
                        class="text-gray-500 hover:text-gray-700 flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                        Quitter
                    </a>
                    <button @click="saveDraft()" class="text-[#F16A00] hover:text-[#CC5A00] font-medium">
                        Sauvegarder le brouillon
                    </button>
                </div>

                <!-- Barre de progression -->
                <div class="flex items-center justify-between">
                    <template x-for="(step, index) in steps" :key="index">
                        <div class="flex items-center" :class="index < steps.length - 1 ? 'flex-1' : ''">
                            <div @click="goToStep(index)"
                                :class="{
                                    'bg-[#F16A00] text-white': currentStep >= index,
                                    'bg-gray-200 text-gray-500': currentStep < index,
                                    'cursor-pointer hover:bg-[#CC5A00]': currentStep > index
                                }"
                                class="w-10 h-10 rounded-full flex items-center justify-center font-semibold transition">
                                <span x-show="currentStep <= index" x-text="index + 1"></span>
                                <svg x-show="currentStep > index" class="w-5 h-5" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M5 13l4 4L19 7" />
                                </svg>
                            </div>
                            <div x-show="index < steps.length - 1"
                                :class="currentStep > index ? 'bg-[#F16A00]' : 'bg-gray-200'"
                                class="flex-1 h-1 mx-2 rounded transition"></div>
                        </div>
                    </template>
                </div>
                <div class="flex justify-between mt-2 text-xs text-gray-500">
                    <template x-for="(step, index) in steps" :key="index">
                        <span :class="currentStep === index ? 'text-[#F16A00] font-medium' : ''"
                            x-text="step.title"></span>
                    </template>
                </div>
            </div>
        </div>

        <!-- Contenu du formulaire -->
        <form @submit.prevent="submitForm()" class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            @csrf

            <!-- Étape 1: Type de bien -->
            <div x-show="currentStep === 0" x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-x-4">
                <div class="text-center mb-8">
                    <h1 class="text-3xl font-bold text-gray-900">Quel type de bien proposez-vous ?</h1>
                    <p class="mt-2 text-gray-600">Sélectionnez le type qui correspond le mieux à votre logement</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 max-w-3xl mx-auto">
                    <template x-for="type in propertyTypes" :key="type.value">
                        <label
                            :class="formData.type === type.value ? 'border-[#F16A00] bg-[#FFF4EB] ring-2 ring-[#F16A00]' :
                                'border-gray-200 hover:border-gray-300'"
                            class="relative flex flex-col items-center p-6 border-2 rounded-2xl cursor-pointer transition">
                            <input type="radio" name="type" :value="type.value" x-model="formData.type"
                                class="sr-only">
                            <span class="text-4xl mb-3" x-text="type.icon"></span>
                            <span class="font-semibold text-gray-900" x-text="type.label"></span>
                            <span class="text-sm text-gray-500 text-center mt-1" x-text="type.description"></span>
                        </label>
                    </template>
                </div>
            </div>

            <!-- Étape 2: Informations de base -->
            <div x-show="currentStep === 1" x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-x-4">
                <div class="text-center mb-8">
                    <div class="flex items-center justify-center gap-3">
                        <h1 class="text-3xl font-bold text-gray-900">Décrivez votre bien</h1>
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-violet-100 text-violet-700 rounded-full text-[10px] font-bold uppercase tracking-wide">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                            IA
                        </span>
                    </div>
                    <p class="mt-2 text-gray-600">Ces informations aideront les voyageurs à trouver votre annonce</p>
                </div>

                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 space-y-6 max-w-2xl mx-auto">
                    {{-- Erreur IA --}}
                    <div x-show="aiError" x-cloak x-transition class="bg-red-50 border border-red-200 text-red-600 text-xs rounded-lg px-3 py-2">
                        <span x-text="aiError"></span>
                        <button type="button" @click="aiError=''" class="ml-2 text-red-400 hover:text-red-600">&times;</button>
                    </div>

                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <label class="block text-sm font-medium text-gray-700">Titre de l'annonce *</label>
                            <button type="button" @click="aiGenerateTitle()" :disabled="aiTitleLoading"
                                class="inline-flex items-center gap-1 px-2.5 py-1 bg-violet-50 text-violet-700 rounded-lg text-xs font-medium hover:bg-violet-100 transition disabled:opacity-50">
                                <svg x-show="!aiTitleLoading" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                <svg x-show="aiTitleLoading" class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                                <span x-text="aiTitleLoading ? 'Génération...' : 'Titre par IA'"></span>
                            </button>
                        </div>
                        <input type="text" x-model="formData.name" maxlength="100"
                            placeholder="Ex: Superbe appartement avec vue sur la lagune"
                            class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-[#F16A00] focus:border-[#F16A00]">
                        <p class="mt-1 text-xs text-gray-500"><span x-text="formData.name.length"></span>/100 caractères</p>
                    </div>

                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <label class="block text-sm font-medium text-gray-700">Description *</label>
                            <div class="flex items-center gap-2">
                                <button type="button" @click="aiGenerateDescription()" :disabled="aiLoading"
                                    class="inline-flex items-center gap-1 px-2.5 py-1 bg-violet-50 text-violet-700 rounded-lg text-xs font-medium hover:bg-violet-100 transition disabled:opacity-50">
                                    <svg x-show="!aiLoading" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                    <svg x-show="aiLoading" class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                                    <span x-text="aiLoading ? 'Génération...' : 'Générer par IA'"></span>
                                </button>
                                <button type="button" @click="aiImproveDescription()" :disabled="aiImproveLoading || formData.description.length < 10"
                                    class="inline-flex items-center gap-1 px-2.5 py-1 bg-amber-50 text-amber-700 rounded-lg text-xs font-medium hover:bg-amber-100 transition disabled:opacity-50">
                                    <svg x-show="!aiImproveLoading" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/></svg>
                                    <svg x-show="aiImproveLoading" class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                                    <span x-text="aiImproveLoading ? 'Amélioration...' : 'Améliorer'"></span>
                                </button>
                            </div>
                        </div>
                        <textarea x-model="formData.description" rows="5" maxlength="2000"
                            placeholder="Décrivez votre logement, ses atouts, l'ambiance du quartier..."
                            class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-[#F16A00] focus:border-[#F16A00]"></textarea>
                        <p class="mt-1 text-xs text-gray-500"><span x-text="formData.description.length"></span>/2000
                            caractères</p>
                    </div>

                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Chambres *</label>
                            <div class="flex items-center gap-3">
                                <button type="button" @click="formData.bedrooms = Math.max(0, formData.bedrooms - 1)"
                                    class="w-10 h-10 bg-gray-100 rounded-lg hover:bg-gray-200">−</button>
                                <span class="text-xl font-semibold w-8 text-center" x-text="formData.bedrooms"></span>
                                <button type="button" @click="formData.bedrooms++"
                                    class="w-10 h-10 bg-gray-100 rounded-lg hover:bg-gray-200">+</button>
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Salles de bain *</label>
                            <div class="flex items-center gap-3">
                                <button type="button" @click="formData.bathrooms = Math.max(0, formData.bathrooms - 1)"
                                    class="w-10 h-10 bg-gray-100 rounded-lg hover:bg-gray-200">−</button>
                                <span class="text-xl font-semibold w-8 text-center" x-text="formData.bathrooms"></span>
                                <button type="button" @click="formData.bathrooms++"
                                    class="w-10 h-10 bg-gray-100 rounded-lg hover:bg-gray-200">+</button>
                            </div>
                        </div>
                        <div class="col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Surface (m²)</label>
                            <input type="number" x-model="formData.surface_area" min="10" max="1000"
                                placeholder="Ex: 75"
                                class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-[#F16A00] focus:border-[#F16A00]">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Étape 3: Localisation -->
            <div x-show="currentStep === 2" x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-x-4">
                <div class="text-center mb-8">
                    <h1 class="text-3xl font-bold text-gray-900">Où se situe votre bien ?</h1>
                    <p class="mt-2 text-gray-600">Une localisation précise aide les voyageurs</p>
                </div>

                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 space-y-6 max-w-2xl mx-auto">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Commune *</label>
                        <select x-model="formData.commune"
                            class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-[#F16A00] focus:border-[#F16A00]">
                            <option value="">Sélectionnez une commune</option>
                            <option value="Cocody">Cocody</option>
                            <option value="Plateau">Plateau</option>
                            <option value="Marcory">Marcory</option>
                            <option value="Koumassi">Koumassi</option>
                            <option value="Treichville">Treichville</option>
                            <option value="Yopougon">Yopougon</option>
                            <option value="Abobo">Abobo</option>
                            <option value="Adjamé">Adjamé</option>
                            <option value="Attécoubé">Attécoubé</option>
                            <option value="Port-Bouët">Port-Bouët</option>
                            <option value="Bingerville">Bingerville</option>
                            <option value="Songon">Songon</option>
                            <option value="Anyama">Anyama</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Quartier *</label>
                        <input type="text" x-model="formData.quartier"
                            placeholder="Ex: Riviera Palmeraie, Zone 4, Angré..."
                            class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-[#F16A00] focus:border-[#F16A00]">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Adresse complète *</label>
                        <input type="text" x-model="formData.address"
                            placeholder="Ex: Rue des Jardins, près du supermarché..."
                            class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-[#F16A00] focus:border-[#F16A00]">
                    </div>

                    <!-- Carte pour sélectionner la position -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Position sur la carte</label>
                        <div class="bg-gray-100 rounded-xl h-64 flex items-center justify-center relative overflow-hidden">
                            <div id="map-wizard" class="absolute inset-0"></div>
                            <div x-show="!formData.latitude" class="text-center z-10 bg-white/90 p-4 rounded-xl">
                                <button type="button" @click="detectLocation()"
                                    class="px-4 py-2 bg-[#F16A00] text-white rounded-xl hover:bg-[#CC5A00] flex items-center gap-2 mx-auto">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                    </svg>
                                    Détecter ma position
                                </button>
                                <p class="text-sm text-gray-500 mt-2">ou cliquez sur la carte</p>
                            </div>
                        </div>
                        <div x-show="formData.latitude" class="mt-2 text-sm text-gray-500">
                            📍 Position : <span x-text="formData.latitude?.toFixed(6)"></span>, <span
                                x-text="formData.longitude?.toFixed(6)"></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Étape 4: Équipements -->
            <div x-show="currentStep === 3" x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-x-4">
                <div class="text-center mb-8">
                    <h1 class="text-3xl font-bold text-gray-900">Quels équipements proposez-vous ?</h1>
                    <p class="mt-2 text-gray-600">Sélectionnez tous les équipements disponibles</p>
                </div>

                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 max-w-3xl mx-auto">
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                        <template x-for="amenity in amenities" :key="amenity.id">
                            <label
                                :class="formData.amenities.includes(amenity.id) ? 'border-[#F16A00] bg-[#FFF4EB]' :
                                    'border-gray-200 hover:border-gray-300'"
                                class="flex items-center gap-3 p-4 border-2 rounded-xl cursor-pointer transition">
                                <input type="checkbox" :value="amenity.id" x-model="formData.amenities"
                                    class="sr-only">
                                <span class="text-2xl" x-text="amenity.icon"></span>
                                <span class="text-sm font-medium text-gray-700" x-text="amenity.name"></span>
                                <svg x-show="formData.amenities.includes(amenity.id)"
                                    class="w-5 h-5 text-[#F16A00] ml-auto" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M5 13l4 4L19 7" />
                                </svg>
                            </label>
                        </template>
                    </div>
                </div>
            </div>

            <!-- Étape 5: Photos -->
            <div x-show="currentStep === 4" x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-x-4">
                <div class="text-center mb-8">
                    <h1 class="text-3xl font-bold text-gray-900">Ajoutez des photos</h1>
                    <p class="mt-2 text-gray-600">Les annonces avec de belles photos reçoivent 3x plus de contacts</p>
                </div>

                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 max-w-3xl mx-auto">
                    <!-- Zone de drop -->
                    <div @dragover.prevent="isDragging = true" @dragleave.prevent="isDragging = false"
                        @drop.prevent="handleDrop($event)"
                        :class="isDragging ? 'border-[#F16A00] bg-[#FFF4EB]' : 'border-gray-300'"
                        class="border-2 border-dashed rounded-2xl p-8 text-center transition">
                        <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <p class="text-gray-600 mb-2">Glissez vos photos ici ou</p>
                        <label
                            class="inline-block px-6 py-3 bg-[#F16A00] text-white rounded-xl cursor-pointer hover:bg-[#CC5A00] transition">
                            <input type="file" @change="handleFiles($event)" multiple accept="image/*"
                                class="hidden">
                            Parcourir
                        </label>
                        <p class="text-sm text-gray-400 mt-4">JPG, PNG • Max 5MB par photo • Min 5 photos recommandées</p>
                    </div>

                    <!-- Prévisualisation des photos -->
                    <div x-show="formData.photos.length > 0" class="mt-6">
                        <div class="flex items-center justify-between mb-3">
                            <h3 class="font-medium text-gray-900"><span x-text="formData.photos.length"></span> photo(s)
                            </h3>
                            <span class="text-sm text-gray-500">Glissez pour réorganiser • Cliquez sur ⭐ pour définir la
                                photo principale</span>
                        </div>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <template x-for="(photo, index) in formData.photos" :key="index">
                                <div class="relative group aspect-square">
                                    <img loading="lazy" :src="photo.preview" alt="Image"
                                        class="w-full h-full object-cover rounded-xl">
                                    <div
                                        class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition rounded-xl flex items-center justify-center gap-2">
                                        <button type="button" @click="setPrimaryPhoto(index)"
                                            :class="photo.isPrimary ? 'bg-yellow-500' : 'bg-white/20 hover:bg-white/40'"
                                            class="p-2 rounded-lg transition">
                                            <span class="text-lg">⭐</span>
                                        </button>
                                        <button type="button" @click="removePhoto(index)"
                                            class="p-2 bg-red-500 hover:bg-red-600 rounded-lg transition">
                                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                    <div x-show="photo.isPrimary"
                                        class="absolute top-2 left-2 px-2 py-1 bg-yellow-500 text-white text-xs rounded-full">
                                        Photo principale
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Étape 6: Tarification -->
            <div x-show="currentStep === 5" x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-x-4">
                <div class="text-center mb-8">
                    <h1 class="text-3xl font-bold text-gray-900">Définissez vos tarifs</h1>
                    <p class="mt-2 text-gray-600">Vous pourrez ajuster les prix plus tard selon les saisons</p>
                </div>

                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 space-y-6 max-w-2xl mx-auto">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Prix par jour *</label>
                        <div class="relative">
                            <input type="number" x-model="formData.price_per_day" min="5000" step="500"
                                placeholder="25000"
                                class="w-full px-4 py-3 pr-16 border border-gray-300 rounded-xl focus:ring-2 focus:ring-[#F16A00] focus:border-[#F16A00] text-2xl font-bold">
                            <span class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-500">FCFA</span>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Prix par semaine <span
                                    class="text-gray-400">(optionnel)</span></label>
                            <div class="relative">
                                <input type="number" x-model="formData.price_per_week" min="0" step="1000"
                                    :placeholder="formData.price_per_day ? (formData.price_per_day * 6) : '150000'"
                                    class="w-full px-4 py-3 pr-16 border border-gray-300 rounded-xl focus:ring-2 focus:ring-[#F16A00] focus:border-[#F16A00]">
                                <span class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-500 text-sm">FCFA</span>
                            </div>
                            <p class="text-xs text-[#F16A00] mt-1"
                                x-show="formData.price_per_week && formData.price_per_day">
                                💡 Économie de <span
                                    x-text="Math.round((1 - formData.price_per_week / (formData.price_per_day * 7)) * 100)"></span>%
                            </p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Prix par jour <span
                                    class="text-gray-400">(optionnel)</span></label>
                            <div class="relative">
                                <input type="number" x-model="formData.price_per_month" min="0" step="5000"
                                    :placeholder="formData.price_per_day ? (formData.price_per_day * 25) : '600000'"
                                    class="w-full px-4 py-3 pr-16 border border-gray-300 rounded-xl focus:ring-2 focus:ring-[#F16A00] focus:border-[#F16A00]">
                                <span class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-500 text-sm">FCFA</span>
                            </div>
                            <p class="text-xs text-[#F16A00] mt-1"
                                x-show="formData.price_per_month && formData.price_per_day">
                                💡 Économie de <span
                                    x-text="Math.round((1 - formData.price_per_month / (formData.price_per_day * 30)) * 100)"></span>%
                            </p>
                        </div>
                    </div>

                    <!-- Estimation de revenus -->
                    <div class="bg-[#FFF4EB] rounded-xl p-4" x-show="formData.price_per_day">

                    <!-- Frais de ménage -->
                    <div class="border-t border-gray-100 pt-4 mt-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Frais de ménage
                            <span class="text-gray-400 font-normal">(optionnel)</span>
                        </label>
                        <div class="relative">
                            <input type="number" x-model="formData.cleaning_fee" min="0" step="500"
                                placeholder="0"
                                class="w-full px-4 py-3 pr-16 border border-gray-300 rounded-xl focus:ring-2 focus:ring-[#F16A00] focus:border-[#F16A00]">
                            <span class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-500 text-sm">FCFA</span>
                        </div>
                        <p class="text-xs text-gray-500 mt-1">Facturés une seule fois par séjour, en sus du montant de location.</p>
                    </div>

                    <!-- Estimation de revenus -->
                    <div class="bg-[#FFF4EB] rounded-xl p-4 mt-4" x-show="formData.price_per_day">
                        <h4 class="font-medium text-[#A34700] mb-2">💰 Estimation de revenus</h4>
                        <div class="grid grid-cols-3 gap-4 text-center">
                            <div>
                                <p class="text-2xl font-bold text-[#F16A00]"
                                    x-text="formatPrice(formData.price_per_day * 15)"></p>
                                <p class="text-xs text-[#CC5A00]">50% occupation</p>
                            </div>
                            <div>
                                <p class="text-2xl font-bold text-[#F16A00]"
                                    x-text="formatPrice(formData.price_per_day * 22)"></p>
                                <p class="text-xs text-[#CC5A00]">75% occupation</p>
                            </div>
                            <div>
                                <p class="text-2xl font-bold text-[#F16A00]"
                                    x-text="formatPrice(formData.price_per_day * 30)"></p>
                                <p class="text-xs text-[#CC5A00]">100% occupation</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Étape 7: Finalisation -->
            <div x-show="currentStep === 6" x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-x-4">
                <div class="text-center mb-8">
                    <h1 class="text-3xl font-bold text-gray-900">Dernière étape !</h1>
                    <p class="mt-2 text-gray-600">Vérifiez les informations et publiez votre annonce</p>
                </div>

                <div class="max-w-2xl mx-auto space-y-6">
                    <!-- Récapitulatif -->
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                        <h3 class="font-semibold text-gray-900 mb-4">Récapitulatif</h3>

                        <div class="space-y-4">
                            <div class="flex justify-between py-2 border-b border-gray-100">
                                <span class="text-gray-500">Type</span>
                                <span class="font-medium" x-text="getTypeLabel(formData.type)"></span>
                            </div>
                            <div class="flex justify-between py-2 border-b border-gray-100">
                                <span class="text-gray-500">Titre</span>
                                <span class="font-medium" x-text="formData.name || '—'"></span>
                            </div>
                            <div class="flex justify-between py-2 border-b border-gray-100">
                                <span class="text-gray-500">Localisation</span>
                                <span class="font-medium" x-text="formData.quartier + ', ' + formData.commune"></span>
                            </div>
                            <div class="flex justify-between py-2 border-b border-gray-100">
                                <span class="text-gray-500">Caractéristiques</span>
                                <span class="font-medium"><span x-text="formData.bedrooms"></span> ch. • <span
                                        x-text="formData.bathrooms"></span> sdb</span>
                            </div>
                            <div class="flex justify-between py-2 border-b border-gray-100">
                                <span class="text-gray-500">Équipements</span>
                                <span class="font-medium"><span x-text="formData.amenities.length"></span>
                                    sélectionnés</span>
                            </div>
                            <div class="flex justify-between py-2 border-b border-gray-100">
                                <span class="text-gray-500">Photos</span>
                                <span class="font-medium"><span x-text="formData.photos.length"></span> photos</span>
                            </div>
                            <div class="flex justify-between py-2">
                                <span class="text-gray-500">Prix par jour</span>
                                <span class="font-bold text-[#F16A00]"
                                    x-text="formatPrice(formData.price_per_day) + ' FCFA'"></span>
                            </div>
                        </div>
                    </div>

                    <!-- Options de publication -->
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                        <h3 class="font-semibold text-gray-900 mb-4">Options de publication</h3>

                        <label
                            class="flex items-start gap-4 p-4 border border-gray-200 rounded-xl cursor-pointer hover:bg-gray-50 has-checked:border-[#F16A00] has-checked:bg-[#FFF4EB] mb-3">
                            <input type="checkbox" x-model="formData.is_available"
                                class="mt-1 w-5 h-5 text-[#F16A00] border-gray-300 rounded focus:ring-[#F16A00]">
                            <div>
                                <span class="font-medium text-gray-900">Disponible immédiatement</span>
                                <p class="text-sm text-gray-500">Votre annonce sera visible dès approbation</p>
                            </div>
                        </label>

                        <label
                            class="flex items-start gap-4 p-4 border border-gray-200 rounded-xl cursor-pointer hover:bg-gray-50 has-checked:border-[#F16A00] has-checked:bg-[#FFF4EB]">
                            <input type="checkbox" x-model="formData.accept_terms"
                                class="mt-1 w-5 h-5 text-[#F16A00] border-gray-300 rounded focus:ring-[#F16A00]">
                            <div>
                                <span class="font-medium text-gray-900">J'accepte les conditions *</span>
                                <p class="text-sm text-gray-500">J'ai lu et j'accepte les <a
                                        href="{{ route('pages.cgu') }}" class="text-[#F16A00] hover:underline"
                                        target="_blank">conditions d'utilisation</a> et la <a
                                        href="{{ route('pages.confidentialite') }}"
                                        class="text-[#F16A00] hover:underline" target="_blank">politique de
                                        confidentialité</a></p>
                            </div>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Navigation -->
            <div class="flex justify-between mt-8 max-w-3xl mx-auto">
                <button type="button" @click="previousStep()" x-show="currentStep > 0"
                    class="px-6 py-3 bg-gray-100 text-gray-700 rounded-xl hover:bg-gray-200 transition flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                    Retour
                </button>
                <div x-show="currentStep === 0"></div>

                <button type="button" @click="nextStep()" x-show="currentStep < steps.length - 1"
                    :disabled="!canProceed()"
                    :class="canProceed() ? 'bg-[#F16A00] hover:bg-[#CC5A00]' : 'bg-gray-300 cursor-not-allowed'"
                    class="px-6 py-3 text-white rounded-xl transition flex items-center gap-2">
                    Continuer
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </button>

                <button type="submit" x-show="currentStep === steps.length - 1"
                    :disabled="!formData.accept_terms || isSubmitting"
                    :class="formData.accept_terms && !isSubmitting ? 'bg-[#F16A00] hover:bg-[#CC5A00]' :
                        'bg-gray-300 cursor-not-allowed'"
                    class="px-8 py-3 text-white rounded-xl transition flex items-center gap-2">
                    <span x-show="!isSubmitting">🎉 Publier mon annonce</span>
                    <span x-show="isSubmitting">Publication en cours...</span>
                </button>
            </div>
        </form>
    </div>
@endsection
@endsection
