@extends('layouts.owner')

@section('title', 'Guides de bienvenue — ReziApp')

@section('owner-content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Guides de bienvenue</h1>
            <p class="text-sm text-gray-500 mt-1">Créez des guides personnalisés pour vos voyageurs</p>
        </div>
        <a href="{{ route('owner.guidebooks.create') }}" class="inline-flex items-center gap-2 px-5 py-2.5 bg-gray-900 text-white font-semibold rounded-xl hover:bg-gray-800 transition-all text-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
            Nouveau guide
        </a>
    </div>

    <div class="space-y-3">
        @forelse($guidebooks as $guidebook)
        <a href="{{ route('owner.guidebooks.show', $guidebook) }}" class="block bg-white rounded-2xl border border-gray-100 p-5 hover:shadow-sm transition-shadow">
            <div class="flex items-center justify-between gap-4">
                <div class="flex items-center gap-4 min-w-0">
                    <div class="w-10 h-10 rounded-xl {{ $guidebook->is_published ? 'bg-green-100' : 'bg-gray-100' }} flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 {{ $guidebook->is_published ? 'text-green-600' : 'text-gray-400' }}" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25" /></svg>
                    </div>
                    <div class="min-w-0">
                        <div class="flex items-center gap-2">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold uppercase {{ $guidebook->is_published ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">{{ $guidebook->is_published ? 'Publié' : 'Brouillon' }}</span>
                        </div>
                        <p class="font-semibold text-gray-900 mt-1 truncate">{{ $guidebook->title }}</p>
                        <p class="text-sm text-gray-500">{{ $guidebook->residence?->name }} · {{ $guidebook->sections()->count() }} section(s)</p>
                    </div>
                </div>
                <svg class="w-5 h-5 text-gray-300" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" /></svg>
            </div>
        </a>
        @empty
        <div class="bg-white rounded-2xl border border-gray-100 p-12 text-center">
            <svg class="w-12 h-12 mx-auto text-gray-300 mb-3" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25" /></svg>
            <p class="text-gray-400 font-medium">Aucun guide créé</p>
            <p class="text-xs text-gray-400 mt-1">Créez un guide pour améliorer l'expérience de vos voyageurs</p>
        </div>
        @endforelse
    </div>

    @if($guidebooks->hasPages())
    <div>{{ $guidebooks->withQueryString()->links() }}</div>
    @endif
</div>
@endsection
