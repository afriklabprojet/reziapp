{{-- 
    Composant Prix formaté — <x-price :amount="50000" />
    Usage : 
        <x-price :amount="$residence->price_per_day" />
        <x-price :amount="$residence->price_per_day" suffix="/jour" />
        <x-price :amount="$booking->total_amount" class="text-xl font-bold text-[#e00b41]" />
        <x-price :amount="$discount" prefix="-" class="text-green-600" />
--}}
@props([
    'amount' => 0,
    'suffix' => '',
    'prefix' => '',
    'decimals' => 0,
])

<span {{ $attributes }}>{{ $prefix }}{{ number_format($amount ?? 0, $decimals, ',', ' ') }} FCFA{{ $suffix }}</span>
