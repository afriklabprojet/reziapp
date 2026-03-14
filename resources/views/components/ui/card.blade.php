@props([
    'padding' => true,
    'hover' => false,
    'shadow' => 'sm', // none, sm, md, lg
])

@php
    $shadows = [
        'none' => '',
        'sm' => 'shadow-sm',
        'md' => 'shadow-md',
        'lg' => 'shadow-lg',
    ];

    $paddingClass = $padding ? 'p-6' : '';
    $hoverClass = $hover ? 'hover:shadow-lg hover:-translate-y-1 transition-all duration-300 cursor-pointer' : '';
    $shadowClass = $shadows[$shadow] ?? $shadows['sm'];
@endphp

<div {{ $attributes->merge(['class' => "bg-white rounded-2xl $shadowClass $paddingClass $hoverClass"]) }}>
    @if(isset($header))
        <div class="border-b border-gray-100 pb-4 mb-4">
            {{ $header }}
        </div>
    @endif
    
    {{ $slot }}
    
    @if(isset($footer))
        <div class="border-t border-gray-100 pt-4 mt-4">
            {{ $footer }}
        </div>
    @endif
</div>
