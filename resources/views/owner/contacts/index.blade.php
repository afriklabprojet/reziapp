@extends('layouts.owner')

@section('title', 'Mes contacts - REZI')

@section('owner-content')
    <div class="min-h-screen bg-gray-50/50">

        {{-- ============================== HEADER ============================== --}}
        <div class="bg-white border-b border-gray-100">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <h1 class="text-xl sm:text-2xl font-bold text-gray-900">Mes contacts</h1>
                        <p class="text-sm text-gray-500 mt-0.5">{{ $contactStats['all'] }}
                            demande{{ $contactStats['all'] > 1 ? 's' : '' }} de contact</p>
                    </div>
                    <a href="{{ route('owner.dashboard') }}"
                        class="inline-flex items-center text-sm text-gray-500 hover:text-gray-700 font-medium gap-1.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                        </svg>
                        Dashboard
                    </a>
                </div>

                {{-- KPIs mini --}}
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mt-5">
                    <div class="bg-gray-50 rounded-xl p-3 flex items-center gap-3">
                        <div class="w-9 h-9 bg-red-100 rounded-lg flex items-center justify-center shrink-0">
                            <svg class="w-4.5 h-4.5 text-red-600" fill="none" stroke="currentColor" stroke-width="2"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-lg font-extrabold text-gray-900">{{ $contactStats['pending'] }}</p>
                            <p class="text-[11px] text-gray-500 font-medium">Non lus</p>
                        </div>
                    </div>
                    <div class="bg-gray-50 rounded-xl p-3 flex items-center gap-3">
                        <div class="w-9 h-9 bg-green-100 rounded-lg flex items-center justify-center shrink-0">
                            <svg class="w-4.5 h-4.5 text-green-600" fill="none" stroke="currentColor" stroke-width="2"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-lg font-extrabold text-gray-900">{{ $responseRate }}%</p>
                            <p class="text-[11px] text-gray-500 font-medium">Taux réponse</p>
                        </div>
                    </div>
                    <div class="bg-gray-50 rounded-xl p-3 flex items-center gap-3">
                        <div class="w-9 h-9 bg-blue-100 rounded-lg flex items-center justify-center shrink-0">
                            <svg class="w-4.5 h-4.5 text-blue-600" fill="none" stroke="currentColor" stroke-width="2"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-lg font-extrabold text-gray-900">
                                {{ $avgResponseTime !== null ? ($avgResponseTime < 1 ? '<1h' : $avgResponseTime . 'h') : '—' }}
                            </p>
                            <p class="text-[11px] text-gray-500 font-medium">Temps moyen</p>
                        </div>
                    </div>
                    <div class="bg-gray-50 rounded-xl p-3 flex items-center gap-3">
                        <div class="w-9 h-9 bg-orange-100 rounded-lg flex items-center justify-center shrink-0">
                            <svg class="w-4.5 h-4.5 text-orange-600" fill="none" stroke="currentColor" stroke-width="2"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-lg font-extrabold text-gray-900">{{ $todayCount }}</p>
                            <p class="text-[11px] text-gray-500 font-medium">Aujourd'hui</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8">

            {{-- ============================== FILTRES + RECHERCHE ============================== --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 mb-6">
                <div class="flex flex-col sm:flex-row gap-3">
                    {{-- Recherche --}}
                    <form method="GET" action="{{ route('owner.contacts.index') }}" class="flex-1 flex gap-3">
                        @if ($status)
                            <input type="hidden" name="status" value="{{ $status }}">
                        @endif
                        <div class="relative flex-1">
                            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none"
                                stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                            </svg>
                            <input type="text" name="search" value="{{ request('search') }}"
                                placeholder="Rechercher un nom, résidence, message..."
                                class="w-full pl-10 pr-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-orange-500/20 focus:border-orange-500 transition-colors">
                        </div>
                        <button type="submit"
                            class="px-4 py-2.5 bg-orange-500 text-white rounded-xl text-sm font-semibold hover:bg-orange-600 transition-colors shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                            </svg>
                        </button>
                    </form>

                    {{-- Pills status --}}
                    <div class="flex flex-wrap gap-1.5">
                        <a href="{{ route('owner.contacts.index', request()->only('search')) }}"
                            class="px-3 py-2 rounded-xl text-xs font-semibold transition-colors {{ !$status ? 'bg-gray-900 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                            Tous <span class="opacity-60">{{ $contactStats['all'] }}</span>
                        </a>
                        <a href="{{ route('owner.contacts.index', array_merge(request()->only('search'), ['status' => 'pending'])) }}"
                            class="px-3 py-2 rounded-xl text-xs font-semibold transition-colors {{ $status === 'pending' ? 'bg-amber-500 text-white' : 'bg-amber-50 text-amber-700 hover:bg-amber-100' }}">
                            <span class="inline-block w-1.5 h-1.5 rounded-full bg-current mr-0.5 opacity-70"></span>
                            Non lus <span class="opacity-60">{{ $contactStats['pending'] }}</span>
                        </a>
                        <a href="{{ route('owner.contacts.index', array_merge(request()->only('search'), ['status' => 'viewed'])) }}"
                            class="px-3 py-2 rounded-xl text-xs font-semibold transition-colors {{ $status === 'viewed' ? 'bg-blue-500 text-white' : 'bg-blue-50 text-blue-700 hover:bg-blue-100' }}">
                            Vus <span class="opacity-60">{{ $contactStats['viewed'] }}</span>
                        </a>
                        <a href="{{ route('owner.contacts.index', array_merge(request()->only('search'), ['status' => 'responded'])) }}"
                            class="px-3 py-2 rounded-xl text-xs font-semibold transition-colors {{ $status === 'responded' ? 'bg-green-500 text-white' : 'bg-green-50 text-green-700 hover:bg-green-100' }}">
                            Répondus <span class="opacity-60">{{ $contactStats['responded'] }}</span>
                        </a>
                    </div>
                </div>
            </div>

            {{-- ============================== LISTE CONTACTS ============================== --}}
            @if ($contacts->isNotEmpty())
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden divide-y divide-gray-50">
                    @foreach ($contacts as $contact)
                        @php
                            $isPending = $contact->status === 'pending';
                            $avatarColors = [
                                'bg-linear-to-br from-orange-400 to-orange-500',
                                'bg-linear-to-br from-blue-400 to-blue-500',
                                'bg-linear-to-br from-purple-400 to-purple-500',
                                'bg-linear-to-br from-green-400 to-green-500',
                                'bg-linear-to-br from-pink-400 to-pink-500',
                                'bg-linear-to-br from-indigo-400 to-indigo-500',
                            ];
                            $avatarColor = $avatarColors[($contact->user?->id ?? 0) % count($avatarColors)];
                        @endphp
                        <div class="p-4 sm:p-5 hover:bg-gray-50/50 transition-colors {{ $isPending ? 'bg-orange-50/20' : '' }}"
                            x-data="{ showMessage: false }">
                            <div class="flex items-start gap-3 sm:gap-4">
                                {{-- Avatar --}}
                                <div
                                    class="w-11 h-11 rounded-full {{ $avatarColor }} flex items-center justify-center text-white font-bold text-sm shrink-0 shadow-sm">
                                    {{ strtoupper(substr($contact->user->name ?? 'A', 0, 1)) }}
                                </div>

                                {{-- Content --}}
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-start justify-between gap-2">
                                        <div class="min-w-0">
                                            <div class="flex items-center gap-2 flex-wrap">
                                                <h3 class="text-sm font-bold text-gray-900">
                                                    {{ $contact->user->name ?? 'Utilisateur' }}
                                                </h3>
                                                @if ($isPending)
                                                    <span
                                                        class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold bg-amber-100 text-amber-700">
                                                        <span
                                                            class="w-1.5 h-1.5 rounded-full bg-amber-400 mr-1 animate-pulse"></span>
                                                        Nouveau
                                                    </span>
                                                @elseif($contact->status === 'viewed')
                                                    <span
                                                        class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold bg-blue-100 text-blue-700">
                                                        Vu
                                                    </span>
                                                @elseif($contact->status === 'responded')
                                                    <span
                                                        class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold bg-green-100 text-green-700">
                                                        ✓ Répondu
                                                    </span>
                                                @endif
                                            </div>
                                            <p class="text-xs text-gray-500 mt-0.5 truncate">
                                                Pour :
                                                <a href="{{ route('owner.residences.show', $contact->residence) }}"
                                                    class="text-orange-600 hover:text-orange-700 font-medium">{{ $contact->residence->name ?? 'Résidence' }}</a>
                                                @if ($contact->residence?->commune)
                                                    <span class="text-gray-400">·
                                                        {{ $contact->residence->commune }}</span>
                                                @endif
                                            </p>
                                        </div>
                                        <span class="text-[11px] text-gray-400 shrink-0 mt-0.5">
                                            {{ $contact->created_at->diffForHumans() }}
                                        </span>
                                    </div>

                                    {{-- Message preview --}}
                                    @if ($contact->message)
                                        <div class="mt-2">
                                            <p class="text-sm text-gray-600 line-clamp-2 cursor-pointer"
                                                @click="showMessage = !showMessage">
                                                {{ $contact->message }}
                                            </p>
                                            <div x-show="showMessage" x-collapse x-cloak
                                                class="mt-2 p-3 bg-gray-50 rounded-xl text-sm text-gray-700 whitespace-pre-wrap border border-gray-100">
                                                {{ $contact->message }}
                                            </div>
                                        </div>
                                    @endif

                                    {{-- Contact info + Actions row --}}
                                    <div class="flex flex-wrap items-center gap-2 mt-3 pt-3 border-t border-gray-100/80">
                                        {{-- Phone / Email badges --}}
                                        @if ($contact->phone ?? $contact->user->phone)
                                            <span
                                                class="inline-flex items-center gap-1 px-2 py-1 bg-gray-50 text-gray-600 rounded-lg text-[11px] font-medium">
                                                <svg class="w-3 h-3 text-gray-400" fill="none" stroke="currentColor"
                                                    stroke-width="2" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25Z" />
                                                </svg>
                                                {{ $contact->phone ?? $contact->user->phone }}
                                            </span>
                                        @endif
                                        @if ($contact->user->email)
                                            <span
                                                class="inline-flex items-center gap-1 px-2 py-1 bg-gray-50 text-gray-600 rounded-lg text-[11px] font-medium truncate max-w-48">
                                                <svg class="w-3 h-3 text-gray-400 shrink-0" fill="none"
                                                    stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" />
                                                </svg>
                                                {{ $contact->user->email }}
                                            </span>
                                        @endif

                                        {{-- Action buttons --}}
                                        <div class="flex items-center gap-1.5 ml-auto">
                                            @if ($contact->phone ?? $contact->user->phone)
                                                <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $contact->phone ?? $contact->user->phone) }}"
                                                    target="_blank"
                                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-green-50 text-green-700 text-[11px] font-semibold rounded-lg hover:bg-green-100 transition-colors"
                                                    title="WhatsApp">
                                                    <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24">
                                                        <path
                                                            d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z" />
                                                    </svg>
                                                    WhatsApp
                                                </a>
                                                <a href="tel:{{ $contact->phone ?? $contact->user->phone }}"
                                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-blue-50 text-blue-700 text-[11px] font-semibold rounded-lg hover:bg-blue-100 transition-colors"
                                                    title="Appeler">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor"
                                                        stroke-width="2" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25Z" />
                                                    </svg>
                                                    Appeler
                                                </a>
                                            @endif

                                            @if ($contact->user->email)
                                                <a href="mailto:{{ $contact->user->email }}"
                                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-gray-50 text-gray-700 text-[11px] font-semibold rounded-lg hover:bg-gray-100 transition-colors border border-gray-200"
                                                    title="Email">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor"
                                                        stroke-width="2" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" />
                                                    </svg>
                                                    Email
                                                </a>
                                            @endif

                                            {{-- Marquer comme traité --}}
                                            @if ($contact->status !== 'responded')
                                                <form action="{{ route('owner.contacts.respond', $contact) }}"
                                                    method="POST" class="inline">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit"
                                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-orange-50 text-orange-700 text-[11px] font-semibold rounded-lg hover:bg-orange-100 transition-colors"
                                                        title="Marquer comme répondu">
                                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor"
                                                            stroke-width="2" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                d="m4.5 12.75 6 6 9-13.5" />
                                                        </svg>
                                                        Traité
                                                    </button>
                                                </form>
                                            @else
                                                <span
                                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-[11px] font-medium text-green-600">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor"
                                                        stroke-width="2" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                                    </svg>
                                                    {{ $contact->responded_at?->diffForHumans() ?? 'Traité' }}
                                                </span>
                                            @endif

                                            {{-- Voir détails --}}
                                            <a href="{{ route('owner.contacts.show', $contact) }}"
                                                class="inline-flex items-center justify-center w-8 h-8 rounded-lg hover:bg-gray-100 transition-colors text-gray-400 hover:text-gray-600"
                                                title="Détails">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                    stroke-width="2" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                                                </svg>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Pagination --}}
                @if ($contacts->hasPages())
                    <div class="mt-6">
                        {{ $contacts->links() }}
                    </div>
                @endif
            @else
                {{-- Empty state --}}
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-12 sm:p-16 text-center">
                    <div
                        class="w-20 h-20 bg-linear-to-br from-orange-100 to-orange-50 rounded-2xl flex items-center justify-center mx-auto mb-5">
                        <svg class="w-10 h-10 text-orange-400" fill="none" stroke="currentColor" stroke-width="1.5"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" />
                        </svg>
                    </div>
                    @if ($status || request('search'))
                        <h3 class="text-lg font-bold text-gray-900 mb-2">Aucun résultat</h3>
                        <p class="text-sm text-gray-500 mb-6 max-w-sm mx-auto">
                            Aucun contact ne correspond à vos critères.
                        </p>
                        <a href="{{ route('owner.contacts.index') }}"
                            class="inline-flex items-center px-5 py-2.5 bg-gray-100 text-gray-700 rounded-xl font-semibold text-sm hover:bg-gray-200 transition-colors gap-2">
                            Voir tous les contacts
                        </a>
                    @else
                        <h3 class="text-lg font-bold text-gray-900 mb-2">Aucun contact</h3>
                        <p class="text-sm text-gray-500 mb-6 max-w-sm mx-auto">
                            Les demandes de contact apparaîtront ici lorsque des visiteurs s'intéresseront à vos annonces.
                        </p>
                        <a href="{{ route('owner.residences.index') }}"
                            class="inline-flex items-center px-5 py-2.5 bg-gray-900 text-white rounded-xl font-semibold text-sm hover:bg-gray-800 transition-colors gap-2">
                            Voir mes annonces
                        </a>
                    @endif
                </div>
            @endif
        </div>
    </div>
@endsection
