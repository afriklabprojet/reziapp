                {{-- 8. SECTION PROPRIÉTAIRES — Devenez propriétaire Rezi App --}}
                <section class="py-8 sm:py-12 bg-linear-to-br from-gray-900 via-gray-900 to-[#3d0014] text-white overflow-hidden relative">
                    {{-- Déco background --}}
                    <div class="absolute inset-0 opacity-10 pointer-events-none"
                        style="background-image: radial-gradient(circle at 15% 85%, #F97316 0%, transparent 45%), radial-gradient(circle at 85% 15%, #06B6D4 0%, transparent 45%);">
                    </div>
                    <div class="relative max-w-5xl mx-auto px-4 sm:px-6">

                        {{-- Étapes publication --}}
                        <div class="bg-white/5 border border-white/10 rounded-2xl p-5 mb-6 reveal-hidden"
                            x-intersect.once="$el.classList.add('reveal-visible')">
                            <h3 class="text-center font-bold text-white text-base mb-5">Publiez en 3 étapes simples</h3>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 relative">
                                {{-- Ligne de connexion desktop --}}
                                <div class="hidden sm:block absolute top-5 left-1/6 right-1/6 h-px bg-white/20 z-0"></div>

                                @foreach([
                                    ['num' => '1', 'title' => 'Créez votre compte', 'desc' => 'En moins de 2 minutes avec votre email', 'color' => 'bg-[#F16A00]'],
                                    ['num' => '2', 'title' => 'Décrivez votre résidence', 'desc' => 'Photos, prix, équipements, localisation', 'color' => 'bg-[#F16A00]'],
                                    ['num' => '3', 'title' => 'Publiez et recevez', 'desc' => 'Visibilité immédiate, réservation instantanée', 'color' => 'bg-green-500'],
                                ] as $step)
                                <div class="relative z-10 flex flex-col items-center text-center">
                                    <div class="w-10 h-10 {{ $step['color'] }} rounded-full flex items-center justify-center text-white font-extrabold text-sm shadow-lg mb-2">
                                        {{ $step['num'] }}
                                    </div>
                                    <h4 class="font-semibold text-white text-sm mb-0.5">{{ $step['title'] }}</h4>
                                    <p class="text-gray-400 text-xs">{{ $step['desc'] }}</p>
                                </div>
                                @endforeach
                            </div>
                        </div>

                        {{-- Stats + CTA inline --}}
                        <div class="flex flex-col sm:flex-row items-center justify-between gap-4 reveal-hidden"
                            x-intersect.once="$el.classList.add('reveal-visible')">
                            <div class="flex gap-6 sm:gap-10">
                                @foreach([
                                    ['val' => $stats['owners'] ?? 0, 'label' => 'Propriétaires', 'suffix' => '+'],
                                    ['val' => $stats['residences'] ?? 0, 'label' => 'Résidences', 'suffix' => ''],
                                    ['val' => 48, 'label' => 'h délai moyen', 'suffix' => 'h'],
                                ] as $stat)
                                <div class="text-center">
                                    <div class="text-2xl font-extrabold text-[#FF8A1F]">
                                        {{ number_format($stat['val']) }}{{ $stat['suffix'] }}
                                    </div>
                                    <div class="text-xs text-gray-400 mt-0.5">{{ $stat['label'] }}</div>
                                </div>
                                @endforeach
                            </div>
                            <a href="{{ route('owner.residences.create') }}"
                                class="btn-premium px-6 py-3 text-sm gap-2 shrink-0">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                                Publier ma résidence gratuitement
                            </a>
                        </div>

                    </div>
                </section>

