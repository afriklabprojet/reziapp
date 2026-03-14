@extends('layouts.app')

@section('title', 'Mes documents')

@section('content')
<div class="max-w-4xl mx-auto px-4 py-8">
    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Mes documents</h1>
            <p class="text-gray-600 mt-1">Gérez vos documents partagés</p>
        </div>
        <a href="{{ route('documents.create') }}" class="btn-primary">
            <svg class="w-5 h-5 mr-1 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Ajouter
        </a>
    </div>

    @if($documents->isEmpty())
    <div class="bg-white rounded-xl shadow-sm border p-12 text-center">
        <svg class="w-16 h-16 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
        <h3 class="text-lg font-medium text-gray-900 mb-2">Aucun document</h3>
        <p class="text-gray-600 mb-4">Vous n'avez pas encore ajouté de documents.</p>
        <a href="{{ route('documents.create') }}" class="btn-primary">Ajouter un document</a>
    </div>
    @else
    <div class="space-y-4">
        @foreach($documents as $document)
        <div class="bg-white rounded-xl shadow-sm border p-5 hover:shadow-md transition-shadow">
            <div class="flex items-start justify-between">
                <div class="flex items-start gap-4">
                    <div class="w-10 h-10 rounded-lg bg-primary-50 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                    </div>
                    <div>
                        <a href="{{ route('documents.show', $document) }}" class="font-semibold text-gray-900 hover:text-primary-600">{{ $document->name }}</a>
                        <div class="flex items-center gap-3 mt-1 text-sm text-gray-500">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-700">
                                {{ $types[$document->type] ?? $document->type }}
                            </span>
                            @if($document->residence)
                            <span>{{ $document->residence->name }}</span>
                            @endif
                            <span>{{ $document->created_at->format('d/m/Y') }}</span>
                            @if($document->file_size)
                            <span>{{ number_format($document->file_size / 1024, 0) }} Ko</span>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <a href="{{ route('documents.download', $document) }}" class="text-gray-400 hover:text-primary-600" title="Télécharger">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    </a>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    <div class="mt-6">
        {{ $documents->links() }}
    </div>
    @endif
</div>
@endsection
