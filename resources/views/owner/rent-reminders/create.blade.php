@extends('layouts.owner')

@section('title', 'Nouvelle relance — REZI')

@section('owner-content')
<div class="max-w-2xl">
    <div class="mb-6">
        <a href="{{ route('owner.rent-reminders.index') }}" class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-700"><svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" /></svg> Retour</a>
        <h1 class="text-2xl font-bold text-gray-900 mt-2">Nouvelle relance de loyer</h1>
    </div>

    <form method="POST" action="{{ route('owner.rent-reminders.store') }}" class="bg-white rounded-2xl border border-gray-100 p-6 space-y-5">
        @csrf
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">Résidence *</label>
            <select name="residence_id" required class="w-full rounded-xl border-gray-200 text-sm py-2.5 focus:ring-orange-500 focus:border-orange-500">
                <option value="">Sélectionner...</option>
                @foreach($residences as $r) <option value="{{ $r->id }}" {{ old('residence_id') == $r->id ? 'selected' : '' }}>{{ $r->name }}</option> @endforeach
            </select>
            @error('residence_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">Locataire (ID) *</label>
            <input type="number" name="tenant_id" value="{{ old('tenant_id') }}" required class="w-full rounded-xl border-gray-200 text-sm py-2.5 focus:ring-orange-500 focus:border-orange-500" placeholder="ID du locataire">
            @error('tenant_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Montant (FCFA) *</label>
                <input type="number" name="amount" value="{{ old('amount') }}" required min="0" class="w-full rounded-xl border-gray-200 text-sm py-2.5 focus:ring-orange-500 focus:border-orange-500">
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Date d'échéance *</label>
                <input type="date" name="due_date" value="{{ old('due_date') }}" required class="w-full rounded-xl border-gray-200 text-sm py-2.5 focus:ring-orange-500 focus:border-orange-500">
            </div>
        </div>
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">Canal de notification *</label>
            <select name="channel" required class="w-full rounded-xl border-gray-200 text-sm py-2.5 focus:ring-orange-500 focus:border-orange-500">
                <option value="email" {{ old('channel') === 'email' ? 'selected' : '' }}>Email</option>
                <option value="sms" {{ old('channel') === 'sms' ? 'selected' : '' }}>SMS</option>
                <option value="whatsapp" {{ old('channel') === 'whatsapp' ? 'selected' : '' }}>WhatsApp</option>
            </select>
        </div>
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">Notes</label>
            <textarea name="notes" rows="2" class="w-full rounded-xl border-gray-200 text-sm py-2.5 focus:ring-orange-500 focus:border-orange-500">{{ old('notes') }}</textarea>
        </div>
        <div class="flex justify-end gap-3 pt-3 border-t border-gray-100">
            <a href="{{ route('owner.rent-reminders.index') }}" class="px-5 py-2.5 text-sm font-medium text-gray-700 bg-gray-100 rounded-xl hover:bg-gray-200 transition-colors">Annuler</a>
            <button type="submit" class="px-5 py-2.5 text-sm font-semibold text-white bg-gray-900 rounded-xl hover:bg-gray-800 transition-colors">Créer la relance</button>
        </div>
    </form>
</div>
@endsection
