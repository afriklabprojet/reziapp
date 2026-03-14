@props([
    'type' => 'spinner', // spinner, dots, pulse, skeleton
    'size' => 'md',      // sm, md, lg
    'text' => null,
])

@php
    $sizes = [
        'sm' => 'w-4 h-4',
        'md' => 'w-6 h-6',
        'lg' => 'w-8 h-8',
    ];
    
    $sizeClass = $sizes[$size] ?? $sizes['md'];
@endphp

@if($type === 'spinner')
    <div {{ $attributes->merge(['class' => 'flex items-center justify-center gap-2']) }}>
        <svg class="{{ $sizeClass }} animate-spin text-orange-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        @if($text)
            <span class="text-sm text-gray-500">{{ $text }}</span>
        @endif
    </div>

@elseif($type === 'dots')
    <div {{ $attributes->merge(['class' => 'flex items-center justify-center gap-1']) }}>
        <div class="w-2 h-2 bg-orange-500 rounded-full animate-bounce" style="animation-delay: 0ms;"></div>
        <div class="w-2 h-2 bg-orange-500 rounded-full animate-bounce" style="animation-delay: 150ms;"></div>
        <div class="w-2 h-2 bg-orange-500 rounded-full animate-bounce" style="animation-delay: 300ms;"></div>
    </div>

@elseif($type === 'pulse')
    <div {{ $attributes->merge(['class' => 'flex items-center justify-center']) }}>
        <div class="relative">
            <div class="{{ $sizeClass }} bg-orange-500 rounded-full"></div>
            <div class="absolute inset-0 {{ $sizeClass }} bg-orange-500 rounded-full animate-ping opacity-75"></div>
        </div>
    </div>

@elseif($type === 'skeleton')
    {{-- Skeleton card loader --}}
    <div {{ $attributes->merge(['class' => 'animate-pulse']) }}>
        {{ $slot }}
    </div>
@endif
