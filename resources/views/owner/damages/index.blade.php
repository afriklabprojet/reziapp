@extends('layouts.owner')

@section('title', 'Rapports de dommages — Rezi Studio Meublé Faya')

@section('owner-content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Rapports de dommages</h1>
            <p class="text-sm text-gray-500 mt-1">Suivez les dégradations et réclamations</p>
        </div>
        <a href="{{ route('owner.damages.create') }}" class="inline-flex items-center gap-2 px-5 py-2.5 bg-gray-900 text-white font-semibold rounded-xl hover:bg-gray-800 transition-all text-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
            Nouveau rapport
        </a>
    </div>

    <div class="bg-white rounded-2xl border border-gray-100 p-4">
        <form method="GET" class="flex flex-wrap gap-3 items-end">
            <div>
                <label class="block text-xs font-semibold text-gray-500 mb-1">Statut</label>
                <select name="status" class="rounded-xl border-gray-200 text-sm py-2 px-3">
                    <option value="">Tous</option>
                    @foreach(\App\Models\DamageReport::STATUSES as $key => $label)
                        <option value="{{ $key }}" {{ request('status') === $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-500 mb-1">Gravité</label>
                <select name="severity" class="rounded-xl border-gray-200 text-sm py-2 px-3">
                    <option value="">Toutes</option>
                    @foreach(\App\Models\DamageReport::SEVERITIES as $key => $label)
                        <option value="{{ $key }}" {{ request('severity') === $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-500 mb-1">Résidence</label>
                <select name="residence_id" class="rounded-xl border-gray-200 text-sm py-2 px-3">
                    <option value="">Toutes</option>
                    @foreach($residences as $r) <option value="{{ $r->id }}" {{ request('residence_id') == $r->id ? 'selected' : '' }}>{{ $r->name }}</option> @endforeach
                </select>
            </div>
            <button type="submit" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-sm font-medium rounded-xl transition-colors">Filtrer</button>
        </form>
    </div>

    <div class="space-y-3">
        @forelse($reports as $report)
        <a href="{{ route('owner.damages.show', $report) }}" class="block bg-white rounded-2xl border border-gray-100 p-5 hover:shadow-sm transition-shadow">
            <div class="flex items-center justify-between gap-4">
                <div class="flex items-center gap-4 min-w-0">
                    <div class="w-10 h-10 rounded-xl bg-{{ \App\Models\DamageReport::SEVERITY_COLORS[$report->severity] ?? 'gray' }}-100 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 text-{{ \App\Models\DamageReport::SEVERITY_COLORS[$report->severity] ?? 'gray' }}-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" /></svg>
                    </div>
                    <div class="min-w-0">
                        <div class="flex items-center gap-2">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold uppercase bg-{{ \App\Models\DamageReport::SEVERITY_COLORS[$report->severity] ?? 'gray' }}-100 text-{{ \App\Models\DamageReport::SEVERITY_COLORS[$report->severity] ?? 'gray' }}-700">{{ \App\Models\DamageReport::SEVERITIES[$report->severity] ?? $report->severity }}</span>
                            <span class="text-xs text-gray-400">{{ $report->reference }}</span>
                        </div>
                        <p class="font-semibold text-gray-900 mt-1 truncate">{{ $report->title }}</p>
                        <p class="text-sm text-gray-500">{{ $report->residence?->name }} · {{ \App\Models\DamageReport::CATEGORIES[$report->category] ?? $report->category }}</p>
                    </div>
                </div>
                <div class="text-right shrink-0">
                    @if($report->estimated_cost)
                    <p class="text-sm font-semibold text-gray-900">{{ number_format($report->estimated_cost, 0, ',', ' ') }} FCFA</p>
                    <p class="text-xs text-gray-400">estimé</p>
                    @endif
                </div>
            </div>
        </a>
        @empty
        <div class="bg-white rounded-2xl border border-gray-100 p-12 text-center">
            <svg class="w-12 h-12 mx-auto text-gray-300 mb-3" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
            <p class="text-gray-400 font-medium">Aucun rapport de dommage</p>
        </div>
        @endforelse
    </div>

    @if($reports->hasPages())
    <div>{{ $reports->withQueryString()->links() }}</div>
    @endif
</div>
@endsection
