@extends('layouts.app')

@section('title', $residence->title . ' - Rezi Studio Meublé Faya')
@section('description', Str::limit(strip_tags($residence->description ?? ''), 160))

{{-- SEO: utilise uniquement @section title/description consommés par le layout --}}
{{-- Les meta OG/JSON-LD spécifiques sont poussés via @push sans dupliquer <x-seo-meta> --}}
@push('meta')
    {{-- Open Graph spécifique à la résidence (le layout gère déjà title/description/canonical) --}}
    <meta property="og:type" content="place" />
    <meta property="og:image" content="{{ $residence->photos->first()?->url }}" />
    <meta property="og:image:alt" content="{{ $residence->title }}" />
    <meta name="keywords" content="résidence meublée, {{ $residence->commune }}, {{ $residence->quartier }}, location, {{ $residence->city ?? '' }}" />

    {{-- JSON-LD Structured Data --}}
    <script type="application/ld+json">
    {!! json_encode([
        '@context' => 'https://schema.org',
        '@type' => 'LodgingBusiness',
        'name' => $residence->title,
        'description' => Str::limit(strip_tags($residence->description ?? ''), 300),
        'image' => $residence->photos->first()?->url,
        'address' => [
            '@type' => 'PostalAddress',
            'addressLocality' => $residence->commune,
            'addressRegion' => $residence->city ?? 'Abidjan',
            'addressCountry' => 'CI',
        ],
        'geo' => $residence->latitude ? [
            '@type' => 'GeoCoordinates',
            'latitude' => $residence->latitude,
            'longitude' => $residence->longitude,
        ] : null,
        'priceRange' => number_format($residence->price_per_day ?? 0) . ' FCFA/jour',
        'aggregateRating' => $residence->reviews_count > 0 ? [
            '@type' => 'AggregateRating',
            'ratingValue' => $residence->average_rating,
            'reviewCount' => $residence->reviews_count,
        ] : null,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}
    </script>
@endpush

@push('styles')
    <style>
        /* ── Photo Grid Airbnb (main large + 2×2 side) ── */
        .photo-grid {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr;
            grid-template-rows: 1fr 1fr;
            gap: 8px;
            height: 56vh;
            min-height: 400px;
            max-height: 560px;
            border-radius: 12px;
            overflow: hidden;
            position: relative;
        }

        .photo-grid .photo-main {
            grid-column: 1;
            grid-row: 1 / -1; /* span both rows on the left */
        }

        .photo-grid .photo-item {
            position: relative;
            overflow: hidden;
            cursor: pointer;
        }

        .photo-grid .photo-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.4s cubic-bezier(.25, .46, .45, .94);
        }

        .photo-grid .photo-item:hover img {
            transform: scale(1.04);
        }

        /* ── Gallery Lightbox ── */
        .gallery-modal {
            position: fixed;
            inset: 0;
            z-index: 9999;
            background: rgba(0, 0, 0, 0.92);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .gallery-modal img {
            max-width: 90vw;
            max-height: 85vh;
            object-fit: contain;
            border-radius: 8px;
        }

        /* ── Booking Card ── */
        .booking-card {
            position: sticky;
            top: 88px;
            border: 1.5px solid transparent;
            background: linear-gradient(#fff, #fff) padding-box,
                        linear-gradient(135deg, #F97316 0%, #F59E0B 100%) border-box;
            border-radius: 16px;
            box-shadow: 0 8px 30px rgba(249, 115, 22, 0.12), 0 2px 8px rgba(0,0,0,0.06);
            padding: 24px;
            transition: box-shadow 0.3s;
        }

        .booking-card:hover {
            box-shadow: 0 12px 40px rgba(249, 115, 22, 0.2), 0 4px 12px rgba(0,0,0,0.08);
        }

        /* ── Rating Bar ── */
        .rating-bar {
            height: 5px;
            border-radius: 3px;
            background: #e5e7eb;
            overflow: hidden;
        }

        .rating-bar-fill {
            height: 100%;
            border-radius: 3px;
            background: linear-gradient(90deg, #F97316, #F59E0B);
        }

        /* ── Responsive ── */
        @media (max-width: 768px) {
            .photo-grid {
                grid-template-columns: 1fr;
                grid-template-rows: auto;
                height: auto;
                min-height: 0;
                max-height: none;
                border-radius: 0;
                gap: 0;
            }

            .photo-grid .photo-main {
                grid-row: span 1;
                height: min(55vw, 260px);
            }

            .photo-grid .photo-item:not(.photo-main) {
                display: none;
            }

            .booking-card {
                position: static;
                box-shadow: none;
                border: none;
                border-top: 2px solid #F2F2F2;
                padding: 20px 0 0 0;
                margin-top: 8px;
            }
        }

        @media (max-width: 480px) {
            .photo-grid .photo-main {
                height: 52vw;
            }
        }

        /* ── Sticky Section Nav ── */
        .hide-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
        .hide-scrollbar::-webkit-scrollbar { display: none; }

        .section-nav-link {
            position: relative;
            flex-shrink: 0;
            padding: 18px 4px;
            font-size: 14px;
            font-weight: 600;
            color: #6b7280;
            white-space: nowrap;
            border-bottom: 2px solid transparent;
            transition: color 0.15s, border-color 0.15s;
            background: none;
            cursor: pointer;
        }
        .section-nav-link:hover { color: #111827; }
        .section-nav-link.active {
            color: #111827;
            border-bottom-color: #111827;
        }
    </style>
@endpush

@section('content')
    @php
        $photos = $residence->photos;
        $mainPhoto = $photos->first();
        $sidePhotos = $photos->skip(1)->take(4); // Airbnb: 1 main + 4 side (2×2 right)
        $totalPhotos = $photos->count();

        // Résolution du prix : toujours le tarif journalier
        if (($residence->price_per_day ?? 0) > 0) {
            $displayPrice = $residence->price_per_day;
            $priceLabel = '/ jour';
            $pricePerNight = $residence->price_per_day;
        } elseif (($residence->price_per_month ?? 0) > 0) {
            $displayPrice = round($residence->price_per_month / 30);
            $priceLabel = '/ jour';
            $pricePerNight = round($residence->price_per_month / 30);
        } elseif (($residence->price_per_week ?? 0) > 0) {
            $displayPrice = round($residence->price_per_week / 7);
            $priceLabel = '/ jour';
            $pricePerNight = round($residence->price_per_week / 7);
        } else {
            $displayPrice = 0;
            $priceLabel = '';
            $pricePerNight = 0;
        }
    @endphp

    <div class="bg-white min-h-screen" x-data="residencePage({{ \Illuminate\Support\Js::encode(['totalPhotos' => $totalPhotos, 'title' => $residence->title, 'photoUrls' => $photos->map(fn($p) => storage_url($p->path))->values()->all()]) }})">

        {{-- ═══════════════════════════════════
         Sticky Section Navigation (Airbnb)
        ═══════════════════════════════════ --}}
        <div x-data="stickyNav()"
             x-show="visible"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 -translate-y-1"
             x-transition:enter-end="opacity-100 translate-y-0"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 translate-y-0"
             x-transition:leave-end="opacity-0 -translate-y-1"
             class="fixed top-16 left-0 right-0 z-20 bg-white border-b border-gray-200 hidden md:block"
             x-cloak>
            <div class="max-w-280 mx-auto px-6">
                <nav class="flex items-center gap-8 hide-scrollbar overflow-x-auto" aria-label="Sections de la résidence">
                    <button @click="navScrollTo('photos')" class="section-nav-link" :class="{ active: navActive === 'photos' }">Photos</button>
                    <button @click="navScrollTo('equipements')" class="section-nav-link" :class="{ active: navActive === 'equipements' }">Équipements</button>
                    <button @click="navScrollTo('calendrier')" class="section-nav-link" :class="{ active: navActive === 'calendrier' }">Disponibilités</button>
                    <button @click="navScrollTo('avis')" class="section-nav-link" :class="{ active: navActive === 'avis' }">Avis</button>
                    <button @click="navScrollTo('emplacement')" class="section-nav-link" :class="{ active: navActive === 'emplacement' }">Emplacement</button>
                    <button @click="navScrollTo('hote')" class="section-nav-link" :class="{ active: navActive === 'hote' }">Hôte</button>
                </nav>
            </div>
        </div>

        {{-- ═══════════════════════════════════
         SECTION 1 — Title + Actions
    ═══════════════════════════════════ --}}
        {{-- Breadcrumb localisation --}}
        <div class="max-w-280 mx-auto px-4 sm:px-6 pt-4 sm:pt-3">
            <nav class="flex items-center gap-1.5 text-sm text-gray-500 flex-wrap" aria-label="Breadcrumb">
                <a href="{{ route('home') }}" class="hover:text-[#CC5A00] transition-colors">Accueil</a>
                <svg class="w-3.5 h-3.5 text-gray-300 shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                <a href="{{ route('residences.index', ['city' => $residence->city]) }}" class="hover:text-[#CC5A00] transition-colors">{{ $residence->city ?? 'Abidjan' }}</a>
                @if ($residence->commune)
                    <svg class="w-3.5 h-3.5 text-gray-300 shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                    <a href="{{ route('residences.index', ['commune' => $residence->commune]) }}" class="hover:text-[#CC5A00] transition-colors">{{ $residence->commune }}</a>
                @endif
                <svg class="w-3.5 h-3.5 text-gray-300 shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                <span class="text-gray-900 font-medium truncate max-w-48 sm:max-w-xs">{{ Str::limit($residence->title, 40) }}</span>
            </nav>
        </div>

        <div class="max-w-280 mx-auto px-4 sm:px-6 pt-2 sm:pt-3 pb-3 sm:pb-4">
            <div class="flex items-center gap-3 flex-wrap">
                <h1 class="font-sans text-2xl sm:text-3xl lg:text-4xl font-bold text-gray-900 leading-tight tracking-tight">{{ $residence->title }}</h1>
                @if ($isSponsored ?? false)
                    <span
                        class="inline-flex items-center gap-1 px-3 py-1 bg-amber-500 text-white text-xs font-semibold rounded-full shadow-sm">
                        <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                            <path
                                d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                        </svg>
                        Sponsorisé
                    </span>
                @endif
                @if (!empty($isSuperhost))
                    <span class="inline-flex items-center gap-1 px-3 py-1 bg-rose-50 text-rose-700 text-xs font-bold rounded-full">
                        <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2l2.39 7.36H22l-6.19 4.5L18.2 22 12 17.27 5.8 22l2.39-8.14L2 9.36h7.61z"/></svg>
                        Superhôte
                    </span>
                @endif
            </div>
            {{-- Badges FOMO style Booking/Airbnb --}}
            @if (($activeViewers ?? 0) > 0 || ($bookingsThisMonth ?? 0) > 0 || ($lastBookedDaysAgo ?? 0) > 0)
                <div class="flex flex-wrap items-center gap-2 mt-2.5">
                    @if (($activeViewers ?? 0) >= 3)
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-[#FFF4EB] text-[#A34700] text-xs font-semibold rounded-full border border-[#FFD0A3]">
                            <span class="relative flex h-2 w-2">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-[#FF8A1F] opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-2 w-2 bg-[#F16A00]"></span>
                            </span>
                            {{ $activeViewers }} personnes regardent ce logement
                        </span>
                    @endif
                    @if (($bookingsThisMonth ?? 0) >= 2)
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-emerald-50 text-emerald-700 text-xs font-semibold rounded-full border border-emerald-200">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            Réservé {{ $bookingsThisMonth }} fois ce mois-ci
                        </span>
                    @endif
                    @if (($lastBookedDaysAgo ?? 0) > 0 && ($bookingsThisMonth ?? 0) < 2)
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-blue-50 text-blue-700 text-xs font-semibold rounded-full border border-blue-200">
                            <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm.75-13a.75.75 0 00-1.5 0v5c0 .2.08.39.22.53l3 3a.75.75 0 101.06-1.06L10.75 9.69V5z" clip-rule="evenodd"/></svg>
                            Réservé récemment
                        </span>
                    @endif
                </div>
            @endif
            <div class="flex flex-wrap items-center justify-between mt-2 gap-2">
                <div class="flex flex-wrap items-center gap-1 text-sm">
                    @if ($residence->reviews_count > 0)
                        <svg aria-hidden="true" class="w-4 h-4 text-amber-400" fill="currentColor" viewBox="0 0 20 20">
                            <path
                                d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                        </svg>
                        <span class="font-bold text-gray-900">{{ number_format($residence->average_rating, 1) }}</span>
                        <span class="text-gray-400 mx-0.5">·</span>
                        <a href="#avis"
                            class="underline font-medium text-gray-900 hover:text-[#CC5A00]">{{ $residence->reviews_count }}
                            commentaire{{ $residence->reviews_count > 1 ? 's' : '' }}</a>
                        <span class="text-gray-400 mx-0.5">·</span>
                    @endif
                    @if ($residence->is_verified)
                        <svg aria-hidden="true" class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                        </svg>
                        <span class="font-medium text-gray-900">Vérifié</span>
                        <span class="text-gray-400 mx-0.5">·</span>
                    @endif
                    @if ($residence->instant_book)
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200 text-xs font-semibold" title="Réservation immédiate sans validation">
                            ⚡ Réservation instantanée
                        </span>
                        <span class="text-gray-400 mx-0.5">·</span>
                    @endif
                    <a href="#emplacement"
                        class="underline font-medium text-gray-900 hover:text-gray-600">{{ $residence->commune ?? ($residence->city ?? 'N/A') }}{{ $residence->city ? ', ' . $residence->city : '' }}</a>
                </div>
                <div class="flex items-center gap-1">
                    <button @click="shareResidence()"
                        class="flex items-center gap-2 px-3 py-2.5 min-h-11 rounded-lg hover:bg-gray-100 transition text-sm font-medium text-gray-900">
                        <svg aria-hidden="true" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                        </svg>
                        Partager
                    </button>
                    @auth
                        <form action="{{ route('favorites.toggle', $residence) }}" method="POST">
                            @csrf
                            <button type="submit"
                                class="flex items-center gap-2 px-3 py-2.5 min-h-11 rounded-lg hover:bg-gray-100 transition text-sm font-medium text-gray-900">
                                @if (method_exists($residence, 'isFavoritedBy') && $residence->isFavoritedBy(auth()->user()))
                                    <svg aria-hidden="true" class="w-4 h-4 text-rose-500" fill="currentColor"
                                        viewBox="0 0 20 20">
                                        <path fill-rule="evenodd"
                                            d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4 4 0 115.656 5.656L10 17.657l-6.828-6.829a4 4 0 010-5.656z"
                                            clip-rule="evenodd" />
                                    </svg>
                                    Enregistré
                                @else
                                    <svg aria-hidden="true" class="w-4 h-4" fill="none" stroke="currentColor"
                                        stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                                    </svg>
                                    Enregistrer
                                @endif
                            </button>
                        </form>
                    @endauth
                </div>
            </div>
        </div>

        {{-- ═══════════════════════════════════
         SECTION 2 — Photo Grid (70/30)
    ═══════════════════════════════════ --}}
        <div class="max-w-280 mx-auto px-0 sm:px-6" id="photo-section">
            <div class="photo-grid" id="photos">
                @if ($mainPhoto)
                    <div class="photo-item photo-main" @click="openGallery(0)">
                        <img src="{{ storage_url($mainPhoto->path) }}" alt="{{ $residence->title }}"
                            loading="eager" fetchpriority="high">
                    </div>
                @else
                    <div class="photo-item photo-main bg-gray-100 flex items-center justify-center">
                        <div class="text-center text-gray-400">
                            <svg aria-hidden="true" class="w-16 h-16 mx-auto" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1"
                                    d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            <p class="mt-2 text-sm">Aucune photo</p>
                        </div>
                    </div>
                @endif
                @foreach ($sidePhotos as $index => $photo)
                    <div class="photo-item" @click="openGallery({{ $index + 1 }})">
                        <img loading="lazy" src="{{ storage_url($photo->path) }}"
                            alt="{{ $residence->title }} {{ $index + 2 }}">
                    </div>
                @endforeach
                @for ($i = $sidePhotos->count(); $i < 4; $i++)
                    <div class="photo-item bg-gray-100 hidden sm:block"></div>
                @endfor
                @if ($totalPhotos > 5)
                    <button @click="openGallery(0)"
                        class="absolute bottom-4 right-4 bg-white/90 backdrop-blur-md px-4 py-2.5 rounded-xl text-sm font-semibold text-gray-900 border border-white/60 shadow-lg hover:bg-white transition-all duration-200 hover:scale-105 flex items-center gap-2">
                        <svg aria-hidden="true" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                        </svg>
                        <span>Voir les {{ $totalPhotos }} photos</span>
                    </button>
                @endif
            </div>
        </div>

        {{-- ═══════════════════════════════════
         Gallery Lightbox Modal
    ═══════════════════════════════════ --}}
        <dialog x-show="galleryOpen" x-transition.opacity class="gallery-modal" @keydown.escape.window="galleryOpen = false"
            x-bind:open="galleryOpen" x-cloak aria-label="Galerie photos">
            <button @click="galleryOpen = false"
                class="absolute top-5 left-5 text-white bg-black/40 hover:bg-black/60 rounded-full p-2.5 transition z-10"
                aria-label="Fermer">
                <svg aria-hidden="true" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
            <div class="absolute top-5 right-5 text-white/80 text-sm font-medium bg-black/30 px-3 py-1.5 rounded-full">
                <span x-text="currentPhoto + 1"></span> / {{ $totalPhotos }}
            </div>
            <button @click="prevPhoto()"
                class="absolute left-4 sm:left-6 text-white bg-black/40 hover:bg-black/60 rounded-full w-10 h-10 flex items-center justify-center transition"
                aria-label="Précédent">
                <svg aria-hidden="true" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                </svg>
            </button>
            <button @click="nextPhoto()"
                class="absolute right-4 sm:right-6 text-white bg-black/40 hover:bg-black/60 rounded-full w-10 h-10 flex items-center justify-center transition"
                aria-label="Suivant">
                <svg aria-hidden="true" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                </svg>
            </button>
            {{-- Single dynamic image (loads only current photo) --}}
              <img alt=""
                  x-bind:src="(photoUrls || [])[currentPhoto] || ''"
                  x-bind:alt="'Visuel ' + (currentPhoto + 1)"
                 x-transition:enter="transition-opacity duration-200"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 class="select-none max-h-full max-w-full object-contain"
                 loading="lazy">
           </dialog>

        {{-- ═══════════════════════════════════
         SECTION 3 — Two-Column Layout
    ═══════════════════════════════════ --}}
        <div class="max-w-280 mx-auto px-4 sm:px-6 pt-6 sm:pt-8 pb-32 sm:pb-24 lg:pb-12">
            <div class="flex flex-col lg:flex-row gap-6 sm:gap-8 lg:gap-24">

                {{-- ─── LEFT COLUMN ─── --}}
                <div class="flex-1 min-w-0">

                    {{-- Property type + Host --}}
                    <div class="flex items-center justify-between pb-8 border-b border-gray-200">
                        <div class="min-w-0">
                            <h2 class="text-[22px] font-semibold text-gray-900">
                                {{ $residence->type ?? 'Logement entier' }} ·
                                {{ $residence->commune ?? ($residence->city ?? '') }}
                            </h2>
                            <p class="text-gray-500 text-sm mt-1">
                                {{ $residence->max_guests ?? 4 }}
                                voyageur{{ ($residence->max_guests ?? 4) > 1 ? 's' : '' }}
                                <span class="mx-1">·</span>
                                {{ $residence->bedrooms ?? 1 }} chambre{{ ($residence->bedrooms ?? 1) > 1 ? 's' : '' }}
                                <span class="mx-1">·</span>
                                {{ $residence->beds ?? ($residence->bedrooms ?? 1) }}
                                lit{{ ($residence->beds ?? ($residence->bedrooms ?? 1)) > 1 ? 's' : '' }}
                                <span class="mx-1">·</span>
                                {{ $residence->bathrooms ?? 1 }} salle{{ ($residence->bathrooms ?? 1) > 1 ? 's' : '' }} de
                                bain
                            </p>
                        </div>
                        <div class="shrink-0 ml-4 relative">
                            @if ($residence->owner)
                                @php $ownerAvatarUrl = $residence->owner->getAvatarUrl(); @endphp
                                <img loading="lazy" src="{{ $ownerAvatarUrl }}"
                                    alt="{{ $residence->owner->name }}" class="w-14 h-14 rounded-full object-cover">
                            @else
                                <div
                                    class="w-14 h-14 rounded-full bg-gray-900 flex items-center justify-center text-white text-xl font-bold">
                                    H
                                </div>
                            @endif
                            @if ($residence->owner?->identity_verified)
                                <div class="absolute -bottom-1 -right-1 w-5 h-5 bg-emerald-500 rounded-full flex items-center justify-center ring-2 ring-white"
                                    title="Identité vérifiée">
                                    <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor"
                                        stroke-width="3" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                    </svg>
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- Highlights --}}
                    <div class="py-8 border-b border-gray-200 space-y-4">
                        @if ($residence->reviews_count > 0 && $residence->average_rating >= 4.5)
                            <div class="flex gap-4 items-start p-4 bg-amber-50 rounded-2xl border border-amber-100">
                                <div class="shrink-0 w-10 h-10 bg-amber-100 rounded-xl flex items-center justify-center">
                                    <svg aria-hidden="true" class="w-5 h-5 text-amber-500" fill="currentColor"
                                        viewBox="0 0 20 20">
                                        <path
                                            d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                    </svg>
                                </div>
                                <div>
                                    <p class="font-semibold text-gray-900">Très bien noté par les voyageurs</p>
                                    <p class="text-gray-500 text-sm mt-0.5">{{ $residence->reviews_count }}
                                        voyageur{{ $residence->reviews_count > 1 ? 's' : '' }}
                                        {{ $residence->reviews_count > 1 ? 'ont' : 'a' }} attribué une note de
                                        {{ number_format($residence->average_rating, 1) }}/5.</p>
                                </div>
                            </div>
                        @endif
                        @if ($residence->commune || $residence->city)
                            <div class="flex gap-4 items-start p-4 bg-[#FFF4EB] rounded-2xl border border-[#FFE7D1]">
                                <div class="shrink-0 w-10 h-10 bg-[#FFE7D1] rounded-xl flex items-center justify-center">
                                    <svg aria-hidden="true" class="w-5 h-5 text-[#F16A00]" fill="none"
                                        stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z" />
                                    </svg>
                                </div>
                                <div>
                                    <p class="font-semibold text-gray-900">Très bien situé</p>
                                    <p class="text-gray-500 text-sm mt-0.5">Situé à {{ $residence->commune ?? $residence->city }}{{ $residence->commune && $residence->city ? ', ' . $residence->city : '' }}.</p>
                                </div>
                            </div>
                        @endif
                        @php
                            $cancelPolicy = $residence->relationLoaded('cancellationPolicy')
                                ? $residence->cancellationPolicy
                                : ($residence->cancellation_policy_id ? $residence->cancellationPolicy()->first() : null);
                            $cancelLabel = $cancelPolicy?->display_name ?? $residence->cancellation_policy;
                            $isFlex48 = $cancelPolicy && in_array($cancelPolicy->name, ['flexible_48h', 'flexible'], true);
                        @endphp
                        @if ($cancelLabel)
                            <div class="flex gap-4 items-start p-4 {{ $isFlex48 ? 'bg-emerald-50 border-emerald-100' : 'bg-white border-sand-200' }} rounded-2xl border">
                                <div class="shrink-0 w-10 h-10 {{ $isFlex48 ? 'bg-emerald-100' : 'bg-[#F2F2F2]' }} rounded-xl flex items-center justify-center">
                                    @if ($isFlex48)
                                        <svg aria-hidden="true" class="w-5 h-5 text-emerald-600" fill="none"
                                            stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    @else
                                        <svg aria-hidden="true" class="w-5 h-5 text-gray-500" fill="none"
                                            stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
                                        </svg>
                                    @endif
                                </div>
                                <div>
                                    <p class="font-semibold text-gray-900">
                                        {{ $isFlex48 ? 'Annulation gratuite' : "Politique d'annulation" }}
                                        @if ($isFlex48)
                                            <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-700">48h</span>
                                        @endif
                                    </p>
                                    <p class="text-gray-500 text-sm mt-0.5">
                                        @if ($isFlex48)
                                            Remboursement intégral si vous annulez jusqu'à 48h avant l'arrivée.
                                        @else
                                            {{ $cancelLabel }}
                                        @endif
                                    </p>
                                </div>
                            </div>
                        @endif
                        @if ($residence->instant_book)
                            <div class="flex gap-4 items-start p-4 bg-blue-50 rounded-2xl border border-blue-100">
                                <div class="shrink-0 w-10 h-10 bg-blue-100 rounded-xl flex items-center justify-center">
                                    <svg aria-hidden="true" class="w-5 h-5 text-blue-500" fill="none"
                                        stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z" />
                                    </svg>
                                </div>
                                <div>
                                    <p class="font-semibold text-gray-900">Réservation instantanée</p>
                                    <p class="text-gray-500 text-sm mt-0.5">Réservez immédiatement sans attendre la
                                        confirmation de l'hôte.</p>
                                </div>
                            </div>
                        @endif
                    </div>

                    {{-- Description --}}
                    <div id="description" class="py-8 border-b border-gray-200" x-data="{ expanded: false }">
                        <div class="text-gray-700 leading-7 text-[15px]" :class="{ 'line-clamp-5': !expanded }">
                            {!! nl2br(e($residence->description ?? 'Description non disponible.')) !!}
                        </div>
                        @if (strlen($residence->description ?? '') > 300)
                            <button @click="expanded = !expanded"
                                class="mt-4 text-gray-900 font-semibold text-sm flex items-center gap-1 underline hover:no-underline">
                                <span x-text="expanded ? 'Afficher moins' : 'Afficher la description complète'"></span>
                                <svg aria-hidden="true" class="w-3.5 h-3.5 transition-transform"
                                    :class="expanded ? 'rotate-180' : ''" fill="none" stroke="currentColor"
                                    stroke-width="2.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                                </svg>
                            </button>
                        @endif
                    </div>

                    {{-- Amenities --}}
                    <div id="equipements" class="py-8 border-b border-gray-200" x-data="{ showAll: false }">
                        <h2 class="text-[22px] font-semibold text-gray-900 mb-6">Ce que propose ce logement</h2>
                        @if ($residence->amenities->count() > 0)
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                @foreach ($residence->amenities->take(10) as $amenity)
                                    <div class="flex items-center gap-4 py-1">
                                        @if ($amenity->icon)
                                            <i class="{{ $amenity->icon }} text-lg text-gray-600 w-6 text-center"></i>
                                        @else
                                            <svg aria-hidden="true" class="w-6 h-6 text-gray-600 shrink-0" fill="none"
                                                stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M4.5 12.75l6 6 9-13.5" />
                                            </svg>
                                        @endif
                                        <span class="text-gray-700 text-[15px]">{{ $amenity->name }}</span>
                                    </div>
                                @endforeach
                            </div>
                            @if ($residence->amenities->count() > 10)
                                <div x-show="showAll" x-collapse x-cloak class="mt-4">
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                        @foreach ($residence->amenities->skip(10) as $amenity)
                                            <div class="flex items-center gap-4 py-1">
                                                @if ($amenity->icon)
                                                    <i
                                                        class="{{ $amenity->icon }} text-lg text-gray-600 w-6 text-center"></i>
                                                @else
                                                    <svg aria-hidden="true" class="w-6 h-6 text-gray-600 shrink-0"
                                                        fill="none" stroke="currentColor" stroke-width="1.5"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            d="M4.5 12.75l6 6 9-13.5" />
                                                    </svg>
                                                @endif
                                                <span class="text-gray-700 text-[15px]">{{ $amenity->name }}</span>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                            <button @click="showAll = !showAll"
                                class="mt-6 px-6 py-3 border border-gray-900 rounded-lg text-sm font-semibold text-gray-900 hover:bg-gray-50 transition">
                                <span
                                    x-text="showAll ? 'Masquer' : 'Afficher les {{ $residence->amenities->count() }} équipements'"></span>
                            </button>
                        @else
                            <p class="text-gray-400 text-sm py-4">Aucun équipement listé pour le moment.</p>
                        @endif
                    </div>

                    {{-- Calendar --}}
                    <div id="calendrier" class="py-8 border-b border-gray-200" x-data="residenceCalendar({{ \Illuminate\Support\Js::encode(['unavailable' => $unavailableDates ?? []]) }})">
                        <h2 class="text-[22px] font-semibold text-gray-900 mb-2">
                            {{ $residence->min_nights ?? 2 }} nuit{{ ($residence->min_nights ?? 2) > 1 ? 's' : '' }}
                            minimum
                        </h2>
                        <p class="text-gray-500 text-sm mb-6">
                            <span x-show="!checkIn">Sélectionnez votre date d'arrivée</span>
                            <span x-show="checkIn && !checkOut" x-cloak>Sélectionnez votre date de départ</span>
                            <span x-show="checkIn && checkOut" x-cloak>
                                <strong x-text="checkIn"></strong> → <strong x-text="checkOut"></strong>
                            </span>
                        </p>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                            {{-- Month 1 --}}
                            <div>
                                <div class="flex items-center justify-between mb-4">
                                    <button @click="prev()" type="button" class="p-1 rounded-full hover:bg-gray-100 transition">
                                        <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                                        </svg>
                                    </button>
                                    <span class="font-semibold text-gray-900 text-sm" x-text="mn[cm]+' '+cy"></span>
                                    <div class="w-7"></div>
                                </div>
                                <div class="grid grid-cols-7 gap-0 text-center">
                                    <template x-for="d in dn" :key="'h1-'+d">
                                        <div class="text-xs font-medium text-gray-400 pb-2" x-text="d"></div>
                                    </template>
                                    <template x-for="b in fdm(cm,cy)" :key="'b1-'+b">
                                        <div></div>
                                    </template>
                                    <template x-for="d in dim(cm,cy)" :key="'d1-'+d">
                                        <div class="py-1">
                                            <span
                                                class="inline-flex items-center justify-center w-9 h-9 text-sm transition-colors"
                                                :class="dayClass(d, cm, cy)"
                                                @click="selectDate(d, cm, cy)"
                                                @mouseenter="!isBlocked(d,cm,cy) && (hoverDate = (checkIn && !checkOut) ? fmt(d,cm,cy) : '')"
                                                @mouseleave="hoverDate = ''"
                                                x-text="d"
                                            ></span>
                                        </div>
                                    </template>
                                </div>
                            </div>
                            {{-- Month 2 --}}
                            <div>
                                <div class="flex items-center justify-between mb-4">
                                    <div class="w-7"></div>
                                    <span class="font-semibold text-gray-900 text-sm" x-text="mn[m2]+' '+y2"></span>
                                    <button @click="next()" type="button" class="p-1 rounded-full hover:bg-gray-100 transition">
                                        <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                        </svg>
                                    </button>
                                </div>
                                <div class="grid grid-cols-7 gap-0 text-center">
                                    <template x-for="d in dn" :key="'h2-'+d">
                                        <div class="text-xs font-medium text-gray-400 pb-2" x-text="d"></div>
                                    </template>
                                    <template x-for="b in fdm(m2,y2)" :key="'b2-'+b">
                                        <div></div>
                                    </template>
                                    <template x-for="d in dim(m2,y2)" :key="'d2-'+d">
                                        <div class="py-1">
                                            <span
                                                class="inline-flex items-center justify-center w-9 h-9 text-sm transition-colors"
                                                :class="dayClass(d, m2, y2)"
                                                @click="selectDate(d, m2, y2)"
                                                @mouseenter="!isBlocked(d,m2,y2) && (hoverDate = (checkIn && !checkOut) ? fmt(d,m2,y2) : '')"
                                                @mouseleave="hoverDate = ''"
                                                x-text="d"
                                            ></span>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>
                        <button type="button" @click="clearDates()" x-show="checkIn || checkOut"
                            class="mt-4 text-sm font-semibold text-gray-900 underline hover:no-underline" x-cloak>
                            Effacer les dates
                        </button>
                    </div>

                    {{-- Reviews --}}
                    <div id="avis" class="py-8 border-b border-gray-200">
                        @if ($residence->reviews_count > 0)
                            <div class="flex flex-col items-center mb-10">
                                <div class="flex items-center gap-2 mb-1">
                                    <svg aria-hidden="true" class="w-8 h-8 text-amber-400" fill="currentColor"
                                        viewBox="0 0 20 20">
                                        <path
                                            d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                    </svg>
                                    <span
                                        class="text-5xl font-bold text-gray-900">{{ number_format($residence->average_rating, 1) }}</span>
                                    <svg aria-hidden="true" class="w-8 h-8 text-amber-400" fill="currentColor"
                                        viewBox="0 0 20 20">
                                        <path
                                            d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                    </svg>
                                </div>
                                <p class="text-lg font-semibold text-gray-900">Coup de cœur voyageurs</p>
                                <p class="text-sm text-gray-500 text-center mt-1 max-w-sm">Ce logement fait partie des
                                    mieux notés sur Rezi Studio Meublé Faya, d'après les commentaires et évaluations des voyageurs.</p>
                            </div>

                            @php
                                $cats = [
                                    'Évaluation globale' => $residence->average_rating,
                                    'Propreté' =>
                                        $residence->reviews->avg('cleanliness_rating') ?? $residence->average_rating,
                                    'Arrivée' =>
                                        $residence->reviews->avg('checkin_rating') ?? $residence->average_rating,
                                    'Communication' =>
                                        $residence->reviews->avg('communication_rating') ?? $residence->average_rating,
                                    'Emplacement' =>
                                        $residence->reviews->avg('location_rating') ?? $residence->average_rating,
                                    'Qualité/prix' =>
                                        $residence->reviews->avg('value_rating') ?? $residence->average_rating,
                                ];
                            @endphp
                            <div
                                class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-6 pb-8 border-b border-gray-200">
                                @foreach ($cats as $label => $score)
                                    <div class="text-center">
                                        <div class="text-xs text-gray-500 mb-2">{{ $label }}</div>
                                        <div class="text-lg font-bold text-gray-900">{{ number_format($score ?? 0, 1) }}
                                        </div>
                                        <div class="rating-bar mt-2">
                                            <div class="rating-bar-fill"
                                                style="width: {{ min(100, (($score ?? 0) / 5) * 100) }}%"></div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-16 gap-y-10 mt-8">
                                @foreach ($residence->reviews->take(6) as $review)
                                    <div>
                                        <div class="flex items-center gap-3 mb-3">
                                            @if ($review->user->avatar ?? false)
                                                <img loading="lazy" src="{{ storage_url($review->user->avatar) }}"
                                                    alt="{{ $review->user->name }}"
                                                    class="w-10 h-10 rounded-full object-cover">
                                            @else
                                                <div
                                                    class="w-10 h-10 rounded-full bg-linear-to-br from-[#FF8A1F] to-[#CC5A00] flex items-center justify-center text-white font-semibold">
                                                    {{ substr($review->user->name ?? 'A', 0, 1) }}</div>
                                            @endif
                                            <div>
                                                <p class="font-semibold text-gray-900 text-sm">
                                                    {{ $review->user->name ?? 'Anonyme' }}</p>
                                                <p class="text-gray-400 text-xs flex items-center gap-0.5">
                                                    @for ($s = 0; $s < 5; $s++)
                                                        <svg aria-hidden="true"
                                                            class="w-2.5 h-2.5 {{ $s < ($review->rating ?? 5) ? 'text-amber-400' : 'text-gray-300' }}"
                                                            fill="currentColor" viewBox="0 0 20 20">
                                                            <path
                                                                d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                                        </svg>
                                                    @endfor
                                                    <span class="ml-1">·
                                                        {{ $review->stay_end_date?->translatedFormat('F Y') ?? $review->created_at->translatedFormat('F Y') }}</span>
                                                </p>
                                            </div>
                                        </div>
                                        <p class="text-gray-600 text-[15px] leading-relaxed line-clamp-4">
                                            {{ $review->comment }}</p>
                                        @if (strlen($review->comment ?? '') > 200)
                                            <button
                                                class="mt-2 text-sm font-semibold text-gray-900 underline hover:no-underline">Lire
                                                la suite</button>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                            @if ($residence->reviews_count > 6)
                                <button
                                    class="mt-8 px-6 py-3 border border-gray-900 rounded-lg text-sm font-semibold text-gray-900 hover:bg-gray-50 transition">
                                    Afficher les {{ $residence->reviews_count }} commentaires
                                </button>
                            @endif
                        @else
                            <h2 class="text-[22px] font-semibold text-gray-900 mb-3">Aucun avis pour le moment</h2>
                            <p class="text-gray-500 text-sm">Soyez le premier à partager votre expérience.</p>
                        @endif
                    </div>

                    {{-- Map --}}
                    <div id="emplacement" class="py-8 border-b border-gray-200">
                        <h2 class="text-[22px] font-semibold text-gray-900 mb-2">Où se situe le logement</h2>
                        <p class="text-gray-500 text-sm mb-5">
                            {{ collect([$residence->commune, $residence->city])->filter()->implode(', ') }}</p>
                        <div class="h-95 bg-gray-100 rounded-xl overflow-hidden">
                            @if ($residence->latitude && $residence->longitude)
                                <div id="map" class="w-full h-full"></div>
                            @else
                                <div class="w-full h-full flex items-center justify-center text-gray-300">
                                    <div class="text-center">
                                        <svg aria-hidden="true" class="w-12 h-12 mx-auto mb-2" fill="none"
                                            stroke="currentColor" stroke-width="1" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
                                        </svg>
                                        <p class="font-medium text-sm">Carte non disponible</p>
                                    </div>
                                </div>
                            @endif
                        </div>

                        {{-- Itinéraire interactif --}}
                        @if ($residence->latitude && $residence->longitude)
                            <div x-data="directionsWidget({{ $residence->id }}, '/api/v1/maps')" class="mt-4">
                                <button @click="getDirections()" :disabled="loading"
                                    class="inline-flex items-center gap-2 px-4 py-2.5 bg-gray-900 text-white rounded-lg text-sm font-medium hover:bg-gray-800 transition disabled:opacity-50">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/>
                                    </svg>
                                    <span x-text="loading ? 'Calcul en cours...' : 'Comment s\'y rendre'"></span>
                                </button>
                                {{-- Mode selector --}}
                                <div x-show="result" x-cloak class="mt-3 flex gap-2">
                                    <template x-for="m in modes">
                                        <button @click="mode = m.value; getDirections()"
                                            :class="mode === m.value ? 'bg-gray-900 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                                            class="px-3 py-1.5 rounded-full text-xs font-medium transition">
                                            <span x-text="m.icon + ' ' + m.label"></span>
                                        </button>
                                    </template>
                                </div>
                                {{-- Result --}}
                                <div x-show="result" x-cloak class="mt-3 bg-gray-50 rounded-xl p-4">
                                    <div class="flex items-center gap-4">
                                        <div class="text-center">
                                            <p class="text-2xl font-bold text-gray-900" x-text="(result && result.duration) ? result.duration.text : '—'"></p>
                                            <p class="text-xs text-gray-500" x-text="(result && result.distance) ? result.distance.text : ''"></p>
                                        </div>
                                        <div class="flex-1 text-sm text-gray-600">
                                            <p>Depuis : <span x-text="(result && result.start_address) ? result.start_address : 'Votre position'" class="font-medium"></span></p>
                                            <p>Vers : <span x-text="(result && result.end_address) ? result.end_address : ''" class="font-medium"></span></p>
                                        </div>
                                    </div>
                                    {{-- Steps --}}
                                    <div x-show="showSteps" x-cloak class="mt-3 space-y-1 border-t border-gray-200 pt-3">
                                        <template x-for="step in (result && result.steps ? result.steps : [])" :key="step.instruction">
                                            <div class="flex items-start gap-2 text-xs text-gray-600">
                                                <span class="text-gray-400 mt-0.5">→</span>
                                                <span x-text="step.instruction + ' (' + step.distance + ')'"></span>
                                            </div>
                                        </template>
                                    </div>
                                    <button @click="showSteps = !showSteps" class="mt-2 text-xs text-blue-600 hover:underline"
                                        x-text="showSteps ? 'Masquer les étapes' : 'Voir les étapes détaillées'"></button>
                                </div>
                                <p x-show="error" x-cloak x-text="error" class="mt-2 text-sm text-red-600"></p>
                            </div>
                        @endif
                    </div>

                    {{-- Points d'intérêt à proximité --}}
                    @if ($residence->latitude && $residence->longitude)
                        <div id="proximite" class="py-8 border-b border-gray-200" x-data="nearbyPOI({{ $residence->id }}, '/api/v1/maps')">
                            <h2 class="text-[22px] font-semibold text-gray-900 mb-2">À proximité</h2>
                            <p class="text-gray-500 text-sm mb-5">Ce qui se trouve autour de ce logement</p>

                            {{-- Loading --}}
                            <div x-show="loading" class="grid grid-cols-2 md:grid-cols-3 gap-3">
                                <template x-for="i in 6">
                                    <div class="bg-gray-100 animate-pulse rounded-xl h-24"></div>
                                </template>
                            </div>

                            {{-- POI Grid --}}
                            <div x-show="!loading && groups.length > 0" x-cloak class="grid grid-cols-2 md:grid-cols-3 gap-3">
                                <template x-for="group in groups" :key="group.type">
                                    <div class="bg-gray-50 rounded-xl p-4 hover:bg-gray-100 transition cursor-pointer"
                                         @click="expanded === group.type ? expanded = null : expanded = group.type">
                                        <div class="flex items-center gap-2 mb-2">
                                            <span class="text-xl" x-text="group.icon"></span>
                                            <span class="font-medium text-sm text-gray-900" x-text="group.label"></span>
                                            <span class="ml-auto text-xs text-gray-400 bg-gray-200 px-1.5 py-0.5 rounded-full" x-text="group.count"></span>
                                        </div>
                                        {{-- Premier résultat --}}
                                        <p class="text-xs text-gray-500 truncate" x-text="(group.places && group.places[0]) ? group.places[0].name + ' · ' + group.places[0].distance : ''"></p>

                                        {{-- Expanded details --}}
                                        <div x-show="expanded === group.type" x-cloak class="mt-2 space-y-1.5 border-t border-gray-200 pt-2">
                                            <template x-for="place in group.places" :key="place.name">
                                                <div class="flex items-center justify-between text-xs">
                                                    <span class="text-gray-700 truncate flex-1" x-text="place.name"></span>
                                                    <span class="text-gray-400 ml-2 whitespace-nowrap" x-text="place.walking_time"></span>
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                </template>
                            </div>

                            {{-- Empty state --}}
                            <div x-show="!loading && groups.length === 0" x-cloak class="text-center py-6 text-gray-400 text-sm">
                                Aucun point d'intérêt trouvé à proximité
                            </div>
                        </div>
                    @endif

                    {{-- Street View --}}
                    @if ($residence->latitude && $residence->longitude)
                        <div id="streetview" class="py-8 border-b border-gray-200" x-data="streetViewWidget({{ $residence->id }}, '/api/v1/maps')">
                            <template x-if="available">
                                <div>
                                    <h2 class="text-[22px] font-semibold text-gray-900 mb-2">Vue du quartier</h2>
                                    <p class="text-gray-500 text-sm mb-5">Explorez les environs en Street View</p>
                                    <div class="rounded-xl overflow-hidden shadow-sm">
                                        <img :src="imageUrl" alt="Street View" class="w-full h-64 md:h-80 object-cover" loading="lazy">
                                    </div>
                                    {{-- Panorama 4 directions --}}
                                    <div x-show="panorama.length > 0" class="grid grid-cols-4 gap-2 mt-2">
                                        <template x-for="(view, idx) in panorama" :key="idx">
                                            <img :src="view.url" :alt="'Vue ' + view.heading + '°'"
                                                class="rounded-lg h-20 w-full object-cover cursor-pointer hover:opacity-80 transition"
                                                @click="imageUrl = view.url" loading="lazy">
                                        </template>
                                    </div>
                                </div>
                            </template>
                        </div>
                    @endif

                    {{-- Zones accessibles (Isochrone) --}}
                    @if ($residence->latitude && $residence->longitude)
                        <div id="isochrone" class="py-8 border-b border-gray-200" x-data="isochroneWidget({{ $residence->id }}, '/api/v1/maps', {{ $residence->latitude }}, {{ $residence->longitude }}, {{ Js::encode($residence->title) }})">
                            <h2 class="text-[22px] font-semibold text-gray-900 mb-2">Zones accessibles</h2>
                            <p class="text-gray-500 text-sm mb-4">Tout ce qui est accessible à pied depuis ce logement</p>

                            {{-- Profile toggles --}}
                            <div class="flex gap-2 mb-4">
                                <template x-for="p in profiles">
                                    <button @click="profile = p.value; fetchIsochrone()"
                                        :class="profile === p.value ? 'bg-gray-900 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                                        class="px-3 py-1.5 rounded-full text-xs font-medium transition">
                                        <span x-text="p.icon + ' ' + p.label"></span>
                                    </button>
                                </template>
                            </div>

                            {{-- Legend --}}
                            <div x-show="data" x-cloak class="flex gap-4 mb-3 text-xs text-gray-600">
                                <span class="flex items-center gap-1"><span class="w-3 h-3 rounded bg-green-300/60"></span> 5 min</span>
                                <span class="flex items-center gap-1"><span class="w-3 h-3 rounded bg-yellow-300/60"></span> 10 min</span>
                                <span class="flex items-center gap-1"><span class="w-3 h-3 rounded bg-red-300/60"></span> 15 min</span>
                            </div>

                            {{-- Map container for isochrone overlay --}}
                            <div x-show="data" x-cloak>
                                <div x-ref="isochroneMap" class="w-full h-72 rounded-xl bg-gray-100"></div>
                            </div>

                            <div x-show="loading" class="h-72 bg-gray-100 animate-pulse rounded-xl"></div>
                            <p x-show="error" x-cloak x-text="error" class="text-sm text-red-600 mt-2"></p>
                        </div>
                    @endif

                    {{-- Host Profile — layout Airbnb --}}
                    <div id="hote" class="py-10 border-b border-gray-200">
                        <h2 class="text-[22px] font-semibold text-gray-900 mb-8">Faites connaissance avec votre hôte</h2>

                        <div class="flex flex-col md:flex-row gap-10">

                            {{-- Colonne gauche : carte hôte --}}
                            <div class="md:w-64 shrink-0">
                                <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6 text-center">
                                    @php
                                        $ownerAvatarLarge = $residence->owner?->getAvatarUrl() ?? '';
                                        $monthsOnRezi = (int) ($residence->owner?->created_at?->diffInMonths(now()) ?? 0) ?: 1;
                                    @endphp

                                    {{-- Photo + badge vérifié --}}
                                    <div class="relative inline-block mb-4">
                                        @if ($ownerAvatarLarge)
                                            <img loading="lazy" src="{{ $ownerAvatarLarge }}"
                                                alt="{{ $residence->owner->name }}"
                                                class="w-28 h-28 rounded-full object-cover ring-2 ring-gray-100">
                                        @else
                                            <div class="w-28 h-28 rounded-full bg-gray-900 flex items-center justify-center text-white text-4xl font-bold">
                                                {{ substr($residence->owner->name ?? 'H', 0, 1) }}
                                            </div>
                                        @endif
                                        @if ($residence->owner?->identity_verified)
                                            <div class="absolute bottom-1 right-1 w-7 h-7 bg-rose-500 rounded-full flex items-center justify-center ring-2 ring-white">
                                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                                                </svg>
                                            </div>
                                        @endif
                                    </div>

                                    {{-- Nom + Superhôte --}}
                                    <h3 class="text-2xl font-bold text-gray-900 leading-tight">{{ $residence->owner->name ?? 'Hôte' }}</h3>
                                    @if (!empty($isSuperhost))
                                        <p class="text-sm text-gray-500 mt-0.5 flex items-center justify-center gap-1">
                                            <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l2.39 7.36H22l-6.19 4.5 2.39 8.14L12 17.27 5.8 22l2.39-8.14L2 9.36h7.61z"/></svg>
                                            Superhôte
                                        </p>
                                    @endif

                                    <div class="border-t border-gray-100 my-4"></div>

                                    {{-- Stats verticaux (comme Airbnb) --}}
                                    <div class="space-y-3 text-left">
                                        @if ($residence->reviews_count > 0)
                                            <div>
                                                <p class="text-xl font-bold text-gray-900">{{ $residence->reviews_count }}</p>
                                                <p class="text-xs text-gray-500">Évaluation{{ $residence->reviews_count > 1 ? 's' : '' }}</p>
                                            </div>
                                            <div class="border-t border-gray-100"></div>
                                            <div>
                                                <p class="text-xl font-bold text-gray-900">{{ number_format($residence->average_rating, 2) }}<span class="text-amber-400">★</span></p>
                                                <p class="text-xs text-gray-500">Note globale</p>
                                            </div>
                                            <div class="border-t border-gray-100"></div>
                                        @endif
                                        <div>
                                            <p class="text-xl font-bold text-gray-900">{{ $monthsOnRezi }}</p>
                                            <p class="text-xs text-gray-500">Mois d'expérience en tant qu'hôte</p>
                                        </div>
                                    </div>
                                </div>

                                {{-- Infos sous la carte --}}
                                <div class="mt-4 space-y-2 text-sm text-gray-700 px-1">
                                    @if ($residence->owner?->languages)
                                        @php $langs = is_array($residence->owner->languages) ? implode(', ', $residence->owner->languages) : $residence->owner->languages; @endphp
                                        @if ($langs)
                                            <p class="flex items-start gap-2">
                                                <svg class="w-4 h-4 mt-0.5 shrink-0 text-gray-500" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 008.716-6.747M12 21a9.004 9.004 0 01-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3"/></svg>
                                                Langues : {{ $langs }}
                                            </p>
                                        @endif
                                    @endif
                                    @if ($residence->owner?->city)
                                        <p class="flex items-start gap-2">
                                            <svg class="w-4 h-4 mt-0.5 shrink-0 text-gray-500" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z"/></svg>
                                            Je vis à : {{ $residence->owner->city }}
                                        </p>
                                    @endif
                                </div>
                            </div>

                            {{-- Colonne droite : infos + contact --}}
                            <div class="flex-1 min-w-0">

                                @if (!empty($isSuperhost))
                                    <div class="mb-6">
                                        <h3 class="text-lg font-semibold text-gray-900 mb-2">{{ $residence->owner->name ?? 'Votre hôte' }} est Superhôte</h3>
                                        <p class="text-gray-600 text-[15px] leading-relaxed">Les Superhôtes sont des hôtes expérimentés qui bénéficient de très bonnes évaluations et qui s'engagent à offrir d'excellents séjours aux voyageurs.</p>
                                    </div>
                                @endif

                                <div class="mb-6">
                                    <h3 class="text-base font-semibold text-gray-900 mb-3">Informations sur l'hôte</h3>
                                    <div class="space-y-1 text-[15px] text-gray-700">
                                        @if (!is_null($responseRate ?? null))
                                            <p>Taux de réponse : <strong>{{ (int) $responseRate }} %</strong></p>
                                        @endif
                                        @if (!empty($avgResponseTime))
                                            @if ($avgResponseTime < 1)
                                                <p>Répond <strong>dans l'heure</strong></p>
                                            @elseif ($avgResponseTime <= 3)
                                                <p>Répond <strong>en {{ (int) $avgResponseTime }}h</strong></p>
                                            @elseif ($avgResponseTime <= 24)
                                                <p>Répond <strong>dans la journée</strong></p>
                                            @else
                                                <p>Répond <strong>en {{ (int) round($avgResponseTime / 24) }} jour(s)</strong></p>
                                            @endif
                                        @endif
                                    </div>
                                </div>

                                @auth
                                    @if (auth()->id() !== $residence->owner_id)
                                        @if ($canContact)
                                            <div x-data="{ showMsgForm: false, message: '' }" class="mb-6">
                                                <button @click="showMsgForm = !showMsgForm"
                                                    class="inline-flex items-center px-6 py-3.5 bg-gray-900 text-white rounded-xl text-sm font-semibold hover:bg-gray-800 transition">
                                                    Envoyer un message à l'hôte
                                                </button>
                                                <form x-show="showMsgForm" x-transition x-cloak
                                                    action="{{ route('chat.start') }}" method="POST" class="mt-4 space-y-3">
                                                    @csrf
                                                    <input type="hidden" name="residence_id" value="{{ $residence->id }}">
                                                    <textarea x-model="message" name="message" rows="3" required
                                                        class="w-full rounded-lg border-gray-300 text-sm focus:border-[#F16A00] focus:ring-[#F16A00]"
                                                        placeholder="Bonjour, je suis intéressé(e) par votre résidence..."></textarea>
                                                    <button type="submit" :disabled="!message.trim()"
                                                        class="px-5 py-2.5 bg-[#F16A00] text-white rounded-lg text-sm font-semibold hover:bg-[#CC5A00] transition disabled:opacity-50 disabled:cursor-not-allowed">
                                                        Envoyer
                                                    </button>
                                                </form>
                                            </div>
                                        @else
                                            <div class="mb-6 p-4 bg-[#FFF4EB] border border-[#FFE7D1] rounded-xl">
                                                <p class="text-sm text-[#8e0730] font-medium mb-1">Réservation requise</p>
                                                <p class="text-sm text-[#A34700] mb-3">Pour contacter le propriétaire, vous devez d'abord effectuer une réservation.</p>
                                                <a href="{{ route('bookings.create', $residence) }}"
                                                    class="inline-flex items-center px-5 py-2.5 bg-[#F16A00] hover:bg-[#CC5A00] text-white text-sm font-semibold rounded-lg transition">
                                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                                    Réserver maintenant
                                                </a>
                                            </div>
                                        @endif
                                    @endif
                                @else
                                    <div class="mb-6">
                                        <a href="{{ route('login') }}"
                                            class="inline-flex items-center px-6 py-3.5 bg-gray-900 text-white rounded-xl text-sm font-semibold hover:bg-gray-800 transition">
                                            Envoyer un message à l'hôte
                                        </a>
                                    </div>
                                @endauth

                                <div class="border-t border-gray-100 pt-5">
                                    <div class="flex items-start gap-3">
                                        <svg class="w-8 h-8 shrink-0 text-rose-400 mt-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z"/>
                                        </svg>
                                        <p class="text-sm text-gray-500 leading-relaxed">Pour protéger votre paiement, ne transférez jamais d'argent et ne communiquez pas en dehors de Rezi Studio Meublé Faya.</p>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>

                    {{-- House Rules --}}
                    <div id="regles" class="py-8">
                        <h2 class="text-[22px] font-semibold text-gray-900 mb-6">À savoir</h2>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-8">
                            <div>
                                <h3 class="font-semibold text-gray-900 mb-3">Règlement intérieur</h3>
                                <ul class="space-y-3 text-sm text-gray-600">
                                    <li>Arrivée : à partir de {{ $residence->check_in_time ?? '14h00' }}</li>
                                    <li>Départ : avant {{ $residence->check_out_time ?? '12h00' }}</li>
                                    <li>{{ $residence->max_guests ?? 4 }} voyageurs maximum</li>
                                    @if ($residence->house_rules)
                                        @php
                                            $rules = $residence->house_rules;
                                            if (is_string($rules)) {
                                                $rules = array_filter(array_map('trim', explode("\n", $rules)));
                                            } elseif (!is_array($rules)) {
                                                $rules = [];
                                            }
                                        @endphp
                                        @foreach (array_slice($rules, 0, 3) as $rule)
                                            <li>{{ $rule }}</li>
                                        @endforeach
                                    @endif
                                </ul>
                            </div>
                            <div>
                                <h3 class="font-semibold text-gray-900 mb-3">Santé et sécurité</h3>
                                <ul class="space-y-3 text-sm text-gray-600">
                                    <li>Détecteur de fumée</li>
                                    <li>Détecteur de monoxyde de carbone</li>
                                    <li>Trousse de premiers secours</li>
                                </ul>
                            </div>
                            <div>
                                <h3 class="font-semibold text-gray-900 mb-3">Conditions d'annulation</h3>
                                <ul class="space-y-3 text-sm text-gray-600">
                                    <li>Gratuite pendant 48h</li>
                                    <li>Consultez les détails complets de la politique d'annulation</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                </div>

                {{-- ─── RIGHT COLUMN (Sticky Booking) ─── --}}
                <div class="hidden lg:block lg:w-92.5 shrink-0">
                    <div class="booking-card" x-data="bookingForm({{ \Illuminate\Support\Js::encode([
    'pricePerNight' => $pricePerNight,
    'pricePerWeek' => $residence->price_per_week ?? 0,
    'pricePerMonth' => $residence->price_per_month ?? 0,
    'maxGuests' => $residence->max_guests ?? 10,
    'minNights' => $residence->min_nights ?? 1,
    'maxNights' => $residence->max_nights ?? 365,
    'instantBook' => (bool) $residence->instant_book,
    'residenceId' => $residence->id,
    'unavailableDates' => $unavailableDates ?? [],
    'cleaningFee' => $residence->cleaning_fee ?? 0,
    'stateTax' => (int) config('rezi.pricing.state_tax', 1000),
    'isAuthenticated' => auth()->check(),
]) }})">

                        {{-- Prix principal --}}
                        <div class="flex items-baseline gap-1.5 mb-1">
                            @if ($displayPrice > 0)
                                <span class="stat-number text-3xl">{{ number_format($displayPrice, 0, ',', ' ') }}</span>
                                <span class="text-gray-600 font-semibold text-sm">FCFA</span>
                                <span class="text-gray-400 text-sm">{{ $priceLabel }}</span>
                            @else
                                <span class="text-xl font-semibold text-gray-900">Prix sur demande</span>
                            @endif
                        </div>

                        {{-- Autres tarifs disponibles --}}
                        @if (($residence->price_per_day ?? 0) > 0)
                            <div class="flex flex-wrap gap-2 mb-3">
                                    <span class="text-xs text-gray-400 bg-gray-50 px-2 py-0.5 rounded-full">
                                        {{ number_format($residence->price_per_day, 0, ',', ' ') }} / jour
                                    </span>
                                @if (($residence->price_per_week ?? 0) > 0)
                                    <span class="text-xs text-gray-400 bg-gray-50 px-2 py-0.5 rounded-full">
                                        {{ number_format($residence->price_per_week, 0, ',', ' ') }} / sem.
                                    </span>
                                @endif
                            </div>
                        @endif

                        {{-- Note + Avis --}}
                        @if ($residence->reviews_count > 0)
                            <div class="flex items-center gap-1.5 mb-5 text-sm">
                                <svg aria-hidden="true" class="w-4 h-4 text-amber-400" fill="currentColor"
                                    viewBox="0 0 20 20">
                                    <path
                                        d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                </svg>
                                <span class="font-bold text-gray-900">{{ number_format($residence->average_rating, 1) }}</span>
                                <span class="text-gray-300">·</span>
                                <a href="#avis" class="underline text-gray-500 hover:text-[#CC5A00]">
                                    {{ $residence->reviews_count }}
                                    commentaire{{ $residence->reviews_count > 1 ? 's' : '' }}
                                </a>
                            </div>
                        @else
                            <div class="mb-5"></div>
                        @endif

                        {{-- Formulaire de réservation --}}
                        <form action="{{ route('bookings.create', $residence) }}" method="GET"
                            @submit.prevent="canSubmit && $el.submit()">

                            {{-- Dates ARRIVÉE / DÉPART --}}
                            <div class="border border-gray-300 rounded-xl mb-4 transition-all"
                                :class="{
                                    'overflow-hidden': !showGuestPicker,
                                    'border-red-400 ring-1 ring-red-400': available === false
                                }">
                                <div class="grid grid-cols-2">
                                    <div
                                        class="p-3 border-r border-gray-300 cursor-pointer hover:bg-gray-50 transition-colors">
                                        <label for="check_in"
                                            class="block text-xs font-bold uppercase tracking-wider text-gray-700">Arrivée</label>
                                        <input type="date" id="check_in" name="check_in" x-model="checkIn"
                                            class="w-full border-0 p-0 mt-0.5 focus:ring-0 text-sm text-gray-900 bg-transparent cursor-pointer font-medium"
                                            :min="todayStr" required>
                                    </div>
                                    <div class="p-3 cursor-pointer hover:bg-gray-50 transition-colors">
                                        <label for="check_out"
                                            class="block text-xs font-bold uppercase tracking-wider text-gray-700">Départ</label>
                                        <input type="date" id="check_out" name="check_out" x-model="checkOut"
                                            class="w-full border-0 p-0 mt-0.5 focus:ring-0 text-sm text-gray-900 bg-transparent cursor-pointer font-medium"
                                            :min="checkIn || todayStr" required>
                                    </div>
                                </div>

                                {{-- Voyageurs --}}
                                <div class="border-t border-gray-300 relative">
                                    <button type="button" class="w-full p-3 text-left cursor-pointer hover:bg-gray-50 transition-colors"
                                        @click="showGuestPicker = !showGuestPicker" @keydown.enter.prevent="showGuestPicker = !showGuestPicker"
                                        @keydown.space.prevent="showGuestPicker = !showGuestPicker" x-ref="guestTrigger"
                                        :aria-expanded="showGuestPicker" aria-labelledby="label-voyageurs">
                                        <span id="label-voyageurs"
                                            class="block text-xs font-bold uppercase tracking-wider text-gray-700">Voyageurs</span>
                                        <div class="flex items-center justify-between mt-0.5">
                                            <span class="text-sm text-gray-900 font-medium" x-text="guestLabel"></span>
                                            <svg aria-hidden="true" class="w-4 h-4 text-gray-500 transition-transform"
                                                :class="showGuestPicker && 'rotate-180'" fill="none"
                                                stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M19 9l-7 7-7-7" />
                                            </svg>
                                        </div>
                                    </button>
                                    <input type="hidden" name="guests" :value="totalGuests">

                                    {{-- Guest Picker Dropdown --}}
                                    <div x-show="showGuestPicker" x-transition.origin.top x-ref="guestPicker"
                                        @click.outside="showGuestPicker = false"
                                        class="absolute left-0 right-0 top-full bg-white border border-gray-200 rounded-b-xl shadow-lg z-20 p-4 space-y-4"
                                        x-cloak>
                                        {{-- Adultes --}}
                                        <div class="flex items-center justify-between">
                                            <div>
                                                <p class="text-sm font-medium text-gray-900">Adultes</p>
                                                <p class="text-xs text-gray-400">13 ans et plus</p>
                                            </div>
                                            <div class="flex items-center gap-3">
                                                <button type="button" @click="decrementGuest('adults')"
                                                    class="w-8 h-8 rounded-full border border-gray-300 flex items-center justify-center text-gray-500 hover:border-gray-900 hover:text-gray-900 transition disabled:opacity-30 disabled:cursor-not-allowed"
                                                    :disabled="adults <= 1">
                                                    <svg aria-hidden="true" class="w-3.5 h-3.5" fill="none"
                                                        stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                        <path d="M20 12H4" />
                                                    </svg>
                                                </button>
                                                <span class="w-5 text-center text-sm font-medium" x-text="adults"></span>
                                                <button type="button" @click="incrementGuest('adults')"
                                                    class="w-8 h-8 rounded-full border border-gray-300 flex items-center justify-center text-gray-500 hover:border-gray-900 hover:text-gray-900 transition disabled:opacity-30 disabled:cursor-not-allowed"
                                                    :disabled="adults + children >= maxGuests">
                                                    <svg aria-hidden="true" class="w-3.5 h-3.5" fill="none"
                                                        stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                        <path d="M12 4v16m8-8H4" />
                                                    </svg>
                                                </button>
                                            </div>
                                        </div>
                                        {{-- Enfants --}}
                                        <div class="flex items-center justify-between">
                                            <div>
                                                <p class="text-sm font-medium text-gray-900">Enfants</p>
                                                <p class="text-xs text-gray-400">2 – 12 ans</p>
                                            </div>
                                            <div class="flex items-center gap-3">
                                                <button type="button" @click="decrementGuest('children')"
                                                    class="w-8 h-8 rounded-full border border-gray-300 flex items-center justify-center text-gray-500 hover:border-gray-900 hover:text-gray-900 transition disabled:opacity-30 disabled:cursor-not-allowed"
                                                    :disabled="children <= 0">
                                                    <svg aria-hidden="true" class="w-3.5 h-3.5" fill="none"
                                                        stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                        <path d="M20 12H4" />
                                                    </svg>
                                                </button>
                                                <span class="w-5 text-center text-sm font-medium"
                                                    x-text="children"></span>
                                                <button type="button" @click="incrementGuest('children')"
                                                    class="w-8 h-8 rounded-full border border-gray-300 flex items-center justify-center text-gray-500 hover:border-gray-900 hover:text-gray-900 transition disabled:opacity-30 disabled:cursor-not-allowed"
                                                    :disabled="adults + children >= maxGuests">
                                                    <svg aria-hidden="true" class="w-3.5 h-3.5" fill="none"
                                                        stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                        <path d="M12 4v16m8-8H4" />
                                                    </svg>
                                                </button>
                                            </div>
                                        </div>
                                        {{-- Bébés --}}
                                        <div class="flex items-center justify-between">
                                            <div>
                                                <p class="text-sm font-medium text-gray-900">Bébés</p>
                                                <p class="text-xs text-gray-400">- de 2 ans</p>
                                            </div>
                                            <div class="flex items-center gap-3">
                                                <button type="button" @click="decrementGuest('infants')"
                                                    class="w-8 h-8 rounded-full border border-gray-300 flex items-center justify-center text-gray-500 hover:border-gray-900 hover:text-gray-900 transition disabled:opacity-30 disabled:cursor-not-allowed"
                                                    :disabled="infants <= 0">
                                                    <svg aria-hidden="true" class="w-3.5 h-3.5" fill="none"
                                                        stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                        <path d="M20 12H4" />
                                                    </svg>
                                                </button>
                                                <span class="w-5 text-center text-sm font-medium" x-text="infants"></span>
                                                <button type="button" @click="incrementGuest('infants')"
                                                    class="w-8 h-8 rounded-full border border-gray-300 flex items-center justify-center text-gray-500 hover:border-gray-900 hover:text-gray-900 transition disabled:opacity-30 disabled:cursor-not-allowed"
                                                    :disabled="infants >= 5">
                                                    <svg aria-hidden="true" class="w-3.5 h-3.5" fill="none"
                                                        stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                        <path d="M12 4v16m8-8H4" />
                                                    </svg>
                                                </button>
                                            </div>
                                        </div>
                                        <p class="text-xs text-gray-400 pt-1">Ce logement accepte
                                            {{ $residence->max_guests ?? 10 }} voyageurs maximum, bébés non comptés.</p>
                                        <button type="button" @click="showGuestPicker = false"
                                            class="w-full text-sm font-semibold text-gray-900 underline hover:no-underline text-right">
                                            Fermer
                                        </button>
                                    </div>
                                </div>
                            </div>

                            {{-- Erreurs de validation --}}
                            <template x-if="minNightsError">
                                <p class="text-xs text-red-500 mb-3 flex items-center gap-1" x-text="minNightsError">
                                </p>
                            </template>
                            <template x-if="maxNightsError">
                                <p class="text-xs text-red-500 mb-3 flex items-center gap-1" x-text="maxNightsError">
                                </p>
                            </template>

                            {{-- Statut de disponibilité --}}
                            <div x-show="checking" class="flex items-center gap-2 mb-3 text-sm text-gray-500" x-cloak>
                                <svg aria-hidden="true" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10"
                                        stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor"
                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                </svg>
                                Vérification de la disponibilité…
                            </div>
                            <div x-show="available === false && !checking"
                                class="mb-3 p-3 bg-red-50 border border-red-100 rounded-lg" x-cloak>
                                <p class="text-sm text-red-600 font-medium flex items-center gap-2">
                                    <svg aria-hidden="true" class="w-4 h-4 shrink-0" fill="none"
                                        stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <span x-text="availabilityMessage || 'Ces dates ne sont pas disponibles'"></span>
                                </p>
                            </div>
                            <div x-show="available === true && nights > 0 && !checking"
                                class="mb-3 p-2.5 bg-green-50 border border-green-100 rounded-lg" x-cloak>
                                <p class="text-sm text-green-700 font-medium flex items-center gap-2">
                                    <svg aria-hidden="true" class="w-4 h-4 shrink-0" fill="none"
                                        stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M5 13l4 4L19 7" />
                                    </svg>
                                    Disponible — <span x-text="nightsLabel"></span>
                                </p>
                            </div>

                            {{-- Bouton Réserver --}}
                            @auth
                                <button type="submit"
                                    class="w-full py-3.5 rounded-xl font-semibold text-base transition-all duration-200 relative overflow-hidden"
                                    :class="canSubmit
                                        ?
                                        'btn-premium text-white' :
                                        'bg-gray-200 text-gray-400 cursor-not-allowed'"
                                    :disabled="!canSubmit">
                                    <span x-show="!loading && !checking">
                                        {{ $residence->instant_book ? 'Réserver maintenant' : 'Demander une réservation' }}
                                    </span>
                                    <span x-show="loading || checking" class="flex items-center justify-center gap-2" x-cloak>
                                        <svg aria-hidden="true" class="w-5 h-5 animate-spin" fill="none"
                                            viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10"
                                                stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor"
                                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                        </svg>
                                        Vérification…
                                    </span>
                                </button>
                            @else
                                <a href="{{ route('login', ['redirect' => url()->current()]) }}"
                                    class="btn-premium w-full py-3.5 rounded-xl font-semibold text-base text-white active:scale-[0.98] transition-all duration-200 block text-center">
                                    Connectez-vous pour réserver
                                </a>
                            @endauth

                            <p class="text-center text-gray-400 text-xs mt-3">Aucun montant ne vous sera débité pour le
                                moment</p>

                            {{-- Détail du prix --}}
                            <div class="mt-5 space-y-3 overflow-hidden" x-show="nights > 0 && unitPrice.perNight > 0"
                                x-transition x-cloak>
                                {{-- Sous-total nuits --}}
                                <div class="flex justify-between text-sm text-gray-600">
                                    <button type="button"
                                        class="underline text-left hover:text-gray-900 transition-colors"
                                        @click="showPriceBreakdown = !showPriceBreakdown">
                                        <span x-text="formatPrice(unitPrice.perNight)"></span> × <span
                                            x-text="nights"></span> nuit<span x-show="nights > 1">s</span>
                                    </button>
                                    <span x-text="formatPrice(subtotal)"></span>
                                </div>

                                {{-- Remise durée --}}
                                <template x-if="discount > 0">
                                    <div class="flex justify-between text-sm text-green-600">
                                        <span class="underline">Remise longue durée</span>
                                        <span>-<span x-text="formatPrice(discount)"></span></span>
                                    </div>
                                </template>

                                {{-- Frais de ménage --}}
                                <template x-if="totalCleaningFee > 0">
                                    <div class="flex justify-between text-sm text-gray-600">
                                        <span class="underline">Frais de ménage</span>
                                        <span x-text="formatPrice(totalCleaningFee)"></span>
                                    </div>
                                </template>

                                {{-- Taxe d'État --}}
                                <template x-if="stateTax > 0">
                                    <div class="flex justify-between text-sm text-gray-600">
                                        <span class="underline">Taxe d'État</span>
                                        <span x-text="formatPrice(stateTax)"></span>
                                    </div>
                                </template>

                                {{-- Total --}}
                                <div
                                    class="pt-4 border-t border-gray-200 flex justify-between font-semibold text-gray-900">
                                    <span>Total</span>
                                    <span x-text="formatPrice(total)"></span>
                                </div>
                            </div>

                            {{-- Code promo --}}
                            <div x-show="nights > 0" class="mt-4 pt-4 border-t border-gray-100" x-cloak>
                                <div class="flex gap-2">
                                    <input type="text" x-model="promoCode" placeholder="Code promo"
                                        class="flex-1 text-sm border border-gray-200 rounded-lg px-3 py-2 focus:border-gray-900 focus:ring-1 focus:ring-gray-900 transition"
                                        @keydown.enter.prevent="applyPromo()">
                                    <button type="button" @click="applyPromo()"
                                        class="px-4 py-2 text-sm font-semibold border border-gray-900 rounded-lg hover:bg-gray-900 hover:text-white transition-all"
                                        :class="promoApplied ? 'bg-green-600 border-green-600 text-white' : ''">
                                        <span x-show="!promoApplied">Appliquer</span>
                                        <span x-show="promoApplied" class="flex items-center gap-1">
                                            <svg aria-hidden="true" class="w-4 h-4" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M5 13l4 4L19 7" />
                                            </svg>
                                            Appliqué
                                        </span>
                                    </button>
                                </div>
                                <p x-show="promoError" class="mt-1 text-xs text-red-500" x-text="promoError" x-cloak></p>
                            </div>
                        </form>

                        {{-- Badge réservation instantanée --}}
                        @if ($residence->instant_book)
                            <div class="mt-4 flex items-center gap-2 text-sm text-gray-500">
                                <svg aria-hidden="true" class="w-4 h-4 text-amber-500" fill="currentColor"
                                    viewBox="0 0 24 24">
                                    <path d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z" />
                                </svg>
                                <span>Réservation instantanée · pas d'attente</span>
                            </div>
                        @endif
                    </div>

                    {{-- Signaler --}}
                    <div class="mt-4 text-center" x-data="{ showReportModal: false, reportSent: false, reportLoading: false }">
                        <button
                            @click="showReportModal = true"
                            class="text-xs text-gray-400 underline hover:text-gray-600 flex items-center gap-1 mx-auto transition-colors">
                            <svg aria-hidden="true" class="w-3.5 h-3.5" fill="none" stroke="currentColor"
                                stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6h-8.5l-1-1H5a2 2 0 00-2 2zm9-13.5V9" />
                            </svg>
                            Signaler cette annonce
                        </button>

                        {{-- Modal de signalement --}}
                        <div x-show="showReportModal" x-cloak
                            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
                            @keydown.escape.window="showReportModal = false">
                            <div @click.outside="showReportModal = false"
                                class="bg-white rounded-2xl shadow-xl max-w-md w-full p-6 text-left">

                                <template x-if="!reportSent">
                                    <div>
                                        <h3 class="text-lg font-bold text-gray-900 mb-1">Signaler cette annonce</h3>
                                        <p class="text-sm text-gray-500 mb-4">Aidez-nous à garder Rezi Studio Meublé Faya sûr. Décrivez le problème rencontré.</p>

                                        <form method="POST" action="{{ route('residences.report', $residence) }}"
                                            @submit.prevent="
                                                reportLoading = true;
                                                fetch($el.action, {
                                                    method: 'POST',
                                                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json', 'Content-Type': 'application/json' },
                                                    body: JSON.stringify({ fraud_type: $el.fraud_type.value, description: $el.description.value })
                                                }).then(r => { reportSent = true; reportLoading = false; }).catch(() => { reportLoading = false; })
                                            ">
                                            <label for="fraud_type" class="block text-sm font-medium text-gray-700 mb-1">Type de problème</label>
                                            <select id="fraud_type" name="fraud_type" required
                                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm mb-3 focus:ring-[#F16A00] focus:border-[#F16A00]">
                                                <option value="">-- Sélectionnez --</option>
                                                <option value="fake_listing">Annonce fictive / fausse</option>
                                                <option value="misleading_photos">Photos trompeuses</option>
                                                <option value="wrong_price">Prix incorrect</option>
                                                <option value="scam">Tentative d'arnaque</option>
                                                <option value="inappropriate_content">Contenu inapproprié</option>
                                                <option value="duplicate">Annonce dupliquée</option>
                                                <option value="other">Autre</option>
                                            </select>

                                            <label for="fraud_description" class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                                            <textarea id="fraud_description" name="description" required rows="3" minlength="10" maxlength="1000"
                                                placeholder="Décrivez le problème en détail..."
                                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm mb-4 focus:ring-[#F16A00] focus:border-[#F16A00]"></textarea>

                                            <div class="flex gap-3 justify-end">
                                                <button type="button" @click="showReportModal = false"
                                                    class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800 transition">Annuler</button>
                                                <button type="submit" :disabled="reportLoading"
                                                    class="px-4 py-2 bg-red-600 text-white text-sm font-semibold rounded-lg hover:bg-red-700 transition disabled:opacity-50">
                                                    <span x-show="!reportLoading">Envoyer le signalement</span>
                                                    <span x-show="reportLoading">Envoi...</span>
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </template>

                                <template x-if="reportSent">
                                    <div class="text-center py-4">
                                        <svg class="w-12 h-12 text-green-500 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                        <h3 class="text-lg font-bold text-gray-900 mb-1">Merci pour votre signalement</h3>
                                        <p class="text-sm text-gray-500 mb-4">Notre équipe examinera cette annonce dans les plus brefs délais.</p>
                                        <button @click="showReportModal = false; reportSent = false"
                                            class="px-4 py-2 bg-gray-100 text-gray-700 text-sm font-semibold rounded-lg hover:bg-gray-200 transition">Fermer</button>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        {{-- ═══════════════════════════════════
         Similar Residences
    ═══════════════════════════════════ --}}
        @if ($similarResidences->isNotEmpty())
            <section class="py-12 bg-gray-50 border-t border-gray-100">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="flex items-end justify-between mb-8">
                        <div>
                            <h2 class="text-xl sm:text-2xl font-bold text-gray-900">Résidences similaires</h2>
                            <p class="mt-1 text-sm text-gray-500">D'autres logements qui pourraient vous intéresser</p>
                        </div>
                        <a href="{{ route('residences.index', ['commune' => $residence->commune]) }}"
                            class="hidden sm:inline-flex items-center gap-1 text-sm font-semibold text-[#F16A00] hover:text-[#CC5A00] transition group">
                            Voir tout à {{ $residence->commune }}
                            <svg aria-hidden="true" class="w-4 h-4 group-hover:translate-x-1 transition-transform"
                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M17 8l4 4m0 0l-4 4m4-4H3" />
                            </svg>
                        </a>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                        @foreach ($similarResidences as $similar)
                            <x-residence-card :residence="$similar" />
                        @endforeach
                    </div>

                    {{-- Mobile link --}}
                    <div class="mt-6 text-center sm:hidden">
                        <a href="{{ route('residences.index', ['commune' => $residence->commune]) }}"
                            class="inline-flex items-center gap-2 text-sm font-semibold text-[#F16A00] hover:text-[#CC5A00] transition">
                            Voir tout à {{ $residence->commune }}
                            <svg aria-hidden="true" class="w-4 h-4" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M17 8l4 4m0 0l-4 4m4-4H3" />
                            </svg>
                        </a>
                    </div>
                </div>
            </section>
        @endif

        {{-- ═══════════════════════════════════
         Mobile Bottom Bar
    ═══════════════════════════════════ --}}
        <div x-data="stickyBookingBar({{ \Illuminate\Support\Js::encode(['bookingBaseUrl' => route('bookings.create', $residence)]) }})"
            x-show="showBar"
            x-transition:enter="transition ease-out duration-300" x-transition:enter-start="translate-y-full opacity-0"
            x-transition:enter-end="translate-y-0 opacity-100" x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="translate-y-0 opacity-100" x-transition:leave-end="translate-y-full opacity-0"
            class="fixed bottom-16 md:bottom-0 left-0 right-0 bg-white border-t border-gray-200 px-4 sm:px-5 pt-3 sm:pt-4 lg:hidden z-50"
            style="padding-bottom: max(0.75rem, env(safe-area-inset-bottom))"
            x-cloak>
            <div class="flex items-center justify-between max-w-lg mx-auto">
                <div>
                    @if ($displayPrice > 0)
                        <div class="flex items-baseline gap-1">
                            <span
                                class="text-base font-semibold text-gray-900">{{ number_format($displayPrice, 0, ',', ' ') }}
                                FCFA</span>
                            <span class="text-gray-500 text-xs">{{ $priceLabel }}</span>
                        </div>
                    @else
                        <span class="text-base font-semibold text-gray-900">Prix sur demande</span>
                    @endif
                    @if ($residence->reviews_count > 0)
                        <div class="flex items-center gap-1 mt-0.5">
                            <svg aria-hidden="true" class="w-3 h-3 text-gray-900" fill="currentColor"
                                viewBox="0 0 20 20">
                                <path
                                    d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                            </svg>
                            <span class="text-xs font-semibold">{{ number_format($residence->average_rating, 1) }}</span>
                            <span class="text-xs text-gray-400">· {{ $residence->reviews_count }} avis</span>
                        </div>
                    @endif
                </div>
                <a :href="bookingUrl"
                    class="px-6 py-3 bg-linear-to-r from-[#F16A00] to-[#CC5A00] text-white rounded-lg font-semibold text-sm active:scale-95 transition-all">
                    Réserver
                </a>
            </div>
        </div>

    </div>
@endsection

@push('scripts')
    @php($cspNonce = \Illuminate\Support\Facades\Vite::cspNonce())
    @if ($residence->latitude && $residence->longitude)
        @vite('resources/js/leaflet.js')
        <script nonce="{{ $cspNonce }}">
            document.addEventListener('DOMContentLoaded', function() {
                const config = @js(['lat' => $residence->latitude, 'lng' => $residence->longitude]);
                if (window.initResidenceMap) {
                    window.initResidenceMap(config);
                }
            });
        </script>

        {{-- Map widgets are registered in app.js (residence-show-maps.js) --}}
    @endif

    @if ($residence->latitude && $residence->longitude)
        <x-google-maps-loader />
    @endif

    {{-- stickyNav registered in app.js (residence-show-maps.js) --}}
@endpush
