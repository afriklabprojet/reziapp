@extends('layouts.app')

@section('title', 'Paiement réussi')

@section('content')
<div class="min-h-screen bg-gray-50 flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-lg w-full">
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-8 text-center">
            
            <!-- Icône de succès -->
            <div class="mx-auto w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mb-6">
                <svg class="w-10 h-10 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
            </div>

            <h1 class="text-2xl font-bold text-gray-900 mb-2">Paiement réussi !</h1>
            <p class="text-gray-600 mb-8">
                Votre paiement de <span class="font-semibold text-orange-600">{{ $payment->formatted_total }}</span> a été effectué avec succès.
            </p>

            <!-- Détails du paiement -->
            <div class="bg-gray-50 rounded-xl p-6 mb-8 text-left">
                <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wider mb-4">Détails du paiement</h3>
                
                <div class="space-y-3">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Référence</span>
                        <span class="font-medium text-gray-900">{{ $payment->reference }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Date</span>
                        <span class="font-medium text-gray-900">{{ $payment->completed_at?->format('d/m/Y à H:i') }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Méthode</span>
                        <span class="font-medium text-gray-900">{{ $payment->provider?->name ?? 'Mobile Money' }}</span>
                    </div>
                    @if($payment->booking)
                    <div class="flex justify-between">
                        <span class="text-gray-600">Réservation</span>
                        <span class="font-medium text-gray-900">{{ $payment->booking->reference }}</span>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Résidence réservée -->
            @if($payment->booking && $payment->booking->residence)
            <div class="bg-orange-50 rounded-xl p-6 mb-8 text-left">
                <div class="flex items-start space-x-4">
                    @if($payment->booking->residence->photos->first())
                    <img loading="lazy" src="{{ $payment->booking->residence->photos->first()?->url }}" 
                         alt="{{ $payment->booking->residence->name }}"
                         class="w-24 h-24 object-cover rounded-lg">
                    @endif
                    <div class="flex-1">
                        <h4 class="font-semibold text-gray-900">{{ $payment->booking->residence->title }}</h4>
                        <p class="text-sm text-gray-600">{{ $payment->booking->residence->city }}</p>
                        <div class="mt-2 flex items-center text-sm text-gray-600">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            {{ $payment->booking->check_in->format('d M') }} - {{ $payment->booking->check_out->format('d M Y') }}
                        </div>
                    </div>
                </div>
            </div>
            @endif

            <!-- Actions -->
            <div class="flex flex-col sm:flex-row gap-3">
                @if($payment->invoice)
                <a href="{{ route('invoices.download', $payment->invoice) }}" 
                   class="flex-1 inline-flex items-center justify-center px-4 py-3 border border-gray-300 rounded-lg text-gray-700 bg-white hover:bg-gray-50 font-medium transition-colors">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    Télécharger la facture
                </a>
                @endif
                
                @if($payment->booking)
                <a href="{{ route('bookings.show', $payment->booking) }}" 
                   class="flex-1 inline-flex items-center justify-center px-4 py-3 bg-orange-600 text-white rounded-lg hover:bg-orange-700 font-medium transition-colors">
                    Voir ma réservation
                    <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>
                @else
                <a href="{{ route('payments.history') }}" 
                   class="flex-1 inline-flex items-center justify-center px-4 py-3 bg-orange-600 text-white rounded-lg hover:bg-orange-700 font-medium transition-colors">
                    Voir mes paiements
                </a>
                @endif
            </div>

            <!-- Confirmation email -->
            <p class="mt-6 text-sm text-gray-500">
                Un email de confirmation a été envoyé à <span class="font-medium">{{ $payment->user->email }}</span>
            </p>
        </div>
    </div>
</div>
@endsection
