@extends('layouts.owner')

@section('title', 'Notifications | ReziApp')

@section('owner-content')
    <div class="min-h-screen bg-gray-50/50">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8">

            {{-- Header --}}
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h1 class="text-2xl font-extrabold text-gray-900 tracking-tight">Notifications</h1>
                    <p class="mt-1 text-sm text-gray-500">
                        @if ($newCount > 0)
                            <span class="text-[#CC5A00] font-semibold">{{ $newCount }} nouvelle(s)</span>
                            · {{ $counts['all'] }} au total
                        @else
                            {{ $counts['all'] }} notification(s) récentes
                        @endif
                    </p>
                </div>
            </div>

            {{-- Filtres par type --}}
            @if ($counts['all'] > 0)
                <div class="flex flex-wrap gap-2 mb-6">
                    @php
                        $typeFilters = [
                            'all' => ['label' => 'Toutes', 'count' => $counts['all']],
                            'contact' => ['label' => 'Contacts', 'count' => $counts['contact']],
                            'booking' => ['label' => 'Réservations', 'count' => $counts['booking']],
                            'approval' => ['label' => 'Approuvées', 'count' => $counts['approval']],
                            'rejection' => ['label' => 'Rejetées', 'count' => $counts['rejection']],
                        ];
                    @endphp
                    @foreach ($typeFilters as $key => $tf)
                        @if ($tf['count'] > 0 || $key === 'all')
                            <a href="{{ route('owner.notifications', ['type' => $key]) }}"
                                class="px-3 py-1.5 rounded-lg text-xs font-bold transition-colors {{ $filter === $key ? 'bg-[#F16A00] text-white shadow-sm' : 'bg-white text-gray-600 border border-gray-200 hover:bg-gray-50' }}">
                                {{ $tf['label'] }}
                                <span
                                    class="ml-1 {{ $filter === $key ? 'text-[#FFD0A3]' : 'text-gray-400' }}">{{ $tf['count'] }}</span>
                            </a>
                        @endif
                    @endforeach
                </div>
            @endif

            {{-- Liste --}}
            @if ($notifications->count() > 0)
                <div class="space-y-2">
                    @foreach ($notifications as $notification)
                        @php
                            $typeStyle = match ($notification['type']) {
                                'contact' => [
                                    'bg' => 'bg-blue-50',
                                    'icon_bg' => 'bg-blue-100',
                                    'icon_color' => 'text-blue-500',
                                ],
                                'booking' => [
                                    'bg' => 'bg-[#FFF4EB]',
                                    'icon_bg' => 'bg-[#FFE7D1]',
                                    'icon_color' => 'text-[#F16A00]',
                                ],
                                'approval' => [
                                    'bg' => 'bg-green-50',
                                    'icon_bg' => 'bg-green-100',
                                    'icon_color' => 'text-green-500',
                                ],
                                'rejection' => [
                                    'bg' => 'bg-red-50',
                                    'icon_bg' => 'bg-red-100',
                                    'icon_color' => 'text-red-500',
                                ],
                                default => [
                                    'bg' => 'bg-gray-50',
                                    'icon_bg' => 'bg-gray-100',
                                    'icon_color' => 'text-gray-500',
                                ],
                            };
                        @endphp
                        <a href="{{ $notification['action_url'] }}"
                            class="block bg-white rounded-2xl border {{ $notification['is_new'] ? 'border-[#FFD0A3] shadow-sm' : 'border-gray-100' }} hover:shadow-md transition-all duration-200 p-4 group">
                            <div class="flex items-start gap-3">
                                {{-- Icon --}}
                                <div
                                    class="w-10 h-10 rounded-xl {{ $typeStyle['icon_bg'] }} flex items-center justify-center shrink-0">
                                    @if ($notification['type'] === 'contact')
                                        <svg class="w-5 h-5 {{ $typeStyle['icon_color'] }}" fill="none"
                                            stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" />
                                        </svg>
                                    @elseif($notification['type'] === 'booking')
                                        <svg class="w-5 h-5 {{ $typeStyle['icon_color'] }}" fill="none"
                                            stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                                        </svg>
                                    @elseif($notification['type'] === 'approval')
                                        <svg class="w-5 h-5 {{ $typeStyle['icon_color'] }}" fill="none"
                                            stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                        </svg>
                                    @elseif($notification['type'] === 'rejection')
                                        <svg class="w-5 h-5 {{ $typeStyle['icon_color'] }}" fill="none"
                                            stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="m9.75 9.75 4.5 4.5m0-4.5-4.5 4.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                        </svg>
                                    @endif
                                </div>

                                {{-- Content --}}
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2">
                                        <h3 class="text-sm font-bold text-gray-900">{{ $notification['title'] }}</h3>
                                        @if ($notification['is_new'])
                                            <span class="w-2 h-2 rounded-full bg-[#F16A00] animate-pulse shrink-0"></span>
                                        @endif
                                    </div>
                                    <p class="text-sm text-gray-600 mt-0.5 line-clamp-2">{{ $notification['message'] }}
                                    </p>
                                    <div class="flex items-center gap-3 mt-2">
                                        <span
                                            class="text-xs font-semibold text-[#F16A00] group-hover:text-[#CC5A00] transition-colors inline-flex items-center gap-1">
                                            {{ $notification['action_text'] }}
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                                            </svg>
                                        </span>
                                        <span class="text-[11px] text-gray-400">
                                            {{ $notification['created_at']->diffForHumans() }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>
            @else
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-12 text-center">
                    <div class="w-16 h-16 bg-gray-50 rounded-2xl flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor" stroke-width="1.5"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" />
                        </svg>
                    </div>
                    <h3 class="text-base font-bold text-gray-900 mb-1">
                        @if ($filter !== 'all')
                            Aucune notification de ce type
                        @else
                            Tout est à jour !
                        @endif
                    </h3>
                    <p class="text-sm text-gray-500">
                        @if ($filter !== 'all')
                            Essayez un autre filtre pour voir d'autres notifications.
                        @else
                            Vous n'avez pas de nouvelle notification pour le moment.
                        @endif
                    </p>
                </div>
            @endif
        </div>
    </div>
@endsection
