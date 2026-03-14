@extends('layouts.owner')

@section('title', 'Nouvelle quittance')

@section('owner-content')
<div class="max-w-2xl space-y-6">

    <nav class="text-sm text-gray-400">
        <a href="{{ route('owner.rent-receipts.index') }}" class="hover:text-blue-600">Quittances</a>
        <span class="mx-2">›</span>
        <span class="text-gray-700">Nouvelle quittance</span>
    </nav>

    <h1 class="text-2xl font-bold text-gray-900">🧾 Émettre une quittance de loyer</h1>

    @if($errors->any())
    <div class="bg-red-50 border border-red-200 rounded-xl p-4">
        <ul class="text-sm text-red-700 list-disc list-inside space-y-1">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <form method="POST" action="{{ route('owner.rent-receipts.store') }}" class="space-y-5">
        @csrf

        {{-- Liaison --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 space-y-4">
            <h2 class="font-semibold text-gray-800 border-b pb-2">Parties</h2>

            @if(isset($leaseContracts))
            <div x-data="{ contractId: '{{ old('lease_contract_id') }}' }">
                <label class="block text-sm font-medium text-gray-700 mb-1">Contrat de bail (optionnel)</label>
                <select name="lease_contract_id" x-model="contractId"
                    class="w-full px-3 py-2 border border-gray-200 rounded-lg focus:ring-blue-500 focus:border-blue-500 text-sm">
                    <option value="">— Aucun contrat —</option>
                    @foreach($leaseContracts as $contract)
                        <option value="{{ $contract->id }}"
                            data-tenant="{{ $contract->tenant_id }}"
                            data-residence="{{ $contract->residence_id }}"
                            data-rent="{{ $contract->monthly_rent }}"
                            data-charges="{{ $contract->charges_amount ?? 0 }}">
                            {{ $contract->reference }} — {{ $contract->tenant->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            @endif

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Locataire *</label>
                <select name="tenant_id" required
                    class="w-full px-3 py-2 border border-gray-200 rounded-lg focus:ring-blue-500 focus:border-blue-500 text-sm">
                    <option value="">— Choisir un locataire —</option>
                    @foreach($tenants as $tenant)
                        <option value="{{ $tenant->id }}" {{ old('tenant_id') == $tenant->id ? 'selected' : '' }}>
                            {{ $tenant->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Résidence *</label>
                <select name="residence_id" required
                    class="w-full px-3 py-2 border border-gray-200 rounded-lg focus:ring-blue-500 focus:border-blue-500 text-sm">
                    <option value="">— Choisir une résidence —</option>
                    @foreach($residences as $residence)
                        <option value="{{ $residence->id }}" {{ old('residence_id') == $residence->id ? 'selected' : '' }}>
                            {{ $residence->title }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        {{-- Période --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 space-y-4">
            <h2 class="font-semibold text-gray-800 border-b pb-2">Période concernée</h2>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Du *</label>
                    <input type="date" name="period_start" value="{{ old('period_start') }}" required
                        class="w-full px-3 py-2 border border-gray-200 rounded-lg focus:ring-blue-500 focus:border-blue-500 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Au *</label>
                    <input type="date" name="period_end" value="{{ old('period_end') }}" required
                        class="w-full px-3 py-2 border border-gray-200 rounded-lg focus:ring-blue-500 focus:border-blue-500 text-sm">
                </div>
            </div>
        </div>

        {{-- Montants --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 space-y-4">
            <h2 class="font-semibold text-gray-800 border-b pb-2">Montants</h2>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Loyer (FCFA) *</label>
                    <input type="number" name="rent_amount" value="{{ old('rent_amount') }}" required min="0" step="500"
                        class="w-full px-3 py-2 border border-gray-200 rounded-lg focus:ring-blue-500 focus:border-blue-500 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Charges (FCFA)</label>
                    <input type="number" name="charges_amount" value="{{ old('charges_amount', 0) }}" min="0" step="500"
                        class="w-full px-3 py-2 border border-gray-200 rounded-lg focus:ring-blue-500 focus:border-blue-500 text-sm">
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Mode de paiement</label>
                    <select name="payment_method"
                        class="w-full px-3 py-2 border border-gray-200 rounded-lg focus:ring-blue-500 focus:border-blue-500 text-sm">
                        <option value="">— Choisir —</option>
                        <option value="cash" {{ old('payment_method') === 'cash' ? 'selected' : '' }}>Espèces</option>
                        <option value="mobile_money" {{ old('payment_method') === 'mobile_money' ? 'selected' : '' }}>Mobile Money</option>
                        <option value="bank_transfer" {{ old('payment_method') === 'bank_transfer' ? 'selected' : '' }}>Virement</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Référence paiement</label>
                    <input type="text" name="payment_reference" value="{{ old('payment_reference') }}"
                        class="w-full px-3 py-2 border border-gray-200 rounded-lg focus:ring-blue-500 focus:border-blue-500 text-sm"
                        placeholder="Numéro de transaction...">
                </div>
            </div>
        </div>

        {{-- Options --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 space-y-3">
            <h2 class="font-semibold text-gray-800 border-b pb-2">Options d'envoi</h2>
            <label class="flex items-center gap-3 cursor-pointer">
                <input type="checkbox" name="send_email" value="1" {{ old('send_email', '1') == '1' ? 'checked' : '' }}
                    class="w-4 h-4 text-blue-600 rounded border-gray-300 focus:ring-blue-500">
                <span class="text-sm text-gray-700">Envoyer par email au locataire</span>
            </label>
        </div>

        <div class="flex gap-3">
            <button type="submit"
                class="flex-1 py-3 bg-blue-600 text-white rounded-xl font-semibold hover:bg-blue-700 transition">
                Émettre la quittance
            </button>
            <a href="{{ route('owner.rent-receipts.index') }}"
                class="px-6 py-3 border border-gray-200 rounded-xl font-semibold text-gray-600 hover:bg-gray-50 transition">
                Annuler
            </a>
        </div>
    </form>
</div>
@endsection
