                {{-- 4. SECTION RÉSIDENCES VEDETTES (Boosted / Premium) --}}
                <section class="py-10 sm:py-16 bg-white">
                    <div class="max-w-7xl mx-auto px-4 sm:px-6">
                        {{-- Section Header --}}
                        <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4 mb-10 reveal-hidden"
                            x-intersect.once="$el.classList.add('reveal-visible')">
                            <div>
                                <div
                                    class="inline-flex items-center gap-2 bg-linear-to-r from-amber-100 to-orange-100 text-amber-700 px-3 py-1.5 rounded-full text-xs font-bold mb-3">
                                    <svg aria-hidden="true" class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                        <path
                                            d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" />
                                    </svg>
                                    Sélection Premium
                                </div>
                                <h2 class="font-display text-2xl sm:text-3xl font-bold text-gray-900">
                                    Résidences Vedettes
                                    @if (isset($userLocation) && !empty($userLocation['city']))
                                        <span class="text-orange-500">à {{ $userLocation['city'] }}</span>
                                    @endif
                                </h2>
                                <p class="mt-2 text-sm text-gray-500">Les logements les mieux notés et les plus populaires</p>
                            </div>
                            <a href="{{ route('residences.index') }}"
                                class="inline-flex items-center gap-2 text-sm font-semibold text-orange-500 hover:text-orange-600 transition group">
                                Voir toutes les résidences
                                <svg aria-hidden="true" class="w-4 h-4 group-hover:translate-x-1 transition-transform"
                                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M17 8l4 4m0 0l-4 4m4-4H3" />
                                </svg>
                            </a>
                        </div>

                        {{-- Featured Grid - Dynamic from Database --}}
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6">

                            @php $staggerClasses = ['', 'reveal-delay-1', 'reveal-delay-2', 'reveal-delay-3', 'reveal-delay-4', 'reveal-delay-5']; @endphp

                            @forelse($featuredResidences as $index => $residence)
                                {{-- Featured Card --}}
                                <div x-intersect.once="$el.classList.add('reveal-visible')"
                                    class="group relative reveal-card card-lift {{ $staggerClasses[$index] ?? '' }} bg-white rounded-2xl {{ $index === 0 ? 'border-2 border-amber-200 shadow-lg shadow-amber-100/50' : 'border border-gray-200 shadow-md' }} overflow-hidden {{ $index === 0 ? 'hover:border-amber-300' : 'hover:border-orange-200' }} transition-all duration-300">
                                    {{-- Boost Badge --}}
                                    @if ($index === 0)
                                        <div
                                            class="absolute top-4 left-4 z-10 flex items-center gap-1.5 bg-linear-to-r from-amber-500 to-orange-500 text-white px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wide shadow-lg">
                                            <svg aria-hidden="true" class="w-3 h-3" fill="currentColor" viewBox="0 0 24 24">
                                                <path
                                                    d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" />
                                            </svg>
                                            Vedette
                                        </div>
                                    @else
                                        <div
                                            class="absolute top-4 left-4 z-10 flex items-center gap-1.5 bg-orange-500 text-white px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wide shadow">
                                            <svg aria-hidden="true" class="w-3 h-3" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M13 10V3L4 14h7v7l9-11h-7z" />
                                            </svg>
                                            Nouveau
                                        </div>
                                    @endif
                                    @if($residence->owner?->isSuperhost())
                                        <div class="absolute top-4 right-4 z-10 flex items-center gap-1 bg-purple-600 text-white px-2 py-1 rounded-full text-[10px] font-bold shadow">
                                            <svg aria-hidden="true" class="w-3 h-3" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                            </svg>
                                            Superhost
                                        </div>
                                    @endif

                                    {{-- Image --}}
                                    <div class="relative h-52 overflow-hidden">
                                        @if ($residence->photos->isNotEmpty())
                                            <img loading="lazy" src="{{ storage_url($residence->photos->first()?->path) }}"
                                                alt="{{ $residence->name }}"
                                                class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-700">
                                        @else
                                            <img loading="lazy" src="{{ asset('images/placeholder-residence.jpg') }}"
                                                alt="{{ $residence->name }}"
                                                class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-700">
                                        @endif
                                        <div
                                            class="absolute inset-0 bg-linear-to-t from-black/60 via-transparent to-transparent">
                                        </div>

                                        {{-- Price Tag --}}
                                        <div
                                            class="absolute bottom-4 right-4 bg-white/95 backdrop-blur px-3 py-1.5 rounded-lg shadow-lg">
                                            @if (($residence->price_per_day ?? 0) > 0)
                                                <span
                                                    class="text-lg font-bold text-gray-900">{{ number_format($residence->price_per_day) }}</span>
                                                <span class="text-xs text-gray-500">F/jour</span>
                                            @elseif(($residence->price_per_month ?? 0) > 0)
                                                <span
                                                    class="text-lg font-bold text-gray-900">{{ number_format(round($residence->price_per_month / 30)) }}</span>
                                                <span class="text-xs text-gray-500">F/jour</span>
                                            @else
                                                <span class="text-sm font-semibold text-gray-600">Prix sur demande</span>
                                            @endif
                                        </div>

                                        {{-- Location --}}
                                        <div class="absolute bottom-4 left-4 text-white">
                                            <h3 class="font-bold text-lg leading-tight drop-shadow">
                                                {{ Str::limit($residence->name, 25) }}</h3>
                                            <p class="text-xs text-white/80 flex items-center gap-1 mt-0.5">
                                                <svg aria-hidden="true" class="w-3 h-3" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                                </svg>
                                                {{ $residence->commune }}{{ $residence->quartier ? ', ' . $residence->quartier : '' }}
                                            </p>
                                        </div>
                                    </div>

                                    {{-- Content --}}
                                    <div class="p-4">
                                        {{-- Rating & Reviews --}}
                                        <div class="flex items-center justify-between mb-3">
                                            <div class="flex items-center gap-1">
                                                <div class="flex items-center gap-0.5 text-amber-500">
                                                    @php $avgRating = $residence->reviews_avg_rating ?? $residence->average_rating ?? 0; @endphp
                                                    @for ($i = 0; $i < 5; $i++)
                                                        <svg aria-hidden="true"
                                                            class="w-4 h-4 fill-current {{ $i < round($avgRating) ? '' : 'opacity-30' }}"
                                                            viewBox="0 0 24 24">
                                                            <path
                                                                d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" />
                                                        </svg>
                                                    @endfor
                                                </div>
                                                <span
                                                    class="text-sm font-semibold text-gray-900">{{ $avgRating > 0 ? number_format($avgRating, 1) : '—' }}</span>
                                                <span class="text-xs text-gray-400">({{ $residence->reviews_count ?? 0 }}
                                                    avis)</span>
                                            </div>
                                            <a href="{{ route('residences.show', $residence) }}"
                                                class="p-2 rounded-full hover:bg-gray-100 transition"
                                                aria-label="Voir les détails">
                                                <svg aria-hidden="true"
                                                    class="w-5 h-5 text-gray-400 hover:text-orange-500 transition"
                                                    fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                                    aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                </svg>
                                            </a>
                                        </div>

                                        {{-- Amenities --}}
                                        <div class="flex flex-wrap gap-2 mb-4">
                                            <span
                                                class="inline-flex items-center gap-1 bg-gray-100 text-gray-600 px-2 py-1 rounded-md text-xs">
                                                <svg aria-hidden="true" class="w-3 h-3" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                                                </svg>
                                                {{ $residence->bedrooms }}
                                                {{ $residence->bedrooms > 1 ? 'chambres' : 'chambre' }}
                                            </span>
                                            @foreach ($residence->amenities->take(2) as $amenity)
                                                <span
                                                    class="inline-flex items-center gap-1 bg-gray-100 text-gray-600 px-2 py-1 rounded-md text-xs">
                                                    <svg aria-hidden="true" class="w-3 h-3" fill="none"
                                                        stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                            d="M5 13l4 4L19 7" />
                                                    </svg>
                                                    {{ $amenity->name }}
                                                </span>
                                            @endforeach
                                        </div>

                                        {{-- CTA --}}
                                        <a href="{{ route('residences.show', $residence) }}"
                                            class="block w-full {{ $index === 0 ? 'bg-linear-to-r from-amber-500 to-orange-500 hover:from-amber-600 hover:to-orange-600 shadow-lg shadow-amber-500/25' : 'bg-orange-500 hover:bg-orange-600 shadow' }} text-white text-center py-2.5 rounded-xl font-semibold text-sm transition-all">
                                            Voir les détails
                                        </a>
                                    </div>
                                </div>
                            @empty
                                <div class="col-span-3 text-center py-12">
                                    <svg aria-hidden="true" class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none"
                                        stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                    </svg>
                                    <p class="text-gray-500">Aucune résidence vedette pour le moment</p>
                                    <a href="{{ route('residences.index') }}"
                                        class="mt-4 inline-flex items-center gap-2 text-orange-500 hover:text-orange-600 font-semibold">
                                        Voir toutes les résidences
                                        <svg aria-hidden="true" class="w-4 h-4" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M17 8l4 4m0 0l-4 4m4-4H3" />
                                        </svg>
                                    </a>
                                </div>
                            @endforelse

                        </div>

                        {{-- CTA for Owners --}}
                        <div class="mt-8 sm:mt-12 bg-linear-to-r from-orange-50 via-orange-50 to-cyan-50 rounded-2xl p-4 sm:p-8 border border-orange-100 reveal-scale"
                            x-intersect.once="$el.classList.add('reveal-visible')">
                            <div class="flex flex-col sm:flex-row items-center justify-between gap-6">
                                <div class="text-center sm:text-left">
                                    <h3 class="text-lg sm:text-xl font-bold text-gray-900">Vous êtes propriétaire ?</h3>
                                    <p class="mt-1 text-sm text-gray-600">Boostez votre résidence et obtenez jusqu'à <span
                                            class="font-bold text-orange-500">5x plus de visibilité</span></p>
                                </div>
                                <div class="flex flex-col sm:flex-row gap-3">
                                    <a href="{{ route('owner.residences.create') }}"
                                        class="inline-flex items-center justify-center gap-2 bg-orange-500 hover:bg-orange-600 text-white px-6 py-3 rounded-xl font-semibold text-sm shadow-lg shadow-orange-500/25 transition-all">
                                        <svg aria-hidden="true" class="w-4 h-4" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M12 4v16m8-8H4" />
                                        </svg>
                                        Publier gratuitement
                                    </a>
                                    <a href="{{ route('pages.guide-proprietaire') }}"
                                        class="inline-flex items-center justify-center gap-2 bg-white hover:bg-gray-50 text-gray-700 px-6 py-3 rounded-xl font-semibold text-sm border border-gray-200 transition-all">
                                        <svg aria-hidden="true" class="w-4 h-4 text-amber-500" fill="currentColor"
                                            viewBox="0 0 24 24">
                                            <path
                                                d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" />
                                        </svg>
                                        Voir les offres Boost
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

