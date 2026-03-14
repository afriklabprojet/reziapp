@extends('layouts.owner')

@section('title', 'Contrats de bail')

@section('owner-content')
<div class="space-y-6">

    {{-- En-tête --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
                <span class="text-2xl">📄</span> Contrats de bail
            </h1>
            <p class="text-gray-500 text-sm mt-1">Gérez vos contrats de location</p>
        </div>
        <a href="{{ route('owner.lease-contracts.create') }}"
            class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 text-white rounded-xl font-semibold text-sm hover:bg-emerald-700 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Nouveau contrat
        </a>
    </div>

    {{-- Statistiques --}}
    @isset($stats)
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 text-center">
            <div class="text-3xl font-bold text-emerald-600">{{ $stats['active'] ?? 0 }}</div>
            <div class="text-xs text-gray-500 mt-1">Actifs</div>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 text-center">
            <div class="text-3xl font-bold text-amber-500">{{ $stats['pending_signature'] ?? 0 }}</div>
            <div class="text-xs text-gray-500 mt-1">En attente signature</div>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 text-center">
            <div class="text-3xl font-bold text-gray-500">{{ $stats['terminated'] ?? 0 }}</div>
            <div class="text-xs text-gray-500 mt-1">Résiliés</div>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 text-center">
            <div class="text-2xl font-bold text-blue-600">{{ number_format($stats['total_monthly_revenue'] ?? 0, 0, ',', ' ') }}</div>
            <div class="text-xs text-gray-500 mt-1">FCFA/mois (actifs)</div>
        </div>
    </div>
    @endisset

    {{-- Filtres --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4">
        <form method="GET" class="flex flex-wrap gap-3">
            <select name="status" class="px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-emerald-500 focus:border-emerald-500">
                <option value="">Tous les statuts</option>
                <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>Brouillon</option>
                <option value="pending_owner" {{ request('status') === 'pending_owner' ? 'selected' : '' }}>En attente bailleur</option>
                <option value="pending_tenant" {{ request('status') === 'pending_tenant' ? 'selected' : '' }}>En attente locataire</option>
                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Actif</option>
                <option value="terminated" {{ request('status') === 'terminated' ? 'selected' : '' }}>Résilié</option>
                <option value="expired" {{ request('status') === 'expired' ? 'selected' : '' }}>Expiré</option>
            </select>
            <input type="text" name="search" value="{{ request('search') }}"
                placeholder="Locataire ou référence..."
                class="flex-1 min-w-40 px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-emerald-500 focus:border-emerald-500">
            <button type="submit" class="px-4 py-2 bg-gray-100 rounded-lg text-sm font-medium hover:bg-gray-200 transition">
                Filtrer
            </button>
        </form>
    </div>

    {{-- Liste des contrats --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        @if($contracts->isEmpty())
            <div class="py-16 text-center text-gray-400">
                <div class="text-5xl mb-3">📄</div>
                <p class="font-medium text-gray-500">Aucun contrat de bail</p>
                <p class="text-sm mt-1">Créez votre premier contrat en cliquant sur "Nouveau contrat"</p>
            </div>
        @else
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-100">
                    <tr>
                        <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Référence</th>
                        <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Locataire</th>
                        <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Résidence</th>
                        <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Loyer/mois</th>
                        <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Période</th>
                        <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Statut</th>
                        <th class="px-5 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @foreach($contracts as $contract)
                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-5 py-4 font-mono text-xs text-gray-500">{{ $contract->reference }}</td>
                        <td class="px-5 py-4">
                            <div class="font-medium text-gray-900">{{ $contract->tenant->name }}</div>
                            <div class="text-xs text-gray-400">{{ $contract->tenant->email }}</div>
                        </td>
                        <td class="px-5 py-4 text-sm text-gray-700 max-w-36 truncate">{{ $contract->residence->title }}</td>
                        <td class="px-5 py-4 text-sm font-semibold text-gray-900">
                            {{ number_format($contract->monthly_rent, 0, ',', ' ') }} FCFA
                        </td>
                        <td class="px-5 py-4 text-xs text-gray-500">
                            {{ $contract->start_date->format('d/m/Y') }}
                            @if($contract->end_date) → {{ $contract->end_date->format('d/m/Y') }} @endif
                        </td>
                        <td class="px-5 py-4">
                            <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-{{ $contract->status_color }}-100 text-{{ $contract->status_color }}-700">
                                {{ $contract->status_label }}
                            </span>
                        </td>
                        <td class="px-5 py-4">
                            <a href="{{ route('owner.lease-contracts.show', $contract) }}"
                                class="text-emerald-600 hover:text-emerald-800 text-sm font-medium">Voir →</a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if($contracts->hasPages())
        <div class="px-5 py-4 border-t border-gray-50">
            {{ $contracts->withQueryString()->links() }}
        </div>
        @endif
        @endif
    </div>

</div>
@endsection
