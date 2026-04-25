@extends('layouts.app')

@section('title', 'Modifier la réservation')

@section('content')
<div class="max-w-2xl mx-auto px-4 py-8">
    <a href="{{ route('bookings.show', $booking) }}" class="inline-flex items-center gap-1 text-sm text-gray-600 hover:text-gray-900 mb-4">
        ← Retour à la réservation
    </a>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
        <h1 class="text-2xl font-bold text-gray-900">Demander une modification</h1>
        <p class="text-sm text-gray-600 mt-1">{{ $booking->residence->name }} · Réf. {{ $booking->reference }}</p>

        @if($pending)
            <div class="mt-4 p-4 rounded-xl bg-amber-50 ring-1 ring-amber-200 text-amber-800 text-sm">
                ⏳ Une demande est déjà en attente :
                <strong>{{ $pending->requested_check_in->format('d/m/Y') }} → {{ $pending->requested_check_out->format('d/m/Y') }}</strong>
                ({{ $pending->requested_guests }} voy.)
            </div>
        @else
            @if($errors->any())
                <div class="mt-4 p-4 rounded-xl bg-red-50 ring-1 ring-red-200 text-red-800 text-sm">
                    @foreach($errors->all() as $err)<div>{{ $err }}</div>@endforeach
                </div>
            @endif

            <form method="POST" action="{{ route('bookings.modify.store', $booking) }}" class="mt-6 space-y-4">
                @csrf

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nouvelle arrivée</label>
                        <input type="date" name="requested_check_in" required min="{{ now()->toDateString() }}"
                            value="{{ old('requested_check_in', $booking->check_in->toDateString()) }}"
                            class="w-full rounded-lg border-gray-300 focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nouveau départ</label>
                        <input type="date" name="requested_check_out" required min="{{ now()->addDay()->toDateString() }}"
                            value="{{ old('requested_check_out', $booking->check_out->toDateString()) }}"
                            class="w-full rounded-lg border-gray-300 focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nombre de voyageurs</label>
                    <input type="number" name="requested_guests" required min="1" max="{{ $booking->residence->max_guests ?? 20 }}"
                        value="{{ old('requested_guests', $booking->guests) }}"
                        class="w-full rounded-lg border-gray-300 focus:ring-2 focus:ring-blue-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Motif (facultatif)</label>
                    <textarea name="reason" rows="3" maxlength="1000"
                        placeholder="Expliquez brièvement la raison de votre modification…"
                        class="w-full rounded-lg border-gray-300 focus:ring-2 focus:ring-blue-500">{{ old('reason') }}</textarea>
                </div>

                <div class="p-4 rounded-xl bg-blue-50 ring-1 ring-blue-100 text-sm text-blue-900">
                    ℹ️ Le propriétaire recevra votre demande et pourra l'approuver ou la refuser.
                    Si elle est approuvée, le prix sera ajusté automatiquement.
                </div>

                <div class="flex items-center justify-end gap-3 pt-2">
                    <a href="{{ route('bookings.show', $booking) }}" class="px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded-lg">Annuler</a>
                    <button type="submit" class="px-5 py-2.5 bg-gray-900 text-white text-sm font-semibold rounded-lg hover:bg-gray-800">
                        Envoyer la demande
                    </button>
                </div>
            </form>
        @endif
    </div>
</div>
@endsection
