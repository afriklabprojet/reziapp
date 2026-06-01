@extends('layouts.owner')

@section('title', 'Programme de Parrainage')

@section('owner-content')
    <div class="space-y-6" x-data="referralPage">

        {{-- Header --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Programme de Parrainage</h1>
                <p class="text-gray-500 mt-1">Invitez vos amis et gagnez des récompenses</p>
            </div>
            <a href="{{ route('owner.marketing.referrals.leaderboard') }}"
                class="inline-flex items-center gap-2 px-4 py-2.5 bg-white border border-gray-200 rounded-xl text-sm font-medium text-gray-700 hover:bg-gray-50 transition shadow-sm">
                <svg class="w-4 h-4 text-amber-500" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd"
                        d="M10 1a.75.75 0 01.65.378l1.953 3.401 3.83.563a.75.75 0 01.416 1.28l-2.772 2.7.654 3.818a.75.75 0 01-1.088.79L10 11.347l-3.643 1.914a.75.75 0 01-1.088-.79l.654-3.818-2.772-2.7a.75.75 0 01.416-1.28l3.83-.563L9.35 1.378A.75.75 0 0110 1z"
                        clip-rule="evenodd" />
                </svg>
                Classement
            </a>
        </div>

        {{-- Flash Messages --}}
        @if (session('success'))
            <div
                class="flex items-center gap-3 px-4 py-3 bg-green-50 border border-green-200 rounded-xl text-sm text-green-700">
                <svg class="w-5 h-5 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd"
                        d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z"
                        clip-rule="evenodd" />
                </svg>
                {{ session('success') }}
            </div>
        @endif
        @if (session('error'))
            <div class="flex items-center gap-3 px-4 py-3 bg-red-50 border border-red-200 rounded-xl text-sm text-red-700">
                <svg class="w-5 h-5 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd"
                        d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z"
                        clip-rule="evenodd" />
                </svg>
                {{ session('error') }}
            </div>
        @endif

        {{-- Referral Code + Share Card --}}
        <div
            class="bg-linear-to-br from-[#F16A00] via-[#CC5A00] to-[#A34700] rounded-2xl p-6 sm:p-8 text-white shadow-lg relative overflow-hidden">
            {{-- Decorative circles --}}
            <div class="absolute top-0 right-0 w-40 h-40 bg-white/5 rounded-full -translate-y-1/2 translate-x-1/2"></div>
            <div class="absolute bottom-0 left-0 w-24 h-24 bg-white/5 rounded-full translate-y-1/2 -translate-x-1/2"></div>

            <div class="relative flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
                <div class="flex-1">
                    <div class="flex items-center gap-2 mb-3">
                        <svg class="w-5 h-5 text-[#FFD0A3]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197" />
                        </svg>
                        <h2 class="text-lg font-bold">Votre code de parrainage</h2>
                    </div>
                    <p class="text-[#FFE7D1] mb-5 text-sm">
                        Partagez ce code et gagnez
                        <strong class="text-white">{{ number_format($referralConfig['referrer_reward'], 0, ',', ' ') }}
                            FCFA</strong>
                        par filleul qualifié
                    </p>

                    {{-- Code + Copy --}}
                    <div class="flex items-center gap-3 mb-5">
                        <code
                            class="px-5 py-3 bg-white/15 backdrop-blur-sm rounded-xl text-2xl font-mono font-bold tracking-[0.15em] border border-white/20">
                            {{ auth()->user()->referral_code }}
                        </code>
                        <button @click="copyCode('{{ auth()->user()->referral_code }}')"
                            class="p-3 bg-white/15 rounded-xl hover:bg-white/25 transition border border-white/20"
                            title="Copier le code">
                            <svg x-show="!codeCopied" class="w-5 h-5" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                            </svg>
                            <svg x-show="codeCopied" x-cloak class="w-5 h-5 text-green-300" fill="currentColor"
                                viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z"
                                    clip-rule="evenodd" />
                            </svg>
                        </button>
                    </div>

                    {{-- Share URL --}}
                    <div class="flex items-center gap-2 bg-white/10 rounded-xl p-2 border border-white/10 max-w-md">
                        <input type="text" readonly
                            value="{{ route('register', ['ref' => auth()->user()->referral_code]) }}" id="referral-url"
                            class="flex-1 bg-transparent border-0 text-white/90 text-xs focus:outline-none focus:ring-0 truncate px-2" />
                        <button @click="copyUrl()"
                            class="px-3 py-1.5 bg-white/20 rounded-lg hover:bg-white/30 transition text-xs font-medium whitespace-nowrap">
                            <span x-show="!urlCopied">Copier le lien</span>
                            <span x-show="urlCopied" x-cloak class="text-green-300">✓ Copié</span>
                        </button>
                    </div>
                </div>

                {{-- Share Buttons --}}
                <div class="flex flex-wrap lg:flex-col gap-2">
                    <a href="{{ route('owner.marketing.referrals.share', ['channel' => 'whatsapp']) }}"
                        class="inline-flex items-center gap-2 px-4 py-2.5 bg-[#25D366] rounded-xl hover:brightness-110 transition text-sm font-medium shadow-sm">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                            <path
                                d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z" />
                        </svg>
                        WhatsApp
                    </a>
                    <a href="{{ route('owner.marketing.referrals.share', ['channel' => 'facebook']) }}"
                        class="inline-flex items-center gap-2 px-4 py-2.5 bg-[#1877F2] rounded-xl hover:brightness-110 transition text-sm font-medium shadow-sm">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                            <path
                                d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z" />
                        </svg>
                        Facebook
                    </a>
                    <a href="{{ route('owner.marketing.referrals.share', ['channel' => 'twitter']) }}"
                        class="inline-flex items-center gap-2 px-4 py-2.5 bg-gray-900 rounded-xl hover:bg-gray-800 transition text-sm font-medium shadow-sm">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                            <path
                                d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z" />
                        </svg>
                        X
                    </a>
                    <a href="{{ route('owner.marketing.referrals.share', ['channel' => 'email']) }}"
                        class="inline-flex items-center gap-2 px-4 py-2.5 bg-white/15 border border-white/20 rounded-xl hover:bg-white/25 transition text-sm font-medium shadow-sm">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                        Email
                    </a>
                </div>
            </div>
        </div>

        {{-- Stats Grid --}}
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-3 sm:gap-4">
            <div class="bg-white rounded-2xl shadow-sm p-5 border border-gray-100">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-blue-50 rounded-xl flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-gray-900">{{ $stats['total_referrals'] }}</p>
                        <p class="text-xs text-gray-500">Filleuls</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-sm p-5 border border-gray-100">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-amber-50 rounded-xl flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-gray-900">{{ $stats['pending'] }}</p>
                        <p class="text-xs text-gray-500">En attente</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-sm p-5 border border-gray-100">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-blue-50 rounded-xl flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-gray-900">{{ $stats['qualified'] }}</p>
                        <p class="text-xs text-gray-500">Qualifiés</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-sm p-5 border border-gray-100">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-green-50 rounded-xl flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-gray-900">{{ $stats['rewarded'] }}</p>
                        <p class="text-xs text-gray-500">Récompensés</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-sm p-5 border border-gray-100 col-span-2 lg:col-span-1">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-purple-50 rounded-xl flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-gray-900">
                            {{ number_format($stats['total_rewards'], 0, ',', ' ') }}</p>
                        <p class="text-xs text-gray-500">FCFA gagnés</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Referral Balance --}}
        @if (($stats['referral_balance'] ?? 0) > 0)
            <div class="bg-green-50 rounded-2xl border border-green-200 p-5 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-green-100 rounded-xl flex items-center justify-center">
                        <svg class="w-5 h-5 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                            <path
                                d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm text-green-700">Votre solde de parrainage disponible</p>
                        <p class="text-2xl font-bold text-green-800">
                            {{ number_format($stats['referral_balance'], 0, ',', ' ') }} FCFA</p>
                    </div>
                </div>
            </div>
        @endif

        {{-- How it works --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-5">Comment ça marche ?</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="relative text-center p-4">
                    <div class="w-12 h-12 mx-auto bg-[#FFE7D1] rounded-2xl flex items-center justify-center mb-3">
                        <svg class="w-6 h-6 text-[#CC5A00]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z" />
                        </svg>
                    </div>
                    <h4 class="font-medium text-gray-900 text-sm mb-1">1. Partagez</h4>
                    <p class="text-xs text-gray-500">Envoyez votre code à vos amis</p>
                    <div class="hidden lg:block absolute top-1/2 -right-2 -translate-y-1/2 text-gray-300">→</div>
                </div>
                <div class="relative text-center p-4">
                    <div class="w-12 h-12 mx-auto bg-blue-100 rounded-2xl flex items-center justify-center mb-3">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                        </svg>
                    </div>
                    <h4 class="font-medium text-gray-900 text-sm mb-1">2. Inscription</h4>
                    <p class="text-xs text-gray-500">Ils créent un compte via votre lien</p>
                    <div class="hidden lg:block absolute top-1/2 -right-2 -translate-y-1/2 text-gray-300">→</div>
                </div>
                <div class="relative text-center p-4">
                    <div class="w-12 h-12 mx-auto bg-green-100 rounded-2xl flex items-center justify-center mb-3">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <h4 class="font-medium text-gray-900 text-sm mb-1">3. Réservation</h4>
                    <p class="text-xs text-gray-500">Leur 1ère réservation confirmée qualifie</p>
                    <div class="hidden lg:block absolute top-1/2 -right-2 -translate-y-1/2 text-gray-300">→</div>
                </div>
                <div class="text-center p-4">
                    <div class="w-12 h-12 mx-auto bg-purple-100 rounded-2xl flex items-center justify-center mb-3">
                        <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7" />
                        </svg>
                    </div>
                    <h4 class="font-medium text-gray-900 text-sm mb-1">4. Récompense</h4>
                    <p class="text-xs text-gray-500">
                        Vous : {{ number_format($referralConfig['referrer_reward'], 0, ',', ' ') }} FCFA •
                        Filleul : {{ number_format($referralConfig['referred_reward'], 0, ',', ' ') }} FCFA
                    </p>
                </div>
            </div>
        </div>

        {{-- Referrals List --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100">
                <h3 class="font-semibold text-gray-900">Vos filleuls</h3>
            </div>

            @if ($referrals->isEmpty())
                <div class="text-center py-16 px-6">
                    <div class="w-16 h-16 mx-auto bg-gray-100 rounded-2xl flex items-center justify-center mb-4">
                        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                    </div>
                    <h3 class="text-sm font-semibold text-gray-900 mb-1">Pas encore de filleuls</h3>
                    <p class="text-sm text-gray-500 mb-4">Partagez votre code pour commencer à parrainer !</p>
                    <button @click="copyCode('{{ auth()->user()->referral_code }}')"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-[#CC5A00] text-white text-sm font-medium rounded-xl hover:bg-[#A34700] transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                        </svg>
                        Copier mon code
                    </button>
                </div>
            @else
                {{-- Mobile Cards --}}
                <div class="sm:hidden divide-y divide-gray-100">
                    @foreach ($referrals as $referral)
                        <div class="p-4">
                            <div class="flex items-center justify-between mb-3">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 bg-[#FFE7D1] rounded-full flex items-center justify-center">
                                        <span
                                            class="text-[#CC5A00] font-semibold text-sm">{{ substr($referral->referred->name ?? 'U', 0, 1) }}</span>
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-900">
                                            {{ $referral->referred->name ?? 'Utilisateur' }}</p>
                                        <p class="text-xs text-gray-500">{{ $referral->created_at->format('d/m/Y') }}</p>
                                    </div>
                                </div>
                                @include('owner.marketing.referrals._status-badge', [
                                    'status' => $referral->status,
                                ])
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-medium text-gray-900">
                                    {{ number_format($referral->referrer_reward ?? $referralConfig['referrer_reward'], 0, ',', ' ') }}
                                    FCFA
                                </span>
                                @if ($referral->status === 'qualified' && !$referral->rewarded_at)
                                    <form action="{{ route('owner.marketing.referrals.claim', $referral) }}"
                                        method="POST">
                                        @csrf
                                        <button type="submit"
                                            class="px-3 py-1.5 bg-[#CC5A00] text-white text-xs font-medium rounded-lg hover:bg-[#A34700] transition">
                                            Réclamer
                                        </button>
                                    </form>
                                @elseif($referral->status === 'pending')
                                    <span class="text-xs text-gray-400 italic">En attente de réservation</span>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Desktop Table --}}
                <div class="hidden sm:block overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-100">
                        <thead class="bg-gray-50/50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Filleul</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Inscrit le</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Statut</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Récompense</th>
                                <th
                                    class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($referrals as $referral)
                                <tr class="hover:bg-gray-50/50 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center gap-3">
                                            <div
                                                class="w-9 h-9 bg-[#FFE7D1] rounded-full flex items-center justify-center">
                                                <span
                                                    class="text-[#CC5A00] font-semibold text-sm">{{ substr($referral->referred->name ?? 'U', 0, 1) }}</span>
                                            </div>
                                            <div>
                                                <p class="text-sm font-medium text-gray-900">
                                                    {{ $referral->referred->name ?? 'Utilisateur' }}</p>
                                                <p class="text-xs text-gray-500">{{ $referral->referred->email ?? '' }}
                                                </p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <p class="text-sm text-gray-700">{{ $referral->created_at->format('d/m/Y') }}</p>
                                        <p class="text-xs text-gray-400">{{ $referral->created_at->diffForHumans() }}</p>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @include('owner.marketing.referrals._status-badge', [
                                            'status' => $referral->status,
                                        ])
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="text-sm font-semibold text-gray-900">
                                            {{ number_format($referral->referrer_reward ?? $referralConfig['referrer_reward'], 0, ',', ' ') }}
                                            FCFA
                                        </span>
                                        @if ($referral->status === 'rewarded')
                                            <span class="text-xs text-green-600 ml-1">✓ Crédité</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right">
                                        @if ($referral->status === 'qualified' && !$referral->rewarded_at)
                                            <form action="{{ route('owner.marketing.referrals.claim', $referral) }}"
                                                method="POST" class="inline">
                                                @csrf
                                                <button type="submit"
                                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-[#CC5A00] text-white text-xs font-medium rounded-lg hover:bg-[#A34700] transition shadow-sm">
                                                    <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                                                        <path
                                                            d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" />
                                                    </svg>
                                                    Réclamer
                                                </button>
                                            </form>
                                        @elseif($referral->status === 'pending')
                                            <span class="text-xs text-gray-400 italic">En attente de réservation</span>
                                        @elseif($referral->status === 'rewarded')
                                            <span class="inline-flex items-center gap-1 text-xs text-green-600">
                                                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd"
                                                        d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z"
                                                        clip-rule="evenodd" />
                                                </svg>
                                                Récompensé
                                            </span>
                                        @else
                                            <span class="text-xs text-gray-400">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if ($referrals->hasPages())
                    <div class="px-6 py-4 border-t border-gray-100">
                        {{ $referrals->links() }}
                    </div>
                @endif
            @endif
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('alpine:init', () => {
                Alpine.data('referralPage', () => ({
                    codeCopied: false,
                    urlCopied: false,
                    copyCode(code) {
                        navigator.clipboard.writeText(code).then(() => {
                            this.codeCopied = true;
                            setTimeout(() => this.codeCopied = false, 2000);
                        });
                    },
                    copyUrl() {
                        const url = document.getElementById('referral-url').value;
                        navigator.clipboard.writeText(url).then(() => {
                            this.urlCopied = true;
                            setTimeout(() => this.urlCopied = false, 2000);
                        });
                    }
                }));
            });
        </script>
    @endpush
@endsection
