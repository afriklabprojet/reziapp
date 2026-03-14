@extends('layouts.owner')

@section('title', 'Contrat ' . $contract->reference)

@section('owner-content')
<div class="space-y-6">

    {{-- Fil d'Ariane --}}
    <nav class="text-sm text-gray-400 flex items-center gap-2">
        <a href="{{ route('owner.lease-contracts.index') }}" class="hover:text-emerald-600">Contrats</a>
        <span>›</span>
        <span class="text-gray-700 font-mono">{{ $contract->reference }}</span>
    </nav>

    {{-- En-tête --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">📄 {{ $contract->reference }}</h1>
            <span class="inline-flex mt-2 px-3 py-1 rounded-full text-sm font-semibold bg-{{ $contract->status_color }}-100 text-{{ $contract->status_color }}-700">
                {{ $contract->status_label }}
            </span>
        </div>
        <div class="flex items-center gap-2 flex-wrap">
            <a href="{{ route('owner.lease-contracts.download', $contract) }}"
                class="inline-flex items-center gap-2 px-4 py-2 bg-red-600 text-white rounded-xl text-sm font-semibold hover:bg-red-700 transition">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z"/>
                </svg>
                PDF
            </a>
            @if($contract->status === 'active')
            <a href="{{ route('owner.lease-contracts.terminate-form', $contract) }}"
                class="px-4 py-2 bg-gray-100 text-gray-700 rounded-xl text-sm font-semibold hover:bg-gray-200 transition">
                Résilier
            </a>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Colonne principale --}}
        <div class="lg:col-span-2 space-y-5">

            {{-- Parties --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <h2 class="font-semibold text-gray-800 mb-4">Parties du contrat</h2>
                <div class="grid grid-cols-2 gap-4">
                    <div class="bg-gray-50 rounded-xl p-4">
                        <div class="text-xs text-gray-400 uppercase tracking-wide mb-1">Bailleur</div>
                        <div class="font-semibold text-gray-900">{{ $contract->owner->name }}</div>
                        <div class="text-sm text-gray-500">{{ $contract->owner->email }}</div>
                    </div>
                    <div class="bg-gray-50 rounded-xl p-4">
                        <div class="text-xs text-gray-400 uppercase tracking-wide mb-1">Locataire</div>
                        <div class="font-semibold text-gray-900">{{ $contract->tenant->name }}</div>
                        <div class="text-sm text-gray-500">{{ $contract->tenant->email }}</div>
                    </div>
                </div>
            </div>

            {{-- Bien loué --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <h2 class="font-semibold text-gray-800 mb-4">Bien loué</h2>
                <div class="flex items-start gap-4">
                    @if($contract->residence->primary_photo_url)
                    <img src="{{ $contract->residence->primary_photo_url }}" alt="" class="w-20 h-20 rounded-xl object-cover">
                    @endif
                    <div>
                        <div class="font-semibold text-gray-900">{{ $contract->residence->title }}</div>
                        <div class="text-sm text-gray-500">{{ $contract->residence->address }}, {{ $contract->residence->commune }}</div>
                        @if($contract->residence->surface)
                        <div class="text-xs text-gray-400 mt-1">{{ $contract->residence->surface }} m²
                            @if($contract->residence->bedrooms) · {{ $contract->residence->bedrooms }} chambre(s) @endif
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Conditions financières --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <h2 class="font-semibold text-gray-800 mb-4">Conditions financières</h2>
                <div class="space-y-3">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Loyer mensuel</span>
                        <span class="font-semibold text-gray-900">{{ number_format($contract->monthly_rent, 0, ',', ' ') }} FCFA</span>
                    </div>
                    @if($contract->charges_amount)
                    <div class="flex justify-between">
                        <span class="text-gray-600">Charges mensuelles</span>
                        <span class="font-semibold">{{ number_format($contract->charges_amount, 0, ',', ' ') }} FCFA</span>
                    </div>
                    <div class="flex justify-between border-t pt-3 mt-3">
                        <span class="text-gray-900 font-medium">Total mensuel</span>
                        <span class="font-bold text-emerald-600">{{ number_format($contract->monthly_rent + ($contract->charges_amount ?? 0), 0, ',', ' ') }} FCFA</span>
                    </div>
                    @endif
                    <div class="flex justify-between">
                        <span class="text-gray-600">Dépôt de garantie</span>
                        <span class="font-semibold">{{ number_format($contract->deposit_amount, 0, ',', ' ') }} FCFA</span>
                    </div>
                    @if($contract->payment_day)
                    <div class="flex justify-between">
                        <span class="text-gray-600">Jour d'échéance</span>
                        <span class="font-semibold">Le {{ $contract->payment_day }} du mois</span>
                    </div>
                    @endif
                </div>
            </div>

            {{-- Clauses spéciales --}}
            @if($contract->special_conditions)
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <h2 class="font-semibold text-gray-800 mb-2">Clauses particulières</h2>
                <p class="text-sm text-gray-600 leading-relaxed">{{ $contract->special_conditions }}</p>
            </div>
            @endif

        </div>

        {{-- Colonne droite --}}
        <div class="space-y-4">

            {{-- Infos rapides --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 space-y-3">
                <h3 class="font-semibold text-gray-800">Détails du bail</h3>
                <div class="text-sm space-y-2">
                    <div class="flex justify-between">
                        <span class="text-gray-500">Type</span>
                        <span class="font-medium">
                            @switch($contract->lease_type)
                                @case('monthly') Mensuel @break
                                @case('annual') Annuel @break
                                @case('seasonal') Saisonnier @break
                                @default {{ $contract->lease_type }}
                            @endswitch
                        </span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Début</span>
                        <span class="font-medium">{{ $contract->start_date->format('d/m/Y') }}</span>
                    </div>
                    @if($contract->end_date)
                    <div class="flex justify-between">
                        <span class="text-gray-500">Fin</span>
                        <span class="font-medium">{{ $contract->end_date->format('d/m/Y') }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Durée</span>
                        <span class="font-medium">{{ $contract->duration_in_months }} mois</span>
                    </div>
                    @endif
                </div>
            </div>

            {{-- Signatures --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 space-y-3">
                <h3 class="font-semibold text-gray-800">Signatures</h3>

                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-full flex items-center justify-center
                        {{ $contract->owner_signed_at ? 'bg-emerald-100' : 'bg-gray-100' }}">
                        @if($contract->owner_signed_at)
                            <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                        @else
                            <span class="text-gray-400 text-xs">?</span>
                        @endif
                    </div>
                    <div>
                        <div class="text-sm font-medium text-gray-800">Bailleur</div>
                        <div class="text-xs text-gray-400">
                            {{ $contract->owner_signed_at ? 'Signé le ' . $contract->owner_signed_at->format('d/m/Y') : 'En attente' }}
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-full flex items-center justify-center
                        {{ $contract->tenant_signed_at ? 'bg-emerald-100' : 'bg-gray-100' }}">
                        @if($contract->tenant_signed_at)
                            <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                        @else
                            <span class="text-gray-400 text-xs">?</span>
                        @endif
                    </div>
                    <div>
                        <div class="text-sm font-medium text-gray-800">Locataire</div>
                        <div class="text-xs text-gray-400">
                            {{ $contract->tenant_signed_at ? 'Signé le ' . $contract->tenant_signed_at->format('d/m/Y') : 'En attente' }}
                        </div>
                    </div>
                </div>
            </div>

            {{-- Actions --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 space-y-2">
                <h3 class="font-semibold text-gray-800 mb-3">Actions</h3>

                @if($contract->canBeSignedByOwner(auth()->user()))
                <form method="POST" action="{{ route('owner.lease-contracts.sign', $contract) }}">
                    @csrf
                    <button type="submit"
                        class="w-full py-2.5 bg-emerald-600 text-white rounded-xl text-sm font-semibold hover:bg-emerald-700 transition">
                        ✍️ Signer (en tant que bailleur)
                    </button>
                </form>
                @endif

                @if(in_array($contract->status, ['draft', 'pending_tenant']))
                <form method="POST" action="{{ route('owner.lease-contracts.send-to-tenant', $contract) }}">
                    @csrf
                    <button type="submit"
                        class="w-full py-2.5 bg-blue-600 text-white rounded-xl text-sm font-semibold hover:bg-blue-700 transition">
                        📧 Envoyer au locataire
                    </button>
                </form>
                @endif

                @if($contract->status === 'active')
                <a href="{{ route('owner.lease-contracts.terminate-form', $contract) }}"
                    class="block w-full py-2.5 bg-red-50 text-red-700 rounded-xl text-sm font-semibold text-center hover:bg-red-100 transition">
                    ⚠️ Résilier le contrat
                </a>
                @endif

                @if($contract->securityDeposit === null && in_array($contract->status, ['active', 'pending_tenant', 'pending_owner']))
                <a href="{{ route('owner.security-deposits.create', ['lease_contract_id' => $contract->id]) }}"
                    class="block w-full py-2.5 bg-amber-50 text-amber-700 rounded-xl text-sm font-semibold text-center hover:bg-amber-100 transition">
                    💰 Créer le dépôt de garantie
                </a>
                @endif
            </div>

        </div>
    </div>
</div>
@endsection
