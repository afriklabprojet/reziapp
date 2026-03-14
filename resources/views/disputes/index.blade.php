@extends('layouts.app')

@section('title', 'Mes litiges')

@section('content')
<div class="max-w-4xl mx-auto px-4 py-8">
    <!-- Header -->
    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Mes litiges</h1>
            <p class="text-gray-600 mt-1">Suivez l'état de vos demandes</p>
        </div>
        <a href="{{ route('disputes.create') }}" 
           class="inline-flex items-center px-4 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700 transition-colors">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Nouveau litige
        </a>
    </div>

    @if($disputes->isEmpty())
        <div class="bg-white rounded-lg shadow-sm border p-12 text-center">
            <svg class="w-16 h-16 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <h3 class="text-lg font-medium text-gray-900 mb-2">Aucun litige</h3>
            <p class="text-gray-600">Vous n'avez aucun litige en cours.</p>
        </div>
    @else
        <div class="space-y-4">
            @foreach($disputes as $dispute)
                <div class="bg-white rounded-lg shadow-sm border overflow-hidden">
                    <div class="p-6">
                        <div class="flex items-start justify-between">
                            <div>
                                <div class="flex items-center space-x-2">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-{{ $dispute->status_color }}-100 text-{{ $dispute->status_color }}-800">
                                        {{ $dispute->status_label }}
                                    </span>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-{{ $dispute->priority_color }}-100 text-{{ $dispute->priority_color }}-800">
                                        Priorité: {{ $dispute->priority_label }}
                                    </span>
                                </div>
                                <h3 class="font-medium text-gray-900 mt-2">{{ $dispute->type_label }}</h3>
                                <p class="text-gray-600 text-sm mt-1">{{ Str::limit($dispute->reason, 100) }}</p>
                            </div>
                            <div class="text-right text-sm text-gray-500">
                                {{ $dispute->created_at->format('d/m/Y') }}
                            </div>
                        </div>

                        @if($dispute->booking)
                            <div class="mt-4 pt-4 border-t flex items-center space-x-4">
                                @if($dispute->booking->residence->mainPhoto)
                                    <img loading="lazy" src="{{ storage_url($dispute->booking->residence->mainPhoto->path) }}" 
                                         alt="{{ $dispute->booking->residence->name }}"
                                         class="w-12 h-12 object-cover rounded">
                                @endif
                                <div>
                                    <p class="text-sm font-medium text-gray-900">{{ $dispute->booking->residence->title }}</p>
                                    <p class="text-xs text-gray-500">
                                        {{ $dispute->booking->check_in->format('d M') }} - {{ $dispute->booking->check_out->format('d M Y') }}
                                    </p>
                                </div>
                            </div>
                        @endif

                        @if($dispute->resolution)
                            <div class="mt-4 pt-4 border-t">
                                <div class="flex items-center text-green-600">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    <span class="font-medium">Résolution: {{ $dispute->resolution_label }}</span>
                                </div>
                            </div>
                        @endif

                        @if($dispute->is_overdue)
                            <div class="mt-4 flex items-center text-red-600 text-sm">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                Délai de réponse dépassé
                            </div>
                        @elseif($dispute->response_deadline && $dispute->isOpen())
                            <div class="mt-4 flex items-center text-yellow-600 text-sm">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                Réponse attendue avant le {{ $dispute->response_deadline->format('d/m/Y à H:i') }}
                            </div>
                        @endif
                    </div>

                    <div class="bg-gray-50 px-6 py-3">
                        <a href="{{ route('disputes.show', $dispute) }}" 
                           class="text-orange-600 hover:text-orange-700 text-sm font-medium">
                            Voir les détails →
                        </a>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection
