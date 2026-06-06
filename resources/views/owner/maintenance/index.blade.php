@extends('layouts.owner')

@section('title', 'Maintenance & Incidents — ReziApp')

@section('owner-content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Maintenance & Incidents</h1>
            <p class="text-sm text-gray-500 mt-1">Suivez les demandes d'intervention</p>
        </div>
        <a href="{{ route('owner.maintenance.create') }}" class="inline-flex items-center gap-2 px-5 py-2.5 bg-gray-900 text-white font-semibold rounded-xl hover:bg-gray-800 transition-all text-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
            Nouvelle demande
        </a>
    </div>

    {{-- Stats --}}
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-3 sm:gap-4">
        @foreach([
            ['label' => 'Total', 'value' => $stats['total'], 'color' => 'gray'],
            ['label' => 'En cours', 'value' => $stats['open'], 'color' => 'blue'],
            ['label' => 'Urgentes', 'value' => $stats['urgent'], 'color' => 'red'],
            ['label' => 'Résolues', 'value' => $stats['resolved'], 'color' => 'green'],
            ['label' => 'Délai moy.', 'value' => round($stats['avg_resolution_days'], 1) . 'j', 'color' => 'purple'],
        ] as $stat)
        <div class="bg-white rounded-2xl border border-gray-100 p-4">
            <p class="text-xs font-semibold text-gray-400 uppercase">{{ $stat['label'] }}</p>
            <p class="text-2xl font-bold text-gray-900 mt-1">{{ $stat['value'] }}</p>
        </div>
        @endforeach
    </div>

    {{-- Filters --}}
    <div class="bg-white rounded-2xl border border-gray-100 p-4">
        <form method="GET" class="flex flex-wrap gap-3 items-end">
            <div>
                <label class="block text-xs font-semibold text-gray-500 mb-1">Statut</label>
                <select name="status" class="rounded-xl border-gray-200 text-sm py-2 px-3">
                    <option value="">Tous</option>
                    @foreach(\App\Models\MaintenanceRequest::STATUSES as $key => $label)
                        <option value="{{ $key }}" {{ request('status') === $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-500 mb-1">Priorité</label>
                <select name="priority" class="rounded-xl border-gray-200 text-sm py-2 px-3">
                    <option value="">Toutes</option>
                    @foreach(\App\Models\MaintenanceRequest::PRIORITIES as $key => $label)
                        <option value="{{ $key }}" {{ request('priority') === $key ? 'selected' : '' }}>{{ $label }}</option>
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

    {{-- List --}}
    <div class="space-y-3">
        @forelse($requests as $req)
        <a href="{{ route('owner.maintenance.show', $req) }}" class="block bg-white rounded-2xl border border-gray-100 p-5 hover:shadow-sm transition-shadow">
            <div class="flex items-start justify-between gap-4">
                <div class="flex items-start gap-4 min-w-0">
                    <div class="w-10 h-10 rounded-xl bg-{{ $req->priority_color }}-100 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 text-{{ $req->priority_color }}-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17 17.25 21A2.652 2.652 0 0 0 21 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 1 1-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.14a4.5 4.5 0 0 0 4.486-6.336l-3.276 3.277a3.004 3.004 0 0 1-2.25-2.25l3.276-3.276a4.5 4.5 0 0 0-6.336 4.486c.091 1.076-.071 2.264-.904 2.95l-.102.085" /></svg>
                    </div>
                    <div class="min-w-0">
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-mono text-gray-400">{{ $req->reference }}</span>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold uppercase bg-{{ $req->status_color }}-100 text-{{ $req->status_color }}-700">{{ $req->status_label }}</span>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold uppercase bg-{{ $req->priority_color }}-100 text-{{ $req->priority_color }}-700">{{ $req->priority_label }}</span>
                        </div>
                        <p class="font-semibold text-gray-900 mt-1 truncate">{{ $req->title }}</p>
                        <p class="text-sm text-gray-500 mt-0.5">{{ $req->residence?->name ?? '—' }} · {{ $req->category_label }}</p>
                    </div>
                </div>
                <div class="text-right shrink-0">
                    <p class="text-xs text-gray-400">{{ $req->created_at->diffForHumans() }}</p>
                    @if($req->assignee)
                        <p class="text-xs text-gray-500 mt-1">→ {{ $req->assignee->name }}</p>
                    @endif
                </div>
            </div>
        </a>
        @empty
        <div class="bg-white rounded-2xl border border-gray-100 p-12 text-center">
            <svg class="w-12 h-12 mx-auto text-gray-300 mb-3" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17 17.25 21A2.652 2.652 0 0 0 21 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 1 1-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.14a4.5 4.5 0 0 0 4.486-6.336l-3.276 3.277a3.004 3.004 0 0 1-2.25-2.25l3.276-3.276a4.5 4.5 0 0 0-6.336 4.486c.091 1.076-.071 2.264-.904 2.95l-.102.085" /></svg>
            <p class="text-gray-400 font-medium">Aucune demande de maintenance</p>
        </div>
        @endforelse
    </div>

    @if($requests->hasPages())
    <div>{{ $requests->withQueryString()->links() }}</div>
    @endif
</div>
@endsection
