@props([
    'type' => 'default', // default, success, warning, danger, info
    'size' => 'md',      // sm, md, lg
    'rounded' => true,
    'dot' => false,
])

@php
    $types = [
        'default' => 'bg-gray-100 text-gray-700',
        'success' => 'bg-green-100 text-green-700',
        'warning' => 'bg-amber-100 text-amber-700',
        'danger' => 'bg-red-100 text-red-700',
        'info' => 'bg-blue-100 text-blue-700',
        'primary' => 'bg-orange-100 text-orange-700',
        'pending' => 'bg-yellow-100 text-yellow-700',
        'approved' => 'bg-green-100 text-green-700',
        'rejected' => 'bg-red-100 text-red-700',
    ];

    $sizes = [
        'sm' => 'px-2 py-0.5 text-xs',
        'md' => 'px-2.5 py-1 text-xs',
        'lg' => 'px-3 py-1.5 text-sm',
    ];

    $dotColors = [
        'default' => 'bg-gray-400',
        'success' => 'bg-green-500',
        'warning' => 'bg-amber-500',
        'danger' => 'bg-red-500',
        'info' => 'bg-blue-500',
        'primary' => 'bg-orange-500',
        'pending' => 'bg-yellow-500',
        'approved' => 'bg-green-500',
        'rejected' => 'bg-red-500',
    ];

    $classes = $types[$type] ?? $types['default'];
    $sizeClasses = $sizes[$size] ?? $sizes['md'];
    $roundedClass = $rounded ? 'rounded-full' : 'rounded';
    $dotColor = $dotColors[$type] ?? $dotColors['default'];
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center gap-1.5 font-medium $classes $sizeClasses $roundedClass"]) }}>
    @if($dot)
        <span class="w-1.5 h-1.5 rounded-full {{ $dotColor }}"></span>
    @endif
    {{ $slot }}
</span>
