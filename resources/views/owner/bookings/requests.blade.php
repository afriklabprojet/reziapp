@extends('layouts.owner')

@section('title', 'Demandes de réservation | REZI')

@section('owner-content')
    <div class="min-h-screen bg-gray-50/50">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8">

            {{-- Breadcrumb --}}
            <a href="{{ route('owner.bookings.index') }}"
                class="inline-flex items-center text-sm text-gray-500 hover:text-gray-700 font-medium gap-1.5 mb-6">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                </svg>
                Retour aux réservations
            </a>

            {{-- Header --}}
            <div class="mb-6">
                <h1 class="text-2xl font-extrabold text-gray-900 tracking-tight">Demandes de réservation</h1>
                <p class="mt-1 text-sm text-gray-500">Demandes en attente de votre approbation</p>
            </div>

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

            @if ($requests->isEmpty())
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-12 text-center">
                    <div class="w-16 h-16 bg-gray-50 rounded-2xl flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor" stroke-width="1.5"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25Z" />
                        </svg>
                    </div>
                    <h3 class="text-base font-bold text-gray-900 mb-1">Aucune demande</h3>
                    <p class="text-sm text-gray-500">Vous n'avez pas de demande de réservation en attente.</p>
                </div>
            @else
                <div class="space-y-3">
                    @foreach ($requests as $request)
                        @php
                            $avatarColors = [
                                'from-orange-400 to-orange-500',
                                'from-blue-400 to-blue-500',
                                'from-purple-400 to-purple-500',
                                'from-green-400 to-green-500',
                                'from-pink-400 to-pink-500',
                            ];
                            $avatarColor = $avatarColors[($request->user_id ?? 0) % count($avatarColors)];
                        @endphp
                        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                            <div class="p-5">
                                <div class="flex items-start gap-4">
                                    {{-- Avatar --}}
                                    <div
                                        class="w-11 h-11 rounded-full bg-linear-to-br {{ $avatarColor }} flex items-center justify-center text-white font-bold text-sm shadow-lg shrink-0">
                                        {{ strtoupper(substr($request->user->name ?? '?', 0, 1)) }}
                                    </div>

                                    {{-- Content --}}
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-start justify-between gap-3">
                                            <div class="min-w-0">
                                                <h3 class="text-sm font-bold text-gray-900">
                                                    {{ $request->user->full_name ?? ($request->user->name ?? 'Voyageur') }}
                                                </h3>
                                                <p class="text-xs text-gray-500 mt-0.5 truncate">
                                                    {{ $request->residence->name ?? '—' }}
                                                </p>
                                            </div>
                                            <div class="text-right shrink-0">
                                                <p class="text-base font-bold text-gray-900">
                                                    {{ number_format($request->total_amount ?? 0, 0, ',', ' ') }}
                                                    <span class="text-[10px] text-gray-400 font-medium">FCFA</span>
                                                </p>
                                                <p class="text-[11px] text-gray-400 mt-0.5">
                                                    {{ $request->created_at->diffForHumans() }}
                                                </p>
                                            </div>
                                        </div>

                                        {{-- Dates --}}
                                        <div class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-gray-500">
                                            <span class="inline-flex items-center gap-1">
                                                <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor"
                                                    stroke-width="1.5" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                                                </svg>
                                                {{ $request->check_in ? \Carbon\Carbon::parse($request->check_in)->format('d M') : '—' }}
                                                →
                                                {{ $request->check_out ? \Carbon\Carbon::parse($request->check_out)->format('d M Y') : '—' }}
                                            </span>
                                            <span class="inline-flex items-center gap-1">
                                                <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor"
                                                    stroke-width="1.5" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                                                </svg>
                                                {{ $request->guests ?? 1 }} voyageur(s)
                                            </span>
                                        </div>

                                        {{-- Message --}}
                                        @if ($request->message)
                                            <div
                                                class="mt-3 bg-gray-50 rounded-xl p-3 text-sm text-gray-700 leading-relaxed border border-gray-100">
                                                {{ $request->message }}
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            {{-- Footer actions --}}
                            @if ($request->status === 'pending')
                                <div class="px-5 py-3 bg-gray-50/50 border-t border-gray-100 flex items-center justify-end gap-2"
                                    x-data="{ showReason: false }">
                                    {{-- Refuser --}}
                                    <form action="{{ route('owner.bookings.requests.reject', $request) }}" method="POST">
                                        @csrf
                                        @method('PATCH')
                                        <template x-if="!showReason">
                                            <button type="button" @click="showReason = true"
                                                class="inline-flex items-center gap-1.5 px-4 py-2 bg-white text-red-600 text-xs font-bold rounded-xl border border-red-200 hover:bg-red-50 transition-colors">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor"
                                                    stroke-width="2" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M6 18 18 6M6 6l12 12" />
                                                </svg>
                                                Refuser
                                            </button>
                                        </template>
                                        <template x-if="showReason">
                                            <div class="flex items-center gap-2">
                                                <input type="text" name="reason"
                                                    class="text-sm rounded-xl border border-gray-200 px-3 py-2 focus:border-red-300 focus:ring focus:ring-red-100"
                                                    placeholder="Raison du refus" required>
                                                <button type="submit"
                                                    class="px-3 py-2 bg-red-500 text-white text-xs font-bold rounded-xl hover:bg-red-600 transition-colors whitespace-nowrap">
                                                    Confirmer
                                                </button>
                                            </div>
                                        </template>
                                    </form>

                                    {{-- Approuver --}}
                                    <form action="{{ route('owner.bookings.requests.approve', $request) }}" method="POST">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit"
                                            class="inline-flex items-center gap-1.5 px-4 py-2 bg-green-500 text-white text-xs font-bold rounded-xl hover:bg-green-600 transition-colors shadow-sm">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="m4.5 12.75 6 6 9-13.5" />
                                            </svg>
                                            Approuver
                                        </button>
                                    </form>
                                </div>
                            @else
                                <div
                                    class="px-5 py-3 bg-gray-50/50 border-t border-gray-100 flex items-center justify-end">
                                    @if ($request->status === 'approved')
                                        <span class="px-3 py-1 rounded-lg text-xs font-bold bg-green-100 text-green-700">
                                            ✓ Approuvée
                                        </span>
                                    @elseif($request->status === 'rejected')
                                        <span class="px-3 py-1 rounded-lg text-xs font-bold bg-red-100 text-red-700">
                                            Refusée
                                        </span>
                                    @elseif($request->status === 'expired')
                                        <span class="px-3 py-1 rounded-lg text-xs font-bold bg-gray-100 text-gray-600">
                                            Expirée
                                        </span>
                                    @endif
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>

                <div class="mt-6">
                    {{ $requests->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection
