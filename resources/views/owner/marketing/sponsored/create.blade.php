@extends('layouts.owner')

@section('title', 'Nouvelle campagne sponsorisée')

@section('owner-content')
    <div class="max-w-3xl mx-auto space-y-6" x-data="sponsoredForm(@js(['packages' => $packages, 'residenceId' => old('residence_id', ''), 'type' => old('type', 'highlighted'), 'duration' => old('duration', '7')]))">

        {{-- ====== Header ====== --}}
        <div>
            <a href="{{ route('owner.marketing.sponsored.index') }}"
                class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-900 transition-colors mb-4">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
                </svg>
                Retour aux campagnes
            </a>
            <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-2.5">
                <span
                    class="inline-flex items-center justify-center w-9 h-9 rounded-xl bg-linear-to-br from-amber-500 to-[#e00b41] text-white shadow-sm">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z" />
                    </svg>
                </span>
                Booster une résidence
            </h1>
            <p class="text-sm text-gray-500 mt-1 ml-12">Choisissez un package pour augmenter la visibilité de votre annonce
            </p>
        </div>

        @if (session('error'))
            <div class="flex items-start gap-3 px-4 py-3 bg-red-50 border border-red-200 rounded-xl text-sm text-red-700">
                <svg class="w-5 h-5 shrink-0 text-red-500 mt-0.5" fill="none" stroke="currentColor" stroke-width="2"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                </svg>
                <span>{{ session('error') }}</span>
            </div>
        @endif

        @if ($errors->any())
            <div class="flex items-start gap-3 px-4 py-3 bg-red-50 border border-red-200 rounded-xl text-sm text-red-700">
                <svg class="w-5 h-5 shrink-0 text-red-500 mt-0.5" fill="none" stroke="currentColor" stroke-width="2"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                </svg>
                <ul class="space-y-0.5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if ($residences->isEmpty())
            <div class="flex items-start gap-3 px-4 py-3 bg-amber-50 border border-amber-200 rounded-xl text-sm text-amber-700">
                <svg class="w-5 h-5 shrink-0 text-amber-500 mt-0.5" fill="none" stroke="currentColor" stroke-width="2"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008z" />
                </svg>
                <div>
                    <p class="font-medium">Aucune résidence disponible</p>
                    <p class="text-xs mt-0.5">Vous devez avoir au moins une résidence approuvée pour créer une campagne sponsorisée.
                        <a href="{{ route('owner.residences.create') }}" class="text-[#e00b41] hover:underline font-semibold">Créer une résidence</a>
                    </p>
                </div>
            </div>
        @else
            <form action="{{ route('owner.marketing.sponsored.store') }}" method="POST" class="space-y-6">
                @csrf

                {{-- ====== Section 1 : Résidence ====== --}}
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <div class="flex items-center gap-3 mb-5">
                        <span
                            class="flex items-center justify-center w-7 h-7 rounded-full bg-gray-900 text-white text-xs font-bold">1</span>
                        <h3 class="text-base font-bold text-gray-900">Sélectionnez une résidence</h3>
                    </div>
                    <select name="residence_id" required x-model="residenceId"
                        class="w-full text-sm border-gray-200 rounded-xl bg-gray-50 focus:ring-[#ff385c] focus:border-[#ff385c] transition-colors">
                        <option value="">Choisir une résidence</option>
                        @foreach ($residences as $residence)
                            <option value="{{ $residence->id }}" {{ old('residence_id') == $residence->id ? 'selected' : '' }}>
                                {{ $residence->name }} — {{ $residence->commune }}
                            </option>
                        @endforeach
                    </select>
                @error('residence_id')
                    <p class="mt-2 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- ====== Section 2 : Packages ====== --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <div class="flex items-center gap-3 mb-5">
                    <span
                        class="flex items-center justify-center w-7 h-7 rounded-full bg-gray-900 text-white text-xs font-bold">2</span>
                    <h3 class="text-base font-bold text-gray-900">Choisissez votre package</h3>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    @php
                        $packageIcons = [
                            'featured_home' =>
                                '<svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" /></svg>',
                            'top_search' =>
                                '<svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607z" /></svg>',
                            'highlighted' =>
                                '<svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z" /></svg>',
                            'premium_listing' =>
                                '<svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.563.563 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z" /></svg>',
                        ];
                        $packageGradients = [
                            'featured_home' => 'from-purple-500 to-indigo-500',
                            'top_search' => 'from-blue-500 to-cyan-500',
                            'highlighted' => 'from-[#ff385c] to-red-500',
                            'premium_listing' => 'from-amber-500 to-yellow-500',
                        ];
                    @endphp
                    @foreach ($packages as $key => $package)
                        <label class="relative cursor-pointer group">
                            <input type="radio" name="type" value="{{ $key }}" x-model="type"
                                class="sr-only peer" {{ old('type', 'highlighted') === $key ? 'checked' : '' }}>
                            <div
                                class="border-2 rounded-2xl p-4 transition-all peer-checked:border-[#ff385c] peer-checked:bg-[#fff0f3]/50 peer-checked:shadow-sm border-gray-100 hover:border-gray-200">
                                @if ($key === 'premium_listing')
                                    <span
                                        class="absolute -top-2.5 right-3 bg-linear-to-r from-amber-500 to-yellow-500 text-white text-[10px] font-bold px-2.5 py-0.5 rounded-full shadow-sm">POPULAIRE</span>
                                @endif
                                <div class="flex items-start gap-3">
                                    <div
                                        class="w-10 h-10 rounded-xl bg-linear-to-br {{ $packageGradients[$key] ?? 'from-gray-500 to-gray-600' }} text-white flex items-center justify-center shrink-0 shadow-sm">
                                        {!! $packageIcons[$key] ?? '' !!}
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center justify-between gap-2">
                                            <h4 class="font-bold text-sm text-gray-900">{{ $package['name'] }}</h4>
                                            <span
                                                class="text-sm font-bold text-[#e00b41] whitespace-nowrap">{{ number_format($package['price'], 0, ',', ' ') }}
                                                F<span class="text-[10px] text-gray-400 font-normal">/sem</span></span>
                                        </div>
                                        <p class="text-xs text-gray-500 mt-0.5 leading-relaxed">
                                            {{ $package['description'] }}</p>
                                        <ul class="mt-2.5 space-y-1">
                                            @foreach ($package['features'] as $feature)
                                                <li class="flex items-center gap-1.5 text-xs text-gray-600">
                                                    <svg class="w-3.5 h-3.5 text-green-500 shrink-0" fill="none"
                                                        stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            d="M4.5 12.75l6 6 9-13.5" />
                                                    </svg>
                                                    {{ $feature }}
                                                </li>
                                            @endforeach
                                        </ul>
                                        @if ($package['billing_type'] === 'per_click')
                                            <div
                                                class="mt-2 inline-flex items-center gap-1 px-2 py-0.5 bg-blue-50 rounded-md">
                                                <span class="text-[10px] font-semibold text-blue-600">💡
                                                    {{ number_format($package['cost_per_unit'], 0, ',', ' ') }}
                                                    FCFA/clic</span>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </label>
                    @endforeach
                </div>
            </div>

            {{-- ====== Section 3 : Durée ====== --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <div class="flex items-center gap-3 mb-5">
                    <span
                        class="flex items-center justify-center w-7 h-7 rounded-full bg-gray-900 text-white text-xs font-bold">3</span>
                    <h3 class="text-base font-bold text-gray-900">Durée de la campagne</h3>
                </div>
                <div class="grid grid-cols-3 gap-3">
                    @foreach ([['val' => '7', 'discount' => null], ['val' => '14', 'discount' => '-10%'], ['val' => '30', 'discount' => '-20%']] as $opt)
                        <label class="relative cursor-pointer">
                            <input type="radio" name="duration" value="{{ $opt['val'] }}" x-model="duration"
                                class="sr-only peer" {{ old('duration', '7') == $opt['val'] ? 'checked' : '' }}>
                            <div
                                class="border-2 rounded-2xl p-4 text-center transition-all peer-checked:border-[#ff385c] peer-checked:bg-[#fff0f3]/50 peer-checked:shadow-sm border-gray-100 hover:border-gray-200">
                                <p class="text-2xl font-bold text-gray-900">{{ $opt['val'] }}</p>
                                <p class="text-xs text-gray-500 mt-0.5">jours</p>
                                @if ($opt['discount'])
                                    <span
                                        class="mt-1.5 inline-block text-[10px] font-bold text-green-600 bg-green-50 px-2 py-0.5 rounded-full">{{ $opt['discount'] }}</span>
                                @endif
                            </div>
                        </label>
                    @endforeach
                </div>
            </div>

            {{-- ====== Section 4 : Budget ====== --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <div class="flex items-center gap-3 mb-5">
                    <span
                        class="flex items-center justify-center w-7 h-7 rounded-full bg-gray-900 text-white text-xs font-bold">4</span>
                    <h3 class="text-base font-bold text-gray-900">Budget <span
                            class="text-xs font-normal text-gray-400">(optionnel)</span></h3>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="daily_budget" class="block text-xs font-semibold text-gray-600 mb-1.5">Budget journalier
                            max</label>
                        <div class="relative">
                            <input type="number" name="daily_budget" id="daily_budget"
                                value="{{ old('daily_budget') }}" min="500" step="500"
                                placeholder="Pas de limite"
                                class="w-full text-sm border-gray-200 rounded-xl bg-gray-50 focus:ring-[#ff385c] focus:border-[#ff385c] pr-16 transition-colors">
                            <span
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-gray-400 font-medium">FCFA</span>
                        </div>
                    </div>
                    <div>
                        <label for="total_budget" class="block text-xs font-semibold text-gray-600 mb-1.5">Budget total
                            max</label>
                        <div class="relative">
                            <input type="number" name="total_budget" id="total_budget"
                                value="{{ old('total_budget') }}" min="5000" step="1000"
                                placeholder="Pas de limite"
                                class="w-full text-sm border-gray-200 rounded-xl bg-gray-50 focus:ring-[#ff385c] focus:border-[#ff385c] pr-16 transition-colors">
                            <span
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-gray-400 font-medium">FCFA</span>
                        </div>
                    </div>
                </div>
                <p class="mt-3 text-xs text-gray-400 flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0zm-9-3.75h.008v.008H12V8.25z" />
                    </svg>
                    La campagne s'arrêtera automatiquement une fois le budget atteint.
                </p>
            </div>

            {{-- ====== Résumé ====== --}}
            <div class="bg-gray-50 rounded-2xl border border-gray-200 p-6">
                <h3 class="text-base font-bold text-gray-900 mb-4 flex items-center gap-2">
                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" stroke-width="2"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25z" />
                    </svg>
                    Résumé de la commande
                </h3>
                <div class="space-y-2.5 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-500">Package</span>
                        <span class="font-semibold text-gray-900" x-text="getPackageName()">-</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Durée</span>
                        <span class="font-semibold text-gray-900"><span x-text="duration">7</span> jours</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Prix de base</span>
                        <span class="font-semibold text-gray-900" x-text="formatPrice(getBasePrice()) + ' FCFA'">-</span>
                    </div>
                    <template x-if="getDiscount() > 0">
                        <div class="flex justify-between text-green-600">
                            <span>Réduction</span>
                            <span class="font-semibold">−<span x-text="getDiscount()">0</span>%</span>
                        </div>
                    </template>
                    <div class="border-t border-gray-200 pt-3 mt-3">
                        <div class="flex justify-between items-center">
                            <span class="text-base font-bold text-gray-900">Total</span>
                            <span class="text-xl font-bold text-[#e00b41]"
                                x-text="formatPrice(getTotalPrice()) + ' FCFA'">-</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ====== Actions ====== --}}
            <div class="flex items-center justify-end gap-3 pt-2">
                <a href="{{ route('owner.marketing.sponsored.index') }}"
                    class="px-5 py-2.5 text-sm font-medium text-gray-600 hover:text-gray-900 transition-colors">
                    Annuler
                </a>
                <button type="submit"
                    class="inline-flex items-center gap-2 bg-gray-900 text-white px-6 py-2.5 rounded-xl font-semibold text-sm shadow-sm hover:bg-gray-800 hover:shadow-md active:scale-95 transition-all disabled:opacity-40 disabled:cursor-not-allowed"
                    :disabled="!residenceId || !type">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z" />
                    </svg>
                    Passer au paiement
                </button>
            </div>
        </form>
        @endif
    </div>
@endsection
