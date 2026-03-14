{{-- 
    Composant Rating/Note — <x-rating :value="4.5" :count="12" />
    Usage :
        <x-rating :value="$residence->average_rating" />
        <x-rating :value="$residence->average_rating" :count="$residence->reviews_count" />
        <x-rating :value="$residence->average_rating" :count="$residence->reviews_count" size="lg" />
--}}
@props([
    'value' => 0,
    'count' => null,
    'size' => 'sm', // sm, md, lg
    'showEmpty' => true,
])

@php
    $sizes = [
        'sm' => ['star' => 'w-3.5 h-3.5', 'text' => 'text-sm', 'count' => 'text-xs'],
        'md' => ['star' => 'w-4 h-4', 'text' => 'text-base', 'count' => 'text-sm'],
        'lg' => ['star' => 'w-5 h-5', 'text' => 'text-lg', 'count' => 'text-base'],
    ];
    $s = $sizes[$size] ?? $sizes['sm'];
@endphp

@if($value > 0 || $showEmpty)
<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1']) }}>
    <svg class="{{ $s['star'] }} text-amber-400" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
    </svg>
    <span class="{{ $s['text'] }} font-semibold text-gray-900">{{ $value > 0 ? number_format($value, 1) : '—' }}</span>
    @if($count !== null)
        <span class="{{ $s['count'] }} text-gray-500">({{ $count }})</span>
    @endif
</span>
@endif
