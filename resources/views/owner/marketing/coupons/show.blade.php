@extends('layouts.owner')

@section('title', 'Code promo — ' . $coupon->code)

@section('owner-content')
    <div x-data="{
        copied: false,
        copyCode() {
            navigator.clipboard.writeText('{{ $coupon->code }}');
            this.copied = true;
            setTimeout(() => this.copied = false, 2000);
        },
        confirmDelete: false
    }">

        {{-- Header --}}
        <div class="mb-6">
            <a href="{{ route('owner.marketing.coupons.index') }}"
                class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-900 transition mb-4 group">
                <svg class="w-4 h-4 group-hover:-translate-x-0.5 transition-transform" fill="none" stroke="currentColor"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
                Retour aux codes promo
            </a>
        </div>

        {{-- Success message --}}
        @if (session('success'))
            <div x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 4000)"
                class="mb-6 bg-green-50 border border-green-200 rounded-2xl p-4 flex items-center gap-3">
                <div class="w-8 h-8 bg-green-100 rounded-lg flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                </div>
                <p class="text-sm font-medium text-green-800">{{ session('success') }}</p>
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            {{-- LEFT: Coupon Card + Details (2 cols) --}}
            <div class="lg:col-span-2 space-y-6">

                {{-- Visual Coupon Card --}}
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                    <div
                        class="relative p-6 overflow-hidden
                        {{ $coupon->isValid() ? 'bg-linear-to-br from-[#ff385c] to-[#e00b41]' : ($coupon->is_active && $coupon->expires_at && $coupon->expires_at->isPast() ? 'bg-linear-to-br from-amber-400 to-amber-500' : 'bg-linear-to-br from-gray-300 to-gray-400') }}
                        text-white">
                        {{-- Ticket holes --}}
                        <div class="absolute -left-3 top-1/2 -translate-y-1/2 w-6 h-6 bg-white rounded-full"></div>
                        <div class="absolute -right-3 top-1/2 -translate-y-1/2 w-6 h-6 bg-white rounded-full"></div>
                        {{-- Decorative --}}
                        <div class="absolute top-0 right-0 w-40 h-40 opacity-10">
                            <svg viewBox="0 0 100 100" fill="currentColor">
                                <circle cx="80" cy="20" r="50" />
                            </svg>
                        </div>
                        <div class="absolute bottom-0 left-0 w-24 h-24 opacity-5">
                            <svg viewBox="0 0 100 100" fill="currentColor">
                                <circle cx="20" cy="80" r="40" />
                            </svg>
                        </div>

                        <div class="relative">
                            <div class="flex items-center justify-between mb-4">
                                <div class="flex items-center gap-2">
                                    <svg class="w-5 h-5 opacity-80" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z" />
                                    </svg>
                                    <span class="text-xs font-medium opacity-70 uppercase tracking-wider">REZI Promo</span>
                                </div>
                                @if (!$coupon->residence)
                                    <span class="text-xs bg-white/20 px-2.5 py-0.5 rounded-full font-medium">GLOBAL</span>
                                @endif
                            </div>

                            <div class="flex items-end justify-between">
                                <div>
                                    <p class="font-mono text-3xl font-bold tracking-[0.15em] mb-2">{{ $coupon->code }}</p>
                                    <p class="text-xl font-bold">{{ $coupon->discount_label }}</p>
                                </div>
                                <button @click="copyCode()"
                                    class="flex items-center gap-2 px-4 py-2.5 bg-white/20 hover:bg-white/30 rounded-xl transition font-medium text-sm">
                                    <template x-if="!copied">
                                        <span class="flex items-center gap-2">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                            </svg>
                                            Copier
                                        </span>
                                    </template>
                                    <template x-if="copied">
                                        <span class="flex items-center gap-2">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M5 13l4 4L19 7" />
                                            </svg>
                                            Copié !
                                        </span>
                                    </template>
                                </button>
                            </div>
                        </div>
                    </div>

                    {{-- Coupon info beneath card --}}
                    <div class="p-6 flex flex-wrap items-center gap-4 border-b border-gray-100">
                        <div class="flex items-center gap-2">
                            @if ($coupon->isValid())
                                <span
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-sm font-semibold bg-green-50 text-green-700 ring-1 ring-green-600/10">
                                    <span class="w-2 h-2 rounded-full bg-green-500"></span> Actif
                                </span>
                            @elseif (!$coupon->is_active)
                                <span
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-sm font-semibold bg-gray-50 text-gray-600 ring-1 ring-gray-500/10">
                                    <span class="w-2 h-2 rounded-full bg-gray-400"></span> Désactivé
                                </span>
                            @elseif ($coupon->expires_at && $coupon->expires_at->isPast())
                                <span
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-sm font-semibold bg-amber-50 text-amber-700 ring-1 ring-amber-600/10">
                                    <span class="w-2 h-2 rounded-full bg-amber-500"></span> Expiré
                                </span>
                            @elseif ($coupon->max_uses && $coupon->uses_count >= $coupon->max_uses)
                                <span
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-sm font-semibold bg-red-50 text-red-700 ring-1 ring-red-600/10">
                                    <span class="w-2 h-2 rounded-full bg-red-500"></span> Épuisé
                                </span>
                            @endif
                        </div>
                        @if ($coupon->description)
                            <p class="text-sm text-gray-500 italic">{{ $coupon->description }}</p>
                        @endif
                        <div class="ml-auto text-xs text-gray-400">
                            Créé le {{ $coupon->created_at->format('d/m/Y à H:i') }}
                        </div>
                    </div>
                </div>

                {{-- Details Grid --}}
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <h3 class="font-semibold text-gray-900 mb-5 flex items-center gap-2">
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Détails du code
                    </h3>
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-5">
                        <div class="bg-gray-50 rounded-xl p-4">
                            <p class="text-xs text-gray-500 mb-1">Type de réduction</p>
                            <p class="font-semibold text-gray-900">
                                {{ $coupon->discount_type === 'percentage' ? 'Pourcentage' : 'Montant fixe' }}
                            </p>
                        </div>
                        <div class="bg-gray-50 rounded-xl p-4">
                            <p class="text-xs text-gray-500 mb-1">Valeur</p>
                            <p class="font-semibold text-[#e00b41] text-lg">{{ $coupon->discount_label }}</p>
                        </div>
                        <div class="bg-gray-50 rounded-xl p-4">
                            <p class="text-xs text-gray-500 mb-1">Résidence</p>
                            <p class="font-semibold text-gray-900">
                                {{ $coupon->residence ? $coupon->residence->name : '🌐 Global' }}
                            </p>
                        </div>
                        <div class="bg-gray-50 rounded-xl p-4">
                            <p class="text-xs text-gray-500 mb-1">Montant minimum</p>
                            <p class="font-semibold text-gray-900">
                                {{ $coupon->min_amount ? number_format($coupon->min_amount, 0, ',', ' ') . ' FCFA' : 'Aucun' }}
                            </p>
                        </div>
                        <div class="bg-gray-50 rounded-xl p-4">
                            <p class="text-xs text-gray-500 mb-1">Plafond réduction</p>
                            <p class="font-semibold text-gray-900">
                                {{ $coupon->max_discount ? number_format($coupon->max_discount, 0, ',', ' ') . ' FCFA' : 'Illimité' }}
                            </p>
                        </div>
                        <div class="bg-gray-50 rounded-xl p-4">
                            <p class="text-xs text-gray-500 mb-1">Max par client</p>
                            <p class="font-semibold text-gray-900">
                                {{ $coupon->max_uses_per_user ?: 1 }}
                                utilisation{{ ($coupon->max_uses_per_user ?? 1) > 1 ? 's' : '' }}
                            </p>
                        </div>
                        <div class="bg-gray-50 rounded-xl p-4">
                            <p class="text-xs text-gray-500 mb-1">Début</p>
                            <p class="font-semibold text-gray-900">
                                {{ $coupon->starts_at ? $coupon->starts_at->format('d/m/Y') : 'Immédiat' }}
                            </p>
                        </div>
                        <div class="bg-gray-50 rounded-xl p-4">
                            <p class="text-xs text-gray-500 mb-1">Expiration</p>
                            <p
                                class="font-semibold {{ $coupon->expires_at && $coupon->expires_at->isPast() ? 'text-red-600' : 'text-gray-900' }}">
                                {{ $coupon->expires_at ? $coupon->expires_at->format('d/m/Y') : '♾️ Illimité' }}
                            </p>
                        </div>
                        @if ($coupon->first_booking_only)
                            <div class="bg-blue-50 rounded-xl p-4">
                                <p class="text-xs text-blue-600 mb-1">Condition</p>
                                <p class="font-semibold text-blue-700">1ère réservation uniquement</p>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Usage History --}}
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                        <h3 class="font-semibold text-gray-900 flex items-center gap-2">
                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Historique d'utilisation
                        </h3>
                        <span class="text-xs font-medium text-gray-500 bg-gray-100 px-2.5 py-1 rounded-full">
                            {{ $coupon->uses->count() }} utilisation{{ $coupon->uses->count() > 1 ? 's' : '' }}
                        </span>
                    </div>

                    @if ($coupon->uses->isEmpty())
                        <div class="text-center py-10">
                            <div class="w-12 h-12 bg-gray-100 rounded-xl flex items-center justify-center mx-auto mb-3">
                                <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                            </div>
                            <p class="text-sm font-medium text-gray-900">Aucune utilisation</p>
                            <p class="text-xs text-gray-500 mt-1">Ce code n'a pas encore été utilisé</p>
                        </div>
                    @else
                        <div class="divide-y divide-gray-50">
                            @foreach ($coupon->uses as $use)
                                <div class="px-6 py-4 flex items-center justify-between hover:bg-gray-50/50 transition">
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 bg-[#fff0f3] rounded-full flex items-center justify-center">
                                            <span
                                                class="text-sm font-bold text-[#e00b41]">{{ mb_substr($use->user->name ?? 'U', 0, 1) }}</span>
                                        </div>
                                        <div>
                                            <p class="text-sm font-medium text-gray-900">
                                                {{ $use->user->name ?? 'Utilisateur' }}</p>
                                            <p class="text-xs text-gray-500">{{ $use->created_at->format('d/m/Y à H:i') }}
                                            </p>
                                        </div>
                                    </div>
                                    <span class="text-sm font-bold text-green-600">
                                        -{{ number_format($use->discount_applied, 0, ',', ' ') }} F
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            {{-- RIGHT: Stats + Actions (1 col) --}}
            <div class="space-y-5">

                {{-- Stats Card --}}
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-5">Statistiques</h3>

                    {{-- Usage gauge --}}
                    <div class="text-center mb-6">
                        <div class="relative inline-flex items-center justify-center">
                            <svg class="w-28 h-28 transform -rotate-90">
                                <circle cx="56" cy="56" r="48" fill="none" stroke="#f3f4f6"
                                    stroke-width="8" />
                                @php
                                    $percent = $coupon->max_uses
                                        ? min(100, ($coupon->uses_count / $coupon->max_uses) * 100)
                                        : ($coupon->uses_count > 0
                                            ? 50
                                            : 0);
                                    $circumference = 2 * 3.14159 * 48;
                                    $offset = $circumference - ($percent / 100) * $circumference;
                                @endphp
                                <circle cx="56" cy="56" r="48" fill="none"
                                    stroke="{{ $percent >= 100 ? '#ef4444' : ($percent >= 75 ? '#f59e0b' : '#f97316') }}"
                                    stroke-width="8" stroke-linecap="round" stroke-dasharray="{{ $circumference }}"
                                    stroke-dashoffset="{{ $offset }}" />
                            </svg>
                            <div class="absolute">
                                <p class="text-2xl font-bold text-gray-900">{{ $coupon->uses_count }}</p>
                                <p class="text-[10px] text-gray-500">
                                    {{ $coupon->max_uses ? '/ ' . $coupon->max_uses : 'utilisations' }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <div class="flex items-center justify-between py-3 border-t border-gray-100">
                            <span class="text-sm text-gray-500">Économies totales</span>
                            <span class="text-sm font-bold text-green-600">
                                {{ number_format($coupon->uses->sum('discount_applied'), 0, ',', ' ') }} F
                            </span>
                        </div>
                        <div class="flex items-center justify-between py-3 border-t border-gray-100">
                            <span class="text-sm text-gray-500">Réduction moyenne</span>
                            <span class="text-sm font-bold text-gray-900">
                                @if ($coupon->uses->count() > 0)
                                    {{ number_format($coupon->uses->avg('discount_applied'), 0, ',', ' ') }} F
                                @else
                                    —
                                @endif
                            </span>
                        </div>
                        <div class="flex items-center justify-between py-3 border-t border-gray-100">
                            <span class="text-sm text-gray-500">Durée de vie</span>
                            <span class="text-sm font-bold text-gray-900">{{ $coupon->created_at->diffInDays(now()) }}
                                jours</span>
                        </div>
                        @if ($coupon->expires_at && !$coupon->expires_at->isPast())
                            <div class="flex items-center justify-between py-3 border-t border-gray-100">
                                <span class="text-sm text-gray-500">Expire dans</span>
                                <span
                                    class="text-sm font-bold text-amber-600">{{ now()->diffInDays($coupon->expires_at) }}
                                    jours</span>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Actions --}}
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 space-y-2">
                    <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-3">Actions</h3>

                    <a href="{{ route('owner.marketing.coupons.edit', $coupon) }}"
                        class="w-full flex items-center gap-3 px-4 py-3 rounded-xl hover:bg-[#fff0f3] transition text-sm font-medium text-gray-700 hover:text-[#b5083a]">
                        <svg class="w-4.5 h-4.5 text-[#ff385c]" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                        Modifier
                    </a>

                    <form action="{{ route('owner.marketing.coupons.toggle', $coupon) }}" method="POST">
                        @csrf @method('PATCH')
                        <button type="submit"
                            class="w-full flex items-center gap-3 px-4 py-3 rounded-xl hover:bg-gray-50 transition text-sm font-medium text-gray-700">
                            @if ($coupon->is_active)
                                <svg class="w-4.5 h-4.5 text-yellow-500" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                Désactiver
                            @else
                                <svg class="w-4.5 h-4.5 text-green-500" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                Activer
                            @endif
                        </button>
                    </form>

                    <form action="{{ route('owner.marketing.coupons.duplicate', $coupon) }}" method="POST">
                        @csrf
                        <button type="submit"
                            class="w-full flex items-center gap-3 px-4 py-3 rounded-xl hover:bg-blue-50 transition text-sm font-medium text-gray-700 hover:text-blue-700">
                            <svg class="w-4.5 h-4.5 text-blue-500" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                            </svg>
                            Dupliquer
                        </button>
                    </form>

                    <button @click="copyCode()"
                        class="w-full flex items-center gap-3 px-4 py-3 rounded-xl hover:bg-gray-50 transition text-sm font-medium text-gray-700">
                        <svg class="w-4.5 h-4.5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3" />
                        </svg>
                        <span x-text="copied ? '✓ Code copié !' : 'Copier le code'"></span>
                    </button>

                    <div class="border-t border-gray-100 pt-2 mt-2">
                        <button @click="confirmDelete = true"
                            class="w-full flex items-center gap-3 px-4 py-3 rounded-xl hover:bg-red-50 transition text-sm font-medium text-gray-500 hover:text-red-600">
                            <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                            Supprimer
                        </button>
                    </div>
                </div>

                {{-- Sharing tip --}}
                <div class="bg-amber-50 rounded-2xl border border-amber-100 p-5">
                    <div class="flex items-center gap-2 mb-2">
                        <span class="text-base">💡</span>
                        <h4 class="font-semibold text-amber-900 text-sm">Partager ce code</h4>
                    </div>
                    <p class="text-xs text-amber-800 leading-relaxed">
                        Partagez le code <strong class="font-mono">{{ $coupon->code }}</strong> avec vos clients par SMS,
                        WhatsApp ou sur vos réseaux sociaux pour maximiser son utilisation.
                    </p>
                </div>
            </div>
        </div>

        {{-- Delete confirmation modal --}}
        <div x-show="confirmDelete" x-transition.opacity
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50"
            @click.self="confirmDelete = false">
            <div x-show="confirmDelete" x-transition class="bg-white rounded-2xl shadow-xl max-w-sm w-full p-6">
                <div class="text-center mb-5">
                    <div class="w-14 h-14 bg-red-50 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-7 h-7 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900">Supprimer ce code promo ?</h3>
                    <p class="text-sm text-gray-500 mt-2">Le code <strong class="font-mono">{{ $coupon->code }}</strong>
                        et tout son historique d'utilisation seront définitivement supprimés.</p>
                </div>
                <div class="flex gap-3">
                    <button @click="confirmDelete = false"
                        class="flex-1 px-4 py-2.5 border border-gray-200 rounded-xl text-sm font-medium text-gray-700 hover:bg-gray-50 transition">
                        Annuler
                    </button>
                    <form action="{{ route('owner.marketing.coupons.destroy', $coupon) }}" method="POST"
                        class="flex-1">
                        @csrf @method('DELETE')
                        <button type="submit"
                            class="w-full px-4 py-2.5 bg-red-600 text-white rounded-xl text-sm font-medium hover:bg-red-700 transition">
                            Supprimer
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
