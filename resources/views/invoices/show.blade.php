@extends('layouts.app')

@section('title', 'Facture ' . $invoice->invoice_number)

@section('content')
<div class="max-w-4xl mx-auto px-4 py-8">
    <div class="mb-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <a href="{{ route('invoices.index') }}" class="inline-flex items-center text-sm text-gray-500 hover:text-gray-700 mb-2">
                <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                Mes factures
            </a>
            <h1 class="text-2xl font-bold text-gray-900">Facture {{ $invoice->invoice_number }}</h1>
        </div>
        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2">
            <a href="{{ route('invoices.download', $invoice) }}" class="btn-primary text-sm text-center">
                <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                Télécharger PDF
            </a>
            <form action="{{ route('invoices.send', $invoice) }}" method="POST">
                @csrf
                <button type="submit" class="btn-secondary text-sm">
                    <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    Envoyer par email
                </button>
            </form>
        </div>
    </div>

    <!-- Invoice Card -->
    <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
        <!-- Header -->
        <div class="bg-gray-50 border-b p-4 sm:p-6">
            <div class="flex flex-col sm:flex-row sm:justify-between gap-4">
                <div>
                    <h2 class="text-xl font-bold text-gray-900">Rezi Studio Meublé Faya</h2>
                    <p class="text-sm text-gray-500 mt-1">Plateforme de résidences meublées</p>
                </div>
                <div class="text-right">
                    <p class="text-sm text-gray-500">Date d'émission</p>
                    <p class="font-medium text-gray-900">{{ $invoice->created_at->format('d/m/Y') }}</p>
                    @if($invoice->due_date)
                    <p class="text-sm text-gray-500 mt-2">Échéance</p>
                    <p class="font-medium text-gray-900">{{ $invoice->due_date->format('d/m/Y') }}</p>
                    @endif
                </div>
            </div>
        </div>

        <div class="p-6">
            <!-- Status -->
            <div class="flex items-center justify-between mb-6">
                <span class="text-sm text-gray-600">Statut</span>
                @php
                    $statusClasses = match($invoice->status) {
                        'paid' => 'bg-green-100 text-green-800',
                        'pending' => 'bg-yellow-100 text-yellow-800',
                        'cancelled' => 'bg-red-100 text-red-800',
                        default => 'bg-gray-100 text-gray-800',
                    };
                @endphp
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium {{ $statusClasses }}">
                    {{ $invoice->status === 'paid' ? 'Payée' : ($invoice->status === 'pending' ? 'En attente' : ucfirst($invoice->status)) }}
                </span>
            </div>

            <!-- Line Items -->
            <div class="overflow-x-auto -mx-4 sm:-mx-0">
            <table class="w-full mb-6">
                <thead>
                    <tr class="border-b border-gray-200">
                        <th class="text-left py-2 text-sm font-medium text-gray-600">Description</th>
                        <th class="text-right py-2 text-sm font-medium text-gray-600">Montant</th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="border-b border-gray-100">
                        <td class="py-3 text-gray-900">
                            @if($invoice->booking)
                            Réservation — {{ $invoice->booking->residence->title ?? 'Résidence' }}
                            <br><span class="text-sm text-gray-500">{{ $invoice->booking->check_in?->format('d/m/Y') }} → {{ $invoice->booking->check_out?->format('d/m/Y') }}</span>
                            @else
                            {{ $invoice->description ?? 'Service Rezi Studio Meublé Faya' }}
                            @endif
                        </td>
                        <td class="py-3 text-right font-medium text-gray-900">{{ number_format($invoice->amount ?? $invoice->total ?? 0, 0, ',', ' ') }} FCFA</td>
                    </tr>
                </tbody>
                <tfoot>
                    <tr>
                        <td class="py-3 text-right font-semibold text-gray-900">Total</td>
                        <td class="py-3 text-right text-lg font-bold text-gray-900">{{ number_format($invoice->amount ?? $invoice->total ?? 0, 0, ',', ' ') }} FCFA</td>
                    </tr>
                </tfoot>
            </table>
            </div>
        </div>
    </div>
</div>
@endsection
