@extends('layouts.owner')

@section('title', 'Vérification d\'identité - Rezi App')

@section('owner-content')
    <div class="space-y-6" x-data="{
        documentType: '{{ old('document_type', 'cni') }}',
        frontPreview: null,
        backPreview: null,
        submitting: false,
        handleFile(input, side) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = (e) => {
                    if (side === 'front') this.frontPreview = e.target.result;
                    else this.backPreview = e.target.result;
                };
                reader.readAsDataURL(input.files[0]);
            }
        }
    }">

        {{-- ============================== HEADER ============================== --}}
        <div>
            <a href="{{ route('verification.dashboard') }}"
                class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-900 transition-colors mb-3">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
                </svg>
                Centre de vérification
            </a>
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-gray-900 rounded-xl flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" stroke-width="2"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M15 9h3.75M15 12h3.75M15 15h3.75M4.5 19.5h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5H4.5A2.25 2.25 0 002.25 6.75v10.5A2.25 2.25 0 004.5 19.5zm6-10.125a1.875 1.875 0 11-3.75 0 1.875 1.875 0 013.75 0zm1.294 6.336a6.721 6.721 0 01-3.17.789 6.721 6.721 0 01-3.168-.789 3.376 3.376 0 016.338 0z" />
                    </svg>
                </div>
                <div>
                    <h1 class="text-xl sm:text-2xl font-bold text-gray-900">Vérification d'identité</h1>
                    <p class="text-sm text-gray-500 mt-0.5">Étape 1 sur 2 · Document d'identité</p>
                </div>
            </div>

            {{-- Barre de progression --}}
            <div class="flex items-center gap-2 mt-5">
                <div class="flex-1 h-1.5 bg-gray-900 rounded-full"></div>
                <div class="flex-1 h-1.5 bg-gray-200 rounded-full"></div>
            </div>
        </div>

        <div class="max-w-2xl">
            <form action="{{ route('verification.identity.upload') }}" method="POST" enctype="multipart/form-data"
                @submit="submitting = true" class="space-y-6">
                @csrf

                {{-- ============================== ERREURS VALIDATION DOCUMENT ============================== --}}
                @if (session('document_validation_errors'))
                    <div class="px-4 py-3 bg-red-50 border border-red-100 rounded-xl">
                        <div class="flex items-start gap-3">
                            <svg class="w-5 h-5 text-red-500 shrink-0 mt-0.5" fill="none" stroke="currentColor"
                                stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                            </svg>
                            <div>
                                <p class="text-sm font-semibold text-red-900 mb-1">Document non valide</p>
                                <ul class="space-y-1">
                                    @foreach (session('document_validation_errors') as $error)
                                        <li class="text-xs text-red-700 flex items-start gap-1.5">
                                            <span class="text-red-400 mt-0.5">•</span>
                                            {{ $error }}
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    </div>
                @endif

                @if (session('document_warnings'))
                    <div class="px-4 py-3 bg-amber-50 border border-amber-100 rounded-xl">
                        <div class="flex items-start gap-3">
                            <svg class="w-5 h-5 text-amber-500 shrink-0 mt-0.5" fill="none" stroke="currentColor"
                                stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                            </svg>
                            <div>
                                <p class="text-sm font-semibold text-amber-900 mb-1">Attention</p>
                                <ul class="space-y-1">
                                    @foreach (session('document_warnings') as $warning)
                                        <li class="text-xs text-amber-700 flex items-start gap-1.5">
                                            <span class="text-amber-400 mt-0.5">•</span>
                                            {{ $warning }}
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- ============================== SÉCURITÉ ============================== --}}
                <div class="flex items-start gap-3 px-4 py-3 bg-emerald-50 border border-emerald-100 rounded-xl">
                    <svg class="w-5 h-5 text-emerald-600 shrink-0 mt-0.5" fill="none" stroke="currentColor"
                        stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                    </svg>
                    <div>
                        <p class="text-sm font-semibold text-emerald-900">Vos données sont protégées</p>
                        <p class="text-xs text-emerald-700 mt-0.5">Documents chiffrés, jamais partagés avec des tiers.</p>
                    </div>
                </div>

                {{-- ============================== TYPE DE DOCUMENT ============================== --}}
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                    <div class="p-5 sm:p-6">
                        <h2 class="text-sm font-semibold text-gray-900 mb-4">Type de document</h2>
                        <div class="grid grid-cols-2 gap-3">
                            {{-- CNI --}}
                            <label class="relative flex cursor-pointer rounded-xl border-2 p-4 transition-all"
                                :class="documentType === 'cni' ? 'border-gray-900 bg-gray-50' :
                                    'border-gray-200 hover:border-gray-300'">
                                <input type="radio" name="document_type" value="cni" class="sr-only"
                                    x-model="documentType">
                                <div class="flex flex-col items-center text-center w-full gap-2">
                                    <div class="w-12 h-12 rounded-xl flex items-center justify-center transition-colors"
                                        :class="documentType === 'cni' ? 'bg-gray-900 text-white' :
                                            'bg-gray-100 text-gray-500'">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M15 9h3.75M15 12h3.75M15 15h3.75M4.5 19.5h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5H4.5A2.25 2.25 0 002.25 6.75v10.5A2.25 2.25 0 004.5 19.5zm6-10.125a1.875 1.875 0 11-3.75 0 1.875 1.875 0 013.75 0zm1.294 6.336a6.721 6.721 0 01-3.17.789 6.721 6.721 0 01-3.168-.789 3.376 3.376 0 016.338 0z" />
                                        </svg>
                                    </div>
                                    <div>
                                        <span class="block text-sm font-semibold text-gray-900">CNI</span>
                                        <span class="block text-[11px] text-gray-500">Carte d'identité</span>
                                    </div>
                                </div>
                                <div x-show="documentType === 'cni'" class="absolute top-2.5 right-2.5">
                                    <svg class="w-5 h-5 text-gray-900" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd"
                                            d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z"
                                            clip-rule="evenodd" />
                                    </svg>
                                </div>
                            </label>

                            {{-- Passeport --}}
                            <label class="relative flex cursor-pointer rounded-xl border-2 p-4 transition-all"
                                :class="documentType === 'passport' ? 'border-gray-900 bg-gray-50' :
                                    'border-gray-200 hover:border-gray-300'">
                                <input type="radio" name="document_type" value="passport" class="sr-only"
                                    x-model="documentType">
                                <div class="flex flex-col items-center text-center w-full gap-2">
                                    <div class="w-12 h-12 rounded-xl flex items-center justify-center transition-colors"
                                        :class="documentType === 'passport' ? 'bg-gray-900 text-white' :
                                            'bg-gray-100 text-gray-500'">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25" />
                                        </svg>
                                    </div>
                                    <div>
                                        <span class="block text-sm font-semibold text-gray-900">Passeport</span>
                                        <span class="block text-[11px] text-gray-500">Passeport valide</span>
                                    </div>
                                </div>
                                <div x-show="documentType === 'passport'" class="absolute top-2.5 right-2.5">
                                    <svg class="w-5 h-5 text-gray-900" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd"
                                            d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z"
                                            clip-rule="evenodd" />
                                    </svg>
                                </div>
                            </label>
                        </div>
                        @error('document_type')
                            <p class="mt-2 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- ============================== PHOTOS DU DOCUMENT ============================== --}}
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                    <div class="p-5 sm:p-6 space-y-5">
                        <h2 class="text-sm font-semibold text-gray-900">Photos du document</h2>

                        {{-- Recto --}}
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-2">
                                <span
                                    x-text="documentType === 'passport' ? 'Page d\'identité du passeport' : 'Recto de la carte'"></span>
                                <span class="text-red-500">*</span>
                            </label>
                            <input type="file" name="document_front" accept="image/*" class="sr-only"
                                id="front-upload" @change="handleFile($event.target, 'front')">
                            <label for="front-upload"
                                class="group flex flex-col items-center justify-center w-full h-44 border-2 border-dashed rounded-xl cursor-pointer transition-all"
                                :class="frontPreview ? 'border-emerald-300 bg-emerald-50/50' :
                                    'border-gray-200 hover:border-gray-400 hover:bg-gray-50'">
                                <template x-if="frontPreview">
                                    <div class="relative w-full h-full p-2">
                                        <img :src="frontPreview" alt="Recto"
                                            class="w-full h-full object-contain rounded-lg">
                                        <div
                                            class="absolute top-3 right-3 w-6 h-6 bg-emerald-500 rounded-full flex items-center justify-center">
                                            <svg class="w-3.5 h-3.5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd"
                                                    d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                                    clip-rule="evenodd" />
                                            </svg>
                                        </div>
                                    </div>
                                </template>
                                <template x-if="!frontPreview">
                                    <div class="flex flex-col items-center justify-center py-6">
                                        <div
                                            class="w-12 h-12 rounded-xl bg-gray-100 flex items-center justify-center mb-3 group-hover:bg-gray-200 transition-colors">
                                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor"
                                                stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
                                            </svg>
                                        </div>
                                        <p class="text-sm font-semibold text-gray-700">Cliquez pour télécharger</p>
                                        <p class="text-[11px] text-gray-400 mt-0.5">JPG, PNG ou WEBP · max 10 Mo</p>
                                    </div>
                                </template>
                            </label>
                            @error('document_front')
                                <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Verso (CNI uniquement) --}}
                        <div x-show="documentType === 'cni'" x-transition>
                            <label class="block text-xs font-semibold text-gray-700 mb-2">
                                Verso de la carte <span class="text-red-500">*</span>
                            </label>
                            <input type="file" name="document_back" accept="image/*" class="sr-only"
                                id="back-upload" @change="handleFile($event.target, 'back')">
                            <label for="back-upload"
                                class="group flex flex-col items-center justify-center w-full h-44 border-2 border-dashed rounded-xl cursor-pointer transition-all"
                                :class="backPreview ? 'border-emerald-300 bg-emerald-50/50' :
                                    'border-gray-200 hover:border-gray-400 hover:bg-gray-50'">
                                <template x-if="backPreview">
                                    <div class="relative w-full h-full p-2">
                                        <img :src="backPreview" alt="Verso"
                                            class="w-full h-full object-contain rounded-lg">
                                        <div
                                            class="absolute top-3 right-3 w-6 h-6 bg-emerald-500 rounded-full flex items-center justify-center">
                                            <svg class="w-3.5 h-3.5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd"
                                                    d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                                    clip-rule="evenodd" />
                                            </svg>
                                        </div>
                                    </div>
                                </template>
                                <template x-if="!backPreview">
                                    <div class="flex flex-col items-center justify-center py-6">
                                        <div
                                            class="w-12 h-12 rounded-xl bg-gray-100 flex items-center justify-center mb-3 group-hover:bg-gray-200 transition-colors">
                                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor"
                                                stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
                                            </svg>
                                        </div>
                                        <p class="text-sm font-semibold text-gray-700">Cliquez pour télécharger</p>
                                        <p class="text-[11px] text-gray-400 mt-0.5">JPG, PNG ou WEBP · max 10 Mo</p>
                                    </div>
                                </template>
                            </label>
                            @error('document_back')
                                <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                {{-- ============================== INFORMATIONS DU DOCUMENT ============================== --}}
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                    <div class="p-5 sm:p-6 space-y-5">
                        <h2 class="text-sm font-semibold text-gray-900">Informations du document</h2>

                        <div>
                            <label for="document_number" class="block text-xs font-semibold text-gray-700 mb-1.5">
                                Numéro du document <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="document_number" id="document_number"
                                value="{{ old('document_number') }}"
                                class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:border-gray-900 focus:ring-1 focus:ring-gray-900 transition-colors placeholder:text-gray-400"
                                placeholder="Ex: CI00123456789">
                            @error('document_number')
                                <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="expiry_date" class="block text-xs font-semibold text-gray-700 mb-1.5">
                                Date d'expiration <span class="text-red-500">*</span>
                            </label>
                            <input type="date" name="expiry_date" id="expiry_date" value="{{ old('expiry_date') }}"
                                class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:border-gray-900 focus:ring-1 focus:ring-gray-900 transition-colors"
                                min="{{ date('Y-m-d') }}">
                            @error('expiry_date')
                                <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                {{-- ============================== CONSEILS ============================== --}}
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                    <div class="p-5 sm:p-6">
                        <h2 class="text-sm font-semibold text-gray-900 mb-3">Conseils pour une bonne photo</h2>
                        <div class="grid grid-cols-2 gap-3">
                            <div class="flex items-start gap-2.5">
                                <div
                                    class="shrink-0 w-6 h-6 rounded-md bg-emerald-50 flex items-center justify-center mt-0.5">
                                    <svg class="w-3.5 h-3.5 text-emerald-600" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd"
                                            d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                            clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <p class="text-xs text-gray-600">Informations lisibles et nettes</p>
                            </div>
                            <div class="flex items-start gap-2.5">
                                <div
                                    class="shrink-0 w-6 h-6 rounded-md bg-emerald-50 flex items-center justify-center mt-0.5">
                                    <svg class="w-3.5 h-3.5 text-emerald-600" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd"
                                            d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                            clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <p class="text-xs text-gray-600">Pas de reflets ni d'ombres</p>
                            </div>
                            <div class="flex items-start gap-2.5">
                                <div
                                    class="shrink-0 w-6 h-6 rounded-md bg-emerald-50 flex items-center justify-center mt-0.5">
                                    <svg class="w-3.5 h-3.5 text-emerald-600" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd"
                                            d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                            clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <p class="text-xs text-gray-600">Document sur fond uni</p>
                            </div>
                            <div class="flex items-start gap-2.5">
                                <div
                                    class="shrink-0 w-6 h-6 rounded-md bg-emerald-50 flex items-center justify-center mt-0.5">
                                    <svg class="w-3.5 h-3.5 text-emerald-600" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd"
                                            d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                            clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <p class="text-xs text-gray-600">Aucun bord coupé</p>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ============================== ACTIONS ============================== --}}
                <div class="flex items-center justify-between gap-4 pt-2 pb-4">
                    <a href="{{ route('verification.dashboard') }}"
                        class="px-5 py-2.5 text-sm font-semibold text-gray-700 bg-gray-100 rounded-xl hover:bg-gray-200 transition-colors">
                        Annuler
                    </a>
                    <button type="submit" :disabled="submitting"
                        class="inline-flex items-center gap-2 px-6 py-2.5 bg-gray-900 text-white text-sm font-semibold rounded-xl hover:bg-gray-800 transition-all shadow-sm hover:shadow-md disabled:opacity-50 disabled:cursor-not-allowed">
                        <template x-if="!submitting">
                            <span class="inline-flex items-center gap-2">
                                Continuer vers le selfie
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                                </svg>
                            </span>
                        </template>
                        <template x-if="submitting">
                            <span class="inline-flex items-center gap-2">
                                <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10"
                                        stroke="currentColor" stroke-width="4" />
                                    <path class="opacity-75" fill="currentColor"
                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
                                </svg>
                                Envoi en cours…
                            </span>
                        </template>
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
