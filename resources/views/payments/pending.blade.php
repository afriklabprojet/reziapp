@extends('layouts.app')

@section('title', 'Paiement en attente')

@section('content')
<div class="min-h-screen bg-gray-50 flex items-center justify-center px-4 py-12">
    <div class="max-w-md w-full text-center">
        <div class="bg-white rounded-2xl shadow-sm border p-8">
            <!-- Spinner -->
            <div class="mb-6">
                <svg class="w-16 h-16 mx-auto text-orange-500 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/>
                </svg>
            </div>

            <h1 class="text-2xl font-bold text-gray-900 mb-2">Paiement en cours</h1>
            <p class="text-gray-600 mb-6">
                Votre paiement de <strong>{{ number_format($payment->amount, 0, ',', ' ') }} FCFA</strong> est en cours de traitement.
            </p>

            <!-- Payment Info -->
            <div class="bg-gray-50 rounded-lg p-4 mb-6 text-left">
                <div class="flex justify-between text-sm mb-2">
                    <span class="text-gray-600">Référence</span>
                    <span class="font-mono text-gray-900">{{ $payment->reference }}</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600">Opérateur</span>
                    <span class="text-gray-900">{{ $payment->provider?->name ?? 'Mobile Money' }}</span>
                </div>
            </div>

            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
                <p class="text-sm text-yellow-800">
                    <svg class="w-5 h-5 inline mr-1 -mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
                    Veuillez confirmer le paiement sur votre téléphone si ce n'est pas déjà fait.
                </p>
            </div>

            <!-- Auto-refresh -->
            <p class="text-xs text-gray-400 mb-4">Cette page se rafraîchit automatiquement</p>

            <a href="{{ route('payments.history') }}" class="text-sm text-gray-500 hover:text-gray-700">
                Retour à l'historique
            </a>
        </div>
    </div>
</div>

<script>
    // Auto-refresh every 5 seconds
    setTimeout(() => window.location.reload(), 5000);
</script>
@endsection
