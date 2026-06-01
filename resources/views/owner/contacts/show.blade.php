@extends('layouts.owner')

@section('title', 'Contact - ' . ($contact->user->name ?? 'Détail') . ' | REZI')

@section('owner-content')
    <div class="min-h-screen bg-gray-50/50">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8">

            {{-- Breadcrumb --}}
            <a href="{{ route('owner.contacts.index') }}"
                class="inline-flex items-center text-sm text-gray-500 hover:text-gray-700 font-medium gap-1.5 mb-6">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                </svg>
                Retour aux contacts
            </a>

            {{-- ============================== CARTE PRINCIPALE ============================== --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">

                {{-- Header --}}
                <div class="p-6 sm:p-8 border-b border-gray-100">
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex items-center gap-4">
                            @php
                                $avatarColors = [
                                    'bg-linear-to-br from-[#FF8A1F] to-[#F16A00]',
                                    'bg-linear-to-br from-blue-400 to-blue-500',
                                    'bg-linear-to-br from-purple-400 to-purple-500',
                                    'bg-linear-to-br from-green-400 to-green-500',
                                    'bg-linear-to-br from-pink-400 to-pink-500',
                                ];
                                $avatarColor = $avatarColors[($contact->user?->id ?? 0) % count($avatarColors)];
                            @endphp
                            <div
                                class="w-14 h-14 rounded-full {{ $avatarColor }} flex items-center justify-center text-white font-bold text-xl shadow-lg">
                                {{ strtoupper(substr($contact->user->name ?? 'A', 0, 1)) }}
                            </div>
                            <div>
                                <h1 class="text-xl font-bold text-gray-900">
                                    {{ $contact->user->name ?? 'Utilisateur' }}</h1>
                                <p class="text-sm text-gray-500 mt-0.5">
                                    Demande reçue {{ $contact->created_at->diffForHumans() }}
                                    <span class="text-gray-300 mx-1">·</span>
                                    {{ $contact->created_at->format('d/m/Y à H:i') }}
                                </p>
                            </div>
                        </div>
                        @php
                            $statusBadge = match ($contact->status) {
                                'pending' => ['bg-amber-100 text-amber-700', 'En attente'],
                                'viewed' => ['bg-blue-100 text-blue-700', 'Vu'],
                                'responded' => ['bg-green-100 text-green-700', '✓ Répondu'],
                                'closed' => ['bg-gray-100 text-gray-600', 'Fermé'],
                                default => ['bg-gray-100 text-gray-600', ucfirst($contact->status)],
                            };
                        @endphp
                        <span class="px-3 py-1 rounded-lg text-xs font-bold {{ $statusBadge[0] }}">
                            {{ $statusBadge[1] }}
                        </span>
                    </div>
                </div>

                <div class="p-6 sm:p-8 space-y-6">

                    {{-- Coordonnées visiteur --}}
                    <div>
                        <h2 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">Coordonnées</h2>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div class="bg-gray-50 rounded-xl p-4 flex items-center gap-3">
                                <div class="w-9 h-9 bg-white rounded-lg flex items-center justify-center shadow-sm">
                                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor"
                                        stroke-width="1.5" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-[11px] text-gray-400 font-medium">Nom</p>
                                    <p class="text-sm font-semibold text-gray-900">{{ $contact->user->name ?? '—' }}</p>
                                </div>
                            </div>
                            <div class="bg-gray-50 rounded-xl p-4 flex items-center gap-3">
                                <div class="w-9 h-9 bg-white rounded-lg flex items-center justify-center shadow-sm">
                                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor"
                                        stroke-width="1.5" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" />
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-[11px] text-gray-400 font-medium">Email</p>
                                    <p class="text-sm font-semibold text-gray-900">{{ $contact->user->email ?? '—' }}</p>
                                </div>
                            </div>
                            <div class="bg-gray-50 rounded-xl p-4 flex items-center gap-3">
                                <div class="w-9 h-9 bg-white rounded-lg flex items-center justify-center shadow-sm">
                                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor"
                                        stroke-width="1.5" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25Z" />
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-[11px] text-gray-400 font-medium">Téléphone</p>
                                    <p class="text-sm font-semibold text-gray-900">
                                        {{ $contact->phone ?? ($contact->user->phone ?? '—') }}
                                    </p>
                                </div>
                            </div>
                            <div class="bg-gray-50 rounded-xl p-4 flex items-center gap-3">
                                <div class="w-9 h-9 bg-white rounded-lg flex items-center justify-center shadow-sm">
                                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor"
                                        stroke-width="1.5" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-[11px] text-gray-400 font-medium">Membre depuis</p>
                                    <p class="text-sm font-semibold text-gray-900">
                                        {{ $contact->user->created_at?->translatedFormat('F Y') ?? '—' }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Résidence concernée --}}
                    @if ($contact->residence)
                        <div>
                            <h2 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">Résidence concernée
                            </h2>
                            <a href="{{ route('owner.residences.show', $contact->residence) }}"
                                class="block bg-gray-50 rounded-xl p-4 hover:bg-gray-100 transition-colors group">
                                <div class="flex items-center gap-3">
                                    @if ($contact->residence->photos && $contact->residence->photos->isNotEmpty())
                                        <div class="w-14 h-14 rounded-xl overflow-hidden bg-gray-200 shrink-0">
                                            <img loading="lazy"
                                                src="{{ storage_url($contact->residence->photos->first()->path) }}"
                                                alt="{{ $contact->residence->title ?? 'Résidence' }}" class="w-full h-full object-cover">
                                        </div>
                                    @else
                                        <div
                                            class="w-14 h-14 rounded-xl bg-gray-200 flex items-center justify-center shrink-0">
                                            <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor"
                                                stroke-width="1.5" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
                                            </svg>
                                        </div>
                                    @endif
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-bold text-gray-900 group-hover:text-[#CC5A00] transition-colors truncate">
                                            {{ $contact->residence->name }}</p>
                                        @if ($contact->residence->commune)
                                            <p class="text-xs text-gray-500 mt-0.5">{{ $contact->residence->commune }}
                                            </p>
                                        @endif
                                    </div>
                                    <svg class="w-4 h-4 text-gray-400 group-hover:text-[#F16A00] transition-colors shrink-0"
                                        fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                                    </svg>
                                </div>
                            </a>
                        </div>
                    @endif

                    {{-- Message --}}
                    @if ($contact->message)
                        <div>
                            <h2 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">Message</h2>
                            <div
                                class="bg-gray-50 rounded-xl p-5 text-sm text-gray-800 leading-relaxed whitespace-pre-wrap border border-gray-100">
                                {{ $contact->message }}
                            </div>
                        </div>
                    @endif
                </div>

                {{-- ============================== ACTIONS FOOTER ============================== --}}
                <div class="p-6 sm:p-8 border-t border-gray-100 bg-gray-50/50">
                    <div class="flex flex-wrap items-center gap-3">
                        {{-- Contact actions --}}
                        @if ($contact->phone ?? $contact->user->phone)
                            <a href="tel:{{ $contact->phone ?? $contact->user->phone }}"
                                class="inline-flex items-center gap-2 px-4 py-2.5 bg-blue-500 text-white text-sm font-semibold rounded-xl hover:bg-blue-600 transition-colors shadow-sm">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25Z" />
                                </svg>
                                Appeler
                            </a>
                        @endif

                        @if ($contact->user->email)
                            <a href="mailto:{{ $contact->user->email }}"
                                class="inline-flex items-center gap-2 px-4 py-2.5 bg-white text-gray-700 text-sm font-semibold rounded-xl hover:bg-gray-100 transition-colors border border-gray-200 shadow-sm">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" />
                                </svg>
                                Email
                            </a>
                        @endif

                        {{-- Status actions --}}
                        @if ($contact->status !== 'closed')
                            <div class="ml-auto flex items-center gap-2">
                                @if ($contact->status === 'pending' || $contact->status === 'viewed')
                                    <form action="{{ route('owner.contacts.status', $contact) }}" method="POST">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="status" value="responded">
                                        <button type="submit"
                                            class="inline-flex items-center gap-2 px-4 py-2.5 bg-[#F16A00] text-white text-sm font-semibold rounded-xl hover:bg-[#CC5A00] transition-colors shadow-sm">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="m4.5 12.75 6 6 9-13.5" />
                                            </svg>
                                            Marquer répondu
                                        </button>
                                    </form>
                                @endif
                                <form action="{{ route('owner.contacts.status', $contact) }}" method="POST">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="status" value="closed">
                                    <button type="submit"
                                        class="inline-flex items-center gap-2 px-4 py-2.5 bg-gray-100 text-gray-600 text-sm font-semibold rounded-xl hover:bg-gray-200 transition-colors">
                                        Fermer
                                    </button>
                                </form>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
