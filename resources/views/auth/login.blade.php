<x-app-layout>
    @section('title', 'Connexion - Rezi App')

    <div class="min-h-[calc(100vh-56px)] md:min-h-[calc(100vh-64px)] flex" x-data="{ showPassword: false }">
        {{-- ══════════════════════════════════════
             LEFT SIDE – BRANDING (Desktop)
        ══════════════════════════════════════ --}}
        <div class="hidden lg:flex lg:w-1/2 relative overflow-hidden">
            {{-- Image d'arrière-plan : vraie photo de résidence si dispo --}}
            @php
                $bgPhoto = $featuredResidence?->photos->first()?->url ?? asset('images/login-bg.jpg');
            @endphp
            <img src="{{ $bgPhoto }}" alt="" aria-hidden="true"
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
                        class="w-20 h-20 bg-white rounded-2xl flex items-center justify-center shadow-2xl mx-auto mb-4 p-2">
                        <img src="{{ asset('images/logo-rezi.png') }}?v=2" alt="Rezi App Logo" class="h-full w-auto object-contain">
                    </div>
                    <h1 class="text-4xl font-extrabold tracking-tight">Rezi App</h1>
                    <p class="text-sm text-gray-400 mt-1 font-medium tracking-wider uppercase">Location meublée</p>
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

                {{-- Résidence en vedette --}}
                @if($featuredResidence)
                <div class="mt-10 w-full max-w-sm bg-white/10 border border-white/20 rounded-2xl p-4 backdrop-blur-sm">
                    <div class="flex items-center gap-3">
                        @if($featuredResidence->photos->first())
                        <img src="{{ $featuredResidence->photos->first()->url }}"
                             alt="{{ $featuredResidence->title }}"
                             class="w-14 h-14 rounded-xl object-cover shrink-0">
                        @endif
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-semibold text-white truncate">{{ $featuredResidence->title }}</p>
                            <p class="text-xs text-gray-400 truncate">{{ $featuredResidence->commune ?? $featuredResidence->city }}</p>
                            <div class="flex items-center gap-1 mt-0.5">
                                <svg class="w-3.5 h-3.5 text-[#FF8A1F]" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                </svg>
                                <span class="text-xs text-[#FF8A1F] font-semibold">{{ number_format($featuredResidence->reviews_avg_rating ?? $featuredResidence->average_rating, 1) }}</span>
                                <span class="text-xs text-gray-500">· {{ number_format($featuredResidence->price_per_month, 0, ',', ' ') }} FCFA/mois</span>
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                {{-- Stats --}}
                <div class="mt-6 flex items-center gap-6">
                    <div class="text-center">
                        <div class="text-2xl font-extrabold text-[#FF8A1F]">{{ $stats['residences'] }}</div>
                        <div class="text-xs text-gray-500 font-medium uppercase tracking-wide mt-0.5">Résidences</div>
                    </div>
                    <div class="w-px h-10 bg-white/10"></div>
                    <div class="text-center">
                        <div class="text-2xl font-extrabold text-white">{{ $stats['users'] }}</div>
                        <div class="text-xs text-gray-500 font-medium uppercase tracking-wide mt-0.5">Utilisateurs</div>
                    </div>
                    <div class="w-px h-10 bg-white/10"></div>
                    <div class="text-center">
                        <div class="text-2xl font-extrabold text-white">{{ $stats['cities'] }}</div>
                        <div class="text-xs text-gray-500 font-medium uppercase tracking-wide mt-0.5">Villes</div>
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
                        class="w-12 h-12 bg-white rounded-xl flex items-center justify-center mx-auto mb-3 shadow-lg p-1.5">
                        <img src="{{ asset('images/logo-rezi.png') }}?v=2" alt="Rezi App Logo" class="h-full w-auto object-contain">
                    </div>
                    <p class="text-xs text-gray-400 font-medium uppercase tracking-widest">Rezi App · Location meublée</p>
                </div>

                {{-- Header --}}
                <div class="text-center mb-8">
                    <h2 class="text-2xl sm:text-3xl font-extrabold text-gray-900">Bon retour !</h2>
                    <p class="mt-2 text-gray-500">Connectez-vous à votre espace personnel</p>
                </div>

                {{-- Session Status --}}
                <x-auth-session-status class="mb-4" :status="session('status')" />

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
