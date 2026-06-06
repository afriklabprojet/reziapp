@extends('layouts.client', ['sidebarActive' => 'profile'])

@section('title', 'Mon profil - ReziApp')

@section('client-content')
    {{-- En-tête --}}
    <div class="mb-6">
        <div class="flex items-center gap-3 mb-4">
            <a href="{{ route('profile.public', $user) }}"
                class="p-2 rounded-lg hover:bg-gray-100 text-gray-500 hover:text-gray-700 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Mon profil</h1>
                <p class="text-gray-500 text-sm">Ces informations sont visibles uniquement par vous</p>
            </div>
        </div>
    </div>

    {{-- Aperçu du profil --}}
    <div class="bg-linear-to-r from-blue-500 to-indigo-600 rounded-2xl p-6 mb-6 text-white relative overflow-hidden">
        <div class="absolute top-0 right-0 w-48 h-48 bg-white/10 rounded-full -translate-y-1/2 translate-x-1/2"></div>
        <div class="relative flex items-center gap-4">
            <div class="w-16 h-16 rounded-full overflow-hidden bg-white/20 ring-2 ring-white/30 shrink-0">
                @if ($user->profile_photo || $user->avatar)
                    <img src="{{ $user->getAvatarUrl() }}" alt="{{ $user->name }}" class="w-full h-full object-cover">
                @else
                    <div class="w-full h-full flex items-center justify-center">
                        <span class="text-2xl font-bold">{{ strtoupper(substr($user->name, 0, 1)) }}</span>
                    </div>
                @endif
            </div>
            <div class="flex-1 min-w-0">
                <h2 class="text-xl font-bold truncate">{{ $user->name }}</h2>
                <div class="flex flex-wrap items-center gap-2 mt-1">
                    @if ($profile->location)
                        <span class="text-white/80 text-sm flex items-center gap-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            {{ $profile->location }}
                        </span>
                    @endif
                    <span class="text-white/60 text-sm">•</span>
                    <span class="text-white/80 text-sm">{{ $profile->profile_views }} vues</span>
                </div>
            </div>
            <a href="{{ route('profile.public', $user) }}"
                class="px-4 py-2 bg-white/20 hover:bg-white/30 rounded-lg text-sm font-medium transition">
                Voir le profil
            </a>
        </div>
    </div>

    <form action="{{ route('profile.public.update') }}" method="POST" class="space-y-6">
        @csrf
        @method('PUT')

        {{-- Informations principales --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
                <h2 class="font-semibold text-gray-900 flex items-center gap-2">
                    <svg class="w-5 h-5 text-[#F16A00]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                    À propos de vous
                </h2>
            </div>
            <div class="p-6 space-y-5">
                {{-- Bio --}}
                <div>
                    <label for="bio" class="block text-sm font-medium text-gray-700 mb-2">
                        Présentation
                        <span class="text-gray-400 font-normal">(optionnel)</span>
                    </label>
                    <div x-data="{ chars: {{ strlen($profile->bio ?? '') }} }">
                        <textarea id="bio" name="bio" rows="4" x-on:input="chars = $event.target.value.length" maxlength="1000"
                            class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:ring-2 focus:ring-[#F16A00] focus:border-[#F16A00] transition resize-none"
                            placeholder="Parlez de vous en quelques mots... Qu'est-ce qui vous passionne ? Pourquoi voyagez-vous ?">{{ old('bio', $profile->bio) }}</textarea>
                        <div class="flex justify-between items-center mt-1">
                            <p class="text-xs text-gray-400">Présentez-vous aux autres membres de la communauté</p>
                            <span class="text-xs" :class="chars > 900 ? 'text-[#F16A00]' : 'text-gray-400'">
                                <span x-text="chars"></span>/1000
                            </span>
                        </div>
                    </div>
                    @error('bio')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Localisation --}}
                <div>
                    <label for="location" class="block text-sm font-medium text-gray-700 mb-2">
                        <span class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                            </svg>
                            Localisation
                        </span>
                    </label>
                    <input type="text" id="location" name="location" value="{{ old('location', $profile->location) }}"
                        class="w-full px-4 py-2.5 border border-gray-200 rounded-lg focus:ring-2 focus:ring-[#F16A00] focus:border-[#F16A00] transition"
                        placeholder="Ex: Cocody, Abidjan">
                    @error('location')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Profession --}}
                <div>
                    <label for="work" class="block text-sm font-medium text-gray-700 mb-2">
                        <span class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                            Profession
                        </span>
                    </label>
                    <input type="text" id="work" name="work" value="{{ old('work', $profile->work) }}"
                        class="w-full px-4 py-2.5 border border-gray-200 rounded-lg focus:ring-2 focus:ring-[#F16A00] focus:border-[#F16A00] transition"
                        placeholder="Ex: Consultant en marketing">
                    @error('work')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        {{-- Langues parlées --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
                <h2 class="font-semibold text-gray-900 flex items-center gap-2">
                    <svg class="w-5 h-5 text-[#F16A00]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129" />
                    </svg>
                    Langues parlées
                </h2>
            </div>
            <div class="p-6" x-data="languageSelector({{ \Illuminate\Support\Js::encode(['languages' => old('languages', $profile->languages ?? ['Français'])]) }})">
                {{-- Langues sélectionnées --}}
                <div class="flex flex-wrap gap-2 mb-4">
                    <template x-for="(lang, index) in languages" :key="index">
                        <span
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-[#FFE7D1] text-[#A34700] rounded-full text-sm font-medium">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129" />
                            </svg>
                            <span x-text="lang"></span>
                            <input type="hidden" name="languages[]" :value="lang">
                            <button type="button" @click="removeLanguage(lang)"
                                class="ml-1 hover:text-[#6e0826] transition">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </span>
                    </template>
                    <span x-show="languages.length === 0" class="text-sm text-gray-400 py-1.5">Aucune langue
                        sélectionnée</span>
                </div>

                {{-- Ajouter une langue --}}
                <div class="flex items-center gap-2 mb-4">
                    <input type="text" x-model="newLang" @keydown.enter.prevent="addLanguage()"
                        class="flex-1 px-4 py-2.5 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-[#F16A00] focus:border-[#F16A00]"
                        placeholder="Tapez une langue...">
                    <button type="button" @click="addLanguage()"
                        class="px-4 py-2.5 bg-[#F16A00] hover:bg-[#CC5A00] text-white rounded-lg text-sm font-medium transition">
                        Ajouter
                    </button>
                </div>

                {{-- Suggestions --}}
                <div x-show="availableSuggestions.length > 0">
                    <p class="text-xs text-gray-500 mb-2">Suggestions :</p>
                    <div class="flex flex-wrap gap-2">
                        <template x-for="suggestion in availableSuggestions.slice(0, 8)" :key="suggestion">
                            <button type="button" @click="addLanguage(suggestion)"
                                class="px-3 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-full text-sm transition">
                                <span x-text="suggestion"></span>
                            </button>
                        </template>
                    </div>
                </div>

                @error('languages')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>

        {{-- Paramètres de confidentialité --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
                <h2 class="font-semibold text-gray-900 flex items-center gap-2">
                    <svg class="w-5 h-5 text-[#F16A00]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                    Confidentialité
                </h2>
            </div>
            <div class="p-6 space-y-4">
                <p class="text-sm text-gray-500 mb-4">Choisissez les informations que vous souhaitez afficher
                    sur votre profil.</p>

                <label
                    class="flex items-start gap-4 p-4 rounded-lg border border-gray-200 hover:border-[#FFD0A3] cursor-pointer transition group">
                    <input type="checkbox" name="show_email" value="1"
                        {{ old('show_email', $profile->show_email) ? 'checked' : '' }}
                        class="mt-0.5 w-5 h-5 text-[#F16A00] border-gray-300 rounded focus:ring-[#F16A00]">
                    <div class="flex-1">
                        <div class="flex items-center gap-2">
                            <svg class="w-5 h-5 text-gray-400 group-hover:text-[#F16A00] transition" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                            <span class="font-medium text-gray-900">Afficher mon email</span>
                        </div>
                        <p class="text-sm text-gray-500 mt-1 ml-7">{{ $user->email }}</p>
                    </div>
                </label>

                <label
                    class="flex items-start gap-4 p-4 rounded-lg border border-gray-200 hover:border-[#FFD0A3] cursor-pointer transition group">
                    <input type="checkbox" name="show_phone" value="1"
                        {{ old('show_phone', $profile->show_phone) ? 'checked' : '' }}
                        class="mt-0.5 w-5 h-5 text-[#F16A00] border-gray-300 rounded focus:ring-[#F16A00]">
                    <div class="flex-1">
                        <div class="flex items-center gap-2">
                            <svg class="w-5 h-5 text-gray-400 group-hover:text-[#F16A00] transition" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                            </svg>
                            <span class="font-medium text-gray-900">Afficher mon téléphone</span>
                        </div>
                        <p class="text-sm text-gray-500 mt-1 ml-7">{{ $user->phone ?? 'Non renseigné' }}</p>
                    </div>
                </label>
            </div>
        </div>

        {{-- Statistiques du profil (lecture seule) --}}
        <div class="bg-gray-50 rounded-xl border border-gray-200 p-6">
            <h3 class="text-sm font-semibold text-gray-700 mb-4 flex items-center gap-2">
                <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                </svg>
                Statistiques de votre profil
            </h3>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                <div class="text-center">
                    <p class="text-2xl font-bold text-gray-900">{{ $profile->profile_views }}</p>
                    <p class="text-xs text-gray-500">Vues du profil</p>
                </div>
                <div class="text-center">
                    <p class="text-2xl font-bold text-gray-900">{{ $profile->total_reviews_given }}</p>
                    <p class="text-xs text-gray-500">Avis donnés</p>
                </div>
                <div class="text-center">
                    <p class="text-2xl font-bold text-gray-900">{{ $profile->total_reviews_received }}</p>
                    <p class="text-xs text-gray-500">Avis reçus</p>
                </div>
                <div class="text-center">
                    <p class="text-2xl font-bold text-gray-900">{{ $user->created_at->diffForHumans(null, true) }}</p>
                    <p class="text-xs text-gray-500">Membre depuis</p>
                </div>
            </div>
        </div>

        {{-- Actions --}}
        <div class="flex flex-col-reverse sm:flex-row items-center justify-between gap-4 pt-4">
            <a href="{{ route('profile.public', $user) }}"
                class="w-full sm:w-auto px-6 py-3 text-center text-gray-600 hover:text-gray-900 font-medium transition">
                Annuler
            </a>
            <button type="submit"
                class="w-full sm:w-auto px-8 py-3 bg-[#F16A00] hover:bg-[#CC5A00] text-white font-medium rounded-lg shadow-sm hover:shadow transition flex items-center justify-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                Enregistrer les modifications
            </button>
        </div>
    </form>
@endsection
