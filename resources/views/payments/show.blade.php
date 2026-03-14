@extends('layouts.app')

@section('title', 'Paiement #' . $payment->reference)

@section('content')
<div class="max-w-4xl mx-auto px-4 py-8">
    <!-- Header -->
    <div class="mb-8">
        <a href="{{ route('payments.history') }}" class="inline-flex items-center text-sm text-gray-500 hover:text-gray-700 mb-4">
            <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            Retour à l'historique
        </a>
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-bold text-gray-900">Paiement {{ $payment->reference }}</h1>
            @php
                $paymentStatusClasses = match($payment->status) {
                    'completed' => 'bg-green-100 text-green-800',
                    'pending' => 'bg-yellow-100 text-yellow-800',
                    'failed' => 'bg-red-100 text-red-800',
                    default => 'bg-gray-100 text-gray-800',
                };
            @endphp
            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium {{ $paymentStatusClasses }}">
                {{ ucfirst($payment->status) }}
            </span>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Main Info -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Payment Details -->
            <div class="bg-white rounded-xl shadow-sm border p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Détails du paiement</h2>
                <dl class="space-y-3">
                    <div class="flex justify-between">
                        <dt class="text-gray-600">Montant</dt>
                        <dd class="font-semibold text-gray-900">{{ number_format($payment->amount, 0, ',', ' ') }} FCFA</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-600">Mode de paiement</dt>
                        <dd class="text-gray-900">{{ $payment->provider?->name ?? 'N/A' }}</dd>
                    </div>
                    @if($payment->paymentMethod)
                    <div class="flex justify-between">
                        <dt class="text-gray-600">Numéro</dt>
                        <dd class="text-gray-900">{{ $payment->paymentMethod->masked_number ?? $payment->paymentMethod->phone_number }}</dd>
                    </div>
                    @endif
                    <div class="flex justify-between">
                        <dt class="text-gray-600">Date</dt>
                        <dd class="text-gray-900">{{ $payment->created_at->format('d/m/Y à H:i') }}</dd>
                    </div>
                    @if($payment->paid_at)
                    <div class="flex justify-between">
                        <dt class="text-gray-600">Payé le</dt>
                        <dd class="text-gray-900">{{ $payment->paid_at->format('d/m/Y à H:i') }}</dd>
                    </div>
                    @endif
                </dl>
            </div>

            <!-- Transactions History -->
            @if($payment->transactions && $payment->transactions->count() > 0)
            <div class="bg-white rounded-xl shadow-sm border p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Historique des transactions</h2>
                <div class="space-y-3">
                    @foreach($payment->transactions as $transaction)
                    @php
                        $txStatusClasses = match($transaction->status) {
                            'success' => 'bg-green-100 text-green-800',
                            'failed' => 'bg-red-100 text-red-800',
                            default => 'bg-gray-100 text-gray-800',
                        };
                    @endphp
                    <div class="flex items-center justify-between py-2 border-b border-gray-100 last:border-0">
                        <div>
                            <p class="text-sm font-medium text-gray-900">{{ ucfirst($transaction->type) }}</p>
                            <p class="text-xs text-gray-500">{{ $transaction->created_at->format('d/m/Y H:i') }}</p>
                        </div>
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $txStatusClasses }}">
                            {{ ucfirst($transaction->status) }}
                        </span>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Booking Info -->
            @if($payment->booking)
            <div class="bg-white rounded-xl shadow-sm border p-6">
                <h3 class="font-semibold text-gray-900 mb-3">Réservation associée</h3>
                @if($payment->booking->residence)
                <div class="flex items-center space-x-3 mb-3">
                    @if($payment->booking->residence->mainPhoto)
                    <img loading="lazy" src="{{ storage_url($payment->booking->residence->mainPhoto->path) }}" 
                         alt="{{ $payment->booking->residence->title }}" class="w-16 h-16 object-cover rounded-lg">
                    @endif
                    <div>
                        <p class="font-medium text-gray-900 text-sm">{{ $payment->booking->residence->title }}</p>
                        <p class="text-xs text-gray-500">{{ $payment->booking->check_in->format('d M') }} → {{ $payment->booking->check_out->format('d M Y') }}</p>
                    </div>
                </div>
                @endif
                <a href="{{ route('bookings.show', $payment->booking) }}" class="text-sm text-orange-600 hover:text-orange-700 font-medium">
                    Voir la réservation →
                </a>
            </div>
            @endif

            <!-- Invoice -->
            @if($payment->invoice)
            <div class="bg-white rounded-xl shadow-sm border p-6">
                <h3 class="font-semibold text-gray-900 mb-3">Facture</h3>
                <p class="text-sm text-gray-600 mb-3">{{ $payment->invoice->invoice_number }}</p>
                <a href="{{ route('invoices.download', $payment->invoice) }}" class="btn-primary text-sm w-full text-center block">
                    <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    Télécharger PDF
                </a>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
