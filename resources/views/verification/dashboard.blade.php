@extends('layouts.owner')

@section('title', 'Centre de Vérification - ReziApp')

@section('owner-content')
    <div x-data="{ showEmergencyModal: false }" class="space-y-6">

        {{-- ============================== HEADER ============================== --}}
        <div>
            <h1 class="text-2xl font-bold text-gray-900">
                <span class="inline-flex items-center gap-2">
                    <svg class="w-7 h-7 text-gray-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
                    </svg>
                    Centre de vérification
                </span>
            </h1>
            <p class="text-gray-600 mt-1">Renforcez la confiance de votre compte</p>
        </div>

        {{-- ============================== FLASH MESSAGES ============================== --}}
        @if (session('success'))
            <div x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 5000)"
                class="flex items-center gap-3 px-4 py-3 bg-emerald-50 border border-emerald-200 rounded-xl">
                <svg class="w-5 h-5 text-emerald-600 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd"
                        d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                        clip-rule="evenodd" />
                </svg>
                <p class="text-sm font-medium text-emerald-800">{{ session('success') }}</p>
                <button @click="show = false" class="ml-auto text-emerald-400 hover:text-emerald-600">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
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
                <button @click="show = false" class="ml-auto text-red-400 hover:text-red-600">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        @endif
        @if (session('status') === 'verification-link-sent')
            <div x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 5000)"
                class="flex items-center gap-3 px-4 py-3 bg-emerald-50 border border-emerald-200 rounded-xl">
                <svg class="w-5 h-5 text-emerald-600 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd"
                        d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                        clip-rule="evenodd" />
                </svg>
                <p class="text-sm font-medium text-emerald-800">Un lien de vérification a été envoyé à votre adresse email.
                </p>
                <button @click="show = false" class="ml-auto text-emerald-400 hover:text-emerald-600">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
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
                <button @click="show = false" class="ml-auto text-blue-400 hover:text-blue-600">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
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

        <div class="space-y-6">

            {{-- ============================== SCORE DE CONFIANCE ============================== --}}
            @php
                $scoreColor = match (true) {
                    $trustScore >= 80 => 'emerald',
                    $trustScore >= 60 => 'blue',
                    $trustScore >= 40 => 'amber',
                    default => 'gray',
                };
                $scoreLabel = match (true) {
                    $trustScore >= 80 => 'Excellent',
                    $trustScore >= 60 => 'Bon',
                    $trustScore >= 40 => 'Moyen',
                    default => 'À compléter',
                };
                $scoreMessage = match (true) {
                    $trustScore >= 80
                        => 'Votre profil inspire confiance. Les utilisateurs voient que votre compte est fiable.',
                    $trustScore >= 60 => 'Bon score ! Complétez les dernières étapes pour atteindre le niveau maximum.',
                    $trustScore >= 40
                        => 'Vérifiez votre identité pour améliorer votre score et débloquer plus de fonctionnalités.',
                    default => 'Commencez par vérifier votre compte pour bâtir la confiance.',
                };
            @endphp

            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                <div class="p-5 sm:p-6">
                    <div class="flex items-center justify-between">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2.5 mb-3">
                                <h2 class="text-sm font-semibold text-gray-900">Score de confiance</h2>
                                <span
                                    class="px-2 py-0.5 rounded-full text-[11px] font-bold bg-{{ $scoreColor }}-50 text-{{ $scoreColor }}-700">{{ $scoreLabel }}</span>
                            </div>
                            <div class="flex items-baseline gap-1">
                                <span class="text-4xl sm:text-5xl font-extrabold text-gray-900">{{ $trustScore }}</span>
                                <span class="text-lg font-semibold text-gray-300">/100</span>
                            </div>
                            <p class="text-sm text-gray-500 mt-2 max-w-md">{{ $scoreMessage }}</p>
                        </div>

                        {{-- Cercle de progression --}}
                        <div class="hidden sm:flex shrink-0 ml-6">
                            <div class="relative w-24 h-24">
                                <svg class="w-24 h-24 transform -rotate-90" viewBox="0 0 100 100">
                                    <circle cx="50" cy="50" r="42" fill="none" stroke="#f3f4f6"
                                        stroke-width="6" />
                                    <circle cx="50" cy="50" r="42" fill="none"
                                        stroke="{{ $trustScore >= 80 ? '#10b981' : ($trustScore >= 60 ? '#3b82f6' : ($trustScore >= 40 ? '#f59e0b' : '#d1d5db')) }}"
                                        stroke-width="6" stroke-dasharray="{{ (263.89 * $trustScore) / 100 }} 263.89"
                                        stroke-linecap="round" />
                                </svg>
                                <div class="absolute inset-0 flex items-center justify-center">
                                    <svg class="w-8 h-8 text-{{ $scoreColor }}-500" fill="none"
                                        stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Barre de progression mobile --}}
                    <div class="sm:hidden mt-4">
                        <div class="w-full h-2 bg-gray-100 rounded-full overflow-hidden">
                            <div class="h-full rounded-full transition-all duration-500 bg-{{ $scoreColor }}-500"
                                style="width: {{ $trustScore }}%"></div>
                        </div>
                    </div>
                </div>

                {{-- Détail des points --}}
                <div class="border-t border-gray-100 px-5 sm:px-6 py-3 bg-gray-50/50">
                    <div class="flex flex-wrap gap-x-5 gap-y-1 text-[11px] text-gray-500">
                        <span class="flex items-center gap-1">
                            <span
                                class="w-1.5 h-1.5 rounded-full {{ $user->email_verified_at ? 'bg-emerald-400' : 'bg-gray-300' }}"></span>
                            Email +10
                        </span>
                        <span class="flex items-center gap-1">
                            <span
                                class="w-1.5 h-1.5 rounded-full {{ $user->phone_verified ? 'bg-emerald-400' : 'bg-gray-300' }}"></span>
                            Téléphone +20
                        </span>
                        <span class="flex items-center gap-1">
                            <span
                                class="w-1.5 h-1.5 rounded-full {{ $user->identity_verified ? 'bg-emerald-400' : 'bg-gray-300' }}"></span>
                            Identité +40
                        </span>
                        <span class="flex items-center gap-1">
                            <span
                                class="w-1.5 h-1.5 rounded-full {{ $user->profile_photo || $user->avatar ? 'bg-emerald-400' : 'bg-gray-300' }}"></span>
                            Photo +5
                        </span>
                        <span class="flex items-center gap-1">
                            <span
                                class="w-1.5 h-1.5 rounded-full {{ $user->created_at->isBefore(now()->subMonths(6)) ? 'bg-emerald-400' : 'bg-gray-300' }}"></span>
                            Ancienneté +10
                        </span>
                        <span class="flex items-center gap-1">
                            <span
                                class="w-1.5 h-1.5 rounded-full {{ $user->two_factor_enabled ? 'bg-emerald-400' : 'bg-gray-300' }}"></span>
                            2FA +10
                        </span>
                        <span class="flex items-center gap-1">
                            <span class="w-1.5 h-1.5 rounded-full bg-gray-300"></span>
                            Avis +15
                        </span>
                    </div>
                </div>
            </div>

            {{-- ============================== ÉTAPES DE VÉRIFICATION ============================== --}}
            <div class="space-y-3">
                <h2 class="text-sm font-semibold text-gray-900 px-1">Vérifications</h2>

                {{-- 1. Identité --}}
                @php
                    $idStatus = $identityVerification?->status;
                    $idApproved = $idStatus === 'approved';
                    $idPending = in_array($idStatus, ['submitted', 'processing', 'manual_review']);
                    $idRejected = $idStatus === 'rejected';
                @endphp
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                    <div class="p-5 sm:p-6">
                        <div class="flex items-start gap-4">
                            <div
                                class="shrink-0 w-11 h-11 rounded-xl flex items-center justify-center {{ $idApproved ? 'bg-emerald-50' : ($idPending ? 'bg-amber-50' : 'bg-gray-100') }}">
                                @if ($idApproved)
                                    <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor"
                                        stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                @elseif($idPending)
                                    <svg class="w-5 h-5 text-amber-600 animate-spin" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10"
                                            stroke="currentColor" stroke-width="4" />
                                        <path class="opacity-75" fill="currentColor"
                                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
                                    </svg>
                                @else
                                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor"
                                        stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M15 9h3.75M15 12h3.75M15 15h3.75M4.5 19.5h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5H4.5A2.25 2.25 0 002.25 6.75v10.5A2.25 2.25 0 004.5 19.5zm6-10.125a1.875 1.875 0 11-3.75 0 1.875 1.875 0 013.75 0zm1.294 6.336a6.721 6.721 0 01-3.17.789 6.721 6.721 0 01-3.168-.789 3.376 3.376 0 016.338 0z" />
                                    </svg>
                                @endif
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between gap-3">
                                    <div>
                                        <h3 class="text-sm font-semibold text-gray-900">Pièce d'identité</h3>
                                        <p class="text-xs text-gray-500 mt-0.5">CNI ou Passeport · +40 points</p>
                                    </div>
                                    @if ($idApproved)
                                        <span
                                            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-700">
                                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd"
                                                    d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                                    clip-rule="evenodd" />
                                            </svg>
                                            Vérifié
                                        </span>
                                    @elseif($idPending)
                                        <span
                                            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-amber-50 text-amber-700">
                                            <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                                            En cours
                                        </span>
                                    @elseif($idRejected)
                                        <span
                                            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-red-50 text-red-700">
                                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd"
                                                    d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                                                    clip-rule="evenodd" />
                                            </svg>
                                            Refusé
                                        </span>
                                    @else
                                        <span
                                            class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-bold bg-gray-100 text-gray-500">Non
                                            vérifié</span>
                                    @endif
                                </div>

                                @if ($idRejected && $identityVerification->rejection_reason)
                                    <div class="mt-3 flex items-start gap-2 px-3 py-2 bg-red-50 rounded-lg">
                                        <svg class="w-4 h-4 text-red-400 shrink-0 mt-0.5" fill="currentColor"
                                            viewBox="0 0 20 20">
                                            <path fill-rule="evenodd"
                                                d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                                                clip-rule="evenodd" />
                                        </svg>
                                        <p class="text-xs text-red-700">{{ $identityVerification->rejection_reason }}</p>
                                    </div>
                                @endif

                                @if (!$idApproved && !$idPending)
                                    <a href="{{ route('verification.identity.start') }}"
                                        class="mt-3 inline-flex items-center gap-1.5 px-4 py-2 bg-gray-900 text-white text-sm font-semibold rounded-xl hover:bg-gray-800 transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                                        </svg>
                                        {{ $idRejected ? 'Réessayer' : 'Vérifier mon identité' }}
                                    </a>
                                @elseif($idPending)
                                    <p class="text-xs text-amber-600 mt-2">Vérification en cours, nous vous informerons par
                                        email.</p>
                                @elseif($idApproved && $identityVerification->expires_at)
                                    <p class="text-xs text-gray-400 mt-2">Expire le
                                        {{ $identityVerification->expires_at->format('d/m/Y') }}</p>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                {{-- 2. Téléphone --}}
                @php $phoneVerified = $user->phone_verified || $user->phone_verified_at; @endphp
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                    <div class="p-5 sm:p-6">
                        <div class="flex items-start gap-4">
                            <div
                                class="shrink-0 w-11 h-11 rounded-xl flex items-center justify-center {{ $phoneVerified ? 'bg-emerald-50' : 'bg-gray-100' }}">
                                @if ($phoneVerified)
                                    <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor"
                                        stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                @else
                                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor"
                                        stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M10.5 1.5H8.25A2.25 2.25 0 006 3.75v16.5a2.25 2.25 0 002.25 2.25h7.5A2.25 2.25 0 0018 20.25V3.75a2.25 2.25 0 00-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 18.75h3" />
                                    </svg>
                                @endif
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between gap-3">
                                    <div>
                                        <h3 class="text-sm font-semibold text-gray-900">Téléphone</h3>
                                        <p class="text-xs text-gray-500 mt-0.5">{{ $user->phone ?? 'Non renseigné' }} ·
                                            +20 points</p>
                                    </div>
                                    @if ($phoneVerified)
                                        <span
                                            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-700">
                                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd"
                                                    d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                                    clip-rule="evenodd" />
                                            </svg>
                                            Vérifié
                                        </span>
                                    @else
                                        <span
                                            class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-bold bg-gray-100 text-gray-500">Non
                                            vérifié</span>
                                    @endif
                                </div>

                                @if (!$phoneVerified)
                                    @if ($user->phone)
                                        <form action="{{ route('verification.phone.send') }}" method="POST"
                                            class="mt-3">
                                            @csrf
                                            <button type="submit"
                                                class="inline-flex items-center gap-1.5 px-4 py-2 bg-gray-900 text-white text-sm font-semibold rounded-xl hover:bg-gray-800 transition-colors">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                    stroke-width="2" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                                                </svg>
                                                Envoyer le code
                                            </button>
                                        </form>
                                    @else
                                        <a href="{{ route('profile.edit') }}"
                                            class="mt-3 inline-flex items-center gap-1.5 text-sm font-semibold text-[#CC5A00] hover:text-[#A34700] transition-colors">
                                            Ajouter un numéro
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor"
                                                stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                                            </svg>
                                        </a>
                                    @endif
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                {{-- 3. Email --}}
                @php $emailVerified = $user->email_verified_at; @endphp
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                    <div class="p-5 sm:p-6">
                        <div class="flex items-start gap-4">
                            <div
                                class="shrink-0 w-11 h-11 rounded-xl flex items-center justify-center {{ $emailVerified ? 'bg-emerald-50' : 'bg-gray-100' }}">
                                @if ($emailVerified)
                                    <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor"
                                        stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                @else
                                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor"
                                        stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" />
                                    </svg>
                                @endif
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between gap-3">
                                    <div>
                                        <h3 class="text-sm font-semibold text-gray-900">Email</h3>
                                        <p class="text-xs text-gray-500 mt-0.5">{{ $user->email }} · +10 points</p>
                                    </div>
                                    @if ($emailVerified)
                                        <span
                                            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-700">
                                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd"
                                                    d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                                    clip-rule="evenodd" />
                                            </svg>
                                            Vérifié
                                        </span>
                                    @else
                                        <span
                                            class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-bold bg-gray-100 text-gray-500">Non
                                            vérifié</span>
                                    @endif
                                </div>

                                @if (!$emailVerified)
                                    <form action="{{ route('verification.send') }}" method="POST" class="mt-3"
                                        x-data="{ sending: false }" @submit="sending = true">
                                        @csrf
                                        <button type="submit" :disabled="sending"
                                            class="inline-flex items-center gap-1.5 px-4 py-2 bg-gray-900 text-white text-sm font-semibold rounded-xl hover:bg-gray-800 transition-colors disabled:opacity-50">
                                            <template x-if="!sending">
                                                <span class="inline-flex items-center gap-1.5">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                        stroke-width="2" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                                                    </svg>
                                                    Renvoyer l'email
                                                </span>
                                            </template>
                                            <template x-if="sending">
                                                <span class="inline-flex items-center gap-1.5">
                                                    <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                                        <circle class="opacity-25" cx="12" cy="12" r="10"
                                                            stroke="currentColor" stroke-width="4" />
                                                        <path class="opacity-75" fill="currentColor"
                                                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
                                                    </svg>
                                                    Envoi…
                                                </span>
                                            </template>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                {{-- 4. Double authentification (2FA) --}}
                <div
                    class="bg-white rounded-2xl border {{ $user->two_factor_enabled ? 'border-emerald-100' : 'border-gray-100' }} shadow-sm overflow-hidden">
                    <div class="p-5 sm:p-6">
                        <div class="flex items-start gap-4">
                            <div
                                class="shrink-0 w-11 h-11 rounded-xl flex items-center justify-center {{ $user->two_factor_enabled ? 'bg-emerald-50' : 'bg-gray-100' }}">
                                @if ($user->two_factor_enabled)
                                    <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor"
                                        stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                @else
                                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor"
                                        stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                                    </svg>
                                @endif
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between gap-3">
                                    <div>
                                        <h3 class="text-sm font-semibold text-gray-900">Double authentification</h3>
                                        <p class="text-xs text-gray-500 mt-0.5">Google Authenticator · Sécurité renforcée
                                        </p>
                                    </div>
                                    @if ($user->two_factor_enabled)
                                        <span
                                            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-700">
                                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd"
                                                    d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                                    clip-rule="evenodd" />
                                            </svg>
                                            Active
                                        </span>
                                    @else
                                        <span
                                            class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-bold bg-gray-100 text-gray-500">Désactivée</span>
                                    @endif
                                </div>

                                <a href="{{ route('two-factor.setup') }}"
                                    class="mt-3 inline-flex items-center gap-1.5 {{ $user->two_factor_enabled ? 'text-sm font-semibold text-gray-600 hover:text-gray-900' : 'px-4 py-2 bg-gray-900 text-white text-sm font-semibold rounded-xl hover:bg-gray-800' }} transition-colors">
                                    @if ($user->two_factor_enabled)
                                        Gérer
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                                        </svg>
                                    @else
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                                        </svg>
                                        Activer la 2FA
                                    @endif
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- 5. Photo de profil --}}
                @php $hasPhoto = $user->profile_photo || $user->avatar; @endphp
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                    <div class="p-5 sm:p-6">
                        <div class="flex items-start gap-4">
                            <div
                                class="shrink-0 w-11 h-11 rounded-xl flex items-center justify-center overflow-hidden {{ $hasPhoto ? 'bg-emerald-50' : 'bg-gray-100' }}">
                                @if ($hasPhoto)
                                    <img src="{{ $user->getAvatarUrl() }}" alt=""
                                        class="w-11 h-11 object-cover">
                                @else
                                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor"
                                        stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                                    </svg>
                                @endif
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between gap-3">
                                    <div>
                                        <h3 class="text-sm font-semibold text-gray-900">Photo de profil</h3>
                                        <p class="text-xs text-gray-500 mt-0.5">Visage visible · +5 points</p>
                                    </div>
                                    @if ($hasPhoto)
                                        <span
                                            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-700">
                                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd"
                                                    d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                                    clip-rule="evenodd" />
                                            </svg>
                                            Ajoutée
                                        </span>
                                    @else
                                        <span
                                            class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-bold bg-gray-100 text-gray-500">Manquante</span>
                                    @endif
                                </div>

                                @if (!$hasPhoto)
                                    <a href="{{ route('profile.edit') }}"
                                        class="mt-3 inline-flex items-center gap-1.5 text-sm font-semibold text-[#CC5A00] hover:text-[#A34700] transition-colors">
                                        Ajouter une photo
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                                        </svg>
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ============================== CONTACTS D'URGENCE ============================== --}}
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                <div class="p-5 sm:p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-blue-50 flex items-center justify-center">
                                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" stroke-width="2"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z" />
                                </svg>
                            </div>
                            <div>
                                <h2 class="text-sm font-semibold text-gray-900">Contacts d'urgence</h2>
                                <p class="text-xs text-gray-500 mt-0.5">{{ $emergencyContacts->count() }}
                                    contact{{ $emergencyContacts->count() > 1 ? 's' : '' }}
                                    enregistré{{ $emergencyContacts->count() > 1 ? 's' : '' }}</p>
                            </div>
                        </div>
                        <a href="{{ route('verification.emergency.contacts') }}"
                            class="inline-flex items-center gap-1 text-sm font-semibold text-gray-600 hover:text-gray-900 transition-colors">
                            Gérer
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                            </svg>
                        </a>
                    </div>

                    @if ($emergencyContacts->count() > 0)
                        <div class="space-y-2">
                            @foreach ($emergencyContacts as $contact)
                                <div class="flex items-center justify-between px-4 py-3 bg-gray-50 rounded-xl">
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="w-9 h-9 rounded-full bg-blue-100 flex items-center justify-center text-blue-700 text-sm font-bold">
                                            {{ strtoupper(substr($contact->name, 0, 1)) }}
                                        </div>
                                        <div>
                                            <p class="text-sm font-semibold text-gray-900">{{ $contact->name }}</p>
                                            <p class="text-xs text-gray-500">{{ $contact->relationship }} ·
                                                {{ $contact->phone }}</p>
                                        </div>
                                    </div>
                                    @if ($contact->is_primary)
                                        <span
                                            class="px-2 py-0.5 rounded-full text-[11px] font-bold bg-blue-50 text-blue-700">Principal</span>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-8">
                            <div class="w-12 h-12 rounded-full bg-gray-100 flex items-center justify-center mx-auto mb-3">
                                <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor"
                                    stroke-width="1.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M19 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zM4 19.235v-.11a6.375 6.375 0 0112.75 0v.109A12.318 12.318 0 0110.374 21c-2.331 0-4.512-.645-6.374-1.766z" />
                                </svg>
                            </div>
                            <p class="text-sm text-gray-500 mb-3">Aucun contact d'urgence</p>
                            <a href="{{ route('verification.emergency.contacts') }}"
                                class="inline-flex items-center gap-1.5 text-sm font-semibold text-[#CC5A00] hover:text-[#A34700]">
                                Ajouter un contact
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                                </svg>
                            </a>
                        </div>
                    @endif
                </div>
            </div>

            {{-- ============================== MODE URGENCE ============================== --}}
            @if ($emergencyContacts->count() > 0)
                <div class="bg-white rounded-2xl border border-red-100 shadow-sm overflow-hidden">
                    <div class="p-5 sm:p-6">
                        <div class="flex items-start gap-4">
                            <div class="shrink-0 w-11 h-11 rounded-xl bg-red-50 flex items-center justify-center">
                                <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" stroke-width="2"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
                                </svg>
                            </div>
                            <div class="flex-1 min-w-0">
                                <h3 class="text-sm font-semibold text-gray-900">Mode urgence</h3>
                                <p class="text-xs text-gray-500 mt-0.5">En cas de danger, alertez vos contacts et notre
                                    équipe de sécurité.</p>
                                <button type="button" @click="showEmergencyModal = true"
                                    class="mt-3 inline-flex items-center gap-1.5 px-4 py-2 bg-red-600 text-white text-sm font-semibold rounded-xl hover:bg-red-700 transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
                                    </svg>
                                    Déclencher une alerte
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            {{-- ============================== AVANTAGES ============================== --}}
            @if ($trustScore < 80)
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                    <div class="p-5 sm:p-6">
                        <h2 class="text-sm font-semibold text-gray-900 mb-4">Pourquoi se vérifier ?</h2>
                        <div class="grid gap-3 sm:grid-cols-2">
                            <div class="flex items-start gap-3">
                                <div class="shrink-0 w-8 h-8 rounded-lg bg-emerald-50 flex items-center justify-center">
                                    <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor"
                                        stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-sm font-semibold text-gray-900">Badge vérifié</p>
                                    <p class="text-xs text-gray-500 mt-0.5">Visible sur votre profil public</p>
                                </div>
                            </div>
                            <div class="flex items-start gap-3">
                                <div class="shrink-0 w-8 h-8 rounded-lg bg-blue-50 flex items-center justify-center">
                                    <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor"
                                        stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3.75h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008z" />
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-sm font-semibold text-gray-900">Annonces illimitées</p>
                                    <p class="text-xs text-gray-500 mt-0.5">Publiez autant d'annonces que vous voulez</p>
                                </div>
                            </div>
                            <div class="flex items-start gap-3">
                                <div class="shrink-0 w-8 h-8 rounded-lg bg-[#FFF4EB] flex items-center justify-center">
                                    <svg class="w-4 h-4 text-[#CC5A00]" fill="none" stroke="currentColor"
                                        stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.563.563 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z" />
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-sm font-semibold text-gray-900">Plus de confiance</p>
                                    <p class="text-xs text-gray-500 mt-0.5">Les locataires vous contactent en priorité</p>
                                </div>
                            </div>
                            <div class="flex items-start gap-3">
                                <div class="shrink-0 w-8 h-8 rounded-lg bg-purple-50 flex items-center justify-center">
                                    <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor"
                                        stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-sm font-semibold text-gray-900">Sécurité renforcée</p>
                                    <p class="text-xs text-gray-500 mt-0.5">Protection accrue de votre compte</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        {{-- ============================== MODAL URGENCE ============================== --}}
        <div x-show="showEmergencyModal" x-cloak
            class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-4" x-data="{
                alertType: 'panic',
                alertMessage: '',
                latitude: null,
                longitude: null,
                locationStatus: 'idle',
                submitting: false,
                init() {
                    this.$watch('showEmergencyModal', (val) => {
                        if (val) this.getLocation();
                    });
                },
                getLocation() {
                    if (!navigator.geolocation) {
                        this.locationStatus = 'unsupported';
                        return;
                    }
                    this.locationStatus = 'loading';
                    navigator.geolocation.getCurrentPosition(
                        (pos) => {
                            this.latitude = pos.coords.latitude;
                            this.longitude = pos.coords.longitude;
                            this.locationStatus = 'success';
                        },
                        () => { this.locationStatus = 'error'; }, { enableHighAccuracy: true, timeout: 10000 }
                    );
                }
            }">
            <div x-show="showEmergencyModal" x-transition:enter="ease-out duration-200"
                x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                x-transition:leave="ease-in duration-150" x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0" class="fixed inset-0 bg-black/50" @click="showEmergencyModal = false">
            </div>

            <div x-show="showEmergencyModal" x-transition:enter="ease-out duration-200"
                x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" x-transition:leave="ease-in duration-150"
                x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                class="relative bg-white rounded-t-2xl sm:rounded-2xl shadow-2xl max-w-md w-full overflow-hidden">

                <div class="p-6">
                    <div class="w-14 h-14 rounded-full bg-red-100 flex items-center justify-center mx-auto mb-4">
                        <svg class="w-7 h-7 text-red-600" fill="none" stroke="currentColor" stroke-width="2"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                        </svg>
                    </div>

                    <h3 class="text-lg font-bold text-gray-900 text-center">Déclencher une alerte</h3>
                    <p class="text-sm text-gray-500 mt-1 text-center">
                        Vos contacts seront notifiés par SMS avec votre position.
                    </p>

                    <form action="{{ route('verification.emergency.trigger') }}" method="POST" class="mt-5 space-y-4"
                        @submit="submitting = true">
                        @csrf
                        <input type="hidden" name="latitude" :value="latitude">
                        <input type="hidden" name="longitude" :value="longitude">

                        {{-- Type d'alerte --}}
                        <div>
                            <label class="block text-sm font-semibold text-gray-900 mb-2">Type d'urgence</label>
                            <div class="grid grid-cols-2 gap-2">
                                <template
                                    x-for="opt in [
                                    { value: 'panic', label: 'Panique', icon: '🚨' },
                                    { value: 'sos', label: 'SOS', icon: '🆘' },
                                    { value: 'suspicious', label: 'Suspect', icon: '👁️' },
                                    { value: 'medical', label: 'Médical', icon: '🏥' }
                                ]"
                                    :key="opt.value">
                                    <label
                                        class="flex items-center gap-2 px-3 py-2.5 rounded-xl border cursor-pointer transition-all text-sm"
                                        :class="alertType === opt.value ?
                                            'border-red-300 bg-red-50 text-red-700 font-semibold' :
                                            'border-gray-200 hover:border-gray-300 text-gray-700'">
                                        <input type="radio" name="type" :value="opt.value" x-model="alertType"
                                            class="sr-only">
                                        <span x-text="opt.icon"></span>
                                        <span x-text="opt.label"></span>
                                    </label>
                                </template>
                            </div>
                        </div>

                        {{-- Message optionnel --}}
                        <div>
                            <label class="block text-sm font-semibold text-gray-900 mb-1.5">Message <span
                                    class="text-gray-400 font-normal">(optionnel)</span></label>
                            <textarea name="message" x-model="alertMessage" rows="2" maxlength="500"
                                class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 bg-gray-50 focus:bg-white focus:border-gray-900 focus:ring-1 focus:ring-gray-900 text-sm transition-all outline-none resize-none"
                                placeholder="Décrivez brièvement la situation…"></textarea>
                        </div>

                        {{-- Localisation --}}
                        <div class="flex items-center gap-2 px-3 py-2 rounded-lg text-xs"
                            :class="{
                                'bg-emerald-50 text-emerald-700': locationStatus === 'success',
                                'bg-amber-50 text-amber-700': locationStatus === 'loading',
                                'bg-red-50 text-red-600': locationStatus === 'error',
                                'bg-gray-50 text-gray-500': locationStatus === 'idle' ||
                                    locationStatus === 'unsupported'
                            }">
                            <template x-if="locationStatus === 'success'">
                                <span class="flex items-center gap-1.5">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd"
                                            d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                            clip-rule="evenodd" />
                                    </svg>
                                    Position GPS récupérée
                                </span>
                            </template>
                            <template x-if="locationStatus === 'loading'">
                                <span class="flex items-center gap-1.5">
                                    <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10"
                                            stroke="currentColor" stroke-width="4" />
                                        <path class="opacity-75" fill="currentColor"
                                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
                                    </svg>
                                    Récupération de votre position…
                                </span>
                            </template>
                            <template x-if="locationStatus === 'error'">
                                <span class="flex items-center gap-1.5">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd"
                                            d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                                            clip-rule="evenodd" />
                                    </svg>
                                    Position non disponible — l'alerte sera envoyée sans localisation
                                </span>
                            </template>
                            <template x-if="locationStatus === 'idle' || locationStatus === 'unsupported'">
                                <span>Localisation en attente…</span>
                            </template>
                        </div>

                        {{-- Actions --}}
                        <div class="flex flex-col gap-2 pt-1">
                            <button type="submit" :disabled="submitting"
                                class="w-full px-4 py-2.5 bg-red-600 text-white text-sm font-semibold rounded-xl hover:bg-red-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                                <template x-if="!submitting">
                                    <span class="inline-flex items-center gap-2 justify-center">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
                                        </svg>
                                        Confirmer l'alerte
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
                                        Envoi de l'alerte…
                                    </span>
                                </template>
                            </button>
                            <button type="button" @click="showEmergencyModal = false"
                                class="w-full px-4 py-2.5 bg-gray-100 text-gray-700 text-sm font-semibold rounded-xl hover:bg-gray-200 transition-colors">
                                Annuler
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    </div>
@endsection
