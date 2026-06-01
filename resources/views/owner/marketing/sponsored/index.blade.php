@extends('layouts.owner')

@section('title', 'Mise en avant')

@section('owner-content')
    <div x-data="{
        view: localStorage.getItem('sponsored_view') || 'cards',
        setView(v) { this.view = v;
            localStorage.setItem('sponsored_view', v); },
    }" class="space-y-6">

        {{-- ====== Header ====== --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-2.5">
                    <span
                        class="inline-flex items-center justify-center w-9 h-9 rounded-xl bg-linear-to-br from-amber-500 to-[#CC5A00] text-white shadow-sm">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z" />
                        </svg>
                    </span>
                    Mise en avant
                </h1>
                <p class="text-sm text-gray-500 mt-1 ml-12">Boostez la visibilité de vos résidences avec nos packages</p>
            </div>
            <a href="{{ route('owner.marketing.sponsored.create') }}"
                class="inline-flex items-center gap-2 bg-gray-900 text-white px-5 py-2.5 rounded-xl font-semibold text-sm shadow-sm hover:bg-gray-800 hover:shadow-md active:scale-95 transition-all">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z" />
                </svg>
                Nouvelle campagne
            </a>
        </div>

        {{-- ====== KPI Cards ====== --}}
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-blue-50 flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" stroke-width="2"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-xl font-bold text-gray-900">{{ $stats['total'] }}</p>
                        <p class="text-[11px] text-gray-500 font-medium">Campagnes</p>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-green-50 flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" stroke-width="2"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-xl font-bold text-gray-900 flex items-center gap-1.5">
                            {{ $stats['active'] }}
                            @if ($stats['active'] > 0)
                                <span class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></span>
                            @endif
                        </p>
                        <p class="text-[11px] text-gray-500 font-medium">Actives</p>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-[#FFF4EB] flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4 text-[#CC5A00]" fill="none" stroke="currentColor" stroke-width="2"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-xl font-bold text-gray-900">{{ number_format($stats['total_spent'], 0, ',', ' ') }}
                        </p>
                        <p class="text-[11px] text-gray-500 font-medium">FCFA dépensés</p>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-purple-50 flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" stroke-width="2"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-xl font-bold text-gray-900">{{ number_format($stats['total_impressions']) }}</p>
                        <p class="text-[11px] text-gray-500 font-medium">Impressions</p>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-indigo-50 flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" stroke-width="2"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M15.042 21.672L13.684 16.6m0 0l-2.51 2.225.569-9.47 5.227 7.917-3.286-.672zM12 2.25V4.5m5.834.166l-1.591 1.591M20.25 10.5H18M7.757 14.743l-1.59 1.59M6 10.5H3.75m4.007-4.243l-1.59-1.59" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-xl font-bold text-gray-900">{{ number_format($stats['total_clicks']) }}</p>
                        <p class="text-[11px] text-gray-500 font-medium">Clics</p>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-cyan-50 flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4 text-cyan-600" fill="none" stroke="currentColor" stroke-width="2"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-xl font-bold text-cyan-600">{{ $stats['ctr'] }}%</p>
                        <p class="text-[11px] text-gray-500 font-medium">CTR</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- ====== Filters + View toggle ====== --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <form method="GET" class="flex flex-wrap items-center gap-3 flex-1">
                    <div class="min-w-48 flex-1">
                        <select name="residence_id"
                            class="w-full text-sm border-gray-200 rounded-lg focus:ring-[#F16A00] focus:border-[#F16A00] bg-gray-50">
                            <option value="">Toutes les résidences</option>
                            @foreach ($residences as $residence)
                                <option value="{{ $residence->id }}"
                                    {{ request('residence_id') == $residence->id ? 'selected' : '' }}>
                                    {{ $residence->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="min-w-36">
                        <select name="status"
                            class="w-full text-sm border-gray-200 rounded-lg focus:ring-[#F16A00] focus:border-[#F16A00] bg-gray-50">
                            <option value="">Tous les statuts</option>
                            <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Actives</option>
                            <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>En attente
                            </option>
                            <option value="paused" {{ request('status') === 'paused' ? 'selected' : '' }}>En pause
                            </option>
                            <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Terminées
                            </option>
                            <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Annulées
                            </option>
                        </select>
                    </div>
                    <button type="submit"
                        class="px-4 py-2 text-sm font-medium bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors">Filtrer</button>
                    @if (request()->hasAny(['residence_id', 'status']))
                        <a href="{{ route('owner.marketing.sponsored.index') }}"
                            class="text-sm text-gray-400 hover:text-gray-600 transition-colors">✕ Réinitialiser</a>
                    @endif
                </form>
                <div class="flex items-center bg-gray-100 rounded-lg p-0.5">
                    <button @click="setView('cards')" type="button" class="p-2 rounded-md transition-all"
                        :class="view === 'cards' ? 'bg-white shadow-sm text-[#CC5A00]' :
                            'text-gray-400 hover:text-gray-600'">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25a2.25 2.25 0 01-2.25-2.25v-2.25z" />
                        </svg>
                    </button>
                    <button @click="setView('table')" type="button" class="p-2 rounded-md transition-all"
                        :class="view === 'table' ? 'bg-white shadow-sm text-[#CC5A00]' :
                            'text-gray-400 hover:text-gray-600'">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M3.75 12h16.5m-16.5 3.75h16.5M3.75 19.5h16.5M5.625 4.5h12.75a1.875 1.875 0 010 3.75H5.625a1.875 1.875 0 010-3.75z" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        {{-- ====== Success/Error messages ====== --}}
        @if (session('success'))
            <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)" x-transition
                class="flex items-center gap-3 px-4 py-3 bg-green-50 border border-green-200 rounded-xl text-sm text-green-700">
                <svg class="w-5 h-5 shrink-0 text-green-500" fill="none" stroke="currentColor" stroke-width="2"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                {{ session('success') }}
            </div>
        @endif
        @if (session('error'))
            <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)" x-transition
                class="flex items-center gap-3 px-4 py-3 bg-red-50 border border-red-200 rounded-xl text-sm text-red-700">
                <svg class="w-5 h-5 shrink-0 text-red-500" fill="none" stroke="currentColor" stroke-width="2"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                </svg>
                {{ session('error') }}
            </div>
        @endif

        {{-- ====== Empty state ====== --}}
        @if ($sponsoredListings->isEmpty())
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-12 text-center">
                <div class="w-16 h-16 bg-amber-50 rounded-2xl flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-amber-400" fill="none" stroke="currentColor" stroke-width="1.5"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z" />
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-gray-900 mb-1">Aucune campagne</h3>
                <p class="text-sm text-gray-500 mb-6 max-w-sm mx-auto">Boostez vos résidences pour les faire apparaître en
                    tête des recherches et sur la page d'accueil !</p>
                <a href="{{ route('owner.marketing.sponsored.create') }}"
                    class="inline-flex items-center gap-2 bg-gray-900 text-white px-5 py-2.5 rounded-xl font-semibold text-sm shadow-sm hover:bg-gray-800 transition-all">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z" />
                    </svg>
                    Créer une campagne
                </a>
            </div>
        @else
            {{-- ====== CARDS VIEW ====== --}}
            <div x-show="view === 'cards'" x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 translate-y-1" x-transition:enter-end="opacity-100 translate-y-0"
                class="space-y-3">
                @foreach ($sponsoredListings as $sponsored)
                    @php
                        $budgetPercent = $sponsored->total_budget
                            ? min(100, round(($sponsored->amount_spent / $sponsored->total_budget) * 100))
                            : null;
                        $typeColors = [
                            'premium_listing' => 'from-amber-500 to-yellow-500 shadow-amber-500/20',
                            'featured_home' => 'from-purple-500 to-indigo-500 shadow-purple-500/20',
                            'top_search' => 'from-blue-500 to-cyan-500 shadow-blue-500/20',
                            'highlighted' => 'from-[#F16A00] to-red-500 shadow-none',
                        ];
                        $badgeColor = $typeColors[$sponsored->type] ?? $typeColors['highlighted'];
                        $isLive = $sponsored->status === 'active';
                    @endphp
                    <a href="{{ route('owner.marketing.sponsored.show', $sponsored) }}"
                        class="group block bg-white rounded-2xl shadow-sm border border-gray-100 hover:shadow-md hover:border-gray-200 transition-all overflow-hidden">
                        <div class="flex flex-col sm:flex-row sm:items-center gap-4 p-5">

                            {{-- Type badge --}}
                            <div class="shrink-0">
                                <div
                                    class="w-18 h-18 rounded-2xl bg-linear-to-br {{ $badgeColor }} text-white flex flex-col items-center justify-center transition-transform group-hover:scale-105 shadow-lg">
                                    @if ($sponsored->type === 'premium_listing')
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.563.563 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z" />
                                        </svg>
                                    @elseif ($sponsored->type === 'featured_home')
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
                                        </svg>
                                    @elseif ($sponsored->type === 'top_search')
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607z" />
                                        </svg>
                                    @else
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z" />
                                        </svg>
                                    @endif
                                    <span
                                        class="text-[9px] font-semibold mt-0.5 opacity-90">{{ Str::limit($sponsored->type_label, 10) }}</span>
                                </div>
                            </div>

                            {{-- Info --}}
                            <div class="flex-1 min-w-0">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <h3 class="font-bold text-gray-900 truncate text-base">
                                            {{ $sponsored->residence->name ?? 'Résidence' }}</h3>
                                        <p class="text-sm text-gray-500 mt-0.5">{{ $sponsored->type_label }}</p>
                                    </div>
                                    {{-- Status badge --}}
                                    @if ($sponsored->status === 'active')
                                        <span
                                            class="shrink-0 inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-semibold rounded-full bg-green-50 text-green-700 border border-green-200">
                                            <span class="w-1.5 h-1.5 bg-green-500 rounded-full animate-pulse"></span>Active
                                        </span>
                                    @elseif ($sponsored->status === 'pending')
                                        <span
                                            class="shrink-0 inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-semibold rounded-full bg-[#FFF4EB] text-[#A34700] border border-[#FFD0A3]">
                                            <span class="w-1.5 h-1.5 bg-[#F16A00] rounded-full"></span>En attente
                                        </span>
                                    @elseif ($sponsored->status === 'paused')
                                        <span
                                            class="shrink-0 inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-semibold rounded-full bg-yellow-50 text-yellow-700 border border-yellow-200">
                                            <span class="w-1.5 h-1.5 bg-yellow-500 rounded-full"></span>En pause
                                        </span>
                                    @elseif ($sponsored->status === 'completed')
                                        <span
                                            class="shrink-0 inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-semibold rounded-full bg-gray-50 text-gray-500 border border-gray-200">
                                            <span class="w-1.5 h-1.5 bg-gray-400 rounded-full"></span>Terminée
                                        </span>
                                    @else
                                        <span
                                            class="shrink-0 inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-semibold rounded-full bg-red-50 text-red-600 border border-red-200">
                                            <span class="w-1.5 h-1.5 bg-red-400 rounded-full"></span>Annulée
                                        </span>
                                    @endif
                                </div>

                                {{-- Metrics row --}}
                                <div class="flex flex-wrap items-center gap-x-5 gap-y-1.5 mt-3 text-xs text-gray-500">
                                    <span class="inline-flex items-center gap-1">
                                        <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor"
                                            stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
                                        </svg>
                                        {{ $sponsored->starts_at->format('d/m') }} →
                                        {{ $sponsored->ends_at->format('d/m/Y') }}
                                    </span>
                                    <span class="inline-flex items-center gap-1">
                                        <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor"
                                            stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        </svg>
                                        {{ number_format($sponsored->impressions) }} vues
                                    </span>
                                    <span class="inline-flex items-center gap-1">
                                        <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor"
                                            stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M15.042 21.672L13.684 16.6m0 0l-2.51 2.225.569-9.47 5.227 7.917-3.286-.672zM12 2.25V4.5m5.834.166l-1.591 1.591M20.25 10.5H18M7.757 14.743l-1.59 1.59M6 10.5H3.75m4.007-4.243l-1.59-1.59" />
                                        </svg>
                                        {{ number_format($sponsored->clicks) }} clics
                                    </span>
                                    <span
                                        class="inline-flex items-center gap-1 font-medium {{ $sponsored->click_rate > 2 ? 'text-green-600' : 'text-gray-500' }}">
                                        {{ $sponsored->click_rate }}% CTR
                                    </span>
                                    @if ($isLive && $sponsored->days_remaining > 0)
                                        <span class="inline-flex items-center gap-1 text-green-600 font-medium">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor"
                                                stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            {{ $sponsored->days_remaining }}j restants
                                        </span>
                                    @endif
                                </div>

                                {{-- Budget bar --}}
                                @if ($budgetPercent !== null)
                                    <div class="mt-3 max-w-xs">
                                        <div class="flex items-center justify-between text-[10px] text-gray-400 mb-1">
                                            <span>Budget</span>
                                            <span
                                                class="font-semibold">{{ number_format($sponsored->amount_spent, 0, ',', ' ') }}
                                                / {{ number_format($sponsored->total_budget, 0, ',', ' ') }} F</span>
                                        </div>
                                        <div class="w-full h-1.5 bg-gray-100 rounded-full overflow-hidden">
                                            <div class="h-full rounded-full transition-all duration-500 {{ $budgetPercent >= 90 ? 'bg-red-500' : ($budgetPercent >= 60 ? 'bg-[#FF8A1F]' : 'bg-green-500') }}"
                                                style="width: {{ $budgetPercent }}%"></div>
                                        </div>
                                    </div>
                                @endif
                            </div>

                            {{-- Arrow --}}
                            <div class="hidden sm:flex shrink-0">
                                <svg class="w-5 h-5 text-gray-300 group-hover:text-[#F16A00] transition-colors"
                                    fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                                </svg>
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>

            {{-- ====== TABLE VIEW ====== --}}
            <div x-show="view === 'table'" x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 translate-y-1" x-transition:enter-end="opacity-100 translate-y-0">
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-gray-100 bg-gray-50/80">
                                    <th
                                        class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                        Résidence</th>
                                    <th
                                        class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                        Type</th>
                                    <th
                                        class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                        Période</th>
                                    <th
                                        class="text-center px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                        Vues / Clics</th>
                                    <th
                                        class="text-center px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                        Budget</th>
                                    <th
                                        class="text-center px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                        Statut</th>
                                    <th
                                        class="text-right px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                        Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                @foreach ($sponsoredListings as $sponsored)
                                    <tr class="hover:bg-gray-50/50 transition-colors">
                                        <td class="px-5 py-3.5">
                                            <p class="font-semibold text-gray-900 truncate max-w-45">
                                                {{ $sponsored->residence->name ?? '—' }}</p>
                                        </td>
                                        <td class="px-5 py-3.5">
                                            @php
                                                $typeBadgeColors = [
                                                    'premium_listing' => 'bg-amber-50 text-amber-700',
                                                    'featured_home' => 'bg-purple-50 text-purple-700',
                                                    'top_search' => 'bg-blue-50 text-blue-700',
                                                    'highlighted' => 'bg-[#FFF4EB] text-[#A34700]',
                                                ];
                                            @endphp
                                            <span
                                                class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-bold {{ $typeBadgeColors[$sponsored->type] ?? 'bg-gray-100 text-gray-600' }}">
                                                {{ $sponsored->type_label }}
                                            </span>
                                        </td>
                                        <td class="px-5 py-3.5 text-gray-600 text-xs whitespace-nowrap">
                                            {{ $sponsored->starts_at->format('d/m') }} →
                                            {{ $sponsored->ends_at->format('d/m/Y') }}
                                        </td>
                                        <td class="px-5 py-3.5 text-center text-xs">
                                            <span
                                                class="font-semibold text-gray-900">{{ number_format($sponsored->impressions) }}</span>
                                            <span class="text-gray-400 mx-0.5">/</span>
                                            <span
                                                class="font-semibold text-gray-900">{{ number_format($sponsored->clicks) }}</span>
                                            <span class="text-gray-400 ml-1">({{ $sponsored->click_rate }}%)</span>
                                        </td>
                                        <td class="px-5 py-3.5 text-center">
                                            <span
                                                class="text-gray-900 font-semibold text-xs">{{ number_format($sponsored->amount_spent, 0, ',', ' ') }}</span>
                                            @if ($sponsored->total_budget)
                                                <span
                                                    class="text-gray-400 text-xs">/{{ number_format($sponsored->total_budget, 0, ',', ' ') }}</span>
                                            @endif
                                        </td>
                                        <td class="px-5 py-3.5 text-center">
                                            @if ($sponsored->status === 'active')
                                                <span
                                                    class="inline-flex items-center gap-1 px-2 py-0.5 text-[11px] font-semibold rounded-full bg-green-50 text-green-700"><span
                                                        class="w-1.5 h-1.5 bg-green-500 rounded-full animate-pulse"></span>Active</span>
                                            @elseif ($sponsored->status === 'pending')
                                                <span
                                                    class="inline-flex items-center gap-1 px-2 py-0.5 text-[11px] font-semibold rounded-full bg-[#FFF4EB] text-[#A34700]">En
                                                    attente</span>
                                            @elseif ($sponsored->status === 'paused')
                                                <span
                                                    class="inline-flex items-center gap-1 px-2 py-0.5 text-[11px] font-semibold rounded-full bg-yellow-50 text-yellow-700">Pause</span>
                                            @elseif ($sponsored->status === 'completed')
                                                <span
                                                    class="inline-flex items-center gap-1 px-2 py-0.5 text-[11px] font-semibold rounded-full bg-gray-100 text-gray-500">Terminée</span>
                                            @else
                                                <span
                                                    class="inline-flex items-center gap-1 px-2 py-0.5 text-[11px] font-semibold rounded-full bg-red-50 text-red-600">Annulée</span>
                                            @endif
                                        </td>
                                        <td class="px-5 py-3.5">
                                            <div class="flex items-center justify-end gap-1">
                                                <a href="{{ route('owner.marketing.sponsored.show', $sponsored) }}"
                                                    class="p-1.5 text-gray-400 hover:text-[#CC5A00] hover:bg-[#FFF4EB] rounded-lg transition-colors"
                                                    title="Détails">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                        stroke-width="2" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                    </svg>
                                                </a>
                                                @if ($sponsored->status === 'pending' && !$sponsored->is_paid)
                                                    <a href="{{ route('owner.marketing.sponsored.payment', $sponsored) }}"
                                                        class="p-1.5 text-[#F16A00] hover:text-[#A34700] hover:bg-[#FFF4EB] rounded-lg transition-colors"
                                                        title="Payer">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                            stroke-width="2" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z" />
                                                        </svg>
                                                    </a>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            @if ($sponsoredListings->hasPages())
                <div class="mt-6">{{ $sponsoredListings->withQueryString()->links() }}</div>
            @endif
        @endif
    </div>
@endsection
