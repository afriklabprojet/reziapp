@extends('layouts.owner')

@section('title', 'Dépôts de garantie')

@section('owner-content')
<div class="space-y-6">

    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
                <span class="text-2xl">💰</span> Dépôts de garantie
            </h1>
            <p class="text-gray-500 text-sm mt-1">Suivi des cautions de vos locataires</p>
        </div>
        <a href="{{ route('owner.security-deposits.create') }}"
            class="inline-flex items-center gap-2 px-4 py-2 bg-amber-500 text-white rounded-xl font-semibold text-sm hover:bg-amber-600 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Nouveau dépôt
        </a>
    </div>

    @isset($stats)
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 text-center">
            <div class="text-3xl font-bold text-amber-500">{{ $stats['pending'] ?? 0 }}</div>
            <div class="text-xs text-gray-500 mt-1">En attente</div>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 text-center">
            <div class="text-3xl font-bold text-emerald-600">{{ $stats['held'] ?? 0 }}</div>
            <div class="text-xs text-gray-500 mt-1">Encaissés</div>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 text-center">
            <div class="text-3xl font-bold text-red-500">{{ $stats['overdue'] ?? 0 }}</div>
            <div class="text-xs text-gray-500 mt-1">En retard de restitution</div>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 text-center">
            <div class="text-2xl font-bold text-blue-600">{{ number_format($stats['total_held'] ?? 0, 0, ',', ' ') }}</div>
            <div class="text-xs text-gray-500 mt-1">FCFA détenus</div>
        </div>
    </div>
    @endisset

    {{-- Alerte dépôts en retard --}}
    @isset($overdueDeposits)
    @if($overdueDeposits->isNotEmpty())
    <div class="bg-red-50 border border-red-200 rounded-xl p-4">
        <div class="font-semibold text-red-700 mb-2">⚠️ {{ $overdueDeposits->count() }} dépôt(s) à restituer en urgence</div>
        @foreach($overdueDeposits as $dep)
        <div class="flex justify-between items-center text-sm text-red-600 py-1 border-t border-red-100">
            <span>{{ $dep->tenant->name }} — {{ $dep->reference }}</span>
            <a href="{{ route('owner.security-deposits.show', $dep) }}" class="font-semibold underline">Traiter →</a>
        </div>
        @endforeach
    </div>
    @endif
    @endisset

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        @if($deposits->isEmpty())
            <div class="py-16 text-center text-gray-400">
                <div class="text-5xl mb-3">💰</div>
                <p class="font-medium text-gray-500">Aucun dépôt de garantie</p>
            </div>
        @else
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-100">
                    <tr>
                        <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Référence</th>
                        <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Locataire</th>
                        <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Montant</th>
                        <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Payé le</th>
                        <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Délai restitution</th>
                        <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Statut</th>
                        <th class="px-5 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @foreach($deposits as $deposit)
                    <tr class="hover:bg-gray-50 transition {{ $deposit->is_overdue ? 'bg-red-50' : '' }}">
                        <td class="px-5 py-4 font-mono text-xs text-gray-500">{{ $deposit->reference }}</td>
                        <td class="px-5 py-4">
                            <div class="font-medium text-gray-900">{{ $deposit->tenant->name }}</div>
                        </td>
                        <td class="px-5 py-4 font-semibold text-gray-900">
                            {{ number_format($deposit->amount, 0, ',', ' ') }} FCFA
                        </td>
                        <td class="px-5 py-4 text-sm text-gray-500">
                            {{ $deposit->paid_at ? $deposit->paid_at->format('d/m/Y') : '—' }}
                        </td>
                        <td class="px-5 py-4 text-sm {{ $deposit->is_overdue ? 'text-red-600 font-semibold' : 'text-gray-500' }}">
                            {{ $deposit->return_deadline ? $deposit->return_deadline->format('d/m/Y') : '—' }}
                            @if($deposit->is_overdue) ⚠️ @endif
                        </td>
                        <td class="px-5 py-4">
                            <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-{{ $deposit->status_color }}-100 text-{{ $deposit->status_color }}-700">
                                {{ $deposit->status_label }}
                            </span>
                        </td>
                        <td class="px-5 py-4">
                            <a href="{{ route('owner.security-deposits.show', $deposit) }}"
                                class="text-amber-600 hover:text-amber-800 text-sm font-medium">Voir →</a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if($deposits->hasPages())
        <div class="px-5 py-4 border-t border-gray-50">
            {{ $deposits->withQueryString()->links() }}
        </div>
        @endif
        @endif
    </div>

</div>
@endsection
