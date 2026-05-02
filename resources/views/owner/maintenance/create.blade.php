@extends('layouts.owner')

@section('title', 'Nouvelle demande — Maintenance — REZI')

@section('owner-content')
<div class="max-w-2xl mx-auto space-y-6">
    <div>
        <a href="{{ route('owner.maintenance.index') }}" class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-700">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" /></svg>
            Retour
        </a>
        <h1 class="text-2xl font-bold text-gray-900 mt-2">Nouvelle demande de maintenance</h1>
    </div>

    <form action="{{ route('owner.maintenance.store') }}" method="POST" enctype="multipart/form-data" class="bg-white rounded-2xl border border-gray-100 p-6 space-y-5">
        @csrf

        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">Résidence *</label>
            <select name="residence_id" class="w-full rounded-xl border-gray-200 focus:ring-[#ff385c] focus:border-[#ff385c] text-sm" required>
                <option value="">Sélectionner</option>
                @foreach($residences as $r) <option value="{{ $r->id }}" {{ old('residence_id') == $r->id ? 'selected' : '' }}>{{ $r->name }}</option> @endforeach
            </select>
            @error('residence_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">Titre *</label>
            <input type="text" name="title" value="{{ old('title') }}" class="w-full rounded-xl border-gray-200 focus:ring-[#ff385c] focus:border-[#ff385c] text-sm" placeholder="Ex: Fuite robinet salle de bain" required>
            @error('title') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Catégorie *</label>
                <select name="category" class="w-full rounded-xl border-gray-200 focus:ring-[#ff385c] focus:border-[#ff385c] text-sm" required>
                    @foreach(\App\Models\MaintenanceRequest::CATEGORIES as $key => $label)
                        <option value="{{ $key }}" {{ old('category') === $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
                @error('category') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Priorité *</label>
                <select name="priority" class="w-full rounded-xl border-gray-200 focus:ring-[#ff385c] focus:border-[#ff385c] text-sm" required>
                    @foreach(\App\Models\MaintenanceRequest::PRIORITIES as $key => $label)
                        <option value="{{ $key }}" {{ old('priority', 'medium') === $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
                @error('priority') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
        </div>

        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">Description *</label>
            <textarea name="description" rows="4" class="w-full rounded-xl border-gray-200 focus:ring-[#ff385c] focus:border-[#ff385c] text-sm" placeholder="Décrivez le problème en détail..." required>{{ old('description') }}</textarea>
            @error('description') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">Photos (max 5)</label>
            <input type="file" name="photos[]" multiple accept="image/*" class="w-full text-sm text-gray-600 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-semibold file:bg-gray-100 file:text-gray-700 hover:file:bg-gray-200">
            @error('photos.*') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
        </div>

        <div class="flex gap-3 pt-2">
            <button type="submit" class="flex-1 py-2.5 bg-gray-900 text-white font-semibold rounded-xl hover:bg-gray-800 transition-all text-sm">Soumettre</button>
            <a href="{{ route('owner.maintenance.index') }}" class="px-6 py-2.5 bg-gray-100 text-gray-700 font-medium rounded-xl hover:bg-gray-200 transition-colors text-sm">Annuler</a>
        </div>
    </form>
</div>
@endsection
