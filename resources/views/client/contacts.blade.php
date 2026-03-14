@extends('layouts.client', ['sidebarActive' => 'contacts'])

@section('title', 'Mes demandes de contact - REZI')

@section('client-content')
    {{-- En-tête --}}
    <div class="mb-8">
        <h1 class="text-2xl font-bold text-gray-900">Mes demandes de contact</h1>
        <p class="text-gray-600">Suivez l'état de vos demandes envoyées aux propriétaires</p>
    </div>

    {{-- Statistiques --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
        <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                    </svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-900">{{ $contactStats['total'] }}</p>
                    <p class="text-xs text-gray-500">Total envoyées</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-amber-100 rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-amber-600">{{ $contactStats['pending'] }}</p>
                    <p class="text-xs text-gray-500">En attente</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-orange-100 rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-orange-500">{{ $contactStats['replied'] }}</p>
                    <p class="text-xs text-gray-500">Répondues</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-gray-100 rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-600">{{ $contactStats['closed'] }}</p>
                    <p class="text-xs text-gray-500">Clôturées</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Filtres --}}
    <div class="flex flex-wrap gap-2 mb-6">
        @if (!request('status'))
            <a href="{{ route('client.contacts') }}"
                class="px-4 py-2 rounded-full text-sm font-medium transition bg-orange-500 text-white">
                Toutes
            </a>
        @else
            <a href="{{ route('client.contacts') }}"
                class="px-4 py-2 rounded-full text-sm font-medium transition bg-white text-gray-700 border border-gray-200 hover:bg-gray-50">
                Toutes
            </a>
        @endif

        @if (request('status') == 'pending')
            <a href="{{ route('client.contacts', ['status' => 'pending']) }}"
                class="px-4 py-2 rounded-full text-sm font-medium transition bg-amber-500 text-white">
                En attente
            </a>
        @else
            <a href="{{ route('client.contacts', ['status' => 'pending']) }}"
                class="px-4 py-2 rounded-full text-sm font-medium transition bg-white text-gray-700 border border-gray-200 hover:bg-gray-50">
                En attente
            </a>
        @endif

        @if (request('status') == 'replied')
            <a href="{{ route('client.contacts', ['status' => 'replied']) }}"
                class="px-4 py-2 rounded-full text-sm font-medium transition bg-orange-500 text-white">
                Répondues
            </a>
        @else
            <a href="{{ route('client.contacts', ['status' => 'replied']) }}"
                class="px-4 py-2 rounded-full text-sm font-medium transition bg-white text-gray-700 border border-gray-200 hover:bg-gray-50">
                Répondues
            </a>
        @endif

        @if (request('status') == 'closed')
            <a href="{{ route('client.contacts', ['status' => 'closed']) }}"
                class="px-4 py-2 rounded-full text-sm font-medium transition bg-gray-600 text-white">
                Clôturées
            </a>
        @else
            <a href="{{ route('client.contacts', ['status' => 'closed']) }}"
                class="px-4 py-2 rounded-full text-sm font-medium transition bg-white text-gray-700 border border-gray-200 hover:bg-gray-50">
                Clôturées
            </a>
        @endif
    </div>

    {{-- Liste des contacts --}}
    @if ($contacts->count() > 0)
        <div class="space-y-4">
            @foreach ($contacts as $contact)
                <div
                    class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-md transition">
                    <div class="flex flex-col sm:flex-row">
                        {{-- Image résidence --}}
                        <a href="{{ route('residences.show', $contact->residence) }}" class="sm:w-48 shrink-0">
                            <div class="aspect-video sm:aspect-square">
                                @if ($contact->residence->photos->count() > 0)
                                    <img loading="lazy"
                                        src="{{ storage_url($contact->residence->photos->first()?->path) }}"
                                        alt="{{ $contact->residence->name }}" class="w-full h-full object-cover">
                                @else
                                    <div class="w-full h-full bg-gray-200 flex items-center justify-center">
                                        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                                        </svg>
                                    </div>
                                @endif
                            </div>
                        </a>

                        {{-- Contenu --}}
                        <div class="flex-1 p-5">
                            <div class="flex items-start justify-between gap-4">
                                <div class="flex-1">
                                    <div class="flex items-center gap-2 mb-2">
                                        @if ($contact->status == 'pending')
                                            <span
                                                class="px-2 py-1 bg-amber-100 text-amber-700 text-xs font-medium rounded-full">En
                                                attente</span>
                                        @elseif($contact->status == 'replied')
                                            <span
                                                class="px-2 py-1 bg-orange-100 text-orange-600 text-xs font-medium rounded-full">Répondue</span>
                                        @else
                                            <span
                                                class="px-2 py-1 bg-gray-100 text-gray-700 text-xs font-medium rounded-full">Clôturée</span>
                                        @endif
                                        <span
                                            class="text-xs text-gray-500">{{ $contact->created_at->diffForHumans() }}</span>
                                    </div>

                                    <a href="{{ route('residences.show', $contact->residence) }}" class="block">
                                        <h3 class="font-semibold text-gray-900 hover:text-orange-500">
                                            {{ $contact->residence->title }}</h3>
                                        <p class="text-sm text-gray-500">{{ $contact->residence->commune }} •
                                            {{ number_format($contact->residence->price, 0, ',', ' ') }}
                                            FCFA/{{ $contact->residence->price_label }}</p>
                                    </a>
                                </div>

                                {{-- Propriétaire --}}
                                <div class="text-right shrink-0">
                                    <div class="flex items-center gap-2">
                                        <div>
                                            <p class="text-sm font-medium text-gray-900">{{ $contact->owner->name }}</p>
                                            <p class="text-xs text-gray-500">Propriétaire</p>
                                        </div>
                                        <div class="w-10 h-10 rounded-full overflow-hidden bg-gray-200">
                                            @if ($contact->owner->avatar || $contact->owner->profile_photo)
                                                <img loading="lazy" src="{{ $contact->owner->getAvatarUrl() }}"
                                                    alt="{{ $contact->owner->name }}" class="w-full h-full object-cover">
                                            @else
                                                <div class="w-full h-full flex items-center justify-center bg-orange-100">
                                                    <span
                                                        class="text-sm font-medium text-orange-500">{{ substr($contact->owner->name, 0, 1) }}</span>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Message envoyé --}}
                            <div class="mt-4 p-3 bg-gray-50 rounded-lg">
                                <p class="text-sm text-gray-600 line-clamp-2">{{ $contact->message }}</p>
                            </div>

                            {{-- Réponse du propriétaire --}}
                            @if ($contact->reply)
                                <div class="mt-3 p-3 bg-orange-50 rounded-lg border-l-4 border-orange-500">
                                    <p class="text-xs text-orange-500 font-medium mb-1">Réponse du propriétaire</p>
                                    <p class="text-sm text-gray-700">{{ $contact->reply }}</p>
                                    @if ($contact->replied_at)
                                        <p class="text-xs text-gray-500 mt-1">{{ $contact->replied_at->diffForHumans() }}
                                        </p>
                                    @endif
                                </div>
                            @endif

                            {{-- Actions --}}
                            <div class="mt-4 flex items-center gap-3">
                                <a href="{{ route('residences.show', $contact->residence) }}"
                                    class="inline-flex items-center px-4 py-2 bg-orange-500 hover:bg-orange-600 text-white text-sm font-medium rounded-lg transition">
                                    Voir la résidence
                                </a>
                                @if ($contact->status == 'replied')
                                    <a href="{{ route('chat.index') }}"
                                        class="inline-flex items-center px-4 py-2 border border-gray-300 text-gray-700 hover:bg-gray-50 text-sm font-medium rounded-lg transition">
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                        </svg>
                                        Continuer la discussion
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Pagination --}}
        <div class="mt-6">
            {{ $contacts->links() }}
        </div>
    @else
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-12 text-center">
            <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                </svg>
            </div>
            <h3 class="text-lg font-medium text-gray-900 mb-2">Aucune demande envoyée</h3>
            <p class="text-gray-600 mb-6">Contactez les propriétaires pour obtenir plus d'informations sur leurs résidences
            </p>
            <a href="{{ route('residences.index') }}"
                class="inline-flex items-center px-4 py-2 bg-orange-500 hover:bg-orange-600 text-white font-medium rounded-lg transition">
                Explorer les résidences
            </a>
        </div>
    @endif
@endsection
