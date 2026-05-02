@extends('layouts.client', ['sidebarActive' => 'payments'])

@section('title', 'Mes paiements - REZI')

@section('client-content')
    {{-- En-tête --}}
    <div class="mb-8">
        <h1 class="text-2xl font-bold text-gray-900">Mes paiements</h1>
        <p class="text-gray-600">Historique de toutes vos transactions sur REZI</p>
    </div>

    {{-- Statistiques --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
        <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-900">{{ $paymentStats['total'] }}</p>
                    <p class="text-xs text-gray-500">Transactions</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div>
                    <p class="text-lg font-bold text-green-600">
                        {{ number_format($paymentStats['total_paid'], 0, ',', ' ') }}<span class="text-xs font-normal ml-0.5">FCFA</span>
                    </p>
                    <p class="text-xs text-gray-500">Total payé</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-amber-100 rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-amber-600">{{ $paymentStats['pending'] }}</p>
                    <p class="text-xs text-gray-500">En attente</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-red-100 rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-red-600">{{ $paymentStats['failed'] }}</p>
                    <p class="text-xs text-gray-500">Échoués</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Filtres --}}
    <div class="flex flex-wrap gap-2 mb-6">
        @foreach ([
            'all'        => ['label' => 'Tous', 'color' => 'bg-[#ff385c] text-white', 'inactive' => 'bg-white text-gray-700 border border-gray-200 hover:bg-gray-50'],
            'completed'  => ['label' => 'Payés', 'color' => 'bg-green-500 text-white', 'inactive' => 'bg-white text-gray-700 border border-gray-200 hover:bg-gray-50'],
            'pending'    => ['label' => 'En attente', 'color' => 'bg-amber-500 text-white', 'inactive' => 'bg-white text-gray-700 border border-gray-200 hover:bg-gray-50'],
            'failed'     => ['label' => 'Échoués', 'color' => 'bg-red-500 text-white', 'inactive' => 'bg-white text-gray-700 border border-gray-200 hover:bg-gray-50'],
            'refunded'   => ['label' => 'Remboursés', 'color' => 'bg-purple-500 text-white', 'inactive' => 'bg-white text-gray-700 border border-gray-200 hover:bg-gray-50'],
        ] as $filterKey => $filterConfig)
            <a href="{{ route('client.payments.history', $filterKey !== 'all' ? ['status' => $filterKey] : []) }}"
                class="px-4 py-2 rounded-full text-sm font-medium transition {{ $status === $filterKey ? $filterConfig['color'] : $filterConfig['inactive'] }}">
                {{ $filterConfig['label'] }}
            </a>
        @endforeach
    </div>

    {{-- Liste des paiements --}}
    @if ($payments->count() > 0)
        <div class="space-y-3">
            @foreach ($payments as $payment)
                @php
                    $statusConfig = match($payment->status) {
                        'completed'      => ['bg' => 'bg-green-100', 'text' => 'text-green-700', 'label' => 'Payé'],
                        'pending'        => ['bg' => 'bg-amber-100', 'text' => 'text-amber-700', 'label' => 'En attente'],
                        'processing'     => ['bg' => 'bg-blue-100', 'text' => 'text-blue-700', 'label' => 'En cours'],
                        'failed'         => ['bg' => 'bg-red-100', 'text' => 'text-red-700', 'label' => 'Échoué'],
                        'cancelled'      => ['bg' => 'bg-gray-100', 'text' => 'text-gray-700', 'label' => 'Annulé'],
                        'refunded'       => ['bg' => 'bg-purple-100', 'text' => 'text-purple-700', 'label' => 'Remboursé'],
                        'partial_refund' => ['bg' => 'bg-purple-100', 'text' => 'text-purple-600', 'label' => 'Remb. partiel'],
                        default          => ['bg' => 'bg-gray-100', 'text' => 'text-gray-600', 'label' => ucfirst($payment->status)],
                    };
                    $typeLabel = match($payment->type) {
                        'booking'           => 'Réservation',
                        'deposit'           => 'Caution',
                        'extension'         => 'Extension',
                        'penalty'           => 'Pénalité',
                        'refund'            => 'Remboursement',
                        'subscription'      => 'Abonnement',
                        'insurance'         => 'Assurance',
                        'additional_service'=> 'Service additionnel',
                        default             => ucfirst($payment->type),
                    };
                @endphp
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 hover:shadow-md transition">
                    <div class="flex flex-col sm:flex-row sm:items-center gap-4">
                        {{-- Icône type --}}
                        <div class="w-12 h-12 bg-[#fff0f3] rounded-xl flex items-center justify-center shrink-0">
                            @if ($payment->type === 'deposit')
                                <svg class="w-6 h-6 text-[#ff4d6d]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                </svg>
                            @elseif ($payment->type === 'refund')
                                <svg class="w-6 h-6 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" />
                                </svg>
                            @else
                                <svg class="w-6 h-6 text-[#ff385c]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z" />
                                </svg>
                            @endif
                        </div>

                        {{-- Infos principale --}}
                        <div class="flex-1 min-w-0">
                            <div class="flex items-start justify-between gap-3 flex-wrap">
                                <div>
                                    <div class="flex items-center gap-2 mb-1">
                                        <span class="px-2 py-0.5 {{ $statusConfig['bg'] }} {{ $statusConfig['text'] }} text-xs font-medium rounded-full">
                                            {{ $statusConfig['label'] }}
                                        </span>
                                        <span class="px-2 py-0.5 bg-gray-100 text-gray-600 text-xs rounded-full">{{ $typeLabel }}</span>
                                    </div>
                                    @if ($payment->booking?->residence)
                                        <p class="font-medium text-gray-900 truncate">
                                            {{ $payment->booking->residence->title }}
                                        </p>
                                        <p class="text-sm text-gray-500">
                                            {{ $payment->booking->residence->commune }}
                                            @if ($payment->booking->check_in)
                                                •
                                                {{ $payment->booking->check_in->translatedFormat('d M Y') }}
                                                →
                                                {{ $payment->booking->check_out->translatedFormat('d M Y') }}
                                            @endif
                                        </p>
                                    @else
                                        <p class="font-medium text-gray-900">{{ $typeLabel }}</p>
                                    @endif
                                </div>

                                {{-- Montant --}}
                                <div class="text-right shrink-0">
                                    <p class="text-xl font-bold {{ $payment->type === 'refund' ? 'text-purple-600' : 'text-gray-900' }}">
                                        {{ $payment->type === 'refund' ? '+ ' : '' }}{{ number_format($payment->total_amount, 0, ',', ' ') }}
                                        <span class="text-sm font-normal text-gray-500">{{ $payment->currency ?? 'FCFA' }}</span>
                                    </p>
                                    @if ($payment->fee > 0)
                                        <p class="text-xs text-gray-400">dont {{ number_format($payment->fee, 0, ',', ' ') }} FCFA de frais</p>
                                    @endif
                                </div>
                            </div>

                            {{-- Métadonnées --}}
                            <div class="flex items-center gap-4 mt-2 flex-wrap">
                                <span class="text-xs text-gray-400">
                                    Réf: <span class="font-mono text-gray-600">{{ $payment->reference }}</span>
                                </span>
                                @if ($payment->completed_at)
                                    <span class="text-xs text-gray-400">
                                        Payé le {{ $payment->completed_at->translatedFormat('d M Y à H:i') }}
                                    </span>
                                @elseif ($payment->initiated_at)
                                    <span class="text-xs text-gray-400">
                                        Initié {{ $payment->initiated_at->diffForHumans() }}
                                    </span>
                                @endif
                                @if ($payment->failure_reason && $payment->status === 'failed')
                                    <span class="text-xs text-red-500">{{ $payment->failure_reason }}</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Pagination --}}
        @if ($payments->hasPages())
            <div class="mt-6">
                {{ $payments->appends(request()->query())->links() }}
            </div>
        @endif

    @else
        {{-- Empty state --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-12 text-center">
            <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z" />
                </svg>
            </div>
            <h3 class="text-lg font-semibold text-gray-900 mb-2">Aucun paiement</h3>
            <p class="text-gray-500 mb-6">
                @if ($status !== 'all')
                    Aucun paiement avec ce statut.
                @else
                    Vous n'avez pas encore effectué de paiement sur REZI.
                @endif
            </p>
            @if ($status !== 'all')
                <a href="{{ route('client.payments.history') }}"
                    class="inline-flex items-center px-4 py-2 bg-white border border-gray-200 text-gray-700 rounded-lg hover:bg-gray-50 transition text-sm font-medium">
                    Voir tous les paiements
                </a>
            @else
                <a href="{{ route('residences.index') }}"
                    class="inline-flex items-center px-6 py-3 bg-[#ff385c] hover:bg-[#e00b41] text-white font-medium rounded-lg transition">
                    Explorer les résidences
                </a>
            @endif
        </div>
    @endif
@endsection
