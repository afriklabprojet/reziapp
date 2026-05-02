@extends('layouts.app')

@section('title', 'Mes factures')

@section('content')
<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <!-- En-tête -->
        <div class="mb-8">
            <h1 class="text-2xl font-bold text-gray-900">Mes factures</h1>
            <p class="mt-1 text-sm text-gray-500">Consultez et téléchargez vos factures</p>
        </div>

        <!-- Statistiques -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
                <div class="flex items-center">
                    <div class="shrink-0 w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Total factures</p>
                        <p class="text-2xl font-bold text-gray-900">{{ $stats['total_invoices'] }}</p>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
                <div class="flex items-center">
                    <div class="shrink-0 w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Payées</p>
                        <p class="text-2xl font-bold text-gray-900">{{ $stats['by_status']['paid'] ?? 0 }}</p>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
                <div class="flex items-center">
                    <div class="shrink-0 w-10 h-10 bg-yellow-100 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">En attente</p>
                        <p class="text-2xl font-bold text-gray-900">{{ number_format($stats['pending_amount'], 0, ',', ' ') }} <span class="text-sm font-normal">FCFA</span></p>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
                <div class="flex items-center">
                    <div class="shrink-0 w-10 h-10 bg-[#ffd1da] rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-[#e00b41]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Total payé</p>
                        <p class="text-2xl font-bold text-gray-900">{{ number_format($stats['total_amount'], 0, ',', ' ') }} <span class="text-sm font-normal">FCFA</span></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filtres -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 mb-6">
            <form method="GET" class="grid grid-cols-2 sm:grid-cols-5 gap-3 items-end">
                <div class="col-span-2 sm:col-span-1">
                    <label class="block text-xs font-medium text-gray-700 mb-1">Recherche</label>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="N° facture, nom..." 
                           class="w-full rounded-lg border-gray-300 text-sm focus:ring-[#ff385c] focus:border-[#ff385c]">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Statut</label>
                    <select name="status" class="w-full rounded-lg border-gray-300 text-sm focus:ring-[#ff385c] focus:border-[#ff385c]">
                        <option value="">Tous</option>
                        <option value="paid" {{ request('status') === 'paid' ? 'selected' : '' }}>Payée</option>
                        <option value="sent" {{ request('status') === 'sent' ? 'selected' : '' }}>Envoyée</option>
                        <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>Brouillon</option>
                        <option value="overdue" {{ request('status') === 'overdue' ? 'selected' : '' }}>En retard</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Du</label>
                    <input type="date" name="from" value="{{ request('from') }}" 
                           class="w-full rounded-lg border-gray-300 text-sm focus:ring-[#ff385c] focus:border-[#ff385c]">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Au</label>
                    <input type="date" name="to" value="{{ request('to') }}" 
                           class="w-full rounded-lg border-gray-300 text-sm focus:ring-[#ff385c] focus:border-[#ff385c]">
                </div>
                <button type="submit" class="col-span-2 sm:col-span-1 px-4 py-2 bg-[#e00b41] text-white rounded-lg text-sm font-medium hover:bg-[#b5083a]">
                    Filtrer
                </button>
            </form>
        </div>

        <!-- Liste des factures -->
        @if($invoices->count() > 0)
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Facture</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Montant</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Statut</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($invoices as $invoice)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">{{ $invoice->invoice_number }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">{{ $invoice->invoice_date->format('d/m/Y') }}</div>
                            </td>
                            <td class="px-6 py-4">
                                @if($invoice->booking && $invoice->booking->residence)
                                <div class="text-sm text-gray-900">Réservation</div>
                                <div class="text-xs text-gray-500">{{ Str::limit($invoice->booking->residence->title, 30) }}</div>
                                @else
                                <div class="text-sm text-gray-900">{{ $invoice->line_items[0]['description'] ?? 'Facture' }}</div>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-semibold text-gray-900">{{ $invoice->formatted_total }}</div>
                                @if($invoice->tax_amount > 0)
                                <div class="text-xs text-gray-500">dont {{ $invoice->formatted_tax }} TVA</div>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @php
                                    $statusColors = [
                                        'green' => 'bg-green-100 text-green-800',
                                        'yellow' => 'bg-yellow-100 text-yellow-800',
                                        'red' => 'bg-red-100 text-red-800',
                                        'blue' => 'bg-blue-100 text-blue-800',
                                        'gray' => 'bg-gray-100 text-gray-800',
                                    ];
                                @endphp
                                <span class="px-2.5 py-1 text-xs font-medium rounded-full {{ $statusColors[$invoice->status_color] ?? 'bg-gray-100 text-gray-800' }}">
                                    {{ $invoice->status_label }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                                <div class="flex items-center justify-end space-x-2">
                                    <a href="{{ route('invoices.view', $invoice) }}" 
                                       class="text-[#e00b41] hover:text-primary-800" title="Voir">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                    </a>
                                    <a href="{{ route('invoices.download', $invoice) }}" 
                                       class="text-gray-600 hover:text-gray-800" title="Télécharger">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                        </svg>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            @if($invoices->hasPages())
            <div class="px-6 py-4 border-t border-gray-200">
                {{ $invoices->withQueryString()->links() }}
            </div>
            @endif
        </div>
        @else
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-12 text-center">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            <h3 class="mt-4 text-lg font-medium text-gray-900">Aucune facture</h3>
            <p class="mt-2 text-sm text-gray-500">Vos factures apparaîtront ici après vos paiements.</p>
        </div>
        @endif

    </div>
</div>
@endsection
