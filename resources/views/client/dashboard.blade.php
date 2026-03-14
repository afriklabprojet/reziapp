@extends('layouts.client', ['sidebarActive' => 'dashboard'])

@section('title', 'Mon Espace - REZI')

@section('client-content')
    <div x-data="{ loaded: false }" x-init="$nextTick(() => { setTimeout(() => loaded = true, 80) })">

        {{-- ============================== SKELETON LOADER (Airbnb-style shimmer) ============================== --}}
        <div x-show="!loaded" x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0">
            {{-- Header skeleton --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-6">
                <div class="flex items-center gap-4">
                    <div class="w-14 h-14 rounded-full bg-gray-200 animate-pulse lg:hidden"></div>
                    <div class="space-y-2 flex-1">
                        <div class="h-7 w-52 bg-gray-200 rounded-lg animate-pulse"></div>
                        <div class="h-4 w-72 bg-gray-100 rounded animate-pulse"></div>
                    </div>
                    <div class="h-10 w-32 bg-gray-200 rounded-lg animate-pulse hidden sm:block"></div>
                </div>
            </div>
            {{-- Stat cards skeleton --}}
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-8">
                @for ($sk = 0; $sk < 6; $sk++)
                    <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100 animate-pulse">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-gray-100 rounded-lg"></div>
                            <div class="space-y-1.5 flex-1">
                                <div class="h-6 w-8 bg-gray-200 rounded"></div>
                                <div class="h-3 w-16 bg-gray-100 rounded"></div>
                            </div>
                        </div>
                    </div>
                @endfor
            </div>
            {{-- Content skeleton --}}
            <div class="grid lg:grid-cols-3 gap-8">
                <div class="lg:col-span-2 space-y-8">
                    <div class="bg-white rounded-xl border border-gray-100 p-6 animate-pulse h-64"></div>
                    <div class="bg-white rounded-xl border border-gray-100 p-6 animate-pulse h-48"></div>
                </div>
                <div class="space-y-6">
                    <div class="bg-white rounded-xl border border-gray-100 p-5 animate-pulse h-52"></div>
                    <div class="bg-white rounded-xl border border-gray-100 p-5 animate-pulse h-40"></div>
                </div>
            </div>
        </div>

        {{-- ============================== REAL CONTENT (revealed after load) ============================== --}}
        <div x-show="loaded" x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" x-cloak>

            {{-- En-tête avec salutation dynamique --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-6">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <div
                            class="w-14 h-14 rounded-full overflow-hidden bg-orange-100 flex items-center justify-center lg:hidden">
                            @if (auth()->user()->profile_photo || auth()->user()->avatar)
                                <img loading="lazy" src="{{ auth()->user()->getAvatarUrl() }}"
                                    alt="{{ auth()->user()->name }}" class="w-full h-full object-cover">
                            @else
                                <span
                                    class="text-xl font-bold text-orange-500">{{ substr(auth()->user()->name, 0, 1) }}</span>
                            @endif
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900">
                                {{ now()->hour < 18 ? 'Bonjour' : 'Bonsoir' }}, {{ explode(' ', auth()->user()->name)[0] }}
                                👋
                            </h1>
                            <p class="text-gray-600">Bienvenue dans votre espace personnel</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        {{-- Notification bell --}}
                        <a href="{{ route('notifications.index') }}"
                            class="relative p-2 text-gray-400 hover:text-gray-600 transition rounded-lg hover:bg-gray-50"
                            title="Notifications">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.8"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
                            </svg>
                            @if ($stats['notifications_unread'] > 0)
                                <span
                                    class="absolute -top-0.5 -right-0.5 min-w-5 h-5 bg-rose-500 text-white text-[10px] font-bold rounded-full flex items-center justify-center px-1">{{ min($stats['notifications_unread'], 9) }}{{ $stats['notifications_unread'] > 9 ? '+' : '' }}</span>
                            @endif
                        </a>
                        {{-- Message bell --}}
                        <a href="{{ route('chat.index') }}"
                            class="relative p-2 text-gray-400 hover:text-gray-600 transition rounded-lg hover:bg-gray-50"
                            title="Messages">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.8"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M8.625 12a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 01-2.555-.337A5.972 5.972 0 015.41 20.97a5.969 5.969 0 01-.474-.065 4.48 4.48 0 00.978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25z" />
                            </svg>
                            @if ($stats['messages_unread'] > 0)
                                <span
                                    class="absolute -top-0.5 -right-0.5 min-w-5 h-5 bg-rose-500 text-white text-[10px] font-bold rounded-full flex items-center justify-center px-1">{{ min($stats['messages_unread'], 9) }}{{ $stats['messages_unread'] > 9 ? '+' : '' }}</span>
                            @endif
                        </a>
                        <a href="{{ route('residences.index') }}"
                            class="inline-flex items-center px-4 py-2 bg-orange-500 hover:bg-orange-600 text-white font-medium rounded-lg transition">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                            Rechercher
                        </a>
                    </div>
                </div>
            </div>
            {{-- Bannière réservation en cours --}}
            @if ($ongoingBooking)
                <div class="bg-green-50 border border-green-200 rounded-xl p-4 mb-6">
                    <div class="flex flex-col sm:flex-row sm:items-center gap-4">
                        <div class="flex items-center gap-3 flex-1 min-w-0">
                            <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center shrink-0">
                                <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                                </svg>
                            </div>
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-green-800">Séjour en cours</p>
                                <p class="text-sm text-green-700 truncate">
                                    {{ $ongoingBooking->residence->title }} —
                                    jusqu'au {{ $ongoingBooking->check_out->translatedFormat('d M Y') }}
                                </p>
                            </div>
                        </div>
                        <a href="{{ route('bookings.show', $ongoingBooking) }}"
                            class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-lg transition shrink-0">
                            Voir détails
                        </a>
                    </div>
                </div>
            @endif

            {{-- Statistiques rapides (clickables) --}}
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-8">
                <a href="{{ route('bookings.index') }}"
                    class="bg-white rounded-xl p-4 shadow-sm border border-gray-100 hover:shadow-md hover:border-orange-200 transition group">
                    <div class="flex items-center gap-3">
                        <div
                            class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center group-hover:bg-green-200 transition">
                            <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-gray-900">{{ $stats['bookings_upcoming'] }}</p>
                            <p class="text-xs text-gray-500">Réservations</p>
                        </div>
                    </div>
                </a>

                <a href="{{ route('favorites.index') }}"
                    class="bg-white rounded-xl p-4 shadow-sm border border-gray-100 hover:shadow-md hover:border-orange-200 transition group">
                    <div class="flex items-center gap-3">
                        <div
                            class="w-10 h-10 bg-rose-100 rounded-lg flex items-center justify-center group-hover:bg-rose-200 transition">
                            <svg class="w-5 h-5 text-rose-600" fill="currentColor" viewBox="0 0 24 24">
                                <path
                                    d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-gray-900">{{ $stats['favorites_count'] }}</p>
                            <p class="text-xs text-gray-500">Favoris</p>
                        </div>
                    </div>
                </a>

                <a href="{{ route('chat.index') }}"
                    class="bg-white rounded-xl p-4 shadow-sm border border-gray-100 hover:shadow-md hover:border-orange-200 transition group">
                    <div class="flex items-center gap-3">
                        <div
                            class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center group-hover:bg-blue-200 transition">
                            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-gray-900">{{ $stats['messages_unread'] }}</p>
                            <p class="text-xs text-gray-500">Non lus</p>
                        </div>
                    </div>
                </a>

                <a href="{{ route('client.view-history') }}"
                    class="bg-white rounded-xl p-4 shadow-sm border border-gray-100 hover:shadow-md hover:border-orange-200 transition group">
                    <div class="flex items-center gap-3">
                        <div
                            class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center group-hover:bg-purple-200 transition">
                            <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-gray-900">{{ $stats['views_count'] }}</p>
                            <p class="text-xs text-gray-500">Visites</p>
                        </div>
                    </div>
                </a>

                <a href="{{ route('client.reviews') }}"
                    class="bg-white rounded-xl p-4 shadow-sm border border-gray-100 hover:shadow-md hover:border-orange-200 transition group">
                    <div class="flex items-center gap-3">
                        <div
                            class="w-10 h-10 bg-orange-100 rounded-lg flex items-center justify-center group-hover:bg-orange-200 transition">
                            <svg class="w-5 h-5 text-orange-500" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-gray-900">{{ $stats['reviews_count'] }}</p>
                            <p class="text-xs text-gray-500">Avis</p>
                        </div>
                    </div>
                </a>

                <a href="{{ route('notifications.index') }}"
                    class="bg-white rounded-xl p-4 shadow-sm border border-gray-100 hover:shadow-md hover:border-orange-200 transition group">
                    <div class="flex items-center gap-3">
                        <div
                            class="w-10 h-10 bg-red-100 rounded-lg flex items-center justify-center group-hover:bg-red-200 transition">
                            <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-gray-900">{{ $stats['notifications_unread'] }}</p>
                            <p class="text-xs text-gray-500">Alertes</p>
                        </div>
                    </div>
                </a>
            </div>

            {{-- Empty state / Onboarding pour nouveaux utilisateurs --}}
            @if (
                $stats['favorites_count'] == 0 &&
                    $stats['views_count'] == 0 &&
                    $stats['bookings_upcoming'] == 0 &&
                    $recentSearches->isEmpty())
                <div
                    class="bg-linear-to-br from-orange-50 to-amber-50 border border-orange-100 rounded-xl p-8 mb-8 text-center">
                    <div class="w-16 h-16 bg-orange-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                        </svg>
                    </div>
                    <h2 class="text-xl font-bold text-gray-900 mb-2">Bienvenue sur REZI ! 🎉</h2>
                    <p class="text-gray-600 mb-6 max-w-md mx-auto">
                        Trouvez votre résidence meublée idéale. Commencez par explorer les résidences disponibles dans votre
                        ville.
                    </p>
                    <div class="flex flex-col sm:flex-row items-center justify-center gap-3">
                        <a href="{{ route('residences.index') }}"
                            class="inline-flex items-center px-6 py-3 bg-orange-500 hover:bg-orange-600 text-white font-medium rounded-lg transition">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                            Explorer les résidences
                        </a>
                        <a href="{{ route('client.alerts') }}"
                            class="inline-flex items-center px-6 py-3 bg-white hover:bg-gray-50 text-gray-700 font-medium rounded-lg border border-gray-200 transition">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                            </svg>
                            Créer une alerte
                        </a>
                    </div>
                </div>
            @endif

            {{-- Réservations à venir --}}
            @if ($upcomingBookings->count() > 0)
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden mb-8">
                    <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 bg-green-100 rounded-lg flex items-center justify-center">
                                <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                            </div>
                            <h2 class="font-semibold text-gray-900">Réservations à venir</h2>
                        </div>
                        <a href="{{ route('bookings.index') }}"
                            class="text-sm text-orange-500 hover:text-orange-600 font-medium">
                            Tout voir →
                        </a>
                    </div>
                    <div class="divide-y divide-gray-100">
                        @foreach ($upcomingBookings as $booking)
                            <a href="{{ route('bookings.show', $booking) }}"
                                class="flex items-center gap-4 p-4 hover:bg-gray-50 transition">
                                <div class="w-20 h-16 rounded-lg overflow-hidden bg-gray-200 shrink-0">
                                    @if ($booking->residence->photos->count() > 0)
                                        <img loading="lazy"
                                            src="{{ storage_url($booking->residence->photos->first()?->path) }}"
                                            alt="{{ $booking->residence->title }}" class="w-full h-full object-cover">
                                    @else
                                        <div class="w-full h-full flex items-center justify-center">
                                            <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                                            </svg>
                                        </div>
                                    @endif
                                </div>
                                <div class="flex-1 min-w-0">
                                    <h3 class="font-medium text-gray-900 truncate">{{ $booking->residence->title }}</h3>
                                    <p class="text-sm text-gray-500">
                                        {{ $booking->check_in->translatedFormat('d M') }} →
                                        {{ $booking->check_out->translatedFormat('d M Y') }}
                                    </p>
                                </div>
                                <div class="text-right shrink-0">
                                    <p class="text-sm font-semibold text-gray-900">
                                        {{ number_format($booking->total_price, 0, ',', ' ') }} FCFA</p>
                                    <p class="text-xs text-green-600 font-medium">
                                        {{ $booking->check_in->diffForHumans() }}</p>
                                </div>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="grid lg:grid-cols-3 gap-8">
                {{-- Colonne principale (2/3) --}}
                <div class="lg:col-span-2 space-y-8">
                    {{-- Recommandations personnalisées --}}
                    @if ($recommendations->count() > 0)
                        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 bg-orange-100 rounded-lg flex items-center justify-center">
                                        <span class="text-lg">✨</span>
                                    </div>
                                    <h2 class="font-semibold text-gray-900">Recommandé pour vous</h2>
                                </div>
                                <a href="{{ route('residences.index') }}"
                                    class="text-sm text-orange-500 hover:text-orange-600 font-medium">
                                    Voir plus →
                                </a>
                            </div>
                            <div class="p-4">
                                <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
                                    @foreach ($recommendations as $residence)
                                        <a href="{{ route('residences.show', $residence) }}" class="group block">
                                            <div class="relative aspect-4/3 rounded-lg overflow-hidden mb-2">
                                                @if ($residence->photos->count() > 0)
                                                    <img loading="lazy"
                                                        src="{{ storage_url($residence->photos->first()?->path) }}"
                                                        alt="{{ $residence->title }}"
                                                        class="w-full h-full object-cover group-hover:scale-105 transition duration-300">
                                                @else
                                                    <div
                                                        class="w-full h-full bg-gray-200 flex items-center justify-center">
                                                        <svg class="w-8 h-8 text-gray-400" fill="none"
                                                            stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                                                        </svg>
                                                    </div>
                                                @endif
                                                <div
                                                    class="absolute top-2 left-2 px-2 py-1 bg-orange-500 text-white text-xs font-medium rounded">
                                                    {{ number_format($residence->price, 0, ',', ' ') }}
                                                    FCFA/{{ $residence->price_label }}
                                                </div>
                                            </div>
                                            <h3
                                                class="font-medium text-gray-900 text-sm truncate group-hover:text-orange-500">
                                                {{ $residence->title }}</h3>
                                            <p class="text-xs text-gray-500">{{ $residence->commune }}</p>
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- Résidences récemment visitées --}}
                    @if ($recentViews->count() > 0)
                        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 bg-purple-100 rounded-lg flex items-center justify-center">
                                        <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </div>
                                    <h2 class="font-semibold text-gray-900">Récemment consultés</h2>
                                </div>
                                <a href="{{ route('client.view-history') }}"
                                    class="text-sm text-orange-500 hover:text-orange-600 font-medium">
                                    Tout voir →
                                </a>
                            </div>
                            <div class="p-4">
                                <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
                                    @foreach ($recentViews as $view)
                                        @if ($view->residence)
                                            <a href="{{ route('residences.show', $view->residence) }}"
                                                class="group block">
                                                <div class="relative aspect-4/3 rounded-lg overflow-hidden mb-2">
                                                    @if ($view->residence->photos->count() > 0)
                                                        <img loading="lazy"
                                                            src="{{ storage_url($view->residence->photos->first()?->path) }}"
                                                            alt="{{ $view->residence->title }}"
                                                            class="w-full h-full object-cover group-hover:scale-105 transition duration-300">
                                                    @else
                                                        <div
                                                            class="w-full h-full bg-gray-200 flex items-center justify-center">
                                                            <svg class="w-8 h-8 text-gray-400" fill="none"
                                                                stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    stroke-width="2"
                                                                    d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                                                            </svg>
                                                        </div>
                                                    @endif
                                                    <div
                                                        class="absolute bottom-2 left-2 px-2 py-1 bg-black/60 text-white text-xs rounded">
                                                        {{ \Carbon\Carbon::parse($view->last_viewed)->diffForHumans() }}
                                                    </div>
                                                </div>
                                                <h3
                                                    class="font-medium text-gray-900 text-sm truncate group-hover:text-orange-500">
                                                    {{ $view->residence->title }}</h3>
                                                <p class="text-xs text-gray-500">
                                                    {{ number_format($view->residence->price, 0, ',', ' ') }}
                                                    FCFA/{{ $view->residence->price_label }}</p>
                                            </a>
                                        @endif
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- Nouvelles résidences dans vos zones --}}
                    @if ($newInFavoriteAreas->count() > 0)
                        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 bg-amber-100 rounded-lg flex items-center justify-center">
                                        <span class="text-lg">🆕</span>
                                    </div>
                                    <h2 class="font-semibold text-gray-900">Nouveautés dans vos zones</h2>
                                </div>
                            </div>
                            <div class="divide-y divide-gray-100">
                                @foreach ($newInFavoriteAreas as $residence)
                                    <a href="{{ route('residences.show', $residence) }}"
                                        class="flex items-center gap-4 p-4 hover:bg-gray-50 transition">
                                        <div class="w-20 h-16 rounded-lg overflow-hidden bg-gray-200 shrink-0">
                                            @if ($residence->photos->count() > 0)
                                                <img loading="lazy"
                                                    src="{{ storage_url($residence->photos->first()?->path) }}"
                                                    alt="{{ $residence->title }}" class="w-full h-full object-cover">
                                            @endif
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-center gap-2 mb-1">
                                                <span
                                                    class="px-2 py-0.5 bg-green-100 text-green-700 text-xs font-medium rounded">Nouveau</span>
                                                <span
                                                    class="text-xs text-gray-500">{{ $residence->created_at->diffForHumans() }}</span>
                                            </div>
                                            <h3 class="font-medium text-gray-900 truncate">{{ $residence->title }}</h3>
                                            <p class="text-sm text-gray-500">{{ $residence->commune }} •
                                                {{ number_format($residence->price, 0, ',', ' ') }}
                                                FCFA/{{ $residence->price_label }}</p>
                                        </div>
                                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9 5l7 7-7 7" />
                                        </svg>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>

                {{-- Sidebar widgets (1/3) --}}
                <div class="space-y-6">
                    {{-- Recherches récentes --}}
                    @if ($recentSearches->count() > 0)
                        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                                <h3 class="font-semibold text-gray-900">Recherches récentes</h3>
                                <a href="{{ route('client.search-history') }}"
                                    class="text-xs text-orange-500 hover:text-orange-600 font-medium">
                                    Voir tout
                                </a>
                            </div>
                            <div class="divide-y divide-gray-100">
                                @foreach ($recentSearches as $search)
                                    <a href="{{ $search->search_url }}"
                                        class="flex items-center gap-3 p-4 hover:bg-gray-50 transition">
                                        <div
                                            class="w-8 h-8 bg-gray-100 rounded-lg flex items-center justify-center shrink-0">
                                            <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                            </svg>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm font-medium text-gray-900 truncate">
                                                {{ $search->description }}</p>
                                            <p class="text-xs text-gray-500">{{ $search->results_count }} résultats •
                                                {{ $search->created_at->diffForHumans() }}</p>
                                        </div>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- Conversations récentes --}}
                    @if ($recentConversations->count() > 0)
                        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <div class="w-6 h-6 bg-orange-100 rounded-md flex items-center justify-center">
                                        <svg class="w-3.5 h-3.5 text-orange-600" fill="none" stroke="currentColor"
                                            stroke-width="1.8" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M8.625 12a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 01-2.555-.337A5.972 5.972 0 015.41 20.97a5.969 5.969 0 01-.474-.065 4.48 4.48 0 00.978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25z" />
                                        </svg>
                                    </div>
                                    <h3 class="font-semibold text-gray-900">Conversations</h3>
                                </div>
                                <a href="{{ route('chat.index') }}"
                                    class="text-xs text-orange-500 hover:text-orange-600 font-medium">
                                    Voir tout
                                </a>
                            </div>
                            <div class="divide-y divide-gray-100">
                                @foreach ($recentConversations as $conversation)
                                    <a href="{{ route('chat.show', $conversation) }}"
                                        class="flex items-center gap-3 p-4 hover:bg-gray-50 transition group">
                                        <div class="relative shrink-0">
                                            <div class="w-10 h-10 rounded-full overflow-hidden bg-gray-200">
                                                @if ($conversation->owner->avatar || $conversation->owner->profile_photo)
                                                    <img loading="lazy" src="{{ $conversation->owner->getAvatarUrl() }}"
                                                        alt="{{ $conversation->owner->name }}"
                                                        class="w-full h-full object-cover">
                                                @else
                                                    <div
                                                        class="w-full h-full flex items-center justify-center bg-orange-100">
                                                        <span
                                                            class="text-sm font-medium text-orange-500">{{ strtoupper(substr($conversation->owner->name, 0, 1)) }}</span>
                                                    </div>
                                                @endif
                                            </div>
                                            {{-- Online indicator --}}
                                            @if ($conversation->owner->last_active_at && $conversation->owner->last_active_at->diffInMinutes(now()) < 15)
                                                <span
                                                    class="absolute bottom-0 right-0 w-2.5 h-2.5 bg-emerald-400 border-2 border-white rounded-full"></span>
                                            @endif
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-center justify-between gap-2 mb-0.5">
                                                <p
                                                    class="text-sm font-medium text-gray-900 truncate group-hover:text-orange-600">
                                                    {{ $conversation->owner->name }}</p>
                                                @if ($conversation->last_message_at)
                                                    <span
                                                        class="text-[10px] text-gray-400 shrink-0">{{ \Carbon\Carbon::parse($conversation->last_message_at)->diffForHumans(null, true) }}</span>
                                                @endif
                                            </div>
                                            <p class="text-xs text-gray-500 truncate">
                                                @if ($conversation->messages->count() > 0)
                                                    {{ Str::limit($conversation->messages->first()->content, 40) }}
                                                @else
                                                    Aucun message
                                                @endif
                                            </p>
                                        </div>
                                        @if ($conversation->unread_count > 0)
                                            <span
                                                class="min-w-5 h-5 bg-rose-500 text-white text-[10px] font-bold rounded-full flex items-center justify-center px-1 shrink-0">{{ $conversation->unread_count }}</span>
                                        @endif
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- Contacts en attente --}}
                    @if ($pendingContacts->count() > 0)
                        <div class="bg-amber-50 rounded-xl border border-amber-100 p-4">
                            <div class="flex items-center gap-3 mb-3">
                                <div class="w-8 h-8 bg-amber-100 rounded-lg flex items-center justify-center">
                                    <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <h3 class="font-semibold text-amber-800">En attente de réponse</h3>
                            </div>
                            <div class="space-y-2">
                                @foreach ($pendingContacts as $contact)
                                    <div class="bg-white rounded-lg p-3 border border-amber-200">
                                        <p class="text-sm font-medium text-gray-900 truncate">
                                            {{ $contact->residence->title }}</p>
                                        <p class="text-xs text-gray-500">Envoyé
                                            {{ $contact->created_at->diffForHumans() }}</p>
                                    </div>
                                @endforeach
                            </div>
                            <a href="{{ route('client.contacts') }}"
                                class="block mt-3 text-center text-sm text-amber-700 hover:text-amber-800 font-medium">
                                Voir toutes mes demandes →
                            </a>
                        </div>
                    @endif
                </div>
            </div>

        </div>{{-- end x-show="loaded" --}}
    </div>{{-- end x-data --}}
@endsection
