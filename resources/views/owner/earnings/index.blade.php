@extends('layouts.owner')

@section('title', 'Mes Revenus | REZI')

@section('owner-content')
    <div class="min-h-screen bg-gray-50/50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8">

            {{-- ============================== HEADER ============================== --}}
            <div class="mb-6">
                <h1 class="text-2xl font-extrabold text-gray-900 tracking-tight">Mes Revenus</h1>
                <p class="mt-1 text-sm text-gray-500">Suivez vos gains et analysez vos performances</p>
            </div>

            {{-- ============================== SOLDE CARDS ============================== --}}
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-6">
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="w-10 h-10 bg-green-50 rounded-xl flex items-center justify-center">
                            <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" stroke-width="1.5"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M12 6v12m-3-2.818.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                            </svg>
                        </div>
                        <p class="text-[11px] text-gray-400 font-medium">Solde disponible</p>
                    </div>
                    <p class="text-xl font-extrabold text-green-600">{{ $balance->formatted_available }}</p>
                </div>

                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="w-10 h-10 bg-amber-50 rounded-xl flex items-center justify-center">
                            <svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" stroke-width="1.5"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                            </svg>
                        </div>
                        <p class="text-[11px] text-gray-400 font-medium">En attente</p>
                    </div>
                    <p class="text-xl font-extrabold text-amber-600">{{ $balance->formatted_pending }}</p>
                </div>

                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="w-10 h-10 bg-blue-50 rounded-xl flex items-center justify-center">
                            <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" stroke-width="1.5"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M2.25 18 9 11.25l4.306 4.306a11.95 11.95 0 0 1 5.814-5.518l2.74-1.22m0 0-5.94-2.281m5.94 2.28-2.28 5.941" />
                            </svg>
                        </div>
                        <p class="text-[11px] text-gray-400 font-medium">Total gagné</p>
                    </div>
                    <p class="text-xl font-extrabold text-gray-900">{{ $balance->formatted_total_earned }}</p>
                </div>

                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="w-10 h-10 bg-gray-50 rounded-xl flex items-center justify-center">
                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" stroke-width="1.5"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm3 0h.008v.008H18V10.5Zm-12 0h.008v.008H6V10.5Z" />
                            </svg>
                        </div>
                        <p class="text-[11px] text-gray-400 font-medium">Total retiré</p>
                    </div>
                    <p class="text-xl font-extrabold text-gray-900">{{ $balance->formatted_total_withdrawn }}</p>
                </div>
            </div>

            {{-- ============================== FILTRE PÉRIODE ============================== --}}
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4 mb-6">
                <form action="{{ route('owner.earnings.index') }}" method="GET" class="flex flex-wrap items-end gap-3">
                    <div>
                        <label class="block text-[11px] text-gray-400 font-medium mb-1">Début</label>
                        <input type="date" name="start_date" value="{{ $startDate->format('Y-m-d') }}"
                            class="rounded-xl border border-gray-200 text-sm px-3 py-2.5 focus:border-orange-300 focus:ring focus:ring-orange-100 bg-gray-50/50">
                    </div>
                    <div>
                        <label class="block text-[11px] text-gray-400 font-medium mb-1">Fin</label>
                        <input type="date" name="end_date" value="{{ $endDate->format('Y-m-d') }}"
                            class="rounded-xl border border-gray-200 text-sm px-3 py-2.5 focus:border-orange-300 focus:ring focus:ring-orange-100 bg-gray-50/50">
                    </div>
                    <button type="submit"
                        class="px-5 py-2.5 bg-gray-900 text-white text-sm font-semibold rounded-xl hover:bg-gray-800 transition-colors">
                        Filtrer
                    </button>
                </form>
            </div>

            {{-- ============================== KPI PÉRIODE ============================== --}}
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-6">
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4">
                    <p class="text-[11px] text-gray-400 font-medium mb-1">Gains nets (période)</p>
                    <p class="text-lg font-bold text-gray-900">
                        {{ number_format($totalEarnings, 0, ',', ' ') }}
                        <span class="text-[10px] text-gray-400 font-medium">F</span>
                    </p>
                </div>
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4">
                    <p class="text-[11px] text-gray-400 font-medium mb-1">Commission REZI</p>
                    <p class="text-lg font-bold text-gray-900">
                        {{ number_format($totalCommission, 0, ',', ' ') }}
                        <span class="text-[10px] text-gray-400 font-medium">F</span>
                    </p>
                    <p class="text-[10px] text-gray-400 mt-0.5">{{ $commissionRate * 100 }}% du brut</p>
                </div>
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4">
                    <p class="text-[11px] text-gray-400 font-medium mb-1">Réservations</p>
                    <p class="text-lg font-bold text-gray-900">{{ $bookingsCount }}</p>
                </div>
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4">
                    <p class="text-[11px] text-gray-400 font-medium mb-1">Gain moyen</p>
                    <p class="text-lg font-bold text-gray-900">
                        {{ number_format($averageEarning, 0, ',', ' ') }}
                        <span class="text-[10px] text-gray-400 font-medium">F</span>
                    </p>
                </div>
            </div>

            {{-- ============================== GRAPHIQUES ============================== --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                {{-- Revenus par résidence --}}
                @if ($earningsByResidence->isNotEmpty())
                    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
                        <h2 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-4">Revenus par résidence
                        </h2>
                        <div class="space-y-4">
                            @foreach ($earningsByResidence as $item)
                                <div>
                                    <div class="flex items-center justify-between mb-1.5">
                                        <span
                                            class="text-sm font-semibold text-gray-900 truncate max-w-[60%]">{{ $item['name'] }}</span>
                                        <span class="text-sm font-bold text-gray-900">
                                            {{ number_format($item['net'], 0, ',', ' ') }}
                                            <span class="text-[10px] text-gray-400 font-medium">F</span>
                                        </span>
                                    </div>
                                    @php
                                        $maxNet = $earningsByResidence->max('net');
                                        $percent = $maxNet > 0 ? ($item['net'] / $maxNet) * 100 : 0;
                                    @endphp
                                    <div class="w-full bg-gray-100 rounded-full h-2">
                                        <div class="bg-orange-500 h-2 rounded-full transition-all duration-500"
                                            style="width: {{ $percent }}%"></div>
                                    </div>
                                    <p class="text-[11px] text-gray-400 mt-1">{{ $item['count'] }}
                                        réservation{{ $item['count'] > 1 ? 's' : '' }}</p>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Évolution mensuelle --}}
                @if ($monthlyEarnings->isNotEmpty())
                    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
                        <h2 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-4">Évolution (12 mois)
                        </h2>
                        <div class="space-y-3">
                            @foreach ($monthlyEarnings as $month)
                                <div class="flex items-center gap-3">
                                    <span
                                        class="text-xs text-gray-500 w-20 shrink-0 font-medium">{{ $month['label'] }}</span>
                                    <div class="flex-1">
                                        @php
                                            $maxMonth = $monthlyEarnings->max('net');
                                            $pct = $maxMonth > 0 ? ($month['net'] / $maxMonth) * 100 : 0;
                                        @endphp
                                        <div class="w-full bg-gray-100 rounded-full h-2">
                                            <div class="bg-blue-500 h-2 rounded-full transition-all duration-500"
                                                style="width: {{ $pct }}%"></div>
                                        </div>
                                    </div>
                                    <span
                                        class="text-xs font-bold text-gray-900 w-28 text-right shrink-0">{{ number_format($month['net'], 0, ',', ' ') }}
                                        F</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            {{-- ============================== DÉTAIL DES GAINS ============================== --}}
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                <div class="px-6 py-5 border-b border-gray-100">
                    <h2 class="text-xs font-bold text-gray-400 uppercase tracking-wider">Détail des gains</h2>
                    <p class="text-sm text-gray-500 mt-1">
                        Réservations complétées du {{ $startDate->format('d/m/Y') }} au
                        {{ $endDate->format('d/m/Y') }}
                    </p>
                </div>

                @if ($bookings->isEmpty())
                    <div class="px-6 py-12 text-center">
                        <div class="w-14 h-14 bg-gray-50 rounded-2xl flex items-center justify-center mx-auto mb-3">
                            <svg class="w-7 h-7 text-gray-300" fill="none" stroke="currentColor" stroke-width="1.5"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm3 0h.008v.008H18V10.5Zm-12 0h.008v.008H6V10.5Z" />
                            </svg>
                        </div>
                        <p class="text-sm font-bold text-gray-900">Aucun gain sur cette période</p>
                        <p class="text-xs text-gray-500 mt-1">Modifiez les dates pour voir d'autres résultats</p>
                    </div>
                @else
                    {{-- Mobile cards --}}
                    <div class="sm:hidden divide-y divide-gray-100">
                        @foreach ($bookings as $booking)
                            <div class="p-4">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-sm font-bold text-gray-900 truncate max-w-[55%]">
                                        {{ $booking->residence->name ?? '—' }}
                                    </span>
                                    <span class="text-sm font-extrabold text-green-600">
                                        {{ number_format($booking->owner_earnings, 0, ',', ' ') }} F
                                    </span>
                                </div>
                                <div class="flex items-center gap-3 text-xs text-gray-500">
                                    <span>{{ $booking->user->name ?? '—' }}</span>
                                    <span class="text-gray-300">·</span>
                                    <span>{{ $booking->nights_count ?? $booking->nights }} nuits</span>
                                    <span class="text-gray-300">·</span>
                                    <span>{{ $booking->check_out?->format('d/m') }}</span>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    {{-- Desktop table --}}
                    <div class="hidden sm:block overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-50/80">
                                <tr>
                                    <th
                                        class="px-6 py-3 text-left text-[11px] font-bold text-gray-400 uppercase tracking-wider">
                                        Résidence</th>
                                    <th
                                        class="px-6 py-3 text-left text-[11px] font-bold text-gray-400 uppercase tracking-wider">
                                        Voyageur</th>
                                    <th
                                        class="px-6 py-3 text-center text-[11px] font-bold text-gray-400 uppercase tracking-wider">
                                        Nuits</th>
                                    <th
                                        class="px-6 py-3 text-right text-[11px] font-bold text-gray-400 uppercase tracking-wider">
                                        Brut</th>
                                    <th
                                        class="px-6 py-3 text-right text-[11px] font-bold text-gray-400 uppercase tracking-wider">
                                        Commission</th>
                                    <th
                                        class="px-6 py-3 text-right text-[11px] font-bold text-gray-400 uppercase tracking-wider">
                                        Net</th>
                                    <th
                                        class="px-6 py-3 text-right text-[11px] font-bold text-gray-400 uppercase tracking-wider">
                                        Date</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach ($bookings as $booking)
                                    <tr class="hover:bg-gray-50/50 transition-colors">
                                        <td class="px-6 py-4">
                                            <span class="font-semibold text-gray-900 truncate block max-w-45">
                                                {{ $booking->residence->name ?? '—' }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-gray-600">{{ $booking->user->name ?? '—' }}</td>
                                        <td class="px-6 py-4 text-center text-gray-600">
                                            {{ $booking->nights_count ?? $booking->nights }}</td>
                                        <td class="px-6 py-4 text-right text-gray-600">
                                            {{ number_format((float) $booking->subtotal + (float) $booking->cleaning_fee, 0, ',', ' ') }}
                                        </td>
                                        <td class="px-6 py-4 text-right text-red-500 font-medium">
                                            −{{ number_format($booking->commission_amount, 0, ',', ' ') }}
                                        </td>
                                        <td class="px-6 py-4 text-right font-bold text-green-600">
                                            {{ number_format($booking->owner_earnings, 0, ',', ' ') }}
                                            <span class="text-[10px] text-gray-400 font-medium">F</span>
                                        </td>
                                        <td class="px-6 py-4 text-right text-gray-500 text-xs">
                                            {{ $booking->check_out?->format('d/m/Y') }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="bg-gray-50/80">
                                <tr class="font-bold">
                                    <td colspan="3" class="px-6 py-4 text-right text-xs text-gray-500 uppercase">
                                        Total</td>
                                    <td class="px-6 py-4 text-right text-gray-900">
                                        {{ number_format($bookings->sum(fn($b) => (float) $b->subtotal + (float) $b->cleaning_fee), 0, ',', ' ') }}
                                    </td>
                                    <td class="px-6 py-4 text-right text-red-500">
                                        −{{ number_format($totalCommission, 0, ',', ' ') }}
                                    </td>
                                    <td class="px-6 py-4 text-right text-green-600">
                                        {{ number_format($totalEarnings, 0, ',', ' ') }} F
                                    </td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                @endif
            </div>

            {{-- ============================== INFO COMMISSION ============================== --}}
            <div class="mt-6 bg-white rounded-2xl border border-blue-100 p-4">
                <div class="flex items-start gap-3">
                    <div class="w-8 h-8 bg-blue-50 rounded-lg flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" stroke-width="1.5"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-bold text-gray-900">Commission REZI : {{ $commissionRate * 100 }}%</p>
                        <p class="text-xs text-gray-500 mt-0.5 leading-relaxed">
                            Cette commission couvre les frais de la plateforme, le support client et la gestion des
                            paiements.
                            Les gains en attente sont libérés 24h après le check-out du voyageur.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
