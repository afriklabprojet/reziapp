@extends('layouts.owner')

@section('title', 'Historique fiscal - ' . $year)

@section('owner-content')
<div class="space-y-6">
    <!-- En-tête -->
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
        <div>
            <nav class="text-sm text-gray-500 mb-2">
                <a href="{{ route('owner.analytics.index') }}" class="hover:text-[#F16A00]">Analytics</a>
                <span class="mx-2">›</span>
                <span class="text-gray-700">Historique fiscal</span>
            </nav>
            <h1 class="text-2xl font-bold text-gray-900">
                <span class="inline-flex items-center gap-2">
                    <svg class="w-7 h-7 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    Récapitulatif fiscal {{ $year }}
                </span>
            </h1>
            <p class="text-gray-600 mt-1">Synthèse annuelle pour vos déclarations</p>
        </div>
        
        <div class="flex items-center gap-3">
            <!-- Sélecteur d'année -->
            <form method="GET" action="{{ route('owner.analytics.fiscal') }}" class="flex items-center gap-2" x-data>
                <label for="year" class="text-sm text-gray-600">Année:</label>
                <select name="year" id="year" @change="$el.closest('form').submit()" 
                        class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-[#F16A00] focus:border-[#F16A00]">
                    @foreach($availableYears as $y)
                    <option value="{{ $y }}" {{ $y == $year ? 'selected' : '' }}>{{ $y }}</option>
                    @endforeach
                </select>
            </form>
            
            <!-- Export PDF -->
            <a href="{{ route('owner.analytics.export.fiscal-pdf', ['year' => $year]) }}" 
               class="inline-flex items-center gap-2 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z"/>
                </svg>
                Télécharger PDF
            </a>
        </div>
    </div>

    <!-- Informations propriétaire -->
    <div class="bg-white rounded-2xl shadow-sm p-6 border border-gray-100">
        <div class="flex items-center gap-4 mb-4">
            <div class="w-12 h-12 bg-[#FFE7D1] rounded-xl flex items-center justify-center">
                <svg class="w-6 h-6 text-[#F16A00]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
            </div>
            <div>
                <h2 class="font-semibold text-gray-900">{{ $fiscalData['owner']['name'] }}</h2>
                <p class="text-sm text-gray-500">{{ $fiscalData['owner']['email'] }}</p>
            </div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
            <div>
                <span class="text-gray-500">Téléphone:</span>
                <span class="font-medium ml-2">{{ $fiscalData['owner']['phone'] ?? 'Non renseigné' }}</span>
            </div>
            <div>
                <span class="text-gray-500">Année fiscale:</span>
                <span class="font-medium ml-2">{{ $year }}</span>
            </div>
            <div>
                <span class="text-gray-500">Généré le:</span>
                <span class="font-medium ml-2">{{ $fiscalData['generated_at'] }}</span>
            </div>
        </div>
    </div>

    <!-- Résumé annuel -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-linear-to-br from-[#F16A00] to-[#F16A00] rounded-2xl p-6 text-white">
            <p class="text-[#FFE7D1] text-sm">Revenus bruts annuels</p>
            <p class="text-3xl font-bold mt-2">{{ number_format($fiscalData['totals']['revenue'], 0, ',', ' ') }}</p>
            <p class="text-[#FFE7D1] text-sm mt-1">FCFA</p>
        </div>
        
        <div class="bg-white rounded-2xl shadow-sm p-6 border border-gray-100">
            <p class="text-gray-500 text-sm">Réservations totales</p>
            <p class="text-3xl font-bold text-gray-900 mt-2">{{ $fiscalData['totals']['bookings'] }}</p>
            <p class="text-gray-400 text-sm mt-1">sur l'année</p>
        </div>
        
        <div class="bg-white rounded-2xl shadow-sm p-6 border border-gray-100">
            <p class="text-gray-500 text-sm">Moyenne mensuelle</p>
            <p class="text-3xl font-bold text-gray-900 mt-2">{{ number_format($fiscalData['totals']['average_per_month'], 0, ',', ' ') }}</p>
            <p class="text-gray-400 text-sm mt-1">FCFA</p>
        </div>
        
        <div class="bg-amber-50 rounded-2xl p-6 border border-amber-200">
            <p class="text-amber-700 text-sm">Taxe de séjour estimée</p>
            <p class="text-3xl font-bold text-amber-800 mt-2">{{ number_format($fiscalData['fiscal']['taxe_sejour_amount'], 0, ',', ' ') }}</p>
            <p class="text-amber-600 text-sm mt-1">FCFA ({{ $fiscalData['fiscal']['taxe_sejour_rate'] }})</p>
        </div>
    </div>

    <!-- Graphique mensuel -->
    <div class="bg-white rounded-2xl shadow-sm p-6 border border-gray-100">
        <h3 class="font-semibold text-gray-900 mb-6">Évolution mensuelle des revenus</h3>
        <div class="h-64">
            <canvas id="monthlyRevenueChart"></canvas>
        </div>
    </div>

    <!-- Tableau des revenus mensuels -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="p-6 border-b border-gray-100">
            <h3 class="font-semibold text-gray-900">Détail mensuel</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Mois</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Réservations</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Revenus bruts</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Taxe séjour (5%)</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Revenus nets</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($fiscalData['monthly'] as $month)
                    <tr class="hover:bg-gray-50 {{ $month['revenue'] > 0 ? '' : 'text-gray-400' }}">
                        <td class="px-6 py-4 font-medium">{{ ucfirst($month['month']) }}</td>
                        <td class="px-6 py-4 text-center">{{ $month['bookings'] }}</td>
                        <td class="px-6 py-4 text-right">{{ number_format($month['revenue'], 0, ',', ' ') }} F</td>
                        <td class="px-6 py-4 text-right text-amber-600">{{ number_format($month['revenue'] * 0.05, 0, ',', ' ') }} F</td>
                        <td class="px-6 py-4 text-right font-medium">{{ number_format($month['revenue'] * 0.95, 0, ',', ' ') }} F</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot class="bg-[#FFF4EB]">
                    <tr class="font-bold">
                        <td class="px-6 py-4">TOTAL {{ $year }}</td>
                        <td class="px-6 py-4 text-center">{{ $fiscalData['totals']['bookings'] }}</td>
                        <td class="px-6 py-4 text-right">{{ number_format($fiscalData['fiscal']['gross_revenue'], 0, ',', ' ') }} F</td>
                        <td class="px-6 py-4 text-right text-amber-600">{{ number_format($fiscalData['fiscal']['taxe_sejour_amount'], 0, ',', ' ') }} F</td>
                        <td class="px-6 py-4 text-right text-[#CC5A00]">{{ number_format($fiscalData['fiscal']['net_revenue'], 0, ',', ' ') }} F</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <!-- Avertissement -->
    <div class="bg-blue-50 rounded-xl p-4 flex items-start gap-3">
        <svg class="w-5 h-5 text-blue-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <div class="text-sm text-blue-800">
            <p class="font-medium">Information importante</p>
            <p class="mt-1">
                Ce récapitulatif est fourni à titre indicatif. La taxe de séjour est calculée à un taux estimatif de 5%. 
                Pour vos déclarations fiscales officielles, veuillez consulter un expert-comptable ou les services fiscaux 
                de Côte d'Ivoire pour connaître les taux et obligations en vigueur.
            </p>
        </div>
    </div>

    <!-- Actions -->
    <div class="flex justify-center gap-4">
        <a href="{{ route('owner.analytics.index') }}" class="px-6 py-3 border border-gray-300 rounded-xl hover:bg-gray-50 transition">
            Retour aux analytics
        </a>
        <a href="{{ route('owner.analytics.export.fiscal-pdf', ['year' => $year]) }}" 
           class="px-6 py-3 bg-[#F16A00] text-white rounded-xl hover:bg-[#CC5A00] transition inline-flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
            </svg>
            Télécharger le récapitulatif complet
        </a>
    </div>
</div>

@push('scripts')
@vite('resources/js/chart.js')
<script>
document.addEventListener('DOMContentLoaded', function() {
    window.initFiscalChart(@json($fiscalData['monthly']));
});
</script>
@endpush
@endsection
