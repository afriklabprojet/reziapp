@extends('layouts.owner')

@section('title', 'Serrures connectées — Rezi App')

@section('owner-content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Serrures connectées</h1>
            <p class="text-sm text-gray-500 mt-1">Gérez les codes d'accès automatiques pour vos résidences</p>
        </div>
    </div>

    {{-- Add Lock Form --}}
    <div class="bg-white rounded-2xl border border-gray-100 p-6">
        <h2 class="text-lg font-bold text-gray-900 mb-4">Ajouter une serrure</h2>
        <form action="{{ route('owner.smart-locks.store') }}" method="POST" class="space-y-4">
            @csrf
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-gray-500 mb-1">Résidence *</label>
                    <select name="residence_id" required class="w-full rounded-xl border-gray-200 text-sm py-2 px-3">
                        <option value="">Sélectionnez...</option>
                        @foreach($residences as $r) <option value="{{ $r->id }}">{{ $r->name }}</option> @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 mb-1">Fournisseur *</label>
                    <select name="provider" required class="w-full rounded-xl border-gray-200 text-sm py-2 px-3">
                        @foreach(\App\Models\SmartLock::PROVIDERS as $k => $l)
                        <option value="{{ $k }}">{{ $l }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-500 mb-1">Nom de la serrure *</label>
                <input type="text" name="name" required class="w-full rounded-xl border-gray-200 text-sm py-2 px-3" placeholder="Porte d'entrée principale">
            </div>
            <button type="submit" class="px-4 py-2.5 bg-gray-900 text-white font-semibold rounded-xl hover:bg-gray-800 text-sm">Ajouter la serrure</button>
        </form>
    </div>

    {{-- Existing Locks --}}
    <div class="space-y-3">
        @forelse($locks as $lock)
        <a href="{{ route('owner.smart-locks.show', $lock) }}" class="block bg-white rounded-2xl border border-gray-100 p-5 hover:shadow-sm transition-shadow">
            <div class="flex items-center justify-between gap-4">
                <div class="flex items-center gap-4 min-w-0">
                    <div class="w-10 h-10 rounded-xl {{ $lock->is_active ? 'bg-green-100' : 'bg-gray-100' }} flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 {{ $lock->is_active ? 'text-green-600' : 'text-gray-400' }}" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 0 1 3 3m3 0a6 6 0 0 1-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1 1 21.75 8.25Z" /></svg>
                    </div>
                    <div class="min-w-0">
                        <div class="flex items-center gap-2">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold uppercase bg-blue-100 text-blue-700">{{ \App\Models\SmartLock::PROVIDERS[$lock->provider] ?? $lock->provider }}</span>
                        </div>
                        <p class="font-semibold text-gray-900 mt-1 truncate">{{ $lock->name }}</p>
                        <p class="text-sm text-gray-500">{{ $lock->residence?->name }} · {{ $lock->codes->count() }} code(s) actif(s)</p>
                    </div>
                </div>
                <svg class="w-5 h-5 text-gray-300" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" /></svg>
            </div>
        </a>
        @empty
        <div class="bg-white rounded-2xl border border-gray-100 p-12 text-center">
            <svg class="w-12 h-12 mx-auto text-gray-300 mb-3" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 0 1 3 3m3 0a6 6 0 0 1-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1 1 21.75 8.25Z" /></svg>
            <p class="text-gray-400 font-medium">Aucune serrure connectée</p>
        </div>
        @endforelse
    </div>

    @if($locks->hasPages())
    <div>{{ $locks->withQueryString()->links() }}</div>
    @endif
</div>
@endsection
