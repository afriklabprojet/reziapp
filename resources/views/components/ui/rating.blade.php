@props([
    'value' => 0,       // Note sur 5
    'max' => 5,
    'count' => null,    // Nombre d'avis
    'size' => 'md',     // sm, md, lg
    'showValue' => true,
    'interactive' => false,
])

@php
    $sizes = [
        'sm' => 'w-3.5 h-3.5',
        'md' => 'w-5 h-5',
        'lg' => 'w-6 h-6',
    ];
    
    $textSizes = [
        'sm' => 'text-xs',
        'md' => 'text-sm',
        'lg' => 'text-base',
    ];
    
    $sizeClass = $sizes[$size] ?? $sizes['md'];
    $textSize = $textSizes[$size] ?? $textSizes['md'];
    
    $fullStars = floor($value);
    $hasHalfStar = ($value - $fullStars) >= 0.5;
    $emptyStars = $max - $fullStars - ($hasHalfStar ? 1 : 0);
@endphp

<div 
    {{ $attributes->merge(['class' => 'inline-flex items-center gap-1']) }}
    @if($interactive) x-data="{ rating: {{ $value }}, hoverRating: 0 }" @endif
>
    {{-- Stars --}}
    <div class="flex items-center gap-0.5">
        @for($i = 1; $i <= $max; $i++)
            @if($interactive)
                <button 
                    type="button"
                    @mouseenter="hoverRating = {{ $i }}"
                    @mouseleave="hoverRating = 0"
                    @click="rating = {{ $i }}; $dispatch('rating-change', { rating: {{ $i }} })"
                    class="focus:outline-none transition-transform hover:scale-110"
                >
                    <svg 
                        class="{{ $sizeClass }} transition-colors"
                        :class="(hoverRating >= {{ $i }} || (!hoverRating && rating >= {{ $i }})) ? 'text-amber-400' : 'text-gray-300'"
                        fill="currentColor" 
                        viewBox="0 0 20 20"
                    >
                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                    </svg>
                </button>
            @else
                {{-- Étoile pleine --}}
                @if($i <= $fullStars)
                    <svg class="{{ $sizeClass }} text-amber-400" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                    </svg>
                {{-- Demi-étoile --}}
                @elseif($hasHalfStar && $i == $fullStars + 1)
                    <div class="relative">
                        <svg class="{{ $sizeClass }} text-gray-300" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                        </svg>
                        <div class="absolute inset-0 overflow-hidden w-1/2">
                            <svg class="{{ $sizeClass }} text-amber-400" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                            </svg>
                        </div>
                    </div>
                {{-- Étoile vide --}}
                @else
                    <svg class="{{ $sizeClass }} text-gray-300" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                    </svg>
                @endif
            @endif
        @endfor
    </div>
    
    {{-- Value and count --}}
    @if($showValue || $count)
        <span class="{{ $textSize }} text-gray-500">
            @if($showValue)
                <span class="font-medium text-gray-700">{{ number_format($value, 1) }}</span>
            @endif
            @if($count)
                <span>({{ $count }} avis)</span>
            @endif
        </span>
    @endif
</div>
