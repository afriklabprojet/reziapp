@extends('layouts.owner')

@section('title', 'Nouvelle réclamation — ReziApp')

@section('owner-content')
<div class="space-y-6">
    <div>
        <a href="{{ route('owner.insurance-claims.index') }}" class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-700 mb-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" /></svg>
            Réclamations
        </a>
        <h1 class="text-2xl font-bold text-gray-900">Nouvelle réclamation assurance</h1>
    </div>

    @if($insurances->isEmpty())
    <div class="bg-yellow-50 border border-yellow-200 rounded-2xl p-6 text-center">
        <svg class="w-12 h-12 mx-auto text-yellow-500 mb-3" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" /></svg>
        <p class="text-yellow-700 font-medium">Aucune assurance active</p>
        <p class="text-sm text-yellow-600 mt-1">Vous devez avoir une réservation avec assurance active pour soumettre une réclamation.</p>
    </div>
    @else
    <div class="bg-white rounded-2xl border border-gray-100 p-6 max-w-2xl">
        <form action="{{ route('owner.insurance-claims.store') }}" method="POST" enctype="multipart/form-data" class="space-y-5">
            @csrf
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-gray-500 mb-1">Assurance *</label>
                    <select name="booking_insurance_id" required class="w-full rounded-xl border-gray-200 text-sm py-2 px-3">
                        <option value="">Sélectionnez une assurance...</option>
                        @foreach($insurances as $ins)
                        <option value="{{ $ins->id }}" {{ old('booking_insurance_id') == $ins->id ? 'selected' : '' }}>
                            {{ $ins->booking->residence->name ?? 'Réservation' }} — {{ $ins->insurancePlan->name ?? 'Assurance' }} ({{ number_format($ins->coverage_amount, 0, ',', ' ') }} F)
                        </option>
                        @endforeach
                    </select>
                    @error('booking_insurance_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 mb-1">Type de réclamation *</label>
                    <select name="claim_type" required class="w-full rounded-xl border-gray-200 text-sm py-2 px-3">
                        <option value="">Sélectionnez...</option>
                        <option value="damage" {{ old('claim_type') == 'damage' ? 'selected' : '' }}>Dommages</option>
                        <option value="theft" {{ old('claim_type') == 'theft' ? 'selected' : '' }}>Vol</option>
                        <option value="cancellation" {{ old('claim_type') == 'cancellation' ? 'selected' : '' }}>Annulation</option>
                        <option value="accident" {{ old('claim_type') == 'accident' ? 'selected' : '' }}>Accident</option>
                        <option value="other" {{ old('claim_type') == 'other' ? 'selected' : '' }}>Autre</option>
                    </select>
                    @error('claim_type') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-500 mb-1">Description détaillée *</label>
                <textarea name="description" rows="4" required class="w-full rounded-xl border-gray-200 text-sm py-2 px-3" placeholder="Décrivez en détail le sinistre, les circonstances et les dommages constatés...">{{ old('description') }}</textarea>
                @error('description') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-gray-500 mb-1">Date du sinistre *</label>
                    <input type="date" name="incident_date" value="{{ old('incident_date', now()->format('Y-m-d')) }}" required max="{{ now()->format('Y-m-d') }}" class="w-full rounded-xl border-gray-200 text-sm py-2 px-3">
                    @error('incident_date') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 mb-1">Montant réclamé (FCFA) *</label>
                    <input type="number" name="claimed_amount" value="{{ old('claimed_amount') }}" required min="1" class="w-full rounded-xl border-gray-200 text-sm py-2 px-3" placeholder="Ex: 250000">
                    @error('claimed_amount') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-500 mb-1">Documents justificatifs</label>
                <input type="file" name="evidence[]" multiple accept=".pdf,.jpg,.jpeg,.png" class="w-full rounded-xl border-gray-200 text-sm py-2 px-3">
                <p class="text-xs text-gray-400 mt-1">PDF, JPG, PNG (max 5 Mo par fichier, 5 fichiers max)</p>
                @error('evidence') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                @error('evidence.*') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div class="flex items-center gap-3 pt-2">
                <button type="submit" class="px-6 py-2.5 bg-gray-900 text-white font-semibold rounded-xl hover:bg-gray-800 text-sm">Soumettre la réclamation</button>
                <a href="{{ route('owner.insurance-claims.index') }}" class="px-4 py-2.5 text-gray-600 hover:text-gray-800 text-sm">Annuler</a>
            </div>
        </form>
    </div>
    @endif
</div>
@endsection
