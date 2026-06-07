<x-app-layout>
    @section('title', 'Inscription - Rezi Studio Meublé Faya')

    <div class="min-h-[calc(100vh-56px)] md:min-h-[calc(100vh-64px)] flex" x-data="{
        showPassword: false,
        showConfirmPassword: false,
        role: '{{ old('role', 'user') }}',
        step: 1,
        name: '{{ old('name') }}',
        email: '{{ old('email') }}'
    }">
        {{-- Left Side - Branding & Benefits (Desktop only) --}}
        <div
            class="hidden lg:flex lg:w-1/2 bg-linear-to-br from-[#F16A00] via-[#CC5A00] to-[#c8102e] relative overflow-hidden">
            {{-- Background Pattern --}}
            <div class="absolute inset-0 opacity-10">
                <svg class="w-full h-full" viewBox="0 0 100 100" preserveAspectRatio="none">
                    <defs>
                        <pattern id="dots" width="10" height="10" patternUnits="userSpaceOnUse">
                            <circle cx="2" cy="2" r="1" fill="white" />
                        </pattern>
                    </defs>
                    <rect width="100" height="100" fill="url(#dots)" />
                </svg>
            </div>

            {{-- Floating Elements --}}
            <div
                class="absolute top-16 left-16 w-24 h-24 bg-white/10 rounded-3xl backdrop-blur-sm animate-pulse flex items-center justify-center">
                <svg class="w-12 h-12 text-white/80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                        d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                </svg>
            </div>
            <div class="absolute top-40 right-16 w-20 h-20 bg-white/10 rounded-2xl backdrop-blur-sm animate-bounce flex items-center justify-center"
                style="animation-duration: 3s;">
                <svg class="w-10 h-10 text-white/80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                        d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                </svg>
            </div>
            <div class="absolute bottom-40 left-24 w-16 h-16 bg-white/10 rounded-xl backdrop-blur-sm animate-pulse flex items-center justify-center"
                style="animation-delay: 0.5s;">
                <svg class="w-8 h-8 text-white/80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                        d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                </svg>
            </div>
            <div class="absolute bottom-24 right-20 w-28 h-28 bg-white/10 rounded-full backdrop-blur-sm animate-bounce flex items-center justify-center"
                style="animation-duration: 4s;">
                <svg class="w-14 h-14 text-white/80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                        d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                </svg>
            </div>

            {{-- Content --}}
            <div class="relative z-10 flex flex-col justify-center items-center w-full p-12 text-white">
                {{-- Logo --}}
                <div class="mb-8">
                    <div class="w-20 h-20 bg-white rounded-2xl flex items-center justify-center shadow-2xl">
                        <span
                            class="text-3xl font-bold bg-linear-to-r from-[#F16A00] to-[#F16A00] bg-clip-text text-transparent">R</span>
                    </div>
                </div>

                {{-- Headline --}}
                <h1 class="text-4xl font-bold text-center mb-4">Rejoignez Rezi Studio Meublé Faya</h1>
                <p class="text-xl text-[#FFE7D1] text-center mb-12 max-w-md">
                    Créez votre compte et accédez à des centaines de résidences meublées
                </p>

                {{-- Benefits --}}
                <div class="space-y-4 w-full max-w-sm">
                    <div class="flex items-start gap-4">
                        <div class="w-8 h-8 bg-white/20 rounded-full flex items-center justify-center shrink-0 mt-0.5">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                    clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div>
                            <div class="font-semibold">Inscription gratuite</div>
                            <div class="text-sm text-[#FFE7D1]">Aucun frais pour créer votre compte</div>
                        </div>
                    </div>

                    <div class="flex items-start gap-4">
                        <div class="w-8 h-8 bg-white/20 rounded-full flex items-center justify-center shrink-0 mt-0.5">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                    clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div>
                            <div class="font-semibold">Contact direct propriétaires</div>
                            <div class="text-sm text-[#FFE7D1]">Sans intermédiaire ni commission</div>
                        </div>
                    </div>

                    <div class="flex items-start gap-4">
                        <div class="w-8 h-8 bg-white/20 rounded-full flex items-center justify-center shrink-0 mt-0.5">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                    clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div>
                            <div class="font-semibold">Alertes personnalisées</div>
                            <div class="text-sm text-[#FFE7D1]">Soyez notifié des nouvelles offres</div>
                        </div>
                    </div>

                    <div class="flex items-start gap-4">
                        <div class="w-8 h-8 bg-white/20 rounded-full flex items-center justify-center shrink-0 mt-0.5">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                    clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div>
                            <div class="font-semibold">Favoris & historique</div>
                            <div class="text-sm text-[#FFE7D1]">Sauvegardez vos recherches</div>
                        </div>
                    </div>
                </div>

                {{-- Testimonial --}}
                <div class="mt-12 bg-white/10 backdrop-blur-sm rounded-2xl p-6 max-w-sm">
                    <div class="flex items-center gap-1 mb-3">
                        @for ($i = 0; $i < 5; $i++)
                            <svg class="w-4 h-4 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                <path
                                    d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                            </svg>
                        @endfor
                    </div>
                    <p class="text-sm italic text-[#FFF4EB]">"J'ai trouvé mon appartement en 2 jours grâce à Rezi Studio Meublé Faya. La
                        géolocalisation m'a fait gagner un temps fou !"</p>
                    <div class="mt-3 flex items-center gap-3">
                        <div
                            class="w-8 h-8 bg-white/20 rounded-full flex items-center justify-center text-sm font-bold">
                            AK</div>
                        <div>
                            <div class="text-sm font-medium">Aminata K.</div>
                            <div class="text-xs text-[#FFD0A3]">Cocody, Abidjan</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Right Side - Registration Form --}}
        <div class="w-full lg:w-1/2 flex flex-col justify-center items-center p-6 sm:p-12 bg-gray-50 overflow-y-auto">
            <div class="w-full max-w-md">
                {{-- Header --}}
                <div class="text-center mb-6">
                    <h2 class="text-3xl font-bold text-gray-900">Créer un compte</h2>
                    <p class="mt-2 text-gray-600">Rejoignez la communauté Rezi Studio Meublé Faya</p>
                </div>

                {{-- Progress Steps --}}
                <div class="flex items-center justify-center gap-2 mb-8">
                    <div class="flex items-center gap-2">
                        <div :class="step >= 1 ? 'bg-[#F16A00] text-white' : 'bg-gray-200 text-gray-500'"
                            class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-semibold transition-colors">
                            1</div>
                        <span class="text-sm font-medium text-gray-600 hidden sm:block">Type</span>
                    </div>
                    <div class="w-8 h-0.5 bg-gray-200">
                        <div :class="step >= 2 ? 'bg-[#F16A00]' : 'bg-gray-200'" class="h-full transition-all"
                            :style="step >= 2 ? 'width: 100%' : 'width: 0%'"></div>
                    </div>
                    <div class="flex items-center gap-2">
                        <div :class="step >= 2 ? 'bg-[#F16A00] text-white' : 'bg-gray-200 text-gray-500'"
                            class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-semibold transition-colors">
                            2</div>
                        <span class="text-sm font-medium text-gray-600 hidden sm:block">Infos</span>
                    </div>
                    <div class="w-8 h-0.5 bg-gray-200">
                        <div :class="step >= 3 ? 'bg-[#F16A00]' : 'bg-gray-200'" class="h-full transition-all"
                            :style="step >= 3 ? 'width: 100%' : 'width: 0%'"></div>
                    </div>
                    <div class="flex items-center gap-2">
                        <div :class="step >= 3 ? 'bg-[#F16A00] text-white' : 'bg-gray-200 text-gray-500'"
                            class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-semibold transition-colors">
                            3</div>
                        <span class="text-sm font-medium text-gray-600 hidden sm:block">Sécurité</span>
                    </div>
                </div>

                {{-- Registration Form --}}
                <form method="POST" action="{{ route('register') }}" class="space-y-6">
                    @csrf

                    {{-- Code de parrainage (caché) --}}
                    @if (request('ref'))
                        <input type="hidden" name="ref" value="{{ request('ref') }}">
                        <div
                            class="flex items-center gap-2 px-4 py-3 bg-green-50 border border-green-200 rounded-xl text-sm text-green-700">
                            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7" />
                            </svg>
                            <span>Code parrainage <strong>{{ request('ref') }}</strong> appliqué !</span>
                        </div>
                    @endif

                    {{-- Step 1: Account Type --}}
                    <div x-show="step === 1" x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0 translate-x-4"
                        x-transition:enter-end="opacity-100 translate-x-0">
                        <label class="block text-sm font-medium text-gray-700 mb-4">Quel type de compte souhaitez-vous
                            créer ?</label>

                        <div class="space-y-3">
                            {{-- Chercheur --}}
                            <label @click="role = 'user'"
                                :class="role === 'user' ? 'border-[#F16A00] bg-[#FFF4EB] ring-2 ring-[#F16A00]' :
                                    'border-gray-200 hover:border-gray-300 hover:bg-gray-50'"
                                class="flex items-center gap-4 p-5 bg-white border-2 rounded-xl cursor-pointer transition-all">
                                <input type="radio" name="role" value="user" x-model="role" class="sr-only">
                                <div :class="role === 'user' ? 'bg-[#F16A00]' : 'bg-gray-100'"
                                    class="w-14 h-14 rounded-xl flex items-center justify-center transition-colors">
                                    <svg :class="role === 'user' ? 'text-white' : 'text-gray-400'"
                                        class="w-7 h-7 transition-colors" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                    </svg>
                                </div>
                                <div class="flex-1">
                                    <div class="flex items-center gap-2">
                                        <span class="font-semibold text-gray-900">Je cherche une résidence</span>
                                        <span x-show="role === 'user'"
                                            class="text-xs bg-[#FFE7D1] text-[#CC5A00] px-2 py-0.5 rounded-full font-medium">Sélectionné</span>
                                    </div>
                                    <p class="text-sm text-gray-500 mt-1">Trouvez votre logement meublé idéal en
                                        Afrique de l'Ouest
                                    </p>
                                </div>
                                <div :class="role === 'user' ? 'border-[#F16A00]' : 'border-gray-300'"
                                    class="w-5 h-5 rounded-full border-2 flex items-center justify-center">
                                    <div x-show="role === 'user'" class="w-2.5 h-2.5 bg-[#F16A00] rounded-full">
                                    </div>
                                </div>
                            </label>

                            {{-- Propriétaire --}}
                            <label @click="role = 'owner'"
                                :class="role === 'owner' ? 'border-amber-500 bg-amber-50 ring-2 ring-amber-500' :
                                    'border-gray-200 hover:border-gray-300 hover:bg-gray-50'"
                                class="flex items-center gap-4 p-5 bg-white border-2 rounded-xl cursor-pointer transition-all">
                                <input type="radio" name="role" value="owner" x-model="role" class="sr-only">
                                <div :class="role === 'owner' ? 'bg-amber-500' : 'bg-gray-100'"
                                    class="w-14 h-14 rounded-xl flex items-center justify-center transition-colors">
                                    <svg :class="role === 'owner' ? 'text-white' : 'text-gray-400'"
                                        class="w-7 h-7 transition-colors" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                    </svg>
                                </div>
                                <div class="flex-1">
                                    <div class="flex items-center gap-2">
                                        <span class="font-semibold text-gray-900">Je suis propriétaire</span>
                                        <span x-show="role === 'owner'"
                                            class="text-xs bg-amber-100 text-amber-700 px-2 py-0.5 rounded-full font-medium">Sélectionné</span>
                                    </div>
                                    <p class="text-sm text-gray-500 mt-1">Publiez vos résidences et trouvez des
                                        locataires</p>
                                </div>
                                <div :class="role === 'owner' ? 'border-amber-500' : 'border-gray-300'"
                                    class="w-5 h-5 rounded-full border-2 flex items-center justify-center">
                                    <div x-show="role === 'owner'" class="w-2.5 h-2.5 bg-amber-500 rounded-full">
                                    </div>
                                </div>
                            </label>
                        </div>

                        {{-- Owner Benefits --}}
                        <div x-show="role === 'owner'" x-transition
                            class="mt-4 p-4 bg-linear-to-r from-amber-50 to-[#FFF4EB] rounded-xl border border-amber-100">
                            <div class="flex items-start gap-3">
                                <div
                                    class="w-8 h-8 bg-amber-500 rounded-lg flex items-center justify-center text-white shrink-0">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M13 10V3L4 14h7v7l9-11h-7z" />
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-amber-900">Avantages propriétaire</p>
                                    <ul class="mt-1 text-xs text-amber-700 space-y-1">
                                        <li>• Publication gratuite de vos annonces</li>
                                        <li>• Tableau de bord de gestion</li>
                                        <li>• Boostez vos annonces pour plus de visibilité</li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        @error('role')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror

                        <button type="button" @click="step = 2"
                            class="mt-6 w-full bg-[#F16A00] hover:bg-[#CC5A00] text-white font-semibold py-3.5 px-4 rounded-xl transition-all shadow-lg shadow-[#F16A00]/30 hover:shadow-[#F16A00]/40 flex items-center justify-center gap-2">
                            <span>Continuer</span>
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M14 5l7 7m0 0l-7 7m7-7H3" />
                            </svg>
                        </button>
                    </div>

                    {{-- Step 2: Personal Info --}}
                    <div x-show="step === 2" x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0 translate-x-4"
                        x-transition:enter-end="opacity-100 translate-x-0" style="display: none;">

                        {{-- Social Login --}}
                        <div class="space-y-3 mb-6">
                            <a href="{{ route('socialite.redirect', 'google') }}"
                                class="w-full flex items-center justify-center gap-3 px-4 py-3 bg-white border border-gray-200 rounded-xl text-gray-700 font-medium hover:bg-gray-50 hover:border-gray-300 transition-all shadow-sm">
                                <svg class="w-5 h-5" viewBox="0 0 24 24">
                                    <path fill="#4285F4"
                                        d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" />
                                    <path fill="#34A853"
                                        d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" />
                                    <path fill="#FBBC05"
                                        d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" />
                                    <path fill="#EA4335"
                                        d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" />
                                </svg>
                                S'inscrire avec Google
                            </a>
                            <a href="{{ route('socialite.redirect', 'facebook') }}"
                                class="w-full flex items-center justify-center gap-3 px-4 py-3 bg-[#1877F2] rounded-xl text-white font-medium hover:bg-[#1565D8] transition-all shadow-sm">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                    <path
                                        d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z" />
                                </svg>
                                S'inscrire avec Facebook
                            </a>
                        </div>

                        {{-- Divider --}}
                        <div class="relative mb-6">
                            <div class="absolute inset-0 flex items-center">
                                <div class="w-full border-t border-gray-200"></div>
                            </div>
                            <div class="relative flex justify-center text-sm">
                                <span class="px-4 bg-gray-50 text-gray-500">ou remplissez le formulaire</span>
                            </div>
                        </div>

                        {{-- Name --}}
                        <div class="mb-4">
                            <label for="name" class="block text-sm font-medium text-gray-700 mb-2">Nom
                                complet</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                </div>
                                <input id="name" name="name" type="text" x-model="name" required
                                    autocomplete="name" placeholder="Jean Kouassi"
                                    class="w-full pl-12 pr-4 py-3.5 bg-white border rounded-xl text-base text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#F16A00] focus:border-transparent transition-all {{ $errors->has('name') ? 'border-red-500 ring-1 ring-red-500' : 'border-gray-200' }}">
                            </div>
                            @error('name')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Email --}}
                        <div class="mb-4">
                            <label for="email" class="block text-sm font-medium text-gray-700 mb-2">Adresse
                                email</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207" />
                                    </svg>
                                </div>
                                <input id="email" name="email" type="email" inputmode="email" x-model="email" required
                                    autocomplete="username" placeholder="vous@exemple.com"
                                    class="w-full pl-12 pr-4 py-3.5 bg-white border rounded-xl text-base text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#F16A00] focus:border-transparent transition-all {{ $errors->has('email') ? 'border-red-500 ring-1 ring-red-500' : 'border-gray-200' }}">
                            </div>
                            @error('email')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Phone (Optional) --}}
                        <div class="mb-6">
                            <label for="phone" class="block text-sm font-medium text-gray-700 mb-2">
                                Téléphone <span class="text-gray-400 font-normal">(optionnel)</span>
                            </label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                    </svg>
                                </div>
                                <input id="phone" name="phone" type="tel" inputmode="tel"
                                    placeholder="+225 07 00 00 00 00"
                                    class="w-full pl-12 pr-4 py-3.5 bg-white border border-gray-200 rounded-xl text-base text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#F16A00] focus:border-transparent transition-all">
                            </div>
                        </div>

                        <div class="flex gap-3">
                            <button type="button" @click="step = 1"
                                class="px-6 py-3.5 border border-gray-200 rounded-xl text-gray-700 font-medium hover:bg-gray-50 transition-all flex items-center gap-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                                </svg>
                                Retour
                            </button>
                            <button type="button" @click="step = 3" :disabled="!name || !email"
                                :class="name && email ?
                                    'bg-[#F16A00] hover:bg-[#CC5A00] shadow-lg shadow-[#F16A00]/30' :
                                    'bg-gray-300 cursor-not-allowed'"
                                class="flex-1 text-white font-semibold py-3.5 px-4 rounded-xl transition-all flex items-center justify-center gap-2">
                                <span>Continuer</span>
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    {{-- Step 3: Password --}}
                    <div x-show="step === 3" x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0 translate-x-4"
                        x-transition:enter-end="opacity-100 translate-x-0" style="display: none;">

                        {{-- Password --}}
                        <div class="mb-4">
                            <label for="password" class="block text-sm font-medium text-gray-700 mb-2">Mot de
                                passe</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                    </svg>
                                </div>
                                <input id="password" name="password" :type="showPassword ? 'text' : 'password'"
                                    required autocomplete="new-password" placeholder="Minimum 8 caractères"
                                    class="w-full pl-12 pr-12 py-3.5 bg-white border rounded-xl text-base text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#F16A00] focus:border-transparent transition-all {{ $errors->has('password') ? 'border-red-500 ring-1 ring-red-500' : 'border-gray-200' }}">
                                <button type="button" @click="showPassword = !showPassword"
                                    class="absolute inset-y-0 right-0 pr-4 flex items-center text-gray-400 hover:text-gray-600">
                                    <svg x-show="!showPassword" class="w-5 h-5" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                    <svg x-show="showPassword" class="w-5 h-5" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24" style="display: none;">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                                    </svg>
                                </button>
                            </div>
                            @error('password')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror

                            {{-- Password Strength Hints --}}
                            <div class="mt-3 grid grid-cols-2 gap-2 text-xs">
                                <div class="flex items-center gap-1.5 text-gray-500">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M5 13l4 4L19 7" />
                                    </svg>
                                    8 caractères min.
                                </div>
                                <div class="flex items-center gap-1.5 text-gray-500">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M5 13l4 4L19 7" />
                                    </svg>
                                    1 majuscule
                                </div>
                                <div class="flex items-center gap-1.5 text-gray-500">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M5 13l4 4L19 7" />
                                    </svg>
                                    1 chiffre
                                </div>
                                <div class="flex items-center gap-1.5 text-gray-500">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M5 13l4 4L19 7" />
                                    </svg>
                                    1 caractère spécial
                                </div>
                            </div>
                        </div>

                        {{-- Confirm Password --}}
                        <div class="mb-6">
                            <label for="password_confirmation"
                                class="block text-sm font-medium text-gray-700 mb-2">Confirmer le mot de passe</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                    </svg>
                                </div>
                                <input id="password_confirmation" name="password_confirmation"
                                    :type="showConfirmPassword ? 'text' : 'password'" required
                                    autocomplete="new-password" placeholder="Répétez le mot de passe"
                                    class="w-full pl-12 pr-12 py-3.5 bg-white border border-gray-200 rounded-xl text-base text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#F16A00] focus:border-transparent transition-all">
                                <button type="button" @click="showConfirmPassword = !showConfirmPassword"
                                    class="absolute inset-y-0 right-0 pr-4 flex items-center text-gray-400 hover:text-gray-600">
                                    <svg x-show="!showConfirmPassword" class="w-5 h-5" fill="none"
                                        stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                    <svg x-show="showConfirmPassword" class="w-5 h-5" fill="none"
                                        stroke="currentColor" viewBox="0 0 24 24" style="display: none;">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        {{-- Terms --}}
                        <div class="mb-6">
                            <label class="flex items-start gap-3 cursor-pointer">
                                <input type="checkbox" name="terms" required
                                    class="mt-1 w-4 h-4 rounded border-gray-300 text-[#F16A00] focus:ring-[#F16A00]">
                                <span class="text-sm text-gray-600">
                                    J'accepte les <a href="{{ route('pages.cgu') }}"
                                        class="text-[#F16A00] hover:underline">Conditions d'utilisation</a> et la <a
                                        href="{{ route('pages.confidentialite') }}"
                                        class="text-[#F16A00] hover:underline">Politique de confidentialité</a>
                                </span>
                            </label>
                        </div>

                        <div class="flex gap-3">
                            <button type="button" @click="step = 2"
                                class="px-6 py-3.5 border border-gray-200 rounded-xl text-gray-700 font-medium hover:bg-gray-50 transition-all flex items-center gap-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                                </svg>
                                Retour
                            </button>
                            <button type="submit"
                                class="flex-1 bg-[#F16A00] hover:bg-[#CC5A00] text-white font-semibold py-3.5 px-4 rounded-xl transition-all shadow-lg shadow-[#F16A00]/30 hover:shadow-[#F16A00]/40 flex items-center justify-center gap-2">
                                <span>Créer mon compte</span>
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </form>

                {{-- Login Link --}}
                <p class="mt-8 text-center text-gray-600">
                    Déjà inscrit ?
                    <a href="{{ route('login') }}"
                        class="font-semibold text-[#F16A00] hover:text-[#CC5A00] transition-colors">
                        Se connecter
                    </a>
                </p>
            </div>
        </div>
    </div>
</x-app-layout>
