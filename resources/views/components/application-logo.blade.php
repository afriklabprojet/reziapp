@props(['showText' => true, 'size' => 'default'])

@php
    $sizes = [
        'small' => 'h-8',
        'default' => 'h-10',
        'large' => 'h-12',
    ];
    $sizeClass = $sizes[$size] ?? $sizes['default'];
@endphp

<div {{ $attributes->merge(['class' => 'flex items-center']) }}>
    <img loading="lazy" src="{{ asset('images/logo-rezi.png') }}" alt="Rezi Studio Meublé Faya Logo" class="{{ $sizeClass }} w-auto">
</div>
