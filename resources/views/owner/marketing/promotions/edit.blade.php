@extends('layouts.owner')

@section('title', 'Modifier la promotion')

@section('owner-content')
    <div x-data="{
        discountType: '{{ old('discount_type', $promotion->discount_type) }}',
        discountValue: '{{ old('discount_value', $promotion->discount_value) }}',
        title: '{{ old('title', addslashes($promotion->title)) }}',
        startsAt: '{{ old('starts_at', $promotion->starts_at?->format('Y-m-d')) }}',
        endsAt: '{{ old('ends_at', $promotion->ends_at?->format('Y-m-d')) }}',
        description: `{{ old('description', addslashes($promotion->description ?? '')) }}`,
        isActive: {{ old('is_active', $promotion->is_active) ? 'true' : 'false' }},
        minNights: '{{ old('min_nights', $promotion->min_nights ?? '') }}',
        maxUses: '{{ old('max_uses', $promotion->max_uses ?? '') }}',
        showDeleteConfirm: false,
        get daysCount() {
            if (!this.startsAt || !this.endsAt) return 0;
            const start = new Date(this.startsAt);
            const end = new Date(this.endsAt);
            const diff = Math.ceil((end - start) / (1000 * 60 * 60 * 24));
            return diff > 0 ? diff : 0;
        },
        get discountLabel() {
            if (!this.discountValue) return '';
            if (this.discountType === 'percentage') return '-' + this.discountValue + '%';
            if (this.discountType === 'fixed') return '-' + Number(this.discountValue).toLocaleString('fr-FR') + ' F';
            return this.discountValue + ' nuit' + (this.discountValue > 1 ? 's' : '') + ' offerte' + (this.discountValue > 1 ? 's' : '');
        },
    }">

        {{-- ====== Header ====== --}}
        <div class="mb-8">
            <a href="{{ route('owner.marketing.promotions.index') }}"
                class="inline-flex items-center gap-2 text-sm font-medium text-gray-500 hover:text-[#CC5A00] transition-colors mb-5 group">
                <svg class="w-4 h-4 transition-transform group-hover:-translate-x-0.5" fill="none" stroke="currentColor"
                    stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
                </svg>
                Retour aux promotions
            </a>
            <div class="flex items-start justify-between gap-4">
                <div class="flex items-center gap-4">
                    <div
                        class="w-12 h-12 rounded-2xl bg-linear-to-br from-[#F16A00] to-red-500 text-white flex items-center justify-center shadow-lg shadow-none">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L6.832 19.82a4.5 4.5 0 0 1-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 0 1 1.13-1.897L16.863 4.487Z" />
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">Modifier la promotion</h1>
                        <p class="text-sm text-gray-500 mt-0.5">{{ $promotion->title }}</p>
                    </div>
                </div>

                {{-- Status badge --}}
                @if ($promotion->isValid())
                    <span
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-full bg-green-50 text-green-700 border border-green-200">
                        <span class="w-1.5 h-1.5 bg-green-500 rounded-full animate-pulse"></span>
                        Active
                    </span>
                @elseif($promotion->ends_at < now())
                    <span
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-full bg-gray-50 text-gray-500 border border-gray-200">
                        <span class="w-1.5 h-1.5 bg-gray-400 rounded-full"></span>
                        Expirée
                    </span>
                @elseif(!$promotion->is_active)
                    <span
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-full bg-yellow-50 text-yellow-700 border border-yellow-200">
                        <span class="w-1.5 h-1.5 bg-yellow-500 rounded-full"></span>
                        Désactivée
                    </span>
                @else
                    <span
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-full bg-blue-50 text-blue-700 border border-blue-200">
                        <span class="w-1.5 h-1.5 bg-blue-500 rounded-full"></span>
                        À venir
                    </span>
                @endif
            </div>
        </div>

        {{-- ====== Stats KPI ====== --}}
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-8">
            @php
                $daysActive = $promotion->starts_at ? $promotion->starts_at->diffInDays(now()) : 0;
                $progressPercent =
                    $promotion->max_uses > 0
                        ? min(100, round(($promotion->uses_count / $promotion->max_uses) * 100))
                        : null;
            @endphp
            <div class="bg-white rounded-2xl border border-gray-100 p-4 shadow-sm">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-blue-50 flex items-center justify-center">
                        <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" stroke-width="2"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-xl font-bold text-gray-900">{{ $promotion->uses_count }}</p>
                        <p class="text-xs text-gray-500">Utilisations</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-2xl border border-gray-100 p-4 shadow-sm">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-green-50 flex items-center justify-center">
                        <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" stroke-width="2"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-xl font-bold text-gray-900">{{ $daysActive }}j</p>
                        <p class="text-xs text-gray-500">Jours actifs</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-2xl border border-gray-100 p-4 shadow-sm">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-[#FFF4EB] flex items-center justify-center">
                        <svg class="w-4 h-4 text-[#CC5A00]" fill="none" stroke="currentColor" stroke-width="2"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-xl font-bold text-gray-900">{{ $promotion->time_remaining ?? '∞' }}</p>
                        <p class="text-xs text-gray-500">Temps restant</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-2xl border border-gray-100 p-4 shadow-sm">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-purple-50 flex items-center justify-center">
                        <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" stroke-width="2"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z" />
                        </svg>
                    </div>
                    <div>
                        @if ($progressPercent !== null)
                            <p class="text-xl font-bold text-gray-900">{{ $progressPercent }}%</p>
                            <p class="text-xs text-gray-500">Quota utilisé</p>
                        @else
                            <p class="text-xl font-bold text-gray-900">∞</p>
                            <p class="text-xs text-gray-500">Illimité</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- ====== Error summary ====== --}}
        @if ($errors->any())
            <div class="mb-6 bg-red-50 border border-red-200 rounded-2xl p-5">
                <div class="flex items-start gap-3">
                    <div class="w-8 h-8 rounded-xl bg-red-100 flex items-center justify-center shrink-0 mt-0.5">
                        <svg class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" stroke-width="2"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-red-800 mb-1.5">Veuillez corriger les erreurs suivantes</p>
                        <ul class="list-disc list-inside text-sm text-red-600 space-y-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        @endif

        {{-- ====== Main layout: Form + Preview sidebar ====== --}}
        <form action="{{ route('owner.marketing.promotions.update', $promotion) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="flex flex-col lg:flex-row gap-6">

                {{-- ========== LEFT: Form ========== --}}
                <div class="flex-1 space-y-6 min-w-0">

                    {{-- Section 1: Résidence (readonly) --}}
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-50 bg-gray-50/50">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-lg bg-[#FFE7D1] flex items-center justify-center">
                                    <svg class="w-4 h-4 text-[#CC5A00]" fill="none" stroke="currentColor"
                                        stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
                                    </svg>
                                </div>
                                <div>
                                    <h2 class="text-base font-bold text-gray-900">Résidence</h2>
                                    <p class="text-xs text-gray-500">Associée à cette promotion</p>
                                </div>
                            </div>
                        </div>
                        <div class="p-6">
                            <div class="flex items-center gap-4 bg-gray-50 rounded-xl p-4 border border-gray-100">
                                <div
                                    class="w-12 h-12 rounded-xl bg-[#FFE7D1] text-[#CC5A00] flex items-center justify-center shrink-0">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.5"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
                                    </svg>
                                </div>
                                <div>
                                    <p class="font-semibold text-gray-900">{{ $promotion->residence->name }}</p>
                                    @if ($promotion->residence->price_per_night)
                                        <p class="text-sm text-gray-500">
                                            {{ number_format($promotion->residence->price_per_night, 0, ',', ' ') }}
                                            FCFA/nuit</p>
                                    @endif
                                </div>
                            </div>
                            <p class="mt-2.5 text-xs text-gray-400 flex items-center gap-1.5">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                                </svg>
                                La résidence ne peut pas être changée. Créez une nouvelle promotion si nécessaire.
                            </p>
                        </div>
                    </div>

                    {{-- Section 2: Détails de l'offre --}}
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-50 bg-gray-50/50">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-lg bg-purple-100 flex items-center justify-center">
                                    <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor"
                                        stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M9.568 3H5.25A2.25 2.25 0 0 0 3 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 0 0 5.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 0 0 9.568 3Z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6Z" />
                                    </svg>
                                </div>
                                <div>
                                    <h2 class="text-base font-bold text-gray-900">Détails de l'offre</h2>
                                    <p class="text-xs text-gray-500">Nom et conditions de réduction</p>
                                </div>
                            </div>
                        </div>
                        <div class="p-6 space-y-6">
                            {{-- Titre --}}
                            <div>
                                <label for="title" class="block text-sm font-semibold text-gray-700 mb-2">
                                    Titre de la promotion <span class="text-red-500">*</span>
                                </label>
                                <input type="text" name="title" id="title" x-model="title"
                                    value="{{ old('title', $promotion->title) }}" required
                                    placeholder="Ex: Promo Noël, Offre Week-end, Flash -30%…"
                                    class="w-full text-base border-gray-200 rounded-xl bg-gray-50/50 focus:ring-2 focus:ring-[#F16A00]/20 focus:border-[#F16A00] py-3 {{ $errors->has('title') ? 'border-red-400 bg-red-50' : '' }}">
                                @error('title')
                                    <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Type de réduction — Cartes visuelles --}}
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-3">
                                    Type de réduction <span class="text-red-500">*</span>
                                </label>
                                <input type="hidden" name="discount_type" :value="discountType">
                                <div class="grid grid-cols-3 gap-3">
                                    {{-- Pourcentage --}}
                                    <button type="button" @click="discountType = 'percentage'"
                                        class="relative flex flex-col items-center gap-2 p-4 rounded-xl border-2 transition-all text-center"
                                        :class="discountType === 'percentage' ?
                                            'border-[#F16A00] bg-[#FFF4EB] shadow-sm shadow-none/10' :
                                            'border-gray-200 bg-white hover:border-gray-300 hover:bg-gray-50'">
                                        <div class="w-10 h-10 rounded-xl flex items-center justify-center text-lg font-bold transition-colors"
                                            :class="discountType === 'percentage' ? 'bg-[#F16A00] text-white' :
                                                'bg-gray-100 text-gray-500'">
                                            %
                                        </div>
                                        <div>
                                            <p class="text-sm font-semibold"
                                                :class="discountType === 'percentage' ? 'text-[#A34700]' : 'text-gray-700'">
                                                Pourcentage</p>
                                            <p class="text-xs text-gray-400 mt-0.5">Ex: -20%</p>
                                        </div>
                                        <div x-show="discountType === 'percentage'"
                                            class="absolute top-2 right-2 w-5 h-5 rounded-full bg-[#F16A00] text-white flex items-center justify-center">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="3"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="m4.5 12.75 6 6 9-13.5" />
                                            </svg>
                                        </div>
                                    </button>

                                    {{-- Montant fixe --}}
                                    <button type="button" @click="discountType = 'fixed'"
                                        class="relative flex flex-col items-center gap-2 p-4 rounded-xl border-2 transition-all text-center"
                                        :class="discountType === 'fixed' ?
                                            'border-[#F16A00] bg-[#FFF4EB] shadow-sm shadow-none/10' :
                                            'border-gray-200 bg-white hover:border-gray-300 hover:bg-gray-50'">
                                        <div class="w-10 h-10 rounded-xl flex items-center justify-center text-sm font-bold transition-colors"
                                            :class="discountType === 'fixed' ? 'bg-[#F16A00] text-white' :
                                                'bg-gray-100 text-gray-500'">
                                            F
                                        </div>
                                        <div>
                                            <p class="text-sm font-semibold"
                                                :class="discountType === 'fixed' ? 'text-[#A34700]' : 'text-gray-700'">
                                                Montant fixe</p>
                                            <p class="text-xs text-gray-400 mt-0.5">Ex: -5 000 FCFA</p>
                                        </div>
                                        <div x-show="discountType === 'fixed'"
                                            class="absolute top-2 right-2 w-5 h-5 rounded-full bg-[#F16A00] text-white flex items-center justify-center">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="3"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="m4.5 12.75 6 6 9-13.5" />
                                            </svg>
                                        </div>
                                    </button>

                                    {{-- Nuits offertes --}}
                                    <button type="button" @click="discountType = 'free_nights'"
                                        class="relative flex flex-col items-center gap-2 p-4 rounded-xl border-2 transition-all text-center"
                                        :class="discountType === 'free_nights' ?
                                            'border-[#F16A00] bg-[#FFF4EB] shadow-sm shadow-none/10' :
                                            'border-gray-200 bg-white hover:border-gray-300 hover:bg-gray-50'">
                                        <div class="w-10 h-10 rounded-xl flex items-center justify-center transition-colors"
                                            :class="discountType === 'free_nights' ? 'bg-[#F16A00] text-white' :
                                                'bg-gray-100 text-gray-500'">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M21.752 15.002A9.72 9.72 0 0 1 18 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 0 0 3 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 0 0 9.002-5.998Z" />
                                            </svg>
                                        </div>
                                        <div>
                                            <p class="text-sm font-semibold"
                                                :class="discountType === 'free_nights' ? 'text-[#A34700]' :
                                                    'text-gray-700'">
                                                Nuits offertes</p>
                                            <p class="text-xs text-gray-400 mt-0.5">Ex: 1 nuit gratuite</p>
                                        </div>
                                        <div x-show="discountType === 'free_nights'"
                                            class="absolute top-2 right-2 w-5 h-5 rounded-full bg-[#F16A00] text-white flex items-center justify-center">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="3"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="m4.5 12.75 6 6 9-13.5" />
                                            </svg>
                                        </div>
                                    </button>
                                </div>
                            </div>

                            {{-- Valeur de réduction --}}
                            <div>
                                <label for="discount_value" class="block text-sm font-semibold text-gray-700 mb-2">
                                    Valeur de la réduction <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <input type="number" name="discount_value" id="discount_value"
                                        x-model="discountValue"
                                        value="{{ old('discount_value', $promotion->discount_value) }}" required
                                        min="1" step="1"
                                        class="w-full text-base border-gray-200 rounded-xl bg-gray-50/50 focus:ring-2 focus:ring-[#F16A00]/20 focus:border-[#F16A00] py-3 pr-20 {{ $errors->has('discount_value') ? 'border-red-400 bg-red-50' : '' }}"
                                        :placeholder="discountType === 'percentage' ? 'Ex: 20' : (
                                            discountType ===
                                            'fixed' ?
                                            'Ex: 5000' : 'Ex: 1')">
                                    <span
                                        class="absolute right-4 top-1/2 -translate-y-1/2 text-sm text-gray-400 font-semibold bg-gray-100 px-2.5 py-1 rounded-lg"
                                        x-text="discountType === 'percentage' ? '%' : (discountType === 'fixed' ? 'FCFA' : 'nuits')"></span>
                                </div>
                                @error('discount_value')
                                    <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                                <p class="mt-2 text-xs text-gray-400 flex items-center gap-1.5"
                                    x-show="discountType === 'percentage'">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" />
                                    </svg>
                                    Maximum autorisé : 90%
                                </p>
                            </div>

                            {{-- Description --}}
                            <div>
                                <label for="description" class="block text-sm font-semibold text-gray-700 mb-2">
                                    Description
                                    <span class="text-gray-400 text-xs font-normal ml-1">(optionnel)</span>
                                </label>
                                <textarea name="description" id="description" rows="3" x-model="description"
                                    placeholder="Décrivez les conditions de votre offre…"
                                    class="w-full text-base border-gray-200 rounded-xl bg-gray-50/50 focus:ring-2 focus:ring-[#F16A00]/20 focus:border-[#F16A00] resize-none py-3">{{ old('description', $promotion->description) }}</textarea>
                                <p class="mt-1.5 text-xs text-gray-400">Visible par les clients sur la page de la
                                    résidence</p>
                            </div>
                        </div>
                    </div>

                    {{-- Section 3: Conditions & Période --}}
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-50 bg-gray-50/50">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-lg bg-blue-100 flex items-center justify-center">
                                    <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor"
                                        stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                                    </svg>
                                </div>
                                <div>
                                    <h2 class="text-base font-bold text-gray-900">Conditions & Période</h2>
                                    <p class="text-xs text-gray-500">Durée de validité et restrictions</p>
                                </div>
                            </div>
                        </div>
                        <div class="p-6 space-y-6">
                            {{-- Période --}}
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-3">Période de validité <span
                                        class="text-red-500">*</span></label>
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label for="starts_at" class="block text-xs font-medium text-gray-500 mb-1.5">Date
                                            de début</label>
                                        <input type="date" name="starts_at" id="starts_at" x-model="startsAt"
                                            value="{{ old('starts_at', $promotion->starts_at?->format('Y-m-d')) }}"
                                            required
                                            class="w-full text-base border-gray-200 rounded-xl bg-gray-50/50 focus:ring-2 focus:ring-[#F16A00]/20 focus:border-[#F16A00] py-3 {{ $errors->has('starts_at') ? 'border-red-400 bg-red-50' : '' }}">
                                        @error('starts_at')
                                            <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>
                                    <div>
                                        <label for="ends_at" class="block text-xs font-medium text-gray-500 mb-1.5">Date
                                            de fin</label>
                                        <input type="date" name="ends_at" id="ends_at" x-model="endsAt"
                                            value="{{ old('ends_at', $promotion->ends_at?->format('Y-m-d')) }}" required
                                            class="w-full text-base border-gray-200 rounded-xl bg-gray-50/50 focus:ring-2 focus:ring-[#F16A00]/20 focus:border-[#F16A00] py-3 {{ $errors->has('ends_at') ? 'border-red-400 bg-red-50' : '' }}">
                                        @error('ends_at')
                                            <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>
                                <div x-show="daysCount > 0" x-transition
                                    class="mt-3 inline-flex items-center gap-1.5 text-sm text-blue-600 bg-blue-50 px-3 py-1.5 rounded-lg">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                    </svg>
                                    Durée : <span class="font-semibold" x-text="daysCount"></span> jours
                                </div>
                            </div>

                            <div class="border-t border-gray-100 pt-6">
                                <label class="block text-sm font-semibold text-gray-700 mb-3">Restrictions
                                    <span class="text-gray-400 text-xs font-normal ml-1">(optionnel)</span>
                                </label>
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label for="min_nights"
                                            class="block text-xs font-medium text-gray-500 mb-1.5">Nuits minimum</label>
                                        <input type="number" name="min_nights" id="min_nights" x-model="minNights"
                                            value="{{ old('min_nights', $promotion->min_nights) }}" min="1"
                                            placeholder="Aucun minimum"
                                            class="w-full text-base border-gray-200 rounded-xl bg-gray-50/50 focus:ring-2 focus:ring-[#F16A00]/20 focus:border-[#F16A00] py-3">
                                        <p class="mt-1.5 text-xs text-gray-400">Séjour minimum requis</p>
                                    </div>
                                    <div>
                                        <label for="max_uses"
                                            class="block text-xs font-medium text-gray-500 mb-1.5">Utilisations
                                            max</label>
                                        <input type="number" name="max_uses" id="max_uses" x-model="maxUses"
                                            value="{{ old('max_uses', $promotion->max_uses) }}" min="1"
                                            placeholder="Illimité"
                                            class="w-full text-base border-gray-200 rounded-xl bg-gray-50/50 focus:ring-2 focus:ring-[#F16A00]/20 focus:border-[#F16A00] py-3">
                                        <p class="mt-1.5 text-xs text-gray-400">Laisser vide = illimité</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Section 4: Activation --}}
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-50 bg-gray-50/50">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-lg bg-green-100 flex items-center justify-center">
                                    <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor"
                                        stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M5.636 5.636a9 9 0 1 0 12.728 0M12 3v9" />
                                    </svg>
                                </div>
                                <div>
                                    <h2 class="text-base font-bold text-gray-900">Activation</h2>
                                    <p class="text-xs text-gray-500">Activez ou désactivez sans supprimer</p>
                                </div>
                            </div>
                        </div>
                        <div class="p-6">
                            <label class="flex items-center justify-between cursor-pointer group">
                                <div>
                                    <p class="text-base font-semibold group-hover:text-[#CC5A00] transition-colors"
                                        :class="isActive ? 'text-green-700' : 'text-gray-700'">
                                        <span x-text="isActive ? 'Promotion active' : 'Promotion désactivée'"></span>
                                    </p>
                                    <p class="text-sm text-gray-500 mt-0.5"
                                        x-text="isActive ? 'Les clients peuvent voir et utiliser cette promotion' : 'La promotion est invisible pour les clients'">
                                    </p>
                                </div>
                                <div class="relative ml-4">
                                    <input type="hidden" name="is_active" value="0">
                                    <input type="checkbox" name="is_active" value="1" x-model="isActive"
                                        class="sr-only peer"
                                        {{ old('is_active', $promotion->is_active) ? 'checked' : '' }}>
                                    <div
                                        class="w-12 h-7 bg-gray-200 rounded-full peer-checked:bg-green-500 peer-focus:ring-2 peer-focus:ring-green-300 transition-colors">
                                    </div>
                                    <div
                                        class="absolute left-0.75 top-0.75 w-5.5 h-5.5 bg-white rounded-full shadow-sm transition-transform peer-checked:translate-x-5">
                                    </div>
                                </div>
                            </label>
                        </div>
                    </div>

                    {{-- Section 5: Zone de danger --}}
                    <div class="bg-white rounded-2xl shadow-sm border border-red-100 overflow-hidden">
                        <div class="px-6 py-4 border-b border-red-50 bg-red-50/30">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-lg bg-red-100 flex items-center justify-center">
                                    <svg class="w-4 h-4 text-red-600" fill="none" stroke="currentColor"
                                        stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126Z" />
                                    </svg>
                                </div>
                                <div>
                                    <h2 class="text-base font-bold text-red-800">Zone de danger</h2>
                                    <p class="text-xs text-red-600/70">Actions irréversibles</p>
                                </div>
                            </div>
                        </div>
                        <div class="p-6">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-semibold text-gray-900">Supprimer cette promotion</p>
                                    <p class="text-sm text-gray-500 mt-0.5">Cette action est définitive et ne peut pas être
                                        annulée.</p>
                                </div>
                                <button type="button" @click="showDeleteConfirm = true"
                                    class="px-4 py-2.5 text-sm font-semibold text-red-600 bg-red-50 hover:bg-red-100 border border-red-200 rounded-xl transition-colors shrink-0">
                                    Supprimer
                                </button>
                            </div>

                            {{-- Delete confirmation --}}
                            <div x-show="showDeleteConfirm" x-transition
                                class="mt-4 p-4 bg-red-50 rounded-xl border border-red-200">
                                <p class="text-sm text-red-700 font-medium mb-3">
                                    Êtes-vous sûr de vouloir supprimer « {{ $promotion->title }} » ?
                                </p>
                                <div class="flex items-center gap-3">
                                    <button type="button" @click="showDeleteConfirm = false"
                                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                                        Annuler
                                    </button>
                                    <button type="button" onclick="document.getElementById('delete-form').submit()"
                                        class="px-4 py-2 text-sm font-semibold text-white bg-red-600 hover:bg-red-700 rounded-lg transition-colors">
                                        Oui, supprimer
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Submit (mobile) --}}
                    <div class="lg:hidden">
                        <div class="flex items-center gap-3">
                            <a href="{{ route('owner.marketing.promotions.index') }}"
                                class="flex-1 text-center px-5 py-3 text-sm font-semibold text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-xl transition-colors">
                                Annuler
                            </a>
                            <button type="submit"
                                class="flex-1 px-5 py-3 bg-linear-to-r from-[#F16A00] to-[#CC5A00] text-white rounded-xl font-bold text-sm shadow-lg shadow-none hover:shadow-xl hover:shadow-none active:scale-[0.98] transition-all">
                                Enregistrer
                            </button>
                        </div>
                    </div>
                </div>

                {{-- ========== RIGHT: Sticky Preview Sidebar ========== --}}
                <div class="hidden lg:block w-80 shrink-0">
                    <div class="sticky top-6 space-y-5">

                        {{-- Live preview card --}}
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                            <div class="px-5 py-3.5 border-b border-gray-100 bg-gray-50/80">
                                <h3 class="text-base font-bold text-gray-900 flex items-center gap-2">
                                    <svg class="w-4.5 h-4.5 text-[#F16A00]" fill="none" stroke="currentColor"
                                        stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                    </svg>
                                    Aperçu en direct
                                </h3>
                            </div>

                            <div class="p-5">
                                {{-- Preview badge --}}
                                <div x-show="discountValue" x-transition
                                    class="rounded-2xl p-6 text-center text-white shadow-lg mb-5"
                                    :class="isActive ?
                                        'bg-linear-to-br from-red-500 to-[#F16A00] shadow-red-500/20' :
                                        'bg-linear-to-br from-gray-400 to-gray-500 shadow-gray-500/20'">
                                    <p class="text-4xl font-extrabold leading-none drop-shadow-sm" x-text="discountLabel">
                                    </p>
                                    <p class="text-base opacity-90 mt-2 font-medium"
                                        x-text="discountType === 'percentage' ? 'de réduction' : (discountType === 'fixed' ? 'de réduction' : '')">
                                    </p>
                                    <p x-show="!isActive" class="text-xs mt-2 opacity-90 font-medium">⏸ Désactivée</p>
                                </div>

                                {{-- Preview details --}}
                                <div class="space-y-4">
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="w-8 h-8 rounded-lg bg-[#FFF4EB] flex items-center justify-center shrink-0">
                                            <svg class="w-4 h-4 text-[#F16A00]" fill="none" stroke="currentColor"
                                                stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M9.568 3H5.25A2.25 2.25 0 0 0 3 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 0 0 5.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 0 0 9.568 3Z" />
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M6 6h.008v.008H6V6Z" />
                                            </svg>
                                        </div>
                                        <div class="min-w-0">
                                            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Titre
                                            </p>
                                            <p class="text-base font-bold text-gray-900 truncate" x-text="title || '—'">
                                            </p>
                                        </div>
                                    </div>

                                    <div class="flex items-center gap-3">
                                        <div
                                            class="w-8 h-8 rounded-lg bg-purple-50 flex items-center justify-center shrink-0">
                                            <svg class="w-4 h-4 text-purple-500" fill="none" stroke="currentColor"
                                                stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
                                            </svg>
                                        </div>
                                        <div class="min-w-0">
                                            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">
                                                Résidence</p>
                                            <p class="text-sm font-bold text-gray-800 truncate">
                                                {{ $promotion->residence->name }}</p>
                                        </div>
                                    </div>

                                    <div class="flex items-center gap-3">
                                        <div
                                            class="w-8 h-8 rounded-lg bg-blue-50 flex items-center justify-center shrink-0">
                                            <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor"
                                                stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                                            </svg>
                                        </div>
                                        <div class="min-w-0">
                                            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Période
                                            </p>
                                            <p class="text-sm font-bold text-gray-800">
                                                <span
                                                    x-text="startsAt ? new Date(startsAt).toLocaleDateString('fr-FR', {day:'numeric', month:'short'}) : '—'"></span>
                                                →
                                                <span
                                                    x-text="endsAt ? new Date(endsAt).toLocaleDateString('fr-FR', {day:'numeric', month:'short'}) : '—'"></span>
                                                <span x-show="daysCount > 0"
                                                    class="text-blue-600 text-xs font-semibold ml-0.5">
                                                    (<span x-text="daysCount"></span>j)
                                                </span>
                                            </p>
                                        </div>
                                    </div>

                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-lg flex items-center justify-center shrink-0"
                                            :class="isActive ? 'bg-green-100' : 'bg-gray-100'">
                                            <svg class="w-4 h-4" :class="isActive ? 'text-green-600' : 'text-gray-400'"
                                                fill="none" stroke="currentColor" stroke-width="2"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M5.636 5.636a9 9 0 1 0 12.728 0M12 3v9" />
                                            </svg>
                                        </div>
                                        <div class="min-w-0">
                                            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Statut
                                            </p>
                                            <p class="text-sm font-bold"
                                                :class="isActive ? 'text-green-600' : 'text-gray-500'"
                                                x-text="isActive ? 'Active' : 'Désactivée'"></p>
                                        </div>
                                    </div>
                                </div>

                                {{-- Usage progress --}}
                                @if ($progressPercent !== null)
                                    <div class="mt-5 pt-4 border-t border-gray-100">
                                        <div class="flex items-center justify-between text-xs mb-2">
                                            <span class="font-medium text-gray-600">Quota d'utilisation</span>
                                            <span class="font-bold text-gray-900">{{ $promotion->uses_count }}
                                                / {{ $promotion->max_uses }}</span>
                                        </div>
                                        <div class="w-full h-2 bg-gray-100 rounded-full overflow-hidden">
                                            <div class="h-full rounded-full transition-all duration-500 {{ $progressPercent >= 80 ? 'bg-red-500' : ($progressPercent >= 50 ? 'bg-[#FF8A1F]' : 'bg-green-500') }}"
                                                style="width: {{ $progressPercent }}%"></div>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>

                        {{-- Submit (desktop) --}}
                        <div class="space-y-3">
                            <button type="submit"
                                class="w-full px-5 py-3.5 bg-linear-to-r from-[#F16A00] to-[#CC5A00] text-white rounded-xl font-bold text-sm shadow-lg shadow-none hover:shadow-xl hover:shadow-none hover:from-[#CC5A00] hover:to-[#A34700] active:scale-[0.98] transition-all flex items-center justify-center gap-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                                </svg>
                                Enregistrer les modifications
                            </button>
                            <a href="{{ route('owner.marketing.promotions.index') }}"
                                class="w-full flex items-center justify-center px-5 py-3 text-sm font-semibold text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-xl transition-colors">
                                Annuler
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </form>

        {{-- Delete form --}}
        <form id="delete-form" action="{{ route('owner.marketing.promotions.destroy', $promotion) }}" method="POST"
            class="hidden">
            @csrf
            @method('DELETE')
        </form>
    </div>
@endsection
