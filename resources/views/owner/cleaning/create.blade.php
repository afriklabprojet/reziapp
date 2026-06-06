@extends('layouts.owner')

@section('title', 'Planifier un ménage — ReziApp')

@section('owner-content')
<div class="max-w-2xl mx-auto space-y-6">
    <div>
        <a href="{{ route('owner.cleaning.index') }}" class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-700">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" /></svg>
            Retour
        </a>
        <h1 class="text-2xl font-bold text-gray-900 mt-2">Planifier un ménage</h1>
    </div>

    <form action="{{ route('owner.cleaning.store') }}" method="POST" class="bg-white rounded-2xl border border-gray-100 p-6 space-y-5" x-data="{ checklist: {{ json_encode(old('checklist', [
        ['label' => 'Nettoyage des sols', 'done' => false],
        ['label' => 'Nettoyage salle de bain', 'done' => false],
        ['label' => 'Nettoyage cuisine', 'done' => false],
        ['label' => 'Changement draps et serviettes', 'done' => false],
        ['label' => 'Poussière et surfaces', 'done' => false],
        ['label' => 'Poubelles vidées', 'done' => false],
        ['label' => 'Vérification équipements', 'done' => false],
        ['label' => 'Réapprovisionnement produits', 'done' => false],
    ])) }} }">
        @csrf

        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">Résidence *</label>
            <select name="residence_id" class="w-full rounded-xl border-gray-200 focus:ring-[#F16A00] focus:border-[#F16A00] text-sm" required>
                <option value="">Sélectionner</option>
                @foreach($residences as $r) <option value="{{ $r->id }}" {{ old('residence_id') == $r->id ? 'selected' : '' }}>{{ $r->name }}</option> @endforeach
            </select>
            @error('residence_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Date et heure *</label>
                <input type="datetime-local" name="scheduled_date" value="{{ old('scheduled_date') }}" class="w-full rounded-xl border-gray-200 focus:ring-[#F16A00] focus:border-[#F16A00] text-sm" required>
                @error('scheduled_date') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Nom du prestataire</label>
                <input type="text" name="cleaner_name" value="{{ old('cleaner_name') }}" class="w-full rounded-xl border-gray-200 focus:ring-[#F16A00] focus:border-[#F16A00] text-sm" placeholder="Ex: Marie Koné">
            </div>
        </div>

        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">Téléphone du prestataire</label>
            <input type="text" name="cleaner_phone" value="{{ old('cleaner_phone') }}" class="w-full rounded-xl border-gray-200 focus:ring-[#F16A00] focus:border-[#F16A00] text-sm" placeholder="+225 07 XX XX XX XX">
        </div>

        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">Checklist</label>
            <div class="space-y-2">
                <template x-for="(item, index) in checklist" :key="index">
                    <div class="flex items-center gap-2">
                        <input type="hidden" :name="'checklist['+index+'][done]'" value="0">
                        <input type="text" :name="'checklist['+index+'][label]'" x-model="item.label" class="flex-1 rounded-xl border-gray-200 focus:ring-[#F16A00] text-sm py-2" placeholder="Tâche...">
                        <button type="button" @click="checklist.splice(index, 1)" class="p-2 text-red-400 hover:text-red-600 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                        </button>
                    </div>
                </template>
            </div>
            <button type="button" @click="checklist.push({label: '', done: false})" class="mt-2 text-sm text-[#CC5A00] hover:text-[#A34700] font-medium">
                + Ajouter une tâche
            </button>
        </div>

        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">Notes</label>
            <textarea name="notes" rows="2" class="w-full rounded-xl border-gray-200 focus:ring-[#F16A00] focus:border-[#F16A00] text-sm" placeholder="Instructions spéciales...">{{ old('notes') }}</textarea>
        </div>

        <div class="flex gap-3 pt-2">
            <button type="submit" class="flex-1 py-2.5 bg-gray-900 text-white font-semibold rounded-xl hover:bg-gray-800 transition-all text-sm">Planifier</button>
            <a href="{{ route('owner.cleaning.index') }}" class="px-6 py-2.5 bg-gray-100 text-gray-700 font-medium rounded-xl hover:bg-gray-200 transition-colors text-sm">Annuler</a>
        </div>
    </form>
</div>
@endsection
