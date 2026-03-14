@extends('layouts.owner')

@section('title', 'Relevés de compteurs — REZI')

@section('owner-content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Relevés de compteurs</h1>
            <p class="text-sm text-gray-500 mt-1">Suivez la consommation eau, électricité et gaz</p>
        </div>
        <a href="{{ route('owner.utilities.create') }}" class="inline-flex items-center gap-2 px-4 py-2.5 bg-gray-900 text-white font-semibold rounded-xl hover:bg-gray-800 text-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
            Nouveau relevé
        </a>
    </div>

    {{-- Alerts --}}
    @if($alerts->isNotEmpty())
    <div class="bg-amber-50 rounded-2xl border border-amber-200 p-4">
        <h3 class="text-sm font-bold text-amber-800 mb-2">⚠️ Alertes de consommation</h3>
        <div class="space-y-2">
            @foreach($alerts as $alert)
            <div class="flex items-center justify-between gap-4 text-sm">
                <div>
                    <span class="font-medium text-amber-900">{{ $alert->residence?->name }}</span>
                    <span class="text-amber-700">— {{ \App\Models\UtilityReading::TYPES[$alert->utility_type] ?? $alert->utility_type }}</span>
                    <span class="text-amber-600 text-xs ml-2">+{{ number_format($alert->percentage_increase, 0) }}%</span>
                </div>
                <form action="{{ route('owner.utilities.dismiss-alert', $alert) }}" method="POST">
                    @csrf @method('PATCH')
                    <button type="submit" class="text-xs text-amber-600 hover:text-amber-800 underline">Ignorer</button>
                </form>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Filter --}}
    <form class="flex flex-wrap items-center gap-3">
        <select name="residence_id" onchange="this.form.submit()" class="rounded-xl border-gray-200 text-sm py-2 px-3">
            <option value="">Toutes les résidences</option>
            @foreach($residences as $r)
            <option value="{{ $r->id }}" {{ request('residence_id') == $r->id ? 'selected' : '' }}>{{ $r->name }}</option>
            @endforeach
        </select>
        <select name="utility_type" onchange="this.form.submit()" class="rounded-xl border-gray-200 text-sm py-2 px-3">
            <option value="">Tous les types</option>
            @foreach(\App\Models\UtilityReading::TYPES as $k => $l)
            <option value="{{ $k }}" {{ request('utility_type') === $k ? 'selected' : '' }}>{{ $l }}</option>
            @endforeach
        </select>
    </form>

    {{-- Readings --}}
    <div class="bg-white rounded-2xl border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="text-left text-xs font-semibold text-gray-500 uppercase px-4 py-3">Résidence</th>
                        <th class="text-left text-xs font-semibold text-gray-500 uppercase px-4 py-3">Type</th>
                        <th class="text-left text-xs font-semibold text-gray-500 uppercase px-4 py-3">Date</th>
                        <th class="text-right text-xs font-semibold text-gray-500 uppercase px-4 py-3">Valeur</th>
                        <th class="text-right text-xs font-semibold text-gray-500 uppercase px-4 py-3">Conso</th>
                        <th class="text-right text-xs font-semibold text-gray-500 uppercase px-4 py-3">Coût</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($readings as $reading)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-sm text-gray-900">{{ $reading->residence?->name }}</td>
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold uppercase {{ $reading->utility_type === 'electricity' ? 'bg-yellow-100 text-yellow-700' : ($reading->utility_type === 'water' ? 'bg-blue-100 text-blue-700' : 'bg-orange-100 text-orange-700') }}">
                                {{ \App\Models\UtilityReading::TYPES[$reading->utility_type] ?? $reading->utility_type }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-600">{{ $reading->reading_date->format('d/m/Y') }}</td>
                        <td class="px-4 py-3 text-sm text-gray-900 text-right font-mono">{{ number_format($reading->value, 2, ',', ' ') }}</td>
                        <td class="px-4 py-3 text-sm text-right">
                            @if($reading->consumption)
                            <span class="{{ $reading->is_anomaly ? 'text-red-600 font-semibold' : 'text-gray-600' }}">{{ number_format($reading->consumption, 2, ',', ' ') }}</span>
                            @else
                            <span class="text-gray-400">-</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-900 text-right font-semibold">{{ $reading->cost ? number_format($reading->cost, 0, ',', ' ') . ' F' : '-' }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-gray-400">Aucun relevé enregistré</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($readings->hasPages())
    <div>{{ $readings->withQueryString()->links() }}</div>
    @endif
</div>
@endsection
