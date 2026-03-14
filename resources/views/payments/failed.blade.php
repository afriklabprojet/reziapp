@extends('layouts.app')

@section('title', 'Paiement échoué')

@section('content')
<div class="min-h-screen bg-gray-50 flex items-center justify-center px-4 py-12">
    <div class="max-w-md w-full text-center">
        <div class="bg-white rounded-2xl shadow-sm border p-8">
            <div class="mb-6">
                <svg class="w-16 h-16 mx-auto text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>

            <h1 class="text-2xl font-bold text-gray-900 mb-2">Paiement échoué</h1>
            <p class="text-gray-600 mb-6">
                Le paiement de <strong>{{ number_format($payment->amount, 0, ',', ' ') }} FCFA</strong> n'a pas pu être traité.
            </p>

            <div class="bg-gray-50 rounded-lg p-4 mb-6 text-left">
                <div class="flex justify-between text-sm mb-2">
                    <span class="text-gray-600">Référence</span>
                    <span class="font-mono text-gray-900">{{ $payment->reference }}</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600">Date</span>
                    <span class="text-gray-900">{{ $payment->created_at->format('d/m/Y à H:i') }}</span>
                </div>
            </div>

            <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
                <p class="text-sm text-red-800">
                    Vérifiez votre solde et réessayez. Si le problème persiste, contactez votre opérateur ou notre support.
                </p>
            </div>

            <div class="space-y-3">
                @if($payment->booking)
                <a href="{{ route('payments.checkout', $payment->booking) }}" class="btn-primary w-full block text-center">
                    Réessayer le paiement
                </a>
                @endif
                <a href="{{ route('payments.history') }}" class="btn-secondary w-full block text-center">
                    Voir l'historique
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
