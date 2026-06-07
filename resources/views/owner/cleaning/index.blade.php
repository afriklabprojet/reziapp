@extends('layouts.owner')

@section('title', 'Gestion du ménage — Rezi Studio Meublé Faya')

@section('owner-content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Gestion du ménage</h1>
            <p class="text-sm text-gray-500 mt-1">Planifiez et suivez les tâches de nettoyage</p>
        </div>
        <a href="{{ route('owner.cleaning.create') }}" class="inline-flex items-center gap-2 px-5 py-2.5 bg-gray-900 text-white font-semibold rounded-xl hover:bg-gray-800 transition-all text-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
            Planifier un ménage
        </a>
    </div>

    {{-- Upcoming --}}
    @if($upcoming->count())
    <div class="bg-blue-50 border border-blue-200 rounded-2xl p-4">
        <p class="text-sm font-semibold text-blue-800 mb-2">🕐 {{ $upcoming->count() }} tâche(s) à venir dans les 48h</p>
        @foreach($upcoming as $task)
        <p class="text-xs text-blue-700">• {{ $task->residence?->name }} — {{ $task->scheduled_date->format('d/m à H:i') }}</p>
        @endforeach
    </div>
    @endif

    {{-- Filters --}}
    <div class="bg-white rounded-2xl border border-gray-100 p-4">
        <form method="GET" class="flex flex-wrap gap-3 items-end">
            <div>
                <label class="block text-xs font-semibold text-gray-500 mb-1">Statut</label>
                <select name="status" class="rounded-xl border-gray-200 text-sm py-2 px-3">
                    <option value="">Tous</option>
                    @foreach(\App\Models\CleaningTask::STATUSES as $key => $label)
                        <option value="{{ $key }}" {{ request('status') === $key ? 'selected' : '' }}>{{ $label }}</option>
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

    {{-- Task List --}}
    <div class="space-y-3">
        @forelse($tasks as $task)
        <a href="{{ route('owner.cleaning.show', $task) }}" class="block bg-white rounded-2xl border border-gray-100 p-5 hover:shadow-sm transition-shadow">
            <div class="flex items-center justify-between gap-4">
                <div class="flex items-center gap-4 min-w-0">
                    <div class="w-10 h-10 rounded-xl bg-{{ $task->status_color }}-100 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 text-{{ $task->status_color }}-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09ZM18.259 8.715 18 9.75l-.259-1.035a3.375 3.375 0 0 0-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 0 0 2.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 0 0 2.455 2.456L21.75 6l-1.036.259a3.375 3.375 0 0 0-2.455 2.456ZM16.894 20.567 16.5 21.75l-.394-1.183a2.25 2.25 0 0 0-1.423-1.423L13.5 18.75l1.183-.394a2.25 2.25 0 0 0 1.423-1.423l.394-1.183.394 1.183a2.25 2.25 0 0 0 1.423 1.423l1.183.394-1.183.394a2.25 2.25 0 0 0-1.423 1.423Z" /></svg>
                    </div>
                    <div class="min-w-0">
                        <div class="flex items-center gap-2">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold uppercase bg-{{ $task->status_color }}-100 text-{{ $task->status_color }}-700">{{ $task->status_label }}</span>
                        </div>
                        <p class="font-semibold text-gray-900 mt-1 truncate">{{ $task->residence?->name ?? 'Résidence inconnue' }}</p>
                        <p class="text-sm text-gray-500">{{ $task->scheduled_date->format('d/m/Y à H:i') }} · {{ $task->cleaner_name ?? 'Non assigné' }}</p>
                    </div>
                </div>
                <div class="text-right shrink-0">
                    @php $done = $task->checklist ? collect($task->checklist)->where('done', true)->count() : 0; $total = $task->checklist ? count($task->checklist) : 0; @endphp
                    @if($total > 0)
                    <p class="text-sm font-semibold text-gray-900">{{ $done }}/{{ $total }}</p>
                    <p class="text-xs text-gray-400">tâches</p>
                    @endif
                </div>
            </div>
        </a>
        @empty
        <div class="bg-white rounded-2xl border border-gray-100 p-12 text-center">
            <svg class="w-12 h-12 mx-auto text-gray-300 mb-3" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09Z" /></svg>
            <p class="text-gray-400 font-medium">Aucune tâche de ménage</p>
        </div>
        @endforelse
    </div>

    @if($tasks->hasPages())
    <div>{{ $tasks->withQueryString()->links() }}</div>
    @endif
</div>
@endsection
