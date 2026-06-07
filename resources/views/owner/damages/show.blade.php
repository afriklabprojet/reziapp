@extends('layouts.owner')

@section('title', $damage->reference . ' — Rezi Studio Meublé Faya')

@section('owner-content')
<div class="space-y-6">
    <div>
        <a href="{{ route('owner.damages.index') }}" class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-700 mb-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" /></svg>
            Rapports
        </a>
        <h1 class="text-2xl font-bold text-gray-900">{{ $damage->title }}</h1>
        <p class="text-sm text-gray-500 mt-1">{{ $damage->reference }} · {{ $damage->residence?->name }}</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white rounded-2xl border border-gray-100 p-6">
                <h2 class="text-lg font-bold text-gray-900 mb-4">Détails</h2>
                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <p class="text-gray-500">Catégorie</p>
                        <p class="font-semibold text-gray-900">{{ \App\Models\DamageReport::CATEGORIES[$damage->category] ?? $damage->category }}</p>
                    </div>
                    <div>
                        <p class="text-gray-500">Gravité</p>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-bold uppercase bg-{{ \App\Models\DamageReport::SEVERITY_COLORS[$damage->severity] ?? 'gray' }}-100 text-{{ \App\Models\DamageReport::SEVERITY_COLORS[$damage->severity] ?? 'gray' }}-700">{{ \App\Models\DamageReport::SEVERITIES[$damage->severity] ?? $damage->severity }}</span>
                    </div>
                    <div>
                        <p class="text-gray-500">Statut</p>
                        <p class="font-semibold text-gray-900">{{ \App\Models\DamageReport::STATUSES[$damage->status] ?? $damage->status }}</p>
                    </div>
                    <div>
                        <p class="text-gray-500">Signalé le</p>
                        <p class="font-semibold text-gray-900">{{ $damage->created_at->format('d/m/Y à H:i') }}</p>
                    </div>
                </div>
                <div class="mt-4 pt-4 border-t border-gray-100">
                    <p class="text-gray-500 text-sm mb-2">Description</p>
                    <p class="text-gray-900 whitespace-pre-wrap">{{ $damage->description }}</p>
                </div>
            </div>

            @if($damage->photos && count($damage->photos))
            <div class="bg-white rounded-2xl border border-gray-100 p-6">
                <h2 class="text-lg font-bold text-gray-900 mb-4">Photos</h2>
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
                    @foreach($damage->photos as $photo)
                    <a href="{{ Storage::url($photo) }}" target="_blank" class="block aspect-square rounded-xl overflow-hidden bg-gray-100">
                        <img src="{{ Storage::url($photo) }}" alt="Photo dommage" class="w-full h-full object-cover">
                    </a>
                    @endforeach
                </div>
            </div>
            @endif
        </div>

        <div class="space-y-6">
            <div class="bg-white rounded-2xl border border-gray-100 p-6">
                <h2 class="text-lg font-bold text-gray-900 mb-4">Coûts</h2>
                <div class="space-y-3 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-500">Estimé</span>
                        <span class="font-semibold">{{ number_format($damage->estimated_cost ?? 0, 0, ',', ' ') }} FCFA</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Réel</span>
                        <span class="font-semibold">{{ number_format($damage->actual_cost ?? 0, 0, ',', ' ') }} FCFA</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Déduit caution</span>
                        <span class="font-semibold">{{ number_format($damage->deducted_amount ?? 0, 0, ',', ' ') }} FCFA</span>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-2xl border border-gray-100 p-6">
                <h2 class="text-lg font-bold text-gray-900 mb-4">Mettre à jour le statut</h2>
                <form action="{{ route('owner.damages.status', $damage) }}" method="POST" class="space-y-4">
                    @csrf @method('PATCH')
                    <select name="status" class="w-full rounded-xl border-gray-200 text-sm py-3 px-4">
                        @foreach(\App\Models\DamageReport::STATUSES as $key => $label)
                            <option value="{{ $key }}" {{ $damage->status === $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    <input type="number" name="actual_cost" value="{{ $damage->actual_cost }}" placeholder="Coût réel (FCFA)"
                           class="w-full rounded-xl border-gray-200 text-sm py-3 px-4">
                    <button type="submit" class="w-full px-4 py-2.5 bg-gray-900 text-white font-semibold rounded-xl hover:bg-gray-800 text-sm">Mettre à jour</button>
                </form>
            </div>

            <form action="{{ route('owner.damages.destroy', $damage) }}" method="POST" onsubmit="return confirm('Supprimer ce rapport ?')">
                @csrf @method('DELETE')
                <button type="submit" class="w-full text-sm text-red-500 hover:text-red-700 text-center">Supprimer ce rapport</button>
            </form>
        </div>
    </div>
</div>
@endsection
