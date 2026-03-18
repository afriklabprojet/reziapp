<nav x-data="{ open: false }" class="bg-white/95 backdrop-blur-md border-b border-gray-100 sticky top-0 z-30">
    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <!-- Logo -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('home') }}">
                        <x-application-logo size="default" />
                    </a>
                </div>

                <!-- Navigation Links -->
                <div class="hidden space-x-6 sm:-my-px sm:ms-10 sm:flex">
                    <x-nav-link :href="route('home')" :active="request()->routeIs('home')">
                        {{ __('Accueil') }}
                    </x-nav-link>
                    <x-nav-link :href="route('residences.index')" :active="request()->routeIs('residences.*')">
                        {{ __('Résidences') }}
                    </x-nav-link>
                    <x-nav-link :href="route('residences.map')" :active="request()->routeIs('residences.map')">
                        {{ __('Carte') }}
                    </x-nav-link>
                    <x-nav-link :href="route('pages.about')" :active="request()->routeIs('pages.about')">
                        {{ __('À propos') }}
                    </x-nav-link>
                    <x-nav-link :href="route('pages.contact')" :active="request()->routeIs('pages.contact')">
                        {{ __('Contact') }}
                    </x-nav-link>
                </div>
            </div>

            <!-- Settings Dropdown -->
            <div class="hidden sm:flex sm:items-center sm:ms-6 gap-3">
                {{-- Theme toggle --}}
                <x-theme-toggle />

                @auth
                    <x-dropdown align="right" width="48">
                        <x-slot name="trigger">
                            <button
                                class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium rounded-lg text-gray-600 hover:text-gray-900 hover:bg-gray-50 focus:outline-none transition-colors duration-200">
                                <div>{{ Auth::user()->name }}</div>

                                <div>
                                    <svg aria-hidden="true" class="fill-current h-4 w-4 text-gray-400"
                                        xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd"
                                            d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                            clip-rule="evenodd" />
                                    </svg>
                                </div>
                            </button>
                        </x-slot>

                        <x-slot name="content">
                            <x-dropdown-link :href="route('dashboard')">
                                {{ __('Dashboard') }}
                            </x-dropdown-link>

                            <!-- Nouvelles fonctionnalités -->
                            <x-dropdown-link :href="route('chat.index')">
                                <span class="flex items-center gap-2">
                                    <svg aria-hidden="true" class="w-4 h-4" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                    </svg>
                                    {{ __('Messages') }}
                                    @if (Auth::user()->unreadMessagesCount() > 0)
                                        <span
                                            class="bg-red-500 text-white text-xs rounded-full px-1.5 py-0.5">{{ Auth::user()->unreadMessagesCount() }}</span>
                                    @endif
                                </span>
                            </x-dropdown-link>

                            <x-dropdown-link :href="route('favorites.index')">
                                <span class="flex items-center gap-2">
                                    <svg aria-hidden="true" class="w-4 h-4" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                                    </svg>
                                    {{ __('Favoris') }}
                                </span>
                            </x-dropdown-link>

                            <x-dropdown-link :href="route('notifications.index')">
                                <span class="flex items-center gap-2">
                                    <svg aria-hidden="true" class="w-4 h-4" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                                    </svg>
                                    {{ __('Notifications') }}
                                    @if (Auth::user()->unreadNotifications()->count() > 0)
                                        <span
                                            class="bg-red-500 text-white text-xs rounded-full px-1.5 py-0.5">{{ Auth::user()->unreadNotifications()->count() }}</span>
                                    @endif
                                </span>
                            </x-dropdown-link>

                            <!-- Liens Client -->
                            @if (Auth::user()->isUser())
                                <hr class="my-1.5 border-gray-100">
                                <x-dropdown-link :href="route('client.compare')">
                                    <span class="flex items-center gap-2">
                                        <svg aria-hidden="true" class="w-4 h-4" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                        </svg>
                                        {{ __('Comparer') }}
                                    </span>
                                </x-dropdown-link>
                                <x-dropdown-link :href="route('client.statistics')">
                                    <span class="flex items-center gap-2">
                                        <svg aria-hidden="true" class="w-4 h-4" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                        </svg>
                                        {{ __('Mes statistiques') }}
                                    </span>
                                </x-dropdown-link>
                            @endif

                            <hr class="my-1.5 border-gray-100">

                            @if (Auth::user()->isOwner())
                                <x-dropdown-link :href="route('owner.dashboard')">
                                    {{ __('Espace Propriétaire') }}
                                </x-dropdown-link>
                            @endif

                            @if (Auth::user()->isAdmin())
                                <x-dropdown-link href="/admin">
                                    {{ __('Administration') }}
                                </x-dropdown-link>
                            @endif

                            <x-dropdown-link :href="route('profile.edit')">
                                {{ __('Profile') }}
                            </x-dropdown-link>

                            <!-- Authentication -->
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf

                                <x-dropdown-link :href="route('logout')"
                                    onclick="event.preventDefault();
													this.closest('form').submit();">
                                    {{ __('Log Out') }}
                                </x-dropdown-link>
                            </form>
                        </x-slot>
                    </x-dropdown>
                @else
                    <div class="flex items-center space-x-3">
                        <a href="{{ route('login') }}"
                            class="text-sm font-medium text-gray-600 hover:text-gray-900 transition-colors duration-200">Connexion</a>
                        <a href="{{ route('register') }}"
                            class="inline-flex items-center px-4 py-2 bg-orange-500 border border-transparent rounded-lg font-semibold text-xs text-white uppercase tracking-wide hover:bg-orange-600 active:bg-orange-700 focus:outline-none focus:ring-2 focus:ring-orange-500/50 focus:ring-offset-2 transition-all duration-200">
                            Inscription
                        </a>
                    </div>
                @endauth
            </div>

            <!-- Hamburger -->
            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open"
                    class="inline-flex items-center justify-center p-2 rounded-lg text-gray-500 hover:text-gray-700 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 active:bg-gray-200 transition-colors duration-200">
                    <svg aria-hidden="true" class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{ 'hidden': open, 'inline-flex': !open }" class="inline-flex"
                            stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{ 'hidden': !open, 'inline-flex': open }" class="hidden" stroke-linecap="round"
                            stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu -->
    <div :class="{ 'block': open, 'hidden': !open }" class="hidden sm:hidden">
        <div class="pt-2 pb-3 space-y-1">
            <x-responsive-nav-link :href="route('home')" :active="request()->routeIs('home')">
                {{ __('Accueil') }}
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('residences.index')" :active="request()->routeIs('residences.*')">
                {{ __('Résidences') }}
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('residences.map')" :active="request()->routeIs('residences.map')">
                {{ __('Carte') }}
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('pages.about')" :active="request()->routeIs('pages.about')">
                {{ __('À propos') }}
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('pages.contact')" :active="request()->routeIs('pages.contact')">
                {{ __('Contact') }}
            </x-responsive-nav-link>
        </div>

        <!-- Responsive Settings Options -->
        @auth
            <div class="pt-4 pb-1 border-t border-gray-200/80">
                <div class="px-4">
                    <div class="font-semibold text-base text-gray-900">{{ Auth::user()->name }}</div>
                    <div class="font-medium text-sm text-gray-500">{{ Auth::user()->email }}</div>
                </div>

                <div class="mt-3 space-y-1">
                    <x-responsive-nav-link :href="route('dashboard')">
                        {{ __('Dashboard') }}
                    </x-responsive-nav-link>

                    @if (Auth::user()->isOwner())
                        <x-responsive-nav-link :href="route('owner.dashboard')">
                            {{ __('Espace Propriétaire') }}
                        </x-responsive-nav-link>
                    @endif

                    @if (Auth::user()->isAdmin())
                        <x-responsive-nav-link href="/admin">
                            {{ __('Administration') }}
                        </x-responsive-nav-link>
                    @endif

                    <x-responsive-nav-link :href="route('profile.edit')">
                        {{ __('Profile') }}
                    </x-responsive-nav-link>

                    <!-- Authentication -->
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf

                        <x-responsive-nav-link :href="route('logout')"
                            onclick="event.preventDefault();
											this.closest('form').submit();">
                            {{ __('Log Out') }}
                        </x-responsive-nav-link>
                    </form>
                </div>
            </div>
        @else
            <div class="pt-4 pb-1 border-t border-gray-200/80">
                <div class="space-y-1">
                    <x-responsive-nav-link :href="route('login')">
                        {{ __('Connexion') }}
                    </x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('register')">
                        {{ __('Inscription') }}
                    </x-responsive-nav-link>
                </div>
            </div>
        @endauth
    </div>
</nav>
