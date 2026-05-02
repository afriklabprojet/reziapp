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
                            class="rounded-xl border border-gray-200 text-sm px-3 py-2.5 focus:border-[#ffb3c1] focus:ring focus:ring-orange-100 bg-gray-50/50">
                    </div>
                    <div>
                        <label class="block text-[11px] text-gray-400 font-medium mb-1">Fin</label>
                        <input type="date" name="end_date" value="{{ $endDate->format('Y-m-d') }}"
                            class="rounded-xl border border-gray-200 text-sm px-3 py-2.5 focus:border-[#ffb3c1] focus:ring focus:ring-orange-100 bg-gray-50/50">
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
                                        <div class="bg-[#ff385c] h-2 rounded-full transition-all duration-500"
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

            {{-- ============================== DEMANDE DE RETRAIT ============================== --}}
            <div class="mt-8" x-data="{
                showPinSetup: false,
                showPayoutForm: false,
                payoutMethod: 'wave',
                amount: '',
                get isBankTransfer() { return this.payoutMethod === 'bank_transfer'; }
            }">
                <div class="flex items-center gap-3 mb-4">
                    <h2 class="text-lg font-extrabold text-gray-900">Demander un retrait</h2>
                    <div class="h-px bg-gray-200 flex-1"></div>
                </div>

                {{-- ── PIN non configuré : message d'alerte ── --}}
                @if (! auth()->user()->hasWithdrawalPin())
                    <div class="bg-amber-50 border border-amber-200 rounded-2xl p-5 mb-4">
                        <div class="flex items-start gap-3">
                            <div class="w-10 h-10 bg-amber-100 rounded-xl flex items-center justify-center shrink-0">
                                <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                                </svg>
                            </div>
                            <div class="flex-1">
                                <p class="text-sm font-bold text-amber-800">PIN de retrait non configuré</p>
                                <p class="text-xs text-amber-700 mt-1">Pour sécuriser vos retraits, vous devez d'abord configurer un PIN à 4 chiffres. Ce code sera demandé à chaque demande de retrait.</p>
                                <button @click="showPinSetup = !showPinSetup"
                                    class="mt-3 px-4 py-2 bg-amber-600 text-white text-sm font-semibold rounded-xl hover:bg-amber-700 transition-colors">
                                    Configurer mon PIN
                                </button>
                            </div>
                        </div>
                    </div>

                    {{-- Formulaire de configuration du PIN --}}
                    <div x-show="showPinSetup" x-transition class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6 mb-4">
                        <h3 class="text-sm font-bold text-gray-900 mb-4">Configurer votre PIN de retrait</h3>
                        <form action="{{ route('owner.earnings.setup-pin') }}" method="POST" class="space-y-4">
                            @csrf
                            <div>
                                <label class="block text-xs text-gray-500 font-medium mb-1">Mot de passe actuel</label>
                                <input type="password" name="current_password" required autocomplete="current-password"
                                    class="w-full rounded-xl border border-gray-200 text-sm px-3 py-2.5 focus:border-[#ffb3c1] focus:ring focus:ring-orange-100 bg-gray-50/50"
                                    placeholder="Votre mot de passe de connexion">
                                @error('current_password')
                                    <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs text-gray-500 font-medium mb-1">PIN (4 chiffres)</label>
                                    <input type="password" name="withdrawal_pin" required maxlength="4" pattern="\d{4}" inputmode="numeric" autocomplete="off"
                                        class="w-full rounded-xl border border-gray-200 text-lg px-3 py-2.5 focus:border-[#ffb3c1] focus:ring focus:ring-orange-100 bg-gray-50/50 tracking-[0.5em] text-center font-mono"
                                        placeholder="••••">
                                    @error('withdrawal_pin')
                                        <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-500 font-medium mb-1">Confirmer le PIN</label>
                                    <input type="password" name="withdrawal_pin_confirmation" required maxlength="4" pattern="\d{4}" inputmode="numeric" autocomplete="off"
                                        class="w-full rounded-xl border border-gray-200 text-lg px-3 py-2.5 focus:border-[#ffb3c1] focus:ring focus:ring-orange-100 bg-gray-50/50 tracking-[0.5em] text-center font-mono"
                                        placeholder="••••">
                                </div>
                            </div>
                            <div class="flex items-center gap-2 text-xs text-gray-400">
                                <svg class="w-4 h-4 text-gray-300" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" />
                                </svg>
                                Ce PIN protège vos retraits. Ne le partagez jamais.
                            </div>
                            <button type="submit"
                                class="w-full px-5 py-3 bg-gray-900 text-white text-sm font-semibold rounded-xl hover:bg-gray-800 transition-colors">
                                Enregistrer le PIN
                            </button>
                        </form>
                    </div>
                @else
                    {{-- ── PIN configuré : bouton modifier + formulaire retrait ── --}}
                    <div class="flex flex-wrap items-center gap-3 mb-4">
                        <div class="flex items-center gap-2 text-sm text-green-600 bg-green-50 px-3 py-1.5 rounded-full">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" />
                            </svg>
                            <span class="font-medium">PIN de retrait actif</span>
                        </div>
                        <button @click="showPinSetup = !showPinSetup"
                            class="text-xs text-gray-500 hover:text-gray-700 underline">
                            Modifier le PIN
                        </button>
                    </div>

                    {{-- Formulaire modification du PIN (caché) --}}
                    <div x-show="showPinSetup" x-transition class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6 mb-4">
                        <h3 class="text-sm font-bold text-gray-900 mb-4">Modifier votre PIN de retrait</h3>
                        <form action="{{ route('owner.earnings.setup-pin') }}" method="POST" class="space-y-4">
                            @csrf
                            <div>
                                <label class="block text-xs text-gray-500 font-medium mb-1">Mot de passe actuel</label>
                                <input type="password" name="current_password" required autocomplete="current-password"
                                    class="w-full rounded-xl border border-gray-200 text-sm px-3 py-2.5 focus:border-[#ffb3c1] focus:ring focus:ring-orange-100 bg-gray-50/50"
                                    placeholder="Votre mot de passe de connexion">
                                @error('current_password')
                                    <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs text-gray-500 font-medium mb-1">Nouveau PIN (4 chiffres)</label>
                                    <input type="password" name="withdrawal_pin" required maxlength="4" pattern="\d{4}" inputmode="numeric" autocomplete="off"
                                        class="w-full rounded-xl border border-gray-200 text-lg px-3 py-2.5 focus:border-[#ffb3c1] focus:ring focus:ring-orange-100 bg-gray-50/50 tracking-[0.5em] text-center font-mono"
                                        placeholder="••••">
                                    @error('withdrawal_pin')
                                        <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-500 font-medium mb-1">Confirmer</label>
                                    <input type="password" name="withdrawal_pin_confirmation" required maxlength="4" pattern="\d{4}" inputmode="numeric" autocomplete="off"
                                        class="w-full rounded-xl border border-gray-200 text-lg px-3 py-2.5 focus:border-[#ffb3c1] focus:ring focus:ring-orange-100 bg-gray-50/50 tracking-[0.5em] text-center font-mono"
                                        placeholder="••••">
                                </div>
                            </div>
                            <button type="submit"
                                class="px-5 py-2.5 bg-gray-900 text-white text-sm font-semibold rounded-xl hover:bg-gray-800 transition-colors">
                                Enregistrer
                            </button>
                        </form>
                    </div>

                    {{-- ── Demande en cours ? ── --}}
                    @if ($pendingPayout)
                        <div class="bg-blue-50 border border-blue-200 rounded-2xl p-5">
                            <div class="flex items-start gap-3">
                                <div class="w-10 h-10 bg-blue-100 rounded-xl flex items-center justify-center shrink-0">
                                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-sm font-bold text-blue-800">Retrait en cours</p>
                                    <p class="text-xs text-blue-700 mt-1">
                                        Votre demande de <strong>{{ number_format((float) $pendingPayout->net_amount, 0, ',', ' ') }} FCFA</strong>
                                        (réf: {{ $pendingPayout->reference }}) est {{ $pendingPayout->status === 'processing' ? 'en cours de traitement' : 'en attente' }}.
                                    </p>
                                </div>
                            </div>
                        </div>
                    @else
                        {{-- ── Formulaire de retrait ── --}}
                        @if ($balance->available_balance >= $minWithdrawal)
                            <button @click="showPayoutForm = !showPayoutForm"
                                class="w-full sm:w-auto px-6 py-3 bg-green-600 text-white text-sm font-semibold rounded-xl hover:bg-green-700 transition-colors flex items-center gap-2 mb-4"
                                x-show="!showPayoutForm">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm3 0h.008v.008H18V10.5Zm-12 0h.008v.008H6V10.5Z" />
                                </svg>
                                Demander un retrait
                            </button>

                            <div x-show="showPayoutForm" x-transition class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6">
                                <div class="flex items-center justify-between mb-5">
                                    <h3 class="text-sm font-bold text-gray-900">Nouveau retrait</h3>
                                    <button @click="showPayoutForm = false" class="text-gray-400 hover:text-gray-600">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                </div>

                                <form action="{{ route('owner.earnings.request-payout') }}" method="POST" class="space-y-4">
                                    @csrf

                                    {{-- Montant --}}
                                    <div>
                                        <label class="block text-xs text-gray-500 font-medium mb-1">Montant (FCFA)</label>
                                        <input type="number" name="amount" x-model="amount" required
                                            min="{{ $minWithdrawal }}" max="{{ (int) $balance->available_balance }}"
                                            step="500" value="{{ old('amount') }}"
                                            class="w-full rounded-xl border border-gray-200 text-sm px-3 py-2.5 focus:border-[#ffb3c1] focus:ring focus:ring-orange-100 bg-gray-50/50"
                                            placeholder="Ex: {{ number_format($minWithdrawal, 0, ',', ' ') }}">
                                        <p class="text-[11px] text-gray-400 mt-1">
                                            Disponible : {{ $balance->formatted_available }} &bull;
                                            Minimum : {{ number_format($minWithdrawal, 0, ',', ' ') }} F
                                        </p>
                                        @error('amount')
                                            <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    {{-- Méthode --}}
                                    <div>
                                        <label class="block text-xs text-gray-500 font-medium mb-1">Méthode de retrait</label>
                                        <select name="payout_method" x-model="payoutMethod" required
                                            class="w-full rounded-xl border border-gray-200 text-sm px-3 py-2.5 focus:border-[#ffb3c1] focus:ring focus:ring-orange-100 bg-gray-50/50">
                                            <option value="wave">Wave</option>
                                            <option value="orange_money">Orange Money</option>
                                            <option value="mtn_money">MTN Money</option>
                                            <option value="moov_money">Moov Money</option>
                                            <option value="bank_transfer">Virement bancaire</option>
                                        </select>
                                        @error('payout_method')
                                            <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    {{-- Téléphone (Mobile Money) --}}
                                    <div x-show="!isBankTransfer" x-transition>
                                        <label class="block text-xs text-gray-500 font-medium mb-1">Numéro de téléphone</label>
                                        <input type="tel" name="phone_number" value="{{ old('phone_number', auth()->user()->phone) }}"
                                            class="w-full rounded-xl border border-gray-200 text-sm px-3 py-2.5 focus:border-[#ffb3c1] focus:ring focus:ring-orange-100 bg-gray-50/50"
                                            placeholder="+225 07 XX XX XX XX">
                                        @error('phone_number')
                                            <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    {{-- Banque --}}
                                    <div x-show="isBankTransfer" x-transition class="space-y-3">
                                        <div>
                                            <label class="block text-xs text-gray-500 font-medium mb-1">Nom de la banque</label>
                                            <input type="text" name="bank_name" value="{{ old('bank_name') }}"
                                                class="w-full rounded-xl border border-gray-200 text-sm px-3 py-2.5 focus:border-[#ffb3c1] focus:ring focus:ring-orange-100 bg-gray-50/50"
                                                placeholder="Ex: SGBCI, BICICI, Ecobank...">
                                            @error('bank_name')
                                                <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                                            @enderror
                                        </div>
                                        <div>
                                            <label class="block text-xs text-gray-500 font-medium mb-1">Numéro de compte / IBAN</label>
                                            <input type="text" name="bank_account" value="{{ old('bank_account') }}"
                                                class="w-full rounded-xl border border-gray-200 text-sm px-3 py-2.5 focus:border-[#ffb3c1] focus:ring focus:ring-orange-100 bg-gray-50/50"
                                                placeholder="CI XX XXXX XXXX XXXX XXXX XXXX">
                                            @error('bank_account')
                                                <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                                            @enderror
                                        </div>
                                    </div>

                                    {{-- PIN de retrait --}}
                                    <div>
                                        <label class="block text-xs text-gray-500 font-medium mb-1">PIN de retrait</label>
                                        <input type="password" name="withdrawal_pin" required maxlength="4" pattern="\d{4}" inputmode="numeric" autocomplete="off"
                                            class="w-full sm:w-48 rounded-xl border border-gray-200 text-lg px-3 py-2.5 focus:border-[#ffb3c1] focus:ring focus:ring-orange-100 bg-gray-50/50 tracking-[0.5em] text-center font-mono"
                                            placeholder="••••">
                                        @error('withdrawal_pin')
                                            <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    {{-- Avertissement sécurité --}}
                                    <div class="bg-gray-50 rounded-xl p-3 border border-gray-100">
                                        <div class="flex items-start gap-2">
                                            <svg class="w-4 h-4 text-gray-400 mt-0.5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                                            </svg>
                                            <p class="text-[11px] text-gray-500 leading-relaxed">
                                                Un email de confirmation sera envoyé à votre adresse. En cas de retrait non autorisé, contactez-nous immédiatement.
                                            </p>
                                        </div>
                                    </div>

                                    <button type="submit"
                                        class="w-full px-6 py-3 bg-green-600 text-white text-sm font-bold rounded-xl hover:bg-green-700 transition-colors">
                                        Confirmer le retrait
                                    </button>
                                </form>
                            </div>
                        @else
                            <div class="bg-gray-50 rounded-2xl border border-gray-100 p-5 text-center">
                                <p class="text-sm text-gray-500">Le montant minimum de retrait est de <strong>{{ number_format($minWithdrawal, 0, ',', ' ') }} FCFA</strong>.</p>
                                <p class="text-xs text-gray-400 mt-1">Votre solde disponible est de {{ $balance->formatted_available }}.</p>
                            </div>
                        @endif
                    @endif
                @endif
            </div>

            {{-- ============================== HISTORIQUE DES RETRAITS ============================== --}}
            @if ($payouts->isNotEmpty())
                <div class="mt-6 bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                    <div class="px-6 py-5 border-b border-gray-100">
                        <h2 class="text-xs font-bold text-gray-400 uppercase tracking-wider">Historique des retraits</h2>
                    </div>
                    <div class="divide-y divide-gray-100">
                        @foreach ($payouts as $payout)
                            <div class="px-6 py-4 flex items-center justify-between">
                                <div>
                                    <div class="flex items-center gap-2">
                                        <span class="text-sm font-bold text-gray-900">{{ number_format((float) $payout->net_amount, 0, ',', ' ') }} F</span>
                                        @php
                                            $badgeColor = match($payout->status) {
                                                'completed' => 'bg-green-50 text-green-700',
                                                'pending' => 'bg-yellow-50 text-yellow-700',
                                                'processing' => 'bg-blue-50 text-blue-700',
                                                'failed' => 'bg-red-50 text-red-700',
                                                default => 'bg-gray-50 text-gray-700',
                                            };
                                        @endphp
                                        <span class="text-[10px] font-medium px-2 py-0.5 rounded-full {{ $badgeColor }}">
                                            {{ $payout->status_label }}
                                        </span>
                                    </div>
                                    <p class="text-xs text-gray-400 mt-0.5">
                                        {{ $payout->reference }} &bull;
                                        {{ $payout->method_label }} &bull;
                                        {{ $payout->created_at->translatedFormat('d M Y H:i') }}
                                    </p>
                                    @if ($payout->status === 'failed' && $payout->failure_reason)
                                        <p class="text-xs text-red-500 mt-0.5">{{ $payout->failure_reason }}</p>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

        </div>
    </div>
@endsection
