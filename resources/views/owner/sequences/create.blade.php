@extends('layouts.owner')

@section('title', 'Nouvelle séquence — Rezi App')

@section('owner-content')
<div class="max-w-2xl mx-auto space-y-6">
    <div>
        <a href="{{ route('owner.sequences.index') }}" class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-700 mb-4">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" /></svg>
            Retour
        </a>
        <h1 class="text-2xl font-bold text-gray-900">Nouvelle séquence de messages</h1>
        <p class="text-sm text-gray-500 mt-1">Configurez l'automatisation de vos communications</p>
    </div>

    <form action="{{ route('owner.sequences.store') }}" method="POST" class="bg-white rounded-2xl border border-gray-100 p-6 space-y-6">
        @csrf

        <div>
            <label for="name" class="block text-sm font-semibold text-gray-700 mb-2">Nom de la séquence *</label>
            <input type="text" id="name" name="name" value="{{ old('name') }}" required
                   class="w-full rounded-xl border-gray-200 text-sm py-3 px-4 focus:ring-2 focus:ring-gray-900 focus:border-transparent"
                   placeholder="Ex: Bienvenue après réservation">
            @error('name') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="trigger_event" class="block text-sm font-semibold text-gray-700 mb-2">Événement déclencheur *</label>
            <select id="trigger_event" name="trigger_event" required class="w-full rounded-xl border-gray-200 text-sm py-3 px-4">
                <option value="">Sélectionnez...</option>
                @foreach($triggers as $key => $label)
                    <option value="{{ $key }}" {{ old('trigger_event') === $key ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
            @error('trigger_event') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="residence_id" class="block text-sm font-semibold text-gray-700 mb-2">Résidence (optionnel)</label>
            <select id="residence_id" name="residence_id" class="w-full rounded-xl border-gray-200 text-sm py-3 px-4">
                <option value="">Toutes les résidences</option>
                @foreach($residences as $r)
                    <option value="{{ $r->id }}" {{ old('residence_id') == $r->id ? 'selected' : '' }}>{{ $r->name }}</option>
                @endforeach
            </select>
            <p class="text-xs text-gray-400 mt-1">Laissez vide pour appliquer à toutes vos résidences</p>
        </div>

        <div class="flex items-center gap-3">
            <input type="checkbox" id="is_active" name="is_active" value="1" checked
                   class="w-5 h-5 rounded border-gray-300 text-gray-900 focus:ring-gray-900">
            <label for="is_active" class="text-sm font-medium text-gray-700">Activer immédiatement</label>
        </div>

        <div class="flex justify-end gap-3 pt-4 border-t border-gray-100">
            <a href="{{ route('owner.sequences.index') }}" class="px-5 py-2.5 text-sm font-semibold text-gray-600 hover:text-gray-800">Annuler</a>
            <button type="submit" class="px-6 py-2.5 bg-gray-900 text-white font-semibold rounded-xl hover:bg-gray-800 transition-all text-sm">
                Créer la séquence
            </button>
        </div>
    </form>
</div>
@endsection
