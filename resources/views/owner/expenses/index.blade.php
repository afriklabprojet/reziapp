@extends('layouts.owner')

@section('title', 'Charges & Dépenses — Rezi App')

@section('owner-content')
<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Charges & Dépenses</h1>
            <p class="text-sm text-gray-500 mt-1">Suivez toutes vos dépenses par résidence</p>
        </div>
        <a href="{{ route('owner.expenses.create') }}"
           class="inline-flex items-center gap-2 px-5 py-2.5 bg-gray-900 text-white font-semibold rounded-xl hover:bg-gray-800 transition-all text-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
            Nouvelle dépense
        </a>
    </div>

    {{-- Summary Cards --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-2xl border border-gray-100 p-5">
            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Total {{ $summary['year'] }}</p>
            <p class="text-2xl font-bold text-gray-900 mt-2">{{ number_format($summary['total'], 0, ',', ' ') }} <span class="text-sm font-medium text-gray-500">FCFA</span></p>
        </div>
        <div class="bg-white rounded-2xl border border-gray-100 p-5">
            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Nb dépenses</p>
            <p class="text-2xl font-bold text-gray-900 mt-2">{{ $summary['count'] }}</p>
        </div>
        @php $topCategory = collect($summary['by_category'])->sortByDesc('total')->first(); @endphp
        <div class="bg-white rounded-2xl border border-gray-100 p-5">
            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Poste principal</p>
            <p class="text-lg font-bold text-gray-900 mt-2">{{ $topCategory['label'] ?? '—' }}</p>
            <p class="text-sm text-gray-500">{{ number_format($topCategory['total'] ?? 0, 0, ',', ' ') }} FCFA</p>
        </div>
        <div class="bg-white rounded-2xl border border-gray-100 p-5">
            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Moy. mensuelle</p>
            <p class="text-2xl font-bold text-gray-900 mt-2">{{ number_format($summary['total'] / max(1, now()->month), 0, ',', ' ') }} <span class="text-sm font-medium text-gray-500">FCFA</span></p>
        </div>
    </div>

    {{-- Filters --}}
    <div class="bg-white rounded-2xl border border-gray-100 p-4">
        <form method="GET" class="flex flex-wrap gap-3 items-end">
            <div>
                <label class="block text-xs font-semibold text-gray-500 mb-1">Catégorie</label>
                <select name="category" class="rounded-xl border-gray-200 text-sm py-2 px-3">
                    <option value="">Toutes</option>
                    @foreach(\App\Models\Expense::CATEGORIES as $key => $label)
                        <option value="{{ $key }}" {{ request('category') === $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-500 mb-1">Résidence</label>
                <select name="residence_id" class="rounded-xl border-gray-200 text-sm py-2 px-3">
                    <option value="">Toutes</option>
                    @foreach($residences as $r)
                        <option value="{{ $r->id }}" {{ request('residence_id') == $r->id ? 'selected' : '' }}>{{ $r->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-500 mb-1">Du</label>
                <input type="date" name="start_date" value="{{ request('start_date') }}" class="rounded-xl border-gray-200 text-sm py-2 px-3">
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-500 mb-1">Au</label>
                <input type="date" name="end_date" value="{{ request('end_date') }}" class="rounded-xl border-gray-200 text-sm py-2 px-3">
            </div>
            <button type="submit" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-sm font-medium rounded-xl transition-colors">Filtrer</button>
        </form>
    </div>

    {{-- Table --}}
    <div class="bg-white rounded-2xl border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Date</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Résidence</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Catégorie</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Description</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Montant</th>
                        <th class="px-5 py-3 text-center text-xs font-semibold text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($expenses as $expense)
                    <tr class="hover:bg-gray-50/50 transition-colors">
                        <td class="px-5 py-3.5 whitespace-nowrap text-gray-600">{{ $expense->expense_date->format('d/m/Y') }}</td>
                        <td class="px-5 py-3.5 text-gray-900 font-medium">{{ $expense->residence?->name ?? '—' }}</td>
                        <td class="px-5 py-3.5">
                            <span class="inline-flex items-center px-2.5 py-1 rounded-lg bg-gray-100 text-xs font-medium text-gray-700">{{ $expense->category_label }}</span>
                        </td>
                        <td class="px-5 py-3.5 text-gray-600 max-w-xs truncate">{{ $expense->description }}</td>
                        <td class="px-5 py-3.5 text-right font-semibold text-gray-900">{{ number_format($expense->amount, 0, ',', ' ') }} F</td>
                        <td class="px-5 py-3.5 text-center">
                            <div class="flex items-center justify-center gap-2">
                                <a href="{{ route('owner.expenses.edit', $expense) }}" class="p-1.5 rounded-lg hover:bg-gray-100 text-gray-400 hover:text-gray-700 transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125" /></svg>
                                </a>
                                <form method="POST" action="{{ route('owner.expenses.destroy', $expense) }}" onsubmit="return confirm('Supprimer cette dépense ?')">
                                    @csrf @method('DELETE')
                                    <button class="p-1.5 rounded-lg hover:bg-red-50 text-gray-400 hover:text-red-600 transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" /></svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-5 py-12 text-center text-gray-400">
                            <svg class="w-12 h-12 mx-auto text-gray-300 mb-3" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm3 0h.008v.008H18V10.5Zm-12 0h.008v.008H6V10.5Z" /></svg>
                            <p class="font-medium">Aucune dépense enregistrée</p>
                            <a href="{{ route('owner.expenses.create') }}" class="text-[#CC5A00] hover:text-[#A34700] text-sm font-medium mt-1 inline-block">Ajouter votre première dépense</a>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($expenses->hasPages())
        <div class="px-5 py-3 border-t border-gray-100">
            {{ $expenses->withQueryString()->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
