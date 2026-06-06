<x-app-layout>
    @section('title', 'Connexion - ReziApp')

    <div class="min-h-[calc(100vh-56px)] md:min-h-[calc(100vh-64px)] flex" x-data="{ showPassword: false }">
        {{-- ══════════════════════════════════════
             LEFT SIDE – BRANDING (Desktop)
        ══════════════════════════════════════ --}}
        <div class="hidden lg:flex lg:w-1/2 relative overflow-hidden">
            {{-- Image d'arrière-plan --}}
            <img src="{{ asset('images/login-bg.jpg') }}" alt="" aria-hidden="true"
                class="absolute inset-0 w-full h-full object-cover" loading="eager">

            {{-- Overlay sombre pour lisibilité du texte --}}
            <div class="absolute inset-0 bg-linear-to-b from-gray-900/80 via-gray-900/60 to-gray-900/85"
                aria-hidden="true"></div>

            {{-- Blobs décoratifs (lueur orange) --}}
            <div class="absolute inset-0 overflow-hidden" aria-hidden="true">
                <div class="absolute -top-12 -left-12 w-48 h-48 sm:-top-24 sm:-left-24 sm:w-96 sm:h-96 rounded-full bg-[#F16A00]/20 blur-3xl"></div>
                <div class="absolute bottom-0 right-0 w-40 h-40 sm:w-80 sm:h-80 rounded-full bg-[#FF8A1F]/15 blur-3xl"></div>
                <div
                    class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-40 h-40 sm:w-64 sm:h-64 rounded-full bg-[#F16A00]/10 blur-3xl">
                </div>
            </div>

            {{-- Content --}}
            <div class="relative z-10 flex flex-col justify-center items-center w-full p-12 text-white">
                {{-- Logo + Nom --}}
                <div class="mb-10 text-center">
                    <div
                        class="w-16 h-16 bg-white rounded-2xl flex items-center justify-center shadow-2xl mx-auto mb-4">
                        <span class="text-2xl font-extrabold text-[#F16A00]">R</span>
                    </div>
                    <h1 class="text-4xl font-extrabold tracking-tight">ReziApp</h1>
                    <p class="text-sm text-gray-400 mt-1 font-medium tracking-wider uppercase">Location meublée en
                        Afrique de l'Ouest</p>
                </div>

                {{-- Headline --}}
                <p class="text-xl text-gray-300 text-center mb-12 max-w-sm leading-relaxed">
                    Trouvez votre <span class="text-[#FF8A1F] font-semibold">résidence meublée idéale</span> en
                    quelques clics
                </p>

                {{-- Features --}}
                <div class="space-y-3 w-full max-w-sm">
                    <div
                        class="flex items-center gap-4 bg-white/5 border border-white/10 rounded-2xl p-4 hover:bg-white/10 transition-colors">
                        <div class="w-10 h-10 bg-[#F16A00]/20 rounded-xl flex items-center justify-center shrink-0">
                            <svg class="w-5 h-5 text-[#FF8A1F]" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                        </div>
                        <div>
                            <div class="font-semibold text-white">Géolocalisation</div>
                            <div class="text-sm text-gray-400">Recherche par carte interactive</div>
                        </div>
                    </div>

                    <div
                        class="flex items-center gap-4 bg-white/5 border border-white/10 rounded-2xl p-4 hover:bg-white/10 transition-colors">
                        <div class="w-10 h-10 bg-emerald-500/20 rounded-xl flex items-center justify-center shrink-0">
                            <svg class="w-5 h-5 text-emerald-400" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                            </svg>
                        </div>
                        <div>
                            <div class="font-semibold text-white">Annonces vérifiées</div>
                            <div class="text-sm text-gray-400">Modérées par notre équipe</div>
                        </div>
                    </div>

                    <div
                        class="flex items-center gap-4 bg-white/5 border border-white/10 rounded-2xl p-4 hover:bg-white/10 transition-colors">
                        <div class="w-10 h-10 bg-blue-500/20 rounded-xl flex items-center justify-center shrink-0">
                            <svg class="w-5 h-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div>
                            <div class="font-semibold text-white">Disponibilité temps réel</div>
                            <div class="text-sm text-gray-400">Infos toujours à jour</div>
                        </div>
                    </div>
                </div>

                {{-- Stats --}}
                <div class="mt-12 flex items-center gap-6">
                    <div class="text-center">
                        <div class="text-2xl font-extrabold text-[#FF8A1F]">500+</div>
                        <div class="text-xs text-gray-500 font-medium uppercase tracking-wide mt-0.5">Résidences</div>
                    </div>
                    <div class="w-px h-10 bg-white/10"></div>
                    <div class="text-center">
                        <div class="text-2xl font-extrabold text-white">10k+</div>
                        <div class="text-xs text-gray-500 font-medium uppercase tracking-wide mt-0.5">Utilisateurs</div>
                    </div>
                    <div class="w-px h-10 bg-white/10"></div>
                    <div class="text-center">
                        <div class="text-2xl font-extrabold text-white">15</div>
                        <div class="text-xs text-gray-500 font-medium uppercase tracking-wide mt-0.5">Communes</div>
                    </div>
                </div>

                {{-- Trust badge --}}
                <div class="mt-10 flex items-center gap-2 bg-white/5 border border-white/10 rounded-full px-4 py-2">
                    <svg class="w-4 h-4 text-emerald-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                            d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                            clip-rule="evenodd" />
                    </svg>
                    <span class="text-xs text-gray-400 font-medium">Gratuit pour les locataires</span>
                </div>
            </div>
        </div>

        {{-- ══════════════════════════════════════
             RIGHT SIDE – LOGIN FORM
        ══════════════════════════════════════ --}}
        <div
            class="w-full lg:w-1/2 flex flex-col justify-center items-center p-6 sm:p-12 bg-white relative overflow-hidden">
            {{-- Fond subtil --}}
            <div class="absolute inset-0 bg-linear-to-b from-gray-50/80 to-white pointer-events-none"
                aria-hidden="true"></div>
            <div class="absolute top-0 right-0 w-64 h-64 bg-[#FFF4EB] rounded-full blur-3xl opacity-60 pointer-events-none"
                aria-hidden="true"></div>

            <div class="w-full max-w-md relative">
                {{-- Mobile branding --}}
                <div class="lg:hidden text-center mb-8">
                    <div
                        class="w-12 h-12 bg-gray-900 rounded-xl flex items-center justify-center mx-auto mb-3 shadow-lg">
                        <span class="text-lg font-extrabold text-[#FF8A1F]">R</span>
                    </div>
                    <p class="text-xs text-gray-400 font-medium uppercase tracking-widest">ReziApp · Location meublée</p>
                </div>

                {{-- Header --}}
                <div class="text-center mb-8">
                    <h2 class="text-2xl sm:text-3xl font-extrabold text-gray-900">Bon retour !</h2>
                    <p class="mt-2 text-gray-500">Connectez-vous à votre espace personnel</p>
                </div>

                {{-- Session Status --}}
                <x-auth-session-status class="mb-4" :status="session('status')" />

                {{-- Social Login --}}
                <div class="grid grid-cols-2 gap-3 mb-6">
                    <a href="{{ route('socialite.redirect', 'google') }}"
                        class="flex items-center justify-center gap-2.5 px-4 py-3 bg-white border border-gray-200 rounded-xl text-gray-700 text-sm font-medium hover:bg-gray-50 hover:border-gray-300 hover:shadow-sm transition-all active:scale-95">
                        <svg class="w-5 h-5 shrink-0" viewBox="0 0 24 24">
                            <path fill="#4285F4"
                                d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" />
                            <path fill="#34A853"
                                d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" />
                            <path fill="#FBBC05"
                                d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" />
                            <path fill="#EA4335"
                                d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" />
                        </svg>
                        Google
                    </a>

                    <a href="{{ route('socialite.redirect', 'facebook') }}"
                        class="flex items-center justify-center gap-2.5 px-4 py-3 bg-[#1877F2] rounded-xl text-white text-sm font-medium hover:bg-[#1565D8] hover:shadow-sm transition-all active:scale-95">
                        <svg class="w-5 h-5 shrink-0" fill="currentColor" viewBox="0 0 24 24">
                            <path
                                d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z" />
                        </svg>
                        Facebook
                    </a>
                </div>

                {{-- Divider --}}
                <div class="relative my-6">
                    <div class="absolute inset-0 flex items-center" aria-hidden="true">
                        <div class="w-full border-t border-gray-200"></div>
                    </div>
                    <div class="relative flex justify-center">
                        <span class="px-4 bg-white text-xs font-medium text-gray-400 uppercase tracking-wide">ou par
                            email</span>
                    </div>
                </div>

                {{-- Login Form --}}
                <form method="POST" action="{{ route('login') }}" class="space-y-5">
                    @csrf

                    {{-- Email --}}
                    <div>
                        <label for="email" class="block text-sm font-semibold text-gray-700 mb-2">Adresse
                            email</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207" />
                                </svg>
                            </div>
                            <input id="email" name="email" type="email" inputmode="email" value="{{ old('email') }}" required
                                autofocus autocomplete="username" placeholder="vous@exemple.com"
                                class="w-full pl-12 pr-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-base text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#F16A00]/40 focus:border-[#FF8A1F] focus:bg-white transition-all">
                        </div>
                        @error('email')
                            <p class="mt-2 text-sm text-red-600 flex items-center gap-1">
                                <svg class="w-4 h-4 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                                        clip-rule="evenodd" />
                                </svg>
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    {{-- Password --}}
                    <div>
                        <label for="password" class="block text-sm font-semibold text-gray-700 mb-2">Mot de
                            passe</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                </svg>
                            </div>
                            <input id="password" name="password" type="password" :type="showPassword ? 'text' : 'password'" required
                                autocomplete="current-password" placeholder="••••••••"
                                class="w-full pl-12 pr-12 py-3 bg-gray-50 border border-gray-200 rounded-xl text-base text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#F16A00]/40 focus:border-[#FF8A1F] focus:bg-white transition-all">
                            <button type="button" @click="showPassword = !showPassword"
                                class="absolute inset-y-0 right-0 pr-4 flex items-center text-gray-400 hover:text-gray-600 transition-colors">
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
                            <p class="mt-2 text-sm text-red-600 flex items-center gap-1">
                                <svg class="w-4 h-4 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                                        clip-rule="evenodd" />
                                </svg>
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    {{-- Remember & Forgot --}}
                    <div class="flex items-center justify-between">
                        <label for="remember_me" class="flex items-center cursor-pointer group">
                            <input id="remember_me" type="checkbox" name="remember"
                                class="w-4 h-4 rounded border-gray-300 text-[#F16A00] focus:ring-[#F16A00] cursor-pointer">
                            <span class="ml-2 text-sm text-gray-500 group-hover:text-gray-700 transition-colors">Se
                                souvenir de moi</span>
                        </label>

                        @if (Route::has('password.request'))
                            <a href="{{ route('password.request') }}"
                                class="text-sm font-medium text-[#F16A00] hover:text-[#CC5A00] transition-colors">
                                Mot de passe oublié ?
                            </a>
                        @endif
                    </div>

                    {{-- Submit Button --}}
                    <button type="submit"
                        class="w-full bg-[#F16A00] hover:bg-[#CC5A00] text-white font-semibold py-3.5 px-4 rounded-xl transition-all duration-200 shadow-lg hover:shadow-xl active:scale-[0.98] flex items-center justify-center gap-2">
                        <span>Se connecter</span>
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M14 5l7 7m0 0l-7 7m7-7H3" />
                        </svg>
                    </button>
                </form>

                {{-- Register Link --}}
                <p class="mt-8 text-center text-gray-500">
                    Pas encore de compte ?
                    <a href="{{ route('register') }}"
                        class="font-semibold text-[#F16A00] hover:text-[#CC5A00] transition-colors">
                        Créer un compte gratuit
                    </a>
                </p>

                {{-- Owner CTA --}}
                <div class="mt-6 p-4 bg-gray-900 rounded-2xl text-white">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-[#F16A00]/20 rounded-xl flex items-center justify-center shrink-0">
                            <svg class="w-5 h-5 text-[#FF8A1F]" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                            </svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-300">Vous êtes propriétaire ?</p>
                            <a href="{{ route('register') }}"
                                class="text-sm text-[#FF8A1F] hover:text-[#FFB46F] font-semibold transition-colors">
                                Publiez votre résidence →
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
