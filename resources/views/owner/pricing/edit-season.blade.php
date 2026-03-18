@extends('layouts.owner')

@section('title', 'Modifier la saison tarifaire')

@section('owner-content')
    <div class="max-w-2xl mx-auto px-4 py-8">
        <div class="mb-8">
            <a href="{{ route('owner.pricing.index', $residence) }}"
                class="text-sm text-gray-500 hover:text-gray-700 inline-flex items-center gap-1 mb-4">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
                Retour aux tarifs
            </a>
            <h1 class="text-2xl font-bold text-gray-900">Modifier la saison tarifaire</h1>
            <p class="text-gray-600 mt-1">{{ $residence->name }}</p>
        </div>

        <form action="{{ route('owner.pricing.update-season', [$residence, $season]) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="bg-white rounded-xl shadow-sm border p-6 space-y-6">
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Nom de la saison <span
                            class="text-red-500">*</span></label>
                    <input type="text" id="name" name="name" value="{{ old('name', $season->name) }}"
                        class="input-field" placeholder="Ex: Haute saison, Noël, Été" required>
                    @error('name')
                        <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="start_date" class="block text-sm font-medium text-gray-700 mb-1">Date de début <span
                                class="text-red-500">*</span></label>
                        <input type="date" id="start_date" name="start_date"
                            value="{{ old('start_date', $season->start_date?->format('Y-m-d') ?? $season->start_date) }}"
                            class="input-field" required>
                        @error('start_date')
                            <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="end_date" class="block text-sm font-medium text-gray-700 mb-1">Date de fin <span
                                class="text-red-500">*</span></label>
                        <input type="date" id="end_date" name="end_date"
                            value="{{ old('end_date', $season->end_date?->format('Y-m-d') ?? $season->end_date) }}"
                            class="input-field" required>
                        @error('end_date')
                            <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="border-t pt-6">
                    <h2 class="text-sm font-medium text-gray-900 mb-4">Tarification</h2>

                    <div class="space-y-4">
                        <div>
                            <label for="price_per_night" class="block text-sm font-medium text-gray-700 mb-1">Prix par jour
                                <span class="text-red-500">*</span></label>
                            <div class="relative">
                                <input type="number" id="price_per_night" name="price_per_night"
                                    value="{{ old('price_per_night', $season->price_per_night) }}" class="input-field pr-16"
                                    min="0" required>
                                <span class="absolute right-3 top-1/2 -translate-y-1/2 text-sm text-gray-500">FCFA</span>
                            </div>
                            @error('price_per_night')
                                <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="price_per_week" class="block text-sm font-medium text-gray-700 mb-1">Prix par
                                semaine</label>
                            <div class="relative">
                                <input type="number" id="price_per_week" name="price_per_week"
                                    value="{{ old('price_per_week', $season->price_per_week) }}" class="input-field pr-16"
                                    min="0">
                                <span class="absolute right-3 top-1/2 -translate-y-1/2 text-sm text-gray-500">FCFA</span>
                            </div>
                            <p class="text-xs text-gray-500 mt-1">Laissez vide pour calculer automatiquement</p>
                        </div>

                        <div>
                            <label for="price_per_month" class="block text-sm font-medium text-gray-700 mb-1">Prix par
                                jour</label>
                            <div class="relative">
                                <input type="number" id="price_per_month" name="price_per_month"
                                    value="{{ old('price_per_month', $season->price_per_month) }}"
                                    class="input-field pr-16" min="0">
                                <span class="absolute right-3 top-1/2 -translate-y-1/2 text-sm text-gray-500">FCFA</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="border-t pt-6">
                    <h2 class="text-sm font-medium text-gray-900 mb-4">Options</h2>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="min_nights" class="block text-sm font-medium text-gray-700 mb-1">Nuits
                                minimum</label>
                            <input type="number" id="min_nights" name="min_nights"
                                value="{{ old('min_nights', $season->min_nights) }}" class="input-field" min="1"
                                placeholder="1">
                        </div>
                        <div>
                            <label for="priority" class="block text-sm font-medium text-gray-700 mb-1">Priorité</label>
                            <select id="priority" name="priority" class="input-field">
                                <option value="low"
                                    {{ old('priority', $season->priority) === 'low' ? 'selected' : '' }}>Basse</option>
                                <option value="normal"
                                    {{ old('priority', $season->priority) === 'normal' ? 'selected' : '' }}>Normale
                                </option>
                                <option value="high"
                                    {{ old('priority', $season->priority) === 'high' ? 'selected' : '' }}>Haute</option>
                            </select>
                            <p class="text-xs text-gray-500 mt-1">En cas de chevauchement, la priorité haute prévaut</p>
                        </div>
                    </div>
                </div>

                <div>
                    <label for="notes" class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                    <textarea id="notes" name="notes" class="input-field" rows="2" placeholder="Notes internes...">{{ old('notes', $season->notes) }}</textarea>
                </div>
            </div>

            <div class="flex items-center justify-between mt-6">
                <form action="{{ route('owner.pricing.destroy-season', [$residence, $season]) }}" method="POST"
                    onsubmit="return confirm('Supprimer cette saison tarifaire ?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="text-sm text-red-600 hover:text-red-800 font-medium">Supprimer cette
                        saison</button>
                </form>

                <div class="flex items-center gap-3">
                    <a href="{{ route('owner.pricing.index', $residence) }}" class="btn-secondary">Annuler</a>
                    <button type="submit" class="btn-primary">Enregistrer</button>
                </div>
            </div>
        </form>
    </div>
@endsection
