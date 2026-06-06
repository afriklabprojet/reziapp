@extends('layouts.owner')

@section('title', 'Paiement - Mise en avant')

@section('owner-content')
    <div class="max-w-3xl mx-auto space-y-6">

        {{-- ====== Header ====== --}}
        <div>
            <a href="{{ route('owner.marketing.sponsored.show', $sponsored) }}"
                class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-900 transition-colors mb-4">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
                </svg>
                Retour à la campagne
            </a>
            <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-2.5">
                <span
                    class="inline-flex items-center justify-center w-9 h-9 rounded-xl bg-linear-to-br from-green-500 to-emerald-600 text-white shadow-sm">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z" />
                    </svg>
                </span>
                Finaliser le paiement
            </h1>
            <p class="text-sm text-gray-500 mt-1 ml-12">Choisissez votre moyen de paiement mobile</p>
        </div>

        {{-- Error / Success / Info alerts --}}
        @if (session('error'))
            <div class="flex items-start gap-3 p-4 bg-red-50 rounded-2xl border border-red-200">
                <svg class="w-5 h-5 text-red-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                </svg>
                <div>
                    <p class="text-sm font-semibold text-red-700">Erreur de paiement</p>
                    <p class="text-xs text-red-600 mt-0.5">{{ session('error') }}</p>
                </div>
            </div>
        @endif

        @if (session('info'))
            <div class="flex items-start gap-3 p-4 bg-blue-50 rounded-2xl border border-blue-200">
                <svg class="w-5 h-5 text-blue-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0zm-9-3.75h.008v.008H12V8.25z" />
                </svg>
                <p class="text-sm text-blue-700">{{ session('info') }}</p>
            </div>
        @endif

        @if (!$jekoEnabled)
            <div class="flex items-start gap-3 p-4 bg-amber-50 rounded-2xl border border-amber-200">
                <svg class="w-5 h-5 text-amber-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                </svg>
                <div>
                    <p class="text-sm font-semibold text-amber-700">Paiement temporairement indisponible</p>
                    <p class="text-xs text-amber-600 mt-0.5">Le service de paiement mobile est momentanément désactivé. Veuillez réessayer plus tard ou contacter le support.</p>
                </div>
            </div>
        @else
            <div class="grid grid-cols-1 lg:grid-cols-5 gap-6">

                {{-- ====== Payment Form (3 cols) ====== --}}
                <div class="lg:col-span-3">
                    <form action="{{ route('owner.marketing.sponsored.payment.confirm', $sponsored) }}" method="POST"
                        x-data="{ method: 'wave', submitting: false }" @submit="submitting = true" class="space-y-6">
                        @csrf

                        {{-- Payment Methods --}}
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                            <h3 class="text-base font-bold text-gray-900 mb-5 flex items-center gap-2">
                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" stroke-width="2"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M10.5 1.5H8.25A2.25 2.25 0 006 3.75v16.5a2.25 2.25 0 002.25 2.25h7.5A2.25 2.25 0 0018 20.25V3.75a2.25 2.25 0 00-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 18.75h3" />
                            </svg>
                            Paiement Mobile Money
                        </h3>
                        <div class="space-y-3">

                            {{-- Wave --}}
                            <label class="block cursor-pointer">
                                <input type="radio" name="payment_method" value="wave" x-model="method"
                                    class="sr-only peer">
                                <div
                                    class="border-2 rounded-2xl p-4 transition-all peer-checked:border-blue-500 peer-checked:bg-blue-50/50 peer-checked:shadow-sm border-gray-100 hover:border-gray-200">
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="w-11 h-11 bg-blue-50 rounded-xl flex items-center justify-center shrink-0">
                                            <span class="text-lg font-black text-blue-600">W</span>
                                        </div>
                                        <div>
                                            <p class="font-semibold text-sm text-gray-900">Wave</p>
                                            <p class="text-xs text-gray-500">Paiement via Wave Mobile Money</p>
                                        </div>
                                        <div class="ml-auto">
                                            <div class="w-5 h-5 rounded-full border-2 flex items-center justify-center transition-colors"
                                                :class="method === 'wave' ? 'border-blue-500 bg-blue-500' :
                                                    'border-gray-300'">
                                                <svg x-show="method === 'wave'" class="w-3 h-3 text-white" fill="none"
                                                    stroke="currentColor" stroke-width="3" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M4.5 12.75l6 6 9-13.5" />
                                                </svg>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </label>

                            {{-- Orange Money --}}
                            <label class="block cursor-pointer">
                                <input type="radio" name="payment_method" value="orange" x-model="method"
                                    class="sr-only peer">
                                <div
                                    class="border-2 rounded-2xl p-4 transition-all peer-checked:border-[#F16A00] peer-checked:bg-[#FFF4EB]/50 peer-checked:shadow-sm border-gray-100 hover:border-gray-200">
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="w-11 h-11 bg-[#FFF4EB] rounded-xl flex items-center justify-center shrink-0">
                                            <span class="text-lg font-black text-[#CC5A00]">OM</span>
                                        </div>
                                        <div>
                                            <p class="font-semibold text-sm text-gray-900">Orange Money</p>
                                            <p class="text-xs text-gray-500">Paiement via Orange Money</p>
                                        </div>
                                        <div class="ml-auto">
                                            <div class="w-5 h-5 rounded-full border-2 flex items-center justify-center transition-colors"
                                                :class="method === 'orange' ? 'border-[#F16A00] bg-[#F16A00]' :
                                                    'border-gray-300'">
                                                <svg x-show="method === 'orange'" class="w-3 h-3 text-white" fill="none"
                                                    stroke="currentColor" stroke-width="3" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M4.5 12.75l6 6 9-13.5" />
                                                </svg>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </label>

                            {{-- MTN MoMo --}}
                            <label class="block cursor-pointer">
                                <input type="radio" name="payment_method" value="mtn" x-model="method"
                                    class="sr-only peer">
                                <div
                                    class="border-2 rounded-2xl p-4 transition-all peer-checked:border-yellow-500 peer-checked:bg-yellow-50/50 peer-checked:shadow-sm border-gray-100 hover:border-gray-200">
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="w-11 h-11 bg-yellow-50 rounded-xl flex items-center justify-center shrink-0">
                                            <span class="text-lg font-black text-yellow-600">MTN</span>
                                        </div>
                                        <div>
                                            <p class="font-semibold text-sm text-gray-900">MTN MoMo</p>
                                            <p class="text-xs text-gray-500">Paiement via MTN Mobile Money</p>
                                        </div>
                                        <div class="ml-auto">
                                            <div class="w-5 h-5 rounded-full border-2 flex items-center justify-center transition-colors"
                                                :class="method === 'mtn' ? 'border-yellow-500 bg-yellow-500' :
                                                    'border-gray-300'">
                                                <svg x-show="method === 'mtn'" class="w-3 h-3 text-white" fill="none"
                                                    stroke="currentColor" stroke-width="3" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M4.5 12.75l6 6 9-13.5" />
                                                </svg>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </label>

                            {{-- Moov Money --}}
                            <label class="block cursor-pointer">
                                <input type="radio" name="payment_method" value="moov" x-model="method"
                                    class="sr-only peer">
                                <div
                                    class="border-2 rounded-2xl p-4 transition-all peer-checked:border-green-500 peer-checked:bg-green-50/50 peer-checked:shadow-sm border-gray-100 hover:border-gray-200">
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="w-11 h-11 bg-green-50 rounded-xl flex items-center justify-center shrink-0">
                                            <span class="text-lg font-black text-green-600">M</span>
                                        </div>
                                        <div>
                                            <p class="font-semibold text-sm text-gray-900">Moov Money</p>
                                            <p class="text-xs text-gray-500">Paiement via Moov Money</p>
                                        </div>
                                        <div class="ml-auto">
                                            <div class="w-5 h-5 rounded-full border-2 flex items-center justify-center transition-colors"
                                                :class="method === 'moov' ? 'border-green-500 bg-green-500' :
                                                    'border-gray-300'">
                                                <svg x-show="method === 'moov'" class="w-3 h-3 text-white" fill="none"
                                                    stroke="currentColor" stroke-width="3" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M4.5 12.75l6 6 9-13.5" />
                                                </svg>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </label>

                            {{-- Djamo --}}
                            <label class="block cursor-pointer">
                                <input type="radio" name="payment_method" value="djamo" x-model="method"
                                    class="sr-only peer">
                                <div
                                    class="border-2 rounded-2xl p-4 transition-all peer-checked:border-purple-500 peer-checked:bg-purple-50/50 peer-checked:shadow-sm border-gray-100 hover:border-gray-200">
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="w-11 h-11 bg-purple-50 rounded-xl flex items-center justify-center shrink-0">
                                            <span class="text-lg font-black text-purple-600">D</span>
                                        </div>
                                        <div>
                                            <p class="font-semibold text-sm text-gray-900">Djamo</p>
                                            <p class="text-xs text-gray-500">Paiement via carte Djamo</p>
                                        </div>
                                        <div class="ml-auto">
                                            <div class="w-5 h-5 rounded-full border-2 flex items-center justify-center transition-colors"
                                                :class="method === 'djamo' ? 'border-purple-500 bg-purple-500' :
                                                    'border-gray-300'">
                                                <svg x-show="method === 'djamo'" class="w-3 h-3 text-white"
                                                    fill="none" stroke="currentColor" stroke-width="3"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M4.5 12.75l6 6 9-13.5" />
                                                </svg>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </label>
                        </div>
                    </div>

                    {{-- Information notice --}}
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                        <div class="flex items-start gap-3 p-4 bg-blue-50 rounded-xl border border-blue-200">
                            <svg class="w-5 h-5 text-blue-500 shrink-0 mt-0.5" fill="none" stroke="currentColor"
                                stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0zm-9-3.75h.008v.008H12V8.25z" />
                            </svg>
                            <div>
                                <p class="text-sm font-semibold text-blue-700">Comment ça marche ?</p>
                                <p class="text-xs text-blue-600 mt-1 leading-relaxed">
                                    Vous serez redirigé vers la page de paiement sécurisée Jeko.
                                    Confirmez le paiement sur votre téléphone, puis vous serez automatiquement redirigé vers
                                    ReziApp.
                                </p>
                            </div>
                        </div>
                    </div>

                    {{-- Terms & Submit --}}
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 space-y-5">
                        <label class="flex items-start gap-3 cursor-pointer">
                            <input type="checkbox" required
                                class="mt-0.5 w-4 h-4 text-[#CC5A00] border-gray-300 rounded focus:ring-[#F16A00]">
                            <span class="text-xs text-gray-500 leading-relaxed">
                                J'accepte les <a href="{{ route('pages.cgu') }}"
                                    class="text-[#CC5A00] hover:underline font-medium" target="_blank">conditions
                                    générales</a>
                                et la <a href="{{ route('pages.confidentialite') }}"
                                    class="text-[#CC5A00] hover:underline font-medium" target="_blank">politique
                                    d'annulation</a>.
                            </span>
                        </label>
                        <button type="submit" :disabled="submitting"
                            class="w-full flex items-center justify-center gap-2 bg-gray-900 text-white px-6 py-3 rounded-xl font-semibold text-sm shadow-sm hover:bg-gray-800 hover:shadow-md active:scale-[0.98] transition-all disabled:opacity-50 disabled:cursor-not-allowed">
                            <template x-if="!submitting">
                                <span class="flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                                    </svg>
                                    Payer {{ number_format($sponsored->total_budget ?? 0, 0, ',', ' ') }} FCFA
                                </span>
                            </template>
                            <template x-if="submitting">
                                <span class="flex items-center gap-2">
                                    <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10"
                                            stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor"
                                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                        </path>
                                    </svg>
                                    Redirection vers Jeko...
                                </span>
                            </template>
                        </button>
                    </div>
                </form>
            </div>

            {{-- ====== Order Summary (2 cols) ====== --}}
            <div class="lg:col-span-2">
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 lg:sticky lg:top-6 space-y-5">
                    <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider">Récapitulatif</h3>

                    {{-- Residence --}}
                    <div class="flex items-center gap-3 pb-4 border-b border-gray-100">
                        @if ($sponsored->residence && $sponsored->residence->photos->first())
                            <img loading="lazy" src="{{ storage_url($sponsored->residence->photos->first()?->path) }}"
                                alt="{{ $sponsored->residence->name }}"
                                class="w-14 h-14 rounded-xl object-cover shrink-0">
                        @else
                            <div class="w-14 h-14 bg-gray-100 rounded-xl flex items-center justify-center shrink-0">
                                <svg class="w-6 h-6 text-gray-300" fill="none" stroke="currentColor"
                                    stroke-width="1.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
                                </svg>
                            </div>
                        @endif
                        <div class="min-w-0">
                            <p class="font-bold text-sm text-gray-900 truncate">
                                {{ $sponsored->residence->name ?? 'Résidence' }}</p>
                            <p class="text-xs text-gray-500">{{ $sponsored->residence->commune ?? '' }}</p>
                        </div>
                    </div>

                    {{-- Package details --}}
                    @php
                        $typeColors = [
                            'premium_listing' => 'from-amber-500 to-yellow-500',
                            'featured_home' => 'from-purple-500 to-indigo-500',
                            'top_search' => 'from-blue-500 to-cyan-500',
                            'highlighted' => 'from-[#F16A00] to-red-500',
                        ];
                    @endphp
                    <div class="flex items-center gap-2.5 p-3 bg-gray-50 rounded-xl">
                        <div
                            class="w-8 h-8 rounded-lg bg-linear-to-br {{ $typeColors[$sponsored->type] ?? 'from-gray-500 to-gray-600' }} text-white flex items-center justify-center shrink-0 shadow-sm">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-bold text-gray-900">{{ $sponsored->type_label }}</p>
                            <p class="text-[11px] text-gray-500">
                                {{ $sponsored->starts_at->diffInDays($sponsored->ends_at) }} jours</p>
                        </div>
                    </div>

                    {{-- Details --}}
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-500">Période</span>
                            <span class="font-semibold text-gray-900">{{ $sponsored->starts_at->format('d/m') }} →
                                {{ $sponsored->ends_at->format('d/m') }}</span>
                        </div>
                    </div>

                    {{-- Total --}}
                    <div class="pt-4 border-t border-gray-100">
                        <div class="flex justify-between items-center">
                            <span class="text-base font-bold text-gray-900">Total</span>
                            <span
                                class="text-xl font-bold text-[#CC5A00]">{{ number_format($sponsored->total_budget ?? 0, 0, ',', ' ') }}
                                F</span>
                        </div>
                    </div>

                    {{-- Security badge --}}
                    <div class="flex items-center gap-2 p-3 bg-green-50 rounded-xl">
                        <svg class="w-4 h-4 text-green-500 shrink-0" fill="none" stroke="currentColor"
                            stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
                        </svg>
                        <span class="text-xs font-medium text-green-700">Paiement sécurisé via Jeko</span>
                    </div>

                    {{-- Powered by Jeko --}}
                    <div class="flex items-center justify-center gap-1.5 pt-2">
                        <span class="text-[10px] text-gray-400">Propulsé par</span>
                        <span class="text-xs font-bold text-gray-500">Jeko</span>
                    </div>
                </div>
            </div>
        </div>
        @endif
    </div>
@endsection
