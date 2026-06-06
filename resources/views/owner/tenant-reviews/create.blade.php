@extends('layouts.owner')

@section('title', 'Nouvel avis locataire — ReziApp')

@section('owner-content')
<div class="max-w-2xl mx-auto space-y-6">
    <div>
        <a href="{{ route('owner.tenant-reviews.index') }}" class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-700">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" /></svg>
            Retour
        </a>
        <h1 class="text-2xl font-bold text-gray-900 mt-2">Évaluer un locataire</h1>
    </div>

    <form action="{{ route('owner.tenant-reviews.store') }}" method="POST" class="bg-white rounded-2xl border border-gray-100 p-6 space-y-5" x-data="{ ratings: { cleanliness: {{ old('cleanliness', 3) }}, respect_rules: {{ old('respect_rules', 3) }}, communication: {{ old('communication', 3) }}, payment: {{ old('payment', 3) }}, overall: {{ old('overall', 3) }} } }">
        @csrf

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Résidence *</label>
                <select name="residence_id" class="w-full rounded-xl border-gray-200 focus:ring-[#F16A00] focus:border-[#F16A00] text-sm" required>
                    <option value="">Sélectionner</option>
                    @foreach($residences as $r) <option value="{{ $r->id }}" {{ old('residence_id') == $r->id ? 'selected' : '' }}>{{ $r->name }}</option> @endforeach
                </select>
                @error('residence_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Locataire (email) *</label>
                <input type="email" name="tenant_email" value="{{ old('tenant_email') }}" class="w-full rounded-xl border-gray-200 focus:ring-[#F16A00] focus:border-[#F16A00] text-sm" placeholder="locataire@email.com" required>
                @error('tenant_email') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                @error('tenant_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
        </div>

        {{-- Rating dimensions --}}
        <div class="space-y-4">
            <h2 class="font-semibold text-gray-900">Évaluations</h2>
            @foreach(\App\Models\TenantReview::DIMENSIONS as $key => $label)
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">{{ $label }}</label>
                <div class="flex items-center gap-1">
                    @for($i = 1; $i <= 5; $i++)
                    <button type="button" @click="ratings.{{ $key }} = {{ $i }}" class="focus:outline-none">
                        <svg class="w-8 h-8 transition-colors" :class="ratings.{{ $key }} >= {{ $i }} ? 'text-amber-400' : 'text-gray-200'" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                        </svg>
                    </button>
                    @endfor
                    <span class="ml-2 text-sm text-gray-500" x-text="ratings.{{ $key }} + '/5'"></span>
                </div>
                <input type="hidden" name="{{ $key }}" :value="ratings.{{ $key }}">
                @error($key) <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            @endforeach
        </div>

        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">Commentaire</label>
            <textarea name="comment" rows="3" class="w-full rounded-xl border-gray-200 focus:ring-[#F16A00] focus:border-[#F16A00] text-sm" placeholder="Votre expérience avec ce locataire...">{{ old('comment') }}</textarea>
            @error('comment') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
        </div>

        <div class="flex items-center gap-3">
            <label for="would_rent_again" class="relative inline-flex items-center cursor-pointer">
                <input type="hidden" name="would_rent_again" value="0">
                <input type="checkbox" id="would_rent_again" name="would_rent_again" value="1" {{ old('would_rent_again') ? 'checked' : '' }} class="sr-only peer">
                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-0.5 after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-green-500"></div>
            </label>
            <span class="text-sm font-semibold text-gray-700">Je relouerais à ce locataire</span>
        </div>

        <div class="flex gap-3 pt-2">
            <button type="submit" class="flex-1 py-2.5 bg-gray-900 text-white font-semibold rounded-xl hover:bg-gray-800 transition-all text-sm">Enregistrer l'avis</button>
            <a href="{{ route('owner.tenant-reviews.index') }}" class="px-6 py-2.5 bg-gray-100 text-gray-700 font-medium rounded-xl hover:bg-gray-200 transition-colors text-sm">Annuler</a>
        </div>
    </form>
</div>
@endsection
