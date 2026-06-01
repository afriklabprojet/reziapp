@extends('layouts.owner')

@section('title', 'Nouvelle saison tarifaire - ' . $residence->name)

@section('owner-content')
    <div class="min-h-screen bg-gray-50 py-8">
        <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="mb-8">
                <nav class="flex items-center gap-2 text-sm text-gray-500 mb-4">
                    <a href="{{ route('owner.pricing.index', $residence) }}" class="hover:text-[#F16A00]">← Retour au
                        calendrier</a>
                </nav>
                <h1 class="text-3xl font-bold text-gray-900">Nouvelle saison tarifaire</h1>
                <p class="mt-1 text-gray-600">Définissez une période avec un tarif spécial</p>
            </div>

            <!-- Formulaire -->
            <form action="{{ route('owner.pricing.store-season', $residence) }}" method="POST"
                class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                @csrf

                <div class="space-y-6">
                    <!-- Nom de la saison -->
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 mb-2">Nom de la saison
                            *</label>
                        <input type="text" name="name" id="name" value="{{ old('name') }}"
                            placeholder="Ex: Haute saison, Noël, Vacances scolaires..."
                            class="w-full px-4 py-3 border rounded-xl focus:ring-2 focus:ring-[#F16A00] focus:border-[#F16A00] {{ $errors->has('name') ? 'border-red-500' : 'border-gray-300' }}">
                        @error('name')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Dates -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="start_date" class="block text-sm font-medium text-gray-700 mb-2">Date de début
                                *</label>
                            <input type="date" name="start_date" id="start_date" value="{{ old('start_date') }}"
                                min="{{ now()->format('Y-m-d') }}"
                                class="w-full px-4 py-3 border rounded-xl focus:ring-2 focus:ring-[#F16A00] focus:border-[#F16A00] {{ $errors->has('start_date') ? 'border-red-500' : 'border-gray-300' }}">
                            @error('start_date')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="end_date" class="block text-sm font-medium text-gray-700 mb-2">Date de fin *</label>
                            <input type="date" name="end_date" id="end_date" value="{{ old('end_date') }}"
                                class="w-full px-4 py-3 border rounded-xl focus:ring-2 focus:ring-[#F16A00] focus:border-[#F16A00] {{ $errors->has('end_date') ? 'border-red-500' : 'border-gray-300' }}">
                            @error('end_date')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <!-- Prix -->
                    <div class="bg-[#FFF4EB] rounded-xl p-4">
                        <h3 class="font-medium text-[#A34700] mb-4">Tarification</h3>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label for="price_per_night" class="block text-sm font-medium text-gray-700 mb-2">Prix par
                                    nuit * (FCFA)</label>
                                <input type="number" name="price_per_night" id="price_per_night"
                                    value="{{ old('price_per_night', $residence->price_per_day) }}" min="0"
                                    step="100"
                                    class="w-full px-4 py-3 border rounded-xl focus:ring-2 focus:ring-[#F16A00] focus:border-[#F16A00] {{ $errors->has('price_per_night') ? 'border-red-500' : 'border-gray-300' }}">
                                @error('price_per_night')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label for="price_per_week" class="block text-sm font-medium text-gray-700 mb-2">Prix par
                                    semaine (FCFA)</label>
                                <input type="number" name="price_per_week" id="price_per_week"
                                    value="{{ old('price_per_week') }}" min="0" step="100"
                                    placeholder="Optionnel"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-[#F16A00] focus:border-[#F16A00]">
                            </div>
                            <div>
                                <label for="price_per_month" class="block text-sm font-medium text-gray-700 mb-2">Prix par
                                    jour (FCFA)</label>
                                <input type="number" name="price_per_month" id="price_per_month"
                                    value="{{ old('price_per_month') }}" min="0" step="100"
                                    placeholder="Optionnel"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-[#F16A00] focus:border-[#F16A00]">
                            </div>
                        </div>
                    </div>

                    <!-- Options avancées -->
                    <div x-data="{ showAdvanced: false }">
                        <button type="button" @click="showAdvanced = !showAdvanced"
                            class="text-[#F16A00] hover:text-[#CC5A00] text-sm font-medium flex items-center gap-1">
                            <span x-text="showAdvanced ? 'Masquer' : 'Afficher'"></span> les options avancées
                            <svg class="w-4 h-4 transition-transform" :class="showAdvanced && 'rotate-180'" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>

                        <div x-show="showAdvanced" x-collapse class="mt-4 space-y-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label for="min_nights" class="block text-sm font-medium text-gray-700 mb-2">Nuits
                                        minimum</label>
                                    <input type="number" name="min_nights" id="min_nights"
                                        value="{{ old('min_nights', 1) }}" min="1" max="30"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-[#F16A00] focus:border-[#F16A00]">
                                </div>
                                <div>
                                    <label for="priority"
                                        class="block text-sm font-medium text-gray-700 mb-2">Priorité</label>
                                    <select name="priority" id="priority"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-[#F16A00] focus:border-[#F16A00]">
                                        <option value="low" {{ old('priority') == 'low' ? 'selected' : '' }}>Basse
                                        </option>
                                        <option value="normal"
                                            {{ old('priority', 'normal') == 'normal' ? 'selected' : '' }}>Normale</option>
                                        <option value="high" {{ old('priority') == 'high' ? 'selected' : '' }}>Haute
                                        </option>
                                    </select>
                                    <p class="mt-1 text-xs text-gray-500">En cas de chevauchement, la priorité haute
                                        l'emporte</p>
                                </div>
                            </div>
                            <div>
                                <label for="notes" class="block text-sm font-medium text-gray-700 mb-2">Notes
                                    internes</label>
                                <textarea name="notes" id="notes" rows="2" placeholder="Notes visibles uniquement par vous..."
                                    class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-[#F16A00] focus:border-[#F16A00]">{{ old('notes') }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex gap-4 mt-8 pt-6 border-t border-gray-200">
                    <a href="{{ route('owner.pricing.index', $residence) }}"
                        class="flex-1 px-6 py-3 bg-gray-100 text-gray-700 text-center rounded-xl hover:bg-gray-200 transition">
                        Annuler
                    </a>
                    <button type="submit"
                        class="flex-1 px-6 py-3 bg-[#F16A00] text-white rounded-xl hover:bg-[#CC5A00] transition">
                        Créer la saison
                    </button>
                </div>
            </form>

            <!-- Exemples -->
            <div class="mt-8 bg-amber-50 rounded-2xl p-6">
                <h3 class="font-semibold text-amber-800 mb-3">💡 Exemples de saisons</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm text-amber-700">
                    <div>
                        <strong>Haute saison</strong>
                        <p>Décembre - Février : +30% sur les prix</p>
                    </div>
                    <div>
                        <strong>Basse saison</strong>
                        <p>Mai - Août : -15% pour attirer plus de clients</p>
                    </div>
                    <div>
                        <strong>Noël & Nouvel An</strong>
                        <p>24 Déc - 2 Jan : +50%, min 3 nuits</p>
                    </div>
                    <div>
                        <strong>Week-ends</strong>
                        <p>Créez plusieurs saisons pour les week-ends spéciaux</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
