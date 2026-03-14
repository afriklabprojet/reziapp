@extends('layouts.owner')

@section('title', 'Ajouter un document — REZI')

@section('owner-content')
<div class="max-w-2xl mx-auto space-y-6">
    <div>
        <a href="{{ route('owner.documents.index') }}" class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-700">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" /></svg>
            Retour
        </a>
        <h1 class="text-2xl font-bold text-gray-900 mt-2">Ajouter un document</h1>
    </div>

    <form action="{{ route('owner.documents.store') }}" method="POST" enctype="multipart/form-data" class="bg-white rounded-2xl border border-gray-100 p-6 space-y-5">
        @csrf

        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">Nom du document *</label>
            <input type="text" name="name" value="{{ old('name') }}" class="w-full rounded-xl border-gray-200 focus:ring-orange-500 focus:border-orange-500 text-sm" placeholder="Ex: Titre foncier Cocody" required>
            @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Catégorie *</label>
                <select name="category" class="w-full rounded-xl border-gray-200 focus:ring-orange-500 focus:border-orange-500 text-sm" required>
                    @foreach(\App\Models\OwnerDocument::CATEGORIES as $key => $label)
                        <option value="{{ $key }}" {{ old('category') === $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
                @error('category') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Date d'expiration</label>
                <input type="date" name="expiry_date" value="{{ old('expiry_date') }}" class="w-full rounded-xl border-gray-200 focus:ring-orange-500 focus:border-orange-500 text-sm">
                @error('expiry_date') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
        </div>

        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">Résidence associée</label>
            <select name="residence_id" class="w-full rounded-xl border-gray-200 focus:ring-orange-500 focus:border-orange-500 text-sm">
                <option value="">Aucune (document général)</option>
                @foreach($residences as $r) <option value="{{ $r->id }}" {{ old('residence_id') == $r->id ? 'selected' : '' }}>{{ $r->name }}</option> @endforeach
            </select>
            @error('residence_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">Fichier *</label>
            <input type="file" name="file" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" class="w-full text-sm text-gray-600 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-semibold file:bg-gray-100 file:text-gray-700 hover:file:bg-gray-200" required>
            <p class="text-xs text-gray-400 mt-1">PDF, images ou Word — max 10 Mo</p>
            @error('file') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">Notes</label>
            <textarea name="notes" rows="2" class="w-full rounded-xl border-gray-200 focus:ring-orange-500 focus:border-orange-500 text-sm" placeholder="Notes optionnelles...">{{ old('notes') }}</textarea>
        </div>

        <div class="flex gap-3 pt-2">
            <button type="submit" class="flex-1 py-2.5 bg-gray-900 text-white font-semibold rounded-xl hover:bg-gray-800 transition-all text-sm">Enregistrer</button>
            <a href="{{ route('owner.documents.index') }}" class="px-6 py-2.5 bg-gray-100 text-gray-700 font-medium rounded-xl hover:bg-gray-200 transition-colors text-sm">Annuler</a>
        </div>
    </form>
</div>
@endsection
