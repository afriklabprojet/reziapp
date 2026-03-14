@props([
    'price' => 0,
    'period' => 'mois', // jour, semaine, mois
    'currency' => 'FCFA',
    'size' => 'md',     // sm, md, lg
    'discount' => null, // Prix barré
])

@php
    $sizes = [
        'sm' => [
            'price' => 'text-lg',
            'currency' => 'text-xs',
            'period' => 'text-xs',
        ],
        'md' => [
            'price' => 'text-2xl',
            'currency' => 'text-sm',
            'period' => 'text-sm',
        ],
        'lg' => [
            'price' => 'text-3xl',
            'currency' => 'text-base',
            'period' => 'text-base',
        ],
    ];
    
    $config = $sizes[$size] ?? $sizes['md'];
    
    // Formatage du prix
    $formattedPrice = number_format($price, 0, ',', ' ');
    $formattedDiscount = $discount ? number_format($discount, 0, ',', ' ') : null;
@endphp

<div {{ $attributes->merge(['class' => 'inline-flex items-baseline gap-1']) }}>
    @if($discount)
        <span class="text-gray-400 line-through text-sm mr-1">
            {{ $formattedDiscount }}
        </span>
    @endif
    
    <span class="{{ $config['price'] }} font-bold text-gray-900">
        {{ $formattedPrice }}
    </span>
    
    <span class="{{ $config['currency'] }} text-gray-500 font-medium">
        {{ $currency }}
    </span>
    
    <span class="{{ $config['period'] }} text-gray-400">
        /{{ $period }}
    </span>
</div>
