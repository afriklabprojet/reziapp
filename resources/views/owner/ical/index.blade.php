@extends('layouts.owner')

@section('title', 'Synchronisation iCal — ReziApp')

@section('owner-content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Synchronisation iCal</h1>
            <p class="text-sm text-gray-500 mt-1">Connectez vos calendriers Airbnb, Booking.com, etc.</p>
        </div>
    </div>

    {{-- Add Feed Form --}}
    <div class="bg-white rounded-2xl border border-gray-100 p-6">
        <h2 class="text-lg font-bold text-gray-900 mb-4">Ajouter un flux iCal</h2>
        <form action="{{ route('owner.ical.store') }}" method="POST" class="space-y-4">
            @csrf
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-gray-500 mb-1">Résidence *</label>
                    <select name="residence_id" required class="w-full rounded-xl border-gray-200 text-sm py-2 px-3">
                        <option value="">Sélectionnez...</option>
                        @foreach($residences as $r) <option value="{{ $r->id }}">{{ $r->name }}</option> @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 mb-1">Plateforme *</label>
                    <select name="platform" required class="w-full rounded-xl border-gray-200 text-sm py-2 px-3">
                        @foreach(\App\Models\IcalFeed::PLATFORMS as $k => $l)
                        <option value="{{ $k }}">{{ $l }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-500 mb-1">Nom du flux *</label>
                <input type="text" name="name" required class="w-full rounded-xl border-gray-200 text-sm py-2 px-3" placeholder="Mon calendrier Airbnb">
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-500 mb-1">URL iCal *</label>
                <input type="url" name="import_url" required class="w-full rounded-xl border-gray-200 text-sm py-2 px-3" placeholder="https://www.airbnb.com/calendar/ical/...">
                <p class="text-xs text-gray-400 mt-1">Copiez l'URL iCal depuis votre compte Airbnb/Booking</p>
            </div>
            <button type="submit" class="px-4 py-2.5 bg-gray-900 text-white font-semibold rounded-xl hover:bg-gray-800 text-sm">Ajouter et synchroniser</button>
        </form>
    </div>

    {{-- Existing Feeds --}}
    <div class="space-y-3">
        @forelse($feeds as $feed)
        <div class="bg-white rounded-2xl border border-gray-100 p-5">
            <div class="flex items-center justify-between gap-4">
                <div class="flex items-center gap-4 min-w-0">
                    <div class="w-10 h-10 rounded-xl {{ $feed->sync_status === 'synced' ? 'bg-green-100' : ($feed->sync_status === 'error' ? 'bg-red-100' : 'bg-gray-100') }} flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 {{ $feed->sync_status === 'synced' ? 'text-green-600' : ($feed->sync_status === 'error' ? 'text-red-600' : 'text-gray-400') }}" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" /></svg>
                    </div>
                    <div class="min-w-0">
                        <div class="flex items-center gap-2">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold uppercase bg-blue-100 text-blue-700">{{ \App\Models\IcalFeed::PLATFORMS[$feed->platform] ?? $feed->platform }}</span>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold uppercase {{ $feed->sync_status === 'synced' ? 'bg-green-100 text-green-700' : ($feed->sync_status === 'error' ? 'bg-red-100 text-red-700' : 'bg-gray-100 text-gray-500') }}">{{ $feed->sync_status }}</span>
                        </div>
                        <p class="font-semibold text-gray-900 mt-1 truncate">{{ $feed->name }}</p>
                        <p class="text-sm text-gray-500">{{ $feed->residence?->name }} · {{ $feed->imported_events_count ?? 0 }} événement(s)</p>
                        @if($feed->last_synced_at)
                        <p class="text-xs text-gray-400">Dernière sync: {{ $feed->last_synced_at->diffForHumans() }}</p>
                        @endif
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <form action="{{ route('owner.ical.sync', $feed) }}" method="POST">
                        @csrf
                        <button type="submit" class="p-2 bg-blue-50 text-blue-700 rounded-lg hover:bg-blue-100" title="Synchroniser">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" /></svg>
                        </button>
                    </form>
                    <form action="{{ route('owner.ical.destroy', $feed) }}" method="POST" onsubmit="return confirm('Supprimer ce flux ?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="p-2 bg-red-50 text-red-600 rounded-lg hover:bg-red-100" title="Supprimer">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                        </button>
                    </form>
                </div>
            </div>
            @if($feed->export_token)
            <div class="mt-4 pt-4 border-t border-gray-100">
                <p class="text-xs font-semibold text-gray-500 mb-1">🔗 URL d'export (partagez avec d'autres plateformes)</p>
                <p class="text-xs text-gray-600 break-all bg-gray-50 p-2 rounded-lg">{{ $feed->export_url }}</p>
            </div>
            @endif
        </div>
        @empty
        <div class="bg-white rounded-2xl border border-gray-100 p-12 text-center">
            <svg class="w-12 h-12 mx-auto text-gray-300 mb-3" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" /></svg>
            <p class="text-gray-400 font-medium">Aucun flux iCal configuré</p>
        </div>
        @endforelse
    </div>

    @if($feeds->hasPages())
    <div>{{ $feeds->withQueryString()->links() }}</div>
    @endif
</div>
@endsection
