@props(['active'])

@php
$classes = ($active ?? false)
            ? 'inline-flex items-center px-1 pt-1 border-b-2 border-[#222222] text-sm font-semibold leading-5 text-[#222222] focus:outline-none transition-colors duration-200'
            : 'inline-flex items-center px-1 pt-1 border-b-2 border-transparent text-sm font-medium leading-5 text-[#6a6a6a] hover:text-[#222222] hover:border-[#dddddd] focus:outline-none focus:text-[#222222] transition-colors duration-200';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
