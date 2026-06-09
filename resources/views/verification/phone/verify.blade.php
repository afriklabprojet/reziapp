@extends('layouts.owner')

@section('title', 'Vérification téléphone - Rezi App')

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
                            d="M10.5 1.5H8.25A2.25 2.25 0 006 3.75v16.5a2.25 2.25 0 002.25 2.25h7.5A2.25 2.25 0 0018 20.25V3.75a2.25 2.25 0 00-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 18.75h3" />
                    </svg>
                </div>
                <div>
                    <h1 class="text-xl sm:text-2xl font-bold text-gray-900">Vérification téléphone</h1>
                    <p class="text-sm text-gray-500 mt-0.5">Confirmez votre numéro de téléphone</p>
                </div>
            </div>
        </div>

        {{-- ============================== FLASH MESSAGES ============================== --}}
        @if (session('success'))
            <div x-data="autoHide(5000)" x-show="show" x-transition
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

        {{-- Dev OTP code display --}}
        @if (session('dev_otp_code') && app()->environment('local', 'testing'))
            <div class="flex items-center gap-3 px-4 py-3 bg-purple-50 border border-purple-200 rounded-xl">
                <span class="text-lg">🔑</span>
                <p class="text-sm font-medium text-purple-800">Code OTP dev : <span
                        class="font-mono font-bold text-purple-900">{{ session('dev_otp_code') }}</span></p>
            </div>
        @endif

        <div class="max-w-lg">

            {{-- ============================== ZONE CODE OTP ============================== --}}
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                <div class="p-5 sm:p-8 text-center">
                    {{-- Icône + info --}}
                    <div class="w-14 h-14 bg-gray-100 rounded-2xl flex items-center justify-center mx-auto mb-4">
                        <svg class="w-7 h-7 text-gray-600" fill="none" stroke="currentColor" stroke-width="1.5"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" />
                        </svg>
                    </div>
                    <p class="text-sm text-gray-600 mb-1">Code envoyé au</p>
                    <p class="text-base font-bold text-gray-900 mb-6">{{ auth()->user()->phone }}</p>

                    {{-- Formulaire OTP --}}
                    <form action="{{ route('verification.phone.verify') }}" method="POST" x-data="otpInputForm()"
                        @submit.prevent="if(fullCode.length === 6) { submitting = true; $el.submit(); }">
                        @csrf

                        <input type="hidden" name="code" :value="fullCode">

                        {{-- Inputs OTP --}}
                        <div class="flex justify-center gap-2.5 sm:gap-3 mb-5" @paste="handlePaste">
                            <template x-for="(digit, index) in code" :key="index">
                                <input type="text" :x-ref="'input' + index" maxlength="1"
                                    class="w-11 h-13 sm:w-12 sm:h-14 text-center text-xl sm:text-2xl font-bold rounded-xl border border-gray-200 bg-gray-50 focus:bg-white focus:border-gray-900 focus:ring-1 focus:ring-gray-900 transition-all outline-none"
                                    x-model="code[index]" @input="focusNext(index)" @keydown="focusPrev(index, $event)"
                                    inputmode="numeric" pattern="[0-9]*">
                            </template>
                        </div>

                        @error('code')
                            <p class="text-xs text-red-600 mb-4">{{ $message }}</p>
                        @enderror

                        {{-- Bouton vérifier --}}
                        <button type="submit" :disabled="fullCode.length !== 6 || submitting"
                            class="w-full py-3 bg-gray-900 text-white text-sm font-semibold rounded-xl hover:bg-gray-800 transition-all shadow-sm hover:shadow-md disabled:opacity-50 disabled:cursor-not-allowed">
                            <template x-if="!submitting">
                                <span class="inline-flex items-center gap-2 justify-center">
                                    Vérifier le code
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </span>
                            </template>
                            <template x-if="submitting">
                                <span class="inline-flex items-center gap-2 justify-center">
                                    <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10"
                                            stroke="currentColor" stroke-width="4" />
                                        <path class="opacity-75" fill="currentColor"
                                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
                                    </svg>
                                    Vérification…
                                </span>
                            </template>
                        </button>
                    </form>
                </div>

                {{-- Timer / Renvoi --}}
                <div class="border-t border-gray-100 px-5 sm:px-8 py-4 text-center bg-gray-50/50" x-data="{
                    timer: {{ $resendTimer ?? 120 }},
                    canResend: false,
                    resending: false,
                    startTimer() {
                        const interval = setInterval(() => {
                            this.timer--;
                            if (this.timer <= 0) {
                                this.canResend = true;
                                clearInterval(interval);
                            }
                        }, 1000);
                    },
                    formatTime(seconds) {
                        const m = Math.floor(seconds / 60);
                        const s = seconds % 60;
                        return m > 0 ? m + 'min ' + (s < 10 ? '0' : '') + s + 's' : s + 's';
                    }
                }"
                    x-init="startTimer()">

                    <template x-if="!canResend">
                        <p class="text-xs text-gray-500">
                            Renvoyer dans
                            <span class="font-semibold text-gray-700 tabular-nums" x-text="formatTime(timer)"></span>
                        </p>
                    </template>

                    <template x-if="canResend">
                        <form action="{{ route('verification.phone.send') }}" method="POST" class="inline"
                            @submit="resending = true">
                            @csrf
                            <button type="submit" :disabled="resending"
                                class="text-sm font-semibold text-gray-900 hover:text-gray-700 transition-colors underline underline-offset-2 decoration-gray-300 hover:decoration-gray-900 disabled:opacity-50">
                                <span x-show="!resending">Renvoyer le code</span>
                                <span x-show="resending" class="inline-flex items-center gap-1.5">
                                    <svg class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10"
                                            stroke="currentColor" stroke-width="4" />
                                        <path class="opacity-75" fill="currentColor"
                                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
                                    </svg>
                                    Envoi…
                                </span>
                            </button>
                        </form>
                    </template>
                </div>
            </div>

            {{-- ============================== AIDE ============================== --}}
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden mt-5">
                <div class="p-5 sm:p-6">
                    <h3 class="text-sm font-semibold text-gray-900 mb-3">Vous n'avez pas reçu le code ?</h3>
                    <ul class="space-y-2.5">
                        <li class="flex items-start gap-2.5">
                            <div
                                class="shrink-0 w-5 h-5 rounded-md bg-gray-100 flex items-center justify-center mt-0.5">
                                <span class="text-[10px] text-gray-500 font-bold">1</span>
                            </div>
                            <p class="text-xs text-gray-600">Vérifiez que votre numéro est correct</p>
                        </li>
                        <li class="flex items-start gap-2.5">
                            <div
                                class="shrink-0 w-5 h-5 rounded-md bg-gray-100 flex items-center justify-center mt-0.5">
                                <span class="text-[10px] text-gray-500 font-bold">2</span>
                            </div>
                            <p class="text-xs text-gray-600">Consultez vos SMS et votre messagerie</p>
                        </li>
                        <li class="flex items-start gap-2.5">
                            <div
                                class="shrink-0 w-5 h-5 rounded-md bg-gray-100 flex items-center justify-center mt-0.5">
                                <span class="text-[10px] text-gray-500 font-bold">3</span>
                            </div>
                            <p class="text-xs text-gray-600">Le code peut prendre jusqu'à 2 minutes pour arriver</p>
                        </li>
                    </ul>
                    <div class="mt-4 pt-3 border-t border-gray-100">
                        <a href="{{ route('profile.edit') }}"
                            class="inline-flex items-center gap-1.5 text-sm font-semibold text-gray-900 hover:text-gray-700 transition-colors">
                            Modifier mon numéro
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                            </svg>
                        </a>
                    </div>
                </div>
            </div>

        </div>
    </div>
@endsection
