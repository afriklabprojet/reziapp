@extends('layouts.owner')

@section('title', 'Modifier — ' . $coupon->code)

@section('owner-content')
    <div x-data="couponEdit()" class="min-h-screen">

        {{-- Header --}}
        <div class="mb-8">
            <a href="{{ route('owner.marketing.coupons.show', $coupon) }}"
                class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-900 transition mb-4 group">
                <svg class="w-4 h-4 group-hover:-translate-x-0.5 transition-transform" fill="none" stroke="currentColor"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
                Retour aux détails
            </a>
            <div class="flex items-center gap-3">
                <div class="p-2.5 bg-[#FFE7D1] rounded-xl">
                    <svg class="w-6 h-6 text-[#CC5A00]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Modifier le code promo</h1>
                    <p class="text-sm text-gray-500 mt-0.5">
                        Code <span class="font-mono font-semibold text-gray-700">{{ $coupon->code }}</span>
                        — Créé {{ $coupon->created_at->diffForHumans() }}
                    </p>
                </div>
            </div>
        </div>

        {{-- Validation errors --}}
        @if ($errors->any())
            <div class="mb-6 bg-red-50 border border-red-200 rounded-2xl p-4">
                <div class="flex items-center gap-2 mb-2">
                    <svg class="w-5 h-5 text-red-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <p class="text-sm font-semibold text-red-700">Veuillez corriger les erreurs suivantes :</p>
                </div>
                <ul class="list-disc list-inside space-y-1">
                    @foreach ($errors->all() as $error)
                        <li class="text-sm text-red-600">{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Main grid: Form + Preview --}}
        <form action="{{ route('owner.marketing.coupons.update', $coupon) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 lg:grid-cols-5 gap-6">

                {{-- LEFT: Form (3 cols) --}}
                <div class="lg:col-span-3 space-y-6">

                    {{-- Section 1: Code promo --}}
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                        <div class="flex items-center gap-2 mb-5">
                            <div
                                class="w-7 h-7 bg-gray-900 text-white rounded-lg flex items-center justify-center text-xs font-bold">
                                1</div>
                            <h3 class="font-semibold text-gray-900">Code promo</h3>
                        </div>

                        <div class="space-y-4">
                            <div>
                                <label for="code" class="block text-sm font-medium text-gray-700 mb-1.5">Code <span
                                        class="text-red-400">*</span></label>
                                <div class="flex gap-2">
                                    <div class="relative flex-1">
                                        <input type="text" name="code" id="code" x-model="code" required
                                            maxlength="50"
                                            class="w-full border-gray-200 rounded-xl focus:ring-[#F16A00] focus:border-[#F16A00] uppercase tracking-wider font-mono text-lg pl-4 pr-10 py-3 bg-gray-50 focus:bg-white transition">
                                        <div x-show="code.length > 0" x-transition
                                            class="absolute right-3 top-1/2 -translate-y-1/2">
                                            <button type="button" @click="code = ''"
                                                class="text-gray-400 hover:text-gray-600">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M6 18L18 6M6 6l12 12" />
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                    <button type="button" @click="generateCode()"
                                        class="px-4 py-3 bg-gray-900 text-white rounded-xl hover:bg-gray-800 transition-all text-sm font-medium flex items-center gap-2 shrink-0">
                                        <svg class="w-4 h-4" :class="{ 'animate-spin': generating }" fill="none"
                                            stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                        </svg>
                                        Générer
                                    </button>
                                </div>
                                @error('code')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="description" class="block text-sm font-medium text-gray-700 mb-1.5">
                                    Description <span class="text-gray-400 font-normal">(interne)</span>
                                </label>
                                <textarea name="description" id="description" x-model="description" rows="2" maxlength="500"
                                    placeholder="Ex: Code pour la campagne d'été 2025..."
                                    class="w-full border-gray-200 rounded-xl focus:ring-[#F16A00] focus:border-[#F16A00] py-3 bg-gray-50 focus:bg-white transition resize-none">{{ old('description', $coupon->description) }}</textarea>
                                <div class="flex justify-between mt-1">
                                    <p class="text-xs text-gray-400">Visible uniquement par vous</p>
                                    <p class="text-xs text-gray-400" x-text="description.length + '/500'"></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Section 2: Réduction --}}
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                        <div class="flex items-center gap-2 mb-5">
                            <div
                                class="w-7 h-7 bg-gray-900 text-white rounded-lg flex items-center justify-center text-xs font-bold">
                                2</div>
                            <h3 class="font-semibold text-gray-900">Réduction</h3>
                        </div>

                        <div class="space-y-5">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Type de réduction <span
                                        class="text-red-400">*</span></label>
                                <div class="grid grid-cols-2 gap-3">
                                    <label
                                        class="relative flex items-center gap-3 p-4 rounded-xl border-2 cursor-pointer transition-all"
                                        :class="discountType === 'percentage' ?
                                            'border-[#F16A00] bg-[#FFF4EB] ring-1 ring-[#F16A00]/20' :
                                            'border-gray-200 bg-white hover:border-gray-300'">
                                        <input type="radio" name="discount_type" value="percentage"
                                            x-model="discountType" class="sr-only">
                                        <div class="shrink-0 w-10 h-10 rounded-lg flex items-center justify-center text-lg transition-colors"
                                            :class="discountType === 'percentage' ? 'bg-[#FFE7D1]' : 'bg-gray-100'">%
                                        </div>
                                        <div>
                                            <p class="font-semibold text-sm text-gray-900">Pourcentage</p>
                                            <p class="text-xs text-gray-500">Réduction en %</p>
                                        </div>
                                        <div x-show="discountType === 'percentage'"
                                            class="absolute top-2 right-2 text-[#F16A00]">
                                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd"
                                                    d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                                    clip-rule="evenodd" />
                                            </svg>
                                        </div>
                                    </label>
                                    <label
                                        class="relative flex items-center gap-3 p-4 rounded-xl border-2 cursor-pointer transition-all"
                                        :class="discountType === 'fixed' ?
                                            'border-[#F16A00] bg-[#FFF4EB] ring-1 ring-[#F16A00]/20' :
                                            'border-gray-200 bg-white hover:border-gray-300'">
                                        <input type="radio" name="discount_type" value="fixed" x-model="discountType"
                                            class="sr-only">
                                        <div class="shrink-0 w-10 h-10 rounded-lg flex items-center justify-center text-lg transition-colors"
                                            :class="discountType === 'fixed' ? 'bg-[#FFE7D1]' : 'bg-gray-100'">F</div>
                                        <div>
                                            <p class="font-semibold text-sm text-gray-900">Montant fixe</p>
                                            <p class="text-xs text-gray-500">Réduction en FCFA</p>
                                        </div>
                                        <div x-show="discountType === 'fixed'"
                                            class="absolute top-2 right-2 text-[#F16A00]">
                                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd"
                                                    d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                                    clip-rule="evenodd" />
                                            </svg>
                                        </div>
                                    </label>
                                </div>
                            </div>

                            <div>
                                <label for="discount_value" class="block text-sm font-medium text-gray-700 mb-1.5">
                                    Valeur de la réduction <span class="text-red-400">*</span>
                                </label>
                                <div class="relative">
                                    <input type="number" name="discount_value" id="discount_value"
                                        x-model="discountValue" required min="1"
                                        :max="discountType === 'percentage' ? 90 : 999999" step="1"
                                        :placeholder="discountType === 'percentage' ? 'Ex: 15' : 'Ex: 5000'"
                                        class="w-full border-gray-200 rounded-xl focus:ring-[#F16A00] focus:border-[#F16A00] py-3 bg-gray-50 focus:bg-white transition pl-4 pr-16 text-lg font-semibold">
                                    <div
                                        class="absolute right-3 top-1/2 -translate-y-1/2 text-sm font-medium text-gray-400">
                                        <span x-text="discountType === 'percentage' ? '%' : 'FCFA'"></span>
                                    </div>
                                </div>
                                @error('discount_value')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                                <div x-show="discountType === 'percentage' && discountValue > 50 && discountValue <= 90"
                                    x-transition
                                    class="mt-2 flex items-center gap-2 text-amber-600 bg-amber-50 rounded-lg p-2.5">
                                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" />
                                    </svg>
                                    <p class="text-xs font-medium">Réduction élevée : cela impactera vos revenus.</p>
                                </div>
                            </div>

                            <div x-show="discountType === 'percentage'" x-transition.opacity>
                                <label for="max_discount" class="block text-sm font-medium text-gray-700 mb-1.5">
                                    Plafond de la réduction <span class="text-gray-400 font-normal">(optionnel)</span>
                                </label>
                                <div class="relative">
                                    <input type="number" name="max_discount" id="max_discount" x-model="maxDiscount"
                                        min="0" placeholder="Pas de limite"
                                        class="w-full border-gray-200 rounded-xl focus:ring-[#F16A00] focus:border-[#F16A00] py-3 bg-gray-50 focus:bg-white transition pl-4 pr-16">
                                    <div
                                        class="absolute right-3 top-1/2 -translate-y-1/2 text-sm font-medium text-gray-400">
                                        FCFA</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Section 3: Conditions & Limites --}}
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                        <div class="flex items-center gap-2 mb-5">
                            <div
                                class="w-7 h-7 bg-gray-900 text-white rounded-lg flex items-center justify-center text-xs font-bold">
                                3</div>
                            <h3 class="font-semibold text-gray-900">Conditions & Limites</h3>
                        </div>

                        <div class="space-y-5">
                            <div>
                                <label for="min_amount" class="block text-sm font-medium text-gray-700 mb-1.5">
                                    Montant minimum de réservation <span
                                        class="text-gray-400 font-normal">(optionnel)</span>
                                </label>
                                <div class="relative">
                                    <input type="number" name="min_amount" id="min_amount" x-model="minAmount"
                                        min="0" placeholder="Aucun minimum"
                                        class="w-full border-gray-200 rounded-xl focus:ring-[#F16A00] focus:border-[#F16A00] py-3 bg-gray-50 focus:bg-white transition pl-4 pr-16">
                                    <div
                                        class="absolute right-3 top-1/2 -translate-y-1/2 text-sm font-medium text-gray-400">
                                        FCFA</div>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label for="max_uses"
                                        class="block text-sm font-medium text-gray-700 mb-1.5">Utilisations totales</label>
                                    <input type="number" name="max_uses" id="max_uses" x-model="maxUses"
                                        min="1" placeholder="♾️ Illimité"
                                        class="w-full border-gray-200 rounded-xl focus:ring-[#F16A00] focus:border-[#F16A00] py-3 bg-gray-50 focus:bg-white transition">
                                </div>
                                <div>
                                    <label for="max_uses_per_user"
                                        class="block text-sm font-medium text-gray-700 mb-1.5">Par client</label>
                                    <input type="number" name="max_uses_per_user" id="max_uses_per_user"
                                        x-model="maxUsesPerUser" min="1" placeholder="1 par défaut"
                                        class="w-full border-gray-200 rounded-xl focus:ring-[#F16A00] focus:border-[#F16A00] py-3 bg-gray-50 focus:bg-white transition">
                                </div>
                            </div>

                            @if ($coupon->max_uses)
                                <div class="bg-blue-50 rounded-xl p-3 flex items-center gap-2">
                                    <svg class="w-4 h-4 text-blue-500 shrink-0" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <p class="text-xs text-blue-700">
                                        Déjà utilisé <strong>{{ $coupon->uses_count }}</strong> fois sur
                                        {{ $coupon->max_uses }} max.
                                    </p>
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- Section 4: Planification --}}
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                        <div class="flex items-center gap-2 mb-5">
                            <div
                                class="w-7 h-7 bg-gray-900 text-white rounded-lg flex items-center justify-center text-xs font-bold">
                                4</div>
                            <h3 class="font-semibold text-gray-900">Planification</h3>
                        </div>

                        <div class="space-y-4">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label for="starts_at"
                                        class="block text-sm font-medium text-gray-700 mb-1.5">Début</label>
                                    <input type="date" name="starts_at" id="starts_at" x-model="startsAt"
                                        class="w-full border-gray-200 rounded-xl focus:ring-[#F16A00] focus:border-[#F16A00] py-3 bg-gray-50 focus:bg-white transition">
                                    <p class="mt-1 text-xs text-gray-400">Vide = immédiat</p>
                                </div>
                                <div>
                                    <label for="expires_at"
                                        class="block text-sm font-medium text-gray-700 mb-1.5">Expiration</label>
                                    <input type="date" name="expires_at" id="expires_at" x-model="expiresAt"
                                        class="w-full border-gray-200 rounded-xl focus:ring-[#F16A00] focus:border-[#F16A00] py-3 bg-gray-50 focus:bg-white transition">
                                    <p class="mt-1 text-xs text-gray-400">Vide = jamais</p>
                                </div>
                            </div>

                            <div x-show="startsAt || expiresAt" x-transition
                                class="flex items-center gap-2 p-3 bg-blue-50 rounded-xl text-sm text-blue-700">
                                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                <span>
                                    <template x-if="startsAt && expiresAt">
                                        <span>Du <strong x-text="formatDate(startsAt)"></strong> au <strong
                                                x-text="formatDate(expiresAt)"></strong></span>
                                    </template>
                                    <template x-if="startsAt && !expiresAt">
                                        <span>À partir du <strong x-text="formatDate(startsAt)"></strong></span>
                                    </template>
                                    <template x-if="!startsAt && expiresAt">
                                        <span>Jusqu'au <strong x-text="formatDate(expiresAt)"></strong></span>
                                    </template>
                                </span>
                            </div>

                            {{-- Active toggle --}}
                            <div
                                class="flex items-center justify-between p-4 bg-gray-50 rounded-xl border border-gray-200">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-lg flex items-center justify-center"
                                        :class="isActive ? 'bg-green-100' : 'bg-gray-200'">
                                        <svg class="w-4 h-4" :class="isActive ? 'text-green-600' : 'text-gray-400'"
                                            fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M13 10V3L4 14h7v7l9-11h-7z" />
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="text-sm font-semibold text-gray-900">Code promo actif</p>
                                        <p class="text-xs text-gray-500"
                                            x-text="isActive ? 'Les clients peuvent utiliser ce code' : 'Ce code est désactivé'">
                                        </p>
                                    </div>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" name="is_active" value="1" x-model="isActive"
                                        class="sr-only peer">
                                    <div
                                        class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-0.5 after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-green-500">
                                    </div>
                                </label>
                            </div>
                        </div>
                    </div>

                    {{-- Submit (mobile) --}}
                    <div class="lg:hidden">
                        <div class="flex items-center gap-3">
                            <a href="{{ route('owner.marketing.coupons.show', $coupon) }}"
                                class="flex-1 text-center px-4 py-3 text-gray-600 hover:text-gray-900 border border-gray-200 rounded-xl font-medium transition">
                                Annuler
                            </a>
                            <button type="submit"
                                class="flex-1 px-6 py-3 bg-gray-900 text-white rounded-xl font-semibold hover:bg-gray-800 transition-all shadow-sm flex items-center justify-center gap-2"
                                :disabled="!discountValue || !code">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M5 13l4 4L19 7" />
                                </svg>
                                Enregistrer
                            </button>
                        </div>
                    </div>
                </div>

                {{-- RIGHT: Preview (2 cols) --}}
                <div class="lg:col-span-2">
                    <div class="lg:sticky lg:top-24 space-y-5">

                        {{-- Live preview card --}}
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                            <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                                <h3 class="font-semibold text-gray-900 text-sm">Aperçu en direct</h3>
                                <span class="relative flex h-2.5 w-2.5">
                                    <span
                                        class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                                    <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-green-500"></span>
                                </span>
                            </div>

                            <div class="p-5">
                                <div class="relative rounded-2xl p-5 text-white overflow-hidden"
                                    :class="isActive ? 'bg-linear-to-br from-[#F16A00] to-[#CC5A00]' :
                                        'bg-linear-to-br from-gray-300 to-gray-400'">
                                    <div class="absolute -left-3 top-1/2 -translate-y-1/2 w-6 h-6 bg-white rounded-full">
                                    </div>
                                    <div class="absolute -right-3 top-1/2 -translate-y-1/2 w-6 h-6 bg-white rounded-full">
                                    </div>
                                    <div class="absolute top-0 right-0 w-32 h-32 opacity-10">
                                        <svg viewBox="0 0 100 100" fill="currentColor">
                                            <circle cx="80" cy="20" r="40" />
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
                                                <span class="text-xs font-medium opacity-80 uppercase tracking-wider">ReziApp
                                                    Promo</span>
                                            </div>
                                        </div>
                                        <div
                                            class="font-mono text-2xl font-bold tracking-[0.15em] mb-3 border-b border-white/20 pb-3">
                                            <span x-text="code.toUpperCase() || 'CODE'"></span>
                                        </div>
                                        <div class="flex items-end justify-between">
                                            <div>
                                                <p class="text-white/70 text-xs mb-0.5">Réduction</p>
                                                <p class="text-lg font-bold" x-text="previewDiscountLabel"></p>
                                            </div>
                                            <div class="text-right">
                                                <template x-if="minAmount > 0">
                                                    <p class="text-[10px] text-white/60">Min. <span
                                                            x-text="Number(minAmount).toLocaleString('fr-FR')"></span> F
                                                    </p>
                                                </template>
                                                <template x-if="expiresAt">
                                                    <p class="text-[10px] text-white/60">Exp. <span
                                                            x-text="formatDate(expiresAt)"></span></p>
                                                </template>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Stats info --}}
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                            <h3 class="font-semibold text-gray-900 text-sm mb-4">Statistiques actuelles</h3>
                            <div class="grid grid-cols-2 gap-4">
                                <div class="text-center p-3 bg-gray-50 rounded-xl">
                                    <p class="text-2xl font-bold text-gray-900">{{ $coupon->uses_count }}</p>
                                    <p class="text-xs text-gray-500">Utilisations</p>
                                </div>
                                <div class="text-center p-3 bg-gray-50 rounded-xl">
                                    <p class="text-2xl font-bold text-gray-900">
                                        {{ $coupon->created_at->diffInDays(now()) }}</p>
                                    <p class="text-xs text-gray-500">Jours actif</p>
                                </div>
                            </div>
                        </div>

                        {{-- Summary --}}
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                            <h3 class="font-semibold text-gray-900 text-sm mb-4">Récapitulatif</h3>
                            <div class="space-y-3">
                                <div class="flex items-center justify-between py-2 border-b border-gray-50">
                                    <span class="text-sm text-gray-500">Code</span>
                                    <span class="text-sm font-mono font-semibold text-gray-900"
                                        x-text="code.toUpperCase() || '—'"></span>
                                </div>
                                <div class="flex items-center justify-between py-2 border-b border-gray-50">
                                    <span class="text-sm text-gray-500">Réduction</span>
                                    <span class="text-sm font-semibold text-[#CC5A00]"
                                        x-text="previewDiscountLabel || '—'"></span>
                                </div>
                                <div class="flex items-center justify-between py-2 border-b border-gray-50">
                                    <span class="text-sm text-gray-500">Limites</span>
                                    <span class="text-sm font-medium text-gray-900"
                                        x-text="maxUses ? maxUses + ' max' : 'Illimitées'"></span>
                                </div>
                                <div class="flex items-center justify-between py-2">
                                    <span class="text-sm text-gray-500">Statut</span>
                                    <span class="text-sm font-medium"
                                        :class="isActive ? 'text-green-600' : 'text-gray-500'"
                                        x-text="isActive ? 'Actif' : 'Désactivé'"></span>
                                </div>
                            </div>
                        </div>

                        {{-- Actions (desktop) --}}
                        <div class="hidden lg:block space-y-3">
                            <button type="submit"
                                class="w-full px-6 py-3.5 bg-gray-900 text-white rounded-xl font-semibold hover:bg-gray-800 transition-all shadow-sm hover:shadow-md flex items-center justify-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed"
                                :disabled="!discountValue || !code">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M5 13l4 4L19 7" />
                                </svg>
                                Enregistrer les modifications
                            </button>
                            <a href="{{ route('owner.marketing.coupons.show', $coupon) }}"
                                class="w-full inline-flex items-center justify-center px-4 py-3 text-gray-500 hover:text-gray-700 rounded-xl border border-gray-200 hover:bg-gray-50 font-medium transition text-sm">
                                Annuler
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <script>
        function couponEdit() {
            return {
                code: '{{ old('code', $coupon->code) }}',
                description: '{{ old('description', addslashes($coupon->description ?? '')) }}',
                discountType: '{{ old('discount_type', $coupon->discount_type) }}',
                discountValue: '{{ old('discount_value', intval($coupon->discount_value)) }}',
                maxDiscount: '{{ old('max_discount', $coupon->max_discount ? intval($coupon->max_discount) : '') }}',
                minAmount: '{{ old('min_amount', $coupon->min_amount ? intval($coupon->min_amount) : '') }}',
                maxUses: '{{ old('max_uses', $coupon->max_uses ?? '') }}',
                maxUsesPerUser: '{{ old('max_uses_per_user', $coupon->max_uses_per_user ?? '') }}',
                startsAt: '{{ old('starts_at', $coupon->starts_at?->format('Y-m-d') ?? '') }}',
                expiresAt: '{{ old('expires_at', $coupon->expires_at?->format('Y-m-d') ?? '') }}',
                isActive: {{ old('is_active', $coupon->is_active) ? 'true' : 'false' }},
                generating: false,

                generateCode() {
                    this.generating = true;
                    const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
                    let code = 'ReziApp';
                    for (let i = 0; i < 6; i++) {
                        code += chars.charAt(Math.floor(Math.random() * chars.length));
                    }
                    this.code = code;
                    setTimeout(() => this.generating = false, 500);
                },

                get previewDiscountLabel() {
                    if (!this.discountValue) return '—';
                    if (this.discountType === 'percentage') {
                        let label = '-' + this.discountValue + '%';
                        if (this.maxDiscount) {
                            label += ' (max ' + Number(this.maxDiscount).toLocaleString('fr-FR') + ' F)';
                        }
                        return label;
                    }
                    return '-' + Number(this.discountValue).toLocaleString('fr-FR') + ' FCFA';
                },

                formatDate(dateStr) {
                    if (!dateStr) return '';
                    const d = new Date(dateStr);
                    return d.toLocaleDateString('fr-FR', {
                        day: 'numeric',
                        month: 'short',
                        year: 'numeric'
                    });
                }
            }
        }
    </script>
@endsection
