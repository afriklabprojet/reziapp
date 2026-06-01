@extends('layouts.app')

@section('title', 'Historique des annulations')

@section('content')
<div class="max-w-4xl mx-auto px-4 py-8">
    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-2xl font-bold text-gray-900">Mes annulations</h1>
        <p class="text-gray-600 mt-1">Historique de vos annulations et remboursements</p>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
        <div class="bg-white rounded-lg shadow-sm border p-4">
            <div class="text-2xl font-bold text-gray-900">{{ $stats['total_bookings'] }}</div>
            <div class="text-sm text-gray-600">Réservations totales</div>
        </div>
        <div class="bg-white rounded-lg shadow-sm border p-4">
            <div class="text-2xl font-bold text-red-600">{{ $stats['cancellations'] }}</div>
            <div class="text-sm text-gray-600">Annulations</div>
        </div>
        <div class="bg-white rounded-lg shadow-sm border p-4">
            <div class="text-2xl font-bold text-gray-900">{{ $stats['cancellation_rate'] }}%</div>
            <div class="text-sm text-gray-600">Taux d'annulation</div>
        </div>
        <div class="bg-white rounded-lg shadow-sm border p-4">
            <div class="text-2xl font-bold text-green-600">{{ number_format($stats['total_refunded'], 0, ',', ' ') }}</div>
            <div class="text-sm text-gray-600">FCFA remboursés</div>
        </div>
    </div>

    <!-- Cancellations List -->
    @if($cancellations->isEmpty())
        <div class="bg-white rounded-lg shadow-sm border p-12 text-center">
            <svg class="w-16 h-16 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <h3 class="text-lg font-medium text-gray-900 mb-2">Aucune annulation</h3>
            <p class="text-gray-600">Vous n'avez encore annulé aucune réservation.</p>
        </div>
    @else
        <div class="space-y-4">
            @foreach($cancellations as $cancellation)
                <div class="bg-white rounded-lg shadow-sm border overflow-hidden">
                    <div class="p-6">
                        <div class="flex items-start justify-between">
                            <div class="flex items-start space-x-4">
                                @if($cancellation->booking->residence->mainPhoto)
                                    <img loading="lazy" src="{{ storage_url($cancellation->booking->residence->mainPhoto->path) }}" 
                                         alt="{{ $cancellation->booking->residence->name }}"
                                         class="w-20 h-20 object-cover rounded-lg">
                                @endif
                                <div>
                                    <h3 class="font-medium text-gray-900">
                                        {{ $cancellation->booking->residence->title }}
                                    </h3>
                                    <p class="text-sm text-gray-600">
                                        {{ $cancellation->booking->check_in->format('d M Y') }} → 
                                        {{ $cancellation->booking->check_out->format('d M Y') }}
                                    </p>
                                    <p class="text-sm text-gray-500 mt-1">
                                        Annulé le {{ $cancellation->created_at->format('d/m/Y à H:i') }}
                                    </p>
                                </div>
                            </div>
                            <div class="text-right">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-{{ $cancellation->status_color }}-100 text-{{ $cancellation->status_color }}-800">
                                    {{ $cancellation->status_label }}
                                </span>
                            </div>
                        </div>

                        <div class="mt-4 pt-4 border-t grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                            <div>
                                <span class="text-gray-500">Raison</span>
                                <p class="font-medium text-gray-900">{{ $cancellation->reason_label }}</p>
                            </div>
                            <div>
                                <span class="text-gray-500">Annulé par</span>
                                <p class="font-medium text-gray-900">{{ $cancellation->cancelled_by_label }}</p>
                            </div>
                            <div>
                                <span class="text-gray-500">Montant total</span>
                                <p class="font-medium text-gray-900">{{ number_format($cancellation->booking->total_amount, 0, ',', ' ') }} FCFA</p>
                            </div>
                            <div>
                                <span class="text-gray-500">Remboursement</span>
                                <p class="font-medium text-green-600">{{ number_format($cancellation->refund_amount, 0, ',', ' ') }} FCFA</p>
                            </div>
                        </div>

                        @if($cancellation->refunds->isNotEmpty())
                            <div class="mt-4 pt-4 border-t">
                                <h4 class="text-sm font-medium text-gray-700 mb-2">Remboursements</h4>
                                <div class="space-y-2">
                                    @foreach($cancellation->refunds as $refund)
                                        <div class="flex items-center justify-between text-sm">
                                            <div class="flex items-center">
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-{{ $refund->status_color }}-100 text-{{ $refund->status_color }}-800 mr-2">
                                                    {{ $refund->status_label }}
                                                </span>
                                                <span class="text-gray-600">{{ $refund->method_label }}</span>
                                            </div>
                                            <span class="font-medium">{{ $refund->formatted_amount }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>

                    <div class="bg-gray-50 px-6 py-3 flex justify-between items-center">
                        <a href="{{ route('cancellations.show', $cancellation) }}" 
                           class="text-[#CC5A00] hover:text-[#A34700] text-sm font-medium">
                            Voir les détails
                        </a>
                        @if($cancellation->canBeDisputed())
                            <a href="{{ route('disputes.create', ['cancellation_id' => $cancellation->id]) }}" 
                               class="text-[#CC5A00] hover:text-[#A34700] text-sm font-medium">
                                Contester
                            </a>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Pagination -->
        <div class="mt-8">
            {{ $cancellations->links() }}
        </div>
    @endif
</div>
@endsection
