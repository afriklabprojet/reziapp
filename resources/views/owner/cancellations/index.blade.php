@extends('layouts.owner')

@section('title', 'Annulations - ReziApp')

@section('owner-content')
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        {{-- En-tête --}}
        <div class="mb-8">
            <h1 class="text-2xl font-extrabold text-gray-900">Annulations</h1>
            <p class="mt-1 text-sm text-gray-500">Annulations concernant vos résidences</p>
        </div>

        {{-- KPI --}}
        @if (isset($stats))
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-lg bg-gray-100 flex items-center justify-center">
                            <svg class="w-4.5 h-4.5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-lg font-extrabold text-gray-900">{{ $stats['total'] ?? 0 }}</p>
                            <p class="text-[11px] text-gray-500">Total</p>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-lg bg-amber-50 flex items-center justify-center">
                            <svg class="w-4.5 h-4.5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-lg font-extrabold text-amber-600">{{ $stats['pending'] ?? 0 }}</p>
                            <p class="text-[11px] text-gray-500">En attente</p>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-lg bg-green-50 flex items-center justify-center">
                            <svg class="w-4.5 h-4.5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-lg font-extrabold text-green-600">{{ $stats['approved'] ?? 0 }}</p>
                            <p class="text-[11px] text-gray-500">Approuvées</p>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-lg bg-red-50 flex items-center justify-center">
                            <svg class="w-4.5 h-4.5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-lg font-extrabold text-red-600">{{ number_format($stats['total_refunded'] ?? 0, 0, ',', ' ') }}</p>
                            <p class="text-[11px] text-gray-500">FCFA remboursés</p>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- Liste des annulations --}}
        @if ($cancellations->isEmpty())
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-12 text-center">
                <div class="w-16 h-16 bg-gray-100 rounded-2xl flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <h3 class="text-base font-bold text-gray-900 mb-1">Aucune annulation</h3>
                <p class="text-sm text-gray-500 max-w-sm mx-auto">
                    Aucun voyageur n'a annulé de réservation sur vos résidences.
                </p>
            </div>
        @else
            <div class="space-y-3">
                @foreach ($cancellations as $cancellation)
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 hover:border-[#FFD0A3] transition">
                        <div class="flex items-start justify-between gap-4">
                            <div class="flex items-start gap-4 flex-1 min-w-0">
                                {{-- Photo résidence --}}
                                @if ($cancellation->booking?->residence?->mainPhoto)
                                    <img loading="lazy"
                                        src="{{ storage_url($cancellation->booking->residence->mainPhoto->path) }}"
                                        alt="{{ $cancellation->booking->residence->title ?? 'Résidence' }}" class="w-14 h-14 object-cover rounded-xl shrink-0">
                                @else
                                    <div class="w-14 h-14 bg-gray-100 rounded-xl flex items-center justify-center shrink-0">
                                        <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                                        </svg>
                                    </div>
                                @endif

                                <div class="min-w-0">
                                    <p class="font-bold text-sm text-gray-900 truncate">
                                        {{ $cancellation->booking?->residence?->title ?? 'Résidence' }}
                                    </p>
                                    <p class="text-xs text-gray-500 mt-0.5">
                                        {{ $cancellation->booking?->check_in?->format('d M Y') }}
                                        →
                                        {{ $cancellation->booking?->check_out?->format('d M Y') }}
                                    </p>
                                    <div class="flex flex-wrap items-center gap-3 mt-2 text-[11px] text-gray-400">
                                        <span class="inline-flex items-center gap-1">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                            </svg>
                                            Par : {{ $cancellation->cancelled_by === 'guest' ? ($cancellation->booking?->user?->name ?? 'Client') : 'Vous' }}
                                        </span>
                                        <span>{{ $cancellation->created_at->format('d/m/Y') }}</span>
                                    </div>
                                </div>
                            </div>

                            <div class="text-right shrink-0">
                                {{-- Badge statut --}}
                                @php
                                    $badgeConfig = match($cancellation->status) {
                                        'approved' => ['bg' => 'bg-green-100', 'text' => 'text-green-700', 'label' => 'Approuvée'],
                                        'pending' => ['bg' => 'bg-amber-100', 'text' => 'text-amber-700', 'label' => 'En attente'],
                                        'rejected' => ['bg' => 'bg-red-100', 'text' => 'text-red-700', 'label' => 'Rejetée'],
                                        default => ['bg' => 'bg-gray-100', 'text' => 'text-gray-700', 'label' => ucfirst($cancellation->status)],
                                    };
                                @endphp
                                <span class="inline-flex items-center px-2.5 py-1 {{ $badgeConfig['bg'] }} {{ $badgeConfig['text'] }} text-xs font-semibold rounded-full">
                                    {{ $badgeConfig['label'] }}
                                </span>
                                @if ($cancellation->refund_amount)
                                    <p class="text-xs font-bold text-green-600 mt-2">
                                        {{ number_format($cancellation->refund_amount, 0, ',', ' ') }} FCFA
                                    </p>
                                    <p class="text-[11px] text-gray-400">remboursés</p>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-6">
                {{ $cancellations->links() }}
            </div>
        @endif
    </div>
@endsection
