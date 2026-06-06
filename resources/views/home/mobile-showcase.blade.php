@php
    $mobileLeadResidence = $featuredResidences->first();
    $mobileSecondaryResidences = $featuredResidences->skip(1)->take(5);
    $mobileLocationLabel = ($userLocation['city'] ?? (
        \App\Services\UserLocationService::current()['city'] ?? 'Abidjan'
    )) . ', Côte d\'Ivoire';
    $mobileResidenceCount = $featuredResidences->count();
    $mobileStartingPrice = $featuredResidences->min('price_per_day');
@endphp

<section x-data="{ menuOpen: false }" class="md:hidden min-h-screen bg-white pb-[calc(5.25rem+env(safe-area-inset-bottom))] text-[#222222]">
    <header class="sticky top-0 z-30 border-b border-[#ebebeb] bg-white/95 px-4 pb-3 pt-[max(env(safe-area-inset-top),0.75rem)] backdrop-blur-xl">
        <div class="flex items-center gap-3">
            <a href="{{ route('home') }}" class="flex items-center gap-2" aria-label="Accueil Rezi">
                <img loading="lazy" src="{{ asset('images/logo-rezi.png') }}" alt="ReziApp" class="h-9 w-auto">
            </a>

            <form @submit.prevent="searchMobile()" class="flex h-14 min-w-0 flex-1 items-center gap-3 rounded-full border border-[#dddddd] bg-white px-4 shadow-[0_2px_8px_rgba(0,0,0,0.12)]">
                <label for="mobile-home-search" class="sr-only">Rechercher un quartier ou une commune</label>
                <svg aria-hidden="true" class="h-5 w-5 shrink-0 text-[#222222]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
                <div class="min-w-0 flex-1">
                    <input
                        id="mobile-home-search"
                        type="text"
                        x-model="searchQuery"
                        list="mobile-home-communes"
                        placeholder="Où allez-vous ?"
                        class="block w-full min-w-0 bg-transparent text-[14px] font-semibold leading-5 text-[#222222] placeholder:text-[#222222] focus:outline-none"
                        autocomplete="off"
                        autocapitalize="words"
                        enterkeyhint="search">
                    <p class="truncate text-[12px] leading-4 text-[#6a6a6a]">{{ $mobileLocationLabel }}</p>
                </div>
            </form>

            <button type="button"
                @click="menuOpen = !menuOpen"
                class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full border border-[#dddddd] bg-white text-[#222222] shadow-[0_2px_8px_rgba(0,0,0,0.08)] transition active:scale-95"
                aria-label="Ouvrir le menu">
                <svg aria-hidden="true" class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
            </button>
        </div>

        <datalist id="mobile-home-communes">
            @foreach ($popularZones as $zone)
                <option value="{{ is_array($zone) ? ($zone['name'] ?? '') : $zone->name }}"></option>
            @endforeach
        </datalist>

        <div x-show="menuOpen" x-cloak x-transition.opacity class="fixed inset-0 z-40 bg-black/25" @click="menuOpen = false"></div>
        <div x-show="menuOpen" x-cloak x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 translate-y-0"
            x-transition:leave-end="opacity-0 -translate-y-2"
            class="absolute inset-x-4 top-full z-50 mt-2 overflow-hidden rounded-lg bg-white shadow-[0_18px_45px_rgba(0,0,0,0.16)] ring-1 ring-black/5">
            <div class="py-2">
                <a href="{{ route('home') }}" class="flex items-center gap-3 px-4 py-3 text-sm font-medium text-[#222222] hover:bg-[#f7f7f7]">Accueil</a>
                <a href="{{ route('residences.index') }}" class="flex items-center gap-3 px-4 py-3 text-sm font-medium text-[#222222] hover:bg-[#f7f7f7]">Résidences</a>
                <a href="{{ route('residences.map') }}" class="flex items-center gap-3 px-4 py-3 text-sm font-medium text-[#222222] hover:bg-[#f7f7f7]">Carte</a>
                <a href="{{ route('pages.contact') }}" class="flex items-center gap-3 px-4 py-3 text-sm font-medium text-[#222222] hover:bg-[#f7f7f7]">Contact</a>
            </div>
        </div>
    </header>

    <div class="space-y-5 px-4 pt-4">
        <div class="flex snap-x gap-6 overflow-x-auto border-b border-[#ebebeb] pb-3 scrollbar-hide">
            <a href="{{ route('residences.index') }}" class="relative shrink-0 pb-2 text-center text-[12px] font-semibold text-[#222222] after:absolute after:inset-x-1 after:-bottom-3 after:h-0.5 after:rounded-full after:bg-[#222222]">
                Résidences
            </a>
            @foreach ($popularZones->take(5) as $zone)
                <a href="{{ route('residences.index', ['commune' => is_array($zone) ? ($zone['name'] ?? '') : $zone->name]) }}" class="shrink-0 pb-2 text-center text-[12px] font-medium text-[#6a6a6a]">
                    {{ is_array($zone) ? ($zone['name'] ?? '') : $zone->name }}
                </a>
            @endforeach
        </div>

        <div class="flex items-center gap-2">
            <button
                type="button"
                @click="startGeoloc()"
                class="inline-flex min-h-11 flex-1 items-center justify-center gap-2 rounded-full border border-[#dddddd] bg-white px-4 text-[13px] font-semibold text-[#222222] shadow-[0_1px_3px_rgba(0,0,0,0.08)] transition active:scale-95"
                :class="gpsState === 'locating' ? 'text-[#e00b41]' : ''">
                <template x-if="gpsState !== 'locating'">
                    <svg aria-hidden="true" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 2v3m0 14v3m10-10h-3M5 12H2m16.95-6.95l-2.12 2.12M7.17 16.83l-2.12 2.12m13.9 0l-2.12-2.12M7.17 7.17L5.05 5.05" />
                        <circle cx="12" cy="12" r="4" stroke-width="2" />
                    </svg>
                </template>
                <template x-if="gpsState === 'locating'">
                    <svg aria-hidden="true" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                    </svg>
                </template>
                <span x-text="gpsState === 'locating' ? 'Localisation...' : 'Autour de moi'"></span>
            </button>
            <a href="{{ route('residences.map') }}" class="inline-flex min-h-11 flex-1 items-center justify-center rounded-full border border-[#dddddd] bg-white px-4 text-[13px] font-semibold text-[#222222] shadow-[0_1px_3px_rgba(0,0,0,0.08)]">
                Carte
            </a>
        </div>

        <div x-show="gpsState === 'success' && gpsAccuracy" x-cloak class="rounded-full bg-[#f7f7f7] px-4 py-2 text-[12px] font-medium text-[#3f3f3f]">
            Position détectée avec une précision de ±<span x-text="gpsAccuracy"></span>m
        </div>

        <div class="space-y-3">
            <div class="flex items-end justify-between gap-3">
                <div>
                    <p class="text-[12px] font-semibold uppercase leading-4 tracking-[0.14em] text-[#6a6a6a]">Explorer par zone</p>
                    <h2 class="text-[22px] font-semibold leading-7 tracking-normal text-[#222222]">Carte des résidences</h2>
                </div>
                <a href="{{ route('residences.map') }}" class="shrink-0 text-[14px] font-semibold text-[#222222] underline underline-offset-2">Plein écran</a>
            </div>

            <div class="relative overflow-hidden rounded-md bg-[#f2f2f2] shadow-[0_2px_12px_rgba(0,0,0,0.10)] ring-1 ring-black/5">
                <div x-ref="mobileMapContainer" x-init="waitAndInitMobileMap()" class="h-[58vh] min-h-112 w-full bg-[#e5e5e5]"></div>

                <div class="pointer-events-none absolute left-3 top-3 rounded-full bg-white/95 px-3 py-2 text-[13px] font-semibold text-[#222222] shadow-[0_6px_18px_rgba(0,0,0,0.14)] backdrop-blur">
                    <span class="text-[#e00b41]">{{ $mobileResidenceCount }}</span> résidences visibles
                </div>

                <div class="absolute bottom-3 left-3 right-3 flex items-center gap-2">
                    <a href="{{ route('residences.map') }}" class="inline-flex min-h-11 flex-1 items-center justify-center rounded-full bg-[#222222] px-5 text-[14px] font-semibold text-white shadow-lg">
                        Ouvrir la carte
                    </a>
                    <button type="button" @click="startGeoloc()" class="inline-flex min-h-11 items-center justify-center rounded-full bg-white px-5 text-[14px] font-semibold text-[#222222] shadow-lg ring-1 ring-black/10">
                        Autour de moi
                    </button>
                </div>
            </div>
        </div>

        <div class="space-y-4 pt-2">
            <div class="flex items-end justify-between gap-3">
                <div>
                    <p class="text-[12px] font-semibold uppercase leading-4 tracking-[0.14em] text-[#6a6a6a]">À proximité</p>
                    <h3 class="text-[22px] font-semibold leading-7 tracking-normal text-[#222222]">Résidences à explorer</h3>
                </div>
                <a href="{{ route('residences.map') }}" class="shrink-0 text-[14px] font-semibold text-[#222222] underline underline-offset-2">Voir plus</a>
            </div>

            <div class="flex snap-x snap-mandatory gap-4 overflow-x-auto pb-20 scrollbar-hide">
                @foreach ($mobileSecondaryResidences as $residence)
                    <a href="{{ route('residences.show', $residence) }}"
                        class="group w-[78vw] max-w-[20rem] shrink-0 snap-start">
                        <div class="relative overflow-hidden rounded-md bg-[#f2f2f2]">
                            @if ($residence->photos->isNotEmpty())
                                <img loading="{{ $loop->first ? 'eager' : 'lazy' }}" src="{{ storage_url($residence->photos->first()?->path) }}"
                                    alt="{{ $residence->name }}" class="aspect-[1.05/1] w-full object-cover transition-transform duration-300 group-active:scale-[0.99]">
                            @else
                                <div class="aspect-[1.05/1] w-full bg-[#f2f2f2]"></div>
                            @endif
                            <p class="absolute left-3 top-3 rounded-full bg-white/95 px-3 py-1 text-[12px] font-semibold text-[#222222] shadow-sm backdrop-blur">{{ number_format($residence->price_per_day, 0, ',', ' ') }} FCFA</p>
                            <div class="absolute right-3 top-3 rounded-full bg-white/90 p-1 shadow-sm backdrop-blur">
                                <x-favorite-button :residence-id="$residence->id" size="sm" class="shadow-none" />
                            </div>
                        </div>
                        <div class="mt-3 grid grid-cols-[1fr_auto] gap-x-2 gap-y-1">
                            <h4 class="truncate text-[15px] font-semibold leading-5 text-[#222222]">{{ $residence->name }}</h4>
                            <span class="text-[14px] leading-5 text-[#222222]">{{ $residence->reviews_count > 0 ? number_format($residence->average_rating, 1) : 'Nouveau' }}</span>
                            <p class="col-span-2 truncate text-[14px] leading-5 text-[#6a6a6a]">{{ $residence->quartier ?: $residence->commune }}</p>
                            <p class="col-span-2 pt-0.5 text-[15px] leading-5 text-[#222222]"><span class="font-semibold">Disponible</span> maintenant</p>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    </div>

    <nav aria-label="Navigation mobile" class="fixed inset-x-0 bottom-0 z-30 border-t border-[#dddddd] bg-white/95 pb-safe backdrop-blur-xl">
        <div class="mx-auto grid h-16 max-w-md grid-cols-4 px-2">
            <a href="{{ route('residences.index') }}" class="relative flex flex-col items-center justify-center gap-1 text-[#ff385c]">
                <span class="absolute top-0 h-1 w-10 rounded-full bg-[#ffd1da]"></span>
                <svg aria-hidden="true" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
                <span class="text-[11px] font-medium">Rechercher</span>
            </a>

            <a href="{{ route('favorites.index') }}" class="flex flex-col items-center justify-center gap-1 text-[#6a6a6a]">
                <svg aria-hidden="true" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                </svg>
                <span class="text-[11px] font-medium">Favoris</span>
            </a>

            <a href="{{ auth()->check() ? route('profile.edit') : route('login') }}" class="flex flex-col items-center justify-center gap-1 text-[#6a6a6a]">
                <svg aria-hidden="true" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
                <span class="text-[11px] font-medium">Profil</span>
            </a>
        </div>
    </nav>
</section>
