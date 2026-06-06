@extends('layouts.app')

@section('title', 'Vérification 2FA - ReziApp')

@section('content')
    <div class="min-h-screen flex items-center justify-center px-4 py-12 bg-gray-50">
        <div class="w-full max-w-sm" x-data="{ mode: 'totp' }">

            {{-- Logo --}}
            <div class="text-center mb-8">
                <a href="{{ url('/') }}" class="inline-block">
                    <img src="{{ asset('images/logo.svg') }}" alt="ReziApp" class="h-8 mx-auto"
                        onerror="this.outerHTML='<span class=\'text-2xl font-black text-gray-900\'>ReziApp</span>'">
                </a>
            </div>

            {{-- Card --}}
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                <div class="p-6">

                    {{-- Icône animée --}}
                    <div class="text-center mb-5">
                        <div
                            class="w-16 h-16 rounded-full bg-[#FFF4EB] flex items-center justify-center mx-auto mb-4 animate-pulse-slow">
                            <svg class="w-8 h-8 text-[#CC5A00]" fill="none" stroke="currentColor" stroke-width="1.5"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
                            </svg>
                        </div>
                        <h1 class="text-xl font-bold text-gray-900">Vérification en deux étapes</h1>
                        <p class="text-sm text-gray-500 mt-1.5" x-show="mode === 'totp'">
                            Entrez le code à 6 chiffres de votre application d'authentification.
                        </p>
                        <p class="text-sm text-gray-500 mt-1.5" x-show="mode === 'recovery'" x-cloak>
                            Entrez un de vos codes de récupération à usage unique.
                        </p>
                    </div>

                    {{-- Flash Messages --}}
                    @if (session('error'))
                        <div x-data="{ show: true }" x-show="show" x-transition
                            class="flex items-center gap-2 px-3 py-2.5 mb-4 bg-red-50 border border-red-200 rounded-xl">
                            <svg class="w-4 h-4 text-red-500 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                                    clip-rule="evenodd" />
                            </svg>
                            <p class="text-sm text-red-700">{{ session('error') }}</p>
                        </div>
                    @endif

                    {{-- ============================== MODE TOTP ============================== --}}
                    <div x-show="mode === 'totp'" x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0 translate-y-2"
                        x-transition:enter-end="opacity-100 translate-y-0">

                        <form action="{{ route('two-factor.verify') }}" method="POST" id="totpForm"
                            x-data="otpInput()" @submit="submitting = true">
                            @csrf

                            {{-- 6 inputs séparés style Airbnb --}}
                            <div class="flex justify-center gap-2 sm:gap-3 mb-4">
                                <template x-for="(digit, index) in digits" :key="index">
                                    <input type="text" maxlength="1" inputmode="numeric" :id="'otp-' + index"
                                        x-model="digits[index]" @input="handleInput($event, index)"
                                        @keydown="handleKeydown($event, index)" @paste.prevent="handlePaste($event)"
                                        @focus="$event.target.select()"
                                        class="w-11 h-13 sm:w-12 sm:h-14 rounded-xl border-2 text-center text-xl font-bold font-mono transition-all duration-200 outline-none"
                                        :class="digits[index] ?
                                            'border-gray-900 bg-gray-50 text-gray-900 shadow-sm' :
                                            'border-gray-200 bg-gray-50 text-gray-400 focus:border-gray-900 focus:bg-white focus:shadow-sm'">
                                </template>
                            </div>

                            {{-- Input caché pour le code complet --}}
                            <input type="hidden" name="code" :value="fullCode">

                            {{-- Se souvenir de cet appareil --}}
                            <label
                                class="flex items-center gap-2.5 px-3 py-2.5 mb-4 rounded-xl bg-gray-50 cursor-pointer hover:bg-gray-100 transition-colors group">
                                <input type="checkbox" name="remember_device" value="1"
                                    class="w-4 h-4 rounded border-gray-300 text-gray-900 focus:ring-gray-900 focus:ring-offset-0">
                                <span class="text-sm text-gray-600 group-hover:text-gray-900 transition-colors">
                                    Se souvenir de cet appareil pendant 30 jours
                                </span>
                            </label>

                            {{-- Bouton vérifier --}}
                            <button type="submit" :disabled="submitting || fullCode.length < 6"
                                class="w-full px-4 py-3.5 bg-gray-900 text-white text-sm font-semibold rounded-xl hover:bg-gray-800 transition-all disabled:opacity-40 disabled:cursor-not-allowed">
                                <span x-show="!submitting" class="inline-flex items-center gap-2 justify-center">
                                    Vérifier
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

                        {{-- Compteur TOTP --}}
                        <div class="mt-3 text-center" x-data="totpTimer()">
                            <div class="inline-flex items-center gap-1.5 text-xs text-gray-400">
                                <div class="relative w-4 h-4">
                                    <svg class="w-4 h-4 -rotate-90" viewBox="0 0 20 20">
                                        <circle cx="10" cy="10" r="8" fill="none" stroke="currentColor"
                                            stroke-width="2" class="text-gray-200" />
                                        <circle cx="10" cy="10" r="8" fill="none" stroke="currentColor"
                                            stroke-width="2" :stroke-dasharray="circumference"
                                            :stroke-dashoffset="circumference - (progress * circumference)"
                                            stroke-linecap="round" :class="seconds <= 5 ? 'text-red-400' : 'text-gray-400'"
                                            class="transition-all duration-1000" />
                                    </svg>
                                </div>
                                <span :class="seconds <= 5 ? 'text-red-400 font-medium' : ''"
                                    x-text="'Nouveau code dans ' + seconds + 's'"></span>
                            </div>
                        </div>
                    </div>

                    {{-- ============================== MODE RECOVERY ============================== --}}
                    <div x-show="mode === 'recovery'" x-cloak x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0 translate-y-2"
                        x-transition:enter-end="opacity-100 translate-y-0">

                        <form action="{{ route('two-factor.verify-recovery') }}" method="POST" x-data="{ submitting: false, code: '' }"
                            @submit="submitting = true">
                            @csrf

                            <div class="mb-4">
                                <input type="text" name="recovery_code" x-model="code"
                                    @input="code = code.toUpperCase().replace(/[^A-Z0-9-]/g, '')" required maxlength="11"
                                    autocomplete="off" autofocus
                                    class="w-full px-4 py-3.5 rounded-xl border-2 border-gray-200 bg-gray-50 focus:bg-white focus:border-gray-900 focus:ring-0 text-base transition-all outline-none font-mono tracking-wider text-center uppercase"
                                    placeholder="XXXXX-XXXXX">
                            </div>

                            <button type="submit" :disabled="submitting || code.length < 10"
                                class="w-full px-4 py-3.5 bg-gray-900 text-white text-sm font-semibold rounded-xl hover:bg-gray-800 transition-all disabled:opacity-40 disabled:cursor-not-allowed">
                                <span x-show="!submitting">Utiliser le code de récupération</span>
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

                    {{-- ============================== TOGGLE METHODS ============================== --}}
                    <div class="mt-4 pt-4 border-t border-gray-100">
                        @if (isset($showRecoveryOption) && $showRecoveryOption)
                            <button x-show="mode === 'totp'" @click="mode = 'recovery'"
                                class="w-full text-center text-sm text-gray-500 hover:text-gray-900 transition-colors py-1">
                                <span class="inline-flex items-center gap-1.5">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M15.75 5.25a3 3 0 013 3m3 0a6 6 0 01-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1121.75 8.25z" />
                                    </svg>
                                    Utiliser un code de récupération
                                </span>
                            </button>
                        @endif
                        <button x-show="mode === 'recovery'" x-cloak @click="mode = 'totp'"
                            class="w-full text-center text-sm text-gray-500 hover:text-gray-900 transition-colors py-1">
                            <span class="inline-flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M10.5 1.5H8.25A2.25 2.25 0 006 3.75v16.5a2.25 2.25 0 002.25 2.25h7.5A2.25 2.25 0 0018 20.25V3.75a2.25 2.25 0 00-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 18.75h3" />
                                </svg>
                                Utiliser l'application d'authentification
                            </span>
                        </button>
                    </div>

                    {{-- Aide --}}
                    <div class="mt-3 text-center">
                        <p class="text-[11px] text-gray-400">
                            Besoin d'aide ?
                            <a href="{{ route('pages.contact') }}"
                                class="text-[#CC5A00] hover:text-[#A34700] font-medium">Contacter le support</a>
                        </p>
                    </div>
                </div>
            </div>

            {{-- Déconnexion --}}
            <div class="text-center mt-4">
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="text-sm text-gray-400 hover:text-gray-700 transition-colors">
                        Se déconnecter
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- Alpine.js Components --}}
    <script>
        // ======= OTP Split Input (6 boxes) =======
        function otpInput() {
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
                        document.getElementById('otp-' + (index + 1)).focus();
                    }

                    // Auto-submit quand les 6 chiffres sont remplis
                    if (this.fullCode.length === 6) {
                        this.submitting = true;
                        this.$nextTick(() => {
                            document.getElementById('totpForm').submit();
                        });
                    }
                },
                handleKeydown(e, index) {
                    if (e.key === 'Backspace') {
                        if (!this.digits[index] && index > 0) {
                            e.preventDefault();
                            this.digits[index - 1] = '';
                            document.getElementById('otp-' + (index - 1)).focus();
                        } else {
                            this.digits[index] = '';
                        }
                    }
                    if (e.key === 'ArrowLeft' && index > 0) {
                        e.preventDefault();
                        document.getElementById('otp-' + (index - 1)).focus();
                    }
                    if (e.key === 'ArrowRight' && index < 5) {
                        e.preventDefault();
                        document.getElementById('otp-' + (index + 1)).focus();
                    }
                },
                handlePaste(e) {
                    const paste = e.clipboardData.getData('text').replace(/[^0-9]/g, '').slice(0, 6);
                    for (let i = 0; i < 6; i++) {
                        this.digits[i] = paste[i] || '';
                    }
                    // Focus le dernier champ rempli
                    const lastIndex = Math.min(paste.length, 5);
                    document.getElementById('otp-' + lastIndex).focus();

                    if (paste.length === 6) {
                        this.submitting = true;
                        this.$nextTick(() => {
                            document.getElementById('totpForm').submit();
                        });
                    }
                }
            }
        }

        // ======= TOTP Countdown Timer (30s cycle) =======
        function totpTimer() {
            return {
                seconds: 30,
                circumference: 2 * Math.PI * 8,
                get progress() {
                    return this.seconds / 30;
                },
                init() {
                    this.seconds = 30 - (Math.floor(Date.now() / 1000) % 30);
                    setInterval(() => {
                        this.seconds = 30 - (Math.floor(Date.now() / 1000) % 30);
                    }, 1000);
                }
            }
        }
    </script>

    <style>
        @keyframes pulse-slow {

            0%,
            100% {
                opacity: 1;
            }

            50% {
                opacity: 0.7;
            }
        }

        .animate-pulse-slow {
            animation: pulse-slow 3s ease-in-out infinite;
        }
    </style>
@endsection
