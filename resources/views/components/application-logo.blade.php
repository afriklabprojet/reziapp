@props(['showText' => true, 'size' => 'default'])

@php
    $sizes = [
        'small' => 'h-9',
        'default' => 'h-14',
        'large' => 'h-16',
    ];
    $sizeClass = $sizes[$size] ?? $sizes['default'];
@endphp

<div {{ $attributes->merge(['class' => 'flex items-center']) }}>
    <img loading="lazy" src="{{ asset('images/logo-rezi.png') }}?v=2" alt="Rezi App Logo" class="{{ $sizeClass }} w-auto">
</div>
