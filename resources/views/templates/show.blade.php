@extends('layouts.app')

@section('title', $template->name)

@section('content')
<div class="max-w-2xl mx-auto px-4 py-8">
    <a href="{{ route('templates.index') }}" class="text-sm text-gray-500 hover:text-gray-700 inline-flex items-center gap-1 mb-6">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        Retour aux modèles
    </a>

    <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
        <div class="p-6 border-b">
            <div class="flex items-start justify-between">
                <div>
                    <div class="flex items-center gap-3">
                        <h1 class="text-xl font-bold text-gray-900">{{ $template->name }}</h1>
                        @if($template->is_active)
                        <span class="badge badge-success">Actif</span>
                        @else
                        <span class="badge bg-gray-100 text-gray-600">Inactif</span>
                        @endif
                    </div>
                    <p class="text-sm text-gray-500 mt-1">
                        {{ $template->category_label ?? $template->category }}
                        @if($template->shortcut)
                        — Raccourci : <code class="bg-gray-100 px-1.5 py-0.5 rounded text-primary-600">/{{ $template->shortcut }}</code>
                        @endif
                    </p>
                </div>
                @if(!$template->is_system && $template->user_id === auth()->id())
                <div class="flex items-center gap-2">
                    <a href="{{ route('templates.edit', $template) }}" class="btn-secondary text-sm">Modifier</a>
                    <form action="{{ route('templates.destroy', $template) }}" method="POST"  data-confirm='Supprimer ce modèle ?'>
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-sm text-red-600 hover:text-red-800 font-medium px-3 py-2">Supprimer</button>
                    </form>
                </div>
                @endif
            </div>
        </div>

        <div class="p-6">
            <h2 class="text-sm font-medium text-gray-500 mb-3">Contenu du message</h2>
            <div class="bg-gray-50 rounded-lg p-4 text-gray-800 whitespace-pre-wrap leading-relaxed">{{ $template->content }}</div>

            @if($template->variables && count($template->variables))
            <div class="mt-4">
                <h3 class="text-sm font-medium text-gray-500 mb-2">Variables détectées</h3>
                <div class="flex flex-wrap gap-2">
                    @foreach($template->variables as $variable)
                    <span class="inline-block bg-primary-50 text-primary-700 text-xs px-2.5 py-1 rounded-full font-medium">{{ '{' . $variable . '}' }}</span>
                    @endforeach
                </div>
            </div>
            @endif
        </div>

        <div class="p-4 sm:p-6 border-t bg-gray-50 grid grid-cols-3 gap-2 sm:gap-4 text-center text-xs sm:text-sm">
            <div>
                <p class="text-gray-500">Utilisations</p>
                <p class="font-bold text-gray-900">{{ $template->usage_count ?? 0 }}</p>
            </div>
            <div>
                <p class="text-gray-500">Langue</p>
                <p class="font-bold text-gray-900">{{ strtoupper($template->language ?? 'FR') }}</p>
            </div>
            <div>
                <p class="text-gray-500">Créé le</p>
                <p class="font-bold text-gray-900">{{ $template->created_at->format('d/m/Y') }}</p>
            </div>
        </div>
    </div>
</div>
@endsection
