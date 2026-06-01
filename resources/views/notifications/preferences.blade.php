@extends('layouts.client', ['sidebarActive' => 'notifications'])

@section('title', 'Préférences de notifications - REZI')

@section('client-content')
    <div x-data="notificationPreferences(@js([
    'preferences' => [
        'messages_email' => $preferences->messages_email,
        'messages_push' => $preferences->messages_push,
        'messages_sms' => $preferences->messages_sms,
        'visits_email' => $preferences->visits_email,
        'visits_push' => $preferences->visits_push,
        'visits_sms' => $preferences->visits_sms,
        'payments_email' => $preferences->payments_email,
        'payments_push' => $preferences->payments_push,
        'payments_sms' => $preferences->payments_sms,
        'marketing_email' => $preferences->marketing_email,
        'marketing_push' => $preferences->marketing_push,
        'marketing_sms' => $preferences->marketing_sms,
        'security_email' => $preferences->security_email,
        'security_push' => $preferences->security_push,
        'security_sms' => $preferences->security_sms,
        'quiet_hours_start' => $preferences->quiet_hours_start?->format('H:i') ?? '',
        'quiet_hours_end' => $preferences->quiet_hours_end?->format('H:i') ?? '',
        'timezone' => $preferences->timezone ?? 'Africa/Abidjan',
    ],
    'updateUrl' => route('notifications.preferences.update'),
    'vapidUrl' => route('notifications.vapid'),
    'subscribeUrl' => route('notifications.push.subscribe'),
    'unsubscribeUrl' => route('notifications.push.unsubscribe'),
    'csrfToken' => csrf_token(),
]))">
        {{-- En-tête --}}
        <div class="mb-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Préférences de notifications</h1>
                    <p class="text-gray-500 text-sm mt-1">Gérez comment et quand vous recevez des notifications</p>
                </div>
                <a href="{{ route('notifications.index') }}"
                    class="text-[#F16A00] hover:text-[#CC5A00] text-sm font-medium flex items-center gap-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                    Retour aux notifications
                </a>
            </div>
        </div>

        {{-- Canaux rapides --}}
        <div class="grid grid-cols-3 gap-4 mb-6">
            <div class="bg-white rounded-xl border border-gray-100 p-4 text-center">
                <div class="w-12 h-12 mx-auto bg-blue-100 rounded-xl flex items-center justify-center mb-3">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                    </svg>
                </div>
                <h3 class="font-semibold text-gray-900 text-sm">Email</h3>
                <p class="text-xs text-gray-500 mt-1">{{ auth()->user()->email }}</p>
            </div>
            <div class="bg-white rounded-xl border border-gray-100 p-4 text-center">
                <div class="w-12 h-12 mx-auto bg-purple-100 rounded-xl flex items-center justify-center mb-3">
                    <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                    </svg>
                </div>
                <h3 class="font-semibold text-gray-900 text-sm">Push</h3>
                <p class="text-xs mt-1" :class="isPushSubscribed ? 'text-green-600' : 'text-gray-500'">
                    <span x-text="isPushSubscribed ? 'Activé' : 'Non activé'"></span>
                </p>
            </div>
            <div class="bg-white rounded-xl border border-gray-100 p-4 text-center">
                <div class="w-12 h-12 mx-auto bg-green-100 rounded-xl flex items-center justify-center mb-3">
                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                    </svg>
                </div>
                <h3 class="font-semibold text-gray-900 text-sm">SMS</h3>
                <p class="text-xs text-gray-500 mt-1">{{ auth()->user()->phone ?? 'Non configuré' }}</p>
            </div>
        </div>

        {{-- Catégories de notifications --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden mb-6">
            <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
                <h2 class="font-semibold text-gray-900 flex items-center gap-2">
                    <svg class="w-5 h-5 text-[#F16A00]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    Préférences par catégorie
                </h2>
                <p class="text-sm text-gray-500 mt-1">Activez ou désactivez les notifications par canal pour chaque
                    catégorie</p>
            </div>

            <div class="divide-y divide-gray-100">
                {{-- Messages --}}
                <div class="p-5 hover:bg-gray-50 transition">
                    <div class="flex items-start gap-4">
                        <div class="w-12 h-12 rounded-xl bg-blue-100 flex items-center justify-center shrink-0">
                            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                            </svg>
                        </div>
                        <div class="flex-1">
                            <h3 class="font-semibold text-gray-900">Messages</h3>
                            <p class="text-sm text-gray-500 mt-0.5">Nouveaux messages et réponses dans vos conversations</p>
                            <div class="flex flex-wrap gap-4 mt-4">
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" x-model="preferences.messages_email" @change="savePreferences()"
                                        class="w-5 h-5 text-[#F16A00] border-gray-300 rounded focus:ring-[#F16A00]">
                                    <span class="text-sm text-gray-700">Email</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" x-model="preferences.messages_push" @change="savePreferences()"
                                        class="w-5 h-5 text-[#F16A00] border-gray-300 rounded focus:ring-[#F16A00]">
                                    <span class="text-sm text-gray-700">Push</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" x-model="preferences.messages_sms" @change="savePreferences()"
                                        class="w-5 h-5 text-[#F16A00] border-gray-300 rounded focus:ring-[#F16A00]">
                                    <span class="text-sm text-gray-700">SMS</span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Réservations --}}
                <div class="p-5 hover:bg-gray-50 transition">
                    <div class="flex items-start gap-4">
                        <div class="w-12 h-12 rounded-xl bg-purple-100 flex items-center justify-center shrink-0">
                            <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <div class="flex-1">
                            <h3 class="font-semibold text-gray-900">Réservations</h3>
                            <p class="text-sm text-gray-500 mt-0.5">Demandes, confirmations et rappels de réservation</p>
                            <div class="flex flex-wrap gap-4 mt-4">
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" x-model="preferences.visits_email" @change="savePreferences()"
                                        class="w-5 h-5 text-[#F16A00] border-gray-300 rounded focus:ring-[#F16A00]">
                                    <span class="text-sm text-gray-700">Email</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" x-model="preferences.visits_push" @change="savePreferences()"
                                        class="w-5 h-5 text-[#F16A00] border-gray-300 rounded focus:ring-[#F16A00]">
                                    <span class="text-sm text-gray-700">Push</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" x-model="preferences.visits_sms" @change="savePreferences()"
                                        class="w-5 h-5 text-[#F16A00] border-gray-300 rounded focus:ring-[#F16A00]">
                                    <span class="text-sm text-gray-700">SMS</span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Paiements --}}
                <div class="p-5 hover:bg-gray-50 transition">
                    <div class="flex items-start gap-4">
                        <div class="w-12 h-12 rounded-xl bg-green-100 flex items-center justify-center shrink-0">
                            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                        </div>
                        <div class="flex-1">
                            <h3 class="font-semibold text-gray-900">Paiements</h3>
                            <p class="text-sm text-gray-500 mt-0.5">Confirmations de paiement, factures et remboursements
                            </p>
                            <div class="flex flex-wrap gap-4 mt-4">
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" x-model="preferences.payments_email"
                                        @change="savePreferences()"
                                        class="w-5 h-5 text-[#F16A00] border-gray-300 rounded focus:ring-[#F16A00]">
                                    <span class="text-sm text-gray-700">Email</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" x-model="preferences.payments_push"
                                        @change="savePreferences()"
                                        class="w-5 h-5 text-[#F16A00] border-gray-300 rounded focus:ring-[#F16A00]">
                                    <span class="text-sm text-gray-700">Push</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" x-model="preferences.payments_sms" @change="savePreferences()"
                                        class="w-5 h-5 text-[#F16A00] border-gray-300 rounded focus:ring-[#F16A00]">
                                    <span class="text-sm text-gray-700">SMS</span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Marketing --}}
                <div class="p-5 hover:bg-gray-50 transition">
                    <div class="flex items-start gap-4">
                        <div class="w-12 h-12 rounded-xl bg-[#FFE7D1] flex items-center justify-center shrink-0">
                            <svg class="w-6 h-6 text-[#CC5A00]" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z" />
                            </svg>
                        </div>
                        <div class="flex-1">
                            <h3 class="font-semibold text-gray-900">Marketing & Promotions</h3>
                            <p class="text-sm text-gray-500 mt-0.5">Offres spéciales, codes promo et nouveautés</p>
                            <div class="flex flex-wrap gap-4 mt-4">
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" x-model="preferences.marketing_email"
                                        @change="savePreferences()"
                                        class="w-5 h-5 text-[#F16A00] border-gray-300 rounded focus:ring-[#F16A00]">
                                    <span class="text-sm text-gray-700">Email</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" x-model="preferences.marketing_push"
                                        @change="savePreferences()"
                                        class="w-5 h-5 text-[#F16A00] border-gray-300 rounded focus:ring-[#F16A00]">
                                    <span class="text-sm text-gray-700">Push</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" x-model="preferences.marketing_sms"
                                        @change="savePreferences()"
                                        class="w-5 h-5 text-[#F16A00] border-gray-300 rounded focus:ring-[#F16A00]">
                                    <span class="text-sm text-gray-700">SMS</span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Sécurité --}}
                <div class="p-5 hover:bg-gray-50 transition">
                    <div class="flex items-start gap-4">
                        <div class="w-12 h-12 rounded-xl bg-red-100 flex items-center justify-center shrink-0">
                            <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                            </svg>
                        </div>
                        <div class="flex-1">
                            <h3 class="font-semibold text-gray-900">Sécurité</h3>
                            <p class="text-sm text-gray-500 mt-0.5">Alertes de connexion, changements de mot de passe et
                                activités suspectes</p>
                            <div class="flex flex-wrap gap-4 mt-4">
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" x-model="preferences.security_email"
                                        @change="savePreferences()"
                                        class="w-5 h-5 text-[#F16A00] border-gray-300 rounded focus:ring-[#F16A00]">
                                    <span class="text-sm text-gray-700">Email</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" x-model="preferences.security_push"
                                        @change="savePreferences()"
                                        class="w-5 h-5 text-[#F16A00] border-gray-300 rounded focus:ring-[#F16A00]">
                                    <span class="text-sm text-gray-700">Push</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" x-model="preferences.security_sms" @change="savePreferences()"
                                        class="w-5 h-5 text-[#F16A00] border-gray-300 rounded focus:ring-[#F16A00]">
                                    <span class="text-sm text-gray-700">SMS</span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Heures silencieuses et Push --}}
        <div class="grid lg:grid-cols-2 gap-6 mb-6">
            {{-- Heures silencieuses --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
                    <h2 class="font-semibold text-gray-900 flex items-center gap-2">
                        <svg class="w-5 h-5 text-[#F16A00]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                        </svg>
                        Heures silencieuses
                    </h2>
                </div>
                <div class="p-6">
                    <p class="text-sm text-gray-500 mb-4">Pendant ces heures, vous ne recevrez pas de notifications push ou
                        SMS (les emails seront toujours envoyés).</p>

                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">De</label>
                            <input type="time" x-model="preferences.quiet_hours_start" @change="savePreferences()"
                                class="w-full px-4 py-2.5 border border-gray-200 rounded-lg focus:ring-2 focus:ring-[#F16A00] focus:border-[#F16A00]">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">À</label>
                            <input type="time" x-model="preferences.quiet_hours_end" @change="savePreferences()"
                                class="w-full px-4 py-2.5 border border-gray-200 rounded-lg focus:ring-2 focus:ring-[#F16A00] focus:border-[#F16A00]">
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Fuseau horaire</label>
                        <select x-model="preferences.timezone" @change="savePreferences()"
                            class="w-full px-4 py-2.5 border border-gray-200 rounded-lg focus:ring-2 focus:ring-[#F16A00] focus:border-[#F16A00]">
                            <option value="Africa/Abidjan">Abidjan (GMT)</option>
                            <option value="Africa/Dakar">Dakar (GMT)</option>
                            <option value="Africa/Ouagadougou">Ouagadougou (GMT)</option>
                            <option value="Africa/Lagos">Lagos (WAT, GMT+1)</option>
                            <option value="Africa/Douala">Douala (WAT, GMT+1)</option>
                            <option value="Europe/Paris">Paris (CET, GMT+1)</option>
                            <option value="America/New_York">New York (EST)</option>
                        </select>
                    </div>

                    <div class="mt-4 p-3 bg-gray-50 rounded-lg">
                        <p class="text-xs text-gray-500 flex items-center gap-2">
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Exemple : 22:00 à 07:00 pour ne pas être dérangé la nuit
                        </p>
                    </div>
                </div>
            </div>

            {{-- Notifications Push --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
                    <h2 class="font-semibold text-gray-900 flex items-center gap-2">
                        <svg class="w-5 h-5 text-[#F16A00]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                        </svg>
                        Notifications Push
                    </h2>
                </div>
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <p class="text-sm text-gray-500">Recevez des notifications en temps réel sur cet appareil</p>
                        <template x-if="!isPushSupported">
                            <span class="px-3 py-1 text-xs bg-gray-100 text-gray-500 rounded-full">Non supporté</span>
                        </template>
                        <template x-if="isPushSupported && !isPushSubscribed">
                            <button @click="subscribeToPush()"
                                class="px-4 py-2 bg-[#F16A00] hover:bg-[#CC5A00] text-white text-sm font-medium rounded-lg transition">
                                Activer
                            </button>
                        </template>
                        <template x-if="isPushSupported && isPushSubscribed">
                            <span
                                class="px-3 py-1 text-xs bg-green-100 text-green-700 rounded-full font-medium flex items-center gap-1">
                                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                        clip-rule="evenodd" />
                                </svg>
                                Activé
                            </span>
                        </template>
                    </div>

                    @if ($pushSubscriptions->isNotEmpty())
                        <div class="space-y-3">
                            <h3 class="text-sm font-medium text-gray-700">Appareils enregistrés
                                ({{ $pushSubscriptions->count() }})</h3>
                            @foreach ($pushSubscriptions as $sub)
                                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg group">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 bg-gray-200 rounded-lg flex items-center justify-center">
                                            <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                            </svg>
                                        </div>
                                        <div>
                                            <p class="font-medium text-gray-900 text-sm">{{ $sub->device_name }}</p>
                                            <p class="text-xs text-gray-500">{{ $sub->created_at->diffForHumans() }}</p>
                                        </div>
                                    </div>
                                    <button @click="unsubscribeDevice('{{ $sub->endpoint }}')"
                                        class="text-gray-400 hover:text-red-600 opacity-0 group-hover:opacity-100 transition">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-6 bg-gray-50 rounded-lg">
                            <svg class="w-10 h-10 text-gray-300 mx-auto mb-2" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                            <p class="text-sm text-gray-500">Aucun appareil enregistré</p>
                            <p class="text-xs text-gray-400 mt-1">Activez les notifications push pour commencer</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Info supplémentaire --}}
        <div class="bg-blue-50 border border-blue-200 rounded-xl p-5">
            <div class="flex gap-4">
                <div class="shrink-0">
                    <svg class="w-6 h-6 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div>
                    <h3 class="font-medium text-blue-900">Conseil</h3>
                    <p class="text-sm text-blue-700 mt-1">
                        Pour une expérience optimale, nous recommandons d'activer les notifications par email pour les
                        catégories importantes comme les paiements et la sécurité. Les notifications push sont idéales pour
                        les messages en temps réel.
                    </p>
                </div>
            </div>
        </div>

        {{-- Indicateurs de sauvegarde --}}
        <div x-show="saving" x-cloak
            class="fixed bottom-4 right-4 bg-gray-900 text-white px-4 py-3 rounded-lg shadow-lg flex items-center gap-3 z-50">
            <svg class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4">
                </circle>
                <path class="opacity-75" fill="currentColor"
                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                </path>
            </svg>
            <span class="text-sm font-medium">Enregistrement...</span>
        </div>

        <div x-show="saved" x-cloak x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0"
            x-transition:leave-end="opacity-0 translate-y-2"
            class="fixed bottom-4 right-4 bg-green-600 text-white px-4 py-3 rounded-lg shadow-lg flex items-center gap-3 z-50">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
            <span class="text-sm font-medium">Préférences enregistrées</span>
        </div>
    </div>

    @push('scripts')
    @endpush
@endsection
