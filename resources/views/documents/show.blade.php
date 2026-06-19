@extends('layouts.app')

@section('title', $document->name)

@section('content')
<div class="max-w-3xl mx-auto px-4 py-8">
    <a href="{{ route('documents.index') }}" class="text-sm text-gray-500 hover:text-gray-700 inline-flex items-center gap-1 mb-6">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        Retour aux documents
    </a>

    <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
        <div class="p-6 border-b">
            <div class="flex items-start justify-between">
                <div class="flex items-start gap-4">
                    <div class="w-12 h-12 rounded-lg bg-primary-50 flex items-center justify-center shrink-0">
                        <svg class="w-6 h-6 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                    </div>
                    <div>
                        <h1 class="text-xl font-bold text-gray-900">{{ $document->name }}</h1>
                        <p class="text-sm text-gray-500 mt-1">Ajouté le {{ $document->created_at->format('d/m/Y à H:i') }}</p>
                    </div>
                </div>
                <a href="{{ route('documents.download', $document) }}" class="btn-primary inline-flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    Télécharger
                </a>
            </div>
        </div>

        <div class="p-6 space-y-4">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <span class="text-sm text-gray-500">Type</span>
                    <p class="font-medium text-gray-900">{{ $document->type_label ?? $document->type }}</p>
                </div>
                <div>
                    <span class="text-sm text-gray-500">Accès</span>
                    <p class="font-medium text-gray-900">
                        @if($document->access_type === 'public')
                            <span class="inline-flex items-center gap-1 text-green-600">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064"/></svg>
                                Public
                            </span>
                        @elseif($document->access_type === 'conversation')
                            <span class="text-blue-600">Conversations</span>
                        @else
                            <span class="text-gray-600">Privé</span>
                        @endif
                    </p>
                </div>
                <div>
                    <span class="text-sm text-gray-500">Taille</span>
                    <p class="font-medium text-gray-900">
                        @if($document->file_size)
                            {{ $document->file_size > 1048576 ? number_format($document->file_size / 1048576, 1) . ' Mo' : number_format($document->file_size / 1024, 0) . ' Ko' }}
                        @else
                            —
                        @endif
                    </p>
                </div>
                <div>
                    <span class="text-sm text-gray-500">Téléchargements</span>
                    <p class="font-medium text-gray-900">{{ $document->download_count ?? 0 }}</p>
                </div>
            </div>

            @if($document->residence)
            <div class="pt-4 border-t">
                <span class="text-sm text-gray-500">Résidence associée</span>
                <p class="font-medium text-gray-900 mt-1">{{ $document->residence->name }}</p>
            </div>
            @endif

            @if($document->expires_at)
            <div class="pt-4 border-t">
                <span class="text-sm text-gray-500">Expiration</span>
                <p class="font-medium {{ $document->expires_at->isPast() ? 'text-red-600' : 'text-gray-900' }}">
                    {{ $document->expires_at->format('d/m/Y à H:i') }}
                    @if($document->expires_at->isPast())
                        <span class="text-xs text-red-500 ml-1">(expiré)</span>
                    @endif
                </p>
            </div>
            @endif
        </div>

        @if($document->user_id === auth()->id())
        <div class="p-6 border-t bg-gray-50 flex items-center justify-end gap-3">
            <form action="{{ route('documents.destroy', $document) }}" method="POST"  data-confirm='Supprimer ce document ?'>
                @csrf
                @method('DELETE')
                <button type="submit" class="text-sm text-red-600 hover:text-red-800 font-medium">Supprimer</button>
            </form>
        </div>
        @endif
    </div>
</div>
@endsection
