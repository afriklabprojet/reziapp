@extends('layouts.owner')

@section('title', 'Nouvelle assurance — REZI')

@section('owner-content')
<div class="max-w-2xl mx-auto space-y-6">
    <div>
        <a href="{{ route('owner.insurance.index') }}" class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-700">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" /></svg>
            Retour
        </a>
        <h1 class="text-2xl font-bold text-gray-900 mt-2">Ajouter un contrat d'assurance</h1>
    </div>

    <form action="{{ route('owner.insurance.store') }}" method="POST" class="bg-white rounded-2xl border border-gray-100 p-6 space-y-5">
        @csrf

        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">Résidence *</label>
            <select name="residence_id" class="w-full rounded-xl border-gray-200 focus:ring-[#ff385c] focus:border-[#ff385c] text-sm" required>
                <option value="">Sélectionner</option>
                @foreach($residences as $r) <option value="{{ $r->id }}" {{ old('residence_id') == $r->id ? 'selected' : '' }}>{{ $r->name }}</option> @endforeach
            </select>
            @error('residence_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Assureur *</label>
                <input type="text" name="provider" value="{{ old('provider') }}" class="w-full rounded-xl border-gray-200 focus:ring-[#ff385c] focus:border-[#ff385c] text-sm" placeholder="Ex: NSIA, Allianz CI" required>
                @error('provider') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">N° de police</label>
                <input type="text" name="policy_number" value="{{ old('policy_number') }}" class="w-full rounded-xl border-gray-200 focus:ring-[#ff385c] focus:border-[#ff385c] text-sm" placeholder="POL-XXXXXXXXX">
                @error('policy_number') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
        </div>

        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">Type de couverture *</label>
            <select name="coverage_type" class="w-full rounded-xl border-gray-200 focus:ring-[#ff385c] focus:border-[#ff385c] text-sm" required>
                @foreach(\App\Models\InsuranceSubscription::COVERAGE_TYPES as $key => $label)
                    <option value="{{ $key }}" {{ old('coverage_type') === $key ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
            @error('coverage_type') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">Prime mensuelle (FCFA) *</label>
            <input type="number" name="monthly_premium" value="{{ old('monthly_premium') }}" class="w-full rounded-xl border-gray-200 focus:ring-[#ff385c] focus:border-[#ff385c] text-sm" placeholder="25000" required>
            @error('monthly_premium') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Date de début *</label>
                <input type="date" name="start_date" value="{{ old('start_date') }}" class="w-full rounded-xl border-gray-200 focus:ring-[#ff385c] focus:border-[#ff385c] text-sm" required>
                @error('start_date') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Date de fin *</label>
                <input type="date" name="end_date" value="{{ old('end_date') }}" class="w-full rounded-xl border-gray-200 focus:ring-[#ff385c] focus:border-[#ff385c] text-sm" required>
                @error('end_date') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
        </div>

        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">Notes</label>
            <textarea name="notes" rows="2" class="w-full rounded-xl border-gray-200 focus:ring-[#ff385c] focus:border-[#ff385c] text-sm" placeholder="Informations complémentaires...">{{ old('notes') }}</textarea>
        </div>

        <div class="flex gap-3 pt-2">
            <button type="submit" class="flex-1 py-2.5 bg-gray-900 text-white font-semibold rounded-xl hover:bg-gray-800 transition-all text-sm">Enregistrer</button>
            <a href="{{ route('owner.insurance.index') }}" class="px-6 py-2.5 bg-gray-100 text-gray-700 font-medium rounded-xl hover:bg-gray-200 transition-colors text-sm">Annuler</a>
        </div>
    </form>
</div>
@endsection
