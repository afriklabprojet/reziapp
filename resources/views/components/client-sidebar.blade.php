@props(['active' => ''])

@php
    $unreadMsgCount = auth()->user()->unreadMessagesCount();
    $unreadNotifCount = auth()->user()->unreadNotifications()->count();
@endphp

{{-- ═══════════════════════════════════════════════════════════════
     CLIENT SIDEBAR — Design inspiré Airbnb
     Clean, minimaliste, blanc, typographie nette, icônes encadrées
     Desktop: sidebar fixe à gauche
     Mobile: bottom tab bar persistante + "Plus" slide-up
     ═══════════════════════════════════════════════════════════════ --}}

{{-- ===== DESKTOP SIDEBAR ===== --}}
<aside class="hidden lg:block w-70 shrink-0">
    <div class="sticky top-20">
        <nav class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">

            {{-- ── Profil utilisateur ── --}}
            <div class="p-5 border-b border-gray-100">
                <div class="flex items-center gap-3">
                    <div class="relative">
                        <div
                            class="w-12 h-12 rounded-full overflow-hidden bg-linear-to-br from-orange-50 to-orange-100 flex items-center justify-center shrink-0 ring-2 ring-white shadow-sm">
                            @if (auth()->user()->profile_photo || auth()->user()->avatar)
                                <img loading="lazy" src="{{ auth()->user()->getAvatarUrl() }}"
                                    alt="{{ auth()->user()->name }}" class="w-full h-full object-cover">
                            @else
                                <span
                                    class="text-lg font-semibold text-orange-600">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
                            @endif
                        </div>
                        {{-- Online indicator --}}
                        <span
                            class="absolute bottom-0 right-0 w-3 h-3 bg-emerald-400 border-2 border-white rounded-full"></span>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="font-semibold text-[15px] text-gray-900 truncate">{{ auth()->user()->name }}</p>
                        <p class="text-xs text-gray-500 flex items-center gap-1">
                            <svg class="w-3 h-3 text-orange-500" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M10 12a2 2 0 100-4 2 2 0 000 4z" />
                                <path fill-rule="evenodd"
                                    d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z"
                                    clip-rule="evenodd" />
                            </svg>
                            Locataire
                        </p>
                    </div>
                </div>
            </div>

            {{-- ── Navigation ── --}}
            <div class="py-2 px-2">

                {{-- Section: Principal --}}
                <div class="mb-1">
                    <p class="px-3 pt-3 pb-1.5 text-[11px] font-semibold text-gray-400 uppercase tracking-wider">
                        Principal</p>
                </div>

                @foreach ([
        ['route' => 'client.dashboard', 'key' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z'],
        ['route' => 'bookings.index', 'key' => 'bookings', 'label' => 'Réservations', 'icon' => 'M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5'],
        ['route' => 'favorites.index', 'key' => 'favorites', 'label' => 'Favoris', 'icon' => 'M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z'],
        ['route' => 'chat.index', 'key' => 'messages', 'label' => 'Messages', 'icon' => 'M8.625 12a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 01-2.555-.337A5.972 5.972 0 015.41 20.97a5.969 5.969 0 01-.474-.065 4.48 4.48 0 00.978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25z', 'badge' => $unreadMsgCount],
        ['route' => 'notifications.index', 'key' => 'notifications', 'label' => 'Notifications', 'icon' => 'M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0', 'badge' => $unreadNotifCount],
    ] as $item)
                    <a href="{{ route($item['route']) }}"
                        class="group flex items-center gap-3 px-3 py-2.5 rounded-xl text-[14px] font-medium transition-all duration-150
                              {{ $active === $item['key'] ? 'bg-gray-900 text-white shadow-sm' : 'text-gray-700 hover:bg-gray-50' }}">
                        <span
                            class="flex items-center justify-center w-8 h-8 rounded-lg transition-colors
                                    {{ $active === $item['key'] ? 'bg-white/10' : 'bg-gray-50 group-hover:bg-gray-100' }}">
                            <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" stroke-width="1.8"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="{{ $item['icon'] }}" />
                            </svg>
                        </span>
                        {{ $item['label'] }}
                        @if (isset($item['badge']) && $item['badge'] > 0)
                            <span
                                class="ml-auto min-w-5 h-5 {{ $active === $item['key'] ? 'bg-white/20 text-white' : 'bg-rose-500 text-white' }} text-[10px] font-bold rounded-full flex items-center justify-center px-1">{{ min($item['badge'], 9) }}{{ $item['badge'] > 9 ? '+' : '' }}</span>
                        @endif
                    </a>
                @endforeach

                {{-- Section: Activité --}}
                <div class="mb-1">
                    <p class="px-3 pt-4 pb-1.5 text-[11px] font-semibold text-gray-400 uppercase tracking-wider">
                        Activité</p>
                </div>

                @foreach ([
        ['route' => 'client.reviews', 'key' => 'reviews', 'label' => 'Mes avis', 'icon' => 'M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.563.563 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z'],
        ['route' => 'client.contacts', 'key' => 'contacts', 'label' => 'Mes demandes', 'icon' => 'M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75'],
        ['route' => 'client.compare', 'key' => 'compare', 'label' => 'Comparer', 'icon' => 'M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z'],
    ] as $item)
                    <a href="{{ route($item['route']) }}"
                        class="group flex items-center gap-3 px-3 py-2.5 rounded-xl text-[14px] font-medium transition-all duration-150
                              {{ $active === $item['key'] ? 'bg-gray-900 text-white shadow-sm' : 'text-gray-700 hover:bg-gray-50' }}">
                        <span
                            class="flex items-center justify-center w-8 h-8 rounded-lg transition-colors
                                    {{ $active === $item['key'] ? 'bg-white/10' : 'bg-gray-50 group-hover:bg-gray-100' }}">
                            <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" stroke-width="1.8"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="{{ $item['icon'] }}" />
                            </svg>
                        </span>
                        {{ $item['label'] }}
                    </a>
                @endforeach

                {{-- Section: Outils --}}
                <div class="mb-1">
                    <p class="px-3 pt-4 pb-1.5 text-[11px] font-semibold text-gray-400 uppercase tracking-wider">Outils
                    </p>
                </div>

                @foreach ([
        ['route' => 'client.search-history', 'key' => 'search-history', 'label' => 'Recherches', 'icon' => 'M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z'],
        ['route' => 'client.view-history', 'key' => 'view-history', 'label' => 'Visites récentes', 'icon' => 'M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z'],
        ['route' => 'client.alerts', 'key' => 'alerts', 'label' => 'Alertes', 'icon' => 'M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0'],
        ['route' => 'client.statistics', 'key' => 'statistics', 'label' => 'Statistiques', 'icon' => 'M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z'],
    ] as $item)
                    <a href="{{ route($item['route']) }}"
                        class="group flex items-center gap-3 px-3 py-2.5 rounded-xl text-[14px] font-medium transition-all duration-150
                              {{ $active === $item['key'] ? 'bg-gray-900 text-white shadow-sm' : 'text-gray-700 hover:bg-gray-50' }}">
                        <span
                            class="flex items-center justify-center w-8 h-8 rounded-lg transition-colors
                                    {{ $active === $item['key'] ? 'bg-white/10' : 'bg-gray-50 group-hover:bg-gray-100' }}">
                            <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" stroke-width="1.8"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="{{ $item['icon'] }}" />
                            </svg>
                        </span>
                        {{ $item['label'] }}
                    </a>
                @endforeach
            </div>

            {{-- ── Profil link ── --}}
            <div class="p-3 border-t border-gray-100">
                <a href="{{ route('profile.edit') }}"
                    class="group flex items-center gap-3 px-3 py-2.5 rounded-xl text-[14px] font-medium transition-all duration-150
                          {{ $active === 'profile' ? 'bg-gray-900 text-white shadow-sm' : 'text-gray-700 hover:bg-gray-50' }}">
                    <span
                        class="flex items-center justify-center w-8 h-8 rounded-lg transition-colors
                                {{ $active === 'profile' ? 'bg-white/10' : 'bg-gray-50 group-hover:bg-gray-100' }}">
                        <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" stroke-width="1.8"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                    </span>
                    Mon profil
                </a>
            </div>
        </nav>
    </div>
</aside>

{{-- ===== MOBILE BOTTOM TAB BAR (Airbnb-style) ===== --}}
<div x-data="{ moreOpen: false }" class="lg:hidden">

    {{-- Fixed bottom tab bar --}}
    <nav class="fixed bottom-0 left-0 right-0 z-40 bg-white border-t border-gray-200"
        style="padding-bottom: env(safe-area-inset-bottom)">
        <div class="grid grid-cols-5 h-14">
            {{-- Accueil --}}
            <a href="{{ route('client.dashboard') }}"
                class="flex flex-col items-center justify-center gap-0.5 {{ $active === 'dashboard' ? 'text-gray-900' : 'text-gray-400' }}">
                <svg class="w-5 h-5" fill="{{ $active === 'dashboard' ? 'currentColor' : 'none' }}"
                    stroke="currentColor" stroke-width="{{ $active === 'dashboard' ? '0' : '1.8' }}"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z" />
                </svg>
                <span class="text-[10px] font-semibold">Accueil</span>
            </a>
            {{-- Favoris --}}
            <a href="{{ route('favorites.index') }}"
                class="flex flex-col items-center justify-center gap-0.5 {{ $active === 'favorites' ? 'text-gray-900' : 'text-gray-400' }}">
                <svg class="w-5 h-5" fill="{{ $active === 'favorites' ? 'currentColor' : 'none' }}"
                    stroke="currentColor" stroke-width="{{ $active === 'favorites' ? '0' : '1.8' }}"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z" />
                </svg>
                <span class="text-[10px] font-semibold">Favoris</span>
            </a>
            {{-- Réservations --}}
            <a href="{{ route('bookings.index') }}"
                class="flex flex-col items-center justify-center gap-0.5 {{ $active === 'bookings' ? 'text-gray-900' : 'text-gray-400' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
                </svg>
                <span class="text-[10px] font-semibold">Réservations</span>
            </a>
            {{-- Messages --}}
            <a href="{{ route('chat.index') }}"
                class="relative flex flex-col items-center justify-center gap-0.5 {{ $active === 'messages' ? 'text-gray-900' : 'text-gray-400' }}">
                <div class="relative">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M8.625 12a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 01-2.555-.337A5.972 5.972 0 015.41 20.97a5.969 5.969 0 01-.474-.065 4.48 4.48 0 00.978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25z" />
                    </svg>
                    @if ($unreadMsgCount > 0)
                        <span
                            class="absolute -top-1.5 -right-2 min-w-4 h-4 bg-rose-500 text-white text-[9px] font-bold rounded-full flex items-center justify-center px-1">{{ min($unreadMsgCount, 9) }}</span>
                    @endif
                </div>
                <span class="text-[10px] font-semibold">Messages</span>
            </a>
            {{-- Plus --}}
            <button @click="moreOpen = !moreOpen"
                class="flex flex-col items-center justify-center gap-0.5 text-gray-400">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                </svg>
                <span class="text-[10px] font-semibold">Plus</span>
            </button>
        </div>
    </nav>
    {{-- Spacer for fixed bottom bar --}}
    <div class="h-14"></div>

    {{-- Overlay --}}
    <div x-show="moreOpen" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" @click="moreOpen = false"
        class="fixed inset-0 bg-black/40 backdrop-blur-sm z-40" x-cloak></div>

    {{-- "Plus" slide-up panel --}}
    <div x-show="moreOpen" x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="translate-y-full" x-transition:enter-end="translate-y-0"
        x-transition:leave="transition ease-in duration-200" x-transition:leave-start="translate-y-0"
        x-transition:leave-end="translate-y-full"
        class="fixed bottom-0 left-0 right-0 z-50 bg-white rounded-t-3xl shadow-2xl max-h-[75vh] overflow-y-auto overscroll-contain"
        x-cloak>

        {{-- Handle --}}
        <div class="flex justify-center pt-3 pb-1 sticky top-0 bg-white rounded-t-3xl z-10">
            <div class="w-10 h-1 bg-gray-300 rounded-full"></div>
        </div>

        {{-- User info --}}
        <div class="px-5 py-3 border-b border-gray-100">
            <div class="flex items-center gap-3">
                <div
                    class="w-10 h-10 rounded-full overflow-hidden bg-linear-to-br from-orange-50 to-orange-100 flex items-center justify-center shrink-0">
                    @if (auth()->user()->profile_photo || auth()->user()->avatar)
                        <img loading="lazy" src="{{ auth()->user()->getAvatarUrl() }}"
                            alt="{{ auth()->user()->name }}" class="w-full h-full object-cover">
                    @else
                        <span
                            class="text-base font-semibold text-orange-600">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
                    @endif
                </div>
                <div>
                    <p class="font-semibold text-gray-900 text-[15px]">{{ auth()->user()->name }}</p>
                    <p class="text-xs text-gray-500">Espace locataire</p>
                </div>
            </div>
        </div>

        {{-- Navigation items --}}
        <div class="px-4 py-3 space-y-0.5">
            <p class="px-2 pt-1 pb-2 text-[11px] font-semibold text-gray-400 uppercase tracking-wider">Activité</p>
            @foreach ([
        ['route' => 'client.reviews', 'key' => 'reviews', 'label' => 'Mes avis', 'icon' => 'M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.563.563 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z'],
        ['route' => 'client.contacts', 'key' => 'contacts', 'label' => 'Mes demandes', 'icon' => 'M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75'],
        ['route' => 'client.compare', 'key' => 'compare', 'label' => 'Comparer', 'icon' => 'M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z'],
        ['route' => 'notifications.index', 'key' => 'notifications', 'label' => 'Notifications', 'icon' => 'M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0', 'badge' => $unreadNotifCount],
    ] as $item)
                <a href="{{ route($item['route']) }}" @click="moreOpen = false"
                    class="flex items-center gap-3 px-3 py-3 rounded-xl text-[15px] font-medium {{ $active === $item['key'] ? 'bg-gray-900 text-white' : 'text-gray-700 active:bg-gray-50' }}">
                    <svg class="w-5 h-5 {{ $active === $item['key'] ? 'text-white' : 'text-gray-400' }}"
                        fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="{{ $item['icon'] }}" />
                    </svg>
                    {{ $item['label'] }}
                    @if (isset($item['badge']) && $item['badge'] > 0)
                        <span
                            class="ml-auto min-w-5 h-5 bg-rose-500 text-white text-[10px] font-bold rounded-full flex items-center justify-center px-1">{{ min($item['badge'], 9) }}{{ $item['badge'] > 9 ? '+' : '' }}</span>
                    @endif
                </a>
            @endforeach

            <p class="px-2 pt-4 pb-2 text-[11px] font-semibold text-gray-400 uppercase tracking-wider">Outils</p>
            @foreach ([
        ['route' => 'client.search-history', 'key' => 'search-history', 'label' => 'Recherches', 'icon' => 'M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z'],
        ['route' => 'client.view-history', 'key' => 'view-history', 'label' => 'Visites récentes', 'icon' => 'M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z'],
        ['route' => 'client.alerts', 'key' => 'alerts', 'label' => 'Alertes', 'icon' => 'M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0'],
        ['route' => 'client.statistics', 'key' => 'statistics', 'label' => 'Statistiques', 'icon' => 'M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z'],
    ] as $item)
                <a href="{{ route($item['route']) }}" @click="moreOpen = false"
                    class="flex items-center gap-3 px-3 py-3 rounded-xl text-[15px] font-medium {{ $active === $item['key'] ? 'bg-gray-900 text-white' : 'text-gray-700 active:bg-gray-50' }}">
                    <svg class="w-5 h-5 {{ $active === $item['key'] ? 'text-white' : 'text-gray-400' }}"
                        fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="{{ $item['icon'] }}" />
                    </svg>
                    {{ $item['label'] }}
                </a>
            @endforeach

            <div class="my-2 border-t border-gray-100"></div>
            <a href="{{ route('profile.edit') }}" @click="moreOpen = false"
                class="flex items-center gap-3 px-3 py-3 rounded-xl text-[15px] font-medium text-gray-700 active:bg-gray-50">
                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" stroke-width="1.8"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                Paramètres
            </a>
        </div>
        <div class="h-6"></div>
    </div>
</div>
