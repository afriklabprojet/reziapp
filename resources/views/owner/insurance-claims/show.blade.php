@extends('layouts.owner')

@section('title', 'Réclamation #' . $claim->claim_number . ' — REZI')

@section('owner-content')
<div class="space-y-6">
    <div>
        <a href="{{ route('owner.insurance-claims.index') }}" class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-700 mb-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" /></svg>
            Réclamations
        </a>
        <div class="flex items-center justify-between gap-4">
            <div>
                <div class="flex items-center gap-2">
                    <span class="text-sm font-mono text-gray-500">#{{ $claim->claim_number }}</span>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-bold uppercase
                        {{ $claim->status === 'approved' ? 'bg-green-100 text-green-700' : ($claim->status === 'rejected' ? 'bg-red-100 text-red-700' : ($claim->status === 'under_review' ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-500')) }}">
                        {{ \App\Models\InsuranceClaim::getStatusLabels()[$claim->status] ?? $claim->status }}
                    </span>
                </div>
                <h1 class="text-2xl font-bold text-gray-900 mt-1">{{ \App\Models\InsuranceClaim::getClaimTypeLabels()[$claim->claim_type] ?? $claim->claim_type }}</h1>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white rounded-2xl border border-gray-100 p-6">
                <h2 class="text-lg font-bold text-gray-900 mb-4">Détails du sinistre</h2>
                <div class="prose prose-sm max-w-none text-gray-600">
                    {!! nl2br(e($claim->description)) !!}
                </div>
            </div>

            @if($claim->evidence && count($claim->evidence) > 0)
            <div class="bg-white rounded-2xl border border-gray-100 p-6">
                <h2 class="text-lg font-bold text-gray-900 mb-4">Documents joints</h2>
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                    @foreach($claim->evidence as $doc)
                    <a href="{{ Storage::url($doc) }}" target="_blank" class="flex items-center gap-3 p-3 bg-gray-50 rounded-xl hover:bg-gray-100">
                        <svg class="w-8 h-8 text-gray-400 shrink-0" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" /></svg>
                        <span class="text-xs text-gray-600 truncate">{{ basename($doc) }}</span>
                    </a>
                    @endforeach
                </div>
            </div>
            @endif

            @if($claim->admin_notes)
            <div class="bg-blue-50 rounded-2xl border border-blue-200 p-6">
                <h2 class="text-lg font-bold text-blue-900 mb-2">Note de l'équipe REZI</h2>
                <p class="text-sm text-blue-800">{{ $claim->admin_notes }}</p>
            </div>
            @endif
        </div>

        <div class="space-y-6">
            <div class="bg-white rounded-2xl border border-gray-100 p-6">
                <h3 class="text-sm font-semibold text-gray-500 uppercase mb-3">Informations</h3>
                <div class="space-y-3 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-500">Résidence</span>
                        <span class="text-gray-900 font-medium">{{ $claim->bookingInsurance?->booking?->residence?->name ?? '-' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Date du sinistre</span>
                        <span class="text-gray-900">{{ $claim->incident_date?->format('d/m/Y') }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Date déclaration</span>
                        <span class="text-gray-900">{{ $claim->created_at->format('d/m/Y') }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Assurance</span>
                        <span class="text-gray-900">{{ $claim->bookingInsurance?->insurancePlan?->name ?? '-' }}</span>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-2xl border border-gray-100 p-6">
                <h3 class="text-sm font-semibold text-gray-500 uppercase mb-3">Montants</h3>
                <div class="space-y-3 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-500">Montant réclamé</span>
                        <span class="text-gray-900 font-bold">{{ number_format($claim->claimed_amount, 0, ',', ' ') }} F</span>
                    </div>
                    @if($claim->approved_amount)
                    <div class="flex justify-between">
                        <span class="text-gray-500">Montant approuvé</span>
                        <span class="text-green-600 font-bold">{{ number_format($claim->approved_amount, 0, ',', ' ') }} F</span>
                    </div>
                    @endif
                </div>
            </div>

            @if($claim->status === 'approved' || $claim->status === 'paid')
            <div class="bg-green-50 rounded-2xl border border-green-200 p-4 text-center">
                <svg class="w-8 h-8 mx-auto text-green-600 mb-2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                <p class="text-sm font-semibold text-green-800">Réclamation {{ $claim->status === 'paid' ? 'payée' : 'approuvée' }}</p>
                @if($claim->status === 'approved')
                <p class="text-xs text-green-600 mt-1">Le paiement sera effectué sous 5-10 jours</p>
                @elseif($claim->paid_at)
                <p class="text-xs text-green-600 mt-1">Payée le {{ $claim->paid_at->format('d/m/Y') }}</p>
                @endif
            </div>
            @elseif($claim->status === 'rejected')
            <div class="bg-red-50 rounded-2xl border border-red-200 p-4 text-center">
                <svg class="w-8 h-8 mx-auto text-red-600 mb-2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                <p class="text-sm font-semibold text-red-800">Réclamation refusée</p>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
