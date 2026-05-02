@extends('layouts.app')

@section('content')
    <div class="min-h-[calc(100vh-4rem)] bg-gray-50 pb-20 lg:pb-0">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 lg:py-8">
            <div class="lg:flex lg:gap-8">
                {{-- Sidebar --}}
                <x-client-sidebar :active="$sidebarActive ?? ''" />

                {{-- Main Content --}}
                <div class="flex-1 min-w-0">
                    @yield('client-content')
                </div>
            </div>
        </div>
    </div>

    {{-- ────── Bottom Navigation Mobile (lg:hidden) ────── --}}
    @php $navRoute = Route::currentRouteName(); @endphp
    <nav class="fixed bottom-0 left-0 right-0 z-50 bg-white border-t border-gray-200 lg:hidden safe-bottom">
        <div class="grid grid-cols-5 h-16">
            {{-- Accueil --}}
            <a href="{{ route('client.dashboard') }}"
                class="flex flex-col items-center justify-center gap-0.5 text-xs transition {{ Str::startsWith($navRoute, 'client.dashboard') ? 'text-[#ff385c]' : 'text-gray-500 hover:text-gray-700' }}">
                <svg class="w-5 h-5" fill="{{ Str::startsWith($navRoute, 'client.dashboard') ? 'currentColor' : 'none' }}" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                </svg>
                <span>Accueil</span>
            </a>
            {{-- Rechercher --}}
            <a href="{{ route('residences.index') }}"
                class="flex flex-col items-center justify-center gap-0.5 text-xs transition {{ Str::startsWith($navRoute, 'residences') ? 'text-[#ff385c]' : 'text-gray-500 hover:text-gray-700' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
                <span>Rechercher</span>
            </a>
            {{-- Réservations --}}
            <a href="{{ route('bookings.index') }}"
                class="flex flex-col items-center justify-center gap-0.5 text-xs transition {{ Str::startsWith($navRoute, 'bookings') ? 'text-[#ff385c]' : 'text-gray-500 hover:text-gray-700' }}">
                <svg class="w-5 h-5" fill="{{ Str::startsWith($navRoute, 'bookings') ? 'currentColor' : 'none' }}" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                <span>Séjours</span>
            </a>
            {{-- Messages --}}
            <a href="{{ route('chat.index') }}"
                class="flex flex-col items-center justify-center gap-0.5 text-xs transition {{ Str::startsWith($navRoute, 'chat') ? 'text-[#ff385c]' : 'text-gray-500 hover:text-gray-700' }}">
                <svg class="w-5 h-5" fill="{{ Str::startsWith($navRoute, 'chat') ? 'currentColor' : 'none' }}" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 01-2.555-.337A5.972 5.972 0 015.41 20.97a5.969 5.969 0 01-.474-.065 4.48 4.48 0 00.978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25z" />
                </svg>
                <span>Messages</span>
            </a>
            {{-- Profil --}}
            <a href="{{ route('profile.edit') }}"
                class="flex flex-col items-center justify-center gap-0.5 text-xs transition {{ Str::startsWith($navRoute, 'profile') ? 'text-[#ff385c]' : 'text-gray-500 hover:text-gray-700' }}">
                <svg class="w-5 h-5" fill="{{ Str::startsWith($navRoute, 'profile') ? 'currentColor' : 'none' }}" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                </svg>
                <span>Profil</span>
            </a>
        </div>
    </nav>
@endsection
