@extends('layouts.app')

@section('title', 'Réservation ' . $booking->reference)

@section('content')
    <div class="min-h-screen bg-gray-50 py-8">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- En-tête avec statut -->
            <div class="mb-8">
                <a href="{{ route('bookings.index') }}"
                    class="inline-flex items-center min-h-11 py-2 text-gray-500 hover:text-gray-700 mb-4">
                    <svg aria-hidden="true" class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Retour aux réservations
                </a>

                <div class="flex items-start justify-between">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">Réservation {{ $booking->reference }}</h1>
                        <p class="text-gray-500 mt-1">Créée le {{ $booking->created_at->format('d/m/Y à H:i') }}</p>
                    </div>

                    @php
                        $statusConfig = [
                            'pending' => ['color' => 'yellow', 'icon' => 'clock', 'label' => 'En attente de paiement'],
                            'confirmed' => ['color' => 'green', 'icon' => 'check-circle', 'label' => 'Confirmée'],
                            'completed' => ['color' => 'blue', 'icon' => 'badge-check', 'label' => 'Terminée'],
                            'cancelled_by_user' => ['color' => 'red', 'icon' => 'x-circle', 'label' => 'Annulée'],
                            'cancelled_by_owner' => [
                                'color' => 'red',
                                'icon' => 'x-circle',
                                'label' => 'Annulée par l\'hôte',
                            ],
                        ];
                        $config = $statusConfig[$booking->status] ?? [
                            'color' => 'gray',
                            'icon' => 'question-mark-circle',
                            'label' => $booking->status,
                        ];
                    @endphp

                    <span
                        class="px-4 py-2 rounded-full text-sm font-medium bg-{{ $config['color'] }}-100 text-{{ $config['color'] }}-800">
                        {{ $config['label'] }}
                    </span>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Détails principaux -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Résidence -->
                    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                        <div class="flex flex-col sm:flex-row">
                            @if ($booking->residence->photos->first())
                                <img loading="lazy" src="{{ $booking->residence->photos->first()?->url }}"
                                    alt="{{ $booking->residence->name }}" class="w-full sm:w-40 h-48 sm:h-32 object-cover">
                            @endif
                            <div class="p-4 flex-1">
                                <h2 class="font-semibold text-lg">
                                    <a href="{{ route('residences.show', $booking->residence) }}"
                                        class="hover:text-[#F16A00]">
                                        {{ $booking->residence->title }}
                                    </a>
                                </h2>
                                <p class="text-gray-500 text-sm">{{ $booking->residence->city }},
                                    {{ $booking->residence->neighborhood }}</p>
                                <p class="text-gray-500 text-sm mt-1">{{ $booking->residence->full_address }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Dates et voyageurs -->
                    <div class="bg-white rounded-xl shadow-sm p-6">
                        <h3 class="font-semibold text-gray-900 mb-4">Détails du séjour</h3>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6">
                            <div class="sm:border-r border-gray-200 sm:pr-6">
                                <div class="mb-4">
                                    <span class="text-sm text-gray-500">Arrivée</span>
                                    <p class="font-semibold text-lg">
                                        {{ \Carbon\Carbon::parse($booking->check_in)->format('D d M Y') }}</p>
                                    <p class="text-gray-600">à partir de {{ $booking->check_in_time ?? '14:00' }}</p>
                                </div>
                                <div>
                                    <span class="text-sm text-gray-500">Départ</span>
                                    <p class="font-semibold text-lg">
                                        {{ \Carbon\Carbon::parse($booking->check_out)->format('D d M Y') }}</p>
                                    <p class="text-gray-600">avant {{ $booking->check_out_time ?? '11:00' }}</p>
                                </div>
                            </div>

                            <div class="sm:pl-6">
                                <div class="mb-4">
                                    <span class="text-sm text-gray-500">Durée</span>
                                    <p class="font-semibold text-lg">{{ $booking->nights }} nuit(s)</p>
                                </div>
                                <div>
                                    <span class="text-sm text-gray-500">Voyageurs</span>
                                    <p class="font-semibold text-lg">{{ $booking->guests }} personne(s)</p>
                                    @if ($booking->adults || $booking->children || $booking->infants)
                                        <p class="text-gray-600 text-sm">
                                            {{ $booking->adults ?? 1 }} adulte(s)
                                            @if ($booking->children)
                                                , {{ $booking->children }} enfant(s)
                                            @endif
                                            @if ($booking->infants)
                                                , {{ $booking->infants }} bébé(s)
                                            @endif
                                        </p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Message au propriétaire -->
                    @if ($booking->guest_message)
                        <div class="bg-white rounded-xl shadow-sm p-6">
                            <h3 class="font-semibold text-gray-900 mb-3">Votre message</h3>
                            <p class="text-gray-600 bg-gray-50 rounded-lg p-4">{{ $booking->guest_message }}</p>
                        </div>
                    @endif

                    <!-- Propriétaire -->
                    <div class="bg-white rounded-xl shadow-sm p-6">
                        <h3 class="font-semibold text-gray-900 mb-4">Votre hôte</h3>
                        <div class="flex items-center">
                            @if ($booking->residence->owner->avatar || $booking->residence->owner->profile_photo)
                                <img loading="lazy" src="{{ $booking->residence->owner->getAvatarUrl() }}"
                                    alt="{{ $booking->residence->owner->first_name }}"
                                    class="w-16 h-16 rounded-full object-cover">
                            @else
                                <div class="w-16 h-16 rounded-full bg-[#FFE7D1] flex items-center justify-center">
                                    <span
                                        class="text-2xl font-bold text-[#CC5A00]">{{ substr($booking->residence->owner->first_name, 0, 1) }}</span>
                                </div>
                            @endif
                            <div class="ml-4">
                                <h4 class="font-semibold">{{ $booking->residence->owner->first_name }}</h4>
                                <p class="text-gray-500 text-sm">Membre depuis
                                    {{ $booking->residence->owner->created_at->format('Y') }}</p>
                            </div>
                            <form action="{{ route('chat.start') }}" method="POST" class="ml-auto">
                                @csrf
                                <input type="hidden" name="residence_id" value="{{ $booking->residence_id }}">
                                <button type="submit" class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50">
                                    Contacter
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Politique d'annulation -->
                    <div class="bg-white rounded-xl shadow-sm p-6">
                        <h3 class="font-semibold text-gray-900 mb-3">Politique d'annulation</h3>
                        @if ($booking->cancellationPolicy)
                            <p class="text-gray-600">{{ $booking->cancellationPolicy->description }}</p>
                        @else
                            <p class="text-gray-600">Annulation gratuite jusqu'à 7 jours avant l'arrivée. Passé ce délai,
                                50% du montant sera retenu.</p>
                        @endif
                    </div>

                    <!-- Actions -->
                    @if (in_array($booking->status, ['pending', 'confirmed']))
                        <div class="bg-white rounded-xl shadow-sm p-6">
                            <h3 class="font-semibold text-gray-900 mb-4">Actions</h3>

                            <div class="flex flex-wrap gap-3">
                                @if ($booking->status === 'pending' && $booking->payment_status !== 'paid')
                                    <a href="{{ route('payments.checkout', ['booking' => $booking->id]) }}"
                                        class="px-6 py-3 bg-[#F16A00] text-white rounded-lg hover:bg-[#CC5A00] font-medium">
                                        Payer maintenant
                                    </a>
                                @endif

                                @if (in_array($booking->status, ['pending', 'confirmed']))
                                    <button
                                        onclick="const m=document.getElementById('cancelModal');m.classList.remove('hidden');m.classList.add('flex')"
                                        class="px-6 py-3 border border-red-300 text-red-600 rounded-lg hover:bg-red-50 font-medium">
                                        Annuler la réservation
                                    </button>
                                @endif

                                @if ($booking->status === 'confirmed' && $booking->check_in > now()->addDays(2))
                                    <a href="{{ route('bookings.modify', $booking) }}"
                                        class="px-6 py-3 border border-blue-300 text-blue-700 rounded-lg hover:bg-blue-50 font-medium">
                                        ✏️ Modifier la réservation
                                    </a>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Récapitulatif prix -->
                <div class="lg:col-span-1">
                    <div class="bg-white rounded-xl shadow-sm p-6 sticky top-24">
                        <h3 class="font-semibold text-gray-900 mb-4">Détail du prix</h3>

                        <div class="space-y-3 border-b border-gray-200 pb-4 mb-4">
                            <div class="flex justify-between text-sm">
                                <span>{{ number_format($booking->price_per_night, 0, ',', ' ') }} FCFA x
                                    {{ $booking->nights }} nuit(s)</span>
                                <span>{{ number_format($booking->subtotal, 0, ',', ' ') }} FCFA</span>
                            </div>

                            @if ($booking->cleaning_fee > 0)
                                <div class="flex justify-between text-sm">
                                    <span>Frais de ménage</span>
                                    <span>{{ number_format($booking->cleaning_fee, 0, ',', ' ') }} FCFA</span>
                                </div>
                            @endif

                            @if ($booking->service_fee > 0)
                            <div class="flex justify-between text-sm">
                                <span>Frais de service</span>
                                <span>{{ number_format($booking->service_fee, 0, ',', ' ') }} FCFA</span>
                            </div>
                            @endif

                            @if ($booking->long_stay_discount > 0)
                                <div class="flex justify-between text-sm text-green-600">
                                    <span>Réduction long séjour</span>
                                    <span>-{{ number_format($booking->long_stay_discount, 0, ',', ' ') }} FCFA</span>
                                </div>
                            @endif

                            @if ($booking->promo_discount > 0)
                                <div class="flex justify-between text-sm text-green-600">
                                    <span>Code promo</span>
                                    <span>-{{ number_format($booking->promo_discount, 0, ',', ' ') }} FCFA</span>
                                </div>
                            @endif

                            @if ($booking->taxes > 0)
                                <div class="flex justify-between text-sm">
                                    <span>Taxes</span>
                                    <span>{{ number_format($booking->taxes, 0, ',', ' ') }} FCFA</span>
                                </div>
                            @endif
                        </div>

                        <div class="flex justify-between items-center font-bold text-lg">
                            <span>Total</span>
                            <span class="text-[#CC5A00]">{{ number_format($booking->total_amount, 0, ',', ' ') }}
                                FCFA</span>
                        </div>

                        <!-- Statut paiement -->
                        <div class="mt-4 pt-4 border-t border-gray-200">
                            @if ($booking->payment_status === 'paid')
                                <div class="flex items-center text-green-600">
                                    <svg aria-hidden="true" class="w-5 h-5 mr-2" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    Payé le {{ \Carbon\Carbon::parse($booking->paid_at)->format('d/m/Y') }}
                                </div>
                            @elseif($booking->payment_status === 'pending')
                                <div class="flex items-center text-yellow-600">
                                    <svg aria-hidden="true" class="w-5 h-5 mr-2" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    En attente de paiement
                                </div>
                            @elseif($booking->payment_status === 'refunded')
                                <div class="flex items-center text-blue-600">
                                    <svg aria-hidden="true" class="w-5 h-5 mr-2" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" />
                                    </svg>
                                    Remboursé
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal d'annulation -->
    <div id="cancelModal" class="hidden fixed inset-0 bg-black/50 z-50 items-center justify-center p-4" role="dialog"
        aria-modal="true" aria-label="Annulation de réservation">
        <div class="bg-white rounded-xl max-w-md w-full p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Annuler la réservation</h3>

            <p class="text-gray-600 mb-4">
                Êtes-vous sûr de vouloir annuler cette réservation ?
                @if ($booking->payment_status === 'paid')
                    Le remboursement sera effectué selon la politique d'annulation.
                @endif
            </p>

            <form action="{{ route('bookings.cancel', $booking) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Raison de l'annulation</label>
                    <textarea name="reason" rows="3" required
                        class="w-full rounded-lg border-gray-300 focus:border-[#F16A00] focus:ring-[#F16A00]"
                        placeholder="Expliquez la raison de votre annulation..."></textarea>
                </div>

                <div class="flex justify-end space-x-3">
                    <button type="button"
                        onclick="const m=document.getElementById('cancelModal');m.classList.add('hidden');m.classList.remove('flex')"
                        class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50">
                        Annuler
                    </button>
                    <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">
                        Confirmer l'annulation
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
