@props(['active'])

@php
$classes = ($active ?? false)
            ? 'block w-full ps-3 pe-4 py-2.5 border-l-4 border-[#ff385c] text-start text-base font-semibold text-[#ff385c] bg-[#fff0f3] focus:outline-none transition-all duration-200'
            : 'block w-full ps-3 pe-4 py-2.5 border-l-4 border-transparent text-start text-base font-medium text-[#222222] hover:text-[#222222] hover:bg-[#f7f7f7] hover:border-[#dddddd] focus:outline-none transition-all duration-200';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
