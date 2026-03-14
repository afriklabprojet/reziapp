@props(['active'])

@php
$classes = ($active ?? false)
            ? 'block w-full ps-3 pe-4 py-2.5 border-l-4 border-orange-500 text-start text-base font-semibold text-orange-700 bg-orange-50/80 focus:outline-none transition-all duration-200'
            : 'block w-full ps-3 pe-4 py-2.5 border-l-4 border-transparent text-start text-base font-medium text-gray-600 hover:text-gray-900 hover:bg-gray-50 hover:border-gray-300 focus:outline-none focus:text-gray-900 focus:bg-gray-50 transition-all duration-200';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
