<div class="space-y-4">
    {{-- Header info --}}
    <div class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
        <span class="text-2xl">
            @switch($campaign->type)
                @case('email')
                    📧
                @break

                @case('sms')
                    📱
                @break

                @case('push')
                    🔔
                @break

                @case('in_app')
                    💬
                @break

                @default
                    📨
            @endswitch
        </span>
        <div>
            <p class="text-xs text-gray-500 dark:text-gray-400">
                {{ match ($campaign->type) {'email' => 'Email','sms' => 'SMS','push' => 'Notification Push','in_app' => 'In-App',default => $campaign->type} }}
            </p>
            <p class="font-semibold text-gray-900 dark:text-white">{{ $campaign->name }}</p>
        </div>
    </div>

    {{-- Subject (email only) --}}
    @if ($campaign->type === 'email' && $campaign->subject)
        <div class="p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
            <p class="text-xs text-blue-600 dark:text-blue-400 font-medium mb-1">Objet</p>
            <p class="text-sm text-blue-900 dark:text-blue-200 font-semibold">{{ $campaign->subject }}</p>
        </div>
    @endif

    {{-- Content --}}
    <div class="p-4 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg">
        <div class="prose prose-sm dark:prose-invert max-w-none">
            {!! $campaign->content !!}
        </div>
    </div>

    {{-- Meta info --}}
    <div class="grid grid-cols-2 gap-3 text-sm">
        <div class="p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
            <p class="text-xs text-gray-500 dark:text-gray-400">Audience</p>
            <p class="font-medium text-gray-900 dark:text-white">
                {{ match ($campaign->audience) {
                    'all' => 'Tous les utilisateurs',
                    'all_users' => 'Tous les utilisateurs',
                    'owners' => 'Propriétaires',
                    'tenants' => 'Locataires',
                    'clients' => 'Clients',
                    'active' => 'Utilisateurs actifs',
                    'inactive' => 'Utilisateurs inactifs',
                    'inactive_users' => 'Utilisateurs inactifs',
                    'new' => 'Nouveaux utilisateurs',
                    'new_users' => 'Nouveaux utilisateurs',
                    'high_value' => 'Utilisateurs actifs',
                    'verified' => 'Utilisateurs vérifiés',
                    'custom' => 'Personnalisé',
                    default => ucfirst($campaign->audience ?? 'Tous'),
                } }}
            </p>
        </div>
        <div class="p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
            <p class="text-xs text-gray-500 dark:text-gray-400">Statut</p>
            <p class="font-medium text-gray-900 dark:text-white">
                {{ match ($campaign->status) {
                    'draft' => 'Brouillon',
                    'scheduled' => 'Planifiée',
                    'sending' => 'En cours d\'envoi',
                    'sent' => 'Envoyée',
                    'failed' => 'Échouée',
                    default => ucfirst($campaign->status),
                } }}
            </p>
        </div>
    </div>

    {{-- Variables hint --}}
    <div class="p-3 bg-amber-50 dark:bg-amber-900/20 rounded-lg border border-amber-200 dark:border-amber-800">
        <p class="text-xs font-semibold text-amber-700 dark:text-amber-400 mb-1">💡 Variables de personnalisation</p>
        <p class="text-xs text-amber-600 dark:text-amber-500">
            Les variables <code class="bg-amber-100 dark:bg-amber-900 px-1 rounded">@{{ name }}</code>,
            <code class="bg-amber-100 dark:bg-amber-900 px-1 rounded">@{{ first_name }}</code>,
            <code class="bg-amber-100 dark:bg-amber-900 px-1 rounded">@{{ email }}</code>
            seront remplacées par les données de chaque destinataire lors de l'envoi.
        </p>
    </div>
</div>
