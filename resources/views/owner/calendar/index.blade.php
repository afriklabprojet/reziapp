@extends('layouts.owner')

@section('title', 'Calendrier unifié — REZI')

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.css" rel="stylesheet">
@endpush

@section('owner-content')
<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Calendrier unifié</h1>
        <p class="text-sm text-gray-500 mt-1">Vue d'ensemble de toutes vos activités</p>
    </div>

    {{-- Legend --}}
    <div class="bg-white rounded-2xl border border-gray-100 p-4">
        <div class="flex flex-wrap gap-4">
            <div class="flex items-center gap-2">
                <div class="w-3 h-3 rounded-full bg-blue-500"></div>
                <span class="text-xs text-gray-600">Réservations</span>
            </div>
            <div class="flex items-center gap-2">
                <div class="w-3 h-3 rounded-full bg-green-500"></div>
                <span class="text-xs text-gray-600">Ménage</span>
            </div>
            <div class="flex items-center gap-2">
                <div class="w-3 h-3 rounded-full bg-amber-500"></div>
                <span class="text-xs text-gray-600">Relances loyer</span>
            </div>
            <div class="flex items-center gap-2">
                <div class="w-3 h-3 rounded-full bg-red-500"></div>
                <span class="text-xs text-gray-600">Maintenance</span>
            </div>
            <div class="flex items-center gap-2">
                <div class="w-3 h-3 rounded-full bg-purple-500"></div>
                <span class="text-xs text-gray-600">Dépenses</span>
            </div>
        </div>
    </div>

    {{-- Calendar --}}
    <div class="bg-white rounded-2xl border border-gray-100 p-6">
        <div id="owner-calendar" style="min-height: 600px;"></div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const calendarEl = document.getElementById('owner-calendar');
    const calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        locale: 'fr',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,listWeek'
        },
        buttonText: {
            today: "Aujourd'hui",
            month: 'Mois',
            week: 'Semaine',
            list: 'Liste',
        },
        events: {
            url: '{{ route("owner.calendar.events") }}',
            failure: function() {
                console.error('Erreur de chargement des événements');
            }
        },
        eventDisplay: 'block',
        dayMaxEvents: 3,
        height: 'auto',
        eventClick: function(info) {
            if (info.event.url) {
                info.jsEvent.preventDefault();
                window.location.href = info.event.url;
            }
        }
    });
    calendar.render();
});
</script>
@endpush
@endsection
