<x-filament-panels::page>
    @php
        $generatedAtLabel = isset($generatedAt)
            ? \Illuminate\Support\Carbon::parse($generatedAt)->translatedFormat('d M Y à H:i')
            : now()->translatedFormat('d M Y à H:i');

        $bookingsStatusCollection = collect($bookingsByStatus ?? []);
        $bookingsStatusLabels = [
            'pending' => 'En attente',
            'confirmed' => 'Confirmées',
            'completed' => 'Terminées',
            'cancelled' => 'Annulées',
            'failed' => 'Échouées',
            'refunded' => 'Remboursées',
            'disputed' => 'Litiges',
        ];
        $bookingsStatusColors = [
            'pending' => 'bg-amber-500',
            'confirmed' => 'bg-sky-500',
            'completed' => 'bg-emerald-500',
            'cancelled' => 'bg-rose-500',
            'failed' => 'bg-red-500',
            'refunded' => 'bg-violet-500',
            'disputed' => 'bg-orange-500',
        ];
        $totalBookingsByStatus = max(1, (int) $bookingsStatusCollection->sum());
        $bookingsStatusChartData = $bookingsStatusCollection
            ->map(function ($count, $status) use ($bookingsStatusLabels) {
                return [
                    'status' => $status,
                    'label' => $bookingsStatusLabels[$status] ?? \Illuminate\Support\Str::headline(str_replace('_', ' ', $status)),
                    'count' => $count,
                ];
            })
            ->values()
            ->all();

        $registrations = collect($registrationsByDay);
        $peakRegistrations = max(1, (int) $registrations->max('users'));
        $registrationsLast7Days = $registrations->take(-7)->sum('users');
        $registrationsLast30Days = $registrations->sum('users');

        $activeResidencesRate = $globalStats['total_residences'] > 0
            ? round(($globalStats['active_residences'] / $globalStats['total_residences']) * 100)
            : 0;

        $ownerCoverageRate = $globalStats['total_users'] > 0
            ? round(($globalStats['total_owners'] / $globalStats['total_users']) * 100)
            : 0;

        $revenueSeries = collect($revenueByMonth);
        $currentMonth = $revenueSeries->last();
        $previousMonth = $revenueSeries->slice(-2, 1)->first();
        $revenueTrend = $previousMonth && ($previousMonth['revenue'] ?? 0) > 0
            ? round((($currentMonth['revenue'] ?? 0) - $previousMonth['revenue']) / $previousMonth['revenue'] * 100)
            : null;
        $maxRevenue = max(1, (int) $revenueSeries->max('revenue'));
        $maxCommuneCount = max(1, (int) $residencesByCommune->max('count'));
        $residencesByCommuneChartData = $residencesByCommune
            ->map(function ($commune) {
                return [
                    'label' => $commune->commune ?? 'Non renseigné',
                    'count' => $commune->count,
                ];
            })
            ->values()
            ->all();

        $adminStatisticsData = [
            'revenueByMonth' => $revenueByMonth,
            'registrationsByDay' => $registrationsByDay,
            'bookingsByStatus' => $bookingsStatusChartData,
            'residencesByCommune' => $residencesByCommuneChartData,
        ];
    @endphp

    <div class="space-y-6">
        <x-filament.admin.hero
            eyebrow="Vue exécutive"
            title="Statistiques de la plateforme"
            subtitle="Un tableau de bord plus lisible pour suivre l’acquisition, la performance commerciale et la qualité opérationnelle de Rezi Studio Meublé Faya."
            icon="heroicon-o-chart-bar-square"
            tone="orange"
        >
            <x-slot name="meta">
                <div>
                    <p class="text-xs uppercase tracking-[0.2em] text-white/60">Dernière génération</p>
                    <p class="mt-1 text-sm font-medium text-white">{{ $generatedAtLabel }}</p>
                </div>
                <div>
                    <p class="text-xs uppercase tracking-[0.2em] text-white/60">Cache</p>
                    <p class="mt-1 text-sm font-medium text-white">5 minutes</p>
                </div>
                <div>
                    <p class="text-xs uppercase tracking-[0.2em] text-white/60">Périmètre</p>
                    <p class="mt-1 text-sm font-medium text-white">Plateforme complète</p>
                </div>
                <div>
                    <p class="text-xs uppercase tracking-[0.2em] text-white/60">Source</p>
                    <p class="mt-1 text-sm font-medium text-white">Données live consolidées</p>
                </div>
            </x-slot>
        </x-filament.admin.hero>

        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <article class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Utilisateurs inscrits</p>
                        <p class="mt-3 text-3xl font-semibold tracking-tight text-gray-950 dark:text-white">{{ number_format($globalStats['total_users'], 0, ',', ' ') }}</p>
                    </div>
                    <span class="rounded-full bg-orange-50 px-2.5 py-1 text-xs font-semibold text-orange-700 ring-1 ring-orange-200 dark:bg-orange-500/10 dark:text-orange-300 dark:ring-orange-400/20">
                        +{{ number_format($globalStats['new_users_month'], 0, ',', ' ') }} ce mois
                    </span>
                </div>
                <div class="mt-5 flex items-center justify-between text-sm">
                    <span class="text-gray-500 dark:text-gray-400">Propriétaires actifs</span>
                    <span class="font-medium text-gray-900 dark:text-white">{{ number_format($globalStats['total_owners'], 0, ',', ' ') }} • {{ $ownerCoverageRate }}%</span>
                </div>
            </article>

            <article class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Parc immobilier</p>
                        <p class="mt-3 text-3xl font-semibold tracking-tight text-gray-950 dark:text-white">{{ number_format($globalStats['total_residences'], 0, ',', ' ') }}</p>
                    </div>
                    <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700 ring-1 ring-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-300 dark:ring-emerald-400/20">
                        {{ $globalStats['active_residences'] }} actives
                    </span>
                </div>
                <div class="mt-5">
                    <div class="mb-2 flex items-center justify-between text-sm">
                        <span class="text-gray-500 dark:text-gray-400">Taux d’activation</span>
                        <span class="font-medium text-gray-900 dark:text-white">{{ $activeResidencesRate }}%</span>
                    </div>
                    <div class="h-2 rounded-full bg-gray-100 dark:bg-gray-800">
                        <div class="h-2 rounded-full bg-emerald-500" style="width: {{ $activeResidencesRate }}%"></div>
                    </div>
                </div>
            </article>

            <article class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Réservations</p>
                        <p class="mt-3 text-3xl font-semibold tracking-tight text-gray-950 dark:text-white">{{ number_format($globalStats['total_bookings'], 0, ',', ' ') }}</p>
                    </div>
                    <span class="rounded-full bg-sky-50 px-2.5 py-1 text-xs font-semibold text-sky-700 ring-1 ring-sky-200 dark:bg-sky-500/10 dark:text-sky-300 dark:ring-sky-400/20">
                        +{{ number_format($globalStats['bookings_month'], 0, ',', ' ') }} ce mois
                    </span>
                </div>
                <div class="mt-5 flex items-center justify-between text-sm">
                    <span class="text-gray-500 dark:text-gray-400">Statuts suivis</span>
                    <span class="font-medium text-gray-900 dark:text-white">{{ $bookingsStatusCollection->count() }} catégories</span>
                </div>
            </article>

            <article class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Revenus encaissés</p>
                        <p class="mt-3 text-3xl font-semibold tracking-tight text-gray-950 dark:text-white">{{ number_format($globalStats['total_revenue'], 0, ',', ' ') }}</p>
                    </div>
                    <span class="rounded-full bg-gray-950 px-2.5 py-1 text-xs font-semibold text-white dark:bg-white dark:text-gray-950">
                        FCFA
                    </span>
                </div>
                <div class="mt-5 flex items-center justify-between text-sm">
                    <span class="text-gray-500 dark:text-gray-400">Ce mois-ci</span>
                    <span class="font-medium text-gray-900 dark:text-white">{{ number_format($globalStats['revenue_month'], 0, ',', ' ') }}</span>
                </div>
                <div class="mt-2 text-xs font-medium {{ is_null($revenueTrend) ? 'text-gray-500 dark:text-gray-400' : ($revenueTrend >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400') }}">
                    @if (is_null($revenueTrend))
                        Historique insuffisant pour calculer la tendance
                    @elseif ($revenueTrend >= 0)
                        +{{ $revenueTrend }}% vs mois précédent
                    @else
                        {{ $revenueTrend }}% vs mois précédent
                    @endif
                </div>
            </article>
        </section>

        <section class="grid gap-6 xl:grid-cols-3">
            <article class="xl:col-span-2 rounded-2xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10">
                <div class="flex flex-col gap-2 border-b border-gray-200 px-5 py-4 dark:border-white/10 lg:flex-row lg:items-end lg:justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-950 dark:text-white">Performance commerciale</h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Revenus et volume de réservations sur les 6 derniers mois.</p>
                    </div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">
                        Dernier mois: <span class="font-semibold text-gray-900 dark:text-white">{{ number_format($currentMonth['revenue'] ?? 0, 0, ',', ' ') }} FCFA</span>
                    </div>
                </div>

                <div class="border-b border-gray-200 px-5 py-5 dark:border-white/10">
                    <div class="h-72">
                        <canvas id="adminRevenueChart"></canvas>
                    </div>
                </div>

                <div class="overflow-x-auto px-3 py-3 sm:px-5 sm:py-4">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-200 text-left dark:border-white/10">
                                <th class="px-3 py-3 font-medium text-gray-500 dark:text-gray-400">Période</th>
                                <th class="px-3 py-3 text-right font-medium text-gray-500 dark:text-gray-400">Revenus</th>
                                <th class="px-3 py-3 text-right font-medium text-gray-500 dark:text-gray-400">Réservations</th>
                                <th class="px-3 py-3 font-medium text-gray-500 dark:text-gray-400">Intensité</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-white/10">
                            @foreach ($revenueByMonth as $month)
                                <tr class="align-middle hover:bg-gray-50/80 dark:hover:bg-white/5">
                                    <td class="px-3 py-3 font-medium text-gray-900 dark:text-white">{{ $month['month'] }}</td>
                                    <td class="px-3 py-3 text-right font-semibold text-emerald-600 dark:text-emerald-400">{{ number_format($month['revenue'], 0, ',', ' ') }} FCFA</td>
                                    <td class="px-3 py-3 text-right text-gray-700 dark:text-gray-300">{{ number_format($month['bookings'], 0, ',', ' ') }}</td>
                                    <td class="px-3 py-3">
                                        <div class="flex items-center gap-3">
                                            <div class="h-2.5 flex-1 rounded-full bg-gray-100 dark:bg-gray-800">
                                                <div class="h-2.5 rounded-full bg-linear-to-r from-orange-500 to-emerald-500" style="width: {{ ($month['revenue'] / $maxRevenue) * 100 }}%"></div>
                                            </div>
                                            <span class="w-10 text-right text-xs font-medium text-gray-500 dark:text-gray-400">{{ round(($month['revenue'] / $maxRevenue) * 100) }}%</span>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </article>

            <article class="rounded-2xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10">
                <div class="border-b border-gray-200 px-5 py-4 dark:border-white/10">
                    <h3 class="text-lg font-semibold text-gray-950 dark:text-white">Santé des réservations</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Répartition des dossiers par statut opérationnel.</p>
                </div>

                <div class="border-b border-gray-200 px-5 py-5 dark:border-white/10">
                    <div class="mx-auto h-72 max-w-xs">
                        <canvas id="adminBookingsStatusChart"></canvas>
                    </div>
                </div>

                <div class="space-y-4 px-5 py-5">
                    @forelse ($bookingsStatusCollection->sortDesc() as $status => $count)
                        @php
                            $label = $bookingsStatusLabels[$status] ?? \Illuminate\Support\Str::headline(str_replace('_', ' ', $status));
                            $colorClass = $bookingsStatusColors[$status] ?? 'bg-gray-500';
                            $percentage = round(($count / $totalBookingsByStatus) * 100);
                        @endphp

                        <div class="space-y-2">
                            <div class="flex items-center justify-between gap-3">
                                <div class="flex items-center gap-2">
                                    <span class="h-2.5 w-2.5 rounded-full {{ $colorClass }}"></span>
                                    <span class="text-sm font-medium text-gray-800 dark:text-gray-200">{{ $label }}</span>
                                </div>
                                <div class="text-right">
                                    <span class="text-sm font-semibold text-gray-950 dark:text-white">{{ number_format($count, 0, ',', ' ') }}</span>
                                    <span class="ml-2 text-xs text-gray-500 dark:text-gray-400">{{ $percentage }}%</span>
                                </div>
                            </div>
                            <div class="h-2 rounded-full bg-gray-100 dark:bg-gray-800">
                                <div class="h-2 rounded-full {{ $colorClass }}" style="width: {{ $percentage }}%"></div>
                            </div>
                        </div>
                    @empty
                        <p class="rounded-2xl border border-dashed border-gray-300 px-4 py-8 text-center text-sm text-gray-500 dark:border-white/10 dark:text-gray-400">
                            Aucun statut de réservation disponible pour le moment.
                        </p>
                    @endforelse
                </div>
            </article>
        </section>

        <section class="grid gap-6 xl:grid-cols-3">
            <article class="xl:col-span-2 rounded-2xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10">
                <div class="flex flex-col gap-2 border-b border-gray-200 px-5 py-4 dark:border-white/10 lg:flex-row lg:items-end lg:justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-950 dark:text-white">Acquisition utilisateurs</h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Volume d’inscriptions quotidiennes sur les 30 derniers jours.</p>
                    </div>
                    <div class="flex flex-wrap items-center gap-3 text-sm text-gray-500 dark:text-gray-400">
                        <span>7 jours: <span class="font-semibold text-gray-900 dark:text-white">{{ number_format($registrationsLast7Days, 0, ',', ' ') }}</span></span>
                        <span>30 jours: <span class="font-semibold text-gray-900 dark:text-white">{{ number_format($registrationsLast30Days, 0, ',', ' ') }}</span></span>
                    </div>
                </div>

                <div class="px-5 py-5">
                    <div class="h-72 rounded-2xl bg-gray-50 px-3 py-3 dark:bg-gray-900/40">
                        <canvas id="adminRegistrationsChart"></canvas>
                    </div>
                </div>
            </article>

            <article class="rounded-2xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10">
                <div class="border-b border-gray-200 px-5 py-4 dark:border-white/10">
                    <h3 class="text-lg font-semibold text-gray-950 dark:text-white">Couverture territoriale</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Communes avec le plus de résidences actives.</p>
                </div>

                <div class="border-b border-gray-200 px-5 py-5 dark:border-white/10">
                    <div class="h-72">
                        <canvas id="adminCommuneChart"></canvas>
                    </div>
                </div>

                <div class="space-y-3 px-5 py-5">
                    @forelse ($residencesByCommune as $commune)
                        <div class="space-y-1.5">
                            <div class="flex items-center justify-between gap-3 text-sm">
                                <span class="truncate font-medium text-gray-800 dark:text-gray-200">{{ $commune->commune ?? 'Non renseigné' }}</span>
                                <span class="font-semibold text-gray-950 dark:text-white">{{ number_format($commune->count, 0, ',', ' ') }}</span>
                            </div>
                            <div class="h-2 rounded-full bg-gray-100 dark:bg-gray-800">
                                <div class="h-2 rounded-full bg-slate-900 dark:bg-white" style="width: {{ round(($commune->count / $maxCommuneCount) * 100) }}%"></div>
                            </div>
                        </div>
                    @empty
                        <p class="rounded-2xl border border-dashed border-gray-300 px-4 py-8 text-center text-sm text-gray-500 dark:border-white/10 dark:text-gray-400">
                            Aucune résidence active à afficher.
                        </p>
                    @endforelse
                </div>
            </article>
        </section>

        <section class="rounded-2xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10">
            <div class="flex flex-col gap-2 border-b border-gray-200 px-5 py-4 dark:border-white/10 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-gray-950 dark:text-white">Top résidences</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Les annonces qui captent le plus d’attention actuellement.</p>
                </div>
                <div class="text-sm text-gray-500 dark:text-gray-400">Classement basé sur les vues cumulées</div>
            </div>

            <div class="grid gap-4 p-5 md:grid-cols-2 xl:grid-cols-3">
                @forelse ($topResidences as $index => $residence)
                    <article class="rounded-2xl border border-gray-200 bg-gray-50/70 p-4 transition hover:border-orange-300 hover:bg-orange-50/60 dark:border-white/10 dark:bg-gray-900/40 dark:hover:border-orange-500/30 dark:hover:bg-orange-500/5">
                        <div class="flex items-start justify-between gap-4">
                            <div class="flex items-center gap-3">
                                <span class="flex h-10 w-10 items-center justify-center rounded-2xl text-sm font-semibold {{ $index < 3 ? 'bg-orange-500 text-white' : 'bg-gray-200 text-gray-700 dark:bg-gray-800 dark:text-gray-300' }}">
                                    {{ $index + 1 }}
                                </span>
                                <div>
                                    <p class="line-clamp-1 font-semibold text-gray-950 dark:text-white">{{ $residence->name }}</p>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ $residence->commune ?: 'Commune non renseignée' }}</p>
                                </div>
                            </div>

                            @if ($residence->average_rating)
                                <span class="rounded-full bg-amber-100 px-2 py-1 text-xs font-semibold text-amber-700 dark:bg-amber-500/10 dark:text-amber-300">
                                    {{ number_format($residence->average_rating, 1) }} / 5
                                </span>
                            @endif
                        </div>

                        <dl class="mt-4 grid grid-cols-2 gap-3 text-sm">
                            <div class="rounded-xl bg-white px-3 py-2 ring-1 ring-gray-200 dark:bg-white/5 dark:ring-white/10">
                                <dt class="text-gray-500 dark:text-gray-400">Vues</dt>
                                <dd class="mt-1 font-semibold text-gray-950 dark:text-white">{{ number_format($residence->views_count, 0, ',', ' ') }}</dd>
                            </div>
                            <div class="rounded-xl bg-white px-3 py-2 ring-1 ring-gray-200 dark:bg-white/5 dark:ring-white/10">
                                <dt class="text-gray-500 dark:text-gray-400">Prix / nuit</dt>
                                <dd class="mt-1 font-semibold text-gray-950 dark:text-white">{{ number_format($residence->price_per_day, 0, ',', ' ') }} FCFA</dd>
                            </div>
                        </dl>

                        <div class="mt-4 border-t border-gray-200 pt-3 text-sm text-gray-500 dark:border-white/10 dark:text-gray-400">
                            Hôte: <span class="font-medium text-gray-800 dark:text-gray-200">{{ $residence->owner?->name ?? 'Non attribué' }}</span>
                        </div>
                    </article>
                @empty
                    <p class="col-span-full rounded-2xl border border-dashed border-gray-300 px-4 py-10 text-center text-sm text-gray-500 dark:border-white/10 dark:text-gray-400">
                        Aucune résidence active à classer pour le moment.
                    </p>
                @endforelse
            </div>
        </section>

        <script id="admin-statistics-data" type="application/json">@json($adminStatisticsData)</script>
        @vite('resources/js/pages/admin-statistics.js')
    </div>
</x-filament-panels::page>
