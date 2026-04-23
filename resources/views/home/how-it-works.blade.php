                {{-- 6. COMMENT ÇA MARCHE —— 3 étapes premium --}}
                <section class="py-16 sm:py-24 bg-white overflow-hidden">
                    <div class="max-w-7xl mx-auto px-4 sm:px-6">
                        {{-- Header --}}
                        <div class="text-center mb-14 sm:mb-20 reveal-hidden"
                            x-intersect.once="$el.classList.add('reveal-visible')">
                            <div class="inline-flex items-center gap-2 bg-orange-100 text-orange-600 px-4 py-2 rounded-full text-sm font-bold mb-5">
                                <svg aria-hidden="true" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                </svg>
                                Simple &amp; Rapide
                            </div>
                            <h2 class="font-display text-3xl sm:text-4xl font-extrabold text-gray-900">
                                Trouver votre logement en<br class="hidden sm:block">
                                <span class="text-gradient-primary">3 étapes simples</span>
                            </h2>
                            <p class="mt-4 text-gray-500 max-w-xl mx-auto text-base leading-relaxed">
                                De la recherche à l'emménagement, REZI vous accompagne à chaque étape
                            </p>
                        </div>

                        {{-- 3 Steps --}}
                        <div class="relative grid grid-cols-1 md:grid-cols-3 gap-8 sm:gap-12">
                            {{-- Ligne connectrice (desktop) --}}
                            <div class="hidden md:block absolute left-[calc(16.67%+3rem)] right-[calc(16.67%+3rem)] h-px bg-linear-to-r from-orange-200 via-orange-400 to-orange-200 z-0" style="top: 2.5rem;"></div>

                            {{-- Étape 1 — Recherchez --}}
                            <div class="relative flex flex-col items-center text-center reveal-hidden reveal-delay-1"
                                x-intersect.once="$el.classList.add('reveal-visible')">
                                <div class="relative z-10 mb-6">
                                    <div class="w-20 h-20 bg-linear-to-br from-orange-400 to-orange-600 rounded-2xl flex items-center justify-center shadow-xl shadow-orange-500/30 transition-transform duration-300 hover:scale-105">
                                        <svg aria-hidden="true" class="w-9 h-9 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                        </svg>
                                    </div>
                                    <div class="absolute -top-2 -right-2 w-8 h-8 bg-white rounded-full flex items-center justify-center shadow-md border border-orange-100">
                                        <span class="text-sm font-black text-orange-500">1</span>
                                    </div>
                                </div>
                                <h3 class="text-xl font-bold text-gray-900 mb-3">Recherchez</h3>
                                <p class="text-gray-500 text-sm leading-relaxed max-w-xs">
                                    Filtrez par quartier, budget et type de logement. Explorez la carte interactive pour trouver les disponibilités près de vous.
                                </p>
                            </div>

                            {{-- Étape 2 — Contactez --}}
                            <div class="relative flex flex-col items-center text-center reveal-hidden reveal-delay-3"
                                x-intersect.once="$el.classList.add('reveal-visible')">
                                <div class="relative z-10 mb-6">
                                    <div class="w-20 h-20 bg-linear-to-br from-blue-400 to-blue-600 rounded-2xl flex items-center justify-center shadow-xl shadow-blue-500/30 transition-transform duration-300 hover:scale-105">
                                        <svg aria-hidden="true" class="w-9 h-9 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                        </svg>
                                    </div>
                                    <div class="absolute -top-2 -right-2 w-8 h-8 bg-white rounded-full flex items-center justify-center shadow-md border border-blue-100">
                                        <span class="text-sm font-black text-blue-500">2</span>
                                    </div>
                                </div>
                                <h3 class="text-xl font-bold text-gray-900 mb-3">Contactez</h3>
                                <p class="text-gray-500 text-sm leading-relaxed max-w-xs">
                                    Envoyez votre demande directement au propriétaire, sans commission ni intermédiaire. Réponse sous 24h garantie.
                                </p>
                            </div>

                            {{-- Étape 3 — Emménagez --}}
                            <div class="relative flex flex-col items-center text-center reveal-hidden reveal-delay-5"
                                x-intersect.once="$el.classList.add('reveal-visible')">
                                <div class="relative z-10 mb-6">
                                    <div class="w-20 h-20 bg-linear-to-br from-emerald-400 to-emerald-600 rounded-2xl flex items-center justify-center shadow-xl shadow-emerald-500/30 transition-transform duration-300 hover:scale-105">
                                        <svg aria-hidden="true" class="w-9 h-9 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                                        </svg>
                                    </div>
                                    <div class="absolute -top-2 -right-2 w-8 h-8 bg-white rounded-full flex items-center justify-center shadow-md border border-emerald-100">
                                        <span class="text-sm font-black text-emerald-500">3</span>
                                    </div>
                                </div>
                                <h3 class="text-xl font-bold text-gray-900 mb-3">Emménagez</h3>
                                <p class="text-gray-500 text-sm leading-relaxed max-w-xs">
                                    Signez votre contrat, récupérez vos clés et installez-vous dans votre nouveau logement à Abidjan.
                                </p>
                            </div>
                        </div>

                        {{-- CTA Button --}}
                        <div class="mt-14 text-center reveal-hidden reveal-delay-7"
                            x-intersect.once="$el.classList.add('reveal-visible')">
                            <a href="{{ route('residences.index') }}"
                                class="inline-flex items-center gap-3 bg-linear-to-r from-orange-500 to-orange-600 hover:from-orange-600 hover:to-orange-700 text-white px-8 py-4 rounded-2xl font-bold text-base shadow-xl shadow-orange-500/30 transition-all duration-200 hover:scale-105">
                                <svg aria-hidden="true" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                                Commencer ma recherche
                            </a>
                            <p class="mt-3 text-sm text-gray-400">100% gratuit &middot; Aucune inscription requise</p>
                        </div>
                    </div>
                </section>

