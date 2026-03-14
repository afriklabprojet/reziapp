{{-- Navigation mobile bottom bar --}}
<nav x-data="{
    activeTab: '{{ request()->routeIs('home') ? 'home' : (request()->routeIs('residences.*') ? 'search' : (request()->routeIs('favorites.*') ? 'favorites' : (request()->routeIs('bookings.*') ? 'bookings' : (request()->routeIs('profile.*', 'settings.*', 'owner.*') ? 'profile' : 'home')))) }}',
    unreadMessages: {{ auth()->check() ? auth()->user()->unreadMessagesCount() : 0 }},
    hasNotifications: {{ auth()->check() ? (auth()->user()->unreadNotifications()->count() > 0 ? 'true' : 'false') : 'false' }}
}" class="fixed bottom-0 inset-x-0 bg-white border-t border-gray-200/80 pb-safe z-40 md:hidden">

    <div class="flex items-center justify-around h-16">
        {{-- Accueil --}}
        <a href="{{ route('home') }}"
            class="flex flex-col items-center justify-center flex-1 h-full min-w-12 group transition-colors duration-200"
            :class="activeTab === 'home' ? 'text-orange-600' : 'text-gray-400 hover:text-gray-600'">
            <div class="relative">
                <svg aria-hidden="true" class="w-6 h-6 transition-all duration-200 group-active:scale-90" fill="none"
                    stroke="currentColor" viewBox="0 0 24 24"
                    :class="activeTab === 'home' ? 'stroke-[2.5]' : 'stroke-[1.5]'">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                        d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                </svg>
            </div>
            <span class="text-[11px] mt-0.5 font-medium tracking-tight">Accueil</span>
        </a>

        {{-- Rechercher --}}
        <a href="{{ route('residences.index') }}"
            class="flex flex-col items-center justify-center flex-1 h-full min-w-12 group transition-colors duration-200"
            :class="activeTab === 'search' ? 'text-orange-600' : 'text-gray-400 hover:text-gray-600'">
            <div class="relative">
                <svg aria-hidden="true" class="w-6 h-6 transition-all duration-200 group-active:scale-90" fill="none"
                    stroke="currentColor" viewBox="0 0 24 24"
                    :class="activeTab === 'search' ? 'stroke-[2.5]' : 'stroke-[1.5]'">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </div>
            <span class="text-[11px] mt-0.5 font-medium tracking-tight">Rechercher</span>
        </a>

        {{-- Favoris --}}
        @auth
            <a href="{{ route('favorites.index') }}"
                class="flex flex-col items-center justify-center flex-1 h-full min-w-12 group transition-colors duration-200"
                :class="activeTab === 'favorites' ? 'text-orange-600' : 'text-gray-400 hover:text-gray-600'">
                <div class="relative">
                    <svg aria-hidden="true" class="w-6 h-6 transition-all duration-200 group-active:scale-90" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24"
                        :class="activeTab === 'favorites' ? 'stroke-[2.5]' : 'stroke-[1.5]'">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                            d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                    </svg>
                </div>
                <span class="text-[11px] mt-0.5 font-medium tracking-tight">Favoris</span>
            </a>
        @endauth

        {{-- Messages --}}
        @auth
            <a href="{{ route('chat.index') }}"
                class="flex flex-col items-center justify-center flex-1 h-full min-w-12 group transition-colors duration-200"
                :class="activeTab === 'messages' ? 'text-orange-600' : 'text-gray-400 hover:text-gray-600'">
                <div class="relative">
                    <svg aria-hidden="true" class="w-6 h-6 transition-all duration-200 group-active:scale-90" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24"
                        :class="activeTab === 'messages' ? 'stroke-[2.5]' : 'stroke-[1.5]'">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                            d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                    </svg>
                    {{-- Badge messages non lus (agrandi pour lisibilité) --}}
                    <span x-show="unreadMessages > 0" x-cloak
                        class="absolute -top-1.5 -right-2 min-w-5 h-5 bg-red-500 text-white text-[10px] font-bold rounded-full flex items-center justify-center px-1 ring-2 ring-white"
                        x-text="unreadMessages > 9 ? '9+' : unreadMessages"></span>
                </div>
                <span class="text-[11px] mt-0.5 font-medium tracking-tight">Messages</span>
            </a>
        @endauth

        {{-- Profil / Connexion --}}
        @auth
            <a href="{{ route('profile.edit') }}"
                class="flex flex-col items-center justify-center flex-1 h-full min-w-12 group transition-colors duration-200"
                :class="activeTab === 'profile' ? 'text-orange-600' : 'text-gray-400 hover:text-gray-600'">
                <div class="relative">
                    @if (auth()->user()->profile_photo || auth()->user()->avatar)
                        <img loading="lazy" src="{{ auth()->user()->getAvatarUrl() }}" alt="{{ auth()->user()->name }}"
                            class="w-6 h-6 rounded-full object-cover transition-all duration-200"
                            :class="activeTab === 'profile' ? 'ring-2 ring-orange-500 ring-offset-1' : ''">
                    @else
                        <svg aria-hidden="true" class="w-6 h-6 transition-all duration-200 group-active:scale-90"
                            fill="none" stroke="currentColor" viewBox="0 0 24 24"
                            :class="activeTab === 'profile' ? 'stroke-[2.5]' : 'stroke-[1.5]'">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                    @endif
                    {{-- Badge notifications --}}
                    <span x-show="hasNotifications" x-cloak
                        class="absolute -top-1.5 -right-1.5 w-2.5 h-2.5 bg-orange-500 rounded-full ring-2 ring-white"></span>
                </div>
                <span class="text-[11px] mt-0.5 font-medium tracking-tight">Profil</span>
            </a>
        @else
            <a href="{{ route('login') }}"
                class="flex flex-col items-center justify-center flex-1 h-full min-w-12 group text-gray-400 hover:text-gray-600 transition-colors duration-200">
                <div class="relative">
                    <svg aria-hidden="true" class="w-6 h-6 transition-all duration-200 group-active:scale-90" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                            d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                </div>
                <span class="text-[11px] mt-0.5 font-medium tracking-tight">Connexion</span>
            </a>
        @endauth
    </div>
</nav>

{{-- Spacer pour éviter que le contenu soit caché par la nav --}}
<div class="h-16 md:hidden"></div>

<style>
    /* Safe area pour les appareils avec encoche (iPhone X+) */
    .pb-safe {
        padding-bottom: env(safe-area-inset-bottom);
    }

    /* Animation de tap fluide */
    nav a:active {
        transition: transform 0.1s ease;
    }
</style>
