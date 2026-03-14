@extends('layouts.owner')

@section('title', 'Vues — Analytics')

@section('owner-content')
    <div class="max-w-7xl mx-auto space-y-6">
        {{-- En-tête --}}
        <div>
            <a href="{{ route('owner.analytics.index') }}"
                class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-orange-500 transition mb-4">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
                Retour aux analytics
            </a>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-xl font-extrabold text-gray-900 flex items-center gap-2">
                        <div class="w-8 h-8 bg-purple-100 rounded-lg flex items-center justify-center">
                            <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                        </div>
                        Analyse des vues
                    </h1>
                    <p class="text-sm text-gray-500 mt-1">Du {{ $startDate->locale('fr')->isoFormat('D MMM YYYY') }} au
                        {{ $endDate->locale('fr')->isoFormat('D MMM YYYY') }}</p>
                </div>

                {{-- Export --}}
                <a href="{{ route('owner.analytics.export.excel', ['start_date' => $startDate->format('Y-m-d'), 'end_date' => $endDate->format('Y-m-d'), 'type' => 'detailed']) }}"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-purple-600 text-white text-sm font-semibold rounded-xl hover:bg-purple-700 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                    </svg>
                    Export Excel
                </a>
            </div>
        </div>

        {{-- Filtre de période --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4">
            <form action="{{ route('owner.analytics.views') }}" method="GET" class="flex flex-wrap items-end gap-3">
                <div class="flex-1 min-w-35">
                    <label class="block text-[11px] font-semibold text-gray-500 uppercase tracking-wider mb-1">Début</label>
                    <input type="date" name="start_date" value="{{ $startDate->format('Y-m-d') }}"
                        class="w-full px-3 py-2 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-purple-500/20 focus:border-purple-500 transition">
                </div>
                <div class="flex-1 min-w-35">
                    <label class="block text-[11px] font-semibold text-gray-500 uppercase tracking-wider mb-1">Fin</label>
                    <input type="date" name="end_date" value="{{ $endDate->format('Y-m-d') }}"
                        class="w-full px-3 py-2 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-purple-500/20 focus:border-purple-500 transition">
                </div>
                <button type="submit"
                    class="px-5 py-2 bg-gray-900 text-white text-sm font-semibold rounded-xl hover:bg-gray-800 transition">
                    Filtrer
                </button>
            </form>
        </div>

        @if ($viewsStats)
            {{-- KPIs --}}
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                {{-- Vues totales --}}
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                    <div class="flex items-center justify-between mb-3">
                        <div class="w-10 h-10 bg-purple-100 rounded-xl flex items-center justify-center">
                            <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                        </div>
                    </div>
                    <p class="text-[11px] font-semibold text-gray-500 uppercase tracking-wider">Vues totales</p>
                    <p class="text-xl font-extrabold text-gray-900 mt-1">
                        {{ number_format($viewsStats['total_views'] ?? 0, 0, ',', ' ') }}</p>
                </div>

                {{-- Visiteurs uniques --}}
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                    <div class="flex items-center justify-between mb-3">
                        <div class="w-10 h-10 bg-blue-100 rounded-xl flex items-center justify-center">
                            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                        </div>
                    </div>
                    <p class="text-[11px] font-semibold text-gray-500 uppercase tracking-wider">Visiteurs uniques</p>
                    <p class="text-xl font-extrabold text-gray-900 mt-1">
                        {{ number_format($viewsStats['unique_visitors'] ?? 0, 0, ',', ' ') }}</p>
                </div>

                {{-- Vues / jour --}}
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                    <div class="flex items-center justify-between mb-3">
                        <div class="w-10 h-10 bg-green-100 rounded-xl flex items-center justify-center">
                            <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                        </div>
                    </div>
                    <p class="text-[11px] font-semibold text-gray-500 uppercase tracking-wider">Vues / jour</p>
                    <p class="text-xl font-extrabold text-gray-900 mt-1">
                        {{ number_format($viewsStats['average_daily'] ?? 0, 0, ',', ' ') }}</p>
                </div>

                {{-- Taux de conversion --}}
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                    <div class="flex items-center justify-between mb-3">
                        <div class="w-10 h-10 bg-orange-100 rounded-xl flex items-center justify-center">
                            <svg class="w-5 h-5 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                            </svg>
                        </div>
                    </div>
                    <p class="text-[11px] font-semibold text-gray-500 uppercase tracking-wider">Conversion</p>
                    <p class="text-xl font-extrabold text-gray-900 mt-1">
                        {{ number_format($viewsStats['conversion_rate'] ?? 0, 1) }}%</p>
                    <p class="text-[11px] text-gray-400 mt-1">{{ $viewsStats['total_contacts'] ?? 0 }}
                        contact{{ ($viewsStats['total_contacts'] ?? 0) > 1 ? 's' : '' }}
                        reçu{{ ($viewsStats['total_contacts'] ?? 0) > 1 ? 's' : '' }}</p>
                </div>
            </div>

            {{-- Graphique vues quotidiennes --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <div class="flex items-center justify-between mb-5">
                    <h2 class="text-sm font-bold text-gray-900">Évolution des vues</h2>
                    <div class="flex items-center gap-2 text-[11px] text-gray-400">
                        <span class="w-2.5 h-2.5 bg-purple-500 rounded-full"></span>
                        Vues par jour
                    </div>
                </div>

                @if (!empty($viewsStats['daily']))
                    @php
                        $maxDailyViews = collect($viewsStats['daily'])->max('views') ?: 1;
                    @endphp
                    <div class="flex items-end gap-0.5 h-40 overflow-x-auto pb-2">
                        @foreach ($viewsStats['daily'] as $day)
                            <div class="flex-1 min-w-1.5 flex flex-col items-center justify-end group relative">
                                <div class="w-full bg-purple-500/80 hover:bg-purple-500 rounded-t transition-all cursor-pointer"
                                    style="height: {{ $maxDailyViews > 0 ? max(($day['views'] / $maxDailyViews) * 100, $day['views'] > 0 ? 4 : 0) : 0 }}%"
                                    title="{{ \Carbon\Carbon::parse($day['date'])->locale('fr')->isoFormat('D MMM') }}: {{ $day['views'] }} vues">
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <div class="flex justify-between mt-2 text-[10px] text-gray-400">
                        <span>{{ $startDate->locale('fr')->isoFormat('D MMM') }}</span>
                        <span>{{ $endDate->locale('fr')->isoFormat('D MMM') }}</span>
                    </div>
                @endif
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                {{-- Vues par résidence --}}
                @if (!empty($viewsStats['by_residence']))
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                        <div class="flex items-center justify-between mb-5">
                            <h2 class="text-sm font-bold text-gray-900">Vues par résidence</h2>
                            <span class="text-[11px] text-gray-400">Top {{ count($viewsStats['by_residence']) }}</span>
                        </div>
                        <div class="space-y-4">
                            @php $maxViews = collect($viewsStats['by_residence'])->max('views') ?: 1; @endphp
                            @foreach ($viewsStats['by_residence'] as $item)
                                <div>
                                    <div class="flex items-center justify-between mb-1.5">
                                        <div class="flex items-center gap-2">
                                            <div
                                                class="w-2 h-2 rounded-full {{ ['bg-purple-500', 'bg-blue-500', 'bg-green-500', 'bg-orange-500', 'bg-pink-500'][$loop->index % 5] }}">
                                            </div>
                                            <span
                                                class="text-sm font-medium text-gray-700">{{ Str::limit($item['name'] ?? 'N/A', 30) }}</span>
                                        </div>
                                        <span
                                            class="text-sm font-bold text-gray-900">{{ number_format($item['views'] ?? 0, 0, ',', ' ') }}</span>
                                    </div>
                                    <div class="w-full bg-gray-100 rounded-full h-2 overflow-hidden">
                                        <div class="h-full {{ ['bg-purple-500', 'bg-blue-500', 'bg-green-500', 'bg-orange-500', 'bg-pink-500'][$loop->index % 5] }} rounded-full transition-all duration-700"
                                            style="width: {{ (($item['views'] ?? 0) / $maxViews) * 100 }}%"></div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Vues par source --}}
                @if (!empty($viewsStats['by_source']) && $viewsStats['by_source']->count() > 0)
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                        <h2 class="text-sm font-bold text-gray-900 mb-5">Vues par source</h2>
                        @php $totalSourceViews = $viewsStats['by_source']->sum('count') ?: 1; @endphp
                        <div class="space-y-4">
                            @foreach ($viewsStats['by_source'] as $source)
                                @php
                                    $sourceName = match (strtolower($source->source)) {
                                        'search' => 'Recherche',
                                        'map' => 'Carte',
                                        'recommendation' => 'Recommandation',
                                        'direct' => 'Accès direct',
                                        'share' => 'Partage',
                                        default => ucfirst($source->source),
                                    };
                                    $sourceIcon = match (strtolower($source->source)) {
                                        'search'
                                            => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>',
                                        'map'
                                            => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>',
                                        'recommendation'
                                            => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>',
                                        'share'
                                            => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/>',
                                        default
                                            => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>',
                                    };
                                    $pct = round(($source->count / $totalSourceViews) * 100, 1);
                                @endphp
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 bg-gray-100 rounded-lg flex items-center justify-center shrink-0">
                                        <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">{!! $sourceIcon !!}</svg>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center justify-between mb-1">
                                            <span class="text-sm font-medium text-gray-700">{{ $sourceName }}</span>
                                            <span class="text-sm font-bold text-gray-900">{{ $pct }}%</span>
                                        </div>
                                        <div class="w-full bg-gray-100 rounded-full h-2 overflow-hidden">
                                            <div class="h-full bg-gray-600 rounded-full transition-all duration-700"
                                                style="width: {{ $pct }}%"></div>
                                        </div>
                                        <p class="text-[11px] text-gray-400 mt-0.5">
                                            {{ number_format($source->count, 0, ',', ' ') }} vues</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            {{-- Détail par jour (tableau) --}}
            @if (!empty($viewsStats['by_day']))
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="p-5 border-b border-gray-100">
                        <h2 class="text-sm font-bold text-gray-900">Détail journalier</h2>
                        <p class="text-[11px] text-gray-400 mt-0.5">Jours avec des vues enregistrées</p>
                    </div>

                    {{-- Mobile --}}
                    <div class="md:hidden divide-y divide-gray-100">
                        @foreach ($viewsStats['by_day'] as $day)
                            <div class="px-5 py-3 flex items-center justify-between">
                                <span
                                    class="text-sm text-gray-700">{{ \Carbon\Carbon::parse($day['date'])->locale('fr')->isoFormat('ddd D MMM') }}</span>
                                <span
                                    class="text-sm font-bold text-gray-900">{{ number_format($day['views'] ?? 0, 0, ',', ' ') }}
                                    vues</span>
                            </div>
                        @endforeach
                    </div>

                    {{-- Desktop --}}
                    <div class="hidden md:block overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th
                                        class="px-5 py-3 text-left text-[11px] font-semibold text-gray-500 uppercase tracking-wider">
                                        Date</th>
                                    <th
                                        class="px-5 py-3 text-center text-[11px] font-semibold text-gray-500 uppercase tracking-wider">
                                        Vues</th>
                                    <th
                                        class="px-5 py-3 text-right text-[11px] font-semibold text-gray-500 uppercase tracking-wider">
                                        % du total</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @php $totalViews = $viewsStats['total_views'] ?: 1; @endphp
                                @foreach ($viewsStats['by_day'] as $day)
                                    <tr class="hover:bg-gray-50/50 transition">
                                        <td class="px-5 py-3 text-sm text-gray-700">
                                            {{ \Carbon\Carbon::parse($day['date'])->locale('fr')->isoFormat('dddd D MMMM') }}
                                        </td>
                                        <td class="px-5 py-3 text-center">
                                            <span class="inline-flex items-center gap-1.5 text-sm font-bold text-gray-900">
                                                {{ number_format($day['views'] ?? 0, 0, ',', ' ') }}
                                            </span>
                                        </td>
                                        <td class="px-5 py-3 text-right">
                                            <span
                                                class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold bg-purple-50 text-purple-600">
                                                {{ round((($day['views'] ?? 0) / $totalViews) * 100, 1) }}%
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="bg-purple-50/50">
                                <tr>
                                    <td class="px-5 py-3 text-sm font-bold text-gray-900">Total</td>
                                    <td class="px-5 py-3 text-sm font-extrabold text-purple-600 text-center">
                                        {{ number_format($viewsStats['total_views'] ?? 0, 0, ',', ' ') }}</td>
                                    <td class="px-5 py-3 text-right">
                                        <span class="text-[11px] font-bold text-purple-600">100%</span>
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            @endif
        @else
            {{-- État vide --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-12 text-center">
                <div class="w-16 h-16 bg-gray-100 rounded-2xl flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                </div>
                <h3 class="text-sm font-bold text-gray-900 mb-1">Aucune donnée de vues</h3>
                <p class="text-sm text-gray-500">Aucune vue enregistrée sur cette période. Essayez de modifier les dates.
                </p>
            </div>
        @endif
    </div>
@endsection
