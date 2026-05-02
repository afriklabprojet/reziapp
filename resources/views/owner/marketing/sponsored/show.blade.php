@extends('layouts.owner')

@section('title', 'Détails de la campagne')

@section('owner-content')
    <div class="max-w-4xl mx-auto space-y-6">

        {{-- ====== Header ====== --}}
        <div>
            <a href="{{ route('owner.marketing.sponsored.index') }}"
                class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-900 transition-colors mb-4">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
                </svg>
                Retour aux campagnes
            </a>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">{{ $sponsored->residence->name ?? 'Campagne sponsorisée' }}
                    </h1>
                    <p class="text-sm text-gray-500 mt-0.5">{{ $sponsored->type_label }} · Créée le
                        {{ $sponsored->created_at->format('d/m/Y') }}</p>
                </div>
                @if ($sponsored->status === 'active')
                    <span
                        class="self-start inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-full bg-green-50 text-green-700 border border-green-200">
                        <span class="w-1.5 h-1.5 bg-green-500 rounded-full animate-pulse"></span>Active
                    </span>
                @elseif ($sponsored->status === 'pending')
                    <span
                        class="self-start inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-full bg-[#fff0f3] text-[#b5083a] border border-[#ffb3c1]">
                        <span class="w-1.5 h-1.5 bg-[#ff385c] rounded-full"></span>En attente
                    </span>
                @elseif ($sponsored->status === 'paused')
                    <span
                        class="self-start inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-full bg-yellow-50 text-yellow-700 border border-yellow-200">
                        <span class="w-1.5 h-1.5 bg-yellow-500 rounded-full"></span>En pause
                    </span>
                @elseif ($sponsored->status === 'completed')
                    <span
                        class="self-start inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-full bg-gray-50 text-gray-500 border border-gray-200">
                        <span class="w-1.5 h-1.5 bg-gray-400 rounded-full"></span>Terminée
                    </span>
                @else
                    <span
                        class="self-start inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-full bg-red-50 text-red-600 border border-red-200">
                        <span class="w-1.5 h-1.5 bg-red-400 rounded-full"></span>Annulée
                    </span>
                @endif
            </div>
        </div>

        {{-- ====== Flash messages ====== --}}
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

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            {{-- ====== Main Column ====== --}}
            <div class="lg:col-span-2 space-y-6">

                {{-- Performance Chart --}}
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <div class="flex items-center justify-between mb-5">
                        <h3 class="text-base font-bold text-gray-900 flex items-center gap-2">
                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" stroke-width="2"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />
                            </svg>
                            Performance
                        </h3>
                        <span class="text-[11px] font-medium text-gray-400 bg-gray-50 px-2 py-0.5 rounded-md">7 derniers
                            jours</span>
                    </div>
                    <div class="h-52">
                        <canvas id="performanceChart"></canvas>
                    </div>
                </div>

                {{-- Stats Grid --}}
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 text-center">
                        <div class="w-8 h-8 rounded-xl bg-purple-50 flex items-center justify-center mx-auto mb-2">
                            <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" stroke-width="2"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                        </div>
                        <p class="text-2xl font-bold text-gray-900">{{ number_format($sponsored->impressions) }}</p>
                        <p class="text-[11px] text-gray-500 font-medium mt-0.5">Impressions</p>
                    </div>
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 text-center">
                        <div class="w-8 h-8 rounded-xl bg-blue-50 flex items-center justify-center mx-auto mb-2">
                            <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" stroke-width="2"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M15.042 21.672L13.684 16.6m0 0l-2.51 2.225.569-9.47 5.227 7.917-3.286-.672zM12 2.25V4.5m5.834.166l-1.591 1.591M20.25 10.5H18M7.757 14.743l-1.59 1.59M6 10.5H3.75m4.007-4.243l-1.59-1.59" />
                            </svg>
                        </div>
                        <p class="text-2xl font-bold text-gray-900">{{ number_format($sponsored->clicks) }}</p>
                        <p class="text-[11px] text-gray-500 font-medium mt-0.5">Clics</p>
                    </div>
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 text-center">
                        <div class="w-8 h-8 rounded-xl bg-cyan-50 flex items-center justify-center mx-auto mb-2">
                            <svg class="w-4 h-4 text-cyan-600" fill="none" stroke="currentColor" stroke-width="2"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />
                            </svg>
                        </div>
                        <p class="text-2xl font-bold text-cyan-600">{{ $sponsored->click_rate }}%</p>
                        <p class="text-[11px] text-gray-500 font-medium mt-0.5">Taux de clic</p>
                    </div>
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 text-center">
                        <div class="w-8 h-8 rounded-xl bg-green-50 flex items-center justify-center mx-auto mb-2">
                            <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" stroke-width="2"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                            </svg>
                        </div>
                        <p class="text-2xl font-bold text-green-600">{{ $sponsored->contacts_generated }}</p>
                        <p class="text-[11px] text-gray-500 font-medium mt-0.5">Contacts</p>
                    </div>
                </div>

                {{-- KPIs avancés --}}
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <h3 class="text-base font-bold text-gray-900 mb-5 flex items-center gap-2">
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" stroke-width="2"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M7.5 14.25v2.25m3-4.5v4.5m3-6.75v6.75m3-9v9M6 20.25h12A2.25 2.25 0 0020.25 18V6A2.25 2.25 0 0018 3.75H6A2.25 2.25 0 003.75 6v12A2.25 2.25 0 006 20.25z" />
                        </svg>
                        Indicateurs clés
                    </h3>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="bg-gray-50 rounded-xl p-4">
                            <p class="text-xs font-semibold text-gray-500 mb-1">Coût par clic moyen</p>
                            <p class="text-xl font-bold text-gray-900">
                                {{ number_format($sponsored->cost_per_click, 0, ',', ' ') }} <span
                                    class="text-sm font-normal text-gray-400">FCFA</span></p>
                        </div>
                        <div class="bg-gray-50 rounded-xl p-4">
                            <p class="text-xs font-semibold text-gray-500 mb-1">Taux de conversion</p>
                            <p class="text-xl font-bold text-gray-900">{{ $sponsored->conversion_rate }}<span
                                    class="text-sm font-normal text-gray-400">%</span></p>
                        </div>
                        <div class="bg-gray-50 rounded-xl p-4">
                            <p class="text-xs font-semibold text-gray-500 mb-1">Coût par contact</p>
                            <p class="text-xl font-bold text-gray-900">
                                @if ($sponsored->contacts_generated > 0)
                                    {{ number_format($sponsored->amount_spent / $sponsored->contacts_generated, 0, ',', ' ') }}
                                    <span class="text-sm font-normal text-gray-400">FCFA</span>
                                @else
                                    <span class="text-gray-300">—</span>
                                @endif
                            </p>
                        </div>
                        <div class="bg-gray-50 rounded-xl p-4">
                            <p class="text-xs font-semibold text-gray-500 mb-1">ROI estimé</p>
                            @if ($sponsored->contacts_generated > 0)
                                @php $roi = round((($sponsored->contacts_generated * 50000 - $sponsored->amount_spent) / max(1, $sponsored->amount_spent)) * 100); @endphp
                                <p class="text-xl font-bold {{ $roi >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                    {{ $roi >= 0 ? '+' : '' }}{{ $roi }}<span
                                        class="text-sm font-normal">%</span></p>
                            @else
                                <p class="text-xl font-bold text-gray-300">—</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- ====== Sidebar ====== --}}
            <div class="space-y-6">

                {{-- Campaign details --}}
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                    <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-4">Détails de la campagne</h3>
                    <div class="space-y-3 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-500">Package</span>
                            <span class="font-semibold text-gray-900">{{ $sponsored->type_label }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500">Période</span>
                            <span class="font-semibold text-gray-900">{{ $sponsored->starts_at->format('d/m') }} →
                                {{ $sponsored->ends_at->format('d/m') }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500">Jours restants</span>
                            @if ($sponsored->days_remaining > 0)
                                <span class="font-semibold text-green-600">{{ $sponsored->days_remaining }}j</span>
                            @else
                                <span class="font-semibold text-red-500">Expiré</span>
                            @endif
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500">Facturation</span>
                            <span class="font-semibold text-gray-900">
                                @if ($sponsored->billing_type === 'per_click')
                                    Au clic
                                @elseif ($sponsored->billing_type === 'per_view')
                                    À la vue
                                @else
                                    Forfait
                                @endif
                            </span>
                        </div>
                    </div>
                </div>

                {{-- Budget --}}
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                    <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-4">Budget</h3>
                    <div class="space-y-3">
                        <div>
                            <div class="flex justify-between text-sm mb-1.5">
                                <span class="text-gray-500">Dépensé</span>
                                <span
                                    class="font-bold text-gray-900">{{ number_format($sponsored->amount_spent, 0, ',', ' ') }}
                                    F</span>
                            </div>
                            @if ($sponsored->total_budget)
                                @php $budgetPct = min(100, round(($sponsored->amount_spent / $sponsored->total_budget) * 100)); @endphp
                                <div class="w-full h-2 bg-gray-100 rounded-full overflow-hidden">
                                    <div class="h-full rounded-full transition-all duration-500 {{ $budgetPct >= 90 ? 'bg-red-500' : ($budgetPct >= 60 ? 'bg-[#ff4d6d]' : 'bg-green-500') }}"
                                        style="width: {{ $budgetPct }}%"></div>
                                </div>
                                <p class="text-[11px] text-gray-400 mt-1">sur
                                    {{ number_format($sponsored->total_budget, 0, ',', ' ') }} FCFA ({{ $budgetPct }}%)
                                </p>
                            @endif
                        </div>
                        @if ($sponsored->remaining_budget)
                            <div class="pt-3 border-t border-gray-100">
                                <p class="text-xs text-gray-500 mb-0.5">Restant</p>
                                <p class="text-lg font-bold text-green-600">
                                    {{ number_format($sponsored->remaining_budget, 0, ',', ' ') }} FCFA</p>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Actions --}}
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                    <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-4">Actions</h3>
                    <div class="space-y-2">
                        @if ($sponsored->status === 'active')
                            <form action="{{ route('owner.marketing.sponsored.pause', $sponsored) }}" method="POST">
                                @csrf
                                @method('PATCH')
                                <button type="submit"
                                    class="w-full flex items-center justify-center gap-2 px-4 py-2.5 bg-yellow-50 text-yellow-700 border border-yellow-200 rounded-xl hover:bg-yellow-100 transition-colors text-sm font-semibold">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M15.75 5.25v13.5m-7.5-13.5v13.5" />
                                    </svg>
                                    Mettre en pause
                                </button>
                            </form>
                        @elseif ($sponsored->status === 'paused')
                            <form action="{{ route('owner.marketing.sponsored.resume', $sponsored) }}" method="POST">
                                @csrf
                                @method('PATCH')
                                <button type="submit"
                                    class="w-full flex items-center justify-center gap-2 px-4 py-2.5 bg-green-50 text-green-700 border border-green-200 rounded-xl hover:bg-green-100 transition-colors text-sm font-semibold">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M5.25 5.653c0-.856.917-1.398 1.667-.986l11.54 6.347a1.125 1.125 0 0 1 0 1.972l-11.54 6.347a1.125 1.125 0 0 1-1.667-.986V5.653z" />
                                    </svg>
                                    Reprendre la campagne
                                </button>
                            </form>
                        @elseif ($sponsored->status === 'pending' && !$sponsored->is_paid)
                            <a href="{{ route('owner.marketing.sponsored.payment', $sponsored) }}"
                                class="w-full flex items-center justify-center gap-2 px-4 py-2.5 bg-gray-900 text-white rounded-xl hover:bg-gray-800 transition-colors text-sm font-semibold shadow-sm">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z" />
                                </svg>
                                Procéder au paiement
                            </a>
                        @endif

                        @if (!in_array($sponsored->status, ['completed', 'cancelled']))
                            <form action="{{ route('owner.marketing.sponsored.cancel', $sponsored) }}" method="POST"
                                x-data="{ confirm: false }">
                                @csrf
                                @method('PATCH')
                                <template x-if="!confirm">
                                    <button type="button" @click="confirm = true"
                                        class="w-full flex items-center justify-center gap-2 px-4 py-2.5 text-red-600 border border-red-200 bg-red-50 rounded-xl hover:bg-red-100 transition-colors text-sm font-semibold">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                        Annuler la campagne
                                    </button>
                                </template>
                                <template x-if="confirm">
                                    <div class="space-y-2">
                                        <p class="text-xs text-red-600 text-center font-medium">Confirmer l'annulation ?
                                        </p>
                                        <div class="flex gap-2">
                                            <button type="button" @click="confirm = false"
                                                class="flex-1 px-3 py-2 text-xs font-medium bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors">Non</button>
                                            <button type="submit"
                                                class="flex-1 px-3 py-2 text-xs font-medium bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">Oui,
                                                annuler</button>
                                        </div>
                                    </div>
                                </template>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        @vite('resources/js/chart.js')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                window.initSponsoredPerformanceChart(@json($performanceData));
            });
        </script>
    @endpush
@endsection
