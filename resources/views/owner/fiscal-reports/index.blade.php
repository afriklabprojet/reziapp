@extends('layouts.owner')

@section('title', 'Rapport fiscal — Rezi App')

@section('owner-content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Rapport fiscal</h1>
            <p class="text-sm text-gray-500 mt-1">Synthèse fiscale pour la Côte d'Ivoire</p>
        </div>
        <div class="flex items-center gap-3">
            <form method="GET" class="flex items-center gap-2" x-data>
                <select name="year" class="rounded-xl border-gray-200 text-sm py-2 px-3" @change="$el.closest('form').submit()">
                    @for($y = now()->year; $y >= now()->year - 3; $y--)
                        <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endfor
                </select>
            </form>
            <a href="{{ route('owner.fiscal-reports.export-pdf', ['year' => $year]) }}" class="inline-flex items-center gap-2 px-5 py-2.5 bg-gray-900 text-white font-semibold rounded-xl hover:bg-gray-800 transition-all text-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" /></svg>
                Exporter PDF
            </a>
        </div>
    </div>

    {{-- Key Metrics --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-2xl border border-gray-100 p-4">
            <p class="text-xs font-semibold text-gray-400 uppercase">Revenus bruts</p>
            <p class="text-2xl font-bold text-green-600 mt-1">{{ number_format($report['total_revenue'], 0, ',', ' ') }}</p>
            <p class="text-xs text-gray-400">FCFA</p>
        </div>
        <div class="bg-white rounded-2xl border border-gray-100 p-4">
            <p class="text-xs font-semibold text-gray-400 uppercase">Dépenses totales</p>
            <p class="text-2xl font-bold text-red-600 mt-1">{{ number_format($report['total_expenses'], 0, ',', ' ') }}</p>
            <p class="text-xs text-gray-400">FCFA</p>
        </div>
        <div class="bg-white rounded-2xl border border-gray-100 p-4">
            <p class="text-xs font-semibold text-gray-400 uppercase">Résultat net</p>
            <p class="text-2xl font-bold {{ $report['net_income'] >= 0 ? 'text-green-600' : 'text-red-600' }} mt-1">{{ number_format($report['net_income'], 0, ',', ' ') }}</p>
            <p class="text-xs text-gray-400">FCFA</p>
        </div>
        <div class="bg-white rounded-2xl border border-gray-100 p-4">
            <p class="text-xs font-semibold text-gray-400 uppercase">Impôts estimés</p>
            <p class="text-2xl font-bold text-gray-900 mt-1">{{ number_format($report['impot_foncier'] + $report['tva'], 0, ',', ' ') }}</p>
            <p class="text-xs text-gray-400">FCFA</p>
        </div>
    </div>

    {{-- Tax Breakdown --}}
    <div class="bg-white rounded-2xl border border-gray-100 p-6">
        <h2 class="font-semibold text-gray-900 mb-4">Fiscalité ivoirienne</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="p-4 bg-blue-50 rounded-xl">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-semibold text-blue-800">Impôt foncier (15%)</p>
                        <p class="text-xs text-blue-600 mt-0.5">Sur le revenu net</p>
                    </div>
                    <p class="text-lg font-bold text-blue-900">{{ number_format($report['impot_foncier'], 0, ',', ' ') }} <span class="text-xs font-normal">FCFA</span></p>
                </div>
            </div>
            <div class="p-4 bg-purple-50 rounded-xl">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-semibold text-purple-800">TVA (18%)</p>
                        <p class="text-xs text-purple-600 mt-0.5">Sur le chiffre d'affaires</p>
                    </div>
                    <p class="text-lg font-bold text-purple-900">{{ number_format($report['tva'], 0, ',', ' ') }} <span class="text-xs font-normal">FCFA</span></p>
                </div>
            </div>
        </div>
    </div>

    {{-- Monthly Breakdown --}}
    <div class="bg-white rounded-2xl border border-gray-100 p-6">
        <h2 class="font-semibold text-gray-900 mb-4">Répartition mensuelle</h2>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-100">
                        <th class="text-left py-2 text-xs font-semibold text-gray-500 uppercase">Mois</th>
                        <th class="text-right py-2 text-xs font-semibold text-gray-500 uppercase">Revenus</th>
                        <th class="text-right py-2 text-xs font-semibold text-gray-500 uppercase">Dépenses</th>
                        <th class="text-right py-2 text-xs font-semibold text-gray-500 uppercase">Net</th>
                    </tr>
                </thead>
                <tbody>
                    @php $months = ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Jun', 'Jul', 'Aoû', 'Sep', 'Oct', 'Nov', 'Déc']; @endphp
                    @foreach($report['by_month'] as $m => $data)
                    <tr class="border-b border-gray-50">
                        <td class="py-2 font-medium text-gray-700">{{ $months[$m - 1] ?? $m }}</td>
                        <td class="py-2 text-right text-green-600">{{ number_format($data['revenue'], 0, ',', ' ') }}</td>
                        <td class="py-2 text-right text-red-600">{{ number_format($data['expenses'], 0, ',', ' ') }}</td>
                        @php $net = $data['revenue'] - $data['expenses']; @endphp
                        <td class="py-2 text-right font-semibold {{ $net >= 0 ? 'text-green-700' : 'text-red-700' }}">{{ number_format($net, 0, ',', ' ') }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- By Residence --}}
    @if(!empty($report['by_residence']))
    <div class="bg-white rounded-2xl border border-gray-100 p-6">
        <h2 class="font-semibold text-gray-900 mb-4">Par résidence</h2>
        <div class="space-y-3">
            @foreach($report['by_residence'] as $res)
            <div class="flex items-center justify-between py-2 {{ !$loop->last ? 'border-b border-gray-50' : '' }}">
                <span class="text-sm font-medium text-gray-700">{{ $res['name'] }}</span>
                <div class="text-right">
                    <span class="text-sm font-semibold text-green-600">{{ number_format($res['revenue'], 0, ',', ' ') }}</span>
                    <span class="text-xs text-gray-400 mx-1">|</span>
                    <span class="text-sm font-semibold text-red-600">{{ number_format($res['expenses'], 0, ',', ' ') }}</span>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- By Category --}}
    @if(!empty($report['expenses_by_category']))
    <div class="bg-white rounded-2xl border border-gray-100 p-6">
        <h2 class="font-semibold text-gray-900 mb-4">Dépenses par catégorie</h2>
        <div class="space-y-2">
            @foreach($report['expenses_by_category'] as $cat)
            @php $pct = $report['total_expenses'] > 0 ? ($cat['total'] / $report['total_expenses']) * 100 : 0; @endphp
            <div>
                <div class="flex items-center justify-between mb-1">
                    <span class="text-sm text-gray-700">{{ $cat['category'] }}</span>
                    <span class="text-sm font-semibold text-gray-700">{{ number_format($cat['total'], 0, ',', ' ') }} FCFA</span>
                </div>
                <div class="bg-gray-100 rounded-full h-2 overflow-hidden">
                    <div class="bg-red-400 h-full rounded-full" style="width: {{ round($pct) }}%"></div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif
</div>
@endsection
