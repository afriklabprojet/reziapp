@extends('layouts.owner')

@section('title', 'Dashboard - Espace Propriétaire | Rezi App')

@section('owner-content')
    <div x-data="ownerDashboard({{ \Illuminate\Support\Js::encode(['csrfToken' => csrf_token(), 'chartData' => $chartData, 'hostScore' => $hostScore['score']]) }})" x-init="init()" class="min-h-screen bg-gray-50/50">

        {{-- ============================== SKELETON LOADER (Airbnb-style shimmer) ============================== --}}
        <div x-ref="skeleton" x-show="!loaded" x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="absolute inset-0 z-10">
            <div class="bg-white border-b border-gray-100">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8">
                    <div class="flex items-center gap-4">
                        <div class="w-14 h-14 rounded-full bg-gray-200 animate-pulse"></div>
                        <div class="space-y-2">
                            <div class="h-6 w-48 bg-gray-200 rounded-lg animate-pulse"></div>
                            <div class="h-4 w-64 bg-gray-100 rounded animate-pulse"></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
                {{-- KPI skeleton --}}
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-3 sm:gap-4 mb-8">
                    <div class="bg-gray-800 rounded-2xl p-5 col-span-2 lg:col-span-1 animate-pulse h-28"></div>
                    @for ($sk = 0; $sk < 4; $sk++)
                        <div class="bg-white rounded-2xl border border-gray-100 p-5 animate-pulse">
                            <div class="w-10 h-10 bg-gray-100 rounded-xl mb-3"></div>
                            <div class="h-8 w-16 bg-gray-200 rounded mb-1"></div>
                            <div class="h-3 w-20 bg-gray-100 rounded"></div>
                        </div>
                    @endfor
                </div>
                {{-- Grid skeleton --}}
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <div class="lg:col-span-2 space-y-6">
                        <div class="bg-white rounded-2xl border border-gray-100 p-6 animate-pulse h-64"></div>
                        <div class="bg-white rounded-2xl border border-gray-100 p-6 animate-pulse h-48"></div>
                    </div>
                    <div class="space-y-6">
                        <div class="bg-gray-800 rounded-2xl p-5 animate-pulse h-52"></div>
                        <div class="bg-white rounded-2xl border border-gray-100 p-5 animate-pulse h-40"></div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ============================== REAL CONTENT (revealed after load) ============================== --}}
        <div x-show="loaded" x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0">

            {{-- ============================== HEADER ============================== --}}
            <div class="bg-white border-b border-gray-100">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <div class="flex items-center gap-4">
                            <div class="relative">
                                <div
                                    class="w-12 h-12 sm:w-14 sm:h-14 rounded-full bg-linear-to-br from-[#FF8A1F] to-[#CC5A00] flex items-center justify-center text-white font-bold text-lg sm:text-xl shadow-lg shadow-none">
                                    {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                                </div>
                                @if ($hostScore['score'] >= 70)
                                    <div
                                        class="absolute -bottom-1 -right-1 w-6 h-6 bg-amber-400 rounded-full flex items-center justify-center text-[10px] shadow-sm border-2 border-white">
                                        {{ $hostScore['level']['icon'] }}
                                    </div>
                                @endif
                            </div>
                            <div>
                                <h1 class="text-xl sm:text-2xl font-bold text-gray-900">
                                    {{ $greeting }}, {{ auth()->user()->name }} 👋
                                </h1>
                                <div class="flex items-center gap-2 mt-0.5 flex-wrap">
                                    <p class="text-sm text-gray-500">{{ now()->translatedFormat('l j F Y') }}</p>
                                    <span class="text-gray-300">·</span>
                                    <span class="text-sm text-[#CC5A00] font-medium">{{ $stats['approved_residences'] }}
                                        annonce{{ $stats['approved_residences'] > 1 ? 's' : '' }}
                                        active{{ $stats['approved_residences'] > 1 ? 's' : '' }}</span>
                                    @if ($unreadMessages > 0)
                                        <span class="text-gray-300">·</span>
                                        <a href="{{ route('chat.index') }}"
                                            class="inline-flex items-center gap-1 text-sm text-red-600 font-medium hover:text-red-700">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" />
                                            </svg>
                                            {{ $unreadMessages }} message{{ $unreadMessages > 1 ? 's' : '' }}
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            {{-- Notification bell --}}
                            <a href="{{ route('owner.notifications') }}"
                                class="relative p-2 rounded-xl hover:bg-gray-100 transition-colors group"
                                title="Notifications">
                                <svg class="w-5 h-5 text-gray-500 group-hover:text-gray-700" fill="none"
                                    stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" />
                                </svg>
                                @if (($stats['pending_contacts'] ?? 0) + ($unreadMessages ?? 0) + ($bookingsData['pending'] ?? 0) > 0)
                                    <span
                                        class="absolute -top-0.5 -right-0.5 w-4.5 h-4.5 bg-red-500 text-white text-[9px] font-bold rounded-full flex items-center justify-center ring-2 ring-white">
                                        {{ min(($stats['pending_contacts'] ?? 0) + ($unreadMessages ?? 0) + ($bookingsData['pending'] ?? 0), 99) }}
                                    </span>
                                @endif
                            </a>

                            <a href="{{ route('owner.residences.create') }}"
                                class="inline-flex items-center px-5 py-2.5 bg-gray-900 text-white font-semibold rounded-xl hover:bg-gray-800 transition-all shadow-sm hover:shadow-md text-sm gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                                </svg>
                                Nouvelle annonce
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8">

                {{-- ============================== VÉRIFICATION IDENTITÉ ============================== --}}
                @php
                    $showVerificationBanner =
                        !$verificationStatus || in_array($verificationStatus, ['pending', 'rejected', 'expired']);
                    $showVerificationPending = in_array($verificationStatus, [
                        'submitted',
                        'processing',
                        'manual_review',
                    ]);
                @endphp

                @if ($showVerificationBanner)
                    <div
                        class="mb-6 sm:mb-8 bg-linear-to-r from-amber-50 to-[#FFF4EB] rounded-2xl border border-amber-200/60 p-5 sm:p-6">
                        <div class="flex flex-col sm:flex-row sm:items-center gap-4">
                            <div class="w-12 h-12 bg-white rounded-xl shadow-sm flex items-center justify-center shrink-0">
                                <svg class="w-6 h-6 text-amber-500" fill="none" stroke="currentColor" stroke-width="1.8"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
                                </svg>
                            </div>
                            <div class="flex-1">
                                <h3 class="text-sm font-bold text-gray-900">Vérifiez votre identité</h3>
                                <p class="text-xs text-gray-600 mt-0.5">Les propriétaires vérifiés reçoivent <span
                                        class="font-semibold text-amber-700">2x plus de contacts</span> et inspirent
                                    davantage
                                    confiance.</p>
                                @if ($verificationStatus === 'rejected')
                                    <p class="text-xs text-red-600 font-medium mt-1">Votre précédente demande a été refusée.
                                        Vous pouvez réessayer.</p>
                                @endif
                            </div>
                            <a href="{{ route('verification.dashboard') }}"
                                class="inline-flex items-center gap-2 px-5 py-2.5 bg-gray-900 text-white text-sm font-semibold rounded-xl hover:bg-gray-800 transition-all shadow-sm hover:shadow-md shrink-0">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
                                </svg>
                                Vérifier mon identité
                            </a>
                        </div>
                    </div>
                @elseif($showVerificationPending)
                    <div class="mb-6 sm:mb-8 bg-blue-50/60 rounded-2xl border border-blue-200/50 p-5 sm:p-6">
                        <div class="flex items-center gap-4">
                            <div class="w-10 h-10 bg-blue-100 rounded-xl flex items-center justify-center shrink-0">
                                <svg class="w-5 h-5 text-blue-600 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10"
                                        stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor"
                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                    </path>
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-gray-900">Vérification en cours</p>
                                <p class="text-xs text-gray-500 mt-0.5">Votre demande est en cours de traitement. Vous
                                    serez
                                    notifié par email.</p>
                            </div>
                            <a href="{{ route('verification.dashboard') }}"
                                class="ml-auto text-xs font-semibold text-blue-600 hover:text-blue-700 shrink-0">Voir le
                                statut
                                →</a>
                        </div>
                    </div>
                @endif

                {{-- ============================== TÂCHES DU JOUR ============================== --}}
                @if (collect($todayTasks)->contains('urgent', true) || collect($todayTasks)->where('icon', '!=', 'check')->isNotEmpty())
                    <div class="mb-6 sm:mb-8">
                        <h2 class="text-base font-bold text-gray-900 mb-3 flex items-center gap-2">
                            <span class="w-2 h-2 bg-[#F16A00] rounded-full animate-pulse"></span>
                            À faire aujourd'hui
                        </h2>
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                            @foreach ($todayTasks as $task)
                                <div
                                    class="bg-white rounded-xl border {{ $task['urgent'] ? 'border-red-200 bg-red-50/30' : 'border-gray-100' }} p-4 flex items-start gap-3 hover:shadow-md transition-shadow">
                                    <div @class([
                                        'w-10 h-10 rounded-xl flex items-center justify-center shrink-0',
                                        'bg-red-100' => $task['color'] === 'red',
                                        'bg-yellow-100' => $task['color'] === 'yellow',
                                        'bg-[#FFE7D1]' => $task['color'] === 'orange',
                                        'bg-green-100' => $task['color'] === 'green',
                                        'bg-blue-100' => $task['color'] === 'blue',
                                    ])>
                                        @if ($task['icon'] === 'mail')
                                            <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor"
                                                stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" />
                                            </svg>
                                        @elseif($task['icon'] === 'clock')
                                            <svg class="w-5 h-5 text-yellow-600" fill="none" stroke="currentColor"
                                                stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                            </svg>
                                        @elseif($task['icon'] === 'camera')
                                            <svg class="w-5 h-5 text-[#CC5A00]" fill="none" stroke="currentColor"
                                                stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M6.827 6.175A2.31 2.31 0 0 1 5.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 0 0-1.134-.175 2.31 2.31 0 0 1-1.64-1.055l-.822-1.316a2.192 2.192 0 0 0-1.736-1.039 48.774 48.774 0 0 0-5.232 0 2.192 2.192 0 0 0-1.736 1.039l-.821 1.316Z" />
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M16.5 12.75a4.5 4.5 0 1 1-9 0 4.5 4.5 0 0 1 9 0Z" />
                                            </svg>
                                        @elseif($task['icon'] === 'alert')
                                            <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor"
                                                stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                                            </svg>
                                        @elseif($task['icon'] === 'booking')
                                            <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor"
                                                stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                                            </svg>
                                        @elseif($task['icon'] === 'checkin')
                                            <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor"
                                                stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 3-3m0 0-3-3m3 3H9" />
                                            </svg>
                                        @else
                                            <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor"
                                                stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                            </svg>
                                        @endif
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-semibold text-gray-900">{{ $task['title'] }}</p>
                                        <p class="text-xs text-gray-500 mt-0.5">{{ $task['description'] }}</p>
                                        @if ($task['action_url'])
                                            <a href="{{ $task['action_url'] }}"
                                                class="inline-flex items-center text-xs font-semibold text-[#CC5A00] hover:text-[#A34700] mt-2 gap-1">
                                                {{ $task['action_text'] }}
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor"
                                                    stroke-width="2" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                                                </svg>
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- ============================== RÉSERVATIONS (STYLE AIRBNB) ============================== --}}
                @if ($bookingsData['total_active'] > 0 || $bookingsData['pending'] > 0 || $bookingsData['upcoming']->isNotEmpty())
                    <div class="mb-6 sm:mb-8">
                        <div class="flex items-center justify-between mb-3">
                            <h2 class="text-base font-bold text-gray-900">Vos réservations</h2>
                            <a href="{{ route('owner.bookings.index') }}"
                                class="text-xs text-[#CC5A00] hover:text-[#A34700] font-semibold">Tout voir →</a>
                        </div>

                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-4">
                            <a href="{{ route('owner.bookings.index') }}"
                                class="bg-white rounded-xl border border-gray-100 p-4 hover:shadow-md transition-shadow text-center">
                                <p class="text-2xl font-extrabold text-gray-900">{{ $bookingsData['checking_out'] }}</p>
                                <p class="text-[11px] text-gray-500 font-medium mt-0.5">Départs aujourd'hui</p>
                            </a>
                            <a href="{{ route('owner.bookings.index') }}"
                                class="bg-white rounded-xl border border-gray-100 p-4 hover:shadow-md transition-shadow text-center">
                                <p class="text-2xl font-extrabold text-gray-900">{{ $bookingsData['currently_hosting'] }}
                                </p>
                                <p class="text-[11px] text-gray-500 font-medium mt-0.5">En cours de séjour</p>
                            </a>
                            <a href="{{ route('owner.bookings.index') }}"
                                class="bg-white rounded-xl border border-gray-100 p-4 hover:shadow-md transition-shadow text-center">
                                <p class="text-2xl font-extrabold text-gray-900">{{ $bookingsData['arriving_soon'] }}</p>
                                <p class="text-[11px] text-gray-500 font-medium mt-0.5">Arrivées sous 7j</p>
                            </a>
                            <a href="{{ route('owner.bookings.requests') }}"
                                class="bg-white rounded-xl border {{ $bookingsData['pending'] > 0 ? 'border-[#FFD0A3] bg-[#FFF4EB]/50' : 'border-gray-100' }} p-4 hover:shadow-md transition-shadow text-center">
                                <p
                                    class="text-2xl font-extrabold {{ $bookingsData['pending'] > 0 ? 'text-[#CC5A00]' : 'text-gray-900' }}">
                                    {{ $bookingsData['pending'] }}</p>
                                <p
                                    class="text-[11px] {{ $bookingsData['pending'] > 0 ? 'text-[#CC5A00]' : 'text-gray-500' }} font-medium mt-0.5">
                                    En attente</p>
                            </a>
                        </div>

                        @if ($bookingsData['upcoming']->isNotEmpty())
                            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                                <div class="divide-y divide-gray-50">
                                    @foreach ($bookingsData['upcoming'] as $booking)
                                        <a href="{{ route('owner.bookings.show', $booking) }}"
                                            class="flex items-center gap-4 p-4 hover:bg-gray-50/50 transition-colors">
                                            <div
                                                class="w-10 h-10 rounded-xl bg-blue-50 flex items-center justify-center shrink-0">
                                                <span
                                                    class="text-sm font-bold text-blue-600">{{ $booking->check_in->format('d') }}</span>
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <div class="flex items-center gap-2">
                                                    <p class="text-sm font-semibold text-gray-900 truncate">
                                                        {{ $booking->user->name ?? 'Locataire' }}</p>
                                                    @if ($booking->status === 'pending')
                                                        <span
                                                            class="text-[10px] px-1.5 py-0.5 bg-[#FFE7D1] text-[#A34700] rounded font-semibold">À
                                                            confirmer</span>
                                                    @endif
                                                </div>
                                                <p class="text-xs text-gray-500 truncate">
                                                    {{ $booking->residence->name ?? '' }} ·
                                                    {{ $booking->check_in->translatedFormat('d M') }} →
                                                    {{ $booking->check_out->translatedFormat('d M') }}</p>
                                            </div>
                                            <div class="text-right shrink-0">
                                                <p class="text-sm font-bold text-gray-900">
                                                    {{ number_format($booking->total_amount, 0, ',', ' ') }} F</p>
                                                <p class="text-[11px] text-gray-400">{{ $booking->nights }}
                                                    nuit{{ $booking->nights > 1 ? 's' : '' }}</p>
                                            </div>
                                            <svg class="w-4 h-4 text-gray-300 shrink-0" fill="none"
                                                stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                                            </svg>
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                @endif

                {{-- ============================== BANNIÈRE ONBOARDING (nouveaux proprio) ============================== --}}
                @if($stats['total_residences'] === 0)
                <div class="mb-6 bg-linear-to-r from-[#F16A00] to-amber-500 rounded-2xl p-5 text-white shadow-lg shadow-none" x-data="{ closed: false }" x-show="!closed" x-transition>
                    <div class="flex items-start gap-4">
                        <div class="w-10 h-10 bg-white/20 rounded-xl flex items-center justify-center shrink-0 text-xl">🚀</div>
                        <div class="flex-1 min-w-0">
                            <p class="font-bold text-white text-base">Publiez votre première résidence en 5 minutes</p>
                            <p class="text-[#FFE7D1] text-sm mt-0.5">Rejoignez {{ number_format($totalOwners) }}+ propriétaires actifs sur Rezi App. Gratuit, sans commission.</p>
                        </div>
                        <div class="flex items-center gap-3 shrink-0">
                            <a href="{{ route('owner.residences.create') }}"
                                class="bg-white text-[#CC5A00] font-bold text-sm px-4 py-2 rounded-xl hover:bg-[#FFF4EB] transition-colors whitespace-nowrap">
                                Publier maintenant
                            </a>
                            <button @click="closed = true" class="text-white/60 hover:text-white transition-colors" aria-label="Fermer">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                            </button>
                        </div>
                    </div>
                    {{-- Progress steps mini --}}
                    <div class="mt-4 flex flex-wrap gap-2">
                        @foreach(['Créer un compte ✅', 'Publier ma résidence', 'Recevoir mon premier contact'] as $step)
                        <div class="flex items-center gap-1.5 text-xs {{ $loop->first ? 'text-white' : 'text-[#FFD0A3]' }}">
                            @if(!$loop->first)<span class="text-[#FFB46F]">→</span>@endif
                            {{ $step }}
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif

                {{-- ============================== KPI CARDS ============================== --}}
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-3 sm:gap-4 mb-6 sm:mb-8">
                    {{-- Revenus ce mois --}}
                    <div
                        class="bg-linear-to-br from-gray-900 to-gray-800 rounded-2xl p-4 sm:p-5 text-white col-span-2 lg:col-span-1 relative overflow-hidden">
                        <div class="absolute top-0 right-0 w-20 h-20 bg-white/5 rounded-full -mr-6 -mt-6"></div>
                        <div class="flex items-center gap-2 mb-3">
                            <div class="w-8 h-8 bg-white/10 rounded-lg flex items-center justify-center">
                                <svg class="w-4 h-4 text-green-400" fill="none" stroke="currentColor"
                                    stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm3 0h.008v.008H18V10.5Zm-12 0h.008v.008H6V10.5Z" />
                                </svg>
                            </div>
                            <span class="text-xs font-medium text-gray-400 uppercase tracking-wide">Revenus</span>
                            @if ($revenueData['trend'] != 0)
                                <span
                                    class="ml-auto text-xs font-bold px-1.5 py-0.5 rounded {{ $revenueData['trend'] > 0 ? 'bg-green-500/20 text-green-400' : 'bg-red-500/20 text-red-400' }}">
                                    {{ $revenueData['trend'] > 0 ? '+' : '' }}{{ $revenueData['trend'] }}%
                                </span>
                            @endif
                        </div>
                        <p class="text-2xl sm:text-3xl font-extrabold tracking-tight">
                            {{ number_format($revenueData['this_month'], 0, ',', ' ') }}
                            <span class="text-sm font-medium text-gray-400">FCFA</span>
                        </p>
                        <p class="text-xs text-gray-500 mt-1">Ce mois-ci</p>
                    </div>

                    {{-- Résidences --}}
                    <div
                        class="bg-white rounded-2xl shadow-sm p-4 sm:p-5 border border-gray-100 hover:shadow-md transition-shadow">
                        <div class="flex items-center justify-between mb-3">
                            <div class="w-10 h-10 bg-blue-50 rounded-xl flex items-center justify-center">
                                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor"
                                    stroke-width="1.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
                                </svg>
                            </div>
                            @if ($stats['pending_residences'] > 0)
                                <span
                                    class="text-xs font-semibold px-2 py-0.5 rounded-full bg-yellow-100 text-yellow-700">{{ $stats['pending_residences'] }}
                                    en attente</span>
                            @endif
                        </div>
                        <p class="text-2xl sm:text-3xl font-extrabold text-gray-900">{{ $stats['total_residences'] }}</p>
                        <p class="text-xs text-gray-500 mt-1">Résidences</p>
                    </div>

                    {{-- Vues --}}
                    <div
                        class="bg-white rounded-2xl shadow-sm p-4 sm:p-5 border border-gray-100 hover:shadow-md transition-shadow">
                        <div class="flex items-center justify-between mb-3">
                            <div class="w-10 h-10 bg-purple-50 rounded-xl flex items-center justify-center">
                                <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor"
                                    stroke-width="1.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                </svg>
                            </div>
                            @if ($viewsTrend != 0)
                                <span
                                    class="text-xs font-semibold px-2 py-0.5 rounded-full {{ $viewsTrend > 0 ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">{{ $viewsTrend > 0 ? '+' : '' }}{{ $viewsTrend }}%</span>
                            @endif
                        </div>
                        <p class="text-2xl sm:text-3xl font-extrabold text-gray-900">
                            {{ number_format($stats['total_views']) }}</p>
                        <p class="text-xs text-gray-500 mt-1">Vues ce mois</p>
                    </div>

                    {{-- Contacts --}}
                    <div
                        class="bg-white rounded-2xl shadow-sm p-4 sm:p-5 border border-gray-100 hover:shadow-md transition-shadow">
                        <div class="flex items-center justify-between mb-3">
                            <div class="w-10 h-10 bg-green-50 rounded-xl flex items-center justify-center">
                                <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor"
                                    stroke-width="1.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M8.625 12a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 0 1-2.555-.337A5.972 5.972 0 0 1 5.41 20.97a5.969 5.969 0 0 1-.474-.065 4.48 4.48 0 0 0 .978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25Z" />
                                </svg>
                            </div>
                            @if ($stats['pending_contacts'] > 0)
                                <span
                                    class="text-xs font-bold px-2 py-0.5 rounded-full bg-red-100 text-red-700 animate-pulse">{{ $stats['pending_contacts'] }}
                                    new</span>
                            @endif
                        </div>
                        <p class="text-2xl sm:text-3xl font-extrabold text-gray-900">{{ $stats['total_contacts'] }}</p>
                        <p class="text-xs text-gray-500 mt-1">Contacts</p>
                    </div>

                    {{-- Taux de conversion --}}
                    <div
                        class="bg-white rounded-2xl shadow-sm p-4 sm:p-5 border border-gray-100 hover:shadow-md transition-shadow">
                        <div class="flex items-center justify-between mb-3">
                            <div class="w-10 h-10 bg-[#FFF4EB] rounded-xl flex items-center justify-center">
                                <svg class="w-5 h-5 text-[#CC5A00]" fill="none" stroke="currentColor"
                                    stroke-width="1.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z" />
                                </svg>
                            </div>
                        </div>
                        <p class="text-2xl sm:text-3xl font-extrabold text-gray-900">{{ $conversionRate }}%</p>
                        <p class="text-xs text-gray-500 mt-1">Conversion</p>
                    </div>
                </div>

                {{-- ============================== SCORE HÔTE + MÉTRIQUES RÉPONSE ============================== --}}
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-6 sm:mb-8">
                    {{-- Score Hôte (style Superhost) --}}
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 sm:p-6">
                        <div class="flex items-center justify-between mb-4">
                            <div>
                                <h2 class="text-base font-bold text-gray-900">Score Hôte</h2>
                                <p class="text-xs text-gray-500 mt-0.5">Votre performance globale sur Rezi App</p>
                            </div>
                            <span @class([
                                'text-xs font-bold px-2.5 py-1 rounded-full',
                                'bg-amber-100 text-amber-700' => $hostScore['level']['color'] === 'amber',
                                'bg-blue-100 text-blue-700' => $hostScore['level']['color'] === 'blue',
                                'bg-green-100 text-green-700' => $hostScore['level']['color'] === 'green',
                                'bg-gray-100 text-gray-600' => $hostScore['level']['color'] === 'gray',
                            ])>
                                {{ $hostScore['level']['icon'] }} {{ $hostScore['level']['name'] }}
                            </span>
                        </div>

                        <div class="flex items-center gap-6">
                            <div class="relative w-24 h-24 shrink-0">
                                <svg class="w-24 h-24 -rotate-90" viewBox="0 0 100 100">
                                    <circle cx="50" cy="50" r="40" fill="none" stroke="#f3f4f6"
                                        stroke-width="8" />
                                    <circle cx="50" cy="50" r="40" fill="none"
                                        stroke="{{ $hostScore['score'] >= 85 ? '#f59e0b' : ($hostScore['score'] >= 70 ? '#3b82f6' : ($hostScore['score'] >= 50 ? '#22c55e' : '#9ca3af')) }}"
                                        stroke-width="8" stroke-linecap="round"
                                        stroke-dasharray="{{ 2 * 3.14159 * 40 }}"
                                        stroke-dashoffset="{{ 2 * 3.14159 * 40 * (1 - $hostScore['score'] / 100) }}"
                                        class="transition-all duration-1000" />
                                </svg>
                                <div class="absolute inset-0 flex items-center justify-center">
                                    <span class="text-2xl font-extrabold text-gray-900">{{ $hostScore['score'] }}</span>
                                </div>
                            </div>
                            <div class="flex-1 space-y-2.5">
                                @foreach ($hostScore['criteria'] as $key => $criterion)
                                    <div>
                                        <div class="flex items-center justify-between mb-0.5">
                                            <span
                                                class="text-[11px] font-medium text-gray-600">{{ $criterion['label'] }}</span>
                                            <div class="flex items-center gap-1.5">
                                                <span
                                                    class="text-[11px] font-semibold text-gray-700">{{ $criterion['value'] }}</span>
                                                @if ($criterion['met'])
                                                    <svg class="w-3.5 h-3.5 text-green-500" fill="currentColor"
                                                        viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd"
                                                            d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z"
                                                            clip-rule="evenodd" />
                                                    </svg>
                                                @else
                                                    <svg class="w-3.5 h-3.5 text-gray-300" fill="currentColor"
                                                        viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd"
                                                            d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z"
                                                            clip-rule="evenodd" />
                                                    </svg>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="w-full h-1.5 bg-gray-100 rounded-full overflow-hidden">
                                            <div class="h-full rounded-full transition-all duration-500 {{ $criterion['met'] ? 'bg-green-500' : 'bg-gray-300' }}"
                                                style="width: {{ round(($criterion['score'] / $criterion['max']) * 100) }}%">
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    {{-- Métriques de réponse --}}
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 sm:p-6">
                        <div class="mb-4">
                            <h2 class="text-base font-bold text-gray-900">Réactivité</h2>
                            <p class="text-xs text-gray-500 mt-0.5">Vos métriques de réponse aux contacts</p>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div class="text-center p-4 bg-gray-50 rounded-xl">
                                <div class="relative w-16 h-16 mx-auto mb-2">
                                    <svg class="w-16 h-16 -rotate-90" viewBox="0 0 100 100">
                                        <circle cx="50" cy="50" r="38" fill="none" stroke="#e5e7eb"
                                            stroke-width="8" />
                                        <circle cx="50" cy="50" r="38" fill="none"
                                            stroke="{{ $responseMetrics['response_rate'] >= 90 ? '#22c55e' : ($responseMetrics['response_rate'] >= 70 ? '#f59e0b' : '#ef4444') }}"
                                            stroke-width="8" stroke-linecap="round"
                                            stroke-dasharray="{{ 2 * 3.14159 * 38 }}"
                                            stroke-dashoffset="{{ 2 * 3.14159 * 38 * (1 - $responseMetrics['response_rate'] / 100) }}" />
                                    </svg>
                                    <div class="absolute inset-0 flex items-center justify-center">
                                        <span
                                            class="text-lg font-extrabold text-gray-900">{{ $responseMetrics['response_rate'] }}%</span>
                                    </div>
                                </div>
                                <p class="text-xs font-semibold text-gray-700">Taux de réponse</p>
                                <p class="text-[10px] text-gray-400 mt-0.5">Objectif : ≥ 90%</p>
                            </div>
                            <div class="text-center p-4 bg-gray-50 rounded-xl">
                                <div class="w-16 h-16 mx-auto mb-2 flex items-center justify-center">
                                    @if ($responseMetrics['avg_response_time'] !== null)
                                        <p
                                            class="text-2xl font-extrabold {{ $responseMetrics['avg_response_time'] <= 2 ? 'text-green-600' : ($responseMetrics['avg_response_time'] <= 6 ? 'text-amber-600' : 'text-red-600') }}">
                                            {{ $responseMetrics['avg_response_time'] }}h
                                        </p>
                                    @else
                                        <div class="w-12 h-12 bg-gray-200 rounded-full flex items-center justify-center">
                                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor"
                                                stroke-width="1.5" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                            </svg>
                                        </div>
                                    @endif
                                </div>
                                <p class="text-xs font-semibold text-gray-700">Temps moyen</p>
                                <p class="text-[10px] text-gray-400 mt-0.5">Objectif : ≤ 2 heures</p>
                            </div>
                        </div>
                        <div class="mt-4 flex items-center justify-between pt-4 border-t border-gray-100">
                            <div class="text-center flex-1">
                                <p class="text-lg font-extrabold text-gray-900">{{ $responseMetrics['total_contacts'] }}
                                </p>
                                <p class="text-[10px] text-gray-500">Total contacts</p>
                            </div>
                            <div class="w-px h-8 bg-gray-100"></div>
                            <div class="text-center flex-1">
                                <p class="text-lg font-extrabold text-gray-900">{{ $responseMetrics['responded'] }}</p>
                                <p class="text-[10px] text-gray-500">Répondus</p>
                            </div>
                            <div class="w-px h-8 bg-gray-100"></div>
                            <div class="text-center flex-1">
                                <p
                                    class="text-lg font-extrabold {{ $stats['pending_contacts'] > 0 ? 'text-red-600' : 'text-gray-900' }}">
                                    {{ $stats['pending_contacts'] }}</p>
                                <p class="text-[10px] text-gray-500">En attente</p>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ============================== GRAPHIQUE 30 JOURS ============================== --}}
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 sm:p-6 mb-6 sm:mb-8">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-5">
                        <div>
                            <h2 class="text-base font-bold text-gray-900">Activité des 30 derniers jours</h2>
                            <p class="text-xs text-gray-500 mt-0.5">Vues et contacts sur vos annonces</p>
                        </div>
                        <div class="flex items-center gap-4 text-xs">
                            <span class="flex items-center gap-1.5">
                                <span class="w-2.5 h-2.5 rounded-full bg-blue-500"></span>
                                <span class="text-gray-600 font-medium">Vues</span>
                            </span>
                            <span class="flex items-center gap-1.5">
                                <span class="w-2.5 h-2.5 rounded-full bg-[#F16A00]"></span>
                                <span class="text-gray-600 font-medium">Contacts</span>
                            </span>
                        </div>
                    </div>
                    <div class="relative h-48 sm:h-56">
                        <canvas x-ref="activityChart" class="w-full h-full"></canvas>
                        @if ($chartData->sum('views') === 0 && $chartData->sum('contacts') === 0)
                            <div class="absolute inset-0 flex items-center justify-center">
                                <div class="text-center">
                                    <div
                                        class="w-12 h-12 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-2">
                                        <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor"
                                            stroke-width="1.5" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z" />
                                        </svg>
                                    </div>
                                    <p class="text-sm text-gray-500">Pas encore de données</p>
                                    <p class="text-xs text-gray-400 mt-0.5">Les statistiques apparaîtront ici</p>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- ============================== MAIN GRID ============================== --}}
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 sm:gap-8">
                    {{-- ========== LEFT: 2/3 ========== --}}
                    <div class="lg:col-span-2 space-y-6 sm:space-y-8">
                        {{-- Mes annonces --}}
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                            <div
                                class="px-5 py-4 sm:px-6 sm:py-5 border-b border-gray-100 flex items-center justify-between">
                                <div>
                                    <h2 class="text-base font-bold text-gray-900">Mes annonces</h2>
                                    <p class="text-xs text-gray-500 mt-0.5">Vos {{ $stats['total_residences'] }}
                                        résidences
                                    </p>
                                </div>
                                <a href="{{ route('owner.residences.index') }}"
                                    class="text-xs text-[#CC5A00] hover:text-[#A34700] font-semibold flex items-center gap-1">
                                    Tout voir
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                                    </svg>
                                </a>
                            </div>

                            @if ($residences->isNotEmpty())
                                <div class="grid grid-cols-1 sm:grid-cols-2 divide-y sm:divide-y-0 divide-gray-100">
                                    @foreach ($residences as $index => $residence)
                                        <div class="p-4 sm:p-5 {{ $index % 2 === 0 && $index < $residences->count() - 1 ? 'sm:border-r sm:border-gray-100' : '' }} {{ $index > 1 ? 'sm:border-t sm:border-gray-100' : '' }} hover:bg-gray-50/50 transition-colors group"
                                            x-data="{ hover: false }" @mouseenter="hover = true"
                                            @mouseleave="hover = false">
                                            <div class="flex gap-3">
                                                <div
                                                    class="w-20 h-16 sm:w-24 sm:h-18 shrink-0 rounded-xl overflow-hidden bg-gray-100">
                                                    @if ($residence->photos->isNotEmpty())
                                                        <img loading="lazy"
                                                            src="{{ storage_url($residence->photos->where('is_primary', true)->first()?->path ?? $residence->photos->first()?->path) }}"
                                                            alt="{{ $residence->name }}"
                                                            class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                                                    @else
                                                        <div class="w-full h-full flex items-center justify-center">
                                                            <svg class="w-6 h-6 text-gray-300" fill="none"
                                                                stroke="currentColor" stroke-width="1.5"
                                                                viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909M18 3.75H6A2.25 2.25 0 0 0 3.75 6v12A2.25 2.25 0 0 0 6 20.25h12A2.25 2.25 0 0 0 20.25 18V6A2.25 2.25 0 0 0 18 3.75Z" />
                                                            </svg>
                                                        </div>
                                                    @endif
                                                </div>
                                                <div class="flex-1 min-w-0">
                                                    <div class="flex items-start justify-between gap-1">
                                                        <h3 class="text-sm font-semibold text-gray-900 truncate">
                                                            {{ $residence->name }}</h3>
                                                        @php
                                                            $statusConfig = match ($residence->status) {
                                                                'active', 'approved' => ['bg-green-100 text-green-700', '●'],
                                                                'pending' => ['bg-yellow-100 text-yellow-700', '◐'],
                                                                'needs_changes' => [
                                                                    'bg-[#FFE7D1] text-[#A34700]',
                                                                    '⚠',
                                                                ],
                                                                'draft' => ['bg-gray-100 text-gray-500', '○'],
                                                                'inactive' => ['bg-gray-100 text-gray-500', '○'],
                                                                default => ['bg-red-100 text-red-700', '✕'],
                                                            };
                                                        @endphp
                                                        <span
                                                            class="shrink-0 text-[10px] font-bold px-1.5 py-0.5 rounded-md {{ $statusConfig[0] }}">{{ $statusConfig[1] }}</span>
                                                    </div>
                                                    <p class="text-xs text-gray-500 mt-0.5">{{ $residence->commune }}</p>
                                                    <div class="flex items-center gap-3 mt-2 text-xs text-gray-400">
                                                        <span class="flex items-center gap-1">
                                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor"
                                                                stroke-width="1.5" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                                            </svg>
                                                            {{ $residence->views_count ?? 0 }}
                                                        </span>
                                                        <span class="flex items-center gap-1">
                                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor"
                                                                stroke-width="1.5" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" />
                                                            </svg>
                                                            {{ $residence->contacts_count ?? 0 }}
                                                        </span>
                                                        @if ($residence->reviews_count > 0)
                                                            <span class="flex items-center gap-0.5 text-amber-500">
                                                                <svg class="w-3 h-3" fill="currentColor"
                                                                    viewBox="0 0 20 20">
                                                                    <path
                                                                        d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                                                </svg>
                                                                {{ number_format($residence->average_rating, 1) }}
                                                            </span>
                                                        @endif
                                                        <span
                                                            class="font-semibold text-gray-700">{{ number_format($residence->price, 0, ',', ' ') }}
                                                            F/jour</span>
                                                    </div>
                                                    <div class="mt-2 flex items-center gap-2" x-show="hover"
                                                        x-transition.opacity>
                                                        @if ($residence->status === 'active')
                                                            <a href="{{ route('residences.show', $residence) }}"
                                                                target="_blank"
                                                                class="text-[11px] px-2 py-1 bg-blue-50 text-blue-700 rounded-md hover:bg-blue-100 transition-colors font-medium">Voir</a>
                                                        @endif
                                                        <a href="{{ route('owner.residences.edit', $residence) }}"
                                                            class="text-[11px] px-2 py-1 bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200 transition-colors font-medium">Modifier</a>
                                                        <button
                                                            @click="toggleAvailability({{ $residence->id }}, {{ $residence->is_available ? 'true' : 'false' }})"
                                                            class="text-[11px] px-2 py-1 {{ $residence->is_available ? 'bg-[#FFF4EB] text-[#A34700]' : 'bg-green-50 text-green-700' }} rounded-md hover:opacity-80 transition-colors font-medium">
                                                            {{ $residence->is_available ? 'Indisponible' : 'Disponible' }}
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="p-10 sm:p-12 text-center">
                                    <div
                                        class="w-16 h-16 bg-[#FFF4EB] rounded-2xl flex items-center justify-center mx-auto mb-4">
                                        <svg class="w-8 h-8 text-[#FF8A1F]" fill="none" stroke="currentColor"
                                            stroke-width="1.5" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
                                        </svg>
                                    </div>
                                    <h3 class="text-lg font-bold text-gray-900 mb-1">Aucune annonce</h3>
                                    <p class="text-sm text-gray-500 mb-5">Publiez votre première résidence sur Rezi App</p>
                                    <a href="{{ route('owner.residences.create') }}"
                                        class="inline-flex items-center px-5 py-2.5 bg-gray-900 text-white rounded-xl font-semibold text-sm hover:bg-gray-800 transition-colors gap-2">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                                        </svg>
                                        Créer une annonce
                                    </a>
                                </div>
                            @endif
                        </div>

                        {{-- Performance par annonce --}}
                        @if ($residences->isNotEmpty())
                            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                                <div class="px-5 py-4 sm:px-6 sm:py-5 border-b border-gray-100">
                                    <h2 class="text-base font-bold text-gray-900">Performance</h2>
                                    <p class="text-xs text-gray-500 mt-0.5">Comparaison de vos annonces</p>
                                </div>
                                <div class="p-4 sm:p-5 space-y-3">
                                    @foreach ($residences as $residence)
                                        @php
                                            $rate =
                                                $residence->views_count > 0
                                                    ? round(
                                                        (($residence->contacts_count ?? 0) / $residence->views_count) *
                                                            100,
                                                        1,
                                                    )
                                                    : 0;
                                            $maxViews = $residences->max('views_count') ?: 1;
                                            $viewsPercent = round((($residence->views_count ?? 0) / $maxViews) * 100);
                                        @endphp
                                        <div class="flex items-center gap-3">
                                            <div class="w-8 h-8 rounded-lg overflow-hidden bg-gray-100 shrink-0">
                                                @if ($residence->photos->isNotEmpty())
                                                    <img loading="lazy"
                                                        src="{{ storage_url($residence->photos->first()?->path) }}"
                                                        alt="{{ $residence->name }}" class="w-full h-full object-cover">
                                                @endif
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <div class="flex items-center justify-between mb-1">
                                                    <p class="text-xs font-semibold text-gray-700 truncate">
                                                        {{ Str::limit($residence->name, 22) }}</p>
                                                    <div class="flex items-center gap-3 text-xs text-gray-500 shrink-0">
                                                        <span>{{ $residence->views_count ?? 0 }} vues</span>
                                                        <span>{{ $residence->contacts_count ?? 0 }} contacts</span>
                                                        <span @class([
                                                            'font-bold px-1.5 py-0.5 rounded',
                                                            'bg-green-100 text-green-700' => $rate >= 5,
                                                            'bg-yellow-100 text-yellow-700' => $rate >= 2 && $rate < 5,
                                                            'bg-gray-100 text-gray-600' => $rate < 2,
                                                        ])>{{ $rate }}%</span>
                                                    </div>
                                                </div>
                                                <div class="w-full h-1.5 bg-gray-100 rounded-full overflow-hidden">
                                                    <div class="h-full bg-linear-to-r from-blue-500 to-purple-500 rounded-full transition-all duration-500"
                                                        style="width: {{ $viewsPercent }}%"></div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        {{-- ============================== AVIS & RÉPUTATION (NOUVEAU) ============================== --}}
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                            <div
                                class="px-5 py-4 sm:px-6 sm:py-5 border-b border-gray-100 flex items-center justify-between">
                                <div>
                                    <h2 class="text-base font-bold text-gray-900">Avis & Réputation</h2>
                                    <p class="text-xs text-gray-500 mt-0.5">Ce que disent vos locataires</p>
                                </div>
                                @if ($reviewsData['total'] > 0)
                                    <div class="flex items-center gap-2">
                                        <svg class="w-5 h-5 text-amber-400" fill="currentColor" viewBox="0 0 20 20">
                                            <path
                                                d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                        </svg>
                                        <span
                                            class="text-lg font-extrabold text-gray-900">{{ $reviewsData['average_rating'] }}</span>
                                        <span class="text-xs text-gray-400">({{ $reviewsData['total'] }} avis)</span>
                                        @if ($reviewsData['unanswered'] > 0)
                                            <span
                                                class="text-[10px] px-2 py-0.5 bg-red-100 text-red-700 rounded-full font-bold">{{ $reviewsData['unanswered'] }}
                                                sans réponse</span>
                                        @endif
                                    </div>
                                @endif
                            </div>

                            @if ($reviewsData['total'] > 0)
                                {{-- Star Distribution (Airbnb-style horizontal bars) --}}
                                <div class="px-5 py-4 sm:px-6 border-b border-gray-50 bg-gray-50/30">
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                                        {{-- Distribution bars --}}
                                        <div class="space-y-1.5">
                                            @for ($star = 5; $star >= 1; $star--)
                                                @php
                                                    $count = $starDistribution[$star] ?? 0;
                                                    $percent =
                                                        $reviewsData['total'] > 0
                                                            ? round(($count / $reviewsData['total']) * 100)
                                                            : 0;
                                                @endphp
                                                <div class="flex items-center gap-2">
                                                    <span
                                                        class="text-xs font-medium text-gray-600 w-3 text-right">{{ $star }}</span>
                                                    <svg class="w-3 h-3 text-amber-400 shrink-0" fill="currentColor"
                                                        viewBox="0 0 20 20">
                                                        <path
                                                            d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                                    </svg>
                                                    <div class="flex-1 h-2 bg-gray-200 rounded-full overflow-hidden">
                                                        <div class="h-full bg-gray-900 rounded-full transition-all duration-500"
                                                            style="width: {{ $percent }}%"></div>
                                                    </div>
                                                    <span
                                                        class="text-[10px] text-gray-400 w-6 text-right">{{ $count }}</span>
                                                </div>
                                            @endfor
                                        </div>
                                        {{-- Detailed ratings --}}
                                        @if (!empty($reviewsData['detailed_ratings']))
                                            <div class="space-y-2">
                                                @foreach ($reviewsData['detailed_ratings'] as $category => $rating)
                                                    @php
                                                        $categoryLabels = [
                                                            'cleanliness' => 'Propreté',
                                                            'location' => 'Emplacement',
                                                            'value' => 'Rapport qualité/prix',
                                                            'communication' => 'Communication',
                                                            'accuracy' => 'Exactitude',
                                                            'checkin' => 'Arrivée',
                                                        ];
                                                    @endphp
                                                    <div class="flex items-center justify-between">
                                                        <span
                                                            class="text-[11px] text-gray-600">{{ $categoryLabels[$category] ?? $category }}</span>
                                                        <div class="flex items-center gap-1.5">
                                                            <div class="w-16 h-1 bg-gray-200 rounded-full overflow-hidden">
                                                                <div class="h-full bg-gray-900 rounded-full"
                                                                    style="width: {{ ($rating / 5) * 100 }}%"></div>
                                                            </div>
                                                            <span
                                                                class="text-[11px] font-semibold text-gray-700 w-6 text-right">{{ $rating }}</span>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                <div class="divide-y divide-gray-50">
                                    @foreach ($reviewsData['recent'] as $review)
                                        <div class="p-4 sm:p-5">
                                            <div class="flex items-start gap-3">
                                                <div
                                                    class="w-9 h-9 rounded-full bg-linear-to-br from-gray-400 to-gray-500 flex items-center justify-center text-white font-semibold text-xs shrink-0">
                                                    {{ strtoupper(substr($review->user->name ?? 'A', 0, 1)) }}
                                                </div>
                                                <div class="flex-1 min-w-0">
                                                    <div class="flex items-center gap-2 mb-1">
                                                        <p class="text-sm font-semibold text-gray-900">
                                                            {{ $review->user->name ?? 'Anonyme' }}</p>
                                                        <div class="flex items-center gap-0.5">
                                                            @for ($i = 1; $i <= 5; $i++)
                                                                <svg class="w-3 h-3 {{ $i <= $review->rating ? 'text-amber-400' : 'text-gray-200' }}"
                                                                    fill="currentColor" viewBox="0 0 20 20">
                                                                    <path
                                                                        d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                                                </svg>
                                                            @endfor
                                                        </div>
                                                        <span
                                                            class="text-[10px] text-gray-400">{{ $review->created_at->diffForHumans() }}</span>
                                                    </div>
                                                    <p class="text-xs text-gray-500 mb-1">
                                                        {{ $review->residence->name ?? '' }}</p>
                                                    <p class="text-sm text-gray-700 line-clamp-2">{{ $review->comment }}
                                                    </p>
                                                    @if ($review->owner_response)
                                                        <div class="mt-2 pl-3 border-l-2 border-[#FFD0A3]">
                                                            <p class="text-[11px] text-[#CC5A00] font-medium">Votre
                                                                réponse
                                                            </p>
                                                            <p class="text-xs text-gray-600 line-clamp-1">
                                                                {{ $review->owner_response }}</p>
                                                        </div>
                                                    @else
                                                        {{-- Inline reply toggle --}}
                                                        <button
                                                            @click="toggleReply({{ $review->id }})"
                                                            class="inline-flex items-center text-[11px] text-[#CC5A00] font-medium mt-1.5 gap-0.5 hover:text-[#A34700] transition-colors">
                                                            <svg class="w-3 h-3" fill="none" stroke="currentColor"
                                                                stroke-width="2" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    d="M12 4v16m8-8H4" />
                                                            </svg>
                                                            Répondre
                                                        </button>
                                                        {{-- Inline reply form --}}
                                                        <div x-show="replyingTo === {{ $review->id }}"
                                                            x-transition:enter="transition ease-out duration-200"
                                                            x-transition:enter-start="opacity-0 -translate-y-1"
                                                            x-transition:enter-end="opacity-100 translate-y-0"
                                                            class="mt-2" x-cloak>
                                                            <textarea x-ref="replyInput{{ $review->id }}" x-model="replyText"
                                                                class="w-full border border-gray-200 rounded-xl px-3 py-2 text-xs text-gray-700 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#F16A00]/20 focus:border-[#FF8A1F] resize-none transition-all"
                                                                rows="2" placeholder="Écrivez votre réponse (min. 10 caractères)..." maxlength="1000"></textarea>
                                                            <div class="flex items-center justify-between mt-1.5">
                                                                <span class="text-[10px] text-gray-400"
                                                                    x-text="replyText.length + '/1000'"></span>
                                                                <div class="flex items-center gap-2">
                                                                    <button @click="replyingTo = null; replyText = ''"
                                                                        class="text-[11px] text-gray-500 hover:text-gray-700 font-medium">Annuler</button>
                                                                    <button
                                                                        @click="submitReviewReply({{ $review->id }})"
                                                                        :disabled="replyText.trim().length < 10 || replySending"
                                                                        class="text-[11px] px-3 py-1.5 bg-gray-900 text-white rounded-lg font-semibold hover:bg-gray-800 transition-colors disabled:opacity-40 disabled:cursor-not-allowed flex items-center gap-1">
                                                                        <template x-if="replySending">
                                                                            <svg class="w-3 h-3 animate-spin"
                                                                                fill="none" viewBox="0 0 24 24">
                                                                                <circle class="opacity-25" cx="12"
                                                                                    cy="12" r="10"
                                                                                    stroke="currentColor"
                                                                                    stroke-width="4"></circle>
                                                                                <path class="opacity-75"
                                                                                    fill="currentColor"
                                                                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                                                                </path>
                                                                            </svg>
                                                                        </template>
                                                                        Envoyer
                                                                    </button>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="p-10 text-center">
                                    <div
                                        class="w-12 h-12 bg-amber-50 rounded-full flex items-center justify-center mx-auto mb-3">
                                        <svg class="w-6 h-6 text-amber-400" fill="none" stroke="currentColor"
                                            stroke-width="1.5" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M11.48 3.499a.562.562 0 0 1 1.04 0l2.125 5.111a.563.563 0 0 0 .475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 0 0-.182.557l1.285 5.385a.562.562 0 0 1-.84.61l-4.725-2.885a.562.562 0 0 0-.586 0L6.982 20.54a.562.562 0 0 1-.84-.61l1.285-5.386a.562.562 0 0 0-.182-.557l-4.204-3.602a.562.562 0 0 1 .321-.988l5.518-.442a.563.563 0 0 0 .475-.345L11.48 3.5Z" />
                                        </svg>
                                    </div>
                                    <h3 class="text-sm font-bold text-gray-900 mb-1">Pas encore d'avis</h3>
                                    <p class="text-xs text-gray-500">Les avis de vos locataires apparaîtront ici</p>
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- ========== RIGHT: Sidebar ========== --}}
                    <div class="space-y-6">
                        {{-- Revenus & Solde (style Airbnb Earnings) --}}
                        <div
                            class="bg-linear-to-br from-gray-900 to-gray-800 rounded-2xl p-4 sm:p-5 text-white overflow-hidden relative">
                            <div class="absolute top-0 right-0 w-32 h-32 bg-white/5 rounded-full -mr-10 -mt-10"></div>
                            <h3 class="font-bold text-sm mb-4 flex items-center gap-2">
                                <svg class="w-4 h-4 text-green-400" fill="none" stroke="currentColor"
                                    stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm3 0h.008v.008H18V10.5Zm-12 0h.008v.008H6V10.5Z" />
                                </svg>
                                Vos revenus
                            </h3>
                            <div class="space-y-3 relative z-10">
                                <div class="bg-white/10 rounded-xl p-3">
                                    <p class="text-[10px] text-gray-400 uppercase tracking-wider font-medium">Solde
                                        disponible
                                    </p>
                                    <p class="text-xl font-extrabold mt-0.5">
                                        {{ number_format($earningsData['available_balance'], 0, ',', ' ') }} <span
                                            class="text-xs font-medium text-gray-400">FCFA</span></p>
                                </div>
                                <div class="grid grid-cols-2 gap-2">
                                    <div class="bg-white/5 rounded-lg p-2.5">
                                        <p class="text-[10px] text-gray-400">En attente</p>
                                        <p class="text-sm font-bold mt-0.5">
                                            {{ number_format($earningsData['pending_balance'], 0, ',', ' ') }} F</p>
                                    </div>
                                    <div class="bg-white/5 rounded-lg p-2.5">
                                        <p class="text-[10px] text-gray-400">Total gagné</p>
                                        <p class="text-sm font-bold mt-0.5">
                                            {{ number_format($earningsData['total_earned'], 0, ',', ' ') }} F</p>
                                    </div>
                                </div>
                                @if ($earningsData['next_payout'])
                                    <div class="flex items-center gap-2 bg-green-500/10 rounded-lg p-2.5">
                                        <div
                                            class="w-7 h-7 bg-green-500/20 rounded-lg flex items-center justify-center shrink-0">
                                            <svg class="w-3.5 h-3.5 text-green-400" fill="none" stroke="currentColor"
                                                stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M4.5 12h15m0 0l-6.75-6.75M19.5 12l-6.75 6.75" />
                                            </svg>
                                        </div>
                                        <div>
                                            <p class="text-[10px] text-green-300">Prochain versement</p>
                                            <p class="text-xs font-bold text-green-400">
                                                {{ number_format($earningsData['next_payout']->net_amount, 0, ',', ' ') }}
                                                FCFA</p>
                                        </div>
                                    </div>
                                @endif
                            </div>
                            <a href="{{ route('owner.earnings.index') }}"
                                class="mt-4 block text-center text-xs text-gray-400 hover:text-white font-medium transition-colors">Voir
                                les détails →</a>
                        </div>

                        {{-- Messages récents (Airbnb-style preview) --}}
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                            <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                                <h2 class="text-sm font-bold text-gray-900 flex items-center gap-2">
                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor"
                                        stroke-width="1.8" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M8.625 12a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 0 1-2.555-.337A5.972 5.972 0 0 1 5.41 20.97a5.969 5.969 0 0 1-.474-.065 4.48 4.48 0 0 0 .978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25Z" />
                                    </svg>
                                    Messages
                                </h2>
                                @if ($unreadMessages > 0)
                                    <span
                                        class="w-5 h-5 bg-red-500 text-white text-[10px] font-bold rounded-full flex items-center justify-center">{{ $unreadMessages }}</span>
                                @endif
                            </div>
                            @if ($recentMessages->isNotEmpty())
                                <div class="divide-y divide-gray-50">
                                    @foreach ($recentMessages as $conversation)
                                        @php
                                            $lastMsg = $conversation->lastMessage;
                                            $otherUser = $conversation->user;
                                            $isUnread =
                                                $lastMsg && $lastMsg->sender_id !== auth()->id() && !$lastMsg->read_at;
                                        @endphp
                                        <a href="{{ route('chat.show', $conversation) }}"
                                            class="flex items-center gap-3 p-4 hover:bg-gray-50/50 transition-colors {{ $isUnread ? 'bg-blue-50/30' : '' }}">
                                            <div class="relative shrink-0">
                                                <div
                                                    class="w-9 h-9 rounded-full bg-linear-to-br from-gray-400 to-gray-500 flex items-center justify-center text-white font-semibold text-xs">
                                                    {{ strtoupper(substr($otherUser->name ?? 'U', 0, 1)) }}
                                                </div>
                                                @if ($isUnread)
                                                    <span
                                                        class="absolute -top-0.5 -right-0.5 w-2.5 h-2.5 bg-blue-500 rounded-full ring-2 ring-white"></span>
                                                @endif
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <div class="flex items-center justify-between gap-2">
                                                    <p
                                                        class="text-sm {{ $isUnread ? 'font-bold text-gray-900' : 'font-medium text-gray-700' }} truncate">
                                                        {{ $otherUser->name ?? 'Utilisateur' }}</p>
                                                    <span
                                                        class="text-[10px] text-gray-400 shrink-0">{{ $lastMsg?->created_at?->diffForHumans(null, true) }}</span>
                                                </div>
                                                @if ($conversation->residence)
                                                    <p class="text-[10px] text-gray-400 truncate">
                                                        {{ $conversation->residence->name }}</p>
                                                @endif
                                                <p
                                                    class="text-xs {{ $isUnread ? 'text-gray-900 font-medium' : 'text-gray-500' }} truncate mt-0.5">
                                                    {{ Str::limit($lastMsg?->content ?? 'Aucun message', 50) }}</p>
                                            </div>
                                        </a>
                                    @endforeach
                                </div>
                                <div class="px-5 py-3 border-t border-gray-100 bg-gray-50/50">
                                    <a href="{{ route('chat.index') }}"
                                        class="block text-center text-xs text-[#CC5A00] hover:text-[#A34700] font-semibold">Voir
                                        tous les messages →</a>
                                </div>
                            @else
                                <div class="p-8 text-center">
                                    <div
                                        class="w-10 h-10 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-2">
                                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor"
                                            stroke-width="1.5" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M8.625 12a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 0 1-2.555-.337A5.972 5.972 0 0 1 5.41 20.97a5.969 5.969 0 0 1-.474-.065 4.48 4.48 0 0 0 .978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25Z" />
                                        </svg>
                                    </div>
                                    <p class="text-xs text-gray-500">Aucun message</p>
                                </div>
                            @endif
                        </div>

                        {{-- Mini-calendrier (prochains check-in/out) --}}
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                            <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                                <h2 class="text-sm font-bold text-gray-900 flex items-center gap-2">
                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor"
                                        stroke-width="1.8" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                                    </svg>
                                    Prochains événements
                                </h2>
                                <a href="{{ route('owner.bookings.index') }}"
                                    class="text-[10px] text-[#CC5A00] hover:text-[#A34700] font-semibold">Calendrier
                                    →</a>
                            </div>
                            @if ($calendarEvents->isNotEmpty())
                                <div class="divide-y divide-gray-50">
                                    @foreach ($calendarEvents as $event)
                                        @php
                                            $isToday = $event->check_in->isToday() || $event->check_out->isToday();
                                            $eventType = $event->check_out->isToday()
                                                ? 'checkout'
                                                : ($event->check_in->isToday()
                                                    ? 'checkin'
                                                    : ($event->check_in->isFuture()
                                                        ? 'checkin'
                                                        : 'checkout'));
                                        @endphp
                                        <div class="flex items-center gap-3 p-4 {{ $isToday ? 'bg-[#FFF4EB]/30' : '' }}">
                                            <div
                                                class="w-10 h-12 rounded-xl {{ $eventType === 'checkin' ? 'bg-green-50' : 'bg-blue-50' }} flex flex-col items-center justify-center shrink-0">
                                                <span
                                                    class="text-[9px] font-bold uppercase {{ $eventType === 'checkin' ? 'text-green-600' : 'text-blue-600' }}">
                                                    {{ $eventType === 'checkin' ? $event->check_in->translatedFormat('M') : $event->check_out->translatedFormat('M') }}
                                                </span>
                                                <span
                                                    class="text-sm font-extrabold {{ $eventType === 'checkin' ? 'text-green-700' : 'text-blue-700' }}">
                                                    {{ $eventType === 'checkin' ? $event->check_in->format('d') : $event->check_out->format('d') }}
                                                </span>
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <div class="flex items-center gap-1.5">
                                                    <span
                                                        class="text-[10px] px-1.5 py-0.5 rounded font-bold {{ $eventType === 'checkin' ? 'bg-green-100 text-green-700' : 'bg-blue-100 text-blue-700' }}">
                                                        {{ $eventType === 'checkin' ? '↓ Arrivée' : '↑ Départ' }}
                                                    </span>
                                                    @if ($isToday)
                                                        <span
                                                            class="text-[9px] px-1 py-0.5 bg-[#FFE7D1] text-[#A34700] rounded font-bold">Aujourd'hui</span>
                                                    @endif
                                                </div>
                                                <p class="text-xs font-medium text-gray-900 truncate mt-0.5">
                                                    {{ $event->user->name ?? 'Locataire' }}</p>
                                                <p class="text-[10px] text-gray-400 truncate">
                                                    {{ $event->residence->name ?? '' }}</p>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="p-8 text-center">
                                    <div
                                        class="w-10 h-10 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-2">
                                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor"
                                            stroke-width="1.5" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                                        </svg>
                                    </div>
                                    <p class="text-xs text-gray-500">Aucun événement prévu</p>
                                    <p class="text-[10px] text-gray-400 mt-0.5">Les 7 prochains jours</p>
                                </div>
                            @endif
                        </div>

                        {{-- Contacts récents --}}
                        <div id="contacts" class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                            <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                                <h2 class="text-sm font-bold text-gray-900">Contacts récents</h2>
                                @if ($stats['pending_contacts'] > 0)
                                    <span
                                        class="w-5 h-5 bg-red-500 text-white text-[10px] font-bold rounded-full flex items-center justify-center">{{ $stats['pending_contacts'] }}</span>
                                @endif
                            </div>
                            @if ($recentContacts->isNotEmpty())
                                <div class="divide-y divide-gray-50">
                                    @foreach ($recentContacts as $contact)
                                        <div class="p-4 hover:bg-gray-50/50 transition-colors">
                                            <div class="flex items-start gap-3">
                                                <div
                                                    class="w-9 h-9 rounded-full flex items-center justify-center text-white font-semibold text-xs shrink-0 {{ $contact->status === 'pending' ? 'bg-linear-to-br from-[#FF8A1F] to-[#F16A00]' : 'bg-linear-to-br from-gray-400 to-gray-500' }}">
                                                    {{ strtoupper(substr($contact->user->name ?? 'A', 0, 1)) }}
                                                </div>
                                                <div class="flex-1 min-w-0">
                                                    <div class="flex items-center justify-between gap-2">
                                                        <p class="text-sm font-semibold text-gray-900 truncate">
                                                            {{ $contact->user->name ?? 'Anonyme' }}</p>
                                                        @if ($contact->status === 'pending')
                                                            <span
                                                                class="shrink-0 text-[10px] px-1.5 py-0.5 bg-[#FFE7D1] text-[#A34700] rounded font-semibold">Nouveau</span>
                                                        @endif
                                                    </div>
                                                    <p class="text-[11px] text-gray-500 truncate">
                                                        {{ $contact->residence->name ?? 'Résidence' }}</p>
                                                    @if ($contact->message)
                                                        <p class="text-xs text-gray-600 mt-1 line-clamp-1">
                                                            {{ $contact->message }}</p>
                                                    @endif
                                                    <p class="text-[10px] text-gray-400 mt-1">
                                                        {{ $contact->created_at->diffForHumans() }}</p>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                                <div class="px-5 py-3 border-t border-gray-100 bg-gray-50/50">
                                    <a href="{{ route('owner.contacts.index') }}"
                                        class="block text-center text-xs text-[#CC5A00] hover:text-[#A34700] font-semibold">Voir
                                        tous les contacts →</a>
                                </div>
                            @else
                                <div class="p-8 text-center">
                                    <div
                                        class="w-10 h-10 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-2">
                                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor"
                                            stroke-width="1.5" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" />
                                        </svg>
                                    </div>
                                    <p class="text-xs text-gray-500">Aucun contact</p>
                                </div>
                            @endif
                        </div>

                        {{-- Actions rapides --}}
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 sm:p-5">
                            <h3 class="text-sm font-bold text-gray-900 mb-3">Actions rapides</h3>
                            <div class="grid grid-cols-2 gap-2.5">
                                <a href="{{ route('owner.residences.create') }}"
                                    class="flex flex-col items-center p-3 bg-blue-50 rounded-xl hover:bg-blue-100 transition-colors group text-center">
                                    <div
                                        class="w-9 h-9 bg-blue-100 rounded-lg flex items-center justify-center mb-1.5 group-hover:bg-blue-200 transition-colors">
                                        <svg class="w-4.5 h-4.5 text-blue-600" fill="none" stroke="currentColor"
                                            stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                                        </svg>
                                    </div>
                                    <span class="text-[11px] font-semibold text-blue-700">Nouvelle annonce</span>
                                </a>
                                <a href="{{ route('owner.residences.index') }}"
                                    class="flex flex-col items-center p-3 bg-gray-50 rounded-xl hover:bg-gray-100 transition-colors group text-center">
                                    <div
                                        class="w-9 h-9 bg-gray-100 rounded-lg flex items-center justify-center mb-1.5 group-hover:bg-gray-200 transition-colors">
                                        <svg class="w-4.5 h-4.5 text-gray-600" fill="none" stroke="currentColor"
                                            stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M8.25 6.75h12M8.25 12h12m-12 5.25h12M3.75 6.75h.007v.008H3.75V6.75Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0ZM3.75 12h.007v.008H3.75V12Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm-.375 5.25h.007v.008H3.75v-.008Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                                        </svg>
                                    </div>
                                    <span class="text-[11px] font-semibold text-gray-700">Mes annonces</span>
                                </a>
                                <a href="{{ route('owner.analytics.index') }}"
                                    class="flex flex-col items-center p-3 bg-[#FFF4EB] rounded-xl hover:bg-[#FFE7D1] transition-colors group text-center">
                                    <div
                                        class="w-9 h-9 bg-[#FFE7D1] rounded-lg flex items-center justify-center mb-1.5 group-hover:bg-[#FFD0A3] transition-colors">
                                        <svg class="w-4.5 h-4.5 text-[#F16A00]" fill="none" stroke="currentColor"
                                            stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z" />
                                        </svg>
                                    </div>
                                    <span class="text-[11px] font-semibold text-[#CC5A00]">Analytics</span>
                                </a>
                                <a href="{{ route('owner.marketing.promotions.index') }}"
                                    class="flex flex-col items-center p-3 bg-purple-50 rounded-xl hover:bg-purple-100 transition-colors group text-center">
                                    <div
                                        class="w-9 h-9 bg-purple-100 rounded-lg flex items-center justify-center mb-1.5 group-hover:bg-purple-200 transition-colors">
                                        <svg class="w-4.5 h-4.5 text-purple-600" fill="none" stroke="currentColor"
                                            stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09ZM18.259 8.715 18 9.75l-.259-1.035a3.375 3.375 0 0 0-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 0 0 2.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 0 0 2.455 2.456L21.75 6l-1.036.259a3.375 3.375 0 0 0-2.455 2.456ZM16.894 20.567 16.5 21.75l-.394-1.183a2.25 2.25 0 0 0-1.423-1.423L13.5 18.75l1.183-.394a2.25 2.25 0 0 0 1.423-1.423l.394-1.183.394 1.183a2.25 2.25 0 0 0 1.423 1.423l1.183.394-1.183.394a2.25 2.25 0 0 0-1.423 1.423Z" />
                                        </svg>
                                    </div>
                                    <span class="text-[11px] font-semibold text-purple-700">Promotions</span>
                                </a>
                            </div>
                        </div>

                        {{-- Conseils contextuels --}}
                        <div class="bg-linear-to-br from-gray-900 to-gray-800 rounded-2xl p-4 sm:p-5 text-white">
                            <h3 class="font-bold text-sm mb-3 flex items-center gap-2">
                                <svg class="w-4 h-4 text-yellow-400" fill="currentColor" viewBox="0 0 24 24">
                                    <path
                                        d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z" />
                                </svg>
                                Conseils pro
                            </h3>
                            <ul class="space-y-2 text-gray-300 text-xs">
                                @if ($hostScore['score'] < 70)
                                    <li class="flex items-start gap-2">
                                        <span class="text-yellow-400 mt-0.5">→</span>
                                        Votre score hôte est de {{ $hostScore['score'] }}/100. Améliorez votre taux de
                                        réponse
                                        pour atteindre "Hôte Confirmé".
                                    </li>
                                @endif
                                @if ($responseMetrics['response_rate'] < 90 && $responseMetrics['total_contacts'] > 0)
                                    <li class="flex items-start gap-2">
                                        <span class="text-yellow-400 mt-0.5">→</span>
                                        Taux de réponse : {{ $responseMetrics['response_rate'] }}%. Visez 90% pour devenir
                                        Hôte Premium.
                                    </li>
                                @endif
                                @if ($reviewsData['unanswered'] > 0)
                                    <li class="flex items-start gap-2">
                                        <span class="text-yellow-400 mt-0.5">→</span>
                                        {{ $reviewsData['unanswered'] }} avis sans réponse. Répondez pour montrer votre
                                        engagement.
                                    </li>
                                @endif
                                @if ($stats['total_views'] > 0 && $conversionRate < 3)
                                    <li class="flex items-start gap-2">
                                        <span class="text-yellow-400 mt-0.5">→</span>
                                        Conversion à {{ $conversionRate }}%. Améliorez photos et descriptions pour
                                        atteindre
                                        5%.
                                    </li>
                                @endif
                                @if ($residences->filter(fn($r) => $r->photos->count() < 3)->isNotEmpty())
                                    <li class="flex items-start gap-2">
                                        <span class="text-yellow-400 mt-0.5">→</span>
                                        Ajoutez au moins 5 photos par annonce — les annonces avec +5 photos reçoivent 3x
                                        plus de
                                        contacts.
                                    </li>
                                @endif
                                <li class="flex items-start gap-2">
                                    <span class="text-yellow-400 mt-0.5">→</span>
                                    Répondez aux contacts en moins de 2h pour maximiser vos chances.
                                </li>
                                @if ($bookingsData['pending_reviews'] > 0)
                                    <li class="flex items-start gap-2">
                                        <span class="text-yellow-400 mt-0.5">→</span>
                                        {{ $bookingsData['pending_reviews'] }} réservation(s) terminée(s) — encouragez vos
                                        locataires à laisser un avis.
                                    </li>
                                @endif
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

        </div>{{-- end x-show="loaded" --}}
    </div>

    @push('scripts')
    @endpush
@endsection
