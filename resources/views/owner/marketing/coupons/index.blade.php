@extends('layouts.owner')

@section('title', 'Codes Promo')

@section('owner-content')
    <div x-data="{
        view: localStorage.getItem('coupon_view') || 'cards',
        copied: null,
        setView(v) { this.view = v;
            localStorage.setItem('coupon_view', v); },
        copyCode(code) {
            navigator.clipboard.writeText(code);
            this.copied = code;
            setTimeout(() => this.copied = null, 2000);
        }
    }" class="space-y-6">

        {{-- Header --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Codes Promo</h1>
                <p class="text-sm text-gray-500 mt-1">Créez des réductions pour booster vos réservations</p>
            </div>
            <a href="{{ route('owner.marketing.coupons.create') }}"
                class="inline-flex items-center justify-center gap-2 bg-gray-900 text-white px-5 py-2.5 rounded-xl hover:bg-gray-800 transition-all shadow-sm hover:shadow-md font-medium text-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Nouveau code
            </a>
        </div>

        {{-- Success message --}}
        @if (session('success'))
            <div x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 4000)"
                class="bg-green-50 border border-green-200 rounded-2xl p-4 flex items-center gap-3">
                <div class="w-8 h-8 bg-green-100 rounded-lg flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                </div>
                <p class="text-sm font-medium text-green-800">{{ session('success') }}</p>
                <button @click="show = false" class="ml-auto text-green-400 hover:text-green-600">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        @endif

        {{-- Stats --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-10 h-10 bg-purple-50 rounded-xl flex items-center justify-center">
                        <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z" />
                        </svg>
                    </div>
                </div>
                <p class="text-2xl font-bold text-gray-900">{{ $stats['total'] }}</p>
                <p class="text-xs text-gray-500 mt-0.5">Codes créés</p>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-10 h-10 bg-green-50 rounded-xl flex items-center justify-center">
                        <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    @if ($stats['active'] > 0)
                        <span class="relative flex h-2.5 w-2.5">
                            <span
                                class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-green-500"></span>
                        </span>
                    @endif
                </div>
                <p class="text-2xl font-bold text-gray-900">{{ $stats['active'] }}</p>
                <p class="text-xs text-gray-500 mt-0.5">Actifs maintenant</p>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-10 h-10 bg-blue-50 rounded-xl flex items-center justify-center">
                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                        </svg>
                    </div>
                </div>
                <p class="text-2xl font-bold text-gray-900">{{ number_format($stats['total_uses']) }}</p>
                <p class="text-xs text-gray-500 mt-0.5">Utilisations totales</p>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-10 h-10 bg-amber-50 rounded-xl flex items-center justify-center">
                        <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
                <p class="text-2xl font-bold text-gray-900">
                    {{ number_format($stats['total_discount'], 0, ',', ' ') }}
                    <span class="text-sm font-medium text-gray-400">F</span>
                </p>
                <p class="text-xs text-gray-500 mt-0.5">Économies clients</p>
            </div>
        </div>

        {{-- Filters + View Toggle --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4">
            <form method="GET" class="flex flex-wrap items-center gap-3">
                <div class="flex-1 min-w-44">
                    <select name="residence_id"
                        class="w-full border-gray-200 rounded-xl text-sm focus:ring-[#F16A00] focus:border-[#F16A00] py-2.5 bg-gray-50 focus:bg-white transition">
                        <option value="">Toutes les résidences</option>
                        <option value="global" {{ request('residence_id') === 'global' ? 'selected' : '' }}>🌐 Codes globaux
                        </option>
                        @foreach ($residences as $residence)
                            <option value="{{ $residence->id }}"
                                {{ request('residence_id') == $residence->id ? 'selected' : '' }}>
                                🏠 {{ $residence->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="min-w-36">
                    <select name="status"
                        class="w-full border-gray-200 rounded-xl text-sm focus:ring-[#F16A00] focus:border-[#F16A00] py-2.5 bg-gray-50 focus:bg-white transition">
                        <option value="">Tous les statuts</option>
                        <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>✅ Actifs</option>
                        <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>⏸️ Inactifs
                        </option>
                        <option value="expired" {{ request('status') === 'expired' ? 'selected' : '' }}>⏰ Expirés</option>
                        <option value="exhausted" {{ request('status') === 'exhausted' ? 'selected' : '' }}>🚫 Épuisés
                        </option>
                    </select>
                </div>
                <button type="submit"
                    class="px-4 py-2.5 bg-gray-900 text-white rounded-xl hover:bg-gray-800 transition text-sm font-medium">
                    Filtrer
                </button>
                @if (request()->hasAny(['residence_id', 'status']))
                    <a href="{{ route('owner.marketing.coupons.index') }}"
                        class="text-sm text-gray-500 hover:text-gray-700 transition">
                        ✕ Réinitialiser
                    </a>
                @endif

                {{-- View toggle --}}
                <div class="ml-auto flex items-center bg-gray-100 rounded-lg p-0.5">
                    <button type="button" @click="setView('cards')" class="p-2 rounded-md transition-all"
                        :class="view === 'cards' ? 'bg-white shadow-sm text-gray-900' : 'text-gray-400 hover:text-gray-600'">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                        </svg>
                    </button>
                    <button type="button" @click="setView('table')" class="p-2 rounded-md transition-all"
                        :class="view === 'table' ? 'bg-white shadow-sm text-gray-900' : 'text-gray-400 hover:text-gray-600'">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                        </svg>
                    </button>
                </div>
            </form>
        </div>

        @if ($coupons->isEmpty())
            {{-- Empty state --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-12 text-center">
                <div class="w-16 h-16 bg-[#FFF4EB] rounded-2xl flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-[#FF8A1F]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z" />
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 mb-1">Aucun code promo</h3>
                <p class="text-sm text-gray-500 mb-6 max-w-sm mx-auto">Créez votre premier code promo pour attirer plus de
                    clients et booster vos réservations.</p>
                <a href="{{ route('owner.marketing.coupons.create') }}"
                    class="inline-flex items-center gap-2 bg-gray-900 text-white px-5 py-2.5 rounded-xl hover:bg-gray-800 transition-all shadow-sm font-medium text-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Créer mon premier code
                </a>
            </div>
        @else
            {{-- Cards View --}}
            <div x-show="view === 'cards'" x-transition.opacity
                class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                @foreach ($coupons as $coupon)
                    <div
                        class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-md transition-all group">
                        {{-- Coupon header with ticket design --}}
                        <div
                            class="relative p-5 overflow-hidden
                            {{ $coupon->isValid() ? 'bg-linear-to-br from-[#F16A00] to-[#CC5A00]' : ($coupon->is_active && $coupon->expires_at && $coupon->expires_at->isPast() ? 'bg-linear-to-br from-amber-400 to-amber-500' : 'bg-linear-to-br from-gray-300 to-gray-400') }}
                            text-white">
                            {{-- Ticket holes --}}
                            <div class="absolute -left-2.5 top-1/2 -translate-y-1/2 w-5 h-5 bg-white rounded-full"></div>
                            <div class="absolute -right-2.5 top-1/2 -translate-y-1/2 w-5 h-5 bg-white rounded-full"></div>
                            {{-- Decorative pattern --}}
                            <div class="absolute top-0 right-0 w-24 h-24 opacity-10">
                                <svg viewBox="0 0 100 100" fill="currentColor">
                                    <circle cx="80" cy="20" r="40" />
                                </svg>
                            </div>

                            <div class="relative">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-[10px] font-medium opacity-70 uppercase tracking-wider">Rezi Studio Meublé Faya
                                        Promo</span>
                                    @if (!$coupon->residence)
                                        <span
                                            class="text-[10px] bg-white/20 px-2 py-0.5 rounded-full font-medium">GLOBAL</span>
                                    @endif
                                </div>
                                <div class="flex items-end justify-between">
                                    <div>
                                        <p class="font-mono text-xl font-bold tracking-wider">{{ $coupon->code }}</p>
                                        <p class="text-lg font-bold mt-1">{{ $coupon->discount_label }}</p>
                                    </div>
                                    <button @click.prevent="copyCode('{{ $coupon->code }}')"
                                        class="p-2 bg-white/20 hover:bg-white/30 rounded-lg transition"
                                        :title="copied === '{{ $coupon->code }}' ? 'Copié !' : 'Copier le code'">
                                        <template x-if="copied === '{{ $coupon->code }}'">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M5 13l4 4L19 7" />
                                            </svg>
                                        </template>
                                        <template x-if="copied !== '{{ $coupon->code }}'">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                            </svg>
                                        </template>
                                    </button>
                                </div>
                            </div>
                        </div>

                        {{-- Card body --}}
                        <div class="p-5">
                            <div class="space-y-2.5 mb-4">
                                <div class="flex items-center justify-between text-sm">
                                    <span class="text-gray-500">Résidence</span>
                                    <span class="font-medium text-gray-900 text-right truncate max-w-[60%]">
                                        {{ $coupon->residence ? Str::limit($coupon->residence->name, 20) : '🌐 Toutes' }}
                                    </span>
                                </div>
                                <div class="flex items-center justify-between text-sm">
                                    <span class="text-gray-500">Utilisations</span>
                                    <span class="font-medium text-gray-900">
                                        {{ $coupon->uses_count }}{{ $coupon->max_uses ? ' / ' . $coupon->max_uses : '' }}
                                    </span>
                                </div>
                                @if ($coupon->max_uses)
                                    <div class="w-full bg-gray-100 rounded-full h-1.5">
                                        <div class="h-1.5 rounded-full transition-all {{ $coupon->uses_count >= $coupon->max_uses ? 'bg-red-500' : 'bg-[#F16A00]' }}"
                                            style="width: {{ min(100, ($coupon->uses_count / max(1, $coupon->max_uses)) * 100) }}%">
                                        </div>
                                    </div>
                                @endif
                                <div class="flex items-center justify-between text-sm">
                                    <span class="text-gray-500">Expire</span>
                                    <span
                                        class="font-medium {{ $coupon->expires_at && $coupon->expires_at->isPast() ? 'text-red-600' : 'text-gray-900' }}">
                                        {{ $coupon->expires_at ? $coupon->expires_at->format('d/m/Y') : '♾️ Jamais' }}
                                    </span>
                                </div>
                            </div>

                            {{-- Status + Actions --}}
                            <div class="flex items-center justify-between pt-4 border-t border-gray-100">
                                <div>
                                    @if ($coupon->isValid())
                                        <span
                                            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-green-50 text-green-700 ring-1 ring-green-600/10">
                                            <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span> Actif
                                        </span>
                                    @elseif (!$coupon->is_active)
                                        <span
                                            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-gray-50 text-gray-600 ring-1 ring-gray-500/10">
                                            <span class="w-1.5 h-1.5 rounded-full bg-gray-400"></span> Désactivé
                                        </span>
                                    @elseif ($coupon->expires_at && $coupon->expires_at->isPast())
                                        <span
                                            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-50 text-amber-700 ring-1 ring-amber-600/10">
                                            <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span> Expiré
                                        </span>
                                    @elseif ($coupon->max_uses && $coupon->uses_count >= $coupon->max_uses)
                                        <span
                                            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-red-50 text-red-700 ring-1 ring-red-600/10">
                                            <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span> Épuisé
                                        </span>
                                    @endif
                                </div>
                                <div class="flex items-center gap-0.5">
                                    <a href="{{ route('owner.marketing.coupons.show', $coupon) }}"
                                        class="p-2 text-gray-400 hover:text-gray-700 hover:bg-gray-100 rounded-lg transition"
                                        title="Détails">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                    </a>
                                    <a href="{{ route('owner.marketing.coupons.edit', $coupon) }}"
                                        class="p-2 text-gray-400 hover:text-[#CC5A00] hover:bg-[#FFF4EB] rounded-lg transition"
                                        title="Modifier">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </a>
                                    <form action="{{ route('owner.marketing.coupons.toggle', $coupon) }}" method="POST"
                                        class="inline">
                                        @csrf @method('PATCH')
                                        <button type="submit"
                                            class="p-2 rounded-lg transition {{ $coupon->is_active ? 'text-gray-400 hover:text-yellow-600 hover:bg-yellow-50' : 'text-gray-400 hover:text-green-600 hover:bg-green-50' }}"
                                            title="{{ $coupon->is_active ? 'Désactiver' : 'Activer' }}">
                                            @if ($coupon->is_active)
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                            @else
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                            @endif
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Table View --}}
            <div x-show="view === 'table'" x-transition.opacity
                class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-100">
                        <thead>
                            <tr class="bg-gray-50/80">
                                <th
                                    class="px-5 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                    Code</th>
                                <th
                                    class="px-5 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                    Résidence</th>
                                <th
                                    class="px-5 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                    Réduction</th>
                                <th
                                    class="px-5 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                    Utilisations</th>
                                <th
                                    class="px-5 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                    Expire</th>
                                <th
                                    class="px-5 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                    Statut</th>
                                <th
                                    class="px-5 py-3.5 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                    Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @foreach ($coupons as $coupon)
                                <tr class="hover:bg-gray-50/50 transition">
                                    <td class="px-5 py-4 whitespace-nowrap">
                                        <div class="flex items-center gap-2">
                                            <span
                                                class="font-mono text-sm font-bold text-gray-900 bg-gray-100 px-2.5 py-1 rounded-lg">{{ $coupon->code }}</span>
                                            <button @click="copyCode('{{ $coupon->code }}')"
                                                class="text-gray-300 hover:text-gray-600 transition">
                                                <template x-if="copied === '{{ $coupon->code }}'">
                                                    <svg class="w-3.5 h-3.5 text-green-500" fill="none"
                                                        stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2" d="M5 13l4 4L19 7" />
                                                    </svg>
                                                </template>
                                                <template x-if="copied !== '{{ $coupon->code }}'">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                                    </svg>
                                                </template>
                                            </button>
                                        </div>
                                    </td>
                                    <td class="px-5 py-4 whitespace-nowrap text-sm text-gray-700">
                                        {{ $coupon->residence ? Str::limit($coupon->residence->name, 25) : '🌐 Global' }}
                                    </td>
                                    <td class="px-5 py-4 whitespace-nowrap">
                                        <span
                                            class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold {{ $coupon->discount_type === 'percentage' ? 'bg-purple-50 text-purple-700' : 'bg-blue-50 text-blue-700' }}">
                                            {{ $coupon->discount_label }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-4 whitespace-nowrap text-sm">
                                        <span class="font-semibold text-gray-900">{{ $coupon->uses_count }}</span>
                                        <span
                                            class="text-gray-400">{{ $coupon->max_uses ? ' / ' . $coupon->max_uses : '' }}</span>
                                    </td>
                                    <td
                                        class="px-5 py-4 whitespace-nowrap text-sm {{ $coupon->expires_at && $coupon->expires_at->isPast() ? 'text-red-600 font-medium' : 'text-gray-500' }}">
                                        {{ $coupon->expires_at ? $coupon->expires_at->format('d/m/Y') : '♾️ Illimité' }}
                                    </td>
                                    <td class="px-5 py-4 whitespace-nowrap">
                                        @if ($coupon->isValid())
                                            <span
                                                class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold bg-green-50 text-green-700 ring-1 ring-green-600/10">
                                                <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span> Actif
                                            </span>
                                        @elseif (!$coupon->is_active)
                                            <span
                                                class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold bg-gray-50 text-gray-600 ring-1 ring-gray-500/10">
                                                <span class="w-1.5 h-1.5 rounded-full bg-gray-400"></span> Off
                                            </span>
                                        @elseif ($coupon->expires_at && $coupon->expires_at->isPast())
                                            <span
                                                class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold bg-amber-50 text-amber-700 ring-1 ring-amber-600/10">
                                                <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span> Expiré
                                            </span>
                                        @elseif ($coupon->max_uses && $coupon->uses_count >= $coupon->max_uses)
                                            <span
                                                class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold bg-red-50 text-red-700 ring-1 ring-red-600/10">
                                                <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span> Épuisé
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-4 whitespace-nowrap text-right">
                                        <div class="flex items-center justify-end gap-0.5">
                                            <a href="{{ route('owner.marketing.coupons.show', $coupon) }}"
                                                class="p-1.5 text-gray-400 hover:text-gray-700 hover:bg-gray-100 rounded-lg transition"
                                                title="Voir">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                </svg>
                                            </a>
                                            <a href="{{ route('owner.marketing.coupons.edit', $coupon) }}"
                                                class="p-1.5 text-gray-400 hover:text-[#CC5A00] hover:bg-[#FFF4EB] rounded-lg transition"
                                                title="Modifier">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                </svg>
                                            </a>
                                            <form action="{{ route('owner.marketing.coupons.toggle', $coupon) }}"
                                                method="POST" class="inline">
                                                @csrf @method('PATCH')
                                                <button type="submit"
                                                    class="p-1.5 rounded-lg transition {{ $coupon->is_active ? 'text-gray-400 hover:text-yellow-600 hover:bg-yellow-50' : 'text-gray-400 hover:text-green-600 hover:bg-green-50' }}"
                                                    title="{{ $coupon->is_active ? 'Désactiver' : 'Activer' }}">
                                                    @if ($coupon->is_active)
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                            viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                        </svg>
                                                    @else
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                            viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                        </svg>
                                                    @endif
                                                </button>
                                            </form>
                                            <form action="{{ route('owner.marketing.coupons.duplicate', $coupon) }}"
                                                method="POST" class="inline">
                                                @csrf
                                                <button type="submit"
                                                    class="p-1.5 text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition"
                                                    title="Dupliquer">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                                    </svg>
                                                </button>
                                            </form>
                                            <form action="{{ route('owner.marketing.coupons.destroy', $coupon) }}"
                                                method="POST" class="inline"
                                                onsubmit="return confirm('Supprimer définitivement ce code promo ?')">
                                                @csrf @method('DELETE')
                                                <button type="submit"
                                                    class="p-1.5 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition"
                                                    title="Supprimer">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
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

            {{-- Pagination --}}
            @if ($coupons->hasPages())
                <div class="mt-2">
                    {{ $coupons->withQueryString()->links() }}
                </div>
            @endif
        @endif
    </div>
@endsection
