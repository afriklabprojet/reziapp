@extends('layouts.owner')

@section('title', 'Alertes — REZI')

@section('owner-content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Mes alertes</h1>
            <p class="text-sm text-gray-500 mt-1">Notifications automatiques sur vos résidences</p>
        </div>
        <button onclick="document.getElementById('settings-modal').classList.remove('hidden')" class="inline-flex items-center gap-2 px-4 py-2.5 bg-gray-100 text-gray-700 font-semibold rounded-xl hover:bg-gray-200 text-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.723 7.723 0 0 1 0 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 0 1 0-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /></svg>
            Paramètres
        </button>
    </div>

    {{-- Unread Alerts --}}
    @if($unread->isNotEmpty())
    <div class="space-y-3">
        <h2 class="text-sm font-semibold text-gray-500 uppercase">Nouvelles alertes</h2>
        @foreach($unread as $alert)
        <div class="bg-white rounded-2xl border border-{{ $alert->severity === 'critical' ? 'red' : ($alert->severity === 'warning' ? 'amber' : 'gray') }}-200 p-5">
            <div class="flex items-start justify-between gap-4">
                <div class="flex items-start gap-4">
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0
                        {{ $alert->severity === 'critical' ? 'bg-red-100 text-red-600' : ($alert->severity === 'warning' ? 'bg-amber-100 text-amber-600' : 'bg-blue-100 text-blue-600') }}">
                        @if($alert->type === 'sla_breach')
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" /></svg>
                        @elseif($alert->type === 'low_occupancy')
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" /></svg>
                        @elseif($alert->type === 'price_anomaly')
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm3 0h.008v.008H18V10.5Zm-12 0h.008v.008H6V10.5Z" /></svg>
                        @elseif($alert->type === 'bad_review')
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 0 1 1.04 0l2.125 5.111a.563.563 0 0 0 .475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 0 0-.182.557l1.285 5.385a.562.562 0 0 1-.84.61l-4.725-2.885a.562.562 0 0 0-.586 0L6.982 20.54a.562.562 0 0 1-.84-.61l1.285-5.386a.562.562 0 0 0-.182-.557l-4.204-3.602a.562.562 0 0 1 .321-.988l5.518-.442a.563.563 0 0 0 .475-.345L11.48 3.5Z" /></svg>
                        @else
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" /></svg>
                        @endif
                    </div>
                    <div>
                        <p class="font-semibold text-gray-900">
                            {{ \App\Models\OwnerAlert::TYPES[$alert->type] ?? $alert->type }}
                        </p>
                        <p class="text-sm text-gray-600 mt-1">{{ $alert->message }}</p>
                        @if($alert->residence)
                        <p class="text-xs text-gray-400 mt-1">{{ $alert->residence->name }}</p>
                        @endif
                        <p class="text-xs text-gray-400">{{ $alert->created_at->diffForHumans() }}</p>
                    </div>
                </div>
                <form action="{{ route('owner.alerts.mark-read', $alert) }}" method="POST">
                    @csrf @method('PATCH')
                    <button type="submit" class="p-2 text-gray-400 hover:text-gray-600" title="Marquer comme lu">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                    </button>
                </form>
            </div>
        </div>
        @endforeach
    </div>
    @endif

    {{-- Read Alerts --}}
    <div class="space-y-3">
        <h2 class="text-sm font-semibold text-gray-500 uppercase">Historique</h2>
        @forelse($read as $alert)
        <div class="bg-white rounded-2xl border border-gray-100 p-5 opacity-75">
            <div class="flex items-start gap-4">
                <div class="w-10 h-10 rounded-xl bg-gray-100 flex items-center justify-center text-gray-400 shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                </div>
                <div>
                    <p class="font-semibold text-gray-700">{{ \App\Models\OwnerAlert::TYPES[$alert->type] ?? $alert->type }}</p>
                    <p class="text-sm text-gray-500 mt-1">{{ $alert->message }}</p>
                    <p class="text-xs text-gray-400 mt-1">{{ $alert->created_at->diffForHumans() }}</p>
                </div>
            </div>
        </div>
        @empty
        <p class="text-gray-400 text-center py-4">Aucune alerte dans l'historique</p>
        @endforelse
    </div>

    @if($read->hasPages())
    <div>{{ $read->withQueryString()->links() }}</div>
    @endif
</div>

{{-- Settings Modal --}}
<div id="settings-modal" class="hidden fixed inset-0 z-50 bg-black/50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-md w-full p-6">
        <h2 class="text-lg font-bold text-gray-900 mb-4">Paramètres des alertes</h2>
        <form action="{{ route('owner.alerts.update-settings') }}" method="POST" class="space-y-4">
            @csrf @method('PATCH')
            <div class="space-y-3">
                @foreach(\App\Models\OwnerAlert::TYPES as $k => $l)
                <label class="flex items-center gap-3 cursor-pointer">
                    <input type="checkbox" name="enabled_types[]" value="{{ $k }}" {{ in_array($k, $settings['enabled_types'] ?? array_keys(\App\Models\OwnerAlert::TYPES)) ? 'checked' : '' }} class="rounded border-gray-300 text-gray-900">
                    <span class="text-sm text-gray-700">{{ $l }}</span>
                </label>
                @endforeach
            </div>
            <div class="flex items-center gap-3 pt-4 border-t border-gray-100">
                <button type="submit" class="px-4 py-2.5 bg-gray-900 text-white font-semibold rounded-xl hover:bg-gray-800 text-sm">Enregistrer</button>
                <button type="button" onclick="document.getElementById('settings-modal').classList.add('hidden')" class="px-4 py-2.5 text-gray-600 hover:text-gray-800 text-sm">Annuler</button>
            </div>
        </form>
    </div>
</div>
@endsection
