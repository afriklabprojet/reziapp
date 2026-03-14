@extends('layouts.owner')

@section('title', 'Comparaison entrée/sortie')

@section('owner-content')
<div class="space-y-6">

    <nav class="text-sm text-gray-400">
        <a href="{{ route('owner.property-inspections.index') }}" class="hover:text-indigo-600">États des lieux</a>
        <span class="mx-2">›</span>
        <span class="text-gray-700">Comparaison — {{ $residence->title }}</span>
    </nav>

    <h1 class="text-2xl font-bold text-gray-900">📊 Comparaison entrée / sortie</h1>
    <p class="text-gray-500 text-sm">Résidence : <span class="font-medium text-gray-700">{{ $residence->title }}</span></p>

    @if(!isset($comparison) || !$comparison)
    <div class="bg-amber-50 border border-amber-200 rounded-xl p-6 text-center">
        <div class="text-3xl mb-2">⚠️</div>
        <p class="text-amber-700 font-medium">Impossible de comparer</p>
        <p class="text-sm text-amber-600 mt-1">Il faut un état des lieux d'entrée signé et un état des lieux de sortie pour effectuer la comparaison.</p>
    </div>
    @else

    {{-- Résumé --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 text-center">
            <div class="text-3xl font-bold text-red-500">{{ count($comparison['degraded_items'] ?? []) }}</div>
            <div class="text-xs text-gray-500 mt-1">Dégradations constatées</div>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 text-center">
            <div class="text-2xl font-bold text-red-600">{{ number_format($comparison['total_repair_cost'] ?? 0, 0, ',', ' ') }}</div>
            <div class="text-xs text-gray-500 mt-1">FCFA estimation réparations</div>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 text-center">
            <div class="font-mono text-sm text-gray-700">{{ $comparison['check_in']?->reference ?? '—' }}</div>
            <div class="text-xs text-gray-500 mt-1">Réf. entrée</div>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 text-center">
            <div class="font-mono text-sm text-gray-700">{{ $comparison['check_out']?->reference ?? '—' }}</div>
            <div class="text-xs text-gray-500 mt-1">Réf. sortie</div>
        </div>
    </div>

    @if(!empty($comparison['degraded_items']))
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="p-5 border-b border-gray-100">
            <h2 class="font-semibold text-gray-800">Éléments dégradés entre l'entrée et la sortie</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-100">
                    <tr>
                        <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Pièce</th>
                        <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Élément</th>
                        <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">État entrée</th>
                        <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">État sortie</th>
                        <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Coût estimé</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @php
                        $condLabels = ['new' => 'Neuf', 'good' => 'Bon', 'fair' => 'Passable', 'damaged' => 'Abîmé'];
                        $condColors = ['new' => 'emerald', 'good' => 'blue', 'fair' => 'amber', 'damaged' => 'red'];
                    @endphp
                    @foreach($comparison['degraded_items'] as $item)
                    <tr class="hover:bg-red-50 transition">
                        <td class="px-5 py-4 text-sm text-gray-700">{{ $item['room'] }}</td>
                        <td class="px-5 py-4 text-sm font-medium text-gray-900">{{ $item['element'] }}</td>
                        <td class="px-5 py-4">
                            <span class="px-2 py-0.5 rounded-full text-xs font-semibold
                                bg-{{ $condColors[$item['check_in_condition']] ?? 'gray' }}-100
                                text-{{ $condColors[$item['check_in_condition']] ?? 'gray' }}-700">
                                {{ $condLabels[$item['check_in_condition']] ?? '—' }}
                            </span>
                        </td>
                        <td class="px-5 py-4">
                            <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-red-100 text-red-700">
                                {{ $condLabels[$item['check_out_condition']] ?? '—' }}
                            </span>
                        </td>
                        <td class="px-5 py-4 text-sm font-semibold {{ $item['repair_estimate'] ? 'text-red-600' : 'text-gray-400' }}">
                            {{ $item['repair_estimate'] ? number_format($item['repair_estimate'], 0, ',', ' ') . ' FCFA' : '—' }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
                @if($comparison['total_repair_cost'] > 0)
                <tfoot>
                    <tr class="bg-red-50">
                        <td colspan="4" class="px-5 py-3 font-semibold text-gray-700">Total estimé</td>
                        <td class="px-5 py-3 font-bold text-red-600">
                            {{ number_format($comparison['total_repair_cost'], 0, ',', ' ') }} FCFA
                        </td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>
    @else
    <div class="bg-emerald-50 border border-emerald-200 rounded-xl p-6 text-center">
        <div class="text-3xl mb-2">✅</div>
        <p class="text-emerald-700 font-medium">Aucune dégradation constatée</p>
        <p class="text-sm text-emerald-600 mt-1">Le logement a été restitué dans le même état qu'à l'entrée.</p>
    </div>
    @endif
    @endif
</div>
@endsection
