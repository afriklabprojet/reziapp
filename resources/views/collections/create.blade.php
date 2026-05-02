@extends('layouts.app')

@section('title', 'Créer une collection')

@section('content')
<div class="max-w-2xl mx-auto px-4 py-8">
    <a href="{{ route('collections.index') }}" class="inline-flex items-center text-sm text-gray-500 hover:text-gray-700 mb-6">
        <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        Mes collections
    </a>

    <h1 class="text-2xl font-bold text-gray-900 mb-6">Nouvelle collection</h1>

    <div class="bg-white rounded-xl shadow-sm border p-6">
        <form action="{{ route('collections.store') }}" method="POST" class="space-y-6">
            @csrf
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Nom de la collection</label>
                <input type="text" name="name" id="name" value="{{ old('name') }}" class="input-field" placeholder="Ex: Week-end à Cocody" required maxlength="100">
                @error('name') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Description (optionnelle)</label>
                <textarea name="description" id="description" rows="3" class="input-field" placeholder="Décrivez votre collection..." maxlength="500">{{ old('description') }}</textarea>
                @error('description') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
            </div>
            <div class="flex items-center">
                <input type="checkbox" name="is_public" id="is_public" value="1" class="rounded border-gray-300 text-[#ff385c] focus:ring-[#ff385c]" {{ old('is_public') ? 'checked' : '' }}>
                <label for="is_public" class="ml-2 text-sm text-gray-700">Rendre cette collection publique (partageable)</label>
            </div>
            <div class="flex justify-end space-x-3">
                <a href="{{ route('collections.index') }}" class="btn-secondary">Annuler</a>
                <button type="submit" class="btn-primary">Créer la collection</button>
            </div>
        </form>
    </div>
</div>
@endsection
