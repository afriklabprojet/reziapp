@extends('layouts.owner')

@section('title', 'Nouveau dépôt de garantie')

@section('owner-content')
<div class="max-w-2xl space-y-6">

    <nav class="text-sm text-gray-400">
        <a href="{{ route('owner.security-deposits.index') }}" class="hover:text-amber-600">Dépôts de garantie</a>
        <span class="mx-2">›</span>
        <span class="text-gray-700">Nouveau dépôt</span>
    </nav>

    <h1 class="text-2xl font-bold text-gray-900">💰 Créer un dépôt de garantie</h1>

    @if($errors->any())
    <div class="bg-red-50 border border-red-200 rounded-xl p-4">
        <ul class="text-sm text-red-700 list-disc list-inside space-y-1">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <form method="POST" action="{{ route('owner.security-deposits.store') }}" class="space-y-5">
        @csrf

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 space-y-4">
            <h2 class="font-semibold text-gray-800 border-b pb-2">Liaison du dépôt</h2>

            @if(isset($leaseContracts))
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Contrat de bail lié (optionnel)</label>
                <select name="lease_contract_id"
                    class="w-full px-3 py-2 border border-gray-200 rounded-lg focus:ring-amber-500 focus:border-amber-500 text-sm">
                    <option value="">— Aucun contrat —</option>
                    @foreach($leaseContracts as $contract)
                        <option value="{{ $contract->id }}"
                            {{ old('lease_contract_id', request('lease_contract_id')) == $contract->id ? 'selected' : '' }}
                            data-amount="{{ $contract->deposit_amount }}">
                            {{ $contract->reference }} — {{ $contract->tenant->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            @endif

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Locataire *</label>
                <select name="tenant_id" required
                    class="w-full px-3 py-2 border border-gray-200 rounded-lg focus:ring-amber-500 focus:border-amber-500 text-sm">
                    <option value="">— Choisir un locataire —</option>
                    @foreach($tenants as $tenant)
                        <option value="{{ $tenant->id }}" {{ old('tenant_id') == $tenant->id ? 'selected' : '' }}>
                            {{ $tenant->name }} ({{ $tenant->email }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Résidence *</label>
                <select name="residence_id" required
                    class="w-full px-3 py-2 border border-gray-200 rounded-lg focus:ring-amber-500 focus:border-amber-500 text-sm">
                    <option value="">— Choisir une résidence —</option>
                    @foreach($residences as $residence)
                        <option value="{{ $residence->id }}" {{ old('residence_id') == $residence->id ? 'selected' : '' }}>
                            {{ $residence->title }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 space-y-4">
            <h2 class="font-semibold text-gray-800 border-b pb-2">Montant et paiement</h2>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Montant du dépôt (FCFA) *</label>
                    <input type="number" name="amount" value="{{ old('amount') }}" required min="0" step="500"
                        id="deposit-amount"
                        class="w-full px-3 py-2 border border-gray-200 rounded-lg focus:ring-amber-500 focus:border-amber-500 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Mode de paiement</label>
                    <select name="payment_method"
                        class="w-full px-3 py-2 border border-gray-200 rounded-lg focus:ring-amber-500 focus:border-amber-500 text-sm">
                        <option value="">— Choisir —</option>
                        <option value="cash" {{ old('payment_method') === 'cash' ? 'selected' : '' }}>Espèces</option>
                        <option value="mobile_money" {{ old('payment_method') === 'mobile_money' ? 'selected' : '' }}>Mobile Money</option>
                        <option value="bank_transfer" {{ old('payment_method') === 'bank_transfer' ? 'selected' : '' }}>Virement bancaire</option>
                        <option value="cheque" {{ old('payment_method') === 'cheque' ? 'selected' : '' }}>Chèque</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="flex gap-3">
            <button type="submit"
                class="flex-1 py-3 bg-amber-500 text-white rounded-xl font-semibold hover:bg-amber-600 transition">
                Créer le dépôt de garantie
            </button>
            <a href="{{ route('owner.security-deposits.index') }}"
                class="px-6 py-3 border border-gray-200 rounded-xl font-semibold text-gray-600 hover:bg-gray-50 transition">
                Annuler
            </a>
        </div>
    </form>
</div>
@endsection
