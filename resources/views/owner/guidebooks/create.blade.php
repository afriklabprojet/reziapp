@extends('layouts.owner')

@section('title', 'Nouveau guide — REZI')

@section('owner-content')
<div class="max-w-2xl mx-auto space-y-6">
    <div>
        <a href="{{ route('owner.guidebooks.index') }}" class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-700 mb-4">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" /></svg>
            Retour
        </a>
        <h1 class="text-2xl font-bold text-gray-900">Nouveau guide de bienvenue</h1>
        <p class="text-sm text-gray-500 mt-1">Créez un guide personnalisé pour vos voyageurs</p>
    </div>

    <form action="{{ route('owner.guidebooks.store') }}" method="POST" class="bg-white rounded-2xl border border-gray-100 p-6 space-y-6">
        @csrf

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
            <label for="title" class="block text-sm font-semibold text-gray-700 mb-2">Titre du guide *</label>
            <input type="text" id="title" name="title" value="{{ old('title') }}" required
                   class="w-full rounded-xl border-gray-200 text-sm py-3 px-4" placeholder="Guide de bienvenue - Villa Cocody">
        </div>

        <div>
            <label for="welcome_message" class="block text-sm font-semibold text-gray-700 mb-2">Message de bienvenue</label>
            <textarea id="welcome_message" name="welcome_message" rows="3"
                      class="w-full rounded-xl border-gray-200 text-sm py-3 px-4" placeholder="Bienvenue chez nous !">{{ old('welcome_message') }}</textarea>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label for="wifi_name" class="block text-sm font-semibold text-gray-700 mb-2">Nom WiFi</label>
                <input type="text" id="wifi_name" name="wifi_name" value="{{ old('wifi_name') }}"
                       class="w-full rounded-xl border-gray-200 text-sm py-3 px-4" placeholder="MonWiFi">
            </div>
            <div>
                <label for="wifi_password" class="block text-sm font-semibold text-gray-700 mb-2">Mot de passe WiFi</label>
                <input type="text" id="wifi_password" name="wifi_password" value="{{ old('wifi_password') }}"
                       class="w-full rounded-xl border-gray-200 text-sm py-3 px-4" placeholder="monmotdepasse">
            </div>
        </div>

        <div>
            <label for="house_rules" class="block text-sm font-semibold text-gray-700 mb-2">Règlement intérieur</label>
            <textarea id="house_rules" name="house_rules" rows="4"
                      class="w-full rounded-xl border-gray-200 text-sm py-3 px-4" placeholder="Merci de respecter les règles suivantes...">{{ old('house_rules') }}</textarea>
        </div>

        <div>
            <label for="parking_info" class="block text-sm font-semibold text-gray-700 mb-2">Informations parking</label>
            <textarea id="parking_info" name="parking_info" rows="2"
                      class="w-full rounded-xl border-gray-200 text-sm py-3 px-4" placeholder="Parking disponible dans la cour...">{{ old('parking_info') }}</textarea>
        </div>

        <div class="flex justify-end gap-3 pt-4 border-t border-gray-100">
            <a href="{{ route('owner.guidebooks.index') }}" class="px-5 py-2.5 text-sm font-semibold text-gray-600 hover:text-gray-800">Annuler</a>
            <button type="submit" class="px-6 py-2.5 bg-gray-900 text-white font-semibold rounded-xl hover:bg-gray-800 transition-all text-sm">
                Créer le guide
            </button>
        </div>
    </form>
</div>
@endsection
