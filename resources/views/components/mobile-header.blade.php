{{-- Header mobile amélioré --}}
@props(['title' => '', 'showBack' => false, 'showSearch' => false, 'transparent' => false])

<header x-data="{
    scrolled: false,
    menuOpen: false
}" @scroll.window="scrolled = window.scrollY > 20"
    class="fixed top-0 inset-x-0 z-40 md:hidden transition-all duration-300 pt-safe"
    :class="{
        'bg-white/98 shadow-sm': scrolled || !{{ $transparent ? 'true' : 'false' }},
        'bg-transparent': !scrolled && {{ $transparent ? 'true' : 'false' }}
    }">

    <div class="flex items-center justify-between h-14 px-3">
        {{-- Bouton retour ou logo (44x44 touch target) --}}
        <div class="w-11 flex items-center justify-start">
            @if ($showBack)
                <button onclick="history.back()"
                    class="p-2.5 rounded-full hover:bg-gray-100/80 active:bg-gray-200 active:scale-95 transition-all duration-200 min-w-11 min-h-11 flex items-center justify-center"
                    :class="{
                        'text-white hover:bg-white/20 active:bg-white/30': !scrolled &&
                            {{ $transparent ? 'true' : 'false' }},
                        'text-gray-700': scrolled || !
                            {{ $transparent ? 'true' : 'false' }}
                    }"
                    aria-label="Retour">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                </button>
            @else
                <a href="{{ route('home') }}" class="flex items-center">
                    <img loading="lazy" src="{{ asset('images/logo-rezi.png') }}" alt="REZI" class="h-8"
                        onerror="this.onerror=null; this.innerHTML='<span class=\'font-bold text-orange-600 text-xl\'>REZI</span>'">
                </a>
            @endif
        </div>

        {{-- Titre --}}
        @if ($title)
            <h1 class="flex-1 text-center font-semibold truncate px-2 text-sm tracking-tight"
                :class="{
                    'text-white': !scrolled && {{ $transparent ? 'true' : 'false' }},
                    'text-gray-900': scrolled || !
                        {{ $transparent ? 'true' : 'false' }}
                }">
                {{ $title }}
            </h1>
        @endif

        {{-- Actions droites --}}
        <div class="flex items-center justify-end gap-0.5">
            @if ($showSearch)
                <a href="{{ route('residences.index') }}"
                    class="p-2.5 rounded-full hover:bg-gray-100/80 active:bg-gray-200 active:scale-95 transition-all duration-200 min-w-11 min-h-11 flex items-center justify-center"
                    :class="{
                        'text-white hover:bg-white/20 active:bg-white/30': !scrolled &&
                            {{ $transparent ? 'true' : 'false' }},
                        'text-gray-700': scrolled || !
                            {{ $transparent ? 'true' : 'false' }}
                    }">
                    <svg aria-hidden="true" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </a>
            @endif

            {{ $slot ?? '' }}

            {{-- Theme toggle (mobile) --}}
            <x-theme-toggle class="p-2 rounded-full hover:bg-gray-100/80 active:bg-gray-200 active:scale-95 transition-all duration-200 min-w-11 min-h-11 flex items-center justify-center" />

            {{-- Bouton hamburger --}}
            <button @click="menuOpen = !menuOpen"
                class="p-2.5 rounded-full hover:bg-gray-100/80 active:bg-gray-200 active:scale-95 transition-all duration-200 min-w-11 min-h-11 flex items-center justify-center"
                :class="{
                    'text-white hover:bg-white/20 active:bg-white/30': !scrolled &&
                        {{ $transparent ? 'true' : 'false' }},
                    'text-gray-700': scrolled || !{{ $transparent ? 'true' : 'false' }}
                }"
                aria-label="Ouvrir le menu" :aria-expanded="menuOpen">
                <svg x-show="!menuOpen" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                    aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
                <svg x-show="menuOpen" x-cloak class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                    aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    </div>

    {{-- Menu déroulant mobile --}}
    <div x-show="menuOpen" x-cloak x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 -translate-y-2" @click.outside="menuOpen = false"
        class="absolute top-full left-0 right-0 bg-white shadow-lg border-t border-gray-100 max-h-[calc(100vh-4rem)] overflow-y-auto">

        {{-- Liens de navigation --}}
        <div class="py-2">
            <a href="{{ route('home') }}"
                class="flex items-center gap-3 px-5 py-3 text-sm font-medium transition-colors {{ request()->routeIs('home') ? 'text-orange-600 bg-orange-50' : 'text-gray-700 hover:bg-gray-50' }}">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                    aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                        d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                </svg>
                Accueil
            </a>
            <a href="{{ route('residences.index') }}"
                class="flex items-center gap-3 px-5 py-3 text-sm font-medium transition-colors {{ request()->routeIs('residences.index', 'residences.show') ? 'text-orange-600 bg-orange-50' : 'text-gray-700 hover:bg-gray-50' }}">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                    aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
                Résidences
            </a>
            <a href="{{ route('residences.map') }}"
                class="flex items-center gap-3 px-5 py-3 text-sm font-medium transition-colors {{ request()->routeIs('residences.map') ? 'text-orange-600 bg-orange-50' : 'text-gray-700 hover:bg-gray-50' }}">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                    aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                        d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
                </svg>
                Carte
            </a>
            <a href="{{ route('pages.about') }}"
                class="flex items-center gap-3 px-5 py-3 text-sm font-medium transition-colors {{ request()->routeIs('pages.about') ? 'text-orange-600 bg-orange-50' : 'text-gray-700 hover:bg-gray-50' }}">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                    aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                        d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                À propos
            </a>
            <a href="{{ route('pages.contact') }}"
                class="flex items-center gap-3 px-5 py-3 text-sm font-medium transition-colors {{ request()->routeIs('pages.contact') ? 'text-orange-600 bg-orange-50' : 'text-gray-700 hover:bg-gray-50' }}">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                    aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                        d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                </svg>
                Contact
            </a>
        </div>

        {{-- Séparateur --}}
        <div class="border-t border-gray-100"></div>

        {{-- Liens authentification / profil --}}
        <div class="py-2">
            @auth
                <div class="px-5 py-3 flex items-center gap-3">
                    @if (auth()->user()->avatar || auth()->user()->profile_photo)
                        <img src="{{ auth()->user()->getAvatarUrl() }}" alt="{{ auth()->user()->name }}"
                            class="w-9 h-9 rounded-full object-cover ring-2 ring-orange-100">
                    @else
                        <div class="w-9 h-9 rounded-full bg-orange-100 flex items-center justify-center">
                            <span
                                class="text-sm font-bold text-orange-600">{{ substr(auth()->user()->name, 0, 1) }}</span>
                        </div>
                    @endif
                    <div class="min-w-0">
                        <p class="text-sm font-semibold text-gray-900 truncate">{{ auth()->user()->name }}</p>
                        <p class="text-xs text-gray-500 truncate">{{ auth()->user()->email }}</p>
                    </div>
                </div>
                <a href="{{ route('dashboard') }}"
                    class="flex items-center gap-3 px-5 py-3 text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                        aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                            d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                    </svg>
                    Dashboard
                </a>
                @if (auth()->user()->isOwner())
                    <a href="{{ route('owner.dashboard') }}"
                        class="flex items-center gap-3 px-5 py-3 text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors">
                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                            aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                        Espace Propriétaire
                    </a>
                @endif
                <div class="border-t border-gray-100 my-1"></div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit"
                        class="flex items-center gap-3 px-5 py-3 text-sm font-medium text-red-600 hover:bg-red-50 transition-colors w-full text-left">
                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                            aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                        </svg>
                        Déconnexion
                    </button>
                </form>
            @else
                <a href="{{ route('login') }}"
                    class="flex items-center gap-3 px-5 py-3 text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                        aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                            d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                    </svg>
                    Connexion
                </a>
                <a href="{{ route('register') }}"
                    class="flex items-center gap-3 px-5 py-3 text-sm font-medium text-orange-600 hover:bg-orange-50 transition-colors">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                        aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                            d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                    </svg>
                    Inscription
                </a>
            @endguest
        </div>
    </div>
</header>

{{-- Spacer pour le header fixe --}}
<div class="h-14 md:hidden"></div>
