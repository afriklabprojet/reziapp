@extends('layouts.owner')

@section('title', 'Détail ménage — ReziApp')

@section('owner-content')
<div class="max-w-2xl mx-auto space-y-6">
    <div>
        <a href="{{ route('owner.cleaning.index') }}" class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-700">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" /></svg>
            Retour
        </a>
    </div>

    {{-- Header --}}
    <div class="bg-white rounded-2xl border border-gray-100 p-6">
        <div class="flex items-center justify-between mb-4">
            <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold uppercase bg-{{ $task->status_color }}-100 text-{{ $task->status_color }}-700">{{ $task->status_label }}</span>
            <p class="text-xs text-gray-400">Créé {{ $task->created_at->diffForHumans() }}</p>
        </div>
        <h1 class="text-xl font-bold text-gray-900">{{ $task->residence?->name ?? 'Résidence' }}</h1>
        <p class="text-sm text-gray-500 mt-1">Planifié : {{ $task->scheduled_date->format('d/m/Y à H:i') }}</p>
        @if($task->cleaner_name)
        <p class="text-sm text-gray-500 mt-0.5">Prestataire : {{ $task->cleaner_name }} {{ $task->cleaner_phone ? '· '.$task->cleaner_phone : '' }}</p>
        @endif
        @if($task->completed_at)
        <p class="text-sm text-green-600 mt-1 font-medium">Terminé le {{ $task->completed_at->format('d/m/Y à H:i') }}</p>
        @endif
    </div>

    {{-- Checklist --}}
    <div class="bg-white rounded-2xl border border-gray-100 p-6">
        <h2 class="font-semibold text-gray-900 mb-4">Checklist</h2>
        @if($task->checklist && count($task->checklist))
        <div class="space-y-2">
            @foreach($task->checklist as $item)
            <div class="flex items-center gap-3 py-2 px-3 rounded-xl {{ ($item['done'] ?? false) ? 'bg-green-50' : 'bg-gray-50' }}">
                @if($item['done'] ?? false)
                    <svg class="w-5 h-5 text-green-500 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                @else
                    <svg class="w-5 h-5 text-gray-300 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" /></svg>
                @endif
                <span class="text-sm {{ ($item['done'] ?? false) ? 'text-green-700 line-through' : 'text-gray-700' }}">{{ $item['label'] ?? '' }}</span>
            </div>
            @endforeach
        </div>
        @php $done = collect($task->checklist)->where('done', true)->count(); $total = count($task->checklist); @endphp
        <div class="mt-4 bg-gray-100 rounded-full h-2 overflow-hidden">
            <div class="bg-green-500 h-full rounded-full transition-all" style="width: {{ $total > 0 ? round($done / $total * 100) : 0 }}%"></div>
        </div>
        <p class="text-xs text-gray-500 mt-1">{{ $done }}/{{ $total }} tâches complétées</p>
        @else
        <p class="text-sm text-gray-400">Aucune checklist définie</p>
        @endif
    </div>

    {{-- Notes --}}
    @if($task->notes)
    <div class="bg-white rounded-2xl border border-gray-100 p-6">
        <h2 class="font-semibold text-gray-900 mb-2">Notes</h2>
        <p class="text-sm text-gray-700 whitespace-pre-line">{{ $task->notes }}</p>
    </div>
    @endif

    {{-- Actions --}}
    <div class="flex flex-wrap gap-3">
        @if($task->status === 'pending' || $task->status === 'in_progress')
        <form method="POST" action="{{ route('owner.cleaning.complete', $task) }}">
            @csrf @method('PATCH')
            <button type="submit" class="px-5 py-2.5 bg-green-600 text-white font-semibold rounded-xl hover:bg-green-700 transition-colors text-sm">
                Marquer comme terminé
            </button>
        </form>
        @endif

        @if($task->status === 'completed')
        <form method="POST" action="{{ route('owner.cleaning.verify', $task) }}">
            @csrf @method('PATCH')
            <button type="submit" class="px-5 py-2.5 bg-blue-600 text-white font-semibold rounded-xl hover:bg-blue-700 transition-colors text-sm">
                Vérifier et valider
            </button>
        </form>
        @endif

        <form method="POST" action="{{ route('owner.cleaning.destroy', $task) }}" onsubmit="return confirm('Supprimer cette tâche ?')">
            @csrf @method('DELETE')
            <button type="submit" class="px-5 py-2.5 bg-red-50 text-red-600 font-semibold rounded-xl hover:bg-red-100 transition-colors text-sm">
                Supprimer
            </button>
        </form>
    </div>
</div>
@endsection
