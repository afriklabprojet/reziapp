@extends('layouts.app')

@section('title', 'Remboursement #' . ($refund->reference ?? $refund->id))

@section('content')
<div class="max-w-3xl mx-auto px-4 py-8">
    <a href="{{ route('refunds.index') }}" class="inline-flex items-center text-sm text-gray-500 hover:text-gray-700 mb-6">
        <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        Mes remboursements
    </a>

    <h1 class="text-2xl font-bold text-gray-900 mb-6">Remboursement #{{ $refund->reference ?? $refund->id }}</h1>

    <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
        <div class="p-6 space-y-4">
            <div class="flex justify-between items-center">
                <span class="text-gray-600">Statut</span>
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium
                    {{ $refund->status === 'completed' ? 'bg-green-100 text-green-800' : ($refund->status === 'pending' ? 'bg-yellow-100 text-yellow-800' : ($refund->status === 'failed' ? 'bg-red-100 text-red-800' : 'bg-gray-100 text-gray-800')) }}">
                    {{ $refund->status === 'completed' ? 'Remboursé' : ($refund->status === 'pending' ? 'En cours' : ($refund->status === 'failed' ? 'Échoué' : ucfirst($refund->status))) }}
                </span>
            </div>
            <div class="flex justify-between"><span class="text-gray-600">Montant</span><span class="font-semibold text-gray-900">{{ number_format($refund->amount, 0, ',', ' ') }} FCFA</span></div>
            <div class="flex justify-between"><span class="text-gray-600">Date de demande</span><span class="text-gray-900">{{ $refund->created_at->format('d/m/Y à H:i') }}</span></div>
            @if($refund->processed_at)
            <div class="flex justify-between"><span class="text-gray-600">Date de traitement</span><span class="text-gray-900">{{ $refund->processed_at->format('d/m/Y à H:i') }}</span></div>
            @endif
            @if($refund->reason)
            <div class="pt-4 border-t">
                <p class="text-sm font-medium text-gray-500 mb-2">Raison</p>
                <p class="text-gray-700">{{ $refund->reason }}</p>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
