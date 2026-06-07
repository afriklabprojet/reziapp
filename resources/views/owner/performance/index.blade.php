@extends('layouts.owner')

@section('title', 'Tableau de performance — Rezi Studio Meublé Faya')

@section('owner-content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Tableau de performance</h1>
            <p class="text-sm text-gray-500 mt-1">Analysez vos KPIs et comparez-vous à la zone</p>
        </div>
        <select id="residence-filter" class="rounded-xl border-gray-200 text-sm py-2 px-3" onchange="window.location=this.value">
            <option value="{{ route('owner.performance.index') }}">Toutes mes résidences</option>
            @foreach($residences as $r)
            <option value="{{ route('owner.performance.index', ['residence_id' => $r->id]) }}" {{ request('residence_id') == $r->id ? 'selected' : '' }}>{{ $r->name }}</option>
            @endforeach
        </select>
    </div>

    {{-- KPIs --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white rounded-2xl border border-gray-100 p-5">
            <p class="text-xs font-semibold text-gray-500 uppercase">RevPAR</p>
            <p class="text-2xl font-bold text-gray-900 mt-1">{{ number_format($kpis['revpar'] ?? 0, 0, ',', ' ') }} <span class="text-sm font-normal text-gray-500">F</span></p>
            <p class="text-xs text-gray-400 mt-1">Revenu par unité dispo</p>
        </div>
        <div class="bg-white rounded-2xl border border-gray-100 p-5">
            <p class="text-xs font-semibold text-gray-500 uppercase">ADR</p>
            <p class="text-2xl font-bold text-gray-900 mt-1">{{ number_format($kpis['adr'] ?? 0, 0, ',', ' ') }} <span class="text-sm font-normal text-gray-500">F</span></p>
            <p class="text-xs text-gray-400 mt-1">Tarif journalier moyen</p>
        </div>
        <div class="bg-white rounded-2xl border border-gray-100 p-5">
            <p class="text-xs font-semibold text-gray-500 uppercase">Taux d'occupation</p>
            <p class="text-2xl font-bold text-gray-900 mt-1">{{ number_format($kpis['occupancy'] ?? 0, 1) }} <span class="text-sm font-normal text-gray-500">%</span></p>
            <p class="text-xs text-gray-400 mt-1">Nuits réservées / dispo</p>
        </div>
        <div class="bg-white rounded-2xl border border-gray-100 p-5">
            <p class="text-xs font-semibold text-gray-500 uppercase">Note moyenne</p>
            <p class="text-2xl font-bold text-gray-900 mt-1">{{ number_format($kpis['avg_rating'] ?? 0, 1) }} <span class="text-sm font-normal text-gray-500">/5</span></p>
            <p class="text-xs text-gray-400 mt-1">Satisfaction voyageurs</p>
        </div>
    </div>

    {{-- Charts Row --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white rounded-2xl border border-gray-100 p-6">
            <h2 class="text-lg font-bold text-gray-900 mb-4">Revenus mensuels</h2>
            <canvas id="revenue-chart" height="200"></canvas>
        </div>
        <div class="bg-white rounded-2xl border border-gray-100 p-6">
            <h2 class="text-lg font-bold text-gray-900 mb-4">Taux d'occupation</h2>
            <canvas id="occupancy-chart" height="200"></canvas>
        </div>
    </div>

    {{-- Benchmark --}}
    <div class="bg-white rounded-2xl border border-gray-100 p-6">
        <h2 class="text-lg font-bold text-gray-900 mb-4">Comparaison avec la zone</h2>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="text-left text-xs font-semibold text-gray-500 uppercase px-4 py-3">Indicateur</th>
                        <th class="text-right text-xs font-semibold text-gray-500 uppercase px-4 py-3">Vous</th>
                        <th class="text-right text-xs font-semibold text-gray-500 uppercase px-4 py-3">Moyenne zone</th>
                        <th class="text-right text-xs font-semibold text-gray-500 uppercase px-4 py-3">Écart</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($benchmark ?? [] as $item)
                    <tr>
                        <td class="px-4 py-3 text-sm text-gray-900">{{ $item['label'] }}</td>
                        <td class="px-4 py-3 text-sm text-gray-900 text-right font-semibold">{{ $item['yours'] }}</td>
                        <td class="px-4 py-3 text-sm text-gray-600 text-right">{{ $item['zone'] }}</td>
                        <td class="px-4 py-3 text-sm text-right {{ $item['diff'] >= 0 ? 'text-green-600' : 'text-red-600' }}">{{ $item['diff'] >= 0 ? '+' : '' }}{{ $item['diff'] }}%</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const revenueData = @json($revenueChart ?? ['labels' => [], 'data' => []]);
    const occupancyData = @json($occupancyChart ?? ['labels' => [], 'data' => []]);

    new Chart(document.getElementById('revenue-chart'), {
        type: 'bar',
        data: {
            labels: revenueData.labels,
            datasets: [{
                label: 'Revenus (FCFA)',
                data: revenueData.data,
                backgroundColor: 'rgba(17, 24, 39, 0.8)',
                borderRadius: 6
            }]
        },
        options: { responsive: true, plugins: { legend: { display: false } } }
    });

    new Chart(document.getElementById('occupancy-chart'), {
        type: 'line',
        data: {
            labels: occupancyData.labels,
            datasets: [{
                label: 'Occupation (%)',
                data: occupancyData.data,
                borderColor: '#10B981',
                backgroundColor: 'rgba(16, 185, 129, 0.1)',
                fill: true,
                tension: 0.4
            }]
        },
        options: { responsive: true, plugins: { legend: { display: false } }, scales: { y: { min: 0, max: 100 } } }
    });
});
</script>
@endpush
@endsection
