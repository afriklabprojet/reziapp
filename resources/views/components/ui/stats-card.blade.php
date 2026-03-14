@props([
    'title' => null,
    'value' => null,
    'change' => null,      // +12%, -5%, etc.
    'changeType' => null,  // increase, decrease
    'icon' => null,        // SVG path ou nom d'icône
    'iconBg' => 'bg-orange-100',
    'iconColor' => 'text-orange-600',
    'href' => null,
])

@php
    $changeClasses = [
        'increase' => 'text-green-600 bg-green-50',
        'decrease' => 'text-red-600 bg-red-50',
    ];
    $changeClass = $changeType ? ($changeClasses[$changeType] ?? '') : '';
    
    $changeIcons = [
        'increase' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11l5-5m0 0l5 5m-5-5v12" />',
        'decrease' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 13l-5 5m0 0l-5-5m5 5V6" />',
    ];
@endphp

<x-ui.card :hover="$href !== null" {{ $attributes }}>
    @if($href)
        <a href="{{ $href }}" class="block">
    @endif
    
    <div class="flex items-start justify-between">
        <div class="flex-1">
            @if($title)
                <p class="text-sm font-medium text-gray-500">{{ $title }}</p>
            @endif
            
            <div class="mt-2 flex items-baseline gap-2">
                @if($value !== null)
                    <p class="text-2xl font-bold text-gray-900">{{ $value }}</p>
                @endif
                
                @if($change)
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium {{ $changeClass }}">
                        @if($changeType)
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                {!! $changeIcons[$changeType] ?? '' !!}
                            </svg>
                        @endif
                        {{ $change }}
                    </span>
                @endif
            </div>
            
            @if($slot->isNotEmpty())
                <div class="mt-2 text-sm text-gray-500">
                    {{ $slot }}
                </div>
            @endif
        </div>
        
        @if($icon)
            <div class="shrink-0 p-3 rounded-xl {{ $iconBg }}">
                @if(str_contains($icon, '<'))
                    {!! $icon !!}
                @else
                    <svg class="w-6 h-6 {{ $iconColor }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        {!! $icon !!}
                    </svg>
                @endif
            </div>
        @endif
    </div>
    
    @if($href)
        </a>
    @endif
</x-ui.card>
