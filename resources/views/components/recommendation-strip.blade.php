{{--
    Bande de recommandations IA (section homepage ou dashboard).

    Props :
      $userId     : int|null  — si fourni, chargement AJAX depuis /api/v1/recommendations
      $residences : Collection — résidences pré-chargées (rendu SSR, ex: dashboard)
      $title      : string    — titre de la section (optionnel)
      $limit      : int       — nombre max affiché
--}}

@props([
    'userId'     => null,
    'residences' => null,
    'title'      => 'Sélectionnés pour vous',
    'limit'      => 6,
])

<section
    x-data="recommendationStrip(@js($userId), @js($residences?->map(fn($r) => [
        'id'             => $r->id,
        'title'          => $r->title ?? $r->name ?? '',
        'commune'        => $r->commune ?? '',
        'price_per_day'  => $r->price_per_day,
        'price_per_month'=> $r->price_per_month,
        'bedrooms'       => $r->bedrooms,
        'type'           => $r->type,
        'average_rating' => $r->average_rating,
        'reviews_count'  => $r->reviews_count,
        'is_verified'    => (bool) ($r->is_verified ?? false),
        'is_top'         => (bool) ($r->is_top_residence ?? false),
        'photo'          => $r->photos->first() ? ['url' => storage_url($r->photos->first()->path)] : null,
        'url'            => route('residences.show', $r->id),
        'match_score'    => $r->match_score ?? null,
        'match_reasons'  => $r->match_reasons ?? [],
    ])->values()->all()), {{ $limit }})"
    x-init="init()"
    class="py-8"
    aria-label="Recommandations personnalisées"
>
    <!-- En-tête -->
    <div class="flex items-center justify-between mb-5">
        <div class="flex items-center gap-2">
            <span class="text-2xl" aria-hidden="true">✨</span>
            <h2 class="font-display text-xl font-bold text-gray-900">{{ $title }}</h2>
        </div>

        <!-- Badge IA -->
        <span class="hidden sm:inline-flex items-center gap-1.5 px-3 py-1 bg-orange-50 border border-orange-200 text-orange-700 text-xs font-semibold rounded-full">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.346.346a3 3 0 01-2.12.878H9.147a3 3 0 01-2.12-.879L6.69 16.9z" />
            </svg>
            Alimenté par l'IA
        </span>
    </div>

    <!-- États : chargement ───────────────────────────────────── -->
    <template x-if="loading">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <template x-for="i in 3" :key="i">
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden animate-pulse">
                    <div class="aspect-4/3 bg-gray-200"></div>
                    <div class="p-4 space-y-3">
                        <div class="h-4 bg-gray-200 rounded w-3/4"></div>
                        <div class="h-3 bg-gray-200 rounded w-1/2"></div>
                        <div class="h-4 bg-gray-200 rounded w-1/3"></div>
                    </div>
                </div>
            </template>
        </div>
    </template>

    <!-- États : erreur ───────────────────────────────────────── -->
    <template x-if="!loading && error">
        <p class="text-sm text-gray-500 italic">Impossible de charger les recommandations.</p>
    </template>

    <!-- États : vide ─────────────────────────────────────────── -->
    <template x-if="!loading && !error && items.length === 0">
        <p class="text-sm text-gray-500 italic">Aucune recommandation pour le moment. Explorez des résidences pour affiner vos suggestions.</p>
    </template>

    <!-- Grille résidences ──────────────────────────────────────── -->
    <template x-if="!loading && !error && items.length > 0">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <template x-for="item in items" :key="item.id">
                <a
                    :href="item.url"
                    class="group bg-white rounded-2xl shadow-md overflow-hidden border border-gray-100 hover:border-orange-100 hover:-translate-y-1 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-orange-400"
                    :aria-label="item.title"
                >
                    <!-- Photo -->
                    <div class="relative aspect-4/3 bg-gray-100 overflow-hidden">
                        <template x-if="item.photo">
                            <img
                                :src="item.photo.url"
                                :alt="item.title"
                                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-700"
                                loading="lazy"
                            >
                        </template>
                        <template x-if="!item.photo">
                            <div class="w-full h-full flex items-center justify-center text-gray-300">
                                <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                                </svg>
                            </div>
                        </template>

                        <!-- Gradient bas -->
                        <div class="absolute inset-0 bg-linear-to-t from-black/50 via-transparent to-transparent pointer-events-none"></div>

                        <!-- Badge type -->
                        <span class="absolute top-3 left-3 px-2.5 py-1 bg-white/90 backdrop-blur-sm text-gray-700 text-xs font-semibold rounded-full shadow-sm capitalize"
                            x-text="item.type ?? 'Résidence'">
                        </span>

                        <!-- Badge vérifié -->
                        <template x-if="item.is_verified">
                            <span class="absolute top-3 right-3 px-2 py-1 bg-green-500 text-white text-xs font-bold rounded-full shadow-sm">
                                ✓ Vérifié
                            </span>
                        </template>

                        <!-- Score de matching -->
                        <template x-if="item.match_score !== null">
                            <span
                                class="absolute bottom-3 right-3 px-2.5 py-1 text-xs font-bold rounded-full shadow"
                                :class="{
                                    'bg-green-500 text-white': item.match_score >= 70,
                                    'bg-orange-400 text-white': item.match_score >= 50 && item.match_score < 70,
                                    'bg-gray-400 text-white': item.match_score < 50,
                                }"
                                :aria-label="`Score de correspondance : ${item.match_score}%`"
                            >
                                <span x-text="`${item.match_score}% match`"></span>
                            </span>
                        </template>

                        <!-- Prix overlay -->
                        <div class="absolute bottom-3 left-3">
                            <span class="px-3 py-1.5 bg-white/95 backdrop-blur-sm text-gray-900 font-bold text-sm rounded-xl shadow">
                                <span x-text="formatPrice(item.price_per_day ?? item.price_per_month)"></span>
                                <span class="font-normal text-xs text-gray-500" x-text="item.price_per_day ? '/jour' : '/mois'"></span>
                            </span>
                        </div>
                    </div>

                    <!-- Contenu texte -->
                    <div class="p-3 sm:p-4">
                        <h3 class="font-display text-base font-semibold text-gray-900 mb-1 line-clamp-1 group-hover:text-orange-600 transition-colors"
                            x-text="item.title">
                        </h3>
                        <p class="flex items-center gap-1 text-sm text-gray-500 mb-2">
                            <svg class="w-3.5 h-3.5 shrink-0 text-orange-400" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                                <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd" />
                            </svg>
                            <span x-text="item.commune"></span>
                        </p>

                        <!-- Caractéristiques + rating -->
                        <div class="flex items-center justify-between text-xs text-gray-500">
                            <span x-text="item.bedrooms ? `${item.bedrooms} ch.` : ''"></span>
                            <template x-if="item.average_rating">
                                <span class="flex items-center gap-0.5">
                                    <svg class="w-3.5 h-3.5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                    </svg>
                                    <span x-text="Number(item.average_rating).toFixed(1)"></span>
                                    <template x-if="item.reviews_count">
                                        <span x-text="`(${item.reviews_count})`"></span>
                                    </template>
                                </span>
                            </template>
                        </div>

                        <!-- Raisons du matching (tooltip-like) -->
                        <template x-if="item.match_reasons && item.match_reasons.length > 0">
                            <div class="mt-2 flex flex-wrap gap-1">
                                <template x-for="reason in item.match_reasons.slice(0, 2)" :key="reason">
                                    <span class="px-2 py-0.5 bg-orange-50 text-orange-600 text-xs rounded-full border border-orange-100"
                                        x-text="reason">
                                    </span>
                                </template>
                            </div>
                        </template>
                    </div>
                </a>
            </template>
        </div>
    </template>
</section>
