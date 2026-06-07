@extends('layouts.owner')

@section('title', 'Nouveau relevé — Rezi App')

@section('owner-content')
<div class="space-y-6">
    <div>
        <a href="{{ route('owner.utilities.index') }}" class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-700 mb-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" /></svg>
            Compteurs
        </a>
        <h1 class="text-2xl font-bold text-gray-900">Nouveau relevé de compteur</h1>
    </div>

    <div class="bg-white rounded-2xl border border-gray-100 p-6 max-w-xl">
        <form action="{{ route('owner.utilities.store') }}" method="POST" class="space-y-5">
            @csrf
            <div>
                <label class="block text-xs font-semibold text-gray-500 mb-1">Résidence *</label>
                <select name="residence_id" required class="w-full rounded-xl border-gray-200 text-sm py-2 px-3">
                    <option value="">Sélectionnez...</option>
                    @foreach($residences as $r) <option value="{{ $r->id }}" {{ old('residence_id') == $r->id ? 'selected' : '' }}>{{ $r->name }}</option> @endforeach
                </select>
                @error('residence_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-500 mb-1">Type de compteur *</label>
                <select name="utility_type" required class="w-full rounded-xl border-gray-200 text-sm py-2 px-3">
                    @foreach(\App\Models\UtilityReading::TYPES as $k => $l)
                    <option value="{{ $k }}" {{ old('utility_type') === $k ? 'selected' : '' }}>{{ $l }}</option>
                    @endforeach
                </select>
                @error('utility_type') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-500 mb-1">Date du relevé *</label>
                <input type="date" name="reading_date" value="{{ old('reading_date', now()->format('Y-m-d')) }}" required class="w-full rounded-xl border-gray-200 text-sm py-2 px-3">
                @error('reading_date') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-500 mb-1">Valeur du compteur *</label>
                <input type="number" step="0.01" name="value" value="{{ old('value') }}" required class="w-full rounded-xl border-gray-200 text-sm py-2 px-3" placeholder="Ex: 12345.67">
                @error('value') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-500 mb-1">Coût estimé (FCFA)</label>
                <input type="number" name="cost" value="{{ old('cost') }}" class="w-full rounded-xl border-gray-200 text-sm py-2 px-3" placeholder="Optionnel">
                @error('cost') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-500 mb-1">Notes</label>
                <textarea name="notes" rows="2" class="w-full rounded-xl border-gray-200 text-sm py-2 px-3" placeholder="Optionnel">{{ old('notes') }}</textarea>
            </div>
            <div class="flex items-center gap-3 pt-2">
                <button type="submit" class="px-6 py-2.5 bg-gray-900 text-white font-semibold rounded-xl hover:bg-gray-800 text-sm">Enregistrer</button>
                <a href="{{ route('owner.utilities.index') }}" class="px-4 py-2.5 text-gray-600 hover:text-gray-800 text-sm">Annuler</a>
            </div>
        </form>
    </div>
</div>
@endsection
