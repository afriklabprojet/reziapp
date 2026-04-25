@extends('layouts.owner')

@section('title', 'Analytics - Tableau de bord')

@section('owner-content')
    <div class="space-y-6" x-data="analyticsPage(@js([
    'revenueData' => $stats['revenue']['daily'],
    'viewsData' => $stats['views']['daily'],
    'contactsData' => $stats['contacts']['daily'],
]))">
        <!-- En-tête avec sélecteur de période -->
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">
                    <span class="inline-flex items-center gap-2">
                        <svg class="w-7 h-7 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                        Analytics & Performance
                    </span>
                </h1>
                <p class="text-gray-600 mt-1">Suivez vos performances en temps réel</p>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <!-- Périodes rapides -->
                <div class="flex bg-gray-100 rounded-lg p-1">
                    @foreach ($quickPeriods as $key => $period)
                        <a href="{{ route('owner.analytics.index', ['period' => $key]) }}"
                            class="px-3 py-1.5 text-sm rounded-md transition {{ request('period', 'month') === $key ? 'bg-white text-orange-500 shadow-sm' : 'text-gray-600 hover:text-gray-900' }}">
                            {{ $period['label'] }}
                        </a>
                    @endforeach
                </div>

                <!-- Export -->
                <div class="relative" x-data="{ open: false }">
                    <button @click="open = !open"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                        </svg>
                        Exporter
                    </button>
                    <div x-show="open" @click.away="open = false" x-transition
                        class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border border-gray-200 py-1 z-10">
                        <a href="{{ route('owner.analytics.export.pdf', ['start_date' => $startDate->format('Y-m-d'), 'end_date' => $endDate->format('Y-m-d')]) }}"
                            class="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                            <svg class="w-4 h-4 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" />
                            </svg>
                            Export PDF
                        </a>
                        <a href="{{ route('owner.analytics.export.excel', ['start_date' => $startDate->format('Y-m-d'), 'end_date' => $endDate->format('Y-m-d'), 'type' => 'detailed']) }}"
                            class="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                            <svg class="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" />
                            </svg>
                            Export Excel
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Période sélectionnée -->
        <div class="text-sm text-gray-500">
            Période: <span class="font-medium text-gray-900">{{ $startDate->locale('fr')->isoFormat('D MMMM YYYY') }}</span>
            au <span class="font-medium text-gray-900">{{ $endDate->locale('fr')->isoFormat('D MMMM YYYY') }}</span>
        </div>

        <!-- KPIs principaux -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Revenus -->
            <div class="bg-white rounded-2xl shadow-sm p-6 border border-gray-100">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-orange-100 rounded-xl flex items-center justify-center">
                        <svg class="w-6 h-6 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    @if ($stats['revenue']['change'] != 0)
                        <span
                            class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium {{ $stats['revenue']['change'] > 0 ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                            @if ($stats['revenue']['change'] > 0)
                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M5 10l7-7m0 0l7 7m-7-7v18" />
                                </svg>
                            @else
                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 14l-7 7m0 0l-7-7m7 7V3" />
                                </svg>
                            @endif
                            {{ abs($stats['revenue']['change']) }}%
                        </span>
                    @endif
                </div>
                <p class="text-sm text-gray-500 mb-1">Revenus confirmés</p>
                <p class="text-2xl font-bold text-gray-900">{{ number_format($stats['revenue']['confirmed'], 0, ',', ' ') }}
                    <span class="text-sm font-normal">FCFA</span></p>
                <p class="text-xs text-gray-400 mt-2">Estimés:
                    {{ number_format($stats['revenue']['estimated'], 0, ',', ' ') }} FCFA</p>
            </div>

            <!-- Taux d'occupation -->
            <div class="bg-white rounded-2xl shadow-sm p-6 border border-gray-100">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <div class="text-right">
                        <span
                            class="text-xs text-gray-400">{{ $occupancy['occupied_days'] }}/{{ $occupancy['available_days'] }}
                            jours</span>
                    </div>
                </div>
                <p class="text-sm text-gray-500 mb-1">Taux d'occupation</p>
                <div class="flex items-end gap-2">
                    <p class="text-2xl font-bold text-gray-900">{{ $occupancy['rate'] }}%</p>
                    <div class="flex-1 h-2 bg-gray-100 rounded-full overflow-hidden mb-2">
                        <div class="h-full bg-blue-500 rounded-full" style="width: {{ min($occupancy['rate'], 100) }}%">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Vues -->
            <div class="bg-white rounded-2xl shadow-sm p-6 border border-gray-100">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-purple-100 rounded-xl flex items-center justify-center">
                        <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        </svg>
                    </div>
                    @if ($stats['overview']['views_change'] != 0)
                        <span
                            class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium {{ $stats['overview']['views_change'] > 0 ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                            {{ $stats['overview']['views_change'] > 0 ? '+' : '' }}{{ $stats['overview']['views_change'] }}%
                        </span>
                    @endif
                </div>
                <p class="text-sm text-gray-500 mb-1">Vues totales</p>
                <p class="text-2xl font-bold text-gray-900">
                    {{ number_format($stats['overview']['total_views'], 0, ',', ' ') }}</p>
                <p class="text-xs text-gray-400 mt-2">{{ $stats['views']['unique_visitors'] ?? 0 }} visiteurs uniques</p>
            </div>

            <!-- Conversion -->
            <div class="bg-white rounded-2xl shadow-sm p-6 border border-gray-100">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-orange-100 rounded-xl flex items-center justify-center">
                        <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                        </svg>
                    </div>
                </div>
                <p class="text-sm text-gray-500 mb-1">Taux de conversion</p>
                <p class="text-2xl font-bold text-gray-900">{{ $stats['conversion']['overall'] }}%</p>
                <div class="flex items-center gap-4 mt-2 text-xs text-gray-400">
                    <span>Vues→Contacts: {{ $stats['conversion']['view_to_contact'] }}%</span>
                </div>
            </div>
        </div>

        <!-- Graphiques -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Graphique des revenus -->
            <div class="bg-white rounded-2xl shadow-sm p-6 border border-gray-100">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="font-semibold text-gray-900">Évolution des revenus</h3>
                    <div class="flex items-center gap-2 text-sm">
                        <span class="inline-flex items-center gap-1">
                            <span class="w-3 h-3 bg-orange-500 rounded-full"></span>
                            Revenus
                        </span>
                    </div>
                </div>
                <div class="h-64">
                    <canvas id="revenueChart"></canvas>
                </div>
            </div>

            <!-- Graphique des vues -->
            <div class="bg-white rounded-2xl shadow-sm p-6 border border-gray-100">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="font-semibold text-gray-900">Vues & Contacts</h3>
                    <div class="flex items-center gap-4 text-sm">
                        <span class="inline-flex items-center gap-1">
                            <span class="w-3 h-3 bg-purple-500 rounded-full"></span>
                            Vues
                        </span>
                        <span class="inline-flex items-center gap-1">
                            <span class="w-3 h-3 bg-orange-500 rounded-full"></span>
                            Contacts
                        </span>
                    </div>
                </div>
                <div class="h-64">
                    <canvas id="viewsChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Funnel de conversion & Occupation -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Funnel -->
            <div class="bg-white rounded-2xl shadow-sm p-6 border border-gray-100">
                <h3 class="font-semibold text-gray-900 mb-6">Funnel de conversion</h3>
                <div class="space-y-4">
                    @foreach ($stats['conversion']['funnel'] as $index => $stage)
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <span class="text-sm text-gray-600">{{ $stage['stage'] }}</span>
                                <span
                                    class="text-sm font-medium text-gray-900">{{ number_format($stage['count'], 0, ',', ' ') }}</span>
                            </div>
                            <div class="h-8 bg-gray-100 rounded-lg overflow-hidden relative">
                                <div class="h-full {{ $index === 0 ? 'bg-purple-500' : ($index === 1 ? 'bg-blue-500' : 'bg-orange-500') }} rounded-lg transition-all duration-500"
                                    style="width: {{ $stage['rate'] }}%">
                                </div>
                                <span
                                    class="absolute inset-0 flex items-center justify-center text-xs font-medium {{ $stage['rate'] > 50 ? 'text-white' : 'text-gray-600' }}">
                                    {{ $stage['rate'] }}%
                                </span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Occupation par jour -->
            <div class="bg-white rounded-2xl shadow-sm p-6 border border-gray-100">
                <h3 class="font-semibold text-gray-900 mb-6">Occupation par jour de la semaine</h3>
                <div class="grid grid-cols-7 gap-2">
                    @foreach ($occupancy['by_day_of_week'] as $day)
                        <div class="text-center">
                            <div class="text-xs text-gray-500 mb-2">{{ $day['short'] }}</div>
                            <div class="relative w-full pt-full">
                                <div class="absolute inset-0 flex items-center justify-center">
                                    <div
                                        class="w-10 h-10 rounded-full flex items-center justify-center text-xs font-medium
                                {{ $day['rate'] >= 70 ? 'bg-orange-500 text-white' : ($day['rate'] >= 40 ? 'bg-orange-200 text-orange-700' : 'bg-gray-100 text-gray-600') }}">
                                        {{ $day['rate'] }}%
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
                <div class="mt-4 flex items-center justify-center gap-4 text-xs text-gray-500">
                    <span class="inline-flex items-center gap-1">
                        <span class="w-3 h-3 bg-gray-100 rounded-full"></span> &lt;40%
                    </span>
                    <span class="inline-flex items-center gap-1">
                        <span class="w-3 h-3 bg-orange-200 rounded-full"></span> 40-70%
                    </span>
                    <span class="inline-flex items-center gap-1">
                        <span class="w-3 h-3 bg-orange-500 rounded-full"></span> &gt;70%
                    </span>
                </div>
            </div>
        </div>

        <!-- Top résidences -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="p-6 border-b border-gray-100">
                <h3 class="font-semibold text-gray-900">Top résidences</h3>
                <p class="text-sm text-gray-500">Performances de vos résidences sur la période</p>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Résidence</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Vues</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Contacts</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Conversion</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Revenus</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($stats['top_residences'] as $residence)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        @if ($residence['photo'])
                                            <img loading="lazy" src="{{ $residence['photo'] }}" alt=""
                                                class="w-10 h-10 rounded-lg object-cover">
                                        @else
                                            <div class="w-10 h-10 bg-gray-200 rounded-lg flex items-center justify-center">
                                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                                                </svg>
                                            </div>
                                        @endif
                                        <div>
                                            <p class="font-medium text-gray-900">{{ Str::limit($residence['name'], 30) }}
                                            </p>
                                            <p class="text-xs text-gray-500">{{ $residence['commune'] }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-center text-sm text-gray-600">
                                    {{ number_format($residence['views'], 0, ',', ' ') }}</td>
                                <td class="px-6 py-4 text-center text-sm text-gray-600">
                                    {{ number_format($residence['contacts'], 0, ',', ' ') }}</td>
                                <td class="px-6 py-4 text-center">
                                    <span
                                        class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium
                                {{ $residence['conversion'] >= 5 ? 'bg-green-100 text-green-800' : ($residence['conversion'] >= 2 ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-800') }}">
                                        {{ $residence['conversion'] }}%
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right text-sm font-medium text-gray-900">
                                    {{ number_format($residence['revenue'], 0, ',', ' ') }} F
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-12 text-center text-gray-500">
                                    Aucune donnée pour cette période
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Actions rapides -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <a href="{{ route('owner.analytics.fiscal') }}"
                class="bg-white rounded-xl p-4 border border-gray-200 hover:border-orange-300 hover:shadow-md transition flex items-center gap-4">
                <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                </div>
                <div>
                    <p class="font-medium text-gray-900">Historique fiscal</p>
                    <p class="text-sm text-gray-500">Récapitulatif annuel</p>
                </div>
            </a>

            <a href="{{ route('owner.analytics.export.pdf', ['start_date' => $startDate->format('Y-m-d'), 'end_date' => $endDate->format('Y-m-d')]) }}"
                class="bg-white rounded-xl p-4 border border-gray-200 hover:border-orange-300 hover:shadow-md transition flex items-center gap-4">
                <div class="w-10 h-10 bg-red-100 rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 text-red-600" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                            d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" />
                    </svg>
                </div>
                <div>
                    <p class="font-medium text-gray-900">Rapport PDF</p>
                    <p class="text-sm text-gray-500">Télécharger le rapport</p>
                </div>
            </a>

            <a href="{{ route('owner.pricing.index', $firstResidenceId) }}"
                class="bg-white rounded-xl p-4 border border-gray-200 hover:border-orange-300 hover:shadow-md transition flex items-center gap-4">
                <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                    </svg>
                </div>
                <div>
                    <p class="font-medium text-gray-900">Prix dynamiques</p>
                    <p class="text-sm text-gray-500">Optimiser les tarifs</p>
                </div>
            </a>
        </div>
    </div>

    @push('scripts')
        @vite('resources/js/chart.js')
    @endpush
@endsection
