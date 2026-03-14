@extends('layouts.app')

@section('title', 'Détails de l\'annulation')

@section('content')
<div class="max-w-3xl mx-auto px-4 py-8">
    <a href="{{ route('cancellations.history') }}" class="inline-flex items-center text-sm text-gray-500 hover:text-gray-700 mb-6">
        <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        Retour
    </a>

    <h1 class="text-2xl font-bold text-gray-900 mb-6">Détails de l'annulation</h1>

    <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
        <!-- Status Banner -->
        <div class="p-4 border-b
            @if($cancellation->status === 'approved') bg-green-50
            @elseif($cancellation->status === 'pending') bg-yellow-50
            @elseif($cancellation->status === 'rejected') bg-red-50
            @else bg-gray-50 @endif">
            <div class="flex items-center">
                @if($cancellation->status === 'approved')
                <svg class="w-6 h-6 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span class="font-medium text-green-800">Annulation approuvée</span>
                @elseif($cancellation->status === 'pending')
                <svg class="w-6 h-6 text-yellow-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span class="font-medium text-yellow-800">Annulation en attente</span>
                @elseif($cancellation->status === 'rejected')
                <svg class="w-6 h-6 text-red-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span class="font-medium text-red-800">Annulation rejetée</span>
                @endif
            </div>
        </div>

        <div class="p-6 space-y-6">
            <!-- Booking Info -->
            @if($cancellation->booking)
            <div>
                <h3 class="text-sm font-medium text-gray-500 uppercase tracking-wider mb-3">Réservation</h3>
                <div class="flex items-center space-x-4">
                    @if($cancellation->booking->residence?->mainPhoto)
                    <img loading="lazy" src="{{ storage_url($cancellation->booking->residence->mainPhoto->path) }}" 
                         alt="{{ $cancellation->booking->residence->title }}" class="w-20 h-20 object-cover rounded-lg">
                    @endif
                    <div>
                        <p class="font-medium text-gray-900">{{ $cancellation->booking->residence->title ?? 'Résidence' }}</p>
                        <p class="text-sm text-gray-600">{{ $cancellation->booking->check_in->format('d M Y') }} → {{ $cancellation->booking->check_out->format('d M Y') }}</p>
                        <p class="text-sm font-medium text-gray-900 mt-1">{{ number_format($cancellation->booking->total_amount ?? 0, 0, ',', ' ') }} FCFA</p>
                    </div>
                </div>
            </div>
            @endif

            <!-- Cancellation Details -->
            <div>
                <h3 class="text-sm font-medium text-gray-500 uppercase tracking-wider mb-3">Détails</h3>
                <dl class="space-y-3">
                    <div class="flex justify-between">
                        <dt class="text-gray-600">Annulé par</dt>
                        <dd class="text-gray-900">{{ $cancellation->initiated_by === 'user' ? 'Voyageur' : 'Propriétaire' }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-600">Raison</dt>
                        <dd class="text-gray-900">{{ $cancellation->reason_category ?? 'Non précisée' }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-600">Date d'annulation</dt>
                        <dd class="text-gray-900">{{ $cancellation->created_at->format('d/m/Y à H:i') }}</dd>
                    </div>
                    @if($cancellation->refund_amount)
                    <div class="flex justify-between">
                        <dt class="text-gray-600">Montant remboursé</dt>
                        <dd class="font-medium text-green-600">{{ number_format($cancellation->refund_amount, 0, ',', ' ') }} FCFA</dd>
                    </div>
                    @endif
                    @if($cancellation->penalty_amount && $cancellation->penalty_amount > 0)
                    <div class="flex justify-between">
                        <dt class="text-gray-600">Pénalité</dt>
                        <dd class="font-medium text-red-600">{{ number_format($cancellation->penalty_amount, 0, ',', ' ') }} FCFA</dd>
                    </div>
                    @endif
                </dl>
            </div>

            @if($cancellation->details)
            <div>
                <h3 class="text-sm font-medium text-gray-500 uppercase tracking-wider mb-3">Commentaire</h3>
                <p class="text-gray-700 bg-gray-50 rounded-lg p-4">{{ $cancellation->details }}</p>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
