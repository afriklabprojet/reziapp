@extends('layouts.owner')

@section('title', 'Comparer mes résidences')

@section('owner-content')
    <div x-data="compareApp()" class="space-y-6">

        {{-- En-tête --}}
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">
                    <span class="inline-flex items-center gap-2">
                        <svg class="w-7 h-7 text-indigo-500" fill="none" stroke="currentColor" stroke-width="2"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M7.5 21L3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5" />
                        </svg>
                        Comparer mes résidences
                    </span>
                </h1>
                <p class="text-gray-600 mt-1">Analysez côte à côte les performances de vos annonces</p>
            </div>

            {{-- Période --}}
            <div class="flex items-center gap-3">
                <span class="text-sm text-gray-500">Période :</span>
                <div class="flex bg-gray-100 rounded-lg p-1">
                    @foreach ([7 => '7j', 30 => '30j', 90 => '90j'] as $days => $label)
                        <a href="{{ route('owner.compare.index', ['ids' => implode(',', $selectedIds), 'period' => $days]) }}"
                            class="px-3 py-1.5 text-sm font-medium rounded-md transition-all
                                {{ (int) $period === $days ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-600 hover:text-gray-900' }}">
                            {{ $label }}
                        </a>
                    @endforeach
                </div>
            </div>
        </div>

        @if ($residences->count() < 2)
            {{-- Pas assez de résidences --}}
            <div class="bg-white rounded-2xl border border-gray-200 p-12 text-center">
                <svg class="mx-auto w-16 h-16 text-gray-300" fill="none" stroke="currentColor" stroke-width="1.5"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 7.5h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008z" />
                </svg>
                <h3 class="mt-4 text-lg font-semibold text-gray-900">Il vous faut au moins 2 annonces</h3>
                <p class="mt-2 text-gray-500">Publiez plusieurs résidences pour pouvoir les comparer entre elles.</p>
                <a href="{{ route('owner.residences.create') }}"
                    class="mt-6 inline-flex items-center gap-2 px-5 py-2.5 bg-gray-900 text-white rounded-xl font-medium hover:bg-gray-800 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    Nouvelle annonce
                </a>
            </div>
        @else
            {{-- Sélecteur de résidences --}}
            <div class="bg-white rounded-2xl border border-gray-200 p-5">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-sm font-semibold text-gray-900 uppercase tracking-wide">Sélectionnez 2 à 4 résidences
                    </h2>
                    <span class="text-xs text-gray-500" x-text="selected.length + '/4 sélectionnées'"></span>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-3">
                    @foreach ($residences as $r)
                        <label
                            class="relative flex items-center gap-3 p-3 rounded-xl border-2 cursor-pointer transition-all hover:bg-gray-50"
                            :class="selected.includes({{ $r->id }}) ?
                                'border-indigo-500 bg-indigo-50/50' :
                                'border-gray-200'"
                            @click.prevent="toggleResidence({{ $r->id }})">

                            <div class="relative shrink-0 w-12 h-12 rounded-lg overflow-hidden bg-gray-100">
                                @if ($r->photos->first())
                                    <img src="{{ $r->photos->first()->url }}" alt=""
                                        class="w-full h-full object-cover" loading="lazy">
                                @else
                                    <div class="w-full h-full flex items-center justify-center">
                                        <svg class="w-6 h-6 text-gray-300" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                                d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909M18 3.75H6A2.25 2.25 0 003.75 6v12A2.25 2.25 0 006 20.25h12" />
                                        </svg>
                                    </div>
                                @endif
                                <div class="absolute inset-0 flex items-center justify-center bg-black/40 transition-opacity"
                                    :class="selected.includes({{ $r->id }}) ? 'opacity-100' : 'opacity-0'">
                                    <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd"
                                            d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z"
                                            clip-rule="evenodd" />
                                    </svg>
                                </div>
                            </div>

                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-medium text-gray-900 truncate">{{ $r->name }}</p>
                                <p class="text-xs text-gray-500 truncate">{{ $r->commune ?? $r->city }}</p>
                            </div>

                            <div class="shrink-0">
                                @if ($r->status === 'approved')
                                    <span class="w-2 h-2 rounded-full bg-emerald-400 block"></span>
                                @else
                                    <span class="w-2 h-2 rounded-full bg-amber-400 block"></span>
                                @endif
                            </div>
                        </label>
                    @endforeach
                </div>

                <div class="mt-4 flex justify-end">
                    <button @click="applyComparison()" :disabled="selected.length < 2"
                        class="inline-flex items-center gap-2 px-5 py-2.5 bg-indigo-600 text-white rounded-xl text-sm font-semibold hover:bg-indigo-700 transition disabled:opacity-40 disabled:cursor-not-allowed">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M7.5 21L3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5" />
                        </svg>
                        Comparer
                    </button>
                </div>
            </div>

            @if ($compared->count() >= 2)
                {{-- Tableau comparatif --}}
                <div class="bg-white rounded-2xl border border-gray-200 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            {{-- En-têtes : photos + noms --}}
                            <thead>
                                <tr class="border-b border-gray-100">
                                    <th
                                        class="sticky left-0 z-10 bg-white px-5 py-4 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider w-44">
                                        Critère
                                    </th>
                                    @foreach ($compared as $r)
                                        <th class="px-5 py-4 text-center min-w-50">
                                            <div class="flex flex-col items-center gap-2">
                                                <div class="w-16 h-16 rounded-xl overflow-hidden bg-gray-100 shadow-sm">
                                                    @if ($r->photos->first())
                                                        <img src="{{ $r->photos->first()->url }}" alt=""
                                                            class="w-full h-full object-cover">
                                                    @endif
                                                </div>
                                                <div>
                                                    <p class="font-semibold text-gray-900 text-sm">{{ $r->name }}</p>
                                                    <p class="text-xs text-gray-500">{{ $r->commune ?? $r->city }}</p>
                                                </div>
                                            </div>
                                        </th>
                                    @endforeach
                                </tr>
                            </thead>

                            <tbody class="divide-y divide-gray-50">

                                {{-- SECTION: Tarification --}}
                                @include('owner.compare._section-header', ['title' => '💰 Tarification'])

                                @include('owner.compare._row', [
                                    'label' => 'Prix / nuit',
                                    'values' => $compared->map(
                                        fn($r) => $r->price_per_day
                                            ? number_format($r->price_per_day, 0, ',', ' ') . ' F'
                                            : '—'),
                                    'highlight' => 'min',
                                    'rawValues' => $compared->pluck('price_per_day'),
                                ])
                                @include('owner.compare._row', [
                                    'label' => 'Prix / semaine',
                                    'values' => $compared->map(
                                        fn($r) => $r->price_per_week
                                            ? number_format($r->price_per_week, 0, ',', ' ') . ' F'
                                            : '—'),
                                    'highlight' => 'min',
                                    'rawValues' => $compared->pluck('price_per_week'),
                                ])
                                @include('owner.compare._row', [
                                    'label' => 'Prix / mois',
                                    'values' => $compared->map(
                                        fn($r) => $r->price_per_month
                                            ? number_format($r->price_per_month, 0, ',', ' ') . ' F'
                                            : '—'),
                                    'highlight' => 'min',
                                    'rawValues' => $compared->pluck('price_per_month'),
                                ])

                                {{-- SECTION: Capacité --}}
                                @include('owner.compare._section-header', [
                                    'title' => '🏠 Capacité & Espace',
                                ])

                                @include('owner.compare._row', [
                                    'label' => 'Chambres',
                                    'values' => $compared->map(fn($r) => $r->bedrooms ?? '—'),
                                    'highlight' => 'max',
                                    'rawValues' => $compared->pluck('bedrooms'),
                                ])
                                @include('owner.compare._row', [
                                    'label' => 'Salles de bain',
                                    'values' => $compared->map(fn($r) => $r->bathrooms ?? '—'),
                                    'highlight' => 'max',
                                    'rawValues' => $compared->pluck('bathrooms'),
                                ])
                                @include('owner.compare._row', [
                                    'label' => 'Voyageurs max',
                                    'values' => $compared->map(fn($r) => $r->max_guests ?? '—'),
                                    'highlight' => 'max',
                                    'rawValues' => $compared->pluck('max_guests'),
                                ])
                                @include('owner.compare._row', [
                                    'label' => 'Surface (m²)',
                                    'values' => $compared->map(
                                        fn($r) => $r->surface_area ? $r->surface_area . ' m²' : '—'),
                                    'highlight' => 'max',
                                    'rawValues' => $compared->pluck('surface_area'),
                                ])

                                {{-- SECTION: Performance Période --}}
                                @include('owner.compare._section-header', [
                                    'title' => '📊 Performance ({{ $period }} derniers jours)',
                                ])

                                @include('owner.compare._row', [
                                    'label' => 'Vues',
                                    'values' => $compared->map(
                                        fn($r) => number_format($r->period_views ?? 0, 0, ',', ' ')),
                                    'highlight' => 'max',
                                    'rawValues' => $compared->map(fn($r) => $r->period_views ?? 0),
                                ])
                                @include('owner.compare._row', [
                                    'label' => 'Réservations',
                                    'values' => $compared->map(fn($r) => $r->period_bookings ?? 0),
                                    'highlight' => 'max',
                                    'rawValues' => $compared->map(fn($r) => $r->period_bookings ?? 0),
                                ])
                                @include('owner.compare._row', [
                                    'label' => 'Revenus nets',
                                    'values' => $compared->map(
                                        fn($r) => number_format($r->period_revenue ?? 0, 0, ',', ' ') . ' F'),
                                    'highlight' => 'max',
                                    'rawValues' => $compared->map(fn($r) => $r->period_revenue ?? 0),
                                ])
                                @include('owner.compare._row', [
                                    'label' => 'Taux d\'occupation',
                                    'values' => $compared->map(fn($r) => ($r->period_occupancy ?? 0) . '%'),
                                    'highlight' => 'max',
                                    'rawValues' => $compared->map(fn($r) => $r->period_occupancy ?? 0),
                                ])

                                {{-- SECTION: Performance Globale --}}
                                @include('owner.compare._section-header', [
                                    'title' => '🏆 Performance Globale',
                                ])

                                @include('owner.compare._row', [
                                    'label' => 'Vues totales',
                                    'values' => $compared->map(
                                        fn($r) => number_format($r->views_count ?? 0, 0, ',', ' ')),
                                    'highlight' => 'max',
                                    'rawValues' => $compared->map(fn($r) => $r->views_count ?? 0),
                                ])
                                @include('owner.compare._row', [
                                    'label' => 'Contacts totaux',
                                    'values' => $compared->map(
                                        fn($r) => number_format($r->contacts_count ?? 0, 0, ',', ' ')),
                                    'highlight' => 'max',
                                    'rawValues' => $compared->map(fn($r) => $r->contacts_count ?? 0),
                                ])
                                @include('owner.compare._row', [
                                    'label' => 'Réservations totales',
                                    'values' => $compared->map(fn($r) => $r->total_bookings_count ?? 0),
                                    'highlight' => 'max',
                                    'rawValues' => $compared->map(fn($r) => $r->total_bookings_count ?? 0),
                                ])
                                @include('owner.compare._row', [
                                    'label' => 'Taux conversion',
                                    'values' => $compared->map(fn($r) => ($r->conversion_rate ?? 0) . '%'),
                                    'highlight' => 'max',
                                    'rawValues' => $compared->map(fn($r) => $r->conversion_rate ?? 0),
                                ])
                                @include('owner.compare._row', [
                                    'label' => 'Taux réservation',
                                    'values' => $compared->map(fn($r) => ($r->booking_rate ?? 0) . '%'),
                                    'highlight' => 'max',
                                    'rawValues' => $compared->map(fn($r) => $r->booking_rate ?? 0),
                                ])
                                @include('owner.compare._row', [
                                    'label' => 'Revenu total net',
                                    'values' => $compared->map(
                                        fn($r) => number_format($r->total_revenue ?? 0, 0, ',', ' ') . ' F'),
                                    'highlight' => 'max',
                                    'rawValues' => $compared->map(fn($r) => $r->total_revenue ?? 0),
                                ])
                                @include('owner.compare._row', [
                                    'label' => 'Revenu moyen / résa',
                                    'values' => $compared->map(
                                        fn($r) => number_format($r->avg_revenue ?? 0, 0, ',', ' ') . ' F'),
                                    'highlight' => 'max',
                                    'rawValues' => $compared->map(fn($r) => $r->avg_revenue ?? 0),
                                ])

                                {{-- SECTION: Avis --}}
                                @include('owner.compare._section-header', ['title' => '⭐ Avis & Notes'])

                                @include('owner.compare._row', [
                                    'label' => 'Note moyenne',
                                    'values' => $compared->map(
                                        fn($r) => $r->average_rating
                                            ? number_format($r->average_rating, 1) . '/5'
                                            : '—'),
                                    'highlight' => 'max',
                                    'rawValues' => $compared->pluck('average_rating'),
                                ])
                                @include('owner.compare._row', [
                                    'label' => 'Nombre d\'avis',
                                    'values' => $compared->map(fn($r) => $r->reviews_count ?? 0),
                                    'highlight' => 'max',
                                    'rawValues' => $compared->map(fn($r) => $r->reviews_count ?? 0),
                                ])

                                {{-- SECTION: Équipements --}}
                                @include('owner.compare._section-header', ['title' => '🛋️ Équipements'])

                                <tr>
                                    <td
                                        class="sticky left-0 z-10 bg-white px-5 py-3 text-sm text-gray-600 font-medium align-top">
                                        Équipements
                                    </td>
                                    @foreach ($compared as $r)
                                        <td class="px-5 py-3 text-center align-top">
                                            <div class="flex flex-wrap justify-center gap-1.5">
                                                @forelse ($r->amenities as $amenity)
                                                    <span
                                                        class="inline-flex items-center gap-1 px-2 py-1 rounded-lg bg-gray-50 text-xs text-gray-700">
                                                        @if ($amenity->icon)
                                                            <span>{{ $amenity->icon }}</span>
                                                        @endif
                                                        {{ $amenity->name }}
                                                    </span>
                                                @empty
                                                    <span class="text-gray-400 text-xs">Aucun</span>
                                                @endforelse
                                            </div>
                                        </td>
                                    @endforeach
                                </tr>

                                @include('owner.compare._row', [
                                    'label' => 'Nb équipements',
                                    'values' => $compared->map(fn($r) => $r->amenities->count()),
                                    'highlight' => 'max',
                                    'rawValues' => $compared->map(fn($r) => $r->amenities->count()),
                                ])

                                {{-- SECTION: Détails --}}
                                @include('owner.compare._section-header', ['title' => 'ℹ️ Détails'])

                                @include('owner.compare._row', [
                                    'label' => 'Type',
                                    'values' => $compared->map(fn($r) => ucfirst($r->type ?? '—')),
                                    'highlight' => 'none',
                                    'rawValues' => collect(),
                                ])
                                @include('owner.compare._row', [
                                    'label' => 'Catégorie',
                                    'values' => $compared->map(fn($r) => $r->category?->name ?? '—'),
                                    'highlight' => 'none',
                                    'rawValues' => collect(),
                                ])
                                @include('owner.compare._row', [
                                    'label' => 'Localisation',
                                    'values' => $compared->map(
                                        fn($r) => ($r->commune ?? ($r->city ?? '')) .
                                            ($r->quartier ? ', ' . $r->quartier : '')),
                                    'highlight' => 'none',
                                    'rawValues' => collect(),
                                ])
                                @include('owner.compare._row', [
                                    'label' => 'Réservation instantanée',
                                    'values' => $compared->map(fn($r) => $r->instant_book ? '✅ Oui' : '❌ Non'),
                                    'highlight' => 'none',
                                    'rawValues' => collect(),
                                ])
                                @include('owner.compare._row', [
                                    'label' => 'Nuits min / max',
                                    'values' => $compared->map(
                                        fn($r) => ($r->min_nights ?? 1) . ' / ' . ($r->max_nights ?? '∞')),
                                    'highlight' => 'none',
                                    'rawValues' => collect(),
                                ])
                                @include('owner.compare._row', [
                                    'label' => 'Check-in / Check-out',
                                    'values' => $compared->map(
                                        fn($r) => ($r->check_in_time ?? '14:00') .
                                            ' — ' .
                                            ($r->check_out_time ?? '11:00')),
                                    'highlight' => 'none',
                                    'rawValues' => collect(),
                                ])
                                @include('owner.compare._row', [
                                    'label' => 'Statut',
                                    'values' => $compared->map(
                                        fn($r) => $r->status === 'approved' ? '🟢 Publié' : '🟡 En attente'),
                                    'highlight' => 'none',
                                    'rawValues' => collect(),
                                ])

                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Résumé rapide --}}
                <div class="grid grid-cols-1 md:grid-cols-{{ $compared->count() }} gap-4">
                    @foreach ($compared as $r)
                        <div class="bg-white rounded-2xl border border-gray-200 p-5">
                            <h3 class="font-semibold text-gray-900 mb-3 truncate">{{ $r->name }}</h3>
                            <div class="space-y-2.5">
                                {{-- Score synthétique basé sur les métriques --}}
                                @php
                                    $maxViews = $compared->max('views_count') ?: 1;
                                    $maxRev = $compared->max('total_revenue') ?: 1;
                                    $maxRating = $compared->max('average_rating') ?: 1;
                                    $viewScore = ($r->views_count ?? 0) / $maxViews;
                                    $revScore = ($r->total_revenue ?? 0) / $maxRev;
                                    $ratingScore = ($r->average_rating ?? 0) / $maxRating;
                                @endphp

                                <div>
                                    <div class="flex justify-between text-xs mb-1">
                                        <span class="text-gray-500">Visibilité</span>
                                        <span class="font-medium">{{ number_format($r->views_count ?? 0) }}</span>
                                    </div>
                                    <div class="w-full bg-gray-100 rounded-full h-2">
                                        <div class="bg-blue-500 h-2 rounded-full transition-all"
                                            style="width: {{ $viewScore * 100 }}%"></div>
                                    </div>
                                </div>

                                <div>
                                    <div class="flex justify-between text-xs mb-1">
                                        <span class="text-gray-500">Revenus</span>
                                        <span class="font-medium">{{ number_format($r->total_revenue ?? 0, 0, ',', ' ') }}
                                            F</span>
                                    </div>
                                    <div class="w-full bg-gray-100 rounded-full h-2">
                                        <div class="bg-emerald-500 h-2 rounded-full transition-all"
                                            style="width: {{ $revScore * 100 }}%"></div>
                                    </div>
                                </div>

                                <div>
                                    <div class="flex justify-between text-xs mb-1">
                                        <span class="text-gray-500">Note</span>
                                        <span
                                            class="font-medium">{{ $r->average_rating ? number_format($r->average_rating, 1) : '—' }}</span>
                                    </div>
                                    <div class="w-full bg-gray-100 rounded-full h-2">
                                        <div class="bg-amber-500 h-2 rounded-full transition-all"
                                            style="width: {{ $ratingScore * 100 }}%"></div>
                                    </div>
                                </div>

                                <div class="pt-2 border-t border-gray-100">
                                    <a href="{{ route('owner.residences.edit', $r) }}"
                                        class="text-indigo-600 hover:text-indigo-700 text-xs font-medium">
                                        Modifier cette annonce →
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        @endif
    </div>

    @push('scripts')
        <script>
            function compareApp() {
                return {
                    selected: @json($selectedIds),

                    toggleResidence(id) {
                        const idx = this.selected.indexOf(id);
                        if (idx > -1) {
                            this.selected.splice(idx, 1);
                        } else {
                            if (this.selected.length >= 4) {
                                // Retirer le premier pour faire de la place
                                this.selected.shift();
                            }
                            this.selected.push(id);
                        }
                    },

                    applyComparison() {
                        if (this.selected.length < 2) return;
                        const params = new URLSearchParams();
                        params.set('ids', this.selected.join(','));
                        params.set('period', '{{ $period }}');
                        window.location.href = '{{ route('owner.compare.index') }}?' + params.toString();
                    }
                }
            }
        </script>
    @endpush
@endsection
