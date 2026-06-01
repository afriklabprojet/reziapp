@extends('layouts.owner')

@section('title', 'Quittance ' . $receipt->reference)

@section('owner-content')
<div class="max-w-2xl space-y-6">

    <nav class="text-sm text-gray-400">
        <a href="{{ route('owner.rent-receipts.index') }}" class="hover:text-blue-600">Quittances</a>
        <span class="mx-2">›</span>
        <span class="text-gray-700 font-mono">{{ $receipt->reference }}</span>
    </nav>

    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-900">🧾 {{ $receipt->reference }}</h1>
        <div class="flex gap-2">
            <a href="{{ route('owner.rent-receipts.download', $receipt) }}"
                class="inline-flex items-center gap-2 px-4 py-2 bg-red-600 text-white rounded-xl text-sm font-semibold hover:bg-red-700 transition">
                PDF
            </a>
            <form method="POST" action="{{ route('owner.rent-receipts.resend', $receipt) }}">
                @csrf
                <button type="submit"
                    class="px-4 py-2 bg-blue-600 text-white rounded-xl text-sm font-semibold hover:bg-blue-700 transition">
                    📧 Renvoyer
                </button>
            </form>
        </div>
    </div>

    {{-- Carte quittance --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
        <div class="text-center py-4 border-b border-gray-100 mb-5">
            <div class="text-lg font-bold text-gray-900">REÇU DE LOCATION</div>
            <div class="text-sm text-gray-500">Période : {{ $receipt->period_label }}</div>
        </div>

        <div class="space-y-3 text-sm">
            <div class="flex justify-between">
                <span class="text-gray-500">Bailleur</span>
                <span class="font-medium">{{ $receipt->owner->name }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-500">Locataire</span>
                <span class="font-medium">{{ $receipt->tenant->name }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-500">Résidence</span>
                <span class="font-medium">{{ $receipt->residence->title }}</span>
            </div>
            <div class="border-t border-gray-100 pt-3 mt-3">
                <div class="flex justify-between">
                    <span class="text-gray-500">Montant de location</span>
                    <span class="font-medium">{{ number_format($receipt->rent_amount, 0, ',', ' ') }} FCFA</span>
                </div>
                @if($receipt->charges_amount > 0)
                <div class="flex justify-between">
                    <span class="text-gray-500">Charges</span>
                    <span class="font-medium">{{ number_format($receipt->charges_amount, 0, ',', ' ') }} FCFA</span>
                </div>
                @endif
                <div class="flex justify-between font-bold text-base mt-2 border-t border-gray-100 pt-2">
                    <span>Total perçu</span>
                    <span class="text-emerald-600">{{ number_format($receipt->total_amount, 0, ',', ' ') }} FCFA</span>
                </div>
            </div>
            @if($receipt->payment_method)
            <div class="flex justify-between">
                <span class="text-gray-500">Mode de règlement</span>
                <span class="font-medium">{{ $receipt->payment_method }}</span>
            </div>
            @endif
            @if($receipt->payment_reference)
            <div class="flex justify-between">
                <span class="text-gray-500">Réf. paiement</span>
                <span class="font-mono text-xs">{{ $receipt->payment_reference }}</span>
            </div>
            @endif
        </div>
    </div>

    {{-- Statut envoi --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
        <h3 class="font-semibold text-gray-800 mb-3">Historique d'envoi</h3>
        <div class="space-y-2 text-sm">
            @if($receipt->sent_by_email_at)
            <div class="flex items-center gap-2 text-emerald-600">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                Email envoyé le {{ $receipt->sent_by_email_at->format('d/m/Y à H:i') }}
            </div>
            @else
            <div class="text-gray-400">Email non encore envoyé</div>
            @endif
            @if($receipt->sent_by_whatsapp_at)
            <div class="flex items-center gap-2 text-emerald-600">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                WhatsApp envoyé le {{ $receipt->sent_by_whatsapp_at->format('d/m/Y à H:i') }}
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
