@extends('layouts.owner')

@section('title', 'Double authentification - ReziApp')

@section('owner-content')
    <div class="space-y-6">

        {{-- ============================== HEADER ============================== --}}
        <div>
            <a href="{{ route('verification.dashboard') }}"
                class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-900 transition-colors mb-3">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
                </svg>
                Centre de vérification
            </a>
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-gray-900 rounded-xl flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" stroke-width="2"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                    </svg>
                </div>
                <div>
                    <h1 class="text-xl sm:text-2xl font-bold text-gray-900">Double authentification</h1>
                    <p class="text-sm text-gray-500 mt-0.5">Protégez votre compte avec un code à usage unique</p>
                </div>
            </div>
        </div>

        {{-- ============================== FLASH MESSAGES ============================== --}}
        @if (session('success'))
            <div x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 6000)"
                class="flex items-center gap-3 px-4 py-3 bg-emerald-50 border border-emerald-200 rounded-xl">
                <svg class="w-5 h-5 text-emerald-600 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd"
                        d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                        clip-rule="evenodd" />
                </svg>
                <p class="text-sm font-medium text-emerald-800">{{ session('success') }}</p>
            </div>
        @endif
        @if (session('error'))
            <div x-data="{ show: true }" x-show="show" x-transition
                class="flex items-center gap-3 px-4 py-3 bg-red-50 border border-red-200 rounded-xl">
                <svg class="w-5 h-5 text-red-600 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd"
                        d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                        clip-rule="evenodd" />
                </svg>
                <p class="text-sm font-medium text-red-800">{{ session('error') }}</p>
            </div>
        @endif
        @if (session('info'))
            <div x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 5000)"
                class="flex items-center gap-3 px-4 py-3 bg-blue-50 border border-blue-200 rounded-xl">
                <svg class="w-5 h-5 text-blue-600 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd"
                        d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zm-1 4a1 1 0 00-1 1v3a1 1 0 102 0v-3a1 1 0 00-1-1z"
                        clip-rule="evenodd" />
                </svg>
                <p class="text-sm font-medium text-blue-800">{{ session('info') }}</p>
            </div>
        @endif

        {{-- ============================== STATUT ACTIVÉ ============================== --}}
        @if ($enabled)
            {{-- Status Card --}}
            <div class="bg-white rounded-2xl border border-emerald-200 shadow-sm overflow-hidden">
                <div class="p-5 sm:p-6">
                    <div class="flex items-start gap-4">
                        <div class="shrink-0 w-12 h-12 rounded-xl bg-emerald-50 flex items-center justify-center">
                            <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" stroke-width="2"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
                            </svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 flex-wrap">
                                <h2 class="text-lg font-bold text-gray-900">2FA activée</h2>
                                <span
                                    class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-700">
                                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd"
                                            d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                            clip-rule="evenodd" />
                                    </svg>
                                    Active
                                </span>
                            </div>
                            <p class="text-sm text-gray-500 mt-1">
                                Votre compte est protégé. Un code sera demandé à chaque connexion depuis un nouvel appareil.
                            </p>
                            @if ($user->last_security_check)
                                <p class="text-xs text-gray-400 mt-2">
                                    Dernière vérification : {{ $user->last_security_check->diffForHumans() }}
                                </p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- Codes de récupération --}}
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden" x-data="{ showRegen: false }">
                <div class="p-5 sm:p-6">
                    <div class="flex items-start gap-3">
                        <div class="shrink-0 w-10 h-10 rounded-xl bg-amber-50 flex items-center justify-center">
                            <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" stroke-width="2"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M15.75 5.25a3 3 0 013 3m3 0a6 6 0 01-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1121.75 8.25z" />
                            </svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <h3 class="text-sm font-semibold text-gray-900">Codes de récupération</h3>
                            <p class="text-xs text-gray-500 mt-0.5">
                                @if ($user->two_factor_recovery_codes)
                                    Vous avez des codes de récupération actifs. Ils vous permettent de vous connecter si
                                    vous perdez votre téléphone.
                                @else
                                    <span class="text-red-600 font-medium">Aucun code restant.</span> Régénérez-en pour
                                    éviter de perdre l'accès à votre compte.
                                @endif
                            </p>
                        </div>
                    </div>

                    <button @click="showRegen = !showRegen"
                        class="mt-3 w-full flex items-center justify-between px-3 py-2.5 bg-gray-50 rounded-xl text-sm font-medium text-gray-700 hover:bg-gray-100 transition-colors">
                        <span class="inline-flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182" />
                            </svg>
                            Régénérer les codes
                        </span>
                        <svg class="w-4 h-4 transition-transform" :class="showRegen ? 'rotate-180' : ''" fill="none"
                            stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                        </svg>
                    </button>

                    <div x-show="showRegen" x-collapse x-cloak class="mt-3">
                        <form action="{{ route('two-factor.recovery-codes.regenerate') }}" method="POST"
                            class="space-y-3" x-data="{ submitting: false }" @submit="submitting = true">
                            @csrf
                            <p class="text-xs text-amber-700 bg-amber-50 px-3 py-2 rounded-lg">
                                ⚠️ Les anciens codes seront invalidés. Vous recevrez 8 nouveaux codes.
                            </p>
                            <input type="password" name="password" required
                                class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 bg-gray-50 focus:bg-white focus:border-gray-900 focus:ring-1 focus:ring-gray-900 text-sm transition-all outline-none"
                                placeholder="Confirmez votre mot de passe">
                            <button type="submit" :disabled="submitting"
                                class="w-full px-4 py-2.5 bg-gray-900 text-white text-sm font-semibold rounded-xl hover:bg-gray-800 transition-colors disabled:opacity-50">
                                <span x-show="!submitting">Régénérer les codes</span>
                                <span x-show="submitting" x-cloak class="inline-flex items-center gap-2 justify-center">
                                    <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10"
                                            stroke="currentColor" stroke-width="4" />
                                        <path class="opacity-75" fill="currentColor"
                                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
                                    </svg>
                                    Génération…
                                </span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            {{-- Appareil de confiance --}}
            @if ($user->trusted_device_token && $user->trusted_device_expires_at && $user->trusted_device_expires_at->isFuture())
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                    <div class="p-5 sm:p-6">
                        <div class="flex items-start gap-3">
                            <div class="shrink-0 w-10 h-10 rounded-xl bg-blue-50 flex items-center justify-center">
                                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" stroke-width="2"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M9 17.25v1.007a3 3 0 01-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0115 18.257V17.25m6-12V15a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 15V5.25m18 0A2.25 2.25 0 0018.75 3H5.25A2.25 2.25 0 003 5.25m18 0V12a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 12V5.25" />
                                </svg>
                            </div>
                            <div class="flex-1 min-w-0">
                                <h3 class="text-sm font-semibold text-gray-900">Appareil de confiance</h3>
                                <p class="text-xs text-gray-500 mt-0.5">
                                    Cet appareil est reconnu jusqu'au
                                    <strong>{{ $user->trusted_device_expires_at->format('d/m/Y') }}</strong>.
                                    La 2FA ne sera pas demandée à chaque connexion depuis cet appareil.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Désactivation --}}
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden" x-data="{ showDisable: false }">
                <div class="p-5 sm:p-6">
                    <button @click="showDisable = !showDisable"
                        class="w-full flex items-center justify-between text-left">
                        <div class="flex items-center gap-3">
                            <div class="shrink-0 w-10 h-10 rounded-xl bg-red-50 flex items-center justify-center">
                                <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" stroke-width="2"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-sm font-semibold text-gray-900">Désactiver la 2FA</h3>
                                <p class="text-xs text-gray-500 mt-0.5">Réduira la sécurité de votre compte</p>
                            </div>
                        </div>
                        <svg class="w-4 h-4 text-gray-400 transition-transform" :class="showDisable ? 'rotate-180' : ''"
                            fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                        </svg>
                    </button>

                    <div x-show="showDisable" x-collapse x-cloak class="mt-4 pt-4 border-t border-gray-100">
                        <form action="{{ route('two-factor.disable') }}" method="POST" class="space-y-3"
                            x-data="{ submitting: false }" @submit="submitting = true">
                            @csrf
                            @method('DELETE')

                            <div>
                                <label for="password" class="block text-xs font-medium text-gray-600 mb-1">Mot de
                                    passe</label>
                                <input type="password" name="password" id="password" required
                                    class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 bg-gray-50 focus:bg-white focus:border-gray-900 focus:ring-1 focus:ring-gray-900 text-sm transition-all outline-none"
                                    placeholder="Votre mot de passe actuel">
                            </div>

                            <div>
                                <label for="disable_code" class="block text-xs font-medium text-gray-600 mb-1">Code 2FA
                                    actuel</label>
                                <input type="text" name="code" id="disable_code" required maxlength="6"
                                    pattern="[0-9]{6}" inputmode="numeric" autocomplete="one-time-code"
                                    class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 bg-gray-50 focus:bg-white focus:border-gray-900 focus:ring-1 focus:ring-gray-900 transition-all outline-none font-mono tracking-[0.5em] text-center text-lg"
                                    placeholder="000000">
                            </div>

                            <button type="submit" :disabled="submitting"
                                class="w-full px-4 py-2.5 bg-red-600 text-white text-sm font-semibold rounded-xl hover:bg-red-700 transition-colors disabled:opacity-50">
                                <span x-show="!submitting" class="inline-flex items-center gap-2 justify-center">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                                    </svg>
                                    Désactiver la 2FA
                                </span>
                                <span x-show="submitting" x-cloak class="inline-flex items-center gap-2 justify-center">
                                    <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10"
                                            stroke="currentColor" stroke-width="4" />
                                        <path class="opacity-75" fill="currentColor"
                                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
                                    </svg>
                                    Vérification…
                                </span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        @elseif(isset($showSetup) && $showSetup)
            {{-- ============================== ÉTAPE 2 : QR CODE + VÉRIFICATION ============================== --}}
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                <div class="p-5 sm:p-6">
                    {{-- Progress bar --}}
                    <div class="flex items-center gap-2 mb-6">
                        <div class="flex-1 h-1 rounded-full bg-gray-900"></div>
                        <div class="flex-1 h-1 rounded-full bg-gray-900"></div>
                        <div class="flex-1 h-1 rounded-full bg-gray-200"></div>
                    </div>

                    <div class="text-center mb-6">
                        <div class="w-14 h-14 rounded-full bg-[#FFF4EB] flex items-center justify-center mx-auto mb-3">
                            <svg class="w-7 h-7 text-[#CC5A00]" fill="none" stroke="currentColor" stroke-width="2"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M10.5 1.5H8.25A2.25 2.25 0 006 3.75v16.5a2.25 2.25 0 002.25 2.25h7.5A2.25 2.25 0 0018 20.25V3.75a2.25 2.25 0 00-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 18.75h3" />
                            </svg>
                        </div>
                        <h2 class="text-lg font-bold text-gray-900">Scannez le QR code</h2>
                        <p class="text-sm text-gray-500 mt-1">
                            Ouvrez votre application d'authentification et scannez le code ci-dessous.
                        </p>
                    </div>

                    {{-- QR Code --}}
                    <div class="flex justify-center mb-4">
                        <div id="qrcode" class="bg-white p-4 rounded-xl border border-gray-200 inline-block shadow-sm">
                        </div>
                    </div>

                    {{-- Clé manuelle avec copy --}}
                    <div class="mb-6" x-data="{ showKey: false, copied: false }">
                        <button @click="showKey = !showKey"
                            class="text-xs text-gray-500 hover:text-gray-900 transition-colors mx-auto block">
                            <span x-show="!showKey">Impossible de scanner ? Entrer la clé manuellement ↓</span>
                            <span x-show="showKey" x-cloak>Masquer la clé ↑</span>
                        </button>
                        <div x-show="showKey" x-collapse x-cloak class="mt-3">
                            <div class="flex items-center justify-center gap-2">
                                <code
                                    class="px-3 py-2 bg-gray-100 rounded-lg text-sm font-mono font-bold text-gray-900 tracking-wider select-all break-all">{{ $secret }}</code>
                                <button type="button"
                                    @click="navigator.clipboard.writeText('{{ $secret }}'); copied = true; setTimeout(() => copied = false, 2000)"
                                    class="shrink-0 p-2 rounded-lg border border-gray-200 hover:bg-gray-50 transition-colors">
                                    <svg x-show="!copied" class="w-4 h-4 text-gray-500" fill="none"
                                        stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M15.666 3.888A2.25 2.25 0 0013.5 2.25h-3c-1.03 0-1.9.693-2.166 1.638m7.332 0c.055.194.084.4.084.612v0a.75.75 0 01-.75.75H9.75a.75.75 0 01-.75-.75v0c0-.212.03-.418.084-.612m7.332 0c.646.049 1.288.11 1.927.184 1.1.128 1.907 1.077 1.907 2.185V19.5a2.25 2.25 0 01-2.25 2.25H6.75A2.25 2.25 0 014.5 19.5V6.257c0-1.108.806-2.057 1.907-2.185a48.208 48.208 0 011.927-.184" />
                                    </svg>
                                    <svg x-show="copied" x-cloak class="w-4 h-4 text-emerald-500" fill="none"
                                        stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>

                    {{-- Vérification du code avec split inputs --}}
                    <form action="{{ route('two-factor.enable') }}" method="POST" id="enableForm" class="space-y-4"
                        x-data="setupOtpInput()" @submit="submitting = true">
                        @csrf

                        <div>
                            <label class="block text-sm font-semibold text-gray-900 mb-3 text-center">
                                Entrez le code à 6 chiffres
                            </label>
                            <div class="flex justify-center gap-2 sm:gap-3">
                                <template x-for="(digit, index) in digits" :key="index">
                                    <input type="text" maxlength="1" inputmode="numeric" :id="'setup-otp-' + index"
                                        x-model="digits[index]" @input="handleInput($event, index)"
                                        @keydown="handleKeydown($event, index)" @paste.prevent="handlePaste($event)"
                                        @focus="$event.target.select()"
                                        class="w-11 h-13 sm:w-12 sm:h-14 rounded-xl border-2 text-center text-xl font-bold font-mono transition-all duration-200 outline-none"
                                        :class="digits[index] ?
                                            'border-gray-900 bg-gray-50 text-gray-900 shadow-sm' :
                                            'border-gray-200 bg-gray-50 text-gray-400 focus:border-gray-900 focus:bg-white focus:shadow-sm'">
                                </template>
                            </div>
                            <input type="hidden" name="code" :value="fullCode">
                        </div>

                        <button type="submit" :disabled="submitting || fullCode.length < 6"
                            class="w-full px-4 py-3.5 bg-gray-900 text-white text-sm font-semibold rounded-xl hover:bg-gray-800 transition-all disabled:opacity-40 disabled:cursor-not-allowed">
                            <span x-show="!submitting" class="inline-flex items-center gap-2 justify-center">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
                                </svg>
                                Activer la double authentification
                            </span>
                            <span x-show="submitting" x-cloak class="inline-flex items-center gap-2 justify-center">
                                <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10"
                                        stroke="currentColor" stroke-width="4" />
                                    <path class="opacity-75" fill="currentColor"
                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
                                </svg>
                                Vérification…
                            </span>
                        </button>
                    </form>
                </div>
            </div>

            {{-- QR Code Generation Script --}}
            <script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    new QRCode(document.getElementById('qrcode'), {
                        text: @json($qrCodeUrl),
                        width: 200,
                        height: 200,
                        colorDark: '#111827',
                        colorLight: '#ffffff',
                        correctLevel: QRCode.CorrectLevel.H
                    });
                });

                // Split OTP Input for setup page
                function setupOtpInput() {
                    return {
                        digits: ['', '', '', '', '', ''],
                        submitting: false,
                        get fullCode() {
                            return this.digits.join('');
                        },
                        handleInput(e, index) {
                            const val = e.target.value.replace(/[^0-9]/g, '');
                            this.digits[index] = val.slice(-1);
                            if (val && index < 5) {
                                document.getElementById('setup-otp-' + (index + 1)).focus();
                            }
                            if (this.fullCode.length === 6) {
                                this.submitting = true;
                                this.$nextTick(() => document.getElementById('enableForm').submit());
                            }
                        },
                        handleKeydown(e, index) {
                            if (e.key === 'Backspace') {
                                if (!this.digits[index] && index > 0) {
                                    e.preventDefault();
                                    this.digits[index - 1] = '';
                                    document.getElementById('setup-otp-' + (index - 1)).focus();
                                } else {
                                    this.digits[index] = '';
                                }
                            }
                            if (e.key === 'ArrowLeft' && index > 0) {
                                e.preventDefault();
                                document.getElementById('setup-otp-' + (index - 1)).focus();
                            }
                            if (e.key === 'ArrowRight' && index < 5) {
                                e.preventDefault();
                                document.getElementById('setup-otp-' + (index + 1)).focus();
                            }
                        },
                        handlePaste(e) {
                            const paste = e.clipboardData.getData('text').replace(/[^0-9]/g, '').slice(0, 6);
                            for (let i = 0; i < 6; i++) {
                                this.digits[i] = paste[i] || '';
                            }
                            const lastIndex = Math.min(paste.length, 5);
                            document.getElementById('setup-otp-' + lastIndex).focus();
                            if (paste.length === 6) {
                                this.submitting = true;
                                this.$nextTick(() => document.getElementById('enableForm').submit());
                            }
                        }
                    }
                }
            </script>
        @else
            {{-- ============================== ÉTAPE 1 : PAS ENCORE ACTIVÉE ============================== --}}
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                <div class="p-5 sm:p-6">
                    {{-- Progress bar --}}
                    <div class="flex items-center gap-2 mb-6">
                        <div class="flex-1 h-1 rounded-full bg-gray-900"></div>
                        <div class="flex-1 h-1 rounded-full bg-gray-200"></div>
                        <div class="flex-1 h-1 rounded-full bg-gray-200"></div>
                    </div>

                    <div class="text-center py-4">
                        <div class="w-20 h-20 rounded-full bg-gray-50 flex items-center justify-center mx-auto mb-5">
                            <svg class="w-10 h-10 text-gray-300" fill="none" stroke="currentColor" stroke-width="1.5"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                            </svg>
                        </div>
                        <h2 class="text-xl font-bold text-gray-900">Renforcez la sécurité de votre compte</h2>
                        <p class="text-sm text-gray-500 mt-2 max-w-md mx-auto">
                            La double authentification ajoute une couche de protection supplémentaire.
                            Un code temporaire sera demandé à chaque connexion depuis un nouvel appareil.
                        </p>
                    </div>

                    {{-- Comment ça marche (style Airbnb stepper) --}}
                    <div class="space-y-3 mb-6">
                        <div class="flex items-start gap-3 p-3 rounded-xl hover:bg-gray-50 transition-colors">
                            <div class="shrink-0 w-8 h-8 rounded-full bg-gray-900 flex items-center justify-center">
                                <span class="text-xs font-bold text-white">1</span>
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-gray-900">Installez une application</p>
                                <p class="text-xs text-gray-500 mt-0.5">Google Authenticator, Authy ou Microsoft
                                    Authenticator</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-3 p-3 rounded-xl hover:bg-gray-50 transition-colors">
                            <div class="shrink-0 w-8 h-8 rounded-full bg-gray-900 flex items-center justify-center">
                                <span class="text-xs font-bold text-white">2</span>
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-gray-900">Scannez le QR code</p>
                                <p class="text-xs text-gray-500 mt-0.5">Liez votre compte ReziApp à l'application</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-3 p-3 rounded-xl hover:bg-gray-50 transition-colors">
                            <div class="shrink-0 w-8 h-8 rounded-full bg-gray-900 flex items-center justify-center">
                                <span class="text-xs font-bold text-white">3</span>
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-gray-900">Sauvegardez vos codes</p>
                                <p class="text-xs text-gray-500 mt-0.5">8 codes de récupération en cas de perte de votre
                                    téléphone</p>
                            </div>
                        </div>
                    </div>

                    <form action="{{ route('two-factor.generate') }}" method="POST">
                        @csrf
                        <button type="submit"
                            class="w-full px-4 py-3.5 bg-gray-900 text-white text-sm font-bold rounded-xl hover:bg-gray-800 transition-colors">
                            <span class="inline-flex items-center gap-2 justify-center">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                                </svg>
                                Commencer la configuration
                            </span>
                        </button>
                    </form>
                </div>
            </div>

            {{-- Apps recommandées --}}
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                <div class="p-5 sm:p-6">
                    <h3 class="text-sm font-semibold text-gray-900 mb-3">Applications recommandées</h3>
                    <div class="space-y-2">
                        <a href="https://apps.apple.com/app/google-authenticator/id388497605" target="_blank"
                            rel="noopener"
                            class="flex items-center gap-3 px-4 py-3 bg-gray-50 rounded-xl hover:bg-gray-100 transition-colors group">
                            <div class="shrink-0 w-10 h-10 rounded-xl bg-blue-100 flex items-center justify-center">
                                <svg class="w-5 h-5 text-blue-700" fill="none" stroke="currentColor" stroke-width="2"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M10.5 1.5H8.25A2.25 2.25 0 006 3.75v16.5a2.25 2.25 0 002.25 2.25h7.5A2.25 2.25 0 0018 20.25V3.75a2.25 2.25 0 00-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 18.75h3" />
                                </svg>
                            </div>
                            <div class="flex-1">
                                <p class="text-sm font-semibold text-gray-900">Google Authenticator</p>
                                <p class="text-xs text-gray-500">Gratuit · iOS & Android</p>
                            </div>
                            <svg class="w-4 h-4 text-gray-400 group-hover:text-gray-600 transition-colors" fill="none"
                                stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
                            </svg>
                        </a>
                        <a href="https://authy.com/download/" target="_blank" rel="noopener"
                            class="flex items-center gap-3 px-4 py-3 bg-gray-50 rounded-xl hover:bg-gray-100 transition-colors group">
                            <div class="shrink-0 w-10 h-10 rounded-xl bg-red-100 flex items-center justify-center">
                                <svg class="w-5 h-5 text-red-700" fill="none" stroke="currentColor" stroke-width="2"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
                                </svg>
                            </div>
                            <div class="flex-1">
                                <p class="text-sm font-semibold text-gray-900">Authy</p>
                                <p class="text-xs text-gray-500">Gratuit · Sauvegarde cloud · iOS & Android</p>
                            </div>
                            <svg class="w-4 h-4 text-gray-400 group-hover:text-gray-600 transition-colors" fill="none"
                                stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
                            </svg>
                        </a>
                    </div>
                </div>
            </div>
        @endif
    </div>
@endsection
