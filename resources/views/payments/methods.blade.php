@extends('layouts.app')

@section('title', 'Méthodes de paiement')

@section('content')
<div class="max-w-4xl mx-auto px-4 py-8">
    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Mes méthodes de paiement</h1>
            <p class="text-gray-600 mt-1">Gérez vos moyens de paiement Mobile Money</p>
        </div>
    </div>

    <!-- Saved Methods -->
    <div class="space-y-4 mb-8">
        @forelse($methods as $method)
        <div class="bg-white rounded-xl shadow-sm border p-6 flex items-center justify-between">
            <div class="flex items-center space-x-4">
                <div class="w-12 h-12 bg-[#FFE7D1] rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-[#CC5A00]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                    </svg>
                </div>
                <div>
                    <p class="font-medium text-gray-900">{{ $method->label ?? $method->provider?->name ?? 'Mobile Money' }}</p>
                    <p class="text-sm text-gray-500">{{ $method->phone_number }}</p>
                </div>
            </div>
            <div class="flex items-center space-x-3">
                @if($method->is_default)
                <span class="badge badge-success">Par défaut</span>
                @else
                <form action="{{ route('payments.methods.default', $method) }}" method="POST">
                    @csrf
                    <button type="submit" class="text-sm text-[#CC5A00] hover:text-[#A34700] font-medium">Définir par défaut</button>
                </form>
                @endif
                <form action="{{ route('payments.methods.delete', $method) }}" method="POST" onsubmit="return confirm('Supprimer cette méthode ?')">
                    @csrf @method('DELETE')
                    <button type="submit" class="text-sm text-red-500 hover:text-red-700">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    </button>
                </form>
            </div>
        </div>
        @empty
        <div class="bg-white rounded-xl shadow-sm border p-12 text-center">
            <svg class="w-16 h-16 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
            </svg>
            <h3 class="text-lg font-medium text-gray-900 mb-2">Aucune méthode enregistrée</h3>
            <p class="text-gray-600">Ajoutez un numéro Mobile Money pour simplifier vos paiements.</p>
        </div>
        @endforelse
    </div>

    <!-- Add New Method -->
    <div class="bg-white rounded-xl shadow-sm border p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Ajouter une méthode</h2>
        <form action="{{ route('payments.methods.store') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label for="provider_code" class="block text-sm font-medium text-gray-700 mb-1">Opérateur</label>
                <select name="provider_code" id="provider_code" class="input-field" required>
                    <option value="">Choisir un opérateur</option>
                    @foreach($providers as $provider)
                    <option value="{{ $provider->code }}">{{ $provider->name }}</option>
                    @endforeach
                </select>
                @error('provider_code') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="phone_number" class="block text-sm font-medium text-gray-700 mb-1">Numéro de téléphone</label>
                <input type="tel" name="phone_number" id="phone_number" class="input-field" placeholder="0701020304" required pattern="[0-9]{8,10}">
                @error('phone_number') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="label" class="block text-sm font-medium text-gray-700 mb-1">Libellé (optionnel)</label>
                <input type="text" name="label" id="label" class="input-field" placeholder="Mon numéro Orange">
            </div>
            <div class="flex items-center">
                <input type="checkbox" name="is_default" id="is_default" value="1" class="rounded border-gray-300 text-[#F16A00] focus:ring-[#F16A00]">
                <label for="is_default" class="ml-2 text-sm text-gray-700">Définir comme méthode par défaut</label>
            </div>
            <button type="submit" class="btn-primary">Ajouter la méthode</button>
        </form>
    </div>
</div>
@endsection
