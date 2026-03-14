@extends('layouts.owner')

@section('title', 'Mode vacances — REZI')

@section('owner-content')
<div class="max-w-2xl mx-auto space-y-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Mode vacances</h1>
        <p class="text-sm text-gray-500 mt-1">Désactivez temporairement la disponibilité de vos résidences</p>
    </div>

    {{-- Current Status --}}
    @if($activeMode)
    <div class="bg-amber-50 border border-amber-200 rounded-2xl p-6">
        <div class="flex items-start gap-4">
            <div class="w-12 h-12 rounded-xl bg-amber-100 flex items-center justify-center shrink-0">
                <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386-1.591 1.591M21 12h-2.25m-.386 6.364-1.591-1.591M12 18.75V21m-4.773-4.227-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z" /></svg>
            </div>
            <div class="flex-1">
                <h2 class="font-bold text-amber-900 text-lg">Mode vacances activé</h2>
                <p class="text-sm text-amber-800 mt-1">
                    Du <strong>{{ $activeMode->start_date->format('d/m/Y') }}</strong>
                    au <strong>{{ $activeMode->end_date->format('d/m/Y') }}</strong>
                    · {{ $activeMode->daysRemaining() }} jours restants
                </p>
                @if($activeMode->reason)
                <p class="text-sm text-amber-700 mt-1">{{ $activeMode->reason }}</p>
                @endif

                @if($activeMode->residences && count($activeMode->residences))
                <div class="mt-3 flex flex-wrap gap-1">
                    @foreach($activeMode->residences as $res)
                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium bg-amber-200 text-amber-800">{{ $res->name ?? 'Résidence #'.$res->id }}</span>
                    @endforeach
                </div>
                @else
                <p class="text-xs text-amber-700 mt-2">Toutes les résidences sont affectées</p>
                @endif

                <form method="POST" action="{{ route('owner.vacation-mode.deactivate', $activeMode) }}" class="mt-4">
                    @csrf @method('PATCH')
                    <button type="submit" class="px-5 py-2.5 bg-white text-amber-800 font-semibold rounded-xl border border-amber-300 hover:bg-amber-100 transition-colors text-sm">
                        Désactiver maintenant
                    </button>
                </form>
            </div>
        </div>
    </div>
    @else
    <div class="bg-green-50 border border-green-200 rounded-2xl p-6">
        <div class="flex items-center gap-3">
            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
            <p class="text-sm font-semibold text-green-800">Toutes vos résidences sont disponibles</p>
        </div>
    </div>
    @endif

    {{-- Activate Form --}}
    @if(!$activeMode)
    <form method="POST" action="{{ route('owner.vacation-mode.activate') }}" class="bg-white rounded-2xl border border-gray-100 p-6 space-y-5">
        @csrf

        <h2 class="font-semibold text-gray-900">Activer le mode vacances</h2>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Date de début *</label>
                <input type="date" name="start_date" value="{{ old('start_date') }}" class="w-full rounded-xl border-gray-200 focus:ring-orange-500 focus:border-orange-500 text-sm" required>
                @error('start_date') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Date de fin *</label>
                <input type="date" name="end_date" value="{{ old('end_date') }}" class="w-full rounded-xl border-gray-200 focus:ring-orange-500 focus:border-orange-500 text-sm" required>
                @error('end_date') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
        </div>

        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">Résidences concernées</label>
            <div class="space-y-2 mt-2">
                @foreach($residences as $r)
                <label class="flex items-center gap-3 p-3 rounded-xl border border-gray-100 hover:bg-gray-50 cursor-pointer transition-colors">
                    <input type="checkbox" name="residence_ids[]" value="{{ $r->id }}" class="rounded border-gray-300 text-orange-500 focus:ring-orange-500" {{ in_array($r->id, old('residence_ids', [])) ? 'checked' : '' }}>
                    <span class="text-sm text-gray-700">{{ $r->name }}</span>
                </label>
                @endforeach
            </div>
            <p class="text-xs text-gray-400 mt-1">Ne rien sélectionner = toutes les résidences</p>
        </div>

        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">Raison (optionnel)</label>
            <input type="text" name="reason" value="{{ old('reason') }}" class="w-full rounded-xl border-gray-200 focus:ring-orange-500 focus:border-orange-500 text-sm" placeholder="Ex: Voyage en famille">
        </div>

        <button type="submit" class="w-full py-2.5 bg-amber-500 text-white font-semibold rounded-xl hover:bg-amber-600 transition-all text-sm">
            Activer le mode vacances
        </button>
    </form>
    @endif

    {{-- History --}}
    @if($history->count())
    <div class="bg-white rounded-2xl border border-gray-100 p-6">
        <h2 class="font-semibold text-gray-900 mb-4">Historique</h2>
        <div class="space-y-3">
            @foreach($history as $mode)
            <div class="flex items-center justify-between py-3 {{ !$loop->last ? 'border-b border-gray-50' : '' }}">
                <div>
                    <p class="text-sm font-medium text-gray-700">{{ $mode->start_date->format('d/m/Y') }} — {{ $mode->end_date->format('d/m/Y') }}</p>
                    @if($mode->reason)
                    <p class="text-xs text-gray-400">{{ $mode->reason }}</p>
                    @endif
                </div>
                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold uppercase {{ $mode->is_active ? 'bg-amber-100 text-amber-700' : 'bg-gray-100 text-gray-500' }}">
                    {{ $mode->is_active ? 'Actif' : 'Terminé' }}
                </span>
            </div>
            @endforeach
        </div>
    </div>
    @endif
</div>
@endsection
