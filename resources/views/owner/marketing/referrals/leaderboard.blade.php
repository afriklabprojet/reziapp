@extends('layouts.owner')

@section('title', 'Classement Parrainage')

@section('owner-content')
    <div class="max-w-3xl mx-auto space-y-6">
        {{-- Header --}}
        <div>
            <a href="{{ route('owner.marketing.referrals.index') }}"
                class="inline-flex items-center gap-2 text-gray-500 hover:text-gray-900 text-sm mb-4 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
                Retour au parrainage
            </a>
            <h1 class="text-2xl font-bold text-gray-900">🏆 Classement des Parrains</h1>
            <p class="text-gray-500 mt-1">Les meilleurs ambassadeurs REZI ce mois-ci</p>
        </div>

        {{-- User Rank Card --}}
        @if ($userRank)
            <div
                class="bg-linear-to-br from-[#ff385c] via-[#e00b41] to-[#b5083a] rounded-2xl p-6 text-white relative overflow-hidden shadow-lg">
                <div class="absolute top-0 right-0 w-32 h-32 bg-white/5 rounded-full -translate-y-1/2 translate-x-1/2">
                </div>
                <div class="relative flex items-center justify-between">
                    <div>
                        <p class="text-[#ffd1da] text-sm mb-1">Votre position</p>
                        <p class="text-4xl font-bold">
                            {{ $userRank }}{{ $userRank == 1 ? 'er' : 'ème' }}
                            @if ($userRank <= 3)
                                <span class="ml-1">{{ $userRank == 1 ? '🥇' : ($userRank == 2 ? '🥈' : '🥉') }}</span>
                            @endif
                        </p>
                    </div>
                    <div class="text-right">
                        <p class="text-[#ffd1da] text-sm mb-1">Parrainages récompensés</p>
                        <p class="text-3xl font-bold">{{ $userCompletedCount }}</p>
                    </div>
                </div>
            </div>
        @else
            <div class="bg-gray-50 rounded-2xl p-8 border border-gray-200 text-center">
                <div class="w-14 h-14 mx-auto bg-gray-100 rounded-2xl flex items-center justify-center mb-4">
                    <svg class="w-7 h-7 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                            d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197" />
                    </svg>
                </div>
                <p class="text-gray-700 font-medium mb-1">Vous n'êtes pas encore dans le classement</p>
                <p class="text-sm text-gray-500 mb-4">Parrainez vos amis pour y apparaître !</p>
                <a href="{{ route('owner.marketing.referrals.index') }}"
                    class="inline-flex items-center gap-2 px-4 py-2.5 bg-[#e00b41] text-white text-sm font-medium rounded-xl hover:bg-[#b5083a] transition shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z" />
                    </svg>
                    Commencer à parrainer
                </a>
            </div>
        @endif

        {{-- Leaderboard --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100">
                <h3 class="font-semibold text-gray-900">Top 20 des parrains</h3>
            </div>

            @if ($leaderboard->isEmpty())
                <div class="text-center py-16">
                    <div class="w-16 h-16 mx-auto bg-gray-100 rounded-2xl flex items-center justify-center mb-4">
                        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <h3 class="text-sm font-semibold text-gray-900 mb-1">Pas encore de classement</h3>
                    <p class="text-sm text-gray-500">Soyez le premier à parrainer !</p>
                </div>
            @else
                <ul class="divide-y divide-gray-100">
                    @foreach ($leaderboard as $index => $user)
                        <li
                            class="px-6 py-4 {{ $user->id === auth()->id() ? 'bg-[#fff0f3]/50' : '' }} hover:bg-gray-50/50 transition-colors">
                            <div class="flex items-center">
                                {{-- Rank --}}
                                <div class="w-10 text-center shrink-0">
                                    @if ($index === 0)
                                        <span class="text-2xl">🥇</span>
                                    @elseif($index === 1)
                                        <span class="text-2xl">🥈</span>
                                    @elseif($index === 2)
                                        <span class="text-2xl">🥉</span>
                                    @else
                                        <span class="text-base font-bold text-gray-400">#{{ $index + 1 }}</span>
                                    @endif
                                </div>

                                {{-- User --}}
                                <div class="flex-1 flex items-center gap-3 ml-3">
                                    <div
                                        class="w-10 h-10 {{ $user->id === auth()->id() ? 'bg-[#ffd1da]' : 'bg-gray-100' }} rounded-full flex items-center justify-center">
                                        <span
                                            class="{{ $user->id === auth()->id() ? 'text-[#e00b41]' : 'text-gray-600' }} font-semibold text-sm">{{ substr($user->name, 0, 1) }}</span>
                                    </div>
                                    <div>
                                        <p class="font-medium text-gray-900 text-sm">
                                            {{ $user->name }}
                                            @if ($user->id === auth()->id())
                                                <span
                                                    class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-[#ffd1da] text-[#b5083a] ml-1">Vous</span>
                                            @endif
                                        </p>
                                    </div>
                                </div>

                                {{-- Score --}}
                                <div class="text-right shrink-0">
                                    <p class="text-lg font-bold {{ $index < 3 ? 'text-[#e00b41]' : 'text-gray-700' }}">
                                        {{ $user->completed_referrals }}</p>
                                    <p class="text-[10px] text-gray-500 uppercase tracking-wide">
                                        parrainage{{ $user->completed_referrals > 1 ? 's' : '' }}</p>
                                </div>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>

        {{-- Prizes Section --}}
        <div class="bg-linear-to-br from-amber-50 to-yellow-50 rounded-2xl p-6 border border-amber-200/50">
            <div class="flex items-center gap-2 mb-4">
                <span class="text-xl">🎁</span>
                <h3 class="text-lg font-semibold text-amber-900">Récompenses mensuelles</h3>
            </div>
            <p class="text-sm text-amber-800 mb-5">Les meilleurs parrains du mois remportent des bonus supplémentaires !</p>
            <div class="grid grid-cols-3 gap-3">
                <div class="bg-white rounded-xl p-4 text-center shadow-sm border border-amber-100">
                    <span class="text-3xl block mb-2">🥇</span>
                    <p class="font-bold text-gray-900 text-lg">50 000</p>
                    <p class="text-[10px] text-gray-500 uppercase tracking-wide">FCFA • 1ère place</p>
                </div>
                <div class="bg-white rounded-xl p-4 text-center shadow-sm border border-gray-100">
                    <span class="text-3xl block mb-2">🥈</span>
                    <p class="font-bold text-gray-700 text-lg">25 000</p>
                    <p class="text-[10px] text-gray-500 uppercase tracking-wide">FCFA • 2ème place</p>
                </div>
                <div class="bg-white rounded-xl p-4 text-center shadow-sm border border-gray-100">
                    <span class="text-3xl block mb-2">🥉</span>
                    <p class="font-bold text-amber-700 text-lg">10 000</p>
                    <p class="text-[10px] text-gray-500 uppercase tracking-wide">FCFA • 3ème place</p>
                </div>
            </div>
            <p class="text-xs text-amber-700/70 mt-4 text-center">* Le classement est réinitialisé le 1er de chaque mois</p>
        </div>
    </div>
@endsection
