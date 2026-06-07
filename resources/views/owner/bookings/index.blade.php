@extends('layouts.owner')

@section('title', 'Réservations - Propriétaire | Rezi App')

@section('owner-content')
    <div class="min-h-screen bg-gray-50/50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8">

            {{-- ============================== EN-TÊTE ============================== --}}
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
                <div>
                    <h1 class="text-2xl font-extrabold text-gray-900 tracking-tight">Réservations</h1>
                    <p class="mt-1 text-sm text-gray-500">
                        {{ $stats['total_bookings'] ?? 0 }} réservation(s) au total
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <a href="{{ route('owner.bookings.requests') }}"
                        class="relative inline-flex items-center gap-2 px-4 py-2.5 bg-[#F16A00] text-white text-sm font-semibold rounded-xl hover:bg-[#CC5A00] transition-colors shadow-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" />
                        </svg>
                        Demandes
                        @if (($stats['pending_requests'] ?? 0) > 0)
                            <span
                                class="absolute -top-1.5 -right-1.5 w-5 h-5 bg-red-500 text-white text-[10px] font-bold rounded-full flex items-center justify-center">
                                {{ $stats['pending_requests'] }}
                            </span>
                        @endif
                    </a>
                </div>
            </div>

            {{-- ============================== KPI CARDS ============================== --}}
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-3 mb-6">
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-amber-50 rounded-xl flex items-center justify-center">
                            <svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" stroke-width="1.5"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-[11px] text-gray-400 font-medium">En attente</p>
                            <p class="text-lg font-bold text-gray-900">{{ $stats['pending_bookings'] ?? 0 }}</p>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-green-50 rounded-xl flex items-center justify-center">
                            <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" stroke-width="1.5"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-[11px] text-gray-400 font-medium">Confirmées</p>
                            <p class="text-lg font-bold text-gray-900">{{ $stats['confirmed_bookings'] ?? 0 }}</p>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-blue-50 rounded-xl flex items-center justify-center">
                            <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" stroke-width="1.5"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M21.752 15.002A9.72 9.72 0 0 1 18 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 0 0 3 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 0 0 9.002-5.998Z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-[11px] text-gray-400 font-medium">Nuits totales</p>
                            <p class="text-lg font-bold text-gray-900">{{ $stats['total_nights'] ?? 0 }}</p>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-[#FFF4EB] rounded-xl flex items-center justify-center">
                            <svg class="w-5 h-5 text-[#F16A00]" fill="none" stroke="currentColor" stroke-width="1.5"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M12 6v12m-3-2.818.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-[11px] text-gray-400 font-medium">Revenus mois</p>
                            <p class="text-lg font-bold text-gray-900">
                                {{ number_format($stats['monthly_revenue'] ?? 0, 0, ',', ' ') }}
                                <span class="text-[10px] text-gray-400 font-medium">F</span>
                            </p>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4 col-span-2 lg:col-span-1">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-purple-50 rounded-xl flex items-center justify-center">
                            <svg class="w-5 h-5 text-purple-500" fill="none" stroke="currentColor" stroke-width="1.5"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-[11px] text-gray-400 font-medium">Panier moyen</p>
                            <p class="text-lg font-bold text-gray-900">
                                {{ number_format($stats['avg_booking_value'] ?? 0, 0, ',', ' ') }}
                                <span class="text-[10px] text-gray-400 font-medium">F</span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ============================== RECHERCHE + FILTRES ============================== --}}
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4 mb-6">
                <div class="flex flex-col sm:flex-row sm:items-center gap-3">
                    {{-- Barre de recherche --}}
                    <form method="GET" action="{{ route('owner.bookings.index') }}" class="flex-1 flex gap-2">
                        <input type="hidden" name="status" value="{{ $status }}">
                        <input type="hidden" name="sort" value="{{ $sort }}">
                        <input type="hidden" name="dir" value="{{ $dir }}">
                        <div class="relative flex-1">
                            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none"
                                stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                            </svg>
                            <input type="text" name="search" value="{{ $search }}"
                                placeholder="Référence, nom du client, résidence…"
                                class="w-full pl-10 pr-4 py-2.5 text-sm rounded-xl border border-gray-200 focus:border-[#FFD0A3] focus:ring focus:ring-orange-100 bg-gray-50/50">
                        </div>
                        <button type="submit"
                            class="px-4 py-2.5 bg-gray-900 text-white text-sm font-semibold rounded-xl hover:bg-gray-800 transition-colors">
                            Chercher
                        </button>
                    </form>

                    {{-- Tri --}}
                    <div class="flex items-center gap-2">
                        <select
                            onchange="window.location.href='{{ route('owner.bookings.index') }}?status={{ $status }}&search={{ $search }}&sort='+this.value+'&dir={{ $dir }}'"
                            class="text-sm rounded-xl border border-gray-200 px-3 py-2.5 bg-gray-50/50 focus:border-[#FFD0A3] focus:ring focus:ring-orange-100">
                            <option value="created_at" {{ $sort === 'created_at' ? 'selected' : '' }}>Date</option>
                            <option value="check_in" {{ $sort === 'check_in' ? 'selected' : '' }}>Arrivée</option>
                            <option value="total_amount" {{ $sort === 'total_amount' ? 'selected' : '' }}>Montant
                            </option>
                            <option value="nights" {{ $sort === 'nights' ? 'selected' : '' }}>Durée</option>
                        </select>
                        <a href="{{ route('owner.bookings.index', ['status' => $status, 'search' => $search, 'sort' => $sort, 'dir' => $dir === 'asc' ? 'desc' : 'asc']) }}"
                            class="p-2.5 rounded-xl border border-gray-200 hover:bg-gray-50 transition-colors"
                            title="{{ $dir === 'asc' ? 'Décroissant' : 'Croissant' }}">
                            @if ($dir === 'asc')
                                <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" stroke-width="2"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M3 4.5h14.25M3 9h9.75M3 13.5h5.25m5.914-.493 2.25-2.25a.75.75 0 0 0 0-1.06l-2.25-2.25m0 9.06 2.25-2.25a.75.75 0 0 0 0-1.06l-2.25-2.25" />
                                </svg>
                            @else
                                <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" stroke-width="2"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M3 4.5h14.25M3 9h9.75M3 13.5h9.75m4.5-4.5v12m0 0-3.75-3.75M17.25 21l3.75-3.75" />
                                </svg>
                            @endif
                        </a>
                    </div>
                </div>

                {{-- Filtres statut --}}
                <div class="flex flex-wrap gap-2 mt-3 pt-3 border-t border-gray-100">
                    @php
                        $filters = [
                            'all' => ['label' => 'Toutes', 'count' => $stats['total_bookings'] ?? 0],
                            'pending' => ['label' => 'En attente', 'count' => $stats['pending_bookings'] ?? 0],
                            'confirmed' => ['label' => 'Confirmées', 'count' => $stats['confirmed_bookings'] ?? 0],
                            'completed' => ['label' => 'Terminées', 'count' => $stats['completed_count'] ?? 0],
                            'cancelled' => ['label' => 'Annulées', 'count' => $stats['cancelled_count'] ?? 0],
                        ];
                    @endphp
                    @foreach ($filters as $key => $filter)
                        <a href="{{ route('owner.bookings.index', ['status' => $key, 'search' => $search, 'sort' => $sort, 'dir' => $dir]) }}"
                            class="px-3 py-1.5 rounded-lg text-xs font-bold transition-colors {{ $status === $key ? 'bg-[#F16A00] text-white shadow-sm' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                            {{ $filter['label'] }}
                            <span
                                class="ml-1 {{ $status === $key ? 'text-[#FFD0A3]' : 'text-gray-400' }}">{{ $filter['count'] }}</span>
                        </a>
                    @endforeach
                </div>
            </div>

            {{-- ============================== FLASH MESSAGE ============================== --}}
            @if (session('success'))
                <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)"
                    x-transition:leave="transition ease-in duration-300"
                    x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 -translate-y-2"
                    class="mb-6 p-4 bg-green-50 border border-green-100 text-green-700 rounded-2xl text-sm font-medium flex items-center gap-2">
                    <svg class="w-5 h-5 text-green-500 shrink-0" fill="none" stroke="currentColor" stroke-width="2"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                    {{ session('success') }}
                </div>
            @endif

            {{-- ============================== LISTE DES RÉSERVATIONS ============================== --}}
            @if ($bookings->isEmpty())
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-12 text-center">
                    <div class="w-16 h-16 bg-gray-50 rounded-2xl flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor" stroke-width="1.5"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                        </svg>
                    </div>
                    @if ($search)
                        <h3 class="text-base font-bold text-gray-900 mb-1">Aucun résultat</h3>
                        <p class="text-sm text-gray-500">Aucune réservation ne correspond à « {{ $search }} »</p>
                    @else
                        <h3 class="text-base font-bold text-gray-900 mb-1">Aucune réservation</h3>
                        <p class="text-sm text-gray-500">Vous n'avez pas encore reçu de réservation.</p>
                    @endif
                </div>
            @else
                <div class="space-y-3">
                    @foreach ($bookings as $booking)
                        @php
                            $statusConfig = match ($booking->status) {
                                'pending' => ['bg-amber-100 text-amber-700', 'En attente', 'bg-amber-500'],
                                'confirmed' => ['bg-green-100 text-green-700', 'Confirmée', 'bg-green-500'],
                                'completed' => ['bg-blue-100 text-blue-700', 'Terminée', 'bg-blue-500'],
                                'cancelled_by_user' => ['bg-red-100 text-red-700', 'Annulée client', 'bg-red-500'],
                                'cancelled_by_owner' => ['bg-red-100 text-red-700', 'Annulée proprio', 'bg-red-500'],
                                default => ['bg-gray-100 text-gray-600', ucfirst($booking->status), 'bg-gray-500'],
                            };
                            $avatarColors = [
                                'from-[#FF8A1F] to-[#F16A00]',
                                'from-blue-400 to-blue-500',
                                'from-purple-400 to-purple-500',
                                'from-green-400 to-green-500',
                                'from-pink-400 to-pink-500',
                                'from-teal-400 to-teal-500',
                            ];
                            $avatarColor = $avatarColors[($booking->user_id ?? 0) % count($avatarColors)];
                        @endphp
                        <div
                            class="bg-white rounded-2xl border border-gray-100 shadow-sm hover:shadow-md transition-all duration-200 overflow-hidden">
                            <div class="p-5">
                                <div class="flex items-start gap-4">
                                    {{-- Photo résidence --}}
                                    <div class="hidden sm:block shrink-0">
                                        @if ($booking->residence && $booking->residence->photos && $booking->residence->photos->isNotEmpty())
                                            <div class="w-20 h-16 rounded-xl overflow-hidden bg-gray-100">
                                                <img loading="lazy"
                                                    src="{{ storage_url($booking->residence->photos->first()->path) }}"
                                                    alt="{{ $booking->residence->title ?? 'Résidence' }}" class="w-full h-full object-cover">
                                            </div>
                                        @else
                                            <div class="w-20 h-16 rounded-xl bg-gray-100 flex items-center justify-center">
                                                <svg class="w-6 h-6 text-gray-300" fill="none" stroke="currentColor"
                                                    stroke-width="1.5" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
                                                </svg>
                                            </div>
                                        @endif
                                    </div>

                                    {{-- Contenu principal --}}
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-start justify-between gap-3">
                                            <div class="min-w-0">
                                                <div class="flex items-center gap-2 mb-1">
                                                    <h3 class="text-sm font-bold text-gray-900 truncate">
                                                        {{ $booking->residence->name ?? 'Résidence' }}
                                                    </h3>
                                                    <span
                                                        class="px-2 py-0.5 rounded-md text-[10px] font-bold {{ $statusConfig[0] }} shrink-0">
                                                        {{ $statusConfig[1] }}
                                                    </span>
                                                </div>
                                                <div class="flex items-center gap-2 text-xs text-gray-500">
                                                    {{-- Avatar client --}}
                                                    <div
                                                        class="w-5 h-5 rounded-full bg-linear-to-br {{ $avatarColor }} flex items-center justify-center text-white text-[9px] font-bold shrink-0">
                                                        {{ strtoupper(substr($booking->user->first_name ?? ($booking->user->name ?? 'U'), 0, 1)) }}
                                                    </div>
                                                    <span
                                                        class="font-medium text-gray-700">{{ $booking->user->full_name ?? ($booking->user->name ?? 'Client') }}</span>
                                                    <span class="text-gray-300">·</span>
                                                    <span>Réf: {{ $booking->reference }}</span>
                                                </div>
                                            </div>

                                            {{-- Montant --}}
                                            <div class="text-right shrink-0">
                                                <p class="text-base font-bold text-gray-900">
                                                    {{ number_format($booking->total_amount, 0, ',', ' ') }}
                                                    <span class="text-[10px] text-gray-400 font-medium">FCFA</span>
                                                </p>
                                                @if ($booking->payment_status === 'paid')
                                                    <span
                                                        class="text-[10px] text-green-600 font-bold flex items-center justify-end gap-0.5">
                                                        <svg class="w-3 h-3" fill="none" stroke="currentColor"
                                                            stroke-width="2.5" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                d="m4.5 12.75 6 6 9-13.5" />
                                                        </svg>
                                                        Payé
                                                    </span>
                                                @elseif($booking->payment_status === 'pending')
                                                    <span class="text-[10px] text-amber-600 font-bold">Paiement en
                                                        attente</span>
                                                @endif
                                            </div>
                                        </div>

                                        {{-- Détails dates --}}
                                        <div
                                            class="mt-3 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-gray-500">
                                            <span class="inline-flex items-center gap-1">
                                                <svg class="w-3.5 h-3.5 text-gray-400" fill="none"
                                                    stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                                                </svg>
                                                {{ \Carbon\Carbon::parse($booking->check_in)->format('d M') }} →
                                                {{ \Carbon\Carbon::parse($booking->check_out)->format('d M Y') }}
                                            </span>
                                            <span class="inline-flex items-center gap-1">
                                                <svg class="w-3.5 h-3.5 text-gray-400" fill="none"
                                                    stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M21.752 15.002A9.72 9.72 0 0 1 18 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 0 0 3 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 0 0 9.002-5.998Z" />
                                                </svg>
                                                {{ $booking->nights }} nuit(s)
                                            </span>
                                            <span class="inline-flex items-center gap-1">
                                                <svg class="w-3.5 h-3.5 text-gray-400" fill="none"
                                                    stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                                                </svg>
                                                {{ $booking->guests }} pers.
                                            </span>
                                            <span class="text-gray-300 hidden sm:inline">·</span>
                                            <span class="text-gray-400">{{ $booking->created_at->diffForHumans() }}</span>
                                        </div>
                                    </div>
                                </div>

                                {{-- Actions row --}}
                                <div class="mt-4 pt-3 border-t border-gray-100 flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        {{-- Confirmer (si pending) --}}
                                        @if ($booking->status === 'pending')
                                            <form action="{{ route('owner.bookings.confirm', $booking) }}" method="POST"
                                                class="inline">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit"
                                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-green-500 text-white text-xs font-bold rounded-lg hover:bg-green-600 transition-colors">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor"
                                                        stroke-width="2" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            d="m4.5 12.75 6 6 9-13.5" />
                                                    </svg>
                                                    Confirmer
                                                </button>
                                            </form>
                                        @endif

                                        {{-- Annuler (si pending ou confirmed) --}}
                                        @if (in_array($booking->status, ['pending', 'confirmed']))
                                            <form action="{{ route('owner.bookings.cancel', $booking) }}" method="POST"
                                                class="inline"
                                                onsubmit="return confirm('Êtes-vous sûr de vouloir annuler cette réservation ?')">
                                                @csrf
                                                @method('PUT')
                                                <button type="submit"
                                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white text-red-600 text-xs font-bold rounded-lg border border-red-200 hover:bg-red-50 transition-colors">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor"
                                                        stroke-width="2" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            d="M6 18 18 6M6 6l12 12" />
                                                    </svg>
                                                    Annuler
                                                </button>
                                            </form>
                                        @endif

                                        {{-- Chat --}}
                                        <form action="{{ route('chat.start') }}" method="POST" class="inline">
                                            @csrf
                                            <input type="hidden" name="residence_id"
                                                value="{{ $booking->residence_id }}">
                                            <input type="hidden" name="user_id" value="{{ $booking->user_id }}">
                                            <button type="submit"
                                                class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white text-gray-600 text-xs font-bold rounded-lg border border-gray-200 hover:bg-gray-50 transition-colors">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor"
                                                    stroke-width="1.5" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M8.625 12a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 0 1-2.555-.337A5.972 5.972 0 0 1 5.41 20.97a5.969 5.969 0 0 1-.474-.065 4.48 4.48 0 0 0 .978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25Z" />
                                                </svg>
                                                Chat
                                            </button>
                                        </form>
                                    </div>

                                    <a href="{{ route('owner.bookings.show', $booking) }}"
                                        class="inline-flex items-center gap-1.5 text-xs font-bold text-[#F16A00] hover:text-[#CC5A00] transition-colors">
                                        Voir détails
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                                        </svg>
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Pagination --}}
                <div class="mt-6">
                    {{ $bookings->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection
