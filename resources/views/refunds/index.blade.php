@extends('layouts.app')

@section('title', 'Mes remboursements')

@section('content')
<div class="max-w-4xl mx-auto px-4 py-8">
    <h1 class="text-2xl font-bold text-gray-900 mb-2">Mes remboursements</h1>
    <p class="text-gray-600 mb-8">Suivez l'état de vos demandes de remboursement</p>

    @if($refunds->isEmpty())
    <div class="bg-white rounded-xl shadow-sm border p-12 text-center">
        <svg class="w-16 h-16 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
        <h3 class="text-lg font-medium text-gray-900 mb-2">Aucun remboursement</h3>
        <p class="text-gray-600">Vous n'avez aucune demande de remboursement en cours.</p>
    </div>
    @else
    <div class="space-y-4">
        @foreach($refunds as $refund)
        <a href="{{ route('refunds.show', $refund) }}" class="block bg-white rounded-xl shadow-sm border p-6 hover:shadow-md transition">
            <div class="flex items-center justify-between">
                <div>
                    <p class="font-medium text-gray-900">Remboursement #{{ $refund->reference ?? $refund->id }}</p>
                    <p class="text-sm text-gray-500 mt-1">{{ $refund->created_at->format('d/m/Y') }}</p>
                </div>
                <div class="text-right">
                    <p class="font-semibold text-gray-900">{{ number_format($refund->amount, 0, ',', ' ') }} FCFA</p>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium mt-1
                        {{ $refund->status === 'completed' ? 'bg-green-100 text-green-800' : ($refund->status === 'pending' ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-800') }}">
                        {{ $refund->status === 'completed' ? 'Remboursé' : ($refund->status === 'pending' ? 'En cours' : ucfirst($refund->status)) }}
                    </span>
                </div>
            </div>
        </a>
        @endforeach
    </div>
    {{ $refunds->links() }}
    @endif
</div>
@endsection
