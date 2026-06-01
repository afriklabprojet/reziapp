@extends('layouts.owner')

@section('title', 'Co-hôtes - ' . $residence->name . ' - REZI')

@section('owner-content')
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        {{-- Fil d'Ariane --}}
        <nav class="flex items-center gap-2 text-xs text-gray-400 mb-6">
            <a href="{{ route('owner.dashboard') }}" class="hover:text-[#F16A00] transition">Tableau de bord</a>
            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
            <a href="{{ route('owner.residences.show', $residence) }}"
                class="hover:text-[#F16A00] transition">{{ Str::limit($residence->name, 25) }}</a>
            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
            <span class="text-gray-600 font-medium">Co-hôtes</span>
        </nav>

        {{-- En-tête --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
            <div>
                <h1 class="text-2xl font-extrabold text-gray-900">Gestion des co-hôtes</h1>
                <p class="mt-1 text-sm text-gray-500">Déléguez la gestion de votre résidence</p>
            </div>
            <a href="{{ route('owner.cohosts.create', $residence) }}"
                class="inline-flex items-center gap-2 px-5 py-2.5 bg-[#F16A00] text-white text-sm font-semibold rounded-xl hover:bg-[#CC5A00] transition shadow-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                </svg>
                Inviter un co-hôte
            </a>
        </div>

        {{-- Messages flash --}}
        @if (session('success'))
            <div
                class="mb-6 flex items-center gap-3 bg-[#FFF4EB] border border-[#FFD0A3] text-[#A34700] px-4 py-3 rounded-xl text-sm">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                {{ session('success') }}
            </div>
        @endif
        @if (session('error'))
            <div
                class="mb-6 flex items-center gap-3 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl text-sm">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                {{ session('error') }}
            </div>
        @endif

        {{-- Liste des co-hôtes --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            @forelse($coHosts as $cohost)
                <div class="p-5 border-b border-gray-100 last:border-0 hover:bg-gray-50/50 transition group">
                    <div class="flex flex-col md:flex-row md:items-center gap-4">
                        {{-- Avatar & infos --}}
                        <div class="flex items-center gap-4 flex-1">
                            @php
                                $gradients = [
                                    'from-[#FF8A1F] to-pink-500',
                                    'from-blue-400 to-indigo-500',
                                    'from-emerald-400 to-teal-500',
                                    'from-purple-400 to-pink-500',
                                    'from-amber-400 to-[#F16A00]',
                                ];
                                $gradient = $gradients[($cohost->id ?? 0) % count($gradients)];
                            @endphp
                            <div
                                class="w-11 h-11 rounded-xl bg-linear-to-br {{ $gradient }} flex items-center justify-center shrink-0">
                                @if ($cohost->user && $cohost->user->avatar)
                                    <img loading="lazy" src="{{ $cohost->user->avatar }}" alt=""
                                        class="w-11 h-11 rounded-xl object-cover">
                                @else
                                    <span
                                        class="text-white font-bold text-sm">{{ strtoupper(substr($cohost->name, 0, 1)) }}</span>
                                @endif
                            </div>
                            <div class="min-w-0">
                                <h3 class="font-bold text-sm text-gray-900 truncate">{{ $cohost->name }}</h3>
                                <p class="text-xs text-gray-500 truncate">{{ $cohost->email }}</p>
                                @if ($cohost->phone)
                                    <p class="text-[11px] text-gray-400">{{ $cohost->phone }}</p>
                                @endif
                            </div>
                        </div>

                        {{-- Statut --}}
                        <div class="flex items-center gap-3">
                            @switch($cohost->status)
                                @case('accepted')
                                    <span
                                        class="inline-flex items-center gap-1 px-2.5 py-1 bg-[#FFE7D1] text-[#CC5A00] text-xs font-semibold rounded-full">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M5 13l4 4L19 7" />
                                        </svg>
                                        Actif
                                    </span>
                                @break

                                @case('pending')
                                    <span
                                        class="inline-flex items-center gap-1 px-2.5 py-1 bg-amber-100 text-amber-700 text-xs font-semibold rounded-full">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        En attente
                                    </span>
                                    @if ($cohost->isExpired())
                                        <span class="text-[11px] text-red-600 font-medium">Expiré</span>
                                    @endif
                                @break

                                @case('declined')
                                    <span
                                        class="px-2.5 py-1 bg-gray-100 text-gray-600 text-xs font-semibold rounded-full">Refusé</span>
                                @break

                                @case('revoked')
                                    <span
                                        class="px-2.5 py-1 bg-red-50 text-red-600 text-xs font-semibold rounded-full">Révoqué</span>
                                @break
                            @endswitch

                            {{-- Actions --}}
                            <div class="flex items-center gap-1 opacity-60 group-hover:opacity-100 transition">
                                @if ($cohost->status === 'pending')
                                    <form action="{{ route('owner.cohosts.resend', [$residence, $cohost]) }}"
                                        method="POST" class="inline">
                                        @csrf
                                        <button type="submit" class="p-2 rounded-lg hover:bg-gray-100 transition"
                                            title="Renvoyer l'invitation">
                                            <svg class="w-4.5 h-4.5 text-gray-400" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                            </svg>
                                        </button>
                                    </form>
                                @endif

                                <a href="{{ route('owner.cohosts.edit', [$residence, $cohost]) }}"
                                    class="p-2 rounded-lg hover:bg-gray-100 transition" title="Modifier">
                                    <svg class="w-4.5 h-4.5 text-gray-400" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                </a>

                                @if ($cohost->status === 'accepted')
                                    <form action="{{ route('owner.cohosts.revoke', [$residence, $cohost]) }}"
                                        method="POST" class="inline"
                                        onsubmit="return confirm('Révoquer l\'accès de ce co-hôte ?')">
                                        @csrf
                                        <button type="submit" class="p-2 rounded-lg hover:bg-red-50 transition"
                                            title="Révoquer">
                                            <svg class="w-4.5 h-4.5 text-red-400" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                                            </svg>
                                        </button>
                                    </form>
                                @endif

                                <form action="{{ route('owner.cohosts.destroy', [$residence, $cohost]) }}" method="POST"
                                    class="inline" onsubmit="return confirm('Supprimer définitivement ce co-hôte ?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="p-2 rounded-lg hover:bg-red-50 transition"
                                        title="Supprimer">
                                        <svg class="w-4.5 h-4.5 text-red-400" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>

                    {{-- Permissions --}}
                    @if ($cohost->status === 'accepted')
                        <div class="mt-4 pt-4 border-t border-gray-100">
                            <p class="text-[11px] text-gray-400 font-semibold uppercase tracking-wider mb-2">Permissions
                            </p>
                            <div class="flex flex-wrap gap-2">
                                @php
                                    $perms = [
                                        'can_edit_listing' => [
                                            'label' => 'Modifier l\'annonce',
                                            'bg' => 'bg-blue-50',
                                            'text' => 'text-blue-700',
                                            'icon' =>
                                                'M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z',
                                        ],
                                        'can_manage_calendar' => [
                                            'label' => 'Calendrier',
                                            'bg' => 'bg-purple-50',
                                            'text' => 'text-purple-700',
                                            'icon' =>
                                                'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
                                        ],
                                        'can_manage_pricing' => [
                                            'label' => 'Tarification',
                                            'bg' => 'bg-[#FFF4EB]',
                                            'text' => 'text-[#CC5A00]',
                                            'icon' =>
                                                'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
                                        ],
                                        'can_respond_messages' => [
                                            'label' => 'Messages',
                                            'bg' => 'bg-amber-50',
                                            'text' => 'text-amber-700',
                                            'icon' =>
                                                'M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z',
                                        ],
                                        'can_accept_bookings' => [
                                            'label' => 'Réservations',
                                            'bg' => 'bg-pink-50',
                                            'text' => 'text-pink-700',
                                            'icon' => 'M5 13l4 4L19 7',
                                        ],
                                        'can_view_earnings' => [
                                            'label' => 'Revenus',
                                            'bg' => 'bg-indigo-50',
                                            'text' => 'text-indigo-700',
                                            'icon' =>
                                                'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z',
                                        ],
                                    ];
                                @endphp
                                @foreach ($perms as $key => $perm)
                                    @if ($cohost->$key)
                                        <span
                                            class="inline-flex items-center gap-1 px-2 py-1 {{ $perm['bg'] }} {{ $perm['text'] }} text-[11px] font-medium rounded-full">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="{{ $perm['icon'] }}" />
                                            </svg>
                                            {{ $perm['label'] }}
                                        </span>
                                    @endif
                                @endforeach
                            </div>
                            @if ($cohost->commission_percent)
                                <p class="mt-2 text-xs text-gray-500">Commission : <span
                                        class="font-semibold text-gray-700">{{ $cohost->commission_percent }}%</span></p>
                            @endif
                        </div>
                    @endif
                </div>
                @empty
                    <div class="p-12 text-center">
                        <div class="w-16 h-16 bg-gray-100 rounded-2xl flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                        </div>
                        <h3 class="text-base font-bold text-gray-900 mb-1">Aucun co-hôte</h3>
                        <p class="text-sm text-gray-500 mb-6 max-w-sm mx-auto">
                            Invitez un ami, un proche ou un gestionnaire pour vous aider à gérer cette résidence.
                        </p>
                        <a href="{{ route('owner.cohosts.create', $residence) }}"
                            class="inline-flex items-center gap-2 px-5 py-2.5 bg-[#F16A00] text-white text-sm font-semibold rounded-xl hover:bg-[#CC5A00] transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                            </svg>
                            Inviter mon premier co-hôte
                        </a>
                    </div>
                @endforelse
            </div>

            {{-- Encart explicatif --}}
            <div class="mt-8 bg-blue-50 rounded-2xl border border-blue-100 p-6">
                <div class="flex items-start gap-3">
                    <div class="w-9 h-9 rounded-lg bg-blue-100 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-bold text-sm text-blue-800 mb-2">Qu'est-ce qu'un co-hôte ?</h3>
                        <div class="text-xs text-blue-700 space-y-1.5">
                            <p>Un co-hôte est une personne de confiance qui vous aide à gérer votre résidence sur REZI.</p>
                            <ul class="list-disc list-inside space-y-1 ml-1">
                                <li>Répondre aux messages des voyageurs</li>
                                <li>Gérer le calendrier et les disponibilités</li>
                                <li>Modifier les prix selon la saison</li>
                                <li>Accepter ou refuser les demandes de réservation</li>
                            </ul>
                            <p class="pt-1">Vous restez le propriétaire principal et pouvez révoquer l'accès à tout moment.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endsection
