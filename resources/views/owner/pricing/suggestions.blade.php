@extends('layouts.owner')

@section('title', 'Suggestions de Prix IA - ' . $residence->name)

@section('owner-content')
    <div class="space-y-6" x-data="priceSuggestions(@js(['applyUrl' => route('owner.pricing.apply', $residence), 'csrfToken' => csrf_token()]))">
        <!-- En-tête -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <nav class="text-sm text-gray-500 mb-2">
                    <a href="{{ route('owner.residences.index') }}" class="hover:text-[#e00b41]">Mes résidences</a>
                    <span class="mx-2">›</span>
                    <a href="{{ route('owner.pricing.index', $residence) }}" class="hover:text-[#e00b41]">Tarification</a>
                    <span class="mx-2">›</span>
                    <span class="text-gray-700">Suggestions IA</span>
                </nav>
                <h1 class="text-2xl font-bold text-gray-900">
                    <span class="inline-flex items-center gap-2">
                        <svg class="w-7 h-7 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                        </svg>
                        Suggestions de Prix Intelligentes
                    </span>
                </h1>
                <p class="text-gray-600 mt-1">{{ $residence->name }}</p>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('owner.pricing.index', $residence) }}"
                    class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50">
                    Retour au calendrier
                </a>
                <button @click="applySelected" :disabled="selectedCount === 0 || loading"
                    class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2">
                    <template x-if="!loading">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                    </template>
                    <template x-if="loading">
                        <svg class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z">
                            </path>
                        </svg>
                    </template>
                    <span x-text="loading ? 'Application...' : 'Appliquer (' + selectedCount + ')'"></span>
                </button>
            </div>
        </div>

        <!-- Toast notification -->
        <div x-show="toast.show" x-transition :class="toast.type === 'success' ? 'bg-green-500' : 'bg-red-500'"
            class="fixed bottom-6 right-6 text-white px-6 py-3 rounded-xl shadow-lg z-50 flex items-center gap-3" x-cloak>
            <template x-if="toast.type === 'success'">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
            </template>
            <template x-if="toast.type === 'error'">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </template>
            <span x-text="toast.message"></span>
        </div>
        <!-- Résumé -->
        <div class="bg-linear-to-r from-purple-600 to-[#ff385c] rounded-2xl p-6 text-white">
            <div class="flex items-start gap-4">
                <div class="p-3 bg-white/20 rounded-xl">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                    </svg>
                </div>
                <div class="flex-1">
                    <h2 class="text-xl font-bold mb-2">Analyse de votre résidence</h2>
                    <p class="text-white/80">
                        Notre algorithme a analysé {{ $suggestions['summary']['total_days_analyzed'] ?? 0 }} jours
                        et identifié des opportunités d'optimisation de vos revenus.
                    </p>
                </div>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-6">
                <div class="bg-white/10 rounded-xl p-4">
                    <p class="text-white/70 text-sm">Jours analysés</p>
                    <p class="text-2xl font-bold">{{ $suggestions['summary']['total_days_analyzed'] ?? 0 }}</p>
                </div>
                <div class="bg-white/10 rounded-xl p-4">
                    <p class="text-white/70 text-sm">Ajustement moyen</p>
                    <p
                        class="text-2xl font-bold {{ ($suggestions['summary']['avg_adjustment'] ?? 0) >= 0 ? 'text-green-300' : 'text-red-300' }}">
                        {{ ($suggestions['summary']['avg_adjustment'] ?? 0) >= 0 ? '+' : '' }}{{ $suggestions['summary']['avg_adjustment'] ?? 0 }}%
                    </p>
                </div>
                <div class="bg-white/10 rounded-xl p-4">
                    <p class="text-white/70 text-sm">Max hausse</p>
                    <p class="text-2xl font-bold text-green-300">+{{ $suggestions['summary']['max_increase'] ?? 0 }}%</p>
                </div>
                <div class="bg-white/10 rounded-xl p-4">
                    <p class="text-white/70 text-sm">Max baisse</p>
                    <p class="text-2xl font-bold text-red-300">{{ $suggestions['summary']['max_decrease'] ?? 0 }}%</p>
                </div>
            </div>
        </div>

        <!-- Suggestions immédiates (7 jours) -->
        @if (!empty($suggestions['immediate']))
            <div class="bg-white rounded-2xl shadow-sm overflow-hidden">
                <div class="p-6 border-b border-gray-100">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-red-100 rounded-xl flex items-center justify-center">
                                <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="font-bold text-gray-900">Ajustements immédiats</h3>
                                <p class="text-sm text-gray-500">7 prochains jours - Action recommandée</p>
                            </div>
                        </div>
                        <button @click="selectAll('immediate')" class="text-sm text-purple-600 hover:text-purple-700">
                            Tout sélectionner
                        </button>
                    </div>
                </div>
                <div class="divide-y divide-gray-100">
                    @foreach ($suggestions['immediate'] as $suggestion)
                        <div class="p-4 hover:bg-gray-50 flex items-center gap-4">
                            <input type="checkbox" x-model="selected" data-section="immediate"
                                value="{{ json_encode(['type' => 'daily', 'date' => $suggestion['date'], 'price' => $suggestion['suggested_price']]) }}"
                                class="w-5 h-5 text-purple-600 rounded focus:ring-purple-500">
                            <div class="flex-1">
                                <div class="flex items-center gap-2">
                                    <span class="font-medium">{{ ucfirst($suggestion['day_name']) }}</span>
                                    <span
                                        class="text-gray-500">{{ \Carbon\Carbon::parse($suggestion['date'])->format('d/m/Y') }}</span>
                                </div>
                                <div class="flex flex-wrap gap-1 mt-1">
                                    @foreach ($suggestion['reasons'] as $reason)
                                        <span
                                            class="px-2 py-0.5 bg-gray-100 text-gray-600 text-xs rounded-full">{{ $reason }}</span>
                                    @endforeach
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="text-sm text-gray-500 line-through">
                                    {{ number_format($suggestion['base_price'], 0, ',', ' ') }} F</div>
                                <div
                                    class="text-lg font-bold {{ $suggestion['adjustment'] >= 0 ? 'text-green-600' : 'text-[#e00b41]' }}">
                                    {{ number_format($suggestion['suggested_price'], 0, ',', ' ') }} F
                                </div>
                                <div
                                    class="text-xs {{ $suggestion['adjustment'] >= 0 ? 'text-green-600' : 'text-[#e00b41]' }}">
                                    {{ $suggestion['adjustment'] >= 0 ? '+' : '' }}{{ $suggestion['adjustment'] }}%
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- Suggestions à venir (7-30 jours) -->
        @if (!empty($suggestions['upcoming']))
            <div class="bg-white rounded-2xl shadow-sm overflow-hidden">
                <div class="p-6 border-b border-gray-100">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-[#ffd1da] rounded-xl flex items-center justify-center">
                                <svg class="w-5 h-5 text-[#e00b41]" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="font-bold text-gray-900">Prochaines semaines</h3>
                                <p class="text-sm text-gray-500">7 à 30 jours - Planifiez à l'avance</p>
                            </div>
                        </div>
                        <button @click="selectAll('upcoming')" class="text-sm text-purple-600 hover:text-purple-700">
                            Tout sélectionner
                        </button>
                    </div>
                </div>
                <div class="max-h-96 overflow-y-auto">
                    <div class="divide-y divide-gray-100">
                        @foreach (array_slice($suggestions['upcoming'], 0, 15) as $suggestion)
                            <div class="p-4 hover:bg-gray-50 flex items-center gap-4">
                                <input type="checkbox" x-model="selected" data-section="upcoming"
                                    value="{{ json_encode(['type' => 'daily', 'date' => $suggestion['date'], 'price' => $suggestion['suggested_price']]) }}"
                                    class="w-5 h-5 text-purple-600 rounded focus:ring-purple-500">
                                <div class="flex-1">
                                    <div class="flex items-center gap-2">
                                        <span class="font-medium">{{ ucfirst($suggestion['day_name']) }}</span>
                                        <span
                                            class="text-gray-500">{{ \Carbon\Carbon::parse($suggestion['date'])->format('d/m/Y') }}</span>
                                    </div>
                                    <div class="flex flex-wrap gap-1 mt-1">
                                        @foreach ($suggestion['reasons'] as $reason)
                                            <span
                                                class="px-2 py-0.5 bg-gray-100 text-gray-600 text-xs rounded-full">{{ $reason }}</span>
                                        @endforeach
                                    </div>
                                </div>
                                <div class="text-right">
                                    <div
                                        class="text-lg font-bold {{ $suggestion['adjustment'] >= 0 ? 'text-green-600' : 'text-[#e00b41]' }}">
                                        {{ number_format($suggestion['suggested_price'], 0, ',', ' ') }} F
                                    </div>
                                    <div
                                        class="text-xs {{ $suggestion['adjustment'] >= 0 ? 'text-green-600' : 'text-[#e00b41]' }}">
                                        {{ $suggestion['adjustment'] >= 0 ? '+' : '' }}{{ $suggestion['adjustment'] }}%
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
                @if (count($suggestions['upcoming']) > 15)
                    <div class="p-4 border-t border-gray-100 text-center">
                        <button class="text-purple-600 hover:text-purple-700 text-sm">
                            Voir {{ count($suggestions['upcoming']) - 15 }} autres suggestions
                        </button>
                    </div>
                @endif
            </div>
        @endif

        <!-- Suggestions saisonnières -->
        @if (!empty($suggestions['seasonal']))
            <div class="bg-white rounded-2xl shadow-sm overflow-hidden">
                <div class="p-6 border-b border-gray-100">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-green-100 rounded-xl flex items-center justify-center">
                                <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="font-bold text-gray-900">Saisons tarifaires suggérées</h3>
                                <p class="text-sm text-gray-500">Créez automatiquement des périodes tarifaires</p>
                            </div>
                        </div>
                        <form action="{{ route('owner.pricing.apply-all', $residence) }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="text-sm text-purple-600 hover:text-purple-700 font-medium">
                                Créer toutes les saisons
                            </button>
                        </form>
                    </div>
                </div>
                <div class="divide-y divide-gray-100">
                    @foreach ($suggestions['seasonal'] as $season)
                        <div class="p-6 hover:bg-gray-50">
                            <div class="flex items-start justify-between">
                                <div class="flex items-start gap-4">
                                    <input type="checkbox" x-model="selected" data-section="seasonal"
                                        value="{{ json_encode([
                                            'type' => 'seasonal',
                                            'start_date' => $season['start_date'],
                                            'end_date' => $season['end_date'],
                                            'price' => $season['suggested_price'],
                                            'name' => $season['name'],
                                        ]) }}"
                                        class="w-5 h-5 text-purple-600 rounded focus:ring-purple-500 mt-1">
                                    <div>
                                        <h4 class="font-bold text-gray-900">{{ $season['name'] }}</h4>
                                        <p class="text-sm text-gray-500 mt-1">
                                            Du {{ \Carbon\Carbon::parse($season['start_date'])->format('d/m/Y') }}
                                            au {{ \Carbon\Carbon::parse($season['end_date'])->format('d/m/Y') }}
                                            <span class="text-gray-400">({{ $season['days_count'] }} jours)</span>
                                        </p>
                                        <div class="flex flex-wrap gap-1 mt-2">
                                            @foreach ($season['reasons'] as $reason)
                                                <span
                                                    class="px-2 py-0.5 bg-purple-100 text-purple-700 text-xs rounded-full">{{ $reason }}</span>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <div class="text-2xl font-bold text-gray-900">
                                        {{ number_format($season['suggested_price'], 0, ',', ' ') }} F</div>
                                    <div
                                        class="text-sm {{ $season['avg_adjustment'] >= 0 ? 'text-green-600' : 'text-[#e00b41]' }}">
                                        {{ $season['avg_adjustment'] >= 0 ? '+' : '' }}{{ $season['avg_adjustment'] }}% vs
                                        prix de base
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- Aucune suggestion -->
        @if (empty($suggestions['immediate']) && empty($suggestions['upcoming']) && empty($suggestions['seasonal']))
            <div class="bg-white rounded-2xl shadow-sm p-12 text-center">
                <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">Vos prix sont optimaux !</h3>
                <p class="text-gray-600 max-w-md mx-auto">
                    Notre algorithme n'a pas trouvé d'ajustements significatifs à suggérer pour les 90 prochains jours.
                    Vos prix sont bien calibrés par rapport au marché.
                </p>
            </div>
        @endif

        <!-- Info box -->
        <div class="bg-blue-50 rounded-xl p-4 flex items-start gap-3">
            <svg class="w-5 h-5 text-blue-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <div class="text-sm text-blue-800">
                <p class="font-medium">Comment fonctionnent les suggestions ?</p>
                <p class="mt-1">
                    Notre algorithme analyse plusieurs facteurs : week-ends, jours fériés, saisons, demande locale,
                    et prix du marché pour vous proposer des ajustements optimaux. Les suggestions sont basées sur
                    les données de votre résidence et du marché immobilier local.
                </p>
            </div>
        </div>
    </div>

    @push('scripts')
    @endpush
@endsection
