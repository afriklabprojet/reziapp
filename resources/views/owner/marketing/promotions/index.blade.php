@extends('layouts.owner')

@section('title', 'Promotions Flash')

@section('owner-content')
    <div x-data="{
        view: localStorage.getItem('promo_view') || 'cards',
        setView(v) { this.view = v; localStorage.setItem('promo_view', v); },
    }" class="space-y-6">

        {{-- ====== Header ====== --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-2.5">
                    <span
                        class="inline-flex items-center justify-center w-9 h-9 rounded-xl bg-linear-to-br from-[#F16A00] to-red-500 text-white shadow-sm">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M9.568 3H5.25A2.25 2.25 0 0 0 3 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 0 0 5.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 0 0 9.568 3Z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6Z" />
                        </svg>
                    </span>
                    Promotions Flash
                </h1>
                <p class="text-sm text-gray-500 mt-1 ml-12">Créez des offres limitées pour booster vos réservations</p>
            </div>
            <a href="{{ route('owner.marketing.promotions.create') }}"
                class="inline-flex items-center gap-2 bg-gray-900 text-white px-5 py-2.5 rounded-xl font-semibold text-sm shadow-sm hover:bg-gray-800 hover:shadow-md active:scale-95 transition-all">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                Nouvelle promotion
            </a>
        </div>

        {{-- ====== KPI Cards ====== --}}
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
            {{-- Total --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-blue-50 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 0 0 3 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 0 0 5.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 0 0 9.568 3Z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6Z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-gray-900">{{ $stats['total'] }}</p>
                        <p class="text-[11px] text-gray-500 font-medium">Total</p>
                    </div>
                </div>
            </div>

            {{-- Actives --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-green-50 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-gray-900 flex items-center gap-1.5">
                            {{ $stats['active'] }}
                            @if ($stats['active'] > 0)
                                <span class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></span>
                            @endif
                        </p>
                        <p class="text-[11px] text-gray-500 font-medium">Actives</p>
                    </div>
                </div>
            </div>

            {{-- Expire bientôt (< 7j) --}}
            <div class="bg-white rounded-2xl shadow-sm border border-{{ $stats['expiring_soon'] > 0 ? 'amber' : 'gray' }}-100 p-4 {{ $stats['expiring_soon'] > 0 ? 'bg-amber-50/50' : '' }}">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl {{ $stats['expiring_soon'] > 0 ? 'bg-amber-100' : 'bg-gray-50' }} flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 {{ $stats['expiring_soon'] > 0 ? 'text-amber-600' : 'text-gray-400' }}" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-2xl font-bold {{ $stats['expiring_soon'] > 0 ? 'text-amber-700' : 'text-gray-900' }}">{{ $stats['expiring_soon'] }}</p>
                        <p class="text-[11px] {{ $stats['expiring_soon'] > 0 ? 'text-amber-600' : 'text-gray-500' }} font-medium">Expire < 7j</p>
                    </div>
                </div>
            </div>

            {{-- Utilisations --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-purple-50 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-gray-900">{{ $stats['total_uses'] }}</p>
                        <p class="text-[11px] text-gray-500 font-medium">Utilisations</p>
                    </div>
                </div>
            </div>

            {{-- Expirées --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-gray-100 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-gray-900">{{ $stats['expired'] }}</p>
                        <p class="text-[11px] text-gray-500 font-medium">Expirées</p>
                    </div>
                </div>
            </div>

            {{-- Désactivées --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-red-50 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 text-red-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 0 0 5.636 5.636m12.728 12.728A9 9 0 0 1 5.636 5.636m12.728 12.728L5.636 5.636" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-gray-900">{{ $stats['inactive'] }}</p>
                        <p class="text-[11px] text-gray-500 font-medium">Désactivées</p>
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
                            <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Actives
                            </option>
                            <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactives
                            </option>
                            <option value="expired" {{ request('status') === 'expired' ? 'selected' : '' }}>Expirées
                            </option>
                        </select>
                    </div>
                    <button type="submit"
                        class="px-4 py-2 text-sm font-medium bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors">
                        Filtrer
                    </button>
                    @if (request()->hasAny(['residence_id', 'status']))
                        <a href="{{ route('owner.marketing.promotions.index') }}"
                            class="text-sm text-gray-400 hover:text-gray-600 transition-colors">✕ Réinitialiser</a>
                    @endif
                </form>

                {{-- View toggle --}}
                <div class="flex items-center bg-gray-100 rounded-lg p-0.5">
                    <button @click="setView('cards')" type="button"
                        class="p-2 rounded-md transition-all"
                        :class="view === 'cards' ? 'bg-white shadow-sm text-[#CC5A00]' : 'text-gray-400 hover:text-gray-600'">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25a2.25 2.25 0 0 1-2.25-2.25v-2.25Z" />
                        </svg>
                    </button>
                    <button @click="setView('table')" type="button"
                        class="p-2 rounded-md transition-all"
                        :class="view === 'table' ? 'bg-white shadow-sm text-[#CC5A00]' : 'text-gray-400 hover:text-gray-600'">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M3.75 12h16.5m-16.5 3.75h16.5M3.75 19.5h16.5M5.625 4.5h12.75a1.875 1.875 0 0 1 0 3.75H5.625a1.875 1.875 0 0 1 0-3.75Z" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        {{-- ====== Success message ====== --}}
        @if (session('success'))
            <div x-data="autoHide(4000)" x-show="show" x-transition
                class="flex items-center gap-3 px-4 py-3 bg-green-50 border border-green-200 rounded-xl text-sm text-green-700">
                <svg class="w-5 h-5 shrink-0 text-green-500" fill="none" stroke="currentColor" stroke-width="2"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                </svg>
                {{ session('success') }}
            </div>
        @endif

        {{-- ====== Empty state ====== --}}
        @if ($promotions->isEmpty())
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-12 text-center">
                <div class="w-16 h-16 bg-[#FFF4EB] rounded-2xl flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-[#FF8A1F]" fill="none" stroke="currentColor" stroke-width="1.5"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M9.568 3H5.25A2.25 2.25 0 0 0 3 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 0 0 5.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 0 0 9.568 3Z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6Z" />
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-gray-900 mb-1">Aucune promotion</h3>
                <p class="text-sm text-gray-500 mb-6 max-w-sm mx-auto">
                    Créez votre première promotion flash pour attirer des clients et booster vos réservations !
                </p>
                <a href="{{ route('owner.marketing.promotions.create') }}"
                    class="inline-flex items-center gap-2 bg-gray-900 text-white px-5 py-2.5 rounded-xl font-semibold text-sm shadow-sm hover:bg-gray-800 transition-all">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    Créer une promotion
                </a>
            </div>
        @else

            {{-- ====== CARDS VIEW ====== --}}
            <div x-show="view === 'cards'" x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 translate-y-1" x-transition:enter-end="opacity-100 translate-y-0"
                class="space-y-3">
                @foreach ($promotions as $promotion)
                    @php
                        $isActive = $promotion->isValid();
                        $isExpired = $promotion->ends_at < now();
                        $isUpcoming = $promotion->starts_at > now() && $promotion->is_active;
                        $progressPercent =
                            $promotion->max_uses > 0
                                ? min(100, round(($promotion->uses_count / $promotion->max_uses) * 100))
                                : null;
                    @endphp
                    <div
                        class="group bg-white rounded-2xl shadow-sm border border-gray-100 hover:shadow-md hover:border-gray-200 transition-all overflow-hidden">
                        <div class="flex flex-col sm:flex-row sm:items-center gap-4 p-5">

                            {{-- Discount badge --}}
                            <div class="shrink-0">
                                <div
                                    class="w-18 h-18 rounded-2xl flex flex-col items-center justify-center font-bold transition-transform group-hover:scale-105
                                    {{ $isActive ? 'bg-linear-to-br from-red-500 to-[#F16A00] text-white shadow-lg shadow-red-500/20' : ($isExpired ? 'bg-gray-100 text-gray-400' : ($isUpcoming ? 'bg-linear-to-br from-blue-500 to-indigo-500 text-white shadow-lg shadow-blue-500/20' : 'bg-gray-100 text-gray-500')) }}">
                                    @if ($promotion->discount_type === 'percentage')
                                        <span class="text-xl leading-none font-extrabold">-{{ intval($promotion->discount_value) }}%</span>
                                    @elseif($promotion->discount_type === 'fixed')
                                        <span class="text-base leading-none font-extrabold">-{{ number_format($promotion->discount_value, 0, ',', ' ') }}</span>
                                        <span class="text-[9px] opacity-80 font-semibold mt-0.5">FCFA</span>
                                    @else
                                        <svg class="w-5 h-5 mb-0.5" fill="none" stroke="currentColor"
                                            stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M21.752 15.002A9.72 9.72 0 0 1 18 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 0 0 3 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 0 0 9.002-5.998Z" />
                                        </svg>
                                        <span class="text-[9px] font-semibold">Nuits offertes</span>
                                    @endif
                                </div>
                            </div>

                            {{-- Info --}}
                            <div class="flex-1 min-w-0">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <h3 class="font-bold text-gray-900 truncate text-base">{{ $promotion->title }}</h3>
                                        <p class="text-sm text-gray-500 truncate mt-0.5 flex items-center gap-1.5">
                                            <svg class="w-3.5 h-3.5 text-gray-400 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
                                            </svg>
                                            {{ $promotion->residence->name ?? 'Résidence supprimée' }}
                                        </p>
                                    </div>

                                    {{-- Status badge --}}
                                    @if ($isActive)
                                        <span class="shrink-0 inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-semibold rounded-full bg-green-50 text-green-700 border border-green-200">
                                            <span class="w-1.5 h-1.5 bg-green-500 rounded-full animate-pulse"></span>
                                            Active
                                        </span>
                                    @elseif($isUpcoming)
                                        <span class="shrink-0 inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-semibold rounded-full bg-blue-50 text-blue-700 border border-blue-200">
                                            <span class="w-1.5 h-1.5 bg-blue-500 rounded-full"></span>
                                            À venir
                                        </span>
                                    @elseif($isExpired)
                                        <span class="shrink-0 inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-semibold rounded-full bg-gray-50 text-gray-500 border border-gray-200">
                                            <span class="w-1.5 h-1.5 bg-gray-400 rounded-full"></span>
                                            Expirée
                                        </span>
                                    @else
                                        <span class="shrink-0 inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-semibold rounded-full bg-yellow-50 text-yellow-700 border border-yellow-200">
                                            <span class="w-1.5 h-1.5 bg-yellow-500 rounded-full"></span>
                                            Désactivée
                                        </span>
                                    @endif
                                </div>

                                {{-- Details row --}}
                                <div class="flex flex-wrap items-center gap-x-5 gap-y-1.5 mt-3 text-xs text-gray-500">
                                    <span class="inline-flex items-center gap-1">
                                        <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor"
                                            stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                                        </svg>
                                        {{ $promotion->starts_at->format('d/m/Y') }} →
                                        {{ $promotion->ends_at->format('d/m/Y') }}
                                    </span>

                                    @if ($promotion->min_nights > 1)
                                        <span class="inline-flex items-center gap-1">
                                            <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor"
                                                stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M21.752 15.002A9.72 9.72 0 0 1 18 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 0 0 3 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 0 0 9.002-5.998Z" />
                                            </svg>
                                            Min. {{ $promotion->min_nights }} nuits
                                        </span>
                                    @endif

                                    <span class="inline-flex items-center gap-1">
                                        <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor"
                                            stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                                        </svg>
                                        {{ $promotion->uses_count }}{{ $promotion->max_uses ? '/' . $promotion->max_uses : '' }}
                                        utilisations
                                    </span>

                                    @if ($isActive)
                                        <span class="inline-flex items-center gap-1 text-green-600 font-medium">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor"
                                                stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                            </svg>
                                            {{ $promotion->time_remaining }}
                                        </span>
                                    @endif
                                </div>

                                {{-- Progress bar --}}
                                @if ($progressPercent !== null)
                                    <div class="mt-3 max-w-xs">
                                        <div class="flex items-center justify-between text-[10px] text-gray-400 mb-1">
                                            <span>Utilisation</span>
                                            <span class="font-semibold">{{ $progressPercent }}%</span>
                                        </div>
                                        <div class="w-full h-1.5 bg-gray-100 rounded-full overflow-hidden">
                                            <div class="h-full rounded-full transition-all duration-500 {{ $progressPercent >= 80 ? 'bg-red-500' : ($progressPercent >= 50 ? 'bg-[#FF8A1F]' : 'bg-green-500') }}"
                                                style="width: {{ $progressPercent }}%"></div>
                                        </div>
                                    </div>
                                @endif
                            </div>

                            {{-- Actions --}}
                            <div class="flex sm:flex-col items-center gap-1 shrink-0">
                                <form action="{{ route('owner.marketing.promotions.toggle', $promotion) }}"
                                    method="POST">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit"
                                        class="p-2 rounded-lg transition-colors {{ $promotion->is_active ? 'text-green-600 hover:bg-green-50' : 'text-gray-400 hover:bg-gray-50' }}"
                                        title="{{ $promotion->is_active ? 'Désactiver' : 'Activer' }}">
                                        @if ($promotion->is_active)
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M15.75 5.25v13.5m-7.5-13.5v13.5" />
                                            </svg>
                                        @else
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M5.25 5.653c0-.856.917-1.398 1.667-.986l11.54 6.347a1.125 1.125 0 0 1 0 1.972l-11.54 6.347a1.125 1.125 0 0 1-1.667-.986V5.653Z" />
                                            </svg>
                                        @endif
                                    </button>
                                </form>

                                <a href="{{ route('owner.marketing.promotions.edit', $promotion) }}"
                                    class="p-2 text-gray-400 hover:text-[#CC5A00] hover:bg-[#FFF4EB] rounded-lg transition-colors"
                                    title="Modifier">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125" />
                                    </svg>
                                </a>

                                <form action="{{ route('owner.marketing.promotions.destroy', $promotion) }}"
                                    method="POST"
                                     data-confirm='Supprimer cette promotion ? Cette action est irréversible.'>
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                        class="p-2 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors"
                                        title="Supprimer">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
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
                                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Promotion</th>
                                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Résidence</th>
                                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Réduction</th>
                                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Période</th>
                                    <th class="text-center px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Utilisations</th>
                                    <th class="text-center px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Statut</th>
                                    <th class="text-right px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                @foreach ($promotions as $promotion)
                                    @php
                                        $isActive = $promotion->isValid();
                                        $isExpired = $promotion->ends_at < now();
                                        $isUpcoming = $promotion->starts_at > now() && $promotion->is_active;
                                    @endphp
                                    <tr class="hover:bg-gray-50/50 transition-colors">
                                        <td class="px-5 py-3.5">
                                            <p class="font-semibold text-gray-900 truncate max-w-45">{{ $promotion->title }}</p>
                                        </td>
                                        <td class="px-5 py-3.5">
                                            <p class="text-gray-600 truncate max-w-37.5">{{ $promotion->residence->name ?? '—' }}</p>
                                        </td>
                                        <td class="px-5 py-3.5">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-bold {{ $isActive ? 'bg-red-50 text-red-700' : 'bg-gray-100 text-gray-600' }}">
                                                {{ $promotion->discount_label }}
                                            </span>
                                        </td>
                                        <td class="px-5 py-3.5 text-gray-600 text-xs whitespace-nowrap">
                                            {{ $promotion->starts_at->format('d/m') }} → {{ $promotion->ends_at->format('d/m/Y') }}
                                        </td>
                                        <td class="px-5 py-3.5 text-center">
                                            <span class="text-gray-900 font-semibold">{{ $promotion->uses_count }}</span>
                                            @if ($promotion->max_uses)
                                                <span class="text-gray-400">/{{ $promotion->max_uses }}</span>
                                            @endif
                                        </td>
                                        <td class="px-5 py-3.5 text-center">
                                            @if ($isActive)
                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 text-[11px] font-semibold rounded-full bg-green-50 text-green-700">
                                                    <span class="w-1.5 h-1.5 bg-green-500 rounded-full animate-pulse"></span>
                                                    Active
                                                </span>
                                            @elseif($isUpcoming)
                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 text-[11px] font-semibold rounded-full bg-blue-50 text-blue-700">
                                                    À venir
                                                </span>
                                            @elseif($isExpired)
                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 text-[11px] font-semibold rounded-full bg-gray-100 text-gray-500">
                                                    Expirée
                                                </span>
                                            @else
                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 text-[11px] font-semibold rounded-full bg-yellow-50 text-yellow-700">
                                                    Off
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-5 py-3.5">
                                            <div class="flex items-center justify-end gap-1">
                                                <form action="{{ route('owner.marketing.promotions.toggle', $promotion) }}" method="POST">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit"
                                                        class="p-1.5 rounded-lg transition-colors {{ $promotion->is_active ? 'text-green-600 hover:bg-green-50' : 'text-gray-400 hover:bg-gray-50' }}"
                                                        title="{{ $promotion->is_active ? 'Désactiver' : 'Activer' }}">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M5.636 5.636a9 9 0 1 0 12.728 0M12 3v9" />
                                                        </svg>
                                                    </button>
                                                </form>
                                                <a href="{{ route('owner.marketing.promotions.edit', $promotion) }}"
                                                    class="p-1.5 text-gray-400 hover:text-[#CC5A00] hover:bg-[#FFF4EB] rounded-lg transition-colors"
                                                    title="Modifier">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125" />
                                                    </svg>
                                                </a>
                                                <form action="{{ route('owner.marketing.promotions.destroy', $promotion) }}" method="POST"
                                                     data-confirm='Supprimer cette promotion ?'>
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit"
                                                        class="p-1.5 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors"
                                                        title="Supprimer">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                                        </svg>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            @if ($promotions->hasPages())
                <div class="mt-6">
                    {{ $promotions->withQueryString()->links() }}
                </div>
            @endif
        @endif
    </div>
@endsection
