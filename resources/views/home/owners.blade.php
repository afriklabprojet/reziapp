                {{-- 8. SECTION PROPRIÉTAIRES — Devenez propriétaire REZI --}}
                <section class="py-16 sm:py-24 bg-linear-to-br from-gray-900 via-gray-900 to-orange-950 text-white overflow-hidden relative">
                    {{-- Déco background --}}
                    <div class="absolute inset-0 opacity-10 pointer-events-none"
                        style="background-image: radial-gradient(circle at 15% 85%, #F97316 0%, transparent 45%), radial-gradient(circle at 85% 15%, #06B6D4 0%, transparent 45%);">
                    </div>
                    <div class="relative max-w-7xl mx-auto px-4 sm:px-6">

                        {{-- Header --}}
                        <div class="text-center mb-14 reveal-hidden" x-intersect.once="$el.classList.add('reveal-visible')">
                            <div class="inline-flex items-center gap-2 bg-orange-500/20 text-orange-300 border border-orange-500/30 px-4 py-1.5 rounded-full text-sm font-semibold mb-5">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                                </svg>
                                Propriétaires REZI
                            </div>
                            <h2 class="font-display text-3xl sm:text-5xl font-extrabold text-white mb-4">
                                Louez plus vite,<br>
                                <span class="text-orange-400">gardez 100% de vos revenus</span>
                            </h2>
                            <p class="text-gray-300 text-lg max-w-2xl mx-auto">
                                Publiez votre résidence en 5 minutes, recevez des demandes directement sur WhatsApp,
                                sans intermédiaire ni commission.
                            </p>
                        </div>

                        {{-- Avantages en 3 colonnes --}}
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-14">
                            @foreach([
                                [
                                    'icon' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
                                    'color' => 'from-green-500 to-emerald-600',
                                    'shadow' => 'shadow-green-500/30',
                                    'title' => '0% de commission',
                                    'desc' => 'Vous gardez 100% de vos loyers. REZI ne prend aucune commission sur vos revenus locatifs.',
                                    'badge' => '0 FCFA',
                                ],
                                [
                                    'icon' => 'M13 10V3L4 14h7v7l9-11h-7z',
                                    'color' => 'from-orange-500 to-amber-500',
                                    'shadow' => 'shadow-orange-500/30',
                                    'title' => 'Publication en 5 minutes',
                                    'desc' => 'Ajoutez vos photos, décrivez votre résidence et publiez. Visible immédiatement sur la carte.',
                                    'badge' => '< 5 min',
                                ],
                                [
                                    'icon' => 'M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z',
                                    'color' => 'from-blue-500 to-cyan-500',
                                    'shadow' => 'shadow-blue-500/30',
                                    'title' => 'Contact direct WhatsApp',
                                    'desc' => 'Les locataires vous contactent directement. Pas d\'intermédiaire, pas d\'appel manqué.',
                                    'badge' => 'Direct',
                                ],
                            ] as $avantage)
                            <div class="bg-white/5 border border-white/10 rounded-2xl p-6 hover:bg-white/10 transition-colors reveal-hidden reveal-delay-2"
                                x-intersect.once="$el.classList.add('reveal-visible')">
                                <div class="w-12 h-12 bg-linear-to-br {{ $avantage['color'] }} rounded-xl flex items-center justify-center mb-4 shadow-lg {{ $avantage['shadow'] }}">
                                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $avantage['icon'] }}" />
                                    </svg>
                                </div>
                                <div class="inline-block bg-white/10 text-white text-xs font-bold px-2.5 py-1 rounded-full mb-3">{{ $avantage['badge'] }}</div>
                                <h3 class="font-bold text-white text-lg mb-2">{{ $avantage['title'] }}</h3>
                                <p class="text-gray-400 text-sm leading-relaxed">{{ $avantage['desc'] }}</p>
                            </div>
                            @endforeach
                        </div>

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
                                    ['num' => '3', 'title' => 'Publiez et recevez', 'desc' => 'Visibilité immédiate, contacts directs WhatsApp', 'color' => 'bg-green-500'],
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
                            <a href="{{ route('pages.tarifs') }}"
                                class="inline-flex items-center justify-center gap-2 bg-white/10 hover:bg-white/20 text-white border border-white/20 px-8 py-4 rounded-2xl font-bold text-base transition-all">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                Voir les tarifs
                            </a>
                        </div>

                    </div>
                </section>

