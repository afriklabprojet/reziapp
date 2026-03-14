<x-filament-panels::page>
    <div class="space-y-6">

        {{-- Bannière --}}
        <div class="p-6 bg-linear-to-r from-blue-600 to-indigo-600 dark:from-blue-700 dark:to-indigo-700 rounded-xl text-white">
            <h2 class="text-2xl font-bold">📊 Statistiques REZI</h2>
            <p class="mt-1 opacity-90">Vue d'ensemble des performances de la plateforme</p>
        </div>

        {{-- Statistiques globales --}}
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4 text-center">
                <p class="text-sm text-gray-500 dark:text-gray-400">Utilisateurs</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($globalStats['total_users'], 0, ',', ' ') }}</p>
                <p class="text-xs text-green-600 dark:text-green-400 mt-1">+{{ $globalStats['new_users_month'] }} ce mois</p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4 text-center">
                <p class="text-sm text-gray-500 dark:text-gray-400">Propriétaires</p>
                <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ number_format($globalStats['total_owners'], 0, ',', ' ') }}</p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4 text-center">
                <p class="text-sm text-gray-500 dark:text-gray-400">Résidences</p>
                <p class="text-2xl font-bold text-indigo-600 dark:text-indigo-400">{{ number_format($globalStats['total_residences'], 0, ',', ' ') }}</p>
                <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">{{ $globalStats['active_residences'] }} actives</p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4 text-center">
                <p class="text-sm text-gray-500 dark:text-gray-400">Réservations</p>
                <p class="text-2xl font-bold text-amber-600 dark:text-amber-400">{{ number_format($globalStats['total_bookings'], 0, ',', ' ') }}</p>
                <p class="text-xs text-green-600 dark:text-green-400 mt-1">+{{ $globalStats['bookings_month'] }} ce mois</p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4 text-center">
                <p class="text-sm text-gray-500 dark:text-gray-400">Revenus totaux</p>
                <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ number_format($globalStats['total_revenue'], 0, ',', ' ') }}</p>
                <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">FCFA</p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {{-- Résidences par commune --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="p-5 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">🏘️ Résidences par commune</h3>
                </div>
                <div class="p-5">
                    @if($residencesByCommune->count() > 0)
                        <div class="space-y-3">
                            @php $maxCount = $residencesByCommune->max('count'); @endphp
                            @foreach($residencesByCommune as $commune)
                                <div class="flex items-center gap-3">
                                    <span class="w-28 text-sm text-gray-700 dark:text-gray-300 font-medium truncate">{{ $commune->commune ?? 'Non renseigné' }}</span>
                                    <div class="flex-1 bg-gray-100 dark:bg-gray-700 rounded-full h-4 overflow-hidden">
                                        <div class="h-full bg-indigo-500 dark:bg-indigo-400 rounded-full transition-all" style="width: {{ $maxCount > 0 ? ($commune->count / $maxCount * 100) : 0 }}%"></div>
                                    </div>
                                    <span class="text-sm font-bold text-gray-800 dark:text-gray-200 w-10 text-right">{{ $commune->count }}</span>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-center text-gray-400 dark:text-gray-500 py-8">Aucune résidence active</p>
                    @endif
                </div>
            </div>

            {{-- Top résidences --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="p-5 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">🏆 Top résidences (par vues)</h3>
                </div>
                <div class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($topResidences as $index => $residence)
                        <div class="flex items-center gap-3 p-4 hover:bg-gray-50 dark:hover:bg-gray-700/50">
                            <span class="flex items-center justify-center w-8 h-8 rounded-full text-sm font-bold
                                {{ $index < 3 ? 'bg-amber-100 dark:bg-amber-900/50 text-amber-800 dark:text-amber-300' : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400' }}">
                                {{ $index + 1 }}
                            </span>
                            <div class="flex-1 min-w-0">
                                <p class="font-medium text-gray-900 dark:text-white truncate">{{ $residence->name }}</p>
                                <p class="text-sm text-gray-500 dark:text-gray-400">{{ $residence->commune }} • {{ $residence->owner?->name }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-bold text-gray-900 dark:text-white">{{ number_format($residence->views_count, 0, ',', ' ') }} vues</p>
                                @if($residence->average_rating)
                                    <p class="text-xs text-amber-600 dark:text-amber-400">{{ number_format($residence->average_rating, 1) }} ⭐</p>
                                @endif
                            </div>
                        </div>
                    @empty
                        <p class="text-center text-gray-400 dark:text-gray-500 py-8">Aucune résidence</p>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Revenus par mois --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="p-5 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">💰 Revenus & Réservations (6 derniers mois)</h3>
            </div>
            <div class="p-5">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-200 dark:border-gray-700">
                                <th class="text-left py-3 px-4 font-semibold text-gray-600 dark:text-gray-400">Mois</th>
                                <th class="text-right py-3 px-4 font-semibold text-gray-600 dark:text-gray-400">Revenus (FCFA)</th>
                                <th class="text-right py-3 px-4 font-semibold text-gray-600 dark:text-gray-400">Réservations</th>
                                <th class="text-left py-3 px-4 font-semibold text-gray-600 dark:text-gray-400">Tendance</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @php $maxRevenue = collect($revenueByMonth)->max('revenue'); @endphp
                            @foreach($revenueByMonth as $month)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                    <td class="py-3 px-4 font-medium text-gray-900 dark:text-white">{{ $month['month'] }}</td>
                                    <td class="py-3 px-4 text-right font-bold text-green-700 dark:text-green-400">{{ number_format($month['revenue'], 0, ',', ' ') }}</td>
                                    <td class="py-3 px-4 text-right text-gray-700 dark:text-gray-300">{{ $month['bookings'] }}</td>
                                    <td class="py-3 px-4">
                                        <div class="w-32 bg-gray-100 dark:bg-gray-700 rounded-full h-2.5 overflow-hidden">
                                            <div class="h-full bg-green-500 dark:bg-green-400 rounded-full" style="width: {{ $maxRevenue > 0 ? ($month['revenue'] / $maxRevenue * 100) : 0 }}%"></div>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Inscriptions par jour (30j) --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="p-5 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">📈 Inscriptions (30 derniers jours)</h3>
            </div>
            <div class="p-5">
                <div class="flex items-end gap-1 h-32">
                    @php $maxUsers = collect($registrationsByDay)->max('users') ?: 1; @endphp
                    @foreach($registrationsByDay as $day)
                        <div class="flex-1 flex flex-col items-center gap-1 group relative">
                            <div class="w-full bg-blue-500 dark:bg-blue-400 rounded-t transition-all hover:bg-blue-600 dark:hover:bg-blue-300" 
                                 style="height: {{ ($day['users'] / $maxUsers) * 100 }}%"
                                 title="{{ $day['date'] }}: {{ $day['users'] }} inscriptions">
                            </div>
                            @if(($loop->index % 5) === 0)
                                <span class="text-[10px] text-gray-400 dark:text-gray-500 absolute -bottom-4">{{ $day['date'] }}</span>
                            @endif
                        </div>
                    @endforeach
                </div>
                <div class="mt-6 flex items-center gap-4 text-sm text-gray-500 dark:text-gray-400">
                    <span class="flex items-center gap-1"><span class="w-3 h-3 bg-blue-500 dark:bg-blue-400 rounded inline-block"></span> Inscriptions/jour</span>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>
