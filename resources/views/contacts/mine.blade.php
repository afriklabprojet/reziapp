@extends('layouts.app')

@section('title', 'Mes demandes de contact')

@section('content')
    <div class="max-w-4xl mx-auto px-4 py-8">
        <div class="mb-8">
            <h1 class="text-2xl font-bold text-gray-900">Mes demandes de contact</h1>
            <p class="text-gray-600 mt-1">Historique de vos demandes envoyées aux propriétaires</p>
        </div>

        @if ($contacts->isEmpty())
            <div class="bg-white rounded-xl shadow-sm border p-12 text-center">
                <svg class="w-16 h-16 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                </svg>
                <h3 class="text-lg font-medium text-gray-900 mb-2">Aucune demande</h3>
                <p class="text-gray-600">Vous n'avez pas encore contacté de propriétaire.</p>
            </div>
        @else
            <div class="space-y-4">
                @foreach ($contacts as $contact)
                    <div class="bg-white rounded-xl shadow-sm border p-5 hover:shadow-md transition-shadow">
                        <div class="flex items-start justify-between">
                            <div>
                                <h3 class="font-semibold text-gray-900">
                                    {{ $contact->residence->name ?? 'Résidence supprimée' }}
                                </h3>
                                <p class="text-sm text-gray-500 mt-0.5">
                                    @if ($contact->residence)
                                        {{ $contact->residence->commune ?? '' }}
                                        {{ $contact->residence->quartier ? '— ' . $contact->residence->quartier : '' }}
                                    @endif
                                </p>
                                @if ($contact->message)
                                    <p class="text-sm text-gray-600 mt-2 line-clamp-2">{{ $contact->message }}</p>
                                @endif
                                <p class="text-xs text-gray-400 mt-2">
                                    Envoyé le {{ $contact->created_at->format('d/m/Y à H:i') }}
                                    @if ($contact->owner)
                                        — à {{ $contact->owner->name }}
                                    @endif
                                </p>
                            </div>
                            <div>
                                @if ($contact->status === 'pending')
                                    <span class="badge badge-warning">En attente</span>
                                @elseif($contact->status === 'viewed')
                                    <span class="badge bg-blue-100 text-blue-800">Vu</span>
                                @elseif($contact->status === 'responded')
                                    <span class="badge badge-success">Répondu</span>
                                @elseif($contact->status === 'closed')
                                    <span class="badge bg-gray-100 text-gray-600">Fermé</span>
                                @endif
                            </div>
                        </div>

                        @if ($contact->residence && $contact->residence->price)
                            <div class="mt-3 pt-3 border-t">
                                <span
                                    class="text-sm font-bold text-primary-600">{{ number_format($contact->residence->price, 0, ',', ' ') }}
                                    FCFA/{{ $contact->residence->price_label }}</span>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>

            <div class="mt-6">
                {{ $contacts->links() }}
            </div>
        @endif
    </div>
@endsection
