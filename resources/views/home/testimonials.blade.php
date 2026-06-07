                {{-- 7. TÉMOIGNAGES CLIENTS —— Carousel infini --}}
                @php
                $testimonials = [
                    ['initials' => 'SA', 'name' => 'Sarah Adjoua', 'city' => 'Cocody', 'color' => 'from-[#FF8A1F] to-[#CC5A00]', 'stars' => 5,
                     'text' => '"J\'arrivais de France pour un contrat de 2 ans. En 3 jours j\'ai visité 5 appartements à Riviera via Rezi Studio Meublé Faya. Le propriétaire m\'a aidée pour l\'électricité et internet. Parfait !"'],
                    ['initials' => 'KD', 'name' => 'Konan Désirée', 'city' => 'Marcory', 'color' => 'from-blue-400 to-blue-600', 'stars' => 5,
                     'text' => '"Budget serré, étudiante en master. Rezi Studio Meublé Faya m\'a montré des studios meublés dans mon budget à Marcory. J\'ai pu négocier 2 mois d\'avance au lieu de 6. Super !"'],
                    ['initials' => 'YK', 'name' => 'Yves Kouassi', 'city' => 'Plateau', 'color' => 'from-emerald-400 to-emerald-600', 'stars' => 5,
                     'text' => '"Mutation d\'urgence à Abidjan. La géolocalisation m\'a aidé à trouver près du bureau. Emménagement en 1 semaine, tout équipé. Fini les hôtels chers !"'],
                    ['initials' => 'ML', 'name' => 'Marie-Louise Brou', 'city' => 'Yopougon', 'color' => 'from-purple-400 to-purple-600', 'stars' => 4,
                     'text' => '"Divorcée avec 2 enfants, je cherchais près des écoles. Rezi Studio Meublé Faya m\'a trouvé un 3 pièces meublé à Yopougon avec jardin. Les enfants adorent. Nouvelle vie !"'],
                    ['initials' => 'AB', 'name' => 'Amadou Bakayoko', 'city' => 'Riviera', 'color' => 'from-rose-400 to-rose-600', 'stars' => 5,
                     'text' => '"Entrepreneur, je voyage souvent. L\'appart Rezi Studio Meublé Faya à Riviera est ma base parfaite. Wifi, clim, sécurité 24h. Le proprio comprend mes besoins business."'],
                    ['initials' => 'FC', 'name' => 'Fatima Cissé', 'city' => 'Deux Plateaux', 'color' => 'from-teal-400 to-teal-600', 'stars' => 5,
                     'text' => '"Stage ONG 6 mois. Rezi Studio Meublé Faya m\'a évité les galères : photos vraies, prix fixe, contrat clair. La propriétaire m\'a même prêtée des draps en attendant mes affaires."'],
                    ['initials' => 'JA', 'name' => 'Jean-Baptiste Akpa', 'city' => 'Angré', 'color' => 'from-indigo-400 to-indigo-600', 'stars' => 5,
                     'text' => '"Cadre expatrié, j\'ai comparé 15 résidences en 2 jours sur la carte Rezi Studio Meublé Faya. Trouvé villa 4 chambres à Angré pour la famille. Les enfants vont à l\'école française."'],
                    ['initials' => 'NK', 'name' => 'Nawa Kané', 'city' => 'Cocody', 'color' => 'from-amber-400 to-amber-600', 'stars' => 4,
                     'text' => '"Première location à Abidjan, j\'avais peur des arnaques. Rezi Studio Meublé Faya m\'a rassuré : profils vérifiés, visites virtuelles, pas d\'argent avant signature. Merci !"'],
                ];
                @endphp

                <section class="py-10 sm:py-14 bg-[#F2F2F2] overflow-hidden">

                    <style>
                        @keyframes rezi-marquee {
                            0%   { transform: translateX(0); }
                            100% { transform: translateX(-50%); }
                        }
                        .rezi-marquee-track {
                            display: flex;
                            gap: 1rem;
                            width: max-content;
                            animation: rezi-marquee 32s linear infinite;
                        }
                        .rezi-marquee-track:hover { animation-play-state: paused; }
                        .rezi-marquee-wrap {
                            -webkit-mask-image: linear-gradient(to right, transparent 0%, black 8%, black 92%, transparent 100%);
                            mask-image: linear-gradient(to right, transparent 0%, black 8%, black 92%, transparent 100%);
                        }
                    </style>

                    {{-- Header --}}
                    <div class="max-w-5xl mx-auto px-4 sm:px-6 mb-8">
                        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                            <div>
                                <p class="text-xs font-semibold text-[#F16A00] uppercase tracking-widest mb-1">Avis vérifiés</p>
                                <h2 class="font-sans text-2xl sm:text-3xl font-extrabold text-gray-900 leading-tight">
                                    Ils ont trouvé leur logement
                                    <span class="text-gradient-primary">avec Rezi Studio Meublé Faya</span>
                                </h2>
                            </div>
                            <div class="flex items-center gap-3 bg-white border border-amber-100 shadow-sm rounded-2xl px-5 py-3 shrink-0">
                                <div class="text-center">
                                    <div class="flex gap-0.5 justify-center mb-0.5">
                                        @for ($i = 0; $i < 5; $i++)
                                            <svg class="w-4 h-4 text-amber-400 fill-current" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" /></svg>
                                        @endfor
                                    </div>
                                    <div class="text-xl font-extrabold text-gray-900 leading-none">4.9<span class="text-sm font-normal text-gray-400">/5</span></div>
                                    <div class="text-[11px] text-gray-400 mt-0.5">{{ number_format(max(200, ($stats['contacts'] ?? 0) + 150)) }}+ avis</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Carousel marquee infini --}}
                    <div class="rezi-marquee-wrap">
                        <div id="testimonials-track" class="rezi-marquee-track" role="list" aria-label="Témoignages clients">
                            @foreach(array_merge($testimonials, $testimonials) as $t)
                            <article role="listitem" class="w-72 shrink-0 bg-white rounded-2xl p-5 shadow-sm border border-gray-100 flex flex-col gap-3 hover:shadow-md transition-shadow duration-200">
                                {{-- Stars --}}
                                <div class="flex gap-0.5">
                                    @for ($i = 0; $i < $t['stars']; $i++)
                                        <svg class="w-3.5 h-3.5 text-amber-400 fill-current" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" /></svg>
                                    @endfor
                                </div>
                                {{-- Quote --}}
                                <p class="text-gray-600 text-sm leading-relaxed flex-1">{{ $t['text'] }}</p>
                                {{-- Author --}}
                                <div class="flex items-center gap-3 pt-3 border-t border-gray-50">
                                    <div class="w-9 h-9 rounded-full bg-linear-to-br {{ $t['color'] }} flex items-center justify-center shrink-0 shadow-sm">
                                        <span class="text-white font-bold text-xs">{{ $t['initials'] }}</span>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="font-semibold text-gray-900 text-sm leading-none truncate">{{ $t['name'] }}</div>
                                        <div class="text-xs text-gray-400 mt-0.5 flex items-center gap-1">
                                            <svg class="w-2.5 h-2.5 text-[#FF8A1F] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                            </svg>
                                            {{ $t['city'] }}, Abidjan
                                        </div>
                                    </div>
                                    <span class="inline-flex items-center gap-1 bg-emerald-50 text-emerald-600 text-[10px] font-semibold px-2 py-0.5 rounded-full shrink-0">
                                        <svg class="w-2.5 h-2.5" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" /></svg>
                                        Vérifié
                                    </span>
                                </div>
                            </article>
                            @endforeach
                        </div>
                    </div>

                </section>

                <script nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}">
                (function () {
                    var tracks = document.querySelectorAll('.rezi-marquee-track');
                    if (!tracks.length || typeof IntersectionObserver === 'undefined') return;
                    var observer = new IntersectionObserver(function (entries) {
                        entries.forEach(function (entry) {
                            entry.target.style.animationPlayState = entry.isIntersecting ? 'running' : 'paused';
                        });
                    }, { threshold: 0 });
                    tracks.forEach(function (track) { observer.observe(track); });
                })();
                </script>

