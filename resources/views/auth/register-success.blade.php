<x-app-layout>
    @section('title', 'Inscription réussie - REZI')

    <div class="min-h-[calc(100vh-56px)] md:min-h-[calc(100vh-64px)] flex">
        {{-- Left Side - Branding (Desktop only) --}}
        <div
            class="hidden lg:flex lg:w-1/2 bg-linear-to-br from-[#F16A00] via-[#F16A00] to-green-700 relative overflow-hidden">
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
            <div class="absolute top-16 left-16 w-24 h-24 bg-white/10 rounded-3xl backdrop-blur-sm flex items-center justify-center">
                <svg class="w-12 h-12 text-white/80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                        d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                </svg>
            </div>
            <div class="absolute bottom-32 right-16 w-20 h-20 bg-white/10 rounded-2xl backdrop-blur-sm flex items-center justify-center">
                <svg class="w-10 h-10 text-white/80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <div class="absolute bottom-20 left-24 w-16 h-16 bg-white/10 rounded-xl backdrop-blur-sm animate-pulse"></div>
            <div class="absolute top-40 right-20 w-12 h-12 bg-white/10 rounded-full backdrop-blur-sm animate-bounce" style="animation-duration: 3s;"></div>

            {{-- Content --}}
            <div class="relative z-10 flex flex-col justify-center items-center w-full p-12 text-white">
                <div class="mb-8">
                    <div class="w-20 h-20 bg-white rounded-2xl flex items-center justify-center shadow-2xl">
                        <span class="text-3xl font-bold bg-linear-to-r from-[#F16A00] to-[#F16A00] bg-clip-text text-transparent">R</span>
                    </div>
                </div>
                <h1 class="text-4xl font-bold text-center mb-4">Bienvenue sur REZI !</h1>
                <p class="text-xl text-[#FFE7D1] text-center mb-12 max-w-md">
                    Votre compte a été créé avec succès. Découvrez des centaines de résidences meublées à Abidjan.
                </p>
                <div class="space-y-4 w-full max-w-sm">
                    <div class="flex items-start gap-4">
                        <div class="w-8 h-8 bg-white/20 rounded-full flex items-center justify-center shrink-0 mt-0.5">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div>
                            <p class="font-semibold">+500 résidences disponibles</p>
                            <p class="text-[#FFD0A3] text-sm">Résidences meublées à Abidjan et environs</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-4">
                        <div class="w-8 h-8 bg-white/20 rounded-full flex items-center justify-center shrink-0 mt-0.5">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div>
                            <p class="font-semibold">Réservation instantanée</p>
                            <p class="text-[#FFD0A3] text-sm">Réservez et payez en ligne en quelques clics</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-4">
                        <div class="w-8 h-8 bg-white/20 rounded-full flex items-center justify-center shrink-0 mt-0.5">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div>
                            <p class="font-semibold">Support 7j/7</p>
                            <p class="text-[#FFD0A3] text-sm">Notre équipe est disponible pour vous aider</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Right Side - Status --}}
        <div class="w-full lg:w-1/2 flex items-center justify-center p-6 md:p-12 bg-gray-50">
            <div class="w-full max-w-md">

                @if(session('registration') === 'success')
                    {{-- SUCCESS STATE --}}
                    <div class="text-center">
                        {{-- Animated checkmark --}}
                        <div class="flex items-center justify-center mb-6">
                            <div class="w-24 h-24 bg-green-100 rounded-full flex items-center justify-center">
                                <svg class="w-12 h-12 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                        </div>

                        <h2 class="text-2xl font-bold text-gray-900 mb-2">Inscription réussie !</h2>
                        <p class="text-gray-500 mb-2">
                            Bienvenue, <span class="font-semibold text-[#F16A00]">{{ auth()->user()->name ?? '' }}</span> 👋
                        </p>
                        <p class="text-gray-500 mb-8">
                            Votre compte a été créé avec succès. Vous êtes maintenant connecté et prêt à explorer les meilleures résidences d'Abidjan.
                        </p>

                        {{-- Email verification notice --}}
                        @if(auth()->user() && !auth()->user()->hasVerifiedEmail())
                            <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 mb-6 text-left">
                                <div class="flex items-start gap-3">
                                    <svg class="w-5 h-5 text-amber-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <div>
                                        <p class="text-sm font-semibold text-amber-800">Vérifiez votre email</p>
                                        <p class="text-sm text-amber-700 mt-0.5">
                                            Un email de confirmation a été envoyé à <strong>{{ auth()->user()->email }}</strong>. Vérifiez votre boîte pour activer toutes les fonctionnalités.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        @endif

                        {{-- CTAs --}}
                        <div class="space-y-3">
                            <a href="{{ route('home') }}"
                                class="w-full inline-flex items-center justify-center gap-2 px-6 py-3.5 bg-[#F16A00] hover:bg-[#CC5A00] text-white font-semibold rounded-xl transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                                Rechercher une résidence
                            </a>
                            <a href="{{ route('profile.show') }}"
                                class="w-full inline-flex items-center justify-center gap-2 px-6 py-3.5 bg-white border border-gray-200 hover:bg-gray-50 text-gray-700 font-semibold rounded-xl transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                                Compléter mon profil
                            </a>
                        </div>
                    </div>

                @elseif(session('registration') === 'error')
                    {{-- ERROR STATE --}}
                    <div class="text-center">
                        <div class="flex items-center justify-center mb-6">
                            <div class="w-24 h-24 bg-red-100 rounded-full flex items-center justify-center">
                                <svg class="w-12 h-12 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                        </div>

                        <h2 class="text-2xl font-bold text-gray-900 mb-2">Inscription échouée</h2>
                        <p class="text-gray-500 mb-6">
                            Une erreur s'est produite lors de la création de votre compte. Veuillez réessayer.
                        </p>

                        @if(session('registration_error'))
                            <div class="bg-red-50 border border-red-200 rounded-xl p-4 mb-6 text-left">
                                <div class="flex items-start gap-3">
                                    <svg class="w-5 h-5 text-red-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <p class="text-sm text-red-700">{{ session('registration_error') }}</p>
                                </div>
                            </div>
                        @endif

                        <div class="space-y-3">
                            <a href="{{ route('register') }}"
                                class="w-full inline-flex items-center justify-center gap-2 px-6 py-3.5 bg-[#F16A00] hover:bg-[#CC5A00] text-white font-semibold rounded-xl transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                </svg>
                                Réessayer l'inscription
                            </a>
                            <a href="{{ route('login') }}"
                                class="w-full inline-flex items-center justify-center gap-2 px-6 py-3.5 bg-white border border-gray-200 hover:bg-gray-50 text-gray-700 font-semibold rounded-xl transition-colors">
                                J'ai déjà un compte — Se connecter
                            </a>
                        </div>
                    </div>

                @else
                    {{-- FALLBACK: accès direct sans session → rediriger --}}
                    <div class="text-center">
                        <div class="flex items-center justify-center mb-6">
                            <div class="w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center">
                                <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                        </div>
                        <h2 class="text-2xl font-bold text-gray-900 mb-2">Page inaccessible</h2>
                        <p class="text-gray-500 mb-6">Vous n'avez pas accès à cette page directement.</p>
                        <a href="{{ route('register') }}"
                            class="inline-flex items-center justify-center gap-2 px-6 py-3.5 bg-[#F16A00] hover:bg-[#CC5A00] text-white font-semibold rounded-xl transition-colors">
                            Créer un compte
                        </a>
                    </div>
                @endif

            </div>
        </div>
    </div>
</x-app-layout>
