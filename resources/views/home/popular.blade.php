                {{-- 5. SECTION LES PLUS POPULAIRES --}}
                <section class="py-10 sm:py-16 bg-sand-100">
                    <div class="max-w-7xl mx-auto px-4 sm:px-6">
                        {{-- Section Header --}}
                        <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4 mb-10 reveal-hidden"
                            x-intersect.once="$el.classList.add('reveal-visible')">
                            <div>
                                <div
                                    class="inline-flex items-center gap-2 bg-rose-100 text-rose-600 px-3 py-1.5 rounded-full text-xs font-bold mb-3">
                                    <svg aria-hidden="true" class="w-4 h-4" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M17.657 18.657A8 8 0 016.343 7.343S7 9 9 10c0-2 .5-5 2.986-7C14 5 16.09 5.777 17.656 7.343A7.975 7.975 0 0120 13a7.975 7.975 0 01-2.343 5.657z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9.879 16.121A3 3 0 1012.015 11L11 14H9c0 .768.293 1.536.879 2.121z" />
                                    </svg>
                                    Tendances
                                </div>
                                <h2 class="font-display text-2xl sm:text-3xl font-bold text-gray-900">Les plus populaires 🔥</h2>
                                <p class="mt-2 text-sm text-gray-500">Les résidences les plus consultées cette semaine</p>
                            </div>
                            <a href="{{ route('residences.index') }}?sort=popular"
                                class="inline-flex items-center gap-2 text-sm font-semibold text-orange-500 hover:text-orange-600 transition group">
                                Voir le classement complet
                                <svg aria-hidden="true" class="w-4 h-4 group-hover:translate-x-1 transition-transform"
                                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M17 8l4 4m0 0l-4 4m4-4H3" />
                                </svg>
                            </a>
                        </div>

                        {{-- Popular Grid - Dynamic from Database --}}
                        <div class="grid grid-cols-2 md:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-6">

                            @php
                                $rankColors = [
                                    0 => 'from-rose-500 to-pink-600 ring-2 ring-rose-200',
                                    1 => 'from-gray-400 to-gray-500',
                                    2 => 'from-amber-600 to-yellow-500',
                                    3 => 'bg-gray-200',
                                ];
                            @endphp

                            @forelse($popularZones as $index => $zone)
                                <a href="{{ route('residences.index', ['commune' => $zone['name']]) }}"
                                    x-intersect.once="$el.classList.add('reveal-visible')"
                                    class="group relative reveal-card card-lift-warm {{ $staggerClasses[$index] ?? '' }} bg-white rounded-2xl shadow-md overflow-hidden {{ $index === 0 ? 'ring-2 ring-rose-200' : '' }}">
                                    {{-- Rank Badge --}}
                                    <div
                                        class="absolute top-4 left-4 z-10 w-10 h-10 {{ $index < 3 ? 'bg-linear-to-br ' . ($rankColors[$index] ?? $rankColors[3]) : $rankColors[3] }} rounded-full flex items-center justify-center shadow-lg">
                                        <span
                                            class="{{ $index < 3 ? 'text-white' : 'text-gray-600' }} font-bold text-lg">#{{ $index + 1 }}</span>
                                    </div>

                                    {{-- Count Badge --}}
                                    <div
                                        class="absolute top-4 right-4 z-10 bg-white/90 backdrop-blur px-2 py-1 rounded-full text-xs font-bold {{ $index === 0 ? 'text-rose-600' : 'text-gray-600' }} flex items-center gap-1 shadow">
                                        @if ($index === 0)
                                            <svg aria-hidden="true" class="w-3 h-3" fill="currentColor" viewBox="0 0 24 24">
                                                <path
                                                    d="M17.657 18.657A8 8 0 016.343 7.343S7 9 9 10c0-2 .5-5 2.986-7C14 5 16.09 5.777 17.656 7.343A7.975 7.975 0 0120 13a7.975 7.975 0 01-2.343 5.657z" />
                                            </svg>
                                        @else
                                            <svg aria-hidden="true" class="w-3 h-3" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                            </svg>
                                        @endif
                                        {{ $zone['count'] }} résidences
                                    </div>

                                    {{-- Image --}}
                                    <div class="relative h-44 overflow-hidden">
                                        <img loading="lazy" src="{{ $zone['image'] }}" alt="{{ $zone['name'] }}"
                                            class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-700">
                                    </div>

                                    {{-- Content --}}
                                    <div class="p-4">
                                        <div class="flex items-start justify-between gap-2">
                                            <div class="min-w-0">
                                                <h3 class="font-bold text-gray-900 truncate">{{ $zone['name'] }}</h3>
                                                <p class="text-xs text-gray-500 flex items-center gap-1 mt-1">
                                                    <svg aria-hidden="true" class="w-3 h-3 shrink-0" fill="none"
                                                        stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                            d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                                    </svg>
                                                    {{ $zone['city'] ?? '' }}
                                                </p>
                                            </div>
                                            <div class="text-right shrink-0">
                                                <div class="text-sm font-bold text-gray-900">
                                                    {{ number_format($zone['min_price'] / 1000) }}k</div>
                                                <div class="text-[10px] text-gray-400">min/jour</div>
                                            </div>
                                        </div>
                                        <div class="mt-3 flex items-center justify-between">
                                            <span class="text-xs text-orange-500 font-semibold">Voir les résidences →</span>
                                        </div>
                                    </div>
                                </a>
                            @empty
                                <div class="col-span-4 text-center py-8">
                                    <p class="text-gray-500">Aucune zone populaire pour le moment</p>
                                </div>
                            @endforelse

                        </div>

                        {{-- Stats Bar — Compteurs animés --}}
                        <div class="mt-8 sm:mt-10 bg-white rounded-2xl p-4 sm:p-6 border border-gray-100 shadow-sm"
                            x-data="{
                                r: 0, o: 0, c: 0, ct: 0, animated: false,
                                startCounting() {
                                    if (this.animated) return;
                                    this.animated = true;
                                    const items = [
                                        ['r', {{ $stats['residences'] ?? 0 }}],
                                        ['o', {{ $stats['owners'] ?? 0 }}],
                                        ['c', {{ $stats['communes'] ?? 0 }}],
                                        ['ct', {{ $stats['contacts'] ?? 0 }}]
                                    ];
                                    items.forEach(([k, t]) => {
                                        let cur = 0;
                                        const inc = Math.max(1, Math.ceil(t / 55));
                                        const id = setInterval(() => {
                                            cur = Math.min(cur + inc, t);
                                            this[k] = cur;
                                            if (cur >= t) clearInterval(id);
                                        }, 25);
                                    });
                                }
                            }"
                            x-intersect.once="startCounting()">
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 sm:gap-6 text-center">
                                <div>
                                    <div class="text-2xl sm:text-3xl font-bold text-gray-900" x-text="r.toLocaleString('fr-FR')">
                                        {{ number_format($stats['residences'] ?? 0) }}</div>
                                    <div class="text-xs text-gray-500 mt-1">Résidences disponibles</div>
                                </div>
                                <div>
                                    <div class="text-2xl sm:text-3xl font-bold text-orange-500" x-text="o.toLocaleString('fr-FR')">{{ $stats['owners'] ?? 0 }}
                                    </div>
                                    <div class="text-xs text-gray-500 mt-1">Propriétaires actifs</div>
                                </div>
                                <div>
                                    <div class="text-2xl sm:text-3xl font-bold text-gray-900" x-text="c.toLocaleString('fr-FR')">{{ $stats['communes'] ?? 0 }}
                                    </div>
                                    <div class="text-xs text-gray-500 mt-1">Zones couvertes</div>
                                </div>
                                <div>
                                    <div class="text-2xl sm:text-3xl font-bold text-amber-500" x-text="ct.toLocaleString('fr-FR')">
                                        {{ number_format($stats['contacts'] ?? 0) }}</div>
                                    <div class="text-xs text-gray-500 mt-1">Demandes de contact</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

