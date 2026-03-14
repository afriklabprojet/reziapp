@props([
    'type' => 'info', // info, success, warning, danger
    'title' => null,
    'dismissible' => false,
])

@php
    $types = [
        'info' => [
            'bg' => 'bg-blue-50 border-blue-200',
            'icon' => 'text-blue-500',
            'title' => 'text-blue-800',
            'text' => 'text-blue-700',
        ],
        'success' => [
            'bg' => 'bg-green-50 border-green-200',
            'icon' => 'text-green-500',
            'title' => 'text-green-800',
            'text' => 'text-green-700',
        ],
        'warning' => [
            'bg' => 'bg-amber-50 border-amber-200',
            'icon' => 'text-amber-500',
            'title' => 'text-amber-800',
            'text' => 'text-amber-700',
        ],
        'danger' => [
            'bg' => 'bg-red-50 border-red-200',
            'icon' => 'text-red-500',
            'title' => 'text-red-800',
            'text' => 'text-red-700',
        ],
    ];

    $config = $types[$type] ?? $types['info'];

    $icons = [
        'info' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />',
        'success' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />',
        'warning' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />',
        'danger' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />',
    ];
@endphp

<div 
    x-data="{ show: true }" 
    x-show="show"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    {{ $attributes->merge(['class' => "rounded-xl border p-4 {$config['bg']}"]) }}
    role="alert"
>
    <div class="flex">
        {{-- Icon --}}
        <div class="shrink-0">
            <svg class="w-5 h-5 {{ $config['icon'] }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                {!! $icons[$type] ?? $icons['info'] !!}
            </svg>
        </div>
        
        {{-- Content --}}
        <div class="ml-3 flex-1">
            @if($title)
                <h3 class="text-sm font-semibold {{ $config['title'] }}">{{ $title }}</h3>
            @endif
            <div class="text-sm {{ $config['text'] }} {{ $title ? 'mt-1' : '' }}">
                {{ $slot }}
            </div>
        </div>
        
        {{-- Dismiss Button --}}
        @if($dismissible)
            <div class="ml-auto pl-3">
                <button 
                    @click="show = false" 
                    class="-mx-1.5 -my-1.5 rounded-lg p-1.5 inline-flex items-center justify-center h-8 w-8 {{ $config['text'] }} hover:bg-white/50 focus:ring-2 focus:ring-offset-2"
                >
                    <span class="sr-only">Fermer</span>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        @endif
    </div>
</div>
