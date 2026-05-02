@extends('layouts.app')

@section('title', 'Détails du litige')

@section('content')
<div class="max-w-4xl mx-auto px-4 py-8">
    <!-- Header -->
    <div class="mb-8">
        <a href="{{ route('disputes.index') }}" class="inline-flex items-center text-gray-600 hover:text-gray-900 mb-4">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Retour aux litiges
        </a>
        <div class="flex items-start justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">{{ $dispute->type_label }}</h1>
                <p class="text-gray-600 mt-1">Ouvert le {{ $dispute->created_at->format('d/m/Y à H:i') }}</p>
            </div>
            <div class="flex items-center space-x-2">
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-{{ $dispute->status_color }}-100 text-{{ $dispute->status_color }}-800">
                    {{ $dispute->status_label }}
                </span>
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-{{ $dispute->priority_color }}-100 text-{{ $dispute->priority_color }}-800">
                    {{ $dispute->priority_label }}
                </span>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Reason & Description -->
            <div class="bg-white rounded-lg shadow-sm border p-6">
                <h2 class="font-semibold text-gray-900 mb-4">Description du problème</h2>
                <p class="font-medium text-gray-900 mb-2">{{ $dispute->reason }}</p>
                <p class="text-gray-600 whitespace-pre-line">{{ $dispute->detailed_description }}</p>
            </div>

            <!-- Evidence -->
            @if($dispute->evidence && count($dispute->evidence) > 0)
                <div class="bg-white rounded-lg shadow-sm border p-6">
                    <h2 class="font-semibold text-gray-900 mb-4">Pièces justificatives</h2>
                    <div class="grid grid-cols-2 gap-4">
                        @foreach($dispute->evidence as $evidence)
                            <div class="border rounded-lg p-3">
                                @if(in_array(pathinfo($evidence['name'], PATHINFO_EXTENSION), ['jpg', 'jpeg', 'png']))
                                    <img loading="lazy" src="{{ storage_url($evidence['path']) }}" 
                                         alt="{{ $evidence['name'] }}"
                                         class="w-full h-32 object-cover rounded mb-2">
                                @else
                                    <div class="w-full h-32 bg-gray-100 rounded flex items-center justify-center mb-2">
                                        <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                        </svg>
                                    </div>
                                @endif
                                <p class="text-sm text-gray-600 truncate">{{ $evidence['name'] }}</p>
                                @if(isset($evidence['description']))
                                    <p class="text-xs text-gray-500 mt-1">{{ $evidence['description'] }}</p>
                                @endif
                            </div>
                        @endforeach
                    </div>

                    @if($dispute->isOpen())
                        <form action="{{ route('disputes.add-evidence', $dispute) }}" 
                              method="POST" 
                              enctype="multipart/form-data"
                              class="mt-4 pt-4 border-t">
                            @csrf
                            <div class="flex space-x-4">
                                <input type="file" 
                                       name="file" 
                                       required
                                       class="flex-1 text-sm text-gray-600">
                                <input type="text" 
                                       name="description" 
                                       placeholder="Description..."
                                       class="flex-1 px-3 py-1 border rounded text-sm">
                                <button type="submit" 
                                        class="px-4 py-1 bg-[#e00b41] text-white rounded text-sm hover:bg-[#b5083a]">
                                    Ajouter
                                </button>
                            </div>
                        </form>
                    @endif
                </div>
            @endif

            <!-- Resolution -->
            @if($dispute->resolution)
                <div class="bg-green-50 border border-green-200 rounded-lg p-6">
                    <div class="flex items-center mb-3">
                        <svg class="w-6 h-6 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <h2 class="font-semibold text-green-900">Résolution</h2>
                    </div>
                    <p class="text-green-800 font-medium mb-2">{{ $dispute->resolution_label }}</p>
                    @if($dispute->resolution_notes)
                        <p class="text-green-700 text-sm">{{ $dispute->resolution_notes }}</p>
                    @endif
                    <p class="text-green-600 text-sm mt-2">
                        Résolu le {{ $dispute->resolved_at->format('d/m/Y à H:i') }}
                    </p>
                </div>
            @endif

            <!-- Support Tickets -->
            @if($dispute->supportTickets->isNotEmpty())
                <div class="bg-white rounded-lg shadow-sm border p-6">
                    <h2 class="font-semibold text-gray-900 mb-4">Tickets de support associés</h2>
                    <div class="space-y-3">
                        @foreach($dispute->supportTickets as $ticket)
                            <a href="{{ route('support.show', $ticket) }}" 
                               class="block p-4 border rounded-lg hover:bg-gray-50 transition-colors">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <span class="text-sm text-gray-500">{{ $ticket->ticket_number }}</span>
                                        <h4 class="font-medium text-gray-900">{{ $ticket->subject }}</h4>
                                    </div>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-{{ $ticket->status_color }}-100 text-{{ $ticket->status_color }}-800">
                                        {{ $ticket->status_label }}
                                    </span>
                                </div>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Booking Info -->
            @if($dispute->booking)
                <div class="bg-white rounded-lg shadow-sm border p-6">
                    <h3 class="font-semibold text-gray-900 mb-4">Réservation concernée</h3>
                    <div class="space-y-4">
                        @if($dispute->booking->residence->mainPhoto)
                            <img loading="lazy" src="{{ storage_url($dispute->booking->residence->mainPhoto->path) }}" 
                                 alt="{{ $dispute->booking->residence->name }}"
                                 class="w-full h-32 object-cover rounded-lg">
                        @endif
                        <div>
                            <h4 class="font-medium text-gray-900">{{ $dispute->booking->residence->title }}</h4>
                            <p class="text-sm text-gray-600">{{ $dispute->booking->residence->commune }}</p>
                        </div>
                        <div class="pt-3 border-t space-y-2 text-sm">
                            <div class="flex justify-between">
                                <span class="text-gray-500">Dates</span>
                                <span>{{ $dispute->booking->check_in->format('d/m') }} - {{ $dispute->booking->check_out->format('d/m/Y') }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-500">Montant</span>
                                <span class="font-medium">{{ number_format($dispute->booking->total_amount, 0, ',', ' ') }} FCFA</span>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Timeline -->
            <div class="bg-white rounded-lg shadow-sm border p-6">
                <h3 class="font-semibold text-gray-900 mb-4">Chronologie</h3>
                <div class="space-y-4">
                    <div class="flex items-start">
                        <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center mr-3">
                            <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-900">Litige ouvert</p>
                            <p class="text-xs text-gray-500">{{ $dispute->created_at->format('d/m/Y H:i') }}</p>
                        </div>
                    </div>

                    @if($dispute->assigned_to)
                        <div class="flex items-start">
                            <div class="w-8 h-8 bg-yellow-100 rounded-full flex items-center justify-center mr-3">
                                <svg class="w-4 h-4 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-900">Assigné à un agent</p>
                                <p class="text-xs text-gray-500">En cours de traitement</p>
                            </div>
                        </div>
                    @endif

                    @if($dispute->escalated_at)
                        <div class="flex items-start">
                            <div class="w-8 h-8 bg-[#ffd1da] rounded-full flex items-center justify-center mr-3">
                                <svg class="w-4 h-4 text-[#e00b41]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-900">Escaladé</p>
                                <p class="text-xs text-gray-500">{{ $dispute->escalated_at->format('d/m/Y H:i') }}</p>
                            </div>
                        </div>
                    @endif

                    @if($dispute->resolved_at)
                        <div class="flex items-start">
                            <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center mr-3">
                                <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-900">Résolu</p>
                                <p class="text-xs text-gray-500">{{ $dispute->resolved_at->format('d/m/Y H:i') }}</p>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Need Help -->
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                <h4 class="font-medium text-blue-900 mb-2">Besoin d'aide ?</h4>
                <p class="text-sm text-blue-800 mb-3">
                    Notre équipe est disponible pour répondre à vos questions.
                </p>
                <a href="{{ route('support.create', ['dispute_id' => $dispute->id]) }}" 
                   class="inline-flex items-center text-sm font-medium text-blue-600 hover:text-blue-700">
                    Contacter le support
                    <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
