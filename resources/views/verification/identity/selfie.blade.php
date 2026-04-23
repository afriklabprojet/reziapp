@extends('layouts.owner')

@section('title', 'Selfie de vérification - REZI')

@section('owner-content')
    <div class="space-y-6" x-data="{
        selfiePreview: null,
        useCamera: false,
        stream: null,
        submitting: false,

        async startCamera() {
            try {
                this.stream = await navigator.mediaDevices.getUserMedia({
                    video: { facingMode: 'user', width: 640, height: 480 }
                });
                // Le <video> est toujours dans le DOM (x-show), srcObject assignable directement
                this.$refs.video.srcObject = this.stream;
                this.useCamera = true;
            } catch (err) {
                if (err.name === 'NotAllowedError' || err.name === 'PermissionDeniedError') {
                    alert('Accès à la caméra refusé. Vérifiez les permissions de votre navigateur pour ce site, ou utilisez le bouton Choisir une photo ci-dessous.');
                } else if (err.name === 'NotFoundError' || err.name === 'DevicesNotFoundError') {
                    alert('Aucune caméra détectée. Veuillez utiliser le bouton Choisir une photo ci-dessous.');
                } else {
                    alert('Impossible d\'accéder à la caméra: ' + err.message);
                }
            }
        },

        stopCamera() {
            if (this.stream) {
                this.stream.getTracks().forEach(track => track.stop());
                this.stream = null;
            }
            this.useCamera = false;
        },

        capturePhoto() {
            const canvas = document.createElement('canvas');
            canvas.width = this.$refs.video.videoWidth;
            canvas.height = this.$refs.video.videoHeight;
            canvas.getContext('2d').drawImage(this.$refs.video, 0, 0);

            canvas.toBlob((blob) => {
                const file = new File([blob], 'selfie.jpg', { type: 'image/jpeg' });
                const dataTransfer = new DataTransfer();
                dataTransfer.items.add(file);
                this.$refs.selfieInput.files = dataTransfer.files;

                this.selfiePreview = canvas.toDataURL('image/jpeg');
                this.stopCamera();
            }, 'image/jpeg', 0.95);
        },

        resetSelfie() {
            this.selfiePreview = null;
            this.$refs.selfieInput.value = '';
        }
    }">

        {{-- ============================== HEADER ============================== --}}
        <div>
            <a href="{{ route('verification.identity.start') }}"
                class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-900 transition-colors mb-3">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
                </svg>
                Retour au document
            </a>
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-gray-900 rounded-xl flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" stroke-width="2"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                </div>
                <div>
                    <h1 class="text-xl sm:text-2xl font-bold text-gray-900">Selfie de vérification</h1>
                    <p class="text-sm text-gray-500 mt-0.5">Étape 2 sur 2 · Photo de votre visage</p>
                </div>
            </div>

            {{-- Barre de progression --}}
            <div class="flex items-center gap-2 mt-5">
                <div class="flex-1 h-1.5 bg-gray-900 rounded-full"></div>
                <div class="flex-1 h-1.5 bg-gray-900 rounded-full"></div>
            </div>
        </div>

        <div class="max-w-2xl">
            <form action="{{ route('verification.identity.selfie') }}" method="POST" enctype="multipart/form-data"
                @submit="submitting = true" class="space-y-6">
                @csrf

                {{-- ============================== COMPARAISON VISUELLE ============================== --}}
                <div class="flex items-center gap-3 px-4 py-3 bg-amber-50 border border-amber-100 rounded-xl">
                    <svg class="w-5 h-5 text-amber-600 shrink-0" fill="none" stroke="currentColor" stroke-width="2"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                    </svg>
                    <p class="text-sm text-amber-800">Votre selfie sera comparé à la photo de votre document d'identité</p>
                </div>

                {{-- ============================== ZONE DE CAPTURE ============================== --}}
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                    <div class="p-5 sm:p-6">
                        <h2 class="text-sm font-semibold text-gray-900 mb-4">Prenez votre selfie</h2>

                        {{-- Zone preview/caméra --}}
                        <div class="relative aspect-4/3 bg-gray-100 rounded-xl overflow-hidden mb-5">
                            {{-- Caméra active — x-show pour garder le <video> dans le DOM et pouvoir l'accéder via $refs --}}
                            <div x-show="useCamera" class="absolute inset-0">
                                <video x-ref="video" autoplay playsinline class="w-full h-full object-cover"></video>
                                <div class="absolute inset-0 flex items-center justify-center">
                                    <div class="w-48 h-60 border-[3px] border-white/60 border-dashed rounded-full">
                                    </div>
                                </div>
                                <div class="absolute bottom-3 left-0 right-0 text-center">
                                    <span
                                        class="inline-block px-3 py-1 bg-black/50 text-white text-xs rounded-full">Placez
                                        votre visage dans le cercle</span>
                                </div>
                            </div>

                            {{-- Preview selfie --}}
                            <template x-if="selfiePreview && !useCamera">
                                <div class="relative w-full h-full">
                                    <img :src="selfiePreview" alt="Selfie" class="w-full h-full object-cover">
                                    <div
                                        class="absolute top-3 right-3 w-8 h-8 bg-emerald-500 rounded-full flex items-center justify-center shadow-md">
                                        <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd"
                                                d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                                clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                </div>
                            </template>

                            {{-- État initial --}}
                            <template x-if="!useCamera && !selfiePreview">
                                <div class="absolute inset-0 flex flex-col items-center justify-center">
                                    <div class="w-20 h-20 rounded-full bg-gray-200 flex items-center justify-center mb-4">
                                        <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor"
                                            stroke-width="1.5" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                        </svg>
                                    </div>
                                    <p class="text-sm font-semibold text-gray-600">Votre selfie apparaîtra ici</p>
                                    <p class="text-[11px] text-gray-400 mt-0.5">Photo de face, bien éclairée</p>
                                </div>
                            </template>
                        </div>

                        {{-- Boutons --}}
                        <div class="flex flex-wrap gap-3 justify-center">
                            <input type="file" name="selfie" accept="image/*" class="sr-only"
                                x-ref="selfieInput">
                            <template x-if="!useCamera && !selfiePreview">
                                <div class="flex flex-wrap gap-3 justify-center w-full">
                                    <button type="button" @click="startCamera()"
                                        class="inline-flex items-center gap-2 px-5 py-2.5 bg-gray-900 text-white text-sm font-semibold rounded-xl hover:bg-gray-800 transition-colors shadow-sm">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M6.827 6.175A2.31 2.31 0 015.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 00-1.134-.175 2.31 2.31 0 01-1.64-1.055l-.822-1.316a2.192 2.192 0 00-1.736-1.039 48.774 48.774 0 00-5.232 0 2.192 2.192 0 00-1.736 1.039l-.821 1.316z" />
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M16.5 12.75a4.5 4.5 0 11-9 0 4.5 4.5 0 019 0z" />
                                        </svg>
                                        Démarrer la caméra
                                    </button>
                                </div>
                            </template>

                            <template x-if="useCamera">
                                <div class="flex gap-3">
                                    <button type="button" @click="capturePhoto()"
                                        class="inline-flex items-center gap-2 px-6 py-2.5 bg-emerald-600 text-white text-sm font-semibold rounded-xl hover:bg-emerald-700 transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M6.827 6.175A2.31 2.31 0 015.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 00-1.134-.175 2.31 2.31 0 01-1.64-1.055l-.822-1.316a2.192 2.192 0 00-1.736-1.039 48.774 48.774 0 00-5.232 0 2.192 2.192 0 00-1.736 1.039l-.821 1.316z" />
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M16.5 12.75a4.5 4.5 0 11-9 0 4.5 4.5 0 019 0z" />
                                        </svg>
                                        Prendre la photo
                                    </button>
                                    <button type="button" @click="stopCamera()"
                                        class="px-5 py-2.5 text-sm font-semibold text-gray-700 bg-gray-100 rounded-xl hover:bg-gray-200 transition-colors">
                                        Annuler
                                    </button>
                                </div>
                            </template>

                            <template x-if="selfiePreview && !useCamera">
                                <button type="button" @click="resetSelfie()"
                                    class="inline-flex items-center gap-2 px-5 py-2.5 bg-gray-100 text-gray-700 text-sm font-semibold rounded-xl hover:bg-gray-200 transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182" />
                                    </svg>
                                    Reprendre le selfie
                                </button>
                            </template>
                        </div>

                        @error('selfie')
                            <p class="mt-3 text-xs text-red-600 text-center">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- ============================== CONSEILS ============================== --}}
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                    <div class="p-5 sm:p-6">
                        <h2 class="text-sm font-semibold text-gray-900 mb-3">Conseils pour un selfie valide</h2>
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
                                <p class="text-xs text-gray-600">Visage bien éclairé, de face</p>
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
                                <p class="text-xs text-gray-600">Yeux ouverts et visibles</p>
                            </div>
                            <div class="flex items-start gap-2.5">
                                <div
                                    class="shrink-0 w-6 h-6 rounded-md bg-red-50 flex items-center justify-center mt-0.5">
                                    <svg class="w-3.5 h-3.5 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd"
                                            d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                                            clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <p class="text-xs text-gray-600">Pas de lunettes de soleil</p>
                            </div>
                            <div class="flex items-start gap-2.5">
                                <div
                                    class="shrink-0 w-6 h-6 rounded-md bg-red-50 flex items-center justify-center mt-0.5">
                                    <svg class="w-3.5 h-3.5 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd"
                                            d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                                            clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <p class="text-xs text-gray-600">Pas de masque sur le visage</p>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ============================== ACTIONS ============================== --}}
                <div class="flex items-center justify-between gap-4 pt-2 pb-4">
                    <a href="{{ route('verification.identity.start') }}"
                        class="px-5 py-2.5 text-sm font-semibold text-gray-700 bg-gray-100 rounded-xl hover:bg-gray-200 transition-colors">
                        Retour
                    </a>
                    <button type="submit" :disabled="!selfiePreview || submitting"
                        class="inline-flex items-center gap-2 px-6 py-2.5 bg-gray-900 text-white text-sm font-semibold rounded-xl hover:bg-gray-800 transition-all shadow-sm hover:shadow-md disabled:opacity-50 disabled:cursor-not-allowed">
                        <template x-if="!submitting">
                            <span class="inline-flex items-center gap-2">
                                Soumettre pour vérification
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
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
