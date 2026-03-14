@extends('layouts.owner')

@section('title', 'Réservation #' . ($booking->reference ?? $booking->id) . ' | REZI')

@section('owner-content')
    @php
        $statusConfig = match ($booking->status) {
            'pending' => ['bg-amber-100 text-amber-700 border-amber-200', 'En attente', 'amber'],
            'confirmed' => ['bg-green-100 text-green-700 border-green-200', 'Confirmée', 'green'],
            'completed' => ['bg-blue-100 text-blue-700 border-blue-200', 'Terminée', 'blue'],
            'cancelled_by_user' => ['bg-red-100 text-red-700 border-red-200', 'Annulée (client)', 'red'],
            'cancelled_by_owner' => ['bg-red-100 text-red-700 border-red-200', 'Annulée (proprio)', 'red'],
            default => ['bg-gray-100 text-gray-600 border-gray-200', ucfirst($booking->status), 'gray'],
        };
        $avatarColors = [
            'from-orange-400 to-orange-500',
            'from-blue-400 to-blue-500',
            'from-purple-400 to-purple-500',
            'from-green-400 to-green-500',
            'from-pink-400 to-pink-500',
        ];
        $avatarColor = $avatarColors[($booking->user_id ?? 0) % count($avatarColors)];
    @endphp

    <div class="min-h-screen bg-gray-50/50">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8">

            {{-- Breadcrumb --}}
            <a href="{{ route('owner.bookings.index') }}"
                class="inline-flex items-center text-sm text-gray-500 hover:text-gray-700 font-medium gap-1.5 mb-6">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                </svg>
                Retour aux réservations
            </a>

            {{-- Flash --}}
            @if (session('success'))
                <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)"
                    x-transition:leave="transition ease-in duration-300" x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0"
                    class="mb-6 p-4 bg-green-50 border border-green-100 text-green-700 rounded-2xl text-sm font-medium flex items-center gap-2">
                    <svg class="w-5 h-5 text-green-500 shrink-0" fill="none" stroke="currentColor" stroke-width="2"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                    {{ session('success') }}
                </div>
            @endif
            @if ($errors->any())
                <div class="mb-6 p-4 bg-red-50 border border-red-100 text-red-700 rounded-2xl text-sm font-medium">
                    {{ $errors->first() }}
                </div>
            @endif

            {{-- ============================== HEADER CARD ============================== --}}
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden mb-6">
                <div class="p-6 sm:p-8">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <div>
                            <div class="flex items-center gap-3 mb-1">
                                <h1 class="text-xl font-extrabold text-gray-900 tracking-tight">
                                    Réservation #{{ $booking->reference ?? $booking->id }}
                                </h1>
                                <span class="px-3 py-1 rounded-lg text-xs font-bold border {{ $statusConfig[0] }}">
                                    {{ $statusConfig[1] }}
                                </span>
                            </div>
                            <p class="text-sm text-gray-500">
                                Créée {{ $booking->created_at->diffForHumans() }}
                                <span class="text-gray-300 mx-1">·</span>
                                {{ $booking->created_at->format('d/m/Y à H:i') }}
                            </p>
                        </div>
                        {{-- Quick actions --}}
                        <div class="flex items-center gap-2">
                            @if ($booking->status === 'pending')
                                <form action="{{ route('owner.bookings.confirm', $booking) }}" method="POST">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit"
                                        class="inline-flex items-center gap-2 px-5 py-2.5 bg-green-500 text-white text-sm font-bold rounded-xl hover:bg-green-600 transition-colors shadow-sm">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="m4.5 12.75 6 6 9-13.5" />
                                        </svg>
                                        Confirmer
                                    </button>
                                </form>
                            @endif
                            @if (in_array($booking->status, ['pending', 'confirmed']))
                                <button type="button"
                                    onclick="document.getElementById('cancel-section').classList.toggle('hidden')"
                                    class="inline-flex items-center gap-2 px-4 py-2.5 bg-white text-red-600 text-sm font-bold rounded-xl border border-red-200 hover:bg-red-50 transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                                    </svg>
                                    Annuler
                                </button>
                            @endif
                        </div>
                    </div>

                    {{-- Cancel form (hidden by default) --}}
                    @if (in_array($booking->status, ['pending', 'confirmed']))
                        <div id="cancel-section" class="hidden mt-4 p-4 bg-red-50 rounded-xl border border-red-100">
                            <form action="{{ route('owner.bookings.cancel', $booking) }}" method="POST">
                                @csrf
                                @method('PUT')
                                <p class="text-sm font-bold text-red-700 mb-2">Annuler cette réservation</p>
                                <textarea name="reason" rows="2" required
                                    class="w-full text-sm rounded-xl border border-red-200 focus:border-red-300 focus:ring focus:ring-red-100 p-3 mb-2"
                                    placeholder="Raison de l'annulation (obligatoire)…"></textarea>
                                <button type="submit"
                                    class="px-4 py-2 bg-red-500 text-white text-sm font-bold rounded-xl hover:bg-red-600 transition-colors">
                                    Confirmer l'annulation
                                </button>
                            </form>
                        </div>
                    @endif
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                {{-- ============================== COLONNE PRINCIPALE ============================== --}}
                <div class="lg:col-span-2 space-y-6">

                    {{-- Dates & Séjour --}}
                    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
                        <h2 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-4">Séjour</h2>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                            <div class="bg-gray-50 rounded-xl p-4 text-center">
                                <p class="text-[11px] text-gray-400 font-medium mb-1">Arrivée</p>
                                <p class="text-sm font-bold text-gray-900">
                                    {{ $booking->check_in ? \Carbon\Carbon::parse($booking->check_in)->translatedFormat('d M Y') : '—' }}
                                </p>
                            </div>
                            <div class="bg-gray-50 rounded-xl p-4 text-center">
                                <p class="text-[11px] text-gray-400 font-medium mb-1">Départ</p>
                                <p class="text-sm font-bold text-gray-900">
                                    {{ $booking->check_out ? \Carbon\Carbon::parse($booking->check_out)->translatedFormat('d M Y') : '—' }}
                                </p>
                            </div>
                            <div class="bg-gray-50 rounded-xl p-4 text-center">
                                <p class="text-[11px] text-gray-400 font-medium mb-1">Nuits</p>
                                <p class="text-sm font-bold text-gray-900">{{ $booking->nights ?? '—' }}</p>
                            </div>
                            <div class="bg-gray-50 rounded-xl p-4 text-center">
                                <p class="text-[11px] text-gray-400 font-medium mb-1">Voyageurs</p>
                                <p class="text-sm font-bold text-gray-900">{{ $booking->guests ?? '—' }}</p>
                            </div>
                        </div>
                    </div>

                    {{-- Voyageur --}}
                    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
                        <h2 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-4">Voyageur</h2>
                        @if ($booking->user)
                            <div class="flex items-center gap-4 mb-4">
                                <div
                                    class="w-14 h-14 rounded-full bg-linear-to-br {{ $avatarColor }} flex items-center justify-center text-white font-bold text-xl shadow-lg">
                                    {{ strtoupper(substr($booking->user->name ?? 'U', 0, 1)) }}
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-base font-bold text-gray-900">
                                        {{ $booking->user->full_name ?? $booking->user->name }}
                                    </p>
                                    <p class="text-sm text-gray-500">{{ $booking->user->email }}</p>
                                    <p class="text-xs text-gray-400 mt-0.5">
                                        Membre depuis {{ $booking->user->created_at?->translatedFormat('F Y') ?? '—' }}
                                    </p>
                                </div>
                            </div>

                            {{-- Contact buttons --}}
                            <div class="flex flex-wrap gap-2">
                                @php
                                    $phone = $booking->user->phone ?? null;
                                @endphp
                                @if ($phone)
                                    <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $phone) }}" target="_blank"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-green-500 text-white text-xs font-bold rounded-lg hover:bg-green-600 transition-colors">
                                        <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24">
                                            <path
                                                d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z" />
                                        </svg>
                                        WhatsApp
                                    </a>
                                    <a href="tel:{{ $phone }}"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-blue-500 text-white text-xs font-bold rounded-lg hover:bg-blue-600 transition-colors">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25Z" />
                                        </svg>
                                        Appeler
                                    </a>
                                @endif
                                <a href="mailto:{{ $booking->user->email }}"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white text-gray-600 text-xs font-bold rounded-lg border border-gray-200 hover:bg-gray-50 transition-colors">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" />
                                    </svg>
                                    Email
                                </a>
                                <form action="{{ route('chat.start') }}" method="POST" class="inline">
                                    @csrf
                                    <input type="hidden" name="residence_id" value="{{ $booking->residence_id }}">
                                    <input type="hidden" name="user_id" value="{{ $booking->user_id }}">
                                    <button type="submit"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white text-gray-600 text-xs font-bold rounded-lg border border-gray-200 hover:bg-gray-50 transition-colors">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="1.5"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M8.625 12a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 0 1-2.555-.337A5.972 5.972 0 0 1 5.41 20.97a5.969 5.969 0 0 1-.474-.065 4.48 4.48 0 0 0 .978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25Z" />
                                        </svg>
                                        Chat
                                    </button>
                                </form>
                            </div>
                        @else
                            <p class="text-sm text-gray-500">Voyageur inconnu</p>
                        @endif
                    </div>

                    {{-- Message du voyageur --}}
                    @if ($booking->guest_message)
                        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
                            <h2 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">Message du voyageur
                            </h2>
                            <div
                                class="bg-gray-50 rounded-xl p-4 text-sm text-gray-800 leading-relaxed whitespace-pre-wrap border border-gray-100">
                                {{ $booking->guest_message }}</div>
                        </div>
                    @endif

                    {{-- Résidence --}}
                    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
                        <h2 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-4">Résidence</h2>
                        @if ($booking->residence)
                            <a href="{{ route('owner.residences.show', $booking->residence) }}"
                                class="flex items-center gap-4 group">
                                @if ($booking->residence->photos && $booking->residence->photos->isNotEmpty())
                                    <div class="w-20 h-16 rounded-xl overflow-hidden bg-gray-100 shrink-0">
                                        <img loading="lazy"
                                            src="{{ storage_url($booking->residence->photos->first()->path) }}"
                                            alt="" class="w-full h-full object-cover">
                                    </div>
                                @else
                                    <div
                                        class="w-20 h-16 rounded-xl bg-gray-100 flex items-center justify-center shrink-0">
                                        <svg class="w-6 h-6 text-gray-300" fill="none" stroke="currentColor"
                                            stroke-width="1.5" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
                                        </svg>
                                    </div>
                                @endif
                                <div class="flex-1 min-w-0">
                                    <p
                                        class="text-sm font-bold text-gray-900 group-hover:text-orange-600 transition-colors truncate">
                                        {{ $booking->residence->name }}
                                    </p>
                                    @if ($booking->residence->commune)
                                        <p class="text-xs text-gray-500 mt-0.5">
                                            {{ $booking->residence->commune }}
                                            {{ $booking->residence->quartier ? '· ' . $booking->residence->quartier : '' }}
                                        </p>
                                    @endif
                                </div>
                                <svg class="w-4 h-4 text-gray-400 group-hover:text-orange-500 transition-colors shrink-0"
                                    fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                                </svg>
                            </a>
                        @endif
                    </div>

                    {{-- Timeline --}}
                    @if (isset($timeline) && $timeline->isNotEmpty())
                        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
                            <h2 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-4">Historique</h2>
                            <div class="space-y-0">
                                @foreach ($timeline as $i => $event)
                                    <div class="flex gap-3">
                                        <div class="flex flex-col items-center">
                                            <div
                                                class="w-8 h-8 rounded-full bg-{{ $statusConfig[2] }}-50 flex items-center justify-center shrink-0">
                                                @if ($event['icon'] === 'calendar')
                                                    <svg class="w-3.5 h-3.5 text-{{ $statusConfig[2] }}-500"
                                                        fill="none" stroke="currentColor" stroke-width="2"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                                                    </svg>
                                                @elseif($event['icon'] === 'check')
                                                    <svg class="w-3.5 h-3.5 text-green-500" fill="none"
                                                        stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            d="m4.5 12.75 6 6 9-13.5" />
                                                    </svg>
                                                @elseif($event['icon'] === 'banknotes')
                                                    <svg class="w-3.5 h-3.5 text-green-500" fill="none"
                                                        stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm3 0h.008v.008H18V10.5Zm-12 0h.008v.008H6V10.5Z" />
                                                    </svg>
                                                @elseif($event['icon'] === 'x-mark')
                                                    <svg class="w-3.5 h-3.5 text-red-500" fill="none"
                                                        stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            d="M6 18 18 6M6 6l12 12" />
                                                    </svg>
                                                @else
                                                    <svg class="w-3.5 h-3.5 text-gray-400" fill="none"
                                                        stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                                                    </svg>
                                                @endif
                                            </div>
                                            @if (!$loop->last)
                                                <div class="w-px h-6 bg-gray-200"></div>
                                            @endif
                                        </div>
                                        <div class="pb-6">
                                            <p class="text-sm font-semibold text-gray-900">{{ $event['label'] }}</p>
                                            <p class="text-xs text-gray-400">
                                                {{ $event['date']->format('d/m/Y à H:i') }}
                                            </p>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- Avis --}}
                    @if ($booking->review)
                        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
                            <h2 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">Avis du voyageur</h2>
                            <div class="flex items-center gap-1 mb-2">
                                @for ($i = 1; $i <= 5; $i++)
                                    <svg class="w-4 h-4 {{ $i <= ($booking->review->rating ?? 0) ? 'text-amber-400' : 'text-gray-200' }}"
                                        fill="currentColor" viewBox="0 0 20 20">
                                        <path
                                            d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                    </svg>
                                @endfor
                                <span class="text-sm font-bold text-gray-700 ml-1">{{ $booking->review->rating }}/5</span>
                            </div>
                            @if ($booking->review->comment)
                                <p class="text-sm text-gray-700 leading-relaxed">{{ $booking->review->comment }}</p>
                            @endif
                        </div>
                    @endif

                    {{-- Politique d'annulation --}}
                    @if ($booking->cancellationPolicy)
                        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
                            <h2 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">Politique
                                d'annulation</h2>
                            <p class="text-sm font-semibold text-gray-900">
                                {{ $booking->cancellationPolicy->name ?? $booking->cancellationPolicy->type }}
                            </p>
                            @if ($booking->cancellationPolicy->description)
                                <p class="text-xs text-gray-500 mt-1 leading-relaxed">
                                    {{ $booking->cancellationPolicy->description }}</p>
                            @endif
                        </div>
                    @endif
                </div>

                {{-- ============================== SIDEBAR ============================== --}}
                <div class="space-y-6">

                    {{-- Détail financier --}}
                    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
                        <h2 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-4">Détail financier</h2>
                        <div class="space-y-3">
                            @if ($booking->nights && $booking->price_per_night)
                                <div class="flex justify-between text-sm">
                                    <span
                                        class="text-gray-500">{{ number_format($booking->price_per_night, 0, ',', ' ') }}
                                        × {{ $booking->nights }} nuit(s)</span>
                                    <span
                                        class="text-gray-900 font-medium">{{ number_format($booking->subtotal ?? $booking->price_per_night * $booking->nights, 0, ',', ' ') }}
                                        F</span>
                                </div>
                            @elseif($booking->nights)
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-500">{{ $booking->nights }} nuit(s)</span>
                                    <span
                                        class="text-gray-900 font-medium">{{ number_format($booking->subtotal ?? ($booking->total_amount ?? 0), 0, ',', ' ') }}
                                        F</span>
                                </div>
                            @endif

                            @if ($booking->cleaning_fee && $booking->cleaning_fee > 0)
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-500">Frais de ménage</span>
                                    <span class="text-gray-900">{{ number_format($booking->cleaning_fee, 0, ',', ' ') }}
                                        F</span>
                                </div>
                            @endif

                            @if ($booking->service_fee && $booking->service_fee > 0)
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-500">Frais de service</span>
                                    <span class="text-gray-900">{{ number_format($booking->service_fee, 0, ',', ' ') }}
                                        F</span>
                                </div>
                            @endif

                            @if ($booking->taxes && $booking->taxes > 0)
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-500">Taxes</span>
                                    <span class="text-gray-900">{{ number_format($booking->taxes, 0, ',', ' ') }} F</span>
                                </div>
                            @endif

                            @if ($booking->discount_amount && $booking->discount_amount > 0)
                                <div class="flex justify-between text-sm text-green-600">
                                    <span>Réduction</span>
                                    <span>−{{ number_format($booking->discount_amount, 0, ',', ' ') }} F</span>
                                </div>
                            @endif

                            @if ($booking->coupon_discount && $booking->coupon_discount > 0)
                                <div class="flex justify-between text-sm text-green-600">
                                    <span>Coupon
                                        {{ $booking->coupon_code ? '(' . $booking->coupon_code . ')' : '' }}</span>
                                    <span>−{{ number_format($booking->coupon_discount, 0, ',', ' ') }} F</span>
                                </div>
                            @endif

                            <div class="flex justify-between pt-3 border-t border-gray-100">
                                <span class="text-sm font-bold text-gray-900">Total</span>
                                <span class="text-base font-extrabold text-orange-600">
                                    {{ number_format($booking->total_amount ?? 0, 0, ',', ' ') }}
                                    <span class="text-xs font-bold">FCFA</span>
                                </span>
                            </div>
                        </div>
                    </div>

                    {{-- Informations --}}
                    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
                        <h2 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-4">Informations</h2>
                        <dl class="space-y-3">
                            <div class="flex justify-between text-sm">
                                <dt class="text-gray-500">Référence</dt>
                                <dd class="font-mono text-xs font-bold text-gray-900 bg-gray-100 px-2 py-0.5 rounded">
                                    {{ $booking->reference ?? $booking->id }}
                                </dd>
                            </div>
                            @if ($booking->payment_status)
                                <div class="flex justify-between text-sm items-center">
                                    <dt class="text-gray-500">Paiement</dt>
                                    <dd>
                                        @if ($booking->payment_status === 'paid')
                                            <span class="inline-flex items-center gap-1 text-xs font-bold text-green-600">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor"
                                                    stroke-width="2.5" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="m4.5 12.75 6 6 9-13.5" />
                                                </svg>
                                                Payé
                                            </span>
                                        @elseif($booking->payment_status === 'pending')
                                            <span class="text-xs font-bold text-amber-600">En attente</span>
                                        @elseif($booking->payment_status === 'refunded')
                                            <span class="text-xs font-bold text-blue-600">Remboursé</span>
                                        @else
                                            <span
                                                class="text-xs font-bold text-gray-600">{{ ucfirst($booking->payment_status) }}</span>
                                        @endif
                                    </dd>
                                </div>
                            @endif
                            @if ($booking->payment_method)
                                <div class="flex justify-between text-sm">
                                    <dt class="text-gray-500">Méthode</dt>
                                    <dd class="text-xs font-medium text-gray-900">{{ ucfirst($booking->payment_method) }}
                                    </dd>
                                </div>
                            @endif
                            @if ($booking->paid_at)
                                <div class="flex justify-between text-sm">
                                    <dt class="text-gray-500">Payé le</dt>
                                    <dd class="text-xs font-medium text-gray-900">
                                        {{ $booking->paid_at->format('d/m/Y H:i') }}</dd>
                                </div>
                            @endif
                            @if ($booking->owner_notes)
                                <div class="pt-3 border-t border-gray-100">
                                    <dt class="text-xs font-bold text-gray-400 mb-1">Vos notes</dt>
                                    <dd class="text-sm text-gray-700">{{ $booking->owner_notes }}</dd>
                                </div>
                            @endif
                        </dl>
                    </div>

                    {{-- Deadline réponse --}}
                    @if ($booking->status === 'pending' && $booking->owner_response_deadline)
                        @php
                            $deadline = $booking->owner_response_deadline;
                            $isUrgent = $deadline->lt(now()->addHours(6));
                        @endphp
                        <div
                            class="rounded-2xl border p-4 {{ $isUrgent ? 'bg-red-50 border-red-200' : 'bg-amber-50 border-amber-200' }}">
                            <div class="flex items-center gap-2 mb-1">
                                <svg class="w-4 h-4 {{ $isUrgent ? 'text-red-500' : 'text-amber-500' }}" fill="none"
                                    stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                </svg>
                                <p class="text-xs font-bold {{ $isUrgent ? 'text-red-700' : 'text-amber-700' }}">
                                    Réponse requise avant
                                </p>
                            </div>
                            <p class="text-sm font-bold {{ $isUrgent ? 'text-red-900' : 'text-amber-900' }}">
                                {{ $deadline->translatedFormat('d M Y à H:i') }}
                                <span class="font-normal text-xs">({{ $deadline->diffForHumans() }})</span>
                            </p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
