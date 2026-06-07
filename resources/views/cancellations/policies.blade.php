@extends('layouts.app')

@section('title', 'Politiques d\'annulation')

@section('content')
    <div class="max-w-4xl mx-auto px-4 py-8">
        <!-- Header -->
        <div class="mb-8 text-center">
            <h1 class="text-2xl font-bold text-gray-900">Politiques d'annulation</h1>
            <p class="text-gray-600 mt-2">Comprenez les conditions de remboursement selon la politique choisie par l'hôte</p>
        </div>

        <!-- Policies -->
        <div class="space-y-6">
            @foreach ($policies as $policy)
                <div class="bg-white rounded-lg shadow-sm border overflow-hidden">
                    <div class="p-6">
                        <div class="flex items-start justify-between mb-4">
                            <div>
                                <div class="flex items-center">
                                    <span
                                        class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-{{ $policy->color }}-100 text-{{ $policy->color }}-800">
                                        {{ $policy->badge }}
                                    </span>
                                </div>
                                <h2 class="text-xl font-semibold text-gray-900 mt-2">{{ $policy->name }}</h2>
                            </div>
                            @if ($policy->code === 'flexible')
                                <svg class="w-12 h-12 text-green-500" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            @elseif($policy->code === 'moderate')
                                <svg class="w-12 h-12 text-yellow-500" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            @else
                                <svg class="w-12 h-12 text-red-500" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                            @endif
                        </div>

                        <p class="text-gray-600 mb-6">{{ $policy->description }}</p>

                        <!-- Refund Timeline -->
                        <div class="bg-gray-50 rounded-lg p-4">
                            <h3 class="font-medium text-gray-900 mb-4">Barème des remboursements</h3>
                            <div class="space-y-3">
                                @php
                                    $rules = collect($policy->refund_rules ?? [])->sortByDesc('days_before');
                                @endphp
                                @foreach ($rules as $rule)
                                    @php
                                        $days = $rule['days_before'] ?? 0;
                                        $percentage = $rule['refund_percent'] ?? 0;
                                    @endphp
                                    <div class="flex items-center">
                                        <div class="w-16 text-right mr-4">
                                            <span
                                                class="text-2xl font-bold {{ $percentage >= 100 ? 'text-green-600' : ($percentage >= 50 ? 'text-yellow-600' : 'text-red-600') }}">
                                                {{ $percentage }}%
                                            </span>
                                        </div>
                                        <div class="flex-1">
                                            <div class="h-2 bg-gray-200 rounded-full overflow-hidden">
                                                <div class="h-full {{ $percentage >= 100 ? 'bg-green-500' : ($percentage >= 50 ? 'bg-yellow-500' : 'bg-red-500') }}"
                                                    style="width: {{ $percentage }}%"></div>
                                            </div>
                                        </div>
                                        <div class="w-40 text-right ml-4 text-sm text-gray-600">
                                            @if ($days == 0)
                                                Le jour de l'arrivée
                                            @elseif($days == 1)
                                                Jusqu'à 24h avant
                                            @else
                                                Jusqu'à {{ $days }} jours avant
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        @php
                            $hasFreeCancellation = collect($policy->refund_rules ?? [])->contains(
                                fn($r) => ($r['refund_percent'] ?? 0) >= 100,
                            );
                        @endphp
                        @if ($hasFreeCancellation)
                            <div class="mt-4 flex items-center text-green-600">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M5 13l4 4L19 7" />
                                </svg>
                                <span class="text-sm">
                                    Annulation gratuite disponible
                                </span>
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Additional Info -->
        <div class="mt-8 bg-blue-50 border border-blue-200 rounded-lg p-6">
            <h3 class="font-semibold text-blue-900 mb-3">Informations importantes</h3>
            <ul class="space-y-2 text-blue-800 text-sm">
                <li class="flex items-start">
                    <svg class="w-5 h-5 mr-2 mt-0.5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Les frais de service Rezi App sont remboursables uniquement en cas d'annulation gratuite.
                </li>
                <li class="flex items-start">
                    <svg class="w-5 h-5 mr-2 mt-0.5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Si un hôte annule, vous recevez un remboursement intégral automatique.
                </li>
                <li class="flex items-start">
                    <svg class="w-5 h-5 mr-2 mt-0.5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    En cas de circonstances exceptionnelles, des conditions spéciales peuvent s'appliquer.
                </li>
                <li class="flex items-start">
                    <svg class="w-5 h-5 mr-2 mt-0.5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Les remboursements sont traités sous 5 à 10 jours ouvrés.
                </li>
            </ul>
        </div>
    </div>
@endsection
