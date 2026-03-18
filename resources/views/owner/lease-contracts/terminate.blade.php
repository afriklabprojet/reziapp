@extends('layouts.owner', ['sidebarActive' => 'lease-contracts'])

@section('title', 'Résilier le contrat ' . $contract->reference)

@section('owner-content')
<div class="max-w-xl space-y-6">

    <nav class="text-sm text-gray-400">
        <a href="{{ route('owner.lease-contracts.index') }}" class="hover:text-emerald-600">Contrats</a>
        <span class="mx-2">›</span>
        <a href="{{ route('owner.lease-contracts.show', $contract) }}" class="hover:text-emerald-600">{{ $contract->reference }}</a>
        <span class="mx-2">›</span>
        <span class="text-gray-700">Résiliation</span>
    </nav>

    <h1 class="text-2xl font-bold text-gray-900">⚠️ Résilier le contrat</h1>

    <div class="bg-red-50 border border-red-200 rounded-xl p-4 text-sm text-red-700">
        Vous êtes sur le point de résilier le contrat <strong>{{ $contract->reference }}</strong>
        avec <strong>{{ $contract->tenant->name }}</strong>. Cette action est irréversible.
    </div>

    @if($errors->any())
    <div class="bg-red-50 border border-red-200 rounded-xl p-4">
        <ul class="text-sm text-red-700 list-disc list-inside space-y-1">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <form method="POST" action="{{ route('owner.lease-contracts.terminate', $contract) }}" class="space-y-5">
        @csrf

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 space-y-4">

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Date de résiliation *</label>
                <input type="date" name="terminated_at" value="{{ old('terminated_at', now()->format('Y-m-d')) }}" required
                    class="w-full px-3 py-2 border border-gray-200 rounded-lg focus:ring-red-500 focus:border-red-500 text-sm">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Motif de résiliation *</label>
                <select name="termination_reason" required
                    class="w-full px-3 py-2 border border-gray-200 rounded-lg focus:ring-red-500 focus:border-red-500 text-sm">
                    <option value="">— Choisir un motif —</option>
                    <option value="mutual_agreement" {{ old('termination_reason') === 'mutual_agreement' ? 'selected' : '' }}>Accord mutuel</option>
                    <option value="tenant_request" {{ old('termination_reason') === 'tenant_request' ? 'selected' : '' }}>Demande du locataire</option>
                    <option value="owner_request" {{ old('termination_reason') === 'owner_request' ? 'selected' : '' }}>Décision du bailleur</option>
                    <option value="non_payment" {{ old('termination_reason') === 'non_payment' ? 'selected' : '' }}>Loyers impayés</option>
                    <option value="breach_contract" {{ old('termination_reason') === 'breach_contract' ? 'selected' : '' }}>Non respect du contrat</option>
                    <option value="end_of_term" {{ old('termination_reason') === 'end_of_term' ? 'selected' : '' }}>Fin de terme</option>
                    <option value="other" {{ old('termination_reason') === 'other' ? 'selected' : '' }}>Autre</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Notes (optionnel)</label>
                <textarea name="termination_notes" rows="3"
                    class="w-full px-3 py-2 border border-gray-200 rounded-lg focus:ring-red-500 focus:border-red-500 text-sm"
                    placeholder="Informations supplémentaires sur la résiliation...">{{ old('termination_notes') }}</textarea>
            </div>
        </div>

        <div class="flex gap-3">
            <button type="submit"
                class="flex-1 py-3 bg-red-600 text-white rounded-xl font-semibold hover:bg-red-700 transition">
                Confirmer la résiliation
            </button>
            <a href="{{ route('owner.lease-contracts.show', $contract) }}"
                class="px-6 py-3 border border-gray-200 rounded-xl font-semibold text-gray-600 hover:bg-gray-50 transition">
                Annuler
            </a>
        </div>
    </form>
</div>
@endsection
