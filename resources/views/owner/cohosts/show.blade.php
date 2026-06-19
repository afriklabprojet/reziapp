@extends('layouts.owner')

@section('title', 'Co-hôte — ' . $cohost->name . ' - Rezi App')

@section('owner-content')
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        {{-- Retour --}}
        <a href="{{ route('owner.cohosts.index', $residence) }}"
            class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-[#F16A00] transition mb-6">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
            Retour aux co-hôtes
        </a>

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            {{-- En-tête profil --}}
            <div class="p-6 border-b border-gray-100">
                <div class="flex items-start justify-between">
                    <div class="flex items-center gap-4">
                        @php
                            $gradients = ['from-[#FF8A1F] to-pink-500', 'from-blue-400 to-indigo-500', 'from-emerald-400 to-teal-500', 'from-purple-400 to-pink-500', 'from-amber-400 to-[#F16A00]'];
                            $gradient = $gradients[($cohost->id ?? 0) % count($gradients)];
                        @endphp
                        <div class="w-12 h-12 rounded-xl bg-linear-to-br {{ $gradient }} flex items-center justify-center">
                            <span class="text-white font-bold text-lg">{{ strtoupper(substr($cohost->name, 0, 1)) }}</span>
                        </div>
                        <div>
                            <h1 class="text-xl font-extrabold text-gray-900">{{ $cohost->name }}</h1>
                            <p class="text-sm text-gray-500">{{ $cohost->email }}</p>
                        </div>
                    </div>
                    @switch($cohost->status)
                        @case('accepted')
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 bg-[#FFE7D1] text-[#CC5A00] text-xs font-semibold rounded-full">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                Actif
                            </span>
                        @break
                        @case('pending')
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 bg-amber-100 text-amber-700 text-xs font-semibold rounded-full">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                En attente
                            </span>
                        @break
                        @case('declined')
                            <span class="px-2.5 py-1 bg-red-50 text-red-600 text-xs font-semibold rounded-full">Refusé</span>
                        @break
                        @case('revoked')
                            <span class="px-2.5 py-1 bg-gray-100 text-gray-600 text-xs font-semibold rounded-full">Révoqué</span>
                        @break
                    @endswitch
                </div>
            </div>

            <div class="p-6 space-y-6">
                {{-- Infos --}}
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <span class="text-[11px] text-gray-400 font-semibold uppercase tracking-wider">Résidence</span>
                        <p class="font-semibold text-sm text-gray-900 mt-1">{{ $residence->name }}</p>
                    </div>
                    @if ($cohost->phone)
                        <div>
                            <span class="text-[11px] text-gray-400 font-semibold uppercase tracking-wider">Téléphone</span>
                            <p class="font-semibold text-sm text-gray-900 mt-1">{{ $cohost->phone }}</p>
                        </div>
                    @endif
                    <div>
                        <span class="text-[11px] text-gray-400 font-semibold uppercase tracking-wider">Invité le</span>
                        <p class="font-semibold text-sm text-gray-900 mt-1">{{ $cohost->invited_at?->format('d/m/Y') ?? $cohost->created_at->format('d/m/Y') }}</p>
                    </div>
                    @if ($cohost->commission_percent)
                        <div>
                            <span class="text-[11px] text-gray-400 font-semibold uppercase tracking-wider">Commission</span>
                            <p class="font-bold text-sm text-[#CC5A00] mt-1">{{ $cohost->commission_percent }}%</p>
                        </div>
                    @endif
                </div>

                {{-- Permissions --}}
                <div class="pt-5 border-t border-gray-100">
                    <div class="flex items-center justify-between mb-3">
                        <h2 class="text-[11px] text-gray-400 font-semibold uppercase tracking-wider">Permissions</h2>
                        @if ($cohost->status === 'accepted')
                            <a href="{{ route('owner.cohosts.edit', [$residence, $cohost]) }}"
                                class="text-xs text-[#F16A00] hover:text-[#CC5A00] font-semibold transition">Modifier</a>
                        @endif
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        @php
                            $permissions = [
                                'can_respond_messages' => ['label' => 'Répondre aux messages', 'icon' => 'M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z'],
                                'can_manage_calendar' => ['label' => 'Gérer le calendrier', 'icon' => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z'],
                                'can_manage_pricing' => ['label' => 'Gérer les tarifs', 'icon' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
                                'can_edit_listing' => ['label' => 'Modifier l\'annonce', 'icon' => 'M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z'],
                                'can_accept_bookings' => ['label' => 'Accepter les réservations', 'icon' => 'M5 13l4 4L19 7'],
                                'can_view_earnings' => ['label' => 'Voir les revenus', 'icon' => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z'],
                            ];
                        @endphp
                        @foreach ($permissions as $key => $perm)
                            <div class="flex items-center gap-2.5 text-sm">
                                @if ($cohost->$key)
                                    <div class="w-5 h-5 rounded-full bg-[#FFE7D1] flex items-center justify-center shrink-0">
                                        <svg class="w-3 h-3 text-[#CC5A00]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                    </div>
                                    <span class="text-gray-700 text-sm">{{ $perm['label'] }}</span>
                                @else
                                    <div class="w-5 h-5 rounded-full bg-gray-100 flex items-center justify-center shrink-0">
                                        <svg class="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </div>
                                    <span class="text-gray-400 text-sm">{{ $perm['label'] }}</span>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Notes --}}
                @if ($cohost->notes)
                    <div class="pt-5 border-t border-gray-100">
                        <h2 class="text-[11px] text-gray-400 font-semibold uppercase tracking-wider mb-2">Notes</h2>
                        <p class="text-sm text-gray-700">{{ $cohost->notes }}</p>
                    </div>
                @endif

                {{-- Activités récentes --}}
                @if ($cohost->activities && $cohost->activities->count())
                    <div class="pt-5 border-t border-gray-100">
                        <h2 class="text-[11px] text-gray-400 font-semibold uppercase tracking-wider mb-3">Activités récentes</h2>
                        <div class="space-y-3">
                            @foreach ($cohost->activities as $activity)
                                <div class="flex items-start gap-3 text-sm">
                                    <div class="w-2 h-2 rounded-full bg-[#FF8A1F] mt-1.5 shrink-0"></div>
                                    <div>
                                        <p class="text-gray-700">{{ $activity->description }}</p>
                                        <p class="text-[11px] text-gray-400">{{ $activity->created_at->diffForHumans() }}</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            {{-- Actions footer --}}
            @if ($cohost->status === 'accepted')
                <div class="p-6 border-t border-gray-100 flex items-center justify-between">
                    <a href="{{ route('owner.cohosts.edit', [$residence, $cohost]) }}"
                        class="px-5 py-2.5 bg-gray-100 text-gray-700 text-sm font-semibold rounded-xl hover:bg-gray-200 transition">
                        Modifier les permissions
                    </a>
                    <form action="{{ route('owner.cohosts.revoke', [$residence, $cohost]) }}" method="POST"
                         data-confirm='Révoquer l\'accès de {{ $cohost->name }} ?'>
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="text-sm text-red-600 hover:text-red-700 font-semibold transition">
                            Révoquer l'accès
                        </button>
                    </form>
                </div>
            @elseif($cohost->status === 'pending')
                <div class="p-6 border-t border-gray-100 flex items-center justify-between">
                    <form action="{{ route('owner.cohosts.resend', [$residence, $cohost]) }}" method="POST">
                        @csrf
                        <button type="submit"
                            class="px-5 py-2.5 bg-gray-100 text-gray-700 text-sm font-semibold rounded-xl hover:bg-gray-200 transition">
                            Renvoyer l'invitation
                        </button>
                    </form>
                    <form action="{{ route('owner.cohosts.destroy', [$residence, $cohost]) }}" method="POST"
                         data-confirm='Supprimer cette invitation ?'>
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-sm text-red-600 hover:text-red-700 font-semibold transition">
                            Supprimer
                        </button>
                    </form>
                </div>
            @endif
        </div>
    </div>
@endsection
