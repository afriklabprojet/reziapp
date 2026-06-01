<x-filament-panels::page>
    <div class="space-y-6">
        <x-filament.admin.hero
            eyebrow="Sécurité & conformité"
            title="Tableau de bord sécurité"
            subtitle="Centralisez les alertes urgentes, la vérification d’identité, les fraudes et les actions de modération sensibles."
            icon="heroicon-o-shield-exclamation"
            tone="red"
        />

        {{-- Alertes Urgentes --}}
        @if ($activeAlerts->count() > 0)
            <div class="rounded-2xl border border-red-200 bg-red-50 p-4 shadow-sm dark:border-red-500/20 dark:bg-red-500/10">
                <div class="flex items-center gap-2 mb-3">
                    <x-heroicon-o-exclamation-triangle class="w-6 h-6 text-red-600" />
                    <h3 class="text-lg font-bold text-red-800">🚨 Alertes urgentes actives ({{ $activeAlerts->count() }})
                    </h3>
                </div>
                <div class="space-y-2">
                    @foreach ($activeAlerts as $alert)
                        <div class="flex items-center justify-between p-3 bg-white rounded-lg border border-red-100">
                            <div class="flex items-center gap-3">
                                <span class="inline-flex items-center justify-center w-8 h-8 bg-red-100 rounded-full">
                                    <x-heroicon-o-exclamation-circle class="w-5 h-5 text-red-600" />
                                </span>
                                <div>
                                    <p class="font-medium text-gray-900">
                                        {{ $alert->user?->name ?? 'Utilisateur inconnu' }}</p>
                                    <p class="text-sm text-gray-500">{{ $alert->type ?? 'Alerte' }} •
                                        {{ $alert->created_at->diffForHumans() }}</p>
                                </div>
                            </div>
                            <span
                                class="px-2.5 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">Active</span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Statistiques --}}
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <x-filament.admin.metric-card label="Identités en attente" :value="$stats['identity_pending']" icon="heroicon-o-identification" accent="amber" :meta="$stats['identity_approved'].' approuvées'" :note="$stats['identity_total'].' demandes au total'" />
            <x-filament.admin.metric-card label="Fraudes en attente" :value="$stats['fraud_pending']" icon="heroicon-o-flag" accent="rose" :meta="$stats['fraud_confirmed'].' confirmées'" :note="$stats['fraud_total'].' signalements au total'" />
            <x-filament.admin.metric-card label="Alertes actives" :value="$stats['alerts_active']" icon="heroicon-o-bell-alert" :accent="$stats['alerts_active'] > 0 ? 'rose' : 'emerald'" :meta="$stats['alerts_total'].' alertes au total'" />
            <x-filament.admin.metric-card label="Utilisateurs blacklistés" :value="$stats['blacklist_count']" icon="heroicon-o-no-symbol" accent="slate" note="Comptes bannis actifs" />
        </div>

        {{-- Raccourcis vers les ressources Filament --}}
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <a href="{{ route('filament.admin.resources.identity-verifications.index') }}"
                class="block rounded-2xl bg-white p-4 text-center shadow-sm ring-1 ring-gray-950/5 transition hover:-translate-y-0.5 hover:shadow-md dark:bg-white/5 dark:ring-white/10">
                <x-heroicon-o-identification class="w-8 h-8 mx-auto text-amber-500 mb-2" />
                <p class="font-semibold text-gray-900">Vérifications d'identité</p>
                <p class="text-sm text-gray-500">Gérer les demandes</p>
            </a>
            <a href="{{ route('filament.admin.resources.fraud-reports.index') }}"
                class="block rounded-2xl bg-white p-4 text-center shadow-sm ring-1 ring-gray-950/5 transition hover:-translate-y-0.5 hover:shadow-md dark:bg-white/5 dark:ring-white/10">
                <x-heroicon-o-flag class="w-8 h-8 mx-auto text-red-500 mb-2" />
                <p class="font-semibold text-gray-900">Signalements de fraude</p>
                <p class="text-sm text-gray-500">Gérer les signalements</p>
            </a>
            <a href="{{ route('filament.admin.resources.support-tickets.index') }}"
                class="block rounded-2xl bg-white p-4 text-center shadow-sm ring-1 ring-gray-950/5 transition hover:-translate-y-0.5 hover:shadow-md dark:bg-white/5 dark:ring-white/10">
                <x-heroicon-o-ticket class="w-8 h-8 mx-auto text-blue-500 mb-2" />
                <p class="font-semibold text-gray-900">Tickets support</p>
                <p class="text-sm text-gray-500">Gérer les demandes</p>
            </a>
            <a href="{{ route('filament.admin.resources.disputes.index') }}"
                class="block rounded-2xl bg-white p-4 text-center shadow-sm ring-1 ring-gray-950/5 transition hover:-translate-y-0.5 hover:shadow-md dark:bg-white/5 dark:ring-white/10">
                <x-heroicon-o-scale class="w-8 h-8 mx-auto text-purple-500 mb-2" />
                <p class="font-semibold text-gray-900">Litiges</p>
                <p class="text-sm text-gray-500">Résoudre les litiges</p>
            </a>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {{-- Vérifications d'identité récentes --}}
            <div class="bg-white rounded-xl shadow-sm border">
                <div class="flex items-center justify-between p-5 border-b">
                    <h3 class="text-lg font-semibold text-gray-900">Vérifications d'identité en attente</h3>
                    <a href="{{ route('filament.admin.resources.identity-verifications.index') }}"
                        class="text-sm text-primary-600 hover:text-primary-700 font-medium">
                        Voir tout →
                    </a>
                </div>
                <div class="divide-y">
                    @forelse($recentIdentityRequests as $verification)
                        <div class="flex items-center justify-between p-4 hover:bg-gray-50">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-amber-100 rounded-full flex items-center justify-center">
                                    <span
                                        class="text-amber-800 font-semibold text-sm">{{ strtoupper(substr($verification->user?->name ?? '?', 0, 2)) }}</span>
                                </div>
                                <div>
                                    <p class="font-medium text-gray-900">{{ $verification->user?->name ?? 'Inconnu' }}
                                    </p>
                                    <p class="text-sm text-gray-500">{{ $verification->document_type ?? 'Document' }} •
                                        {{ $verification->created_at->diffForHumans() }}</p>
                                </div>
                            </div>
                            <span class="px-2.5 py-1 text-xs font-semibold rounded-full bg-amber-100 text-amber-800">
                                {{ ucfirst($verification->status) }}
                            </span>
                        </div>
                    @empty
                        <div class="p-8 text-center text-gray-400">
                            <x-heroicon-o-check-circle class="w-12 h-12 mx-auto mb-2 text-green-300" />
                            <p>Aucune vérification en attente</p>
                        </div>
                    @endforelse
                </div>
            </div>

            {{-- Signalements de fraude récents --}}
            <div class="bg-white rounded-xl shadow-sm border">
                <div class="flex items-center justify-between p-5 border-b">
                    <h3 class="text-lg font-semibold text-gray-900">Signalements de fraude</h3>
                    <a href="{{ route('filament.admin.resources.fraud-reports.index') }}"
                        class="text-sm text-primary-600 hover:text-primary-700 font-medium">
                        Voir tout →
                    </a>
                </div>
                <div class="divide-y">
                    @forelse($recentFraudReports as $report)
                        <div class="flex items-center justify-between p-4 hover:bg-gray-50">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-red-100 rounded-full flex items-center justify-center">
                                    <x-heroicon-o-flag class="w-5 h-5 text-red-600" />
                                </div>
                                <div>
                                    <p class="font-medium text-gray-900">
                                        {{ $report->targetUser?->name ?? 'Utilisateur inconnu' }}</p>
                                    <p class="text-sm text-gray-500">
                                        Signalé par {{ $report->reporter?->name ?? 'Système' }} •
                                        {{ $report->created_at->diffForHumans() }}
                                    </p>
                                </div>
                            </div>
                            <span
                                class="px-2.5 py-1 text-xs font-semibold rounded-full
                                {{ $report->status === 'pending' ? 'bg-amber-100 text-amber-800' : '' }}
                                {{ $report->status === 'investigating' ? 'bg-blue-100 text-blue-800' : '' }}
                                {{ $report->status === 'confirmed' ? 'bg-red-100 text-red-800' : '' }}
                                {{ $report->status === 'dismissed' ? 'bg-gray-100 text-gray-800' : '' }}">
                                {{ ucfirst($report->status) }}
                            </span>
                        </div>
                    @empty
                        <div class="p-8 text-center text-gray-400">
                            <x-heroicon-o-shield-check class="w-12 h-12 mx-auto mb-2 text-green-300" />
                            <p>Aucun signalement en attente</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Blacklist récente --}}
        @if ($recentBlacklist->count() > 0)
            <div class="bg-white rounded-xl shadow-sm border">
                <div class="p-5 border-b">
                    <h3 class="text-lg font-semibold text-gray-900">Utilisateurs blacklistés récemment</h3>
                </div>
                <div class="divide-y">
                    @foreach ($recentBlacklist as $entry)
                        <div class="flex items-center justify-between p-4 hover:bg-gray-50">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-gray-200 rounded-full flex items-center justify-center">
                                    <x-heroicon-o-no-symbol class="w-5 h-5 text-gray-600" />
                                </div>
                                <div>
                                    <p class="font-medium text-gray-900">
                                        {{ $entry->user?->name ?? ($entry->value ?? 'Inconnu') }}</p>
                                    <p class="text-sm text-gray-500">{{ $entry->reason ?? 'Aucune raison' }} •
                                        {{ $entry->created_at->diffForHumans() }}</p>
                                </div>
                            </div>
                            <span
                                class="px-2.5 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">Banni</span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</x-filament-panels::page>
