@extends('layouts.app')

@section('title', 'Historique des paiements')

@section('content')
    <div class="min-h-screen bg-gray-50 py-8">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">

            <!-- En-tête -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-8">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Historique des paiements</h1>
                    <p class="mt-1 text-sm text-gray-500">Retrouvez tous vos paiements et factures</p>
                </div>
                <a href="{{ route('payments.methods') }}"
                    class="mt-4 sm:mt-0 inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                    </svg>
                    Méthodes de paiement
                </a>
            </div>

            <!-- Filtres -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 mb-6">
                <form method="GET" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Statut</label>
                        <select name="status"
                            class="w-full rounded-lg border-gray-300 text-sm focus:ring-[#F16A00] focus:border-[#F16A00]">
                            <option value="">Tous les statuts</option>
                            <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Complétés
                            </option>
                            <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>En attente
                            </option>
                            <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>Échoués</option>
                            <option value="refunded" {{ request('status') === 'refunded' ? 'selected' : '' }}>Remboursés
                            </option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Type</label>
                        <select name="type"
                            class="w-full rounded-lg border-gray-300 text-sm focus:ring-[#F16A00] focus:border-[#F16A00]">
                            <option value="">Tous les types</option>
                            <option value="booking" {{ request('type') === 'booking' ? 'selected' : '' }}>Réservation
                            </option>
                            <option value="deposit" {{ request('type') === 'deposit' ? 'selected' : '' }}>Caution</option>
                            <option value="extension" {{ request('type') === 'extension' ? 'selected' : '' }}>Extension
                            </option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Du</label>
                        <input type="date" name="from" value="{{ request('from') }}"
                            class="w-full rounded-lg border-gray-300 text-sm focus:ring-[#F16A00] focus:border-[#F16A00]">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Au</label>
                        <input type="date" name="to" value="{{ request('to') }}"
                            class="w-full rounded-lg border-gray-300 text-sm focus:ring-[#F16A00] focus:border-[#F16A00]">
                    </div>
                    <div class="col-span-2 sm:col-span-1 flex items-end">
                        <button type="submit"
                            class="px-4 py-2 bg-[#CC5A00] text-white rounded-lg text-sm font-medium hover:bg-[#A34700]">
                            Filtrer
                        </button>
                    </div>
                </form>
            </div>

            <!-- Liste des paiements -->
            @if ($payments->count() > 0)
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Référence</th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Date</th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Description</th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Montant</th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Statut</th>
                                    <th
                                        class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach ($payments as $payment)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900">{{ $payment->reference }}</div>
                                            <div class="text-xs text-gray-500">{{ $payment->provider?->name ?? 'N/A' }}
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900">{{ $payment->created_at->format('d/m/Y') }}
                                            </div>
                                            <div class="text-xs text-gray-500">{{ $payment->created_at->format('H:i') }}
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="text-sm text-gray-900">{{ $payment->type_label }}</div>
                                            @if ($payment->booking)
                                                <div class="text-xs text-gray-500">
                                                    {{ Str::limit($payment->booking->residence?->title, 30) }}</div>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-semibold text-gray-900">
                                                {{ $payment->formatted_total }}</div>
                                            @if ($payment->fee > 0)
                                                <div class="text-xs text-gray-500">dont
                                                    {{ number_format($payment->fee, 0, ',', ' ') }}
                                                    {{ $payment->currency }} frais</div>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            @php
                                                $statusColors = [
                                                    'green' => 'bg-green-100 text-green-800',
                                                    'yellow' => 'bg-yellow-100 text-yellow-800',
                                                    'red' => 'bg-red-100 text-red-800',
                                                    'blue' => 'bg-blue-100 text-blue-800',
                                                    'purple' => 'bg-purple-100 text-purple-800',
                                                    'gray' => 'bg-gray-100 text-gray-800',
                                                ];
                                            @endphp
                                            <span
                                                class="px-2.5 py-1 text-xs font-medium rounded-full {{ $statusColors[$payment->status_color] ?? 'bg-gray-100 text-gray-800' }}">
                                                {{ $payment->status_label }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                                            <div class="flex items-center justify-end space-x-2">
                                                <a href="{{ route('payments.show', $payment) }}"
                                                    class="text-[#CC5A00] hover:text-primary-800">
                                                    Détails
                                                </a>
                                                @if ($payment->invoice)
                                                    <a href="{{ route('invoices.download', $payment->invoice) }}"
                                                        class="text-gray-600 hover:text-gray-800"
                                                        title="Télécharger la facture">
                                                        <svg class="w-5 h-5" fill="none" stroke="currentColor"
                                                            viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                        </svg>
                                                    </a>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    @if ($payments->hasPages())
                        <div class="px-6 py-4 border-t border-gray-200">
                            {{ $payments->withQueryString()->links() }}
                        </div>
                    @endif
                </div>
            @else
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-12 text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                    </svg>
                    <h3 class="mt-4 text-lg font-medium text-gray-900">Aucun paiement</h3>
                    <p class="mt-2 text-sm text-gray-500">Vous n'avez pas encore effectué de paiement.</p>
                    <a href="{{ route('home') }}"
                        class="mt-4 inline-flex items-center px-4 py-2 bg-[#CC5A00] text-white rounded-lg text-sm font-medium hover:bg-[#A34700]">
                        Rechercher une résidence
                    </a>
                </div>
            @endif

        </div>
    </div>
@endsection
