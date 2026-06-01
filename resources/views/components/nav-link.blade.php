@props(['active'])

@php
$classes = ($active ?? false)
            ? 'inline-flex items-center px-1 pt-1 border-b-2 border-[#0F0F0F] text-sm font-semibold leading-5 text-[#0F0F0F] focus:outline-none transition-colors duration-200'
            : 'inline-flex items-center px-1 pt-1 border-b-2 border-transparent text-sm font-medium leading-5 text-[#555555] hover:text-[#0F0F0F] hover:border-[#F2F2F2] focus:outline-none focus:text-[#0F0F0F] transition-colors duration-200';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
