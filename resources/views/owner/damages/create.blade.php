@extends('layouts.owner')

@section('title', 'Nouveau rapport de dommage — Rezi App')

@section('owner-content')
<div class="max-w-2xl mx-auto space-y-6">
    <div>
        <a href="{{ route('owner.damages.index') }}" class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-700 mb-4">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" /></svg>
            Retour
        </a>
        <h1 class="text-2xl font-bold text-gray-900">Signaler un dommage</h1>
        <p class="text-sm text-gray-500 mt-1">Documentez les dégradations pour suivi et réclamation</p>
    </div>

    <form action="{{ route('owner.damages.store') }}" method="POST" enctype="multipart/form-data" class="bg-white rounded-2xl border border-gray-100 p-6 space-y-6">
        @csrf

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label for="residence_id" class="block text-sm font-semibold text-gray-700 mb-2">Résidence *</label>
                <select id="residence_id" name="residence_id" required class="w-full rounded-xl border-gray-200 text-sm py-3 px-4">
                    <option value="">Sélectionnez...</option>
                    @foreach($residences as $r)
                        <option value="{{ $r->id }}" {{ old('residence_id') == $r->id ? 'selected' : '' }}>{{ $r->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="booking_id" class="block text-sm font-semibold text-gray-700 mb-2">Réservation liée</label>
                <input type="number" id="booking_id" name="booking_id" value="{{ old('booking_id') }}"
                       class="w-full rounded-xl border-gray-200 text-sm py-3 px-4" placeholder="ID réservation (optionnel)">
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label for="category" class="block text-sm font-semibold text-gray-700 mb-2">Catégorie *</label>
                <select id="category" name="category" required class="w-full rounded-xl border-gray-200 text-sm py-3 px-4">
                    <option value="">Sélectionnez...</option>
                    @foreach($categories as $key => $label)
                        <option value="{{ $key }}" {{ old('category') === $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="severity" class="block text-sm font-semibold text-gray-700 mb-2">Gravité *</label>
                <select id="severity" name="severity" required class="w-full rounded-xl border-gray-200 text-sm py-3 px-4">
                    <option value="">Sélectionnez...</option>
                    @foreach($severities as $key => $label)
                        <option value="{{ $key }}" {{ old('severity') === $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div>
            <label for="title" class="block text-sm font-semibold text-gray-700 mb-2">Titre *</label>
            <input type="text" id="title" name="title" value="{{ old('title') }}" required
                   class="w-full rounded-xl border-gray-200 text-sm py-3 px-4" placeholder="Ex: TV écran fissuré">
        </div>

        <div>
            <label for="description" class="block text-sm font-semibold text-gray-700 mb-2">Description *</label>
            <textarea id="description" name="description" rows="4" required
                      class="w-full rounded-xl border-gray-200 text-sm py-3 px-4" placeholder="Décrivez le dommage en détail...">{{ old('description') }}</textarea>
        </div>

        <div>
            <label for="estimated_cost" class="block text-sm font-semibold text-gray-700 mb-2">Coût estimé (FCFA)</label>
            <input type="number" id="estimated_cost" name="estimated_cost" value="{{ old('estimated_cost') }}" min="0"
                   class="w-full rounded-xl border-gray-200 text-sm py-3 px-4" placeholder="0">
        </div>

        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">Photos (max 10)</label>
            <input type="file" name="photos[]" multiple accept="image/*"
                   class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-semibold file:bg-gray-100 file:text-gray-700 hover:file:bg-gray-200">
            <p class="text-xs text-gray-400 mt-1">Formats acceptés: JPG, PNG (max 5 Mo chacune)</p>
        </div>

        <div class="flex justify-end gap-3 pt-4 border-t border-gray-100">
            <a href="{{ route('owner.damages.index') }}" class="px-5 py-2.5 text-sm font-semibold text-gray-600 hover:text-gray-800">Annuler</a>
            <button type="submit" class="px-6 py-2.5 bg-gray-900 text-white font-semibold rounded-xl hover:bg-gray-800 transition-all text-sm">
                Créer le rapport
            </button>
        </div>
    </form>
</div>
@endsection
