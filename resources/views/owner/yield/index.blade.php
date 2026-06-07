@extends('layouts.owner')

@section('title', 'Yield Management — Rezi App')

@section('owner-content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Yield Management</h1>
            <p class="text-sm text-gray-500 mt-1">Tarification dynamique automatique</p>
        </div>
    </div>

    {{-- Current Prices --}}
    <div class="bg-white rounded-2xl border border-gray-100 p-6">
        <h2 class="text-lg font-bold text-gray-900 mb-4">Prix actuels suggérés</h2>
        <div class="space-y-4">
            @forelse($residences as $residence)
            <div class="border border-gray-100 rounded-xl p-4">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <p class="font-semibold text-gray-900">{{ $residence->name }}</p>
                        <p class="text-sm text-gray-500">
                            Prix de base: {{ number_format($residence->price_per_night ?? $residence->price_per_day ?? 0, 0, ',', ' ') }} F/jour
                        </p>
                    </div>
                    <div class="text-right">
                        @php $suggested = $yieldData[$residence->id] ?? null; @endphp
                        @if($suggested)
                        <p class="text-xl font-bold {{ $suggested['multiplier'] > 1 ? 'text-green-600' : ($suggested['multiplier'] < 1 ? 'text-red-600' : 'text-gray-900') }}">{{ number_format($suggested['suggested_price'], 0, ',', ' ') }} F</p>
                        <p class="text-xs text-gray-500">{{ $suggested['reason'] }}</p>
                        @else
                        <p class="text-sm text-gray-400">Pas de suggestion</p>
                        @endif
                    </div>
                </div>
            </div>
            @empty
            <p class="text-gray-400 text-center py-4">Aucune résidence</p>
            @endforelse
        </div>
    </div>

    {{-- Settings --}}
    <div class="bg-white rounded-2xl border border-gray-100 p-6">
        <h2 class="text-lg font-bold text-gray-900 mb-4">Paramètres de tarification</h2>
        <form action="{{ route('owner.yield.update-settings') }}" method="POST" class="space-y-5">
            @csrf @method('PATCH')
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-xs font-semibold text-gray-500 mb-1">Variation max hausse (%)</label>
                    <input type="number" name="max_increase" value="{{ $settings['max_increase'] ?? 50 }}" min="0" max="200" class="w-full rounded-xl border-gray-200 text-sm py-2 px-3">
                    <p class="text-xs text-gray-400 mt-1">Limite haute vs prix de base</p>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 mb-1">Variation max baisse (%)</label>
                    <input type="number" name="max_decrease" value="{{ $settings['max_decrease'] ?? 30 }}" min="0" max="50" class="w-full rounded-xl border-gray-200 text-sm py-2 px-3">
                    <p class="text-xs text-gray-400 mt-1">Limite basse vs prix de base</p>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 mb-1">Prime week-end (%)</label>
                    <input type="number" name="weekend_premium" value="{{ $settings['weekend_premium'] ?? 15 }}" min="0" max="100" class="w-full rounded-xl border-gray-200 text-sm py-2 px-3">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 mb-1">Prime haute saison (%)</label>
                    <input type="number" name="high_season_premium" value="{{ $settings['high_season_premium'] ?? 25 }}" min="0" max="100" class="w-full rounded-xl border-gray-200 text-sm py-2 px-3">
                </div>
            </div>
            <div class="flex items-center gap-3">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="auto_apply" value="1" {{ $settings['auto_apply'] ?? false ? 'checked' : '' }} class="rounded border-gray-300 text-gray-900">
                    <span class="text-sm text-gray-700">Appliquer automatiquement les suggestions</span>
                </label>
            </div>
            <button type="submit" class="px-6 py-2.5 bg-gray-900 text-white font-semibold rounded-xl hover:bg-gray-800 text-sm">Enregistrer</button>
        </form>
    </div>

    {{-- Gap Nights --}}
    <div class="bg-white rounded-2xl border border-gray-100 p-6">
        <h2 class="text-lg font-bold text-gray-900 mb-4">Nuits isolées (gaps)</h2>
        <p class="text-sm text-gray-500 mb-4">Ces créneaux courts entre réservations sont difficiles à vendre. Appliquez une réduction pour les remplir.</p>
        @if(count($gaps ?? []) > 0)
        <div class="space-y-3">
            @foreach($gaps as $gap)
            <div class="flex items-center justify-between gap-4 border border-amber-100 bg-amber-50 rounded-xl p-4">
                <div>
                    <p class="font-semibold text-gray-900">{{ $gap['residence'] }}</p>
                    <p class="text-sm text-amber-700">{{ $gap['date']->format('d/m/Y') }} — {{ $gap['nights'] }} nuit(s) isolée(s)</p>
                </div>
                <form action="{{ route('owner.yield.apply-gap-discount') }}" method="POST">
                    @csrf
                    <input type="hidden" name="residence_id" value="{{ $gap['residence_id'] }}">
                    <input type="hidden" name="date" value="{{ $gap['date']->format('Y-m-d') }}">
                    <button type="submit" class="px-3 py-1.5 bg-amber-600 text-white rounded-lg text-xs font-semibold hover:bg-amber-700">-20%</button>
                </form>
            </div>
            @endforeach
        </div>
        @else
        <p class="text-gray-400 text-center py-4">Aucune nuit isolée détectée</p>
        @endif
    </div>
</div>
@endsection
