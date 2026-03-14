@extends('layouts.owner')

@section('title', 'Nouveau contrat de bail')

@section('owner-content')
<div class="max-w-3xl space-y-6">

    {{-- Fil d'Ariane --}}
    <nav class="text-sm text-gray-400">
        <a href="{{ route('owner.lease-contracts.index') }}" class="hover:text-emerald-600">Contrats</a>
        <span class="mx-2">›</span>
        <span class="text-gray-700">Nouveau contrat</span>
    </nav>

    <h1 class="text-2xl font-bold text-gray-900">📄 Créer un contrat de bail</h1>

    @if($errors->any())
    <div class="bg-red-50 border border-red-200 rounded-xl p-4">
        <ul class="text-sm text-red-700 list-disc list-inside space-y-1">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <form method="POST" action="{{ route('owner.lease-contracts.store') }}" class="space-y-6">
        @csrf

        {{-- Parties --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 space-y-4">
            <h2 class="font-semibold text-gray-800 border-b pb-2">Parties du contrat</h2>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Résidence *</label>
                <select name="residence_id" required
                    class="w-full px-3 py-2 border border-gray-200 rounded-lg focus:ring-emerald-500 focus:border-emerald-500 text-sm">
                    <option value="">— Choisir une résidence —</option>
                    @foreach($residences as $residence)
                        <option value="{{ $residence->id }}" {{ old('residence_id') == $residence->id ? 'selected' : '' }}>
                            {{ $residence->title }} ({{ $residence->commune }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Locataire *</label>
                <select name="tenant_id" required
                    class="w-full px-3 py-2 border border-gray-200 rounded-lg focus:ring-emerald-500 focus:border-emerald-500 text-sm">
                    <option value="">— Choisir un locataire —</option>
                    @foreach($tenants as $tenant)
                        <option value="{{ $tenant->id }}" {{ old('tenant_id') == $tenant->id ? 'selected' : '' }}>
                            {{ $tenant->name }} ({{ $tenant->email }})
                        </option>
                    @endforeach
                </select>
                <p class="text-xs text-gray-400 mt-1">Sélectionnez parmi vos locataires passés ou actuels</p>
            </div>

            @isset($bookings)
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Réservation liée (optionnel)</label>
                <select name="booking_id"
                    class="w-full px-3 py-2 border border-gray-200 rounded-lg focus:ring-emerald-500 focus:border-emerald-500 text-sm">
                    <option value="">— Aucune réservation —</option>
                    @foreach($bookings as $booking)
                        <option value="{{ $booking->id }}" {{ old('booking_id') == $booking->id ? 'selected' : '' }}>
                            #{{ $booking->id }} — {{ $booking->guest->name }} ({{ $booking->check_in->format('d/m/Y') }})
                        </option>
                    @endforeach
                </select>
            </div>
            @endisset
        </div>

        {{-- Durée du bail --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 space-y-4">
            <h2 class="font-semibold text-gray-800 border-b pb-2">Durée et type de bail</h2>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Type de bail *</label>
                <select name="lease_type" required
                    class="w-full px-3 py-2 border border-gray-200 rounded-lg focus:ring-emerald-500 focus:border-emerald-500 text-sm">
                    <option value="monthly" {{ old('lease_type') === 'monthly' ? 'selected' : '' }}>Mensuel</option>
                    <option value="annual" {{ old('lease_type') === 'annual' ? 'selected' : '' }}>Annuel</option>
                    <option value="seasonal" {{ old('lease_type') === 'seasonal' ? 'selected' : '' }}>Saisonnier</option>
                </select>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Date d'entrée *</label>
                    <input type="date" name="start_date" value="{{ old('start_date') }}" required
                        class="w-full px-3 py-2 border border-gray-200 rounded-lg focus:ring-emerald-500 focus:border-emerald-500 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Date de fin (optionnel)</label>
                    <input type="date" name="end_date" value="{{ old('end_date') }}"
                        class="w-full px-3 py-2 border border-gray-200 rounded-lg focus:ring-emerald-500 focus:border-emerald-500 text-sm">
                </div>
            </div>
        </div>

        {{-- Conditions financières --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 space-y-4">
            <h2 class="font-semibold text-gray-800 border-b pb-2">Conditions financières</h2>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Loyer mensuel (FCFA) *</label>
                    <input type="number" name="monthly_rent" value="{{ old('monthly_rent') }}" required min="0" step="500"
                        class="w-full px-3 py-2 border border-gray-200 rounded-lg focus:ring-emerald-500 focus:border-emerald-500 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Dépôt de garantie (FCFA) *</label>
                    <input type="number" name="deposit_amount" value="{{ old('deposit_amount') }}" required min="0" step="500"
                        class="w-full px-3 py-2 border border-gray-200 rounded-lg focus:ring-emerald-500 focus:border-emerald-500 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Charges mensuelles (FCFA)</label>
                    <input type="number" name="charges_amount" value="{{ old('charges_amount', 0) }}" min="0" step="500"
                        class="w-full px-3 py-2 border border-gray-200 rounded-lg focus:ring-emerald-500 focus:border-emerald-500 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Jour d'échéance du loyer</label>
                    <input type="number" name="payment_day" value="{{ old('payment_day', 5) }}" min="1" max="28"
                        class="w-full px-3 py-2 border border-gray-200 rounded-lg focus:ring-emerald-500 focus:border-emerald-500 text-sm"
                        placeholder="Ex: 5 (pour le 5 du mois)">
                </div>
            </div>
        </div>

        {{-- Clauses particulières --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 space-y-4">
            <h2 class="font-semibold text-gray-800 border-b pb-2">Clauses particulières (optionnel)</h2>
            <textarea name="special_conditions" rows="4"
                class="w-full px-3 py-2 border border-gray-200 rounded-lg focus:ring-emerald-500 focus:border-emerald-500 text-sm"
                placeholder="Conditions spéciales, restrictions, règles particulières...">{{ old('special_conditions') }}</textarea>
        </div>

        {{-- Actions --}}
        <div class="flex gap-3">
            <button type="submit"
                class="flex-1 py-3 bg-emerald-600 text-white rounded-xl font-semibold hover:bg-emerald-700 transition">
                Créer le contrat
            </button>
            <a href="{{ route('owner.lease-contracts.index') }}"
                class="px-6 py-3 border border-gray-200 rounded-xl font-semibold text-gray-600 hover:bg-gray-50 transition">
                Annuler
            </a>
        </div>
    </form>
</div>
@endsection
