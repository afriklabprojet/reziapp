@extends('layouts.owner')

@section('title', 'États des lieux')

@section('owner-content')
<div class="space-y-6">

    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
                <span class="text-2xl">🏠</span> États des lieux
            </h1>
            <p class="text-gray-500 text-sm mt-1">Gérez vos états des lieux numériques</p>
        </div>
        <a href="{{ route('owner.property-inspections.create') }}"
            class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white rounded-xl font-semibold text-sm hover:bg-indigo-700 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Nouvel état des lieux
        </a>
    </div>

    {{-- Filtres --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4">
        <form method="GET" class="flex flex-wrap gap-3">
            <select name="type" class="px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-indigo-500 focus:border-indigo-500">
                <option value="">Tous les types</option>
                <option value="check_in" {{ request('type') === 'check_in' ? 'selected' : '' }}>Entrée</option>
                <option value="check_out" {{ request('type') === 'check_out' ? 'selected' : '' }}>Sortie</option>
                <option value="periodic" {{ request('type') === 'periodic' ? 'selected' : '' }}>Périodique</option>
            </select>
            <select name="status" class="px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-indigo-500 focus:border-indigo-500">
                <option value="">Tous les statuts</option>
                <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>Brouillon</option>
                <option value="in_progress" {{ request('status') === 'in_progress' ? 'selected' : '' }}>En cours</option>
                <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Complété</option>
                <option value="signed" {{ request('status') === 'signed' ? 'selected' : '' }}>Signé</option>
            </select>
            <button type="submit" class="px-4 py-2 bg-gray-100 rounded-lg text-sm font-medium hover:bg-gray-200 transition">
                Filtrer
            </button>
        </form>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        @if($inspections->isEmpty())
            <div class="py-16 text-center text-gray-400">
                <div class="text-5xl mb-3">🏠</div>
                <p class="font-medium text-gray-500">Aucun état des lieux</p>
            </div>
        @else
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-100">
                    <tr>
                        <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Référence</th>
                        <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Type</th>
                        <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Résidence</th>
                        <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Locataire</th>
                        <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Date</th>
                        <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Statut</th>
                        <th class="px-5 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @foreach($inspections as $inspection)
                    @php
                        $typeColors = ['check_in' => 'blue', 'check_out' => 'pink', 'periodic' => 'yellow'];
                        $typeLabels = ['check_in' => 'Entrée ➜', 'check_out' => '← Sortie', 'periodic' => '○ Périodique'];
                        $statusColors = ['draft' => 'gray', 'in_progress' => 'blue', 'completed' => 'amber', 'signed' => 'emerald'];
                        $statusLabels = ['draft' => 'Brouillon', 'in_progress' => 'En cours', 'completed' => 'Complété', 'signed' => 'Signé'];
                    @endphp
                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-5 py-4 font-mono text-xs text-gray-500">{{ $inspection->reference }}</td>
                        <td class="px-5 py-4">
                            <span class="px-2.5 py-1 rounded-full text-xs font-semibold
                                bg-{{ $typeColors[$inspection->type] ?? 'gray' }}-100
                                text-{{ $typeColors[$inspection->type] ?? 'gray' }}-700">
                                {{ $typeLabels[$inspection->type] ?? $inspection->type }}
                            </span>
                        </td>
                        <td class="px-5 py-4 text-sm text-gray-700 max-w-36 truncate">{{ $inspection->residence->title }}</td>
                        <td class="px-5 py-4 text-sm text-gray-700">{{ $inspection->tenant?->name ?? '—' }}</td>
                        <td class="px-5 py-4 text-sm text-gray-500">{{ $inspection->inspection_date->format('d/m/Y') }}</td>
                        <td class="px-5 py-4">
                            <span class="px-2.5 py-1 rounded-full text-xs font-semibold
                                bg-{{ $statusColors[$inspection->status] ?? 'gray' }}-100
                                text-{{ $statusColors[$inspection->status] ?? 'gray' }}-700">
                                {{ $statusLabels[$inspection->status] ?? $inspection->status }}
                            </span>
                        </td>
                        <td class="px-5 py-4">
                            <a href="{{ route('owner.property-inspections.show', $inspection) }}"
                                class="text-indigo-600 hover:text-indigo-800 text-sm font-medium">Voir →</a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if($inspections->hasPages())
        <div class="px-5 py-4 border-t border-gray-50">
            {{ $inspections->withQueryString()->links() }}
        </div>
        @endif
        @endif
    </div>

</div>
@endsection
