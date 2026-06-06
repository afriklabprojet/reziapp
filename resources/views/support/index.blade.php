@extends('layouts.app')

@section('title', 'Centre d\'aide')

@section('content')
<div class="max-w-4xl mx-auto px-4 py-8">
    <!-- Header -->
    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Centre d'aide</h1>
            <p class="text-gray-600 mt-1">
                @if($unreadCount > 0)
                    <span class="text-[#CC5A00] font-medium">{{ $unreadCount }} message(s) non lu(s)</span>
                @else
                    Vos demandes d'assistance
                @endif
            </p>
        </div>
        <a href="{{ route('support.create') }}" 
           class="inline-flex items-center px-4 py-2 bg-[#CC5A00] text-white rounded-lg hover:bg-[#A34700] transition-colors">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Nouvelle demande
        </a>
    </div>

    <!-- Quick Actions -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
        <a href="{{ route('cancellations.policies') }}" 
           class="bg-white rounded-lg shadow-sm border p-4 hover:shadow-md transition-shadow text-center">
            <svg class="w-8 h-8 mx-auto text-gray-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            <span class="text-sm text-gray-700">Politiques d'annulation</span>
        </a>
        <a href="{{ route('refunds.index') }}" 
           class="bg-white rounded-lg shadow-sm border p-4 hover:shadow-md transition-shadow text-center">
            <svg class="w-8 h-8 mx-auto text-gray-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
            </svg>
            <span class="text-sm text-gray-700">Mes remboursements</span>
        </a>
        <a href="{{ route('disputes.index') }}" 
           class="bg-white rounded-lg shadow-sm border p-4 hover:shadow-md transition-shadow text-center">
            <svg class="w-8 h-8 mx-auto text-gray-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span class="text-sm text-gray-700">Mes litiges</span>
        </a>
        <a href="{{ route('cancellations.history') }}" 
           class="bg-white rounded-lg shadow-sm border p-4 hover:shadow-md transition-shadow text-center">
            <svg class="w-8 h-8 mx-auto text-gray-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
            <span class="text-sm text-gray-700">Mes annulations</span>
        </a>
    </div>

    <!-- Tickets List -->
    @if($tickets->isEmpty())
        <div class="bg-white rounded-lg shadow-sm border p-12 text-center">
            <svg class="w-16 h-16 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
            </svg>
            <h3 class="text-lg font-medium text-gray-900 mb-2">Aucune demande</h3>
            <p class="text-gray-600 mb-4">Vous n'avez pas encore contacté notre support.</p>
            <a href="{{ route('support.create') }}" 
               class="inline-flex items-center px-4 py-2 bg-[#CC5A00] text-white rounded-lg hover:bg-[#A34700]">
                Créer une demande
            </a>
        </div>
    @else
        <div class="bg-white rounded-lg shadow-sm border overflow-hidden">
            <div class="divide-y">
                @foreach($tickets as $ticket)
                    <a href="{{ route('support.show', $ticket) }}" 
                       class="block p-4 hover:bg-gray-50 transition-colors">
                        <div class="flex items-start justify-between">
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center space-x-2 mb-1">
                                    <span class="text-xs text-gray-500">{{ $ticket->ticket_number }}</span>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-{{ $ticket->status_color }}-100 text-{{ $ticket->status_color }}-800">
                                        {{ $ticket->status_label }}
                                    </span>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800">
                                        {{ $ticket->category_label }}
                                    </span>
                                </div>
                                <h3 class="font-medium text-gray-900 truncate">{{ $ticket->subject }}</h3>
                                @if($ticket->latestMessage)
                                    <p class="text-sm text-gray-500 truncate mt-1">
                                        {{ Str::limit($ticket->latestMessage->message, 80) }}
                                    </p>
                                @endif
                            </div>
                            <div class="ml-4 text-right">
                                <p class="text-sm text-gray-500">{{ $ticket->updated_at->diffForHumans() }}</p>
                                @if($ticket->messages->where('read_at', null)->where('user_id', '!=', auth()->id())->count() > 0)
                                    <span class="inline-flex items-center justify-center w-5 h-5 bg-[#CC5A00] rounded-full text-xs text-white mt-1">
                                        {{ $ticket->messages->where('read_at', null)->where('user_id', '!=', auth()->id())->count() }}
                                    </span>
                                @endif
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Help Section -->
    <div class="mt-8 bg-blue-50 border border-blue-200 rounded-lg p-6">
        <h3 class="font-semibold text-blue-900 mb-3">Questions fréquentes</h3>
        <div class="space-y-3 text-sm">
            <details class="group">
                <summary class="cursor-pointer text-blue-800 font-medium hover:text-blue-900">
                    Comment annuler une réservation ?
                </summary>
                <p class="mt-2 text-blue-700 pl-4">
                    Rendez-vous dans "Mes réservations", sélectionnez la réservation et cliquez sur "Annuler". 
                    Le remboursement dépend de la politique d'annulation de l'hôte.
                </p>
            </details>
            <details class="group">
                <summary class="cursor-pointer text-blue-800 font-medium hover:text-blue-900">
                    Combien de temps pour recevoir un remboursement ?
                </summary>
                <p class="mt-2 text-blue-700 pl-4">
                    Les remboursements sont traités sous 5 à 10 jours ouvrés selon votre mode de paiement. 
                    Les crédits ReziApp sont instantanés.
                </p>
            </details>
            <details class="group">
                <summary class="cursor-pointer text-blue-800 font-medium hover:text-blue-900">
                    Comment ouvrir un litige ?
                </summary>
                <p class="mt-2 text-blue-700 pl-4">
                    Cliquez sur "Signaler un problème" dans les détails de votre réservation ou litige. 
                    Décrivez le problème et joignez des preuves si possible.
                </p>
            </details>
        </div>
    </div>
</div>
@endsection
