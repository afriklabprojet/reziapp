{{-- Composant de notification push - À inclure dans le layout --}}
<div x-data="pushNotifications()" x-init="init()" x-cloak>
    {{-- Banner de permission --}}
    <div x-show="showBanner && !isSubscribed && permission !== 'denied'"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 transform -translate-y-4"
        x-transition:enter-end="opacity-100 transform translate-y-0"
        class="fixed bottom-20 md:bottom-4 left-3 right-3 md:left-auto md:right-4 md:w-96 bg-white rounded-2xl shadow-xl border border-gray-100 p-4 z-50">
        <div class="flex items-start gap-3">
            <div class="shrink-0">
                <div class="w-10 h-10 bg-[#FFE7D1] rounded-full flex items-center justify-center">
                    <svg class="w-5 h-5 text-[#CC5A00]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                    </svg>
                </div>
            </div>
            <div class="flex-1 min-w-0">
                <h3 class="font-semibold text-gray-900">Activer les notifications</h3>
                <p class="text-sm text-gray-600 mt-1">Recevez des alertes pour les nouveaux messages, réservations et
                    promotions.</p>
                <div class="flex items-center gap-2 mt-3">
                    <button @click="subscribe()" :disabled="loading"
                        class="px-4 py-2.5 bg-[#CC5A00] text-white text-sm font-medium rounded-lg hover:bg-[#A34700] disabled:opacity-50 transition-colors min-h-11">
                        <span x-show="!loading">Activer</span>
                        <span x-show="loading" class="flex items-center">
                            <svg class="animate-spin w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                    stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                            Activation...
                        </span>
                    </button>
                    <button @click="dismissBanner()"
                        class="px-4 py-2.5 text-sm text-gray-600 hover:text-gray-800 min-h-11">
                        Plus tard
                    </button>
                </div>
            </div>
            <button @click="dismissBanner()"
                class="text-gray-400 hover:text-gray-600 p-2 -m-1 min-w-11 min-h-11 flex items-center justify-center">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    </div>

    {{-- Toast de succès --}}
    <div x-show="showSuccess" x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 transform translate-y-4"
        x-transition:enter-end="opacity-100 transform translate-y-0"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 transform translate-y-0"
        x-transition:leave-end="opacity-0 transform translate-y-4"
        class="fixed bottom-4 left-4 right-4 md:left-auto md:right-4 md:w-80 bg-green-600 text-white rounded-xl shadow-lg p-4 z-50">
        <div class="flex items-center gap-3">
            <svg class="w-6 h-6 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
            <p class="text-sm font-medium">Notifications activées avec succès !</p>
        </div>
    </div>

    {{-- Toast d'erreur --}}
    <div x-show="showError" x-transition
        class="fixed bottom-4 left-4 right-4 md:left-auto md:right-4 md:w-80 bg-red-600 text-white rounded-xl shadow-lg p-4 z-50">
        <div class="flex items-center gap-3">
            <svg class="w-6 h-6 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <p class="text-sm font-medium" x-text="errorMessage"></p>
        </div>
    </div>
</div>
