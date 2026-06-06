@extends('layouts.owner')

@section('title', 'Nouvelle dépense — ReziApp')

@section('owner-content')
<div class="max-w-2xl">
    <div class="mb-6">
        <a href="{{ route('owner.expenses.index') }}" class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-700 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" /></svg>
            Retour aux dépenses
        </a>
        <h1 class="text-2xl font-bold text-gray-900 mt-2">Nouvelle dépense</h1>
    </div>

    <form method="POST" action="{{ route('owner.expenses.store') }}" enctype="multipart/form-data" class="bg-white rounded-2xl border border-gray-100 p-6 space-y-5">
        @csrf

        <div>
            <label for="residence_id" class="block text-sm font-semibold text-gray-700 mb-1">Résidence *</label>
            <select name="residence_id" id="residence_id" required class="w-full rounded-xl border-gray-200 text-sm py-2.5 focus:ring-[#F16A00] focus:border-[#F16A00]">
                <option value="">Sélectionner...</option>
                @foreach($residences as $r)
                    <option value="{{ $r->id }}" {{ old('residence_id') == $r->id ? 'selected' : '' }}>{{ $r->name }}</option>
                @endforeach
            </select>
            @error('residence_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label for="category" class="block text-sm font-semibold text-gray-700 mb-1">Catégorie *</label>
                <select name="category" id="category" required class="w-full rounded-xl border-gray-200 text-sm py-2.5 focus:ring-[#F16A00] focus:border-[#F16A00]">
                    <option value="">Sélectionner...</option>
                    @foreach($categories as $key => $label)
                        <option value="{{ $key }}" {{ old('category') === $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
                @error('category') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="expense_date" class="block text-sm font-semibold text-gray-700 mb-1">Date *</label>
                <input type="date" name="expense_date" id="expense_date" value="{{ old('expense_date', now()->format('Y-m-d')) }}" required class="w-full rounded-xl border-gray-200 text-sm py-2.5 focus:ring-[#F16A00] focus:border-[#F16A00]">
                @error('expense_date') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>

        <div>
            <label for="description" class="block text-sm font-semibold text-gray-700 mb-1">Description *</label>
            <input type="text" name="description" id="description" value="{{ old('description') }}" required placeholder="Ex: Facture SODECI mars 2025" class="w-full rounded-xl border-gray-200 text-sm py-2.5 focus:ring-[#F16A00] focus:border-[#F16A00]">
            @error('description') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="amount" class="block text-sm font-semibold text-gray-700 mb-1">Montant (FCFA) *</label>
            <input type="number" name="amount" id="amount" value="{{ old('amount') }}" required min="0" step="1" placeholder="0" class="w-full rounded-xl border-gray-200 text-sm py-2.5 focus:ring-[#F16A00] focus:border-[#F16A00]">
            @error('amount') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        <div x-data="{ recurring: {{ old('is_recurring') ? 'true' : 'false' }} }">
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" name="is_recurring" value="1" x-model="recurring" class="rounded border-gray-300 text-[#F16A00] focus:ring-[#F16A00]">
                <span class="text-sm font-medium text-gray-700">Dépense récurrente</span>
            </label>
            <div x-show="recurring" x-transition class="mt-3">
                <label for="frequency" class="block text-sm font-semibold text-gray-700 mb-1">Fréquence</label>
                <select name="frequency" id="frequency" class="w-full rounded-xl border-gray-200 text-sm py-2.5 focus:ring-[#F16A00] focus:border-[#F16A00]">
                    <option value="monthly" {{ old('frequency') === 'monthly' ? 'selected' : '' }}>Mensuelle</option>
                    <option value="quarterly" {{ old('frequency') === 'quarterly' ? 'selected' : '' }}>Trimestrielle</option>
                    <option value="yearly" {{ old('frequency') === 'yearly' ? 'selected' : '' }}>Annuelle</option>
                </select>
            </div>
        </div>

        <div>
            <label for="receipt_path" class="block text-sm font-semibold text-gray-700 mb-1">Justificatif (optionnel)</label>
            <input type="file" name="receipt_path" id="receipt_path" accept=".pdf,.jpg,.jpeg,.png" class="w-full text-sm text-gray-500 file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-semibold file:bg-gray-100 file:text-gray-700 hover:file:bg-gray-200">
            <p class="mt-1 text-xs text-gray-400">PDF, JPG ou PNG, max 5 Mo</p>
        </div>

        <div>
            <label for="notes" class="block text-sm font-semibold text-gray-700 mb-1">Notes</label>
            <textarea name="notes" id="notes" rows="2" placeholder="Notes complémentaires..." class="w-full rounded-xl border-gray-200 text-sm py-2.5 focus:ring-[#F16A00] focus:border-[#F16A00]">{{ old('notes') }}</textarea>
        </div>

        <div class="flex justify-end gap-3 pt-3 border-t border-gray-100">
            <a href="{{ route('owner.expenses.index') }}" class="px-5 py-2.5 text-sm font-medium text-gray-700 bg-gray-100 rounded-xl hover:bg-gray-200 transition-colors">Annuler</a>
            <button type="submit" class="px-5 py-2.5 text-sm font-semibold text-white bg-gray-900 rounded-xl hover:bg-gray-800 transition-colors">Enregistrer</button>
        </div>
    </form>
</div>
@endsection
