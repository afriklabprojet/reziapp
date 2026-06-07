@extends('layouts.owner')

@section('title', 'Modifier le guide — Rezi App')

@section('owner-content')
<div class="max-w-2xl mx-auto space-y-6">
    <div>
        <a href="{{ route('owner.guidebooks.show', $guidebook) }}" class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-700 mb-4">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" /></svg>
            Retour
        </a>
        <h1 class="text-2xl font-bold text-gray-900">Modifier le guide</h1>
    </div>

    <form action="{{ route('owner.guidebooks.update', $guidebook) }}" method="POST" class="bg-white rounded-2xl border border-gray-100 p-6 space-y-6">
        @csrf @method('PUT')

        <div>
            <label for="title" class="block text-sm font-semibold text-gray-700 mb-2">Titre *</label>
            <input type="text" id="title" name="title" value="{{ old('title', $guidebook->title) }}" required
                   class="w-full rounded-xl border-gray-200 text-sm py-3 px-4">
        </div>

        <div>
            <label for="welcome_message" class="block text-sm font-semibold text-gray-700 mb-2">Message de bienvenue</label>
            <textarea id="welcome_message" name="welcome_message" rows="3"
                      class="w-full rounded-xl border-gray-200 text-sm py-3 px-4">{{ old('welcome_message', $guidebook->welcome_message) }}</textarea>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label for="wifi_name" class="block text-sm font-semibold text-gray-700 mb-2">Nom WiFi</label>
                <input type="text" id="wifi_name" name="wifi_name" value="{{ old('wifi_name', $guidebook->wifi_name) }}"
                       class="w-full rounded-xl border-gray-200 text-sm py-3 px-4">
            </div>
            <div>
                <label for="wifi_password" class="block text-sm font-semibold text-gray-700 mb-2">Mot de passe WiFi</label>
                <input type="text" id="wifi_password" name="wifi_password" value="{{ old('wifi_password', $guidebook->wifi_password) }}"
                       class="w-full rounded-xl border-gray-200 text-sm py-3 px-4">
            </div>
        </div>

        <div>
            <label for="house_rules" class="block text-sm font-semibold text-gray-700 mb-2">Règlement intérieur</label>
            <textarea id="house_rules" name="house_rules" rows="4"
                      class="w-full rounded-xl border-gray-200 text-sm py-3 px-4">{{ old('house_rules', $guidebook->house_rules) }}</textarea>
        </div>

        <div class="flex justify-end gap-3 pt-4 border-t border-gray-100">
            <a href="{{ route('owner.guidebooks.show', $guidebook) }}" class="px-5 py-2.5 text-sm font-semibold text-gray-600 hover:text-gray-800">Annuler</a>
            <button type="submit" class="px-6 py-2.5 bg-gray-900 text-white font-semibold rounded-xl hover:bg-gray-800 transition-all text-sm">
                Enregistrer
            </button>
        </div>
    </form>

    <form action="{{ route('owner.guidebooks.destroy', $guidebook) }}" method="POST" onsubmit="return confirm('Supprimer ce guide ?')">
        @csrf @method('DELETE')
        <button type="submit" class="text-sm text-red-500 hover:text-red-700">Supprimer ce guide</button>
    </form>
</div>
@endsection
