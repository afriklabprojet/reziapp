@extends('layouts.owner')

@section('title', 'Séquences de messages — Rezi Studio Meublé Faya')

@section('owner-content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Séquences de messages</h1>
            <p class="text-sm text-gray-500 mt-1">Automatisez vos communications avec les voyageurs</p>
        </div>
        <div class="flex gap-2">
            <form action="{{ route('owner.sequences.create-defaults') }}" method="POST">
                @csrf
                <button type="submit" class="inline-flex items-center gap-2 px-4 py-2.5 bg-blue-50 text-blue-700 font-semibold rounded-xl hover:bg-blue-100 transition-all text-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09Z" /></svg>
                    Créer les séquences par défaut
                </button>
            </form>
            <a href="{{ route('owner.sequences.create') }}" class="inline-flex items-center gap-2 px-5 py-2.5 bg-gray-900 text-white font-semibold rounded-xl hover:bg-gray-800 transition-all text-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
                Nouvelle séquence
            </a>
        </div>
    </div>

    <div class="space-y-3">
        @forelse($sequences as $seq)
        <a href="{{ route('owner.sequences.show', $seq) }}" class="block bg-white rounded-2xl border border-gray-100 p-5 hover:shadow-sm transition-shadow">
            <div class="flex items-center justify-between gap-4">
                <div class="flex items-center gap-4 min-w-0">
                    <div class="w-10 h-10 rounded-xl {{ $seq->is_active ? 'bg-green-100' : 'bg-gray-100' }} flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 {{ $seq->is_active ? 'text-green-600' : 'text-gray-400' }}" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" /></svg>
                    </div>
                    <div class="min-w-0">
                        <div class="flex items-center gap-2">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold uppercase {{ $seq->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">{{ $seq->is_active ? 'Active' : 'Inactive' }}</span>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold uppercase bg-blue-100 text-blue-700">{{ \App\Models\MessageSequence::TRIGGERS[$seq->trigger_event] ?? $seq->trigger_event }}</span>
                        </div>
                        <p class="font-semibold text-gray-900 mt-1 truncate">{{ $seq->name }}</p>
                        <p class="text-sm text-gray-500">{{ $seq->steps_count }} étape(s) · {{ $seq->residence?->name ?? 'Toutes résidences' }}</p>
                    </div>
                </div>
                <svg class="w-5 h-5 text-gray-300" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" /></svg>
            </div>
        </a>
        @empty
        <div class="bg-white rounded-2xl border border-gray-100 p-12 text-center">
            <svg class="w-12 h-12 mx-auto text-gray-300 mb-3" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" /></svg>
            <p class="text-gray-400 font-medium">Aucune séquence créée</p>
            <p class="text-xs text-gray-400 mt-1">Créez des séquences pour automatiser vos communications</p>
        </div>
        @endforelse
    </div>

    @if($sequences->hasPages())
    <div>{{ $sequences->withQueryString()->links() }}</div>
    @endif
</div>
@endsection
