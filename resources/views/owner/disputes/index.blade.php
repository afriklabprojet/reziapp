@extends('layouts.owner')

@section('title', 'Mes litiges - REZI')

@section('owner-content')
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        {{-- En-tête --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
            <div>
                <h1 class="text-2xl font-extrabold text-gray-900">Mes litiges</h1>
                <p class="mt-1 text-sm text-gray-500">Litiges liés à vos résidences</p>
            </div>
        </div>

        {{-- KPI rapides --}}
        @if ($disputes->count())
            @php
                $allDisputes = $disputes instanceof \Illuminate\Pagination\LengthAwarePaginator ? $disputes : $disputes;
                $openCount = $disputes->where('status', 'open')->count();
                $escalatedCount = $disputes->where('status', 'escalated')->count();
                $resolvedCount = $disputes->where('status', 'resolved')->count();
            @endphp
            <div class="grid grid-cols-3 gap-4 mb-6">
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-lg bg-amber-50 flex items-center justify-center">
                            <svg class="w-4.5 h-4.5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-lg font-extrabold text-amber-600">{{ $openCount }}</p>
                            <p class="text-[11px] text-gray-500">Ouverts</p>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-lg bg-red-50 flex items-center justify-center">
                            <svg class="w-4.5 h-4.5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-lg font-extrabold text-red-600">{{ $escalatedCount }}</p>
                            <p class="text-[11px] text-gray-500">Escaladés</p>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-lg bg-green-50 flex items-center justify-center">
                            <svg class="w-4.5 h-4.5 text-green-600" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-lg font-extrabold text-green-600">{{ $resolvedCount }}</p>
                            <p class="text-[11px] text-gray-500">Résolus</p>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- Liste des litiges --}}
        @if ($disputes->isEmpty())
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-12 text-center">
                <div class="w-16 h-16 bg-gray-100 rounded-2xl flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                    </svg>
                </div>
                <h3 class="text-base font-bold text-gray-900 mb-1">Aucun litige</h3>
                <p class="text-sm text-gray-500 max-w-sm mx-auto">
                    Aucun litige n'a été ouvert pour vos résidences. C'est une bonne nouvelle !
                </p>
            </div>
        @else
            <div class="space-y-3">
                @foreach ($disputes as $dispute)
                    <a href="{{ route('disputes.show', $dispute) }}"
                        class="block bg-white rounded-2xl shadow-sm border border-gray-100 p-5 hover:border-[#FFD0A3] transition group">
                        <div class="flex items-start justify-between gap-4">
                            <div class="flex items-start gap-4 flex-1 min-w-0">
                                {{-- Icône statut --}}
                                @php
                                    $statusConfig = match ($dispute->status) {
                                        'open' => [
                                            'bg' => 'bg-amber-50',
                                            'text' => 'text-amber-600',
                                            'icon' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z',
                                        ],
                                        'escalated' => [
                                            'bg' => 'bg-red-50',
                                            'text' => 'text-red-600',
                                            'icon' =>
                                                'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z',
                                        ],
                                        'resolved' => [
                                            'bg' => 'bg-green-50',
                                            'text' => 'text-green-600',
                                            'icon' => 'M5 13l4 4L19 7',
                                        ],
                                        default => [
                                            'bg' => 'bg-gray-50',
                                            'text' => 'text-gray-600',
                                            'icon' =>
                                                'M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
                                        ],
                                    };
                                @endphp
                                <div
                                    class="w-10 h-10 rounded-xl {{ $statusConfig['bg'] }} flex items-center justify-center shrink-0">
                                    <svg class="w-5 h-5 {{ $statusConfig['text'] }}" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="{{ $statusConfig['icon'] }}" />
                                    </svg>
                                </div>

                                <div class="min-w-0">
                                    <p
                                        class="font-bold text-sm text-gray-900 group-hover:text-[#CC5A00] transition truncate">
                                        {{ $dispute->subject ?? 'Litige #' . $dispute->id }}
                                    </p>
                                    <p class="text-xs text-gray-500 mt-0.5">
                                        {{ $dispute->type ?? 'Général' }}
                                        @if ($dispute->booking?->residence?->name)
                                            — {{ $dispute->booking->residence->name }}
                                        @endif
                                    </p>
                                    <p class="text-[11px] text-gray-400 mt-1">
                                        Ouvert le {{ $dispute->created_at->format('d/m/Y') }}
                                    </p>
                                </div>
                            </div>

                            {{-- Badge statut --}}
                            @php
                                $badgeConfig = match ($dispute->status) {
                                    'open' => ['bg' => 'bg-amber-100', 'text' => 'text-amber-700', 'label' => 'Ouvert'],
                                    'escalated' => [
                                        'bg' => 'bg-red-100',
                                        'text' => 'text-red-700',
                                        'label' => 'Escaladé',
                                    ],
                                    'resolved' => [
                                        'bg' => 'bg-green-100',
                                        'text' => 'text-green-700',
                                        'label' => 'Résolu',
                                    ],
                                    default => [
                                        'bg' => 'bg-gray-100',
                                        'text' => 'text-gray-700',
                                        'label' => ucfirst($dispute->status),
                                    ],
                                };
                            @endphp
                            <span
                                class="inline-flex items-center px-2.5 py-1 {{ $badgeConfig['bg'] }} {{ $badgeConfig['text'] }} text-xs font-semibold rounded-full shrink-0">
                                {{ $badgeConfig['label'] }}
                            </span>
                        </div>
                    </a>
                @endforeach
            </div>

            <div class="mt-6">
                {{ $disputes->links() }}
            </div>
        @endif
    </div>
@endsection
