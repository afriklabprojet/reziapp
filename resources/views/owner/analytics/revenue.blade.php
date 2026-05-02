@extends('layouts.owner')

@section('title', 'Revenus — Analytics')

@section('owner-content')
<div class="max-w-7xl mx-auto space-y-6">
    {{-- En-tête --}}
    <div>
        <a href="{{ route('owner.analytics.index') }}"
           class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-[#ff385c] transition mb-4">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Retour aux analytics
        </a>
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-xl font-extrabold text-gray-900 flex items-center gap-2">
                    <div class="w-8 h-8 bg-[#ffd1da] rounded-lg flex items-center justify-center">
                        <svg class="w-4 h-4 text-[#ff385c]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    Analyse des revenus
                </h1>
                <p class="text-sm text-gray-500 mt-1">Du {{ $startDate->locale('fr')->isoFormat('D MMM YYYY') }} au {{ $endDate->locale('fr')->isoFormat('D MMM YYYY') }}</p>
            </div>

            {{-- Export --}}
            <a href="{{ route('owner.analytics.export.pdf', ['start_date' => $startDate->format('Y-m-d'), 'end_date' => $endDate->format('Y-m-d')]) }}"
               class="inline-flex items-center gap-2 px-4 py-2 bg-[#ff385c] text-white text-sm font-semibold rounded-xl hover:bg-[#e00b41] transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                </svg>
                Export PDF
            </a>
        </div>
    </div>

    {{-- Filtre de période --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4">
        <form action="{{ route('owner.analytics.revenue') }}" method="GET" class="flex flex-wrap items-end gap-3">
            <div class="flex-1 min-w-35">
                <label class="block text-[11px] font-semibold text-gray-500 uppercase tracking-wider mb-1">Début</label>
                <input type="date" name="start_date" value="{{ $startDate->format('Y-m-d') }}"
                       class="w-full px-3 py-2 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-[#ff385c]/20 focus:border-[#ff385c] transition">
            </div>
            <div class="flex-1 min-w-35">
                <label class="block text-[11px] font-semibold text-gray-500 uppercase tracking-wider mb-1">Fin</label>
                <input type="date" name="end_date" value="{{ $endDate->format('Y-m-d') }}"
                       class="w-full px-3 py-2 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-[#ff385c]/20 focus:border-[#ff385c] transition">
            </div>
            <button type="submit"
                    class="px-5 py-2 bg-gray-900 text-white text-sm font-semibold rounded-xl hover:bg-gray-800 transition">
                Filtrer
            </button>
        </form>
    </div>

    @if($revenueStats)
        {{-- KPIs --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            {{-- Revenu confirmé --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-10 h-10 bg-[#ffd1da] rounded-xl flex items-center justify-center">
                        <svg class="w-5 h-5 text-[#ff385c]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    @if(($revenueStats['change'] ?? 0) != 0)
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-bold {{ $revenueStats['change'] > 0 ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                            {{ $revenueStats['change'] > 0 ? '+' : '' }}{{ $revenueStats['change'] }}%
                        </span>
                    @endif
                </div>
                <p class="text-[11px] font-semibold text-gray-500 uppercase tracking-wider">Revenus confirmés</p>
                <p class="text-xl font-extrabold text-gray-900 mt-1">{{ number_format($revenueStats['confirmed'] ?? 0, 0, ',', ' ') }} <span class="text-xs font-normal text-gray-400">FCFA</span></p>
            </div>

            {{-- Revenu estimé --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-10 h-10 bg-amber-100 rounded-xl flex items-center justify-center">
                        <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                        </svg>
                    </div>
                </div>
                <p class="text-[11px] font-semibold text-gray-500 uppercase tracking-wider">Revenus estimés</p>
                <p class="text-xl font-extrabold text-gray-900 mt-1">{{ number_format($revenueStats['estimated'] ?? 0, 0, ',', ' ') }} <span class="text-xs font-normal text-gray-400">FCFA</span></p>
            </div>

            {{-- Moyenne / jour --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-10 h-10 bg-blue-100 rounded-xl flex items-center justify-center">
                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                    </div>
                </div>
                <p class="text-[11px] font-semibold text-gray-500 uppercase tracking-wider">Moyenne / jour</p>
                <p class="text-xl font-extrabold text-gray-900 mt-1">{{ number_format($revenueStats['average_daily'] ?? 0, 0, ',', ' ') }} <span class="text-xs font-normal text-gray-400">FCFA</span></p>
            </div>

            {{-- Panier moyen --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-10 h-10 bg-purple-100 rounded-xl flex items-center justify-center">
                        <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z"/>
                        </svg>
                    </div>
                </div>
                <p class="text-[11px] font-semibold text-gray-500 uppercase tracking-wider">Panier moyen</p>
                <p class="text-xl font-extrabold text-gray-900 mt-1">{{ number_format($revenueStats['average_booking'] ?? 0, 0, ',', ' ') }} <span class="text-xs font-normal text-gray-400">FCFA</span></p>
                <p class="text-[11px] text-gray-400 mt-1">{{ $revenueStats['bookings_count'] ?? 0 }} réservation{{ ($revenueStats['bookings_count'] ?? 0) > 1 ? 's' : '' }}</p>
            </div>
        </div>

        {{-- Revenus par résidence --}}
        @if(!empty($revenueStats['by_residence']))
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <div class="flex items-center justify-between mb-5">
                    <h2 class="text-sm font-bold text-gray-900">Revenus par résidence</h2>
                    <span class="text-[11px] text-gray-400">Top {{ count($revenueStats['by_residence']) }}</span>
                </div>
                <div class="space-y-4">
                    @php $maxRevenue = collect($revenueStats['by_residence'])->max('revenue') ?: 1; @endphp
                    @foreach($revenueStats['by_residence'] as $item)
                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <div class="flex items-center gap-2">
                                    <div class="w-2 h-2 rounded-full bg-[#ff385c]"></div>
                                    <span class="text-sm font-medium text-gray-700">{{ Str::limit($item['name'] ?? 'N/A', 40) }}</span>
                                </div>
                                <div class="text-right">
                                    <span class="text-sm font-bold text-gray-900">{{ number_format($item['revenue'] ?? 0, 0, ',', ' ') }} F</span>
                                    <span class="text-[11px] text-gray-400 ml-2">{{ $item['bookings'] ?? 0 }} rés.</span>
                                </div>
                            </div>
                            <div class="w-full bg-gray-100 rounded-full h-2 overflow-hidden">
                                <div class="h-full bg-[#ff385c] rounded-full transition-all duration-700"
                                     style="width: {{ (($item['revenue'] ?? 0) / $maxRevenue) * 100 }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Graphique revenus quotidiens --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
            <div class="flex items-center justify-between mb-5">
                <h2 class="text-sm font-bold text-gray-900">Évolution des revenus</h2>
                <div class="flex items-center gap-2 text-[11px] text-gray-400">
                    <span class="w-2.5 h-2.5 bg-[#ff385c] rounded-full"></span>
                    Revenus confirmés
                </div>
            </div>

            @if(!empty($revenueStats['daily']))
                @php
                    $maxDailyRevenue = collect($revenueStats['daily'])->max('revenue') ?: 1;
                @endphp
                <div class="flex items-end gap-0.5 h-40 overflow-x-auto pb-2">
                    @foreach($revenueStats['daily'] as $day)
                        <div class="flex-1 min-w-1.5 flex flex-col items-center justify-end group relative">
                            <div class="w-full bg-[#ff385c]/80 hover:bg-[#ff385c] rounded-t transition-all cursor-pointer"
                                 style="height: {{ $maxDailyRevenue > 0 ? max(($day['revenue'] / $maxDailyRevenue) * 100, $day['revenue'] > 0 ? 4 : 0) : 0 }}%"
                                 title="{{ \Carbon\Carbon::parse($day['date'])->locale('fr')->isoFormat('D MMM') }}: {{ number_format($day['revenue'], 0, ',', ' ') }} F">
                            </div>
                        </div>
                    @endforeach
                </div>
                <div class="flex justify-between mt-2 text-[10px] text-gray-400">
                    <span>{{ $startDate->locale('fr')->isoFormat('D MMM') }}</span>
                    <span>{{ $endDate->locale('fr')->isoFormat('D MMM') }}</span>
                </div>
            @endif
        </div>

        {{-- Détail par jour (tableau) --}}
        @if(!empty($revenueStats['by_day']))
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="p-5 border-b border-gray-100">
                    <h2 class="text-sm font-bold text-gray-900">Détail journalier</h2>
                    <p class="text-[11px] text-gray-400 mt-0.5">Jours avec des revenus enregistrés</p>
                </div>

                {{-- Mobile --}}
                <div class="md:hidden divide-y divide-gray-100">
                    @foreach($revenueStats['by_day'] as $day)
                        <div class="px-5 py-3 flex items-center justify-between">
                            <span class="text-sm text-gray-700">{{ \Carbon\Carbon::parse($day['date'])->locale('fr')->isoFormat('ddd D MMM') }}</span>
                            <span class="text-sm font-bold text-gray-900">{{ number_format($day['revenue'] ?? 0, 0, ',', ' ') }} F</span>
                        </div>
                    @endforeach
                </div>

                {{-- Desktop --}}
                <div class="hidden md:block overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-5 py-3 text-left text-[11px] font-semibold text-gray-500 uppercase tracking-wider">Date</th>
                                <th class="px-5 py-3 text-right text-[11px] font-semibold text-gray-500 uppercase tracking-wider">Montant</th>
                                <th class="px-5 py-3 text-right text-[11px] font-semibold text-gray-500 uppercase tracking-wider">% du total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @php $totalConfirmed = $revenueStats['confirmed'] ?: 1; @endphp
                            @foreach($revenueStats['by_day'] as $day)
                                <tr class="hover:bg-gray-50/50 transition">
                                    <td class="px-5 py-3 text-sm text-gray-700">
                                        {{ \Carbon\Carbon::parse($day['date'])->locale('fr')->isoFormat('dddd D MMMM') }}
                                    </td>
                                    <td class="px-5 py-3 text-sm font-bold text-gray-900 text-right">
                                        {{ number_format($day['revenue'] ?? 0, 0, ',', ' ') }} FCFA
                                    </td>
                                    <td class="px-5 py-3 text-right">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold bg-[#fff0f3] text-[#e00b41]">
                                            {{ round((($day['revenue'] ?? 0) / $totalConfirmed) * 100, 1) }}%
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="bg-[#fff0f3]/50">
                            <tr>
                                <td class="px-5 py-3 text-sm font-bold text-gray-900">Total</td>
                                <td class="px-5 py-3 text-sm font-extrabold text-[#e00b41] text-right">{{ number_format($revenueStats['confirmed'] ?? 0, 0, ',', ' ') }} FCFA</td>
                                <td class="px-5 py-3 text-right">
                                    <span class="text-[11px] font-bold text-[#e00b41]">100%</span>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        @endif

        {{-- Mois précédent --}}
        @if(($revenueStats['previous_month'] ?? 0) > 0)
            <div class="bg-blue-50 rounded-2xl p-4 flex items-start gap-3">
                <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center shrink-0">
                    <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-semibold text-blue-900">Comparaison mois précédent</p>
                    <p class="text-sm text-blue-700 mt-0.5">
                        Le mois précédent, vos revenus confirmés étaient de
                        <span class="font-bold">{{ number_format($revenueStats['previous_month'], 0, ',', ' ') }} FCFA</span>,
                        soit une variation de
                        <span class="font-bold {{ ($revenueStats['change'] ?? 0) >= 0 ? 'text-green-700' : 'text-red-700' }}">
                            {{ ($revenueStats['change'] ?? 0) >= 0 ? '+' : '' }}{{ $revenueStats['change'] ?? 0 }}%
                        </span>.
                    </p>
                </div>
            </div>
        @endif

    @else
        {{-- État vide --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-12 text-center">
            <div class="w-16 h-16 bg-gray-100 rounded-2xl flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <h3 class="text-sm font-bold text-gray-900 mb-1">Aucune donnée de revenus</h3>
            <p class="text-sm text-gray-500">Aucun revenu enregistré sur cette période. Essayez de modifier les dates.</p>
        </div>
    @endif
</div>
@endsection
