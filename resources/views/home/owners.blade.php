                {{-- 8. SECTION PROPRIÉTAIRES — Devenez propriétaire REZI --}}
                <section class="py-16 sm:py-24 bg-linear-to-br from-gray-900 via-gray-900 to-orange-950 text-white overflow-hidden relative">
                    {{-- Déco background --}}
                    <div class="absolute inset-0 opacity-10 pointer-events-none"
                        style="background-image: radial-gradient(circle at 15% 85%, #F97316 0%, transparent 45%), radial-gradient(circle at 85% 15%, #06B6D4 0%, transparent 45%);">
                    </div>
                    <div class="relative max-w-7xl mx-auto px-4 sm:px-6">

                        {{-- Étapes publication --}}
                        <div class="bg-white/5 border border-white/10 rounded-3xl p-8 mb-10 reveal-hidden"
                            x-intersect.once="$el.classList.add('reveal-visible')">
                            <h3 class="text-center font-bold text-white text-xl mb-8">Publiez en 3 étapes simples</h3>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 relative">
                                {{-- Ligne de connexion desktop --}}
                                <div class="hidden sm:block absolute top-6 left-1/6 right-1/6 h-px bg-white/20 z-0"></div>

                                @foreach([
                                    ['num' => '1', 'title' => 'Créez votre compte', 'desc' => 'En moins de 2 minutes avec votre email', 'color' => 'bg-orange-500'],
                                    ['num' => '2', 'title' => 'Décrivez votre résidence', 'desc' => 'Photos, prix, équipements, localisation', 'color' => 'bg-orange-500'],
                                    ['num' => '3', 'title' => 'Publiez et recevez', 'desc' => 'Visibilité immédiate, réservation instantanée', 'color' => 'bg-green-500'],
                                ] as $step)
                                <div class="relative z-10 flex flex-col items-center text-center">
                                    <div class="w-12 h-12 {{ $step['color'] }} rounded-full flex items-center justify-center text-white font-extrabold text-lg shadow-xl mb-4">
                                        {{ $step['num'] }}
                                    </div>
                                    <h4 class="font-bold text-white mb-1">{{ $step['title'] }}</h4>
                                    <p class="text-gray-400 text-sm">{{ $step['desc'] }}</p>
                                </div>
                                @endforeach
                            </div>
                        </div>

                        {{-- Stats propriétaires --}}
                        <div class="grid grid-cols-3 gap-4 mb-10 reveal-hidden"
                            x-intersect.once="$el.classList.add('reveal-visible')">
                            @foreach([
                                ['val' => $stats['owners'] ?? 0, 'label' => 'Propriétaires actifs', 'suffix' => '+'],
                                ['val' => $stats['residences'] ?? 0, 'label' => 'Résidences publiées', 'suffix' => ''],
                                ['val' => 48, 'label' => 'Heures délai contact moyen', 'suffix' => 'h'],
                            ] as $stat)
                            <div class="text-center">
                                <div class="text-3xl sm:text-4xl font-extrabold text-orange-400">
                                    {{ number_format($stat['val']) }}{{ $stat['suffix'] }}
                                </div>
                                <div class="text-xs sm:text-sm text-gray-400 mt-1">{{ $stat['label'] }}</div>
                            </div>
                            @endforeach
                        </div>

                        {{-- CTA propriétaire --}}
                        <div class="flex flex-col sm:flex-row gap-4 justify-center reveal-hidden"
                            x-intersect.once="$el.classList.add('reveal-visible')">
                            <a href="{{ route('owner.residences.create') }}"
                                class="inline-flex items-center justify-center gap-2 bg-orange-500 hover:bg-orange-400 text-white px-8 py-4 rounded-2xl font-bold text-base shadow-xl shadow-orange-500/30 transition-all hover:scale-105">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                                Publier ma résidence gratuitement
                            </a>
                        </div>

                    </div>
                </section>

