@extends('layouts.app')

@section('title', 'Synchronisation des canaux')

@section('content')
<div class="max-w-4xl mx-auto px-4 py-8">
    <a href="{{ route('owner.residences.show', $residence) }}" class="inline-flex items-center gap-1 text-sm text-gray-600 hover:text-gray-900 mb-4">← Retour</a>

    <h1 class="text-2xl font-bold text-gray-900">Synchronisation des canaux</h1>
    <p class="text-sm text-gray-600 mt-1">{{ $residence->name }}</p>

    @if(session('success'))
        <div class="mt-4 p-4 rounded-xl bg-emerald-50 ring-1 ring-emerald-200 text-emerald-800 text-sm">{{ session('success') }}</div>
    @endif

    <div class="mt-6 p-4 rounded-xl bg-amber-50 ring-1 ring-amber-200 text-amber-900 text-sm">
        ⚠️ <strong>Aperçu</strong> — La synchronisation officielle Airbnb / Booking nécessite un accord partenaire PMS.
        Les connexions ci-dessous sont enregistrées et prêtes pour l'activation API quand votre compte sera approuvé.
    </div>

    <div class="mt-6 space-y-3">
        @forelse($listings as $listing)
            <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-200 flex items-center justify-between gap-4">
                <div class="min-w-0">
                    <div class="font-semibold text-gray-900">{{ $listing->channelLabel() }}</div>
                    <div class="text-xs text-gray-500 mt-0.5">
                        @if($listing->external_id) ID externe : {{ $listing->external_id }} · @endif
                        @if($listing->last_sync_at) Dernière sync : {{ $listing->last_sync_at->diffForHumans() }} @else Jamais synchronisé @endif
                    </div>
                    @if($listing->sync_message)
                        <div class="text-xs text-gray-600 mt-1">{{ $listing->sync_message }}</div>
                    @endif
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <span class="px-2.5 py-1 rounded-full text-xs font-medium {{ $listing->statusBadge() }}">
                        {{ ucfirst($listing->sync_status) }}
                    </span>
                    <form method="POST" action="{{ route('owner.channels.sync', $listing) }}">
                        @csrf
                        <button class="px-3 py-1.5 text-xs font-semibold rounded-lg bg-gray-900 text-white hover:bg-gray-800">Synchroniser</button>
                    </form>
                    <form method="POST" action="{{ route('owner.channels.destroy', $listing) }}"  data-confirm='Déconnecter ce canal ?'>
                        @csrf @method('DELETE')
                        <button class="px-3 py-1.5 text-xs font-semibold rounded-lg text-red-700 hover:bg-red-50">Déconnecter</button>
                    </form>
                </div>
            </div>
        @empty
            <div class="bg-white p-6 rounded-2xl border border-dashed border-gray-300 text-center text-gray-500 text-sm">
                Aucun canal connecté pour le moment.
            </div>
        @endforelse
    </div>

    <div class="mt-8 bg-white p-5 rounded-2xl shadow-sm border border-gray-200">
        <h2 class="font-semibold text-gray-900 mb-3">Connecter un nouveau canal</h2>
        <form method="POST" action="{{ route('owner.channels.store', $residence) }}" class="grid grid-cols-1 md:grid-cols-3 gap-3">
            @csrf
            <select name="channel" required class="rounded-lg border-gray-300 text-sm">
                @foreach($available as $key => $label)
                    @if(!$listings->has($key))
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endif
                @endforeach
            </select>
            <input type="text" name="external_id" placeholder="ID externe (optionnel)" class="rounded-lg border-gray-300 text-sm">
            <button class="px-4 py-2 bg-gray-900 text-white text-sm font-semibold rounded-lg hover:bg-gray-800">Connecter</button>
        </form>
    </div>
</div>
@endsection
