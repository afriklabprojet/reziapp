@extends('layouts.client', ['sidebarActive' => 'bookings'])

@section('title', 'Mes réservations')

@section('client-content')
    <div>
        <!-- En-tête -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">Mes réservations</h1>
            <p class="mt-2 text-gray-600">Gérez vos réservations passées et à venir</p>
        </div>

        <!-- Statistiques -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-white rounded-xl shadow-sm p-6">
                <div class="flex items-center">
                    <div class="p-3 bg-blue-100 rounded-lg">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Total réservations</p>
                        <p class="text-2xl font-bold text-gray-900">{{ $stats['total'] }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm p-6">
                <div class="flex items-center">
                    <div class="p-3 bg-green-100 rounded-lg">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">À venir</p>
                        <p class="text-2xl font-bold text-gray-900">{{ $stats['upcoming'] }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm p-6">
                <div class="flex items-center">
                    <div class="p-3 bg-orange-100 rounded-lg">
                        <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Terminées</p>
                        <p class="text-2xl font-bold text-gray-900">{{ $stats['completed'] }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filtres -->
        <div class="bg-white rounded-xl shadow-sm p-4 mb-6">
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('bookings.index', ['status' => 'all']) }}"
                    class="px-4 py-2 rounded-lg text-sm font-medium {{ $status === 'all' ? 'bg-orange-500 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                    Toutes
                </a>
                <a href="{{ route('bookings.index', ['status' => 'pending']) }}"
                    class="px-4 py-2 rounded-lg text-sm font-medium {{ $status === 'pending' ? 'bg-orange-500 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                    En attente
                </a>
                <a href="{{ route('bookings.index', ['status' => 'confirmed']) }}"
                    class="px-4 py-2 rounded-lg text-sm font-medium {{ $status === 'confirmed' ? 'bg-orange-500 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                    Confirmées
                </a>
                <a href="{{ route('bookings.index', ['status' => 'completed']) }}"
                    class="px-4 py-2 rounded-lg text-sm font-medium {{ $status === 'completed' ? 'bg-orange-500 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                    Terminées
                </a>
                <a href="{{ route('bookings.index', ['status' => 'cancelled_by_user']) }}"
                    class="px-4 py-2 rounded-lg text-sm font-medium {{ $status === 'cancelled_by_user' ? 'bg-orange-500 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                    Annulées
                </a>
            </div>
        </div>

        <!-- Liste des réservations -->
        @if ($bookings->isEmpty())
            <div class="bg-white rounded-xl shadow-sm p-12 text-center">
                <svg class="w-16 h-16 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                <h3 class="text-lg font-medium text-gray-900 mb-2">Aucune réservation</h3>
                <p class="text-gray-500 mb-6">Vous n'avez pas encore de réservation.</p>
                <a href="{{ route('residences.search') }}"
                    class="inline-flex items-center px-4 py-2 bg-orange-500 text-white rounded-lg hover:bg-orange-600">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    Explorer les résidences
                </a>
            </div>
        @else
            <div class="space-y-4">
                @foreach ($bookings as $booking)
                    <div class="bg-white rounded-xl shadow-sm overflow-hidden hover:shadow-md transition-shadow">
                        <div class="flex flex-col md:flex-row">
                            <!-- Image -->
                            <div class="md:w-48 h-48 md:h-auto">
                                @if ($booking->residence->photos->first())
                                    <img loading="lazy" src="{{ $booking->residence->photos->first()?->url }}"
                                        alt="{{ $booking->residence->name }}" class="w-full h-full object-cover">
                                @else
                                    <div class="w-full h-full bg-gray-200 flex items-center justify-center">
                                        <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                                        </svg>
                                    </div>
                                @endif
                            </div>

                            <!-- Contenu -->
                            <div class="flex-1 p-6">
                                <div class="flex items-start justify-between">
                                    <div>
                                        <h3 class="text-lg font-semibold text-gray-900">
                                            <a href="{{ route('residences.show', $booking->residence) }}"
                                                class="hover:text-orange-500">
                                                {{ $booking->residence->title }}
                                            </a>
                                        </h3>
                                        <p class="text-gray-500 text-sm">{{ $booking->residence->city }},
                                            {{ $booking->residence->neighborhood }}</p>
                                    </div>

                                    <!-- Badge statut -->
                                    @php
                                        $statusColors = [
                                            'pending' => 'bg-yellow-100 text-yellow-800',
                                            'confirmed' => 'bg-green-100 text-green-800',
                                            'completed' => 'bg-blue-100 text-blue-800',
                                            'cancelled_by_user' => 'bg-red-100 text-red-800',
                                            'cancelled_by_owner' => 'bg-red-100 text-red-800',
                                        ];
                                        $statusLabels = [
                                            'pending' => 'En attente',
                                            'confirmed' => 'Confirmée',
                                            'completed' => 'Terminée',
                                            'cancelled_by_user' => 'Annulée',
                                            'cancelled_by_owner' => 'Annulée par l\'hôte',
                                        ];
                                    @endphp
                                    <span
                                        class="px-3 py-1 rounded-full text-sm font-medium {{ $statusColors[$booking->status] ?? 'bg-gray-100 text-gray-800' }}">
                                        {{ $statusLabels[$booking->status] ?? $booking->status }}
                                    </span>
                                </div>

                                <div class="mt-4 grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                                    <div>
                                        <span class="text-gray-500">Arrivée</span>
                                        <p class="font-medium">
                                            {{ \Carbon\Carbon::parse($booking->check_in)->format('d M Y') }}</p>
                                    </div>
                                    <div>
                                        <span class="text-gray-500">Départ</span>
                                        <p class="font-medium">
                                            {{ \Carbon\Carbon::parse($booking->check_out)->format('d M Y') }}</p>
                                    </div>
                                    <div>
                                        <span class="text-gray-500">Voyageurs</span>
                                        <p class="font-medium">{{ $booking->guests }} personne(s)</p>
                                    </div>
                                    <div>
                                        <span class="text-gray-500">Total</span>
                                        <p class="font-semibold text-orange-600">
                                            {{ number_format($booking->total_amount, 0, ',', ' ') }} FCFA</p>
                                    </div>
                                </div>

                                <div class="mt-4 flex items-center justify-between">
                                    <span class="text-sm text-gray-500">
                                        Réf: {{ $booking->reference }}
                                    </span>

                                    <div class="flex items-center space-x-3">
                                        <a href="{{ route('bookings.show', $booking) }}"
                                            class="text-sm text-orange-500 hover:text-orange-600 font-medium">
                                            Voir les détails
                                        </a>

                                        @if (in_array($booking->status, ['pending', 'confirmed']))
                                            @if ($booking->status === 'pending' && $booking->payment_status !== 'paid')
                                                <a href="{{ route('payments.checkout', ['booking' => $booking->id]) }}"
                                                    class="px-4 py-2 bg-orange-500 text-white text-sm rounded-lg hover:bg-orange-600">
                                                    Payer
                                                </a>
                                            @endif
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Pagination -->
            <div class="mt-8">
                {{ $bookings->links() }}
            </div>
        @endif
    </div>
@endsection
