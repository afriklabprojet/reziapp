@extends('layouts.owner')

@section('title', 'Mes résidences - REZI')

@section('owner-content')
    <div x-data="residencesIndex(@js(['csrfToken' => csrf_token()]))" class="min-h-screen bg-gray-50/50">

        {{-- ============================== HEADER ============================== --}}
        <div class="bg-white border-b border-gray-100">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <h1 class="text-xl sm:text-2xl font-bold text-gray-900">Mes résidences</h1>
                        <p class="text-sm text-gray-500 mt-0.5">Gérez vos {{ $counts['total'] }} annonces</p>
                    </div>
                    @php
                        $isVerified = auth()->user()->identity_verified;
                        $canCreate = $isVerified || $counts['total'] < 1;
                    @endphp

                    @if ($canCreate)
                        <a href="{{ route('owner.residences.create') }}"
                            class="inline-flex items-center px-5 py-2.5 bg-gray-900 text-white font-semibold rounded-xl hover:bg-gray-800 transition-all shadow-sm hover:shadow-md text-sm gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                            </svg>
                            Nouvelle annonce
                        </a>
                    @else
                        <div x-data="{ showTooltip: false }" class="relative">
                            <button @mouseenter="showTooltip = true" @mouseleave="showTooltip = false"
                                class="inline-flex items-center px-5 py-2.5 bg-gray-200 text-gray-400 font-semibold rounded-xl text-sm gap-2 cursor-not-allowed"
                                disabled>
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                                </svg>
                                Nouvelle annonce
                            </button>
                            <div x-show="showTooltip" x-transition
                                class="absolute right-0 top-full mt-2 w-72 bg-white rounded-xl shadow-lg border border-gray-200 p-4 z-50">
                                <div class="flex gap-3">
                                    <div
                                        class="shrink-0 w-8 h-8 bg-amber-50 rounded-lg flex items-center justify-center">
                                        <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor"
                                            stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="text-sm font-semibold text-gray-900">Vérification requise</p>
                                        <p class="text-xs text-gray-500 mt-0.5">Vérifiez votre identité pour publier plus
                                            d'une annonce.</p>
                                        <a href="{{ route('verification.dashboard') }}"
                                            class="inline-flex items-center gap-1 text-xs font-semibold text-orange-600 hover:text-orange-700 mt-2">
                                            Vérifier mon identité
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                                            </svg>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>

                {{-- Restriction notice for non-verified users --}}
                @if (!$isVerified && $counts['total'] >= 1)
                    <div class="mt-4 flex items-center gap-3 px-4 py-3 bg-amber-50 border border-amber-100 rounded-xl">
                        <svg class="w-5 h-5 text-amber-500 shrink-0" fill="none" stroke="currentColor"
                            stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
                        </svg>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm text-amber-800">
                                <span class="font-semibold">Limite atteinte :</span> les comptes non vérifiés sont limités à
                                1 annonce.
                                <a href="{{ route('verification.dashboard') }}"
                                    class="font-semibold text-orange-600 hover:text-orange-700 underline underline-offset-2">Vérifier
                                    mon identité →</a>
                            </p>
                        </div>
                    </div>
                @endif

                {{-- Compteurs pills --}}
                <div class="flex flex-wrap gap-2 mt-5">
                    <a href="{{ route('owner.residences.index') }}"
                        class="px-3.5 py-1.5 rounded-full text-xs font-semibold transition-colors {{ !request('status') && !request('available') ? 'bg-gray-900 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                        Toutes <span class="ml-1 opacity-70">{{ $counts['total'] }}</span>
                    </a>
                    <a href="{{ route('owner.residences.index', ['status' => 'active']) }}"
                        class="px-3.5 py-1.5 rounded-full text-xs font-semibold transition-colors {{ request('status') === 'active' ? 'bg-green-600 text-white' : 'bg-green-50 text-green-700 hover:bg-green-100' }}">
                        <span class="inline-block w-1.5 h-1.5 rounded-full bg-current mr-1 opacity-70"></span>
                        Actives <span class="ml-0.5 opacity-70">{{ $counts['active'] }}</span>
                    </a>
                    <a href="{{ route('owner.residences.index', ['status' => 'pending']) }}"
                        class="px-3.5 py-1.5 rounded-full text-xs font-semibold transition-colors {{ request('status') === 'pending' ? 'bg-yellow-500 text-white' : 'bg-yellow-50 text-yellow-700 hover:bg-yellow-100' }}">
                        <span class="inline-block w-1.5 h-1.5 rounded-full bg-current mr-1 opacity-70"></span>
                        En attente <span class="ml-0.5 opacity-70">{{ $counts['pending'] }}</span>
                    </a>
                    @if ($counts['rejected'] > 0)
                        <a href="{{ route('owner.residences.index', ['status' => 'rejected']) }}"
                            class="px-3.5 py-1.5 rounded-full text-xs font-semibold transition-colors {{ request('status') === 'rejected' ? 'bg-red-600 text-white' : 'bg-red-50 text-red-700 hover:bg-red-100' }}">
                            <span class="inline-block w-1.5 h-1.5 rounded-full bg-current mr-1 opacity-70"></span>
                            Rejetées <span class="ml-0.5 opacity-70">{{ $counts['rejected'] }}</span>
                        </a>
                    @endif
                </div>
            </div>
        </div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8">

            {{-- ============================== FLASH MESSAGES ============================== --}}
            @if (session('success'))
                <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)"
                    x-transition:leave="transition ease-in duration-300"
                    x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 -translate-y-2"
                    class="mb-6 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-xl flex items-center gap-3 text-sm font-medium">
                    <svg class="w-5 h-5 text-green-600 shrink-0" fill="none" stroke="currentColor" stroke-width="2"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                    {{ session('success') }}
                    <button @click="show = false" class="ml-auto text-green-600 hover:text-green-800">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            @endif

            @if (session('error'))
                <div x-data="{ show: true }" x-show="show"
                    class="mb-6 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-xl flex items-center gap-3 text-sm font-medium">
                    <svg class="w-5 h-5 text-red-600 shrink-0" fill="none" stroke="currentColor" stroke-width="2"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
                    </svg>
                    {{ session('error') }}
                    <button @click="show = false" class="ml-auto text-red-600 hover:text-red-800">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            @endif

            {{-- ============================== BARRE FILTRES ============================== --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 mb-6">
                <form method="GET" action="{{ route('owner.residences.index') }}"
                    class="flex flex-col sm:flex-row gap-3">
                    {{-- Recherche --}}
                    <div class="relative flex-1">
                        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none"
                            stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                        </svg>
                        <input type="text" name="search" value="{{ request('search') }}"
                            placeholder="Rechercher par nom, commune..."
                            class="w-full pl-10 pr-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-orange-500/20 focus:border-orange-500 transition-colors">
                    </div>

                    {{-- Tri --}}
                    <select name="sort"
                        class="px-3 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm text-gray-700 focus:ring-2 focus:ring-orange-500/20 focus:border-orange-500"
                        onchange="this.form.submit()">
                        <option value="">Trier par date</option>
                        <option value="price" {{ request('sort') === 'price' ? 'selected' : '' }}>Prix</option>
                        <option value="views" {{ request('sort') === 'views' ? 'selected' : '' }}>Vues</option>
                        <option value="contacts" {{ request('sort') === 'contacts' ? 'selected' : '' }}>Contacts
                        </option>
                        <option value="name" {{ request('sort') === 'name' ? 'selected' : '' }}>Nom</option>
                    </select>

                    {{-- Direction --}}
                    <a href="{{ route('owner.residences.index', array_merge(request()->query(), ['dir' => request('dir') === 'asc' ? 'desc' : 'asc'])) }}"
                        class="inline-flex items-center justify-center w-10 h-10 bg-gray-50 border border-gray-200 rounded-xl hover:bg-gray-100 transition-colors shrink-0"
                        title="{{ request('dir') === 'asc' ? 'Tri descendant' : 'Tri ascendant' }}">
                        <svg class="w-4 h-4 text-gray-600 {{ request('dir') === 'asc' ? 'rotate-180' : '' }}"
                            fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M3 4.5h14.25M3 9h9.75M3 13.5h5.25m5.25-.75L17.25 9m0 0L21 12.75M17.25 9v12" />
                        </svg>
                    </a>

                    {{-- Bouton rechercher --}}
                    <button type="submit"
                        class="inline-flex items-center justify-center px-4 py-2.5 bg-orange-500 text-white rounded-xl text-sm font-semibold hover:bg-orange-600 transition-colors gap-2 shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                        </svg>
                        <span class="hidden sm:inline">Rechercher</span>
                    </button>

                    @if (request()->hasAny(['search', 'sort', 'dir', 'status', 'available']))
                        <a href="{{ route('owner.residences.index') }}"
                            class="inline-flex items-center justify-center px-4 py-2.5 bg-gray-100 text-gray-600 rounded-xl text-sm font-medium hover:bg-gray-200 transition-colors gap-1.5 shrink-0">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                            </svg>
                            Réinitialiser
                        </a>
                    @endif
                </form>
            </div>

            {{-- ============================== GRILLE RÉSIDENCES ============================== --}}
            @if ($residences->isEmpty())
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-12 sm:p-16 text-center">
                    <div
                        class="w-20 h-20 bg-linear-to-br from-orange-100 to-orange-50 rounded-2xl flex items-center justify-center mx-auto mb-5">
                        <svg class="w-10 h-10 text-orange-400" fill="none" stroke="currentColor" stroke-width="1.5"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
                        </svg>
                    </div>
                    @if (request()->hasAny(['search', 'status']))
                        <h3 class="text-lg font-bold text-gray-900 mb-2">Aucun résultat</h3>
                        <p class="text-sm text-gray-500 mb-6 max-w-sm mx-auto">
                            Aucune résidence ne correspond à vos critères de recherche.
                        </p>
                        <a href="{{ route('owner.residences.index') }}"
                            class="inline-flex items-center px-5 py-2.5 bg-gray-100 text-gray-700 rounded-xl font-semibold text-sm hover:bg-gray-200 transition-colors gap-2">
                            Voir toutes les résidences
                        </a>
                    @else
                        <h3 class="text-lg font-bold text-gray-900 mb-2">Commencez à publier</h3>
                        <p class="text-sm text-gray-500 mb-6 max-w-sm mx-auto">
                            Ajoutez votre première résidence meublée et commencez à recevoir des contacts.
                        </p>
                        @if ($canCreate)
                            <a href="{{ route('owner.residences.create') }}"
                                class="inline-flex items-center px-5 py-2.5 bg-gray-900 text-white rounded-xl font-semibold text-sm hover:bg-gray-800 transition-colors gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                                </svg>
                                Créer votre première annonce
                            </a>
                        @else
                            <p class="text-sm text-amber-700 bg-amber-50 px-4 py-3 rounded-xl inline-block">
                                <a href="{{ route('verification.dashboard') }}"
                                    class="font-semibold text-orange-600 hover:text-orange-700 underline underline-offset-2">Vérifiez
                                    votre identité</a>
                                pour publier plus d'annonces.
                            </p>
                        @endif
                    @endif
                </div>
            @else
                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
                    @foreach ($residences as $residence)
                        <div
                            class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-lg transition-all duration-300 group">

                            {{-- Photo --}}
                            <div class="relative h-48 sm:h-52 bg-gray-100 overflow-hidden">
                                @if ($residence->primaryPhoto || $residence->photos->isNotEmpty())
                                    <img loading="lazy"
                                        src="{{ storage_url($residence->primaryPhoto?->path ?? $residence->photos->first()?->path) }}"
                                        alt="{{ $residence->name }}"
                                        class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                                @else
                                    <div class="w-full h-full flex flex-col items-center justify-center text-gray-300">
                                        <svg class="w-12 h-12 mb-1" fill="none" stroke="currentColor"
                                            stroke-width="1" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909M18 3.75H6A2.25 2.25 0 0 0 3.75 6v12A2.25 2.25 0 0 0 6 20.25h12A2.25 2.25 0 0 0 20.25 18V6A2.25 2.25 0 0 0 18 3.75Z" />
                                        </svg>
                                        <span class="text-xs">Aucune photo</span>
                                    </div>
                                @endif

                                {{-- Badges overlay --}}
                                <div class="absolute top-3 left-3 flex flex-col gap-1.5">
                                    @php
                                        $statusBadge = match ($residence->status) {
                                            'active' => ['bg-green-500 text-white', 'Publiée'],
                                            'pending' => ['bg-yellow-400 text-yellow-900', 'En attente'],
                                            'needs_changes' => ['bg-orange-500 text-white', 'À modifier'],
                                            'draft' => ['bg-gray-600 text-white', 'Brouillon'],
                                            'inactive' => ['bg-gray-500 text-white', 'Inactive'],
                                            default => ['bg-red-500 text-white', 'Rejetée'],
                                        };
                                    @endphp
                                    <span
                                        class="px-2.5 py-1 text-[11px] font-bold rounded-lg {{ $statusBadge[0] }} shadow-sm backdrop-blur-sm">
                                        {{ $statusBadge[1] }}
                                    </span>
                                </div>

                                <div class="absolute top-3 right-3">
                                    @if ($residence->is_available)
                                        <span
                                            class="px-2.5 py-1 text-[11px] font-bold rounded-lg bg-white/90 text-green-700 shadow-sm backdrop-blur-sm">
                                            ✓ Disponible
                                        </span>
                                    @else
                                        <span
                                            class="px-2.5 py-1 text-[11px] font-bold rounded-lg bg-white/90 text-gray-600 shadow-sm backdrop-blur-sm">
                                            Occupée
                                        </span>
                                    @endif
                                </div>

                                {{-- Photo count --}}
                                @if ($residence->photos->count() > 0)
                                    <div class="absolute bottom-3 right-3">
                                        <span
                                            class="px-2 py-1 text-[10px] font-bold rounded-md bg-black/50 text-white backdrop-blur-sm flex items-center gap-1">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909M18 3.75H6A2.25 2.25 0 0 0 3.75 6v12A2.25 2.25 0 0 0 6 20.25h12A2.25 2.25 0 0 0 20.25 18V6A2.25 2.25 0 0 0 18 3.75Z" />
                                            </svg>
                                            {{ $residence->photos->count() }}
                                        </span>
                                    </div>
                                @endif
                            </div>

                            {{-- Content --}}
                            <div class="p-4 sm:p-5">
                                <div class="flex items-start justify-between gap-2 mb-1">
                                    <h3 class="text-sm font-bold text-gray-900 truncate flex-1">
                                        {{ $residence->name }}
                                    </h3>
                                    {{-- Dropdown actions --}}
                                    <div x-data="{ open: false }" class="relative shrink-0">
                                        <button @click="open = !open" @click.away="open = false"
                                            class="w-7 h-7 flex items-center justify-center rounded-lg hover:bg-gray-100 transition-colors text-gray-400 hover:text-gray-600">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M12 6.75a.75.75 0 1 1 0-1.5.75.75 0 0 1 0 1.5ZM12 12.75a.75.75 0 1 1 0-1.5.75.75 0 0 1 0 1.5ZM12 18.75a.75.75 0 1 1 0-1.5.75.75 0 0 1 0 1.5Z" />
                                            </svg>
                                        </button>
                                        <div x-show="open" x-transition:enter="transition ease-out duration-100"
                                            x-transition:enter-start="opacity-0 scale-95"
                                            x-transition:enter-end="opacity-100 scale-100"
                                            x-transition:leave="transition ease-in duration-75"
                                            x-transition:leave-start="opacity-100 scale-100"
                                            x-transition:leave-end="opacity-0 scale-95"
                                            class="absolute right-0 top-8 w-44 bg-white rounded-xl shadow-lg border border-gray-100 py-1.5 z-20">
                                            <a href="{{ route('owner.residences.show', $residence) }}"
                                                class="flex items-center gap-2.5 px-3.5 py-2 text-sm text-gray-700 hover:bg-gray-50 transition-colors">
                                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor"
                                                    stroke-width="1.5" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                                </svg>
                                                Détails
                                            </a>
                                            <a href="{{ route('owner.residences.edit', $residence) }}"
                                                class="flex items-center gap-2.5 px-3.5 py-2 text-sm text-gray-700 hover:bg-gray-50 transition-colors">
                                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor"
                                                    stroke-width="1.5" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                                                </svg>
                                                Modifier
                                            </a>
                                            @if ($residence->status === 'active')
                                                <a href="{{ route('residences.show', $residence) }}" target="_blank"
                                                    class="flex items-center gap-2.5 px-3.5 py-2 text-sm text-gray-700 hover:bg-gray-50 transition-colors">
                                                    <svg class="w-4 h-4 text-gray-400" fill="none"
                                                        stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
                                                    </svg>
                                                    Voir sur REZI
                                                </a>
                                            @endif
                                            <div class="my-1 border-t border-gray-100"></div>
                                            <button
                                                @click="toggleAvailability({{ $residence->id }}, {{ $residence->is_available ? 'true' : 'false' }}); open = false"
                                                class="w-full flex items-center gap-2.5 px-3.5 py-2 text-sm hover:bg-gray-50 transition-colors {{ $residence->is_available ? 'text-orange-600' : 'text-green-600' }}">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                    stroke-width="1.5" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182" />
                                                </svg>
                                                {{ $residence->is_available ? 'Marquer occupée' : 'Marquer disponible' }}
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <p class="text-xs text-gray-500 mb-3">
                                    <svg class="w-3.5 h-3.5 inline -mt-0.5 mr-0.5 text-gray-400" fill="none"
                                        stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" />
                                    </svg>
                                    {{ $residence->commune }}{{ $residence->quartier ? ', ' . $residence->quartier : '' }}
                                </p>

                                {{-- Prix --}}
                                <p class="text-lg font-extrabold text-gray-900 mb-2">
                                    {{ number_format($residence->price, 0, ',', ' ') }}
                                    <span class="text-xs font-medium text-gray-400">FCFA/jour</span>
                                </p>

                                {{-- Score qualité --}}
                                @if ($residence->listing_score)
                                    @php
                                        $sc = $residence->listing_score;
                                        $scColor = $sc >= 80 ? 'emerald' : ($sc >= 60 ? 'blue' : ($sc >= 40 ? 'amber' : 'red'));
                                        $scLabel = $sc >= 80 ? 'Excellent' : ($sc >= 60 ? 'Très bien' : ($sc >= 40 ? 'Bien' : 'À améliorer'));
                                    @endphp
                                    <a href="{{ route('owner.listing-score.show', $residence) }}"
                                        class="inline-flex items-center gap-1 mb-3 px-2 py-0.5 rounded-full text-[11px] font-semibold bg-{{ $scColor }}-100 text-{{ $scColor }}-700 hover:bg-{{ $scColor }}-200 transition-colors">
                                        ⭐ {{ $sc }}/100 — {{ $scLabel }}
                                    </a>
                                @else
                                    <a href="{{ route('owner.listing-score.show', $residence) }}"
                                        class="inline-flex items-center gap-1 mb-3 text-[11px] text-gray-400 hover:text-orange-500 transition-colors">
                                        ⭐ Score non calculé
                                    </a>
                                @endif

                                {{-- Stats row --}}
                                <div class="flex items-center gap-4 pt-3 border-t border-gray-100 text-xs text-gray-500">
                                    <span class="flex items-center gap-1">
                                        <svg class="w-3.5 h-3.5 text-purple-500" fill="none" stroke="currentColor"
                                            stroke-width="1.5" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                        </svg>
                                        <span
                                            class="font-semibold text-gray-700">{{ $residence->views_count ?? 0 }}</span>
                                        vues
                                    </span>
                                    <span class="flex items-center gap-1">
                                        <svg class="w-3.5 h-3.5 text-green-500" fill="none" stroke="currentColor"
                                            stroke-width="1.5" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M8.625 12a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 0 1-2.555-.337A5.972 5.972 0 0 1 5.41 20.97a5.969 5.969 0 0 1-.474-.065 4.48 4.48 0 0 0 .978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25Z" />
                                        </svg>
                                        <span
                                            class="font-semibold text-gray-700">{{ $residence->contacts_count ?? 0 }}</span>
                                        contacts
                                    </span>
                                    <span class="flex items-center gap-1">
                                        <svg class="w-3.5 h-3.5 text-blue-500" fill="none" stroke="currentColor"
                                            stroke-width="1.5" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909M18 3.75H6A2.25 2.25 0 0 0 3.75 6v12A2.25 2.25 0 0 0 6 20.25h12A2.25 2.25 0 0 0 20.25 18V6A2.25 2.25 0 0 0 18 3.75Z" />
                                        </svg>
                                        <span class="font-semibold text-gray-700">{{ $residence->photos->count() }}</span>
                                        photos
                                    </span>
                                    @php
                                        $convRate =
                                            ($residence->views_count ?? 0) > 0
                                                ? round(
                                                    (($residence->contacts_count ?? 0) / $residence->views_count) * 100,
                                                    1,
                                                )
                                                : 0;
                                    @endphp
                                    <span @class([
                                        'ml-auto font-bold px-1.5 py-0.5 rounded text-[10px]',
                                        'bg-green-100 text-green-700' => $convRate >= 5,
                                        'bg-yellow-100 text-yellow-700' => $convRate >= 2 && $convRate < 5,
                                        'bg-gray-100 text-gray-500' => $convRate < 2,
                                    ])>
                                        {{ $convRate }}% conv.
                                    </span>
                                </div>

                                {{-- Quick actions --}}
                                <div class="flex items-center gap-2 mt-3">
                                    <a href="{{ route('owner.residences.edit', $residence) }}"
                                        class="flex-1 text-center px-3 py-2 bg-gray-100 text-gray-700 rounded-xl text-xs font-semibold hover:bg-gray-200 transition-colors">
                                        Modifier
                                    </a>
                                    @if ($residence->status === 'active')
                                        <a href="{{ route('residences.show', $residence) }}" target="_blank"
                                            class="flex-1 text-center px-3 py-2 bg-orange-50 text-orange-700 rounded-xl text-xs font-semibold hover:bg-orange-100 transition-colors">
                                            Voir l'annonce
                                        </a>
                                    @else
                                        <a href="{{ route('owner.residences.show', $residence) }}"
                                            class="flex-1 text-center px-3 py-2 bg-blue-50 text-blue-700 rounded-xl text-xs font-semibold hover:bg-blue-100 transition-colors">
                                            Détails
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Pagination --}}
                @if ($residences->hasPages())
                    <div class="mt-8">
                        {{ $residences->links() }}
                    </div>
                @endif
            @endif
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('alpine:init', () => {
                Alpine.data('residencesIndex', (config) => ({
                    csrfToken: config.csrfToken,

                    async toggleAvailability(residenceId, currentStatus) {
                        const action = currentStatus ? 'marquer comme occupée' :
                            'marquer comme disponible';
                        if (!confirm(`Voulez-vous ${action} cette résidence ?`)) return;

                        try {
                            const response = await fetch(
                                `/owner/residences/${residenceId}/toggle-availability`, {
                                    method: 'PATCH',
                                    headers: {
                                        'X-CSRF-TOKEN': this.csrfToken,
                                        'Accept': 'application/json',
                                        'Content-Type': 'application/json',
                                    }
                                });

                            if (response.ok) {
                                window.location.reload();
                            } else {
                                alert('Une erreur est survenue');
                            }
                        } catch (error) {
                            console.error('Error:', error);
                            alert('Une erreur est survenue');
                        }
                    }
                }));
            });
        </script>
    @endpush
@endsection
