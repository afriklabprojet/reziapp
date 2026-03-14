@extends('layouts.owner')

@section('title', 'Quittances de loyer')

@section('owner-content')
<div class="space-y-6">

    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
                <span class="text-2xl">🧾</span> Quittances de loyer
            </h1>
            <p class="text-gray-500 text-sm mt-1">Émettez et gérez les quittances de vos locataires</p>
        </div>
        <a href="{{ route('owner.rent-receipts.create') }}"
            class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-xl font-semibold text-sm hover:bg-blue-700 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Nouvelle quittance
        </a>
    </div>

    {{-- Filtres --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4">
        <form method="GET" class="flex flex-wrap gap-3">
            <select name="year" class="px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500">
                @for($y = now()->year; $y >= now()->year - 3; $y--)
                <option value="{{ $y }}" {{ request('year', now()->year) == $y ? 'selected' : '' }}>{{ $y }}</option>
                @endfor
            </select>
            <select name="month" class="px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500">
                <option value="">Tous les mois</option>
                @foreach(range(1,12) as $m)
                <option value="{{ $m }}" {{ request('month') == $m ? 'selected' : '' }}>
                    {{ \Carbon\Carbon::create(null, $m)->translatedFormat('F') }}
                </option>
                @endforeach
            </select>
            <button type="submit" class="px-4 py-2 bg-gray-100 rounded-lg text-sm font-medium hover:bg-gray-200 transition">
                Filtrer
            </button>
        </form>
    </div>

    {{-- Résumé mensuel --}}
    @isset($monthlySummary)
    <div class="grid grid-cols-3 sm:grid-cols-6 gap-3">
        @foreach($monthlySummary as $ms)
        <div class="bg-white rounded-xl border border-gray-100 p-3 text-center">
            <div class="text-xs text-gray-400">{{ \Carbon\Carbon::create(null, $ms['month'])->format('M') }}</div>
            <div class="font-bold text-sm {{ $ms['count'] > 0 ? 'text-blue-600' : 'text-gray-300' }}">
                {{ $ms['count'] > 0 ? number_format($ms['total'], 0, ',', ' ') : '—' }}
            </div>
            @if($ms['count'] > 0)
            <div class="text-xs text-gray-400">{{ $ms['count'] }} quitt.</div>
            @endif
        </div>
        @endforeach
    </div>
    @endisset

    {{-- Liste des quittances --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        @if($receipts->isEmpty())
            <div class="py-16 text-center text-gray-400">
                <div class="text-5xl mb-3">🧾</div>
                <p class="font-medium text-gray-500">Aucune quittance trouvée</p>
                <p class="text-sm mt-1">Créez votre première quittance en cliquant sur "Nouvelle quittance"</p>
            </div>
        @else
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-100">
                    <tr>
                        <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Référence</th>
                        <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Locataire</th>
                        <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Période</th>
                        <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Total</th>
                        <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Envoyée</th>
                        <th class="px-5 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @foreach($receipts as $receipt)
                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-5 py-4 font-mono text-xs text-gray-500">{{ $receipt->reference }}</td>
                        <td class="px-5 py-4">
                            <div class="font-medium text-gray-900">{{ $receipt->tenant->name }}</div>
                        </td>
                        <td class="px-5 py-4 text-sm text-gray-500">{{ $receipt->period_label }}</td>
                        <td class="px-5 py-4 font-semibold text-gray-900">
                            {{ number_format($receipt->total_amount, 0, ',', ' ') }} FCFA
                        </td>
                        <td class="px-5 py-4">
                            @if($receipt->was_sent)
                                <span class="text-xs text-emerald-600 font-medium">✓ Envoyée</span>
                            @else
                                <span class="text-xs text-gray-400">Non envoyée</span>
                            @endif
                        </td>
                        <td class="px-5 py-4 flex items-center gap-3">
                            <a href="{{ route('owner.rent-receipts.show', $receipt) }}"
                                class="text-blue-600 hover:text-blue-800 text-sm font-medium">Voir</a>
                            <a href="{{ route('owner.rent-receipts.download', $receipt) }}"
                                class="text-red-500 hover:text-red-700 text-sm font-medium">PDF</a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if($receipts->hasPages())
        <div class="px-5 py-4 border-t border-gray-50">
            {{ $receipts->withQueryString()->links() }}
        </div>
        @endif
        @endif
    </div>

</div>
@endsection
