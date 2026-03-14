@extends('layouts.owner')

@section('title', 'Nouvel état des lieux')

@section('owner-content')
<div class="max-w-2xl space-y-6">

    <nav class="text-sm text-gray-400">
        <a href="{{ route('owner.property-inspections.index') }}" class="hover:text-indigo-600">États des lieux</a>
        <span class="mx-2">›</span>
        <span class="text-gray-700">Nouveau</span>
    </nav>

    <h1 class="text-2xl font-bold text-gray-900">🏠 Créer un état des lieux</h1>

    @if($errors->any())
    <div class="bg-red-50 border border-red-200 rounded-xl p-4">
        <ul class="text-sm text-red-700 list-disc list-inside space-y-1">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <form method="POST" action="{{ route('owner.property-inspections.store') }}" class="space-y-5">
        @csrf

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 space-y-4">
            <h2 class="font-semibold text-gray-800 border-b pb-2">Informations générales</h2>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Type d'état des lieux *</label>
                    <select name="type" required
                        class="w-full px-3 py-2 border border-gray-200 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                        <option value="check_in" {{ old('type') === 'check_in' ? 'selected' : '' }}>Entrée (remise des clés)</option>
                        <option value="check_out" {{ old('type') === 'check_out' ? 'selected' : '' }}>Sortie (restitution)</option>
                        <option value="periodic" {{ old('type') === 'periodic' ? 'selected' : '' }}>Périodique (contrôle)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Date de l'état des lieux *</label>
                    <input type="date" name="inspection_date" value="{{ old('inspection_date', now()->format('Y-m-d')) }}" required
                        class="w-full px-3 py-2 border border-gray-200 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Résidence *</label>
                <select name="residence_id" required
                    class="w-full px-3 py-2 border border-gray-200 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                    <option value="">— Choisir une résidence —</option>
                    @foreach($residences as $residence)
                    <option value="{{ $residence->id }}" {{ old('residence_id') == $residence->id ? 'selected' : '' }}>
                        {{ $residence->title }} ({{ $residence->bedrooms ?? 1 }} ch.)
                    </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Locataire (optionnel)</label>
                <select name="tenant_id"
                    class="w-full px-3 py-2 border border-gray-200 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                    <option value="">— Aucun locataire —</option>
                    @foreach($tenants as $tenant)
                    <option value="{{ $tenant->id }}" {{ old('tenant_id') == $tenant->id ? 'selected' : '' }}>
                        {{ $tenant->name }}
                    </option>
                    @endforeach
                </select>
            </div>

            @if(isset($leaseContracts))
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Contrat de bail lié (optionnel)</label>
                <select name="lease_contract_id"
                    class="w-full px-3 py-2 border border-gray-200 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                    <option value="">— Aucun contrat —</option>
                    @foreach($leaseContracts as $contract)
                    <option value="{{ $contract->id }}" {{ old('lease_contract_id') == $contract->id ? 'selected' : '' }}>
                        {{ $contract->reference }} — {{ $contract->tenant->name }}
                    </option>
                    @endforeach
                </select>
            </div>
            @endif
        </div>

        {{-- Compteurs --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 space-y-4">
            <h2 class="font-semibold text-gray-800 border-b pb-2">Relevés de compteurs</h2>
            <div class="grid grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">⚡ Électricité (kWh)</label>
                    <input type="number" name="electricity_index" value="{{ old('electricity_index') }}" step="0.01" min="0"
                        class="w-full px-3 py-2 border border-gray-200 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 text-sm"
                        placeholder="ex: 1234.56">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">💧 Eau (m³)</label>
                    <input type="number" name="water_index" value="{{ old('water_index') }}" step="0.01" min="0"
                        class="w-full px-3 py-2 border border-gray-200 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 text-sm"
                        placeholder="ex: 45.20">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">🔥 Gaz (m³)</label>
                    <input type="number" name="gas_index" value="{{ old('gas_index') }}" step="0.01" min="0"
                        class="w-full px-3 py-2 border border-gray-200 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 text-sm"
                        placeholder="ex: 0">
                </div>
            </div>
        </div>

        {{-- Clés --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 space-y-4">
            <h2 class="font-semibold text-gray-800 border-b pb-2">Remise des clés</h2>
            <div class="flex items-center gap-3">
                <input type="checkbox" name="keys_handed_over" value="1" id="keys_check"
                    {{ old('keys_handed_over') ? 'checked' : '' }}
                    class="w-4 h-4 text-indigo-600 rounded border-gray-300">
                <label for="keys_check" class="text-sm text-gray-700">Remise des clés effectuée</label>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nombre de clés</label>
                <input type="number" name="keys_count" value="{{ old('keys_count', 2) }}" min="0"
                    class="w-32 px-3 py-2 border border-gray-200 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 text-sm">
            </div>
        </div>

        {{-- Observations --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
            <h2 class="font-semibold text-gray-800 border-b pb-2 mb-3">Observations générales</h2>
            <textarea name="general_observations" rows="3"
                class="w-full px-3 py-2 border border-gray-200 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 text-sm"
                placeholder="Remarques générales sur l'état du logement...">{{ old('general_observations') }}</textarea>
        </div>

        <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 text-sm text-blue-700">
            💡 Après création, vous pourrez ajouter le détail de chaque pièce et équipement.
        </div>

        <div class="flex gap-3">
            <button type="submit"
                class="flex-1 py-3 bg-indigo-600 text-white rounded-xl font-semibold hover:bg-indigo-700 transition">
                Créer l'état des lieux
            </button>
            <a href="{{ route('owner.property-inspections.index') }}"
                class="px-6 py-3 border border-gray-200 rounded-xl font-semibold text-gray-600 hover:bg-gray-50 transition">
                Annuler
            </a>
        </div>
    </form>
</div>
@endsection
