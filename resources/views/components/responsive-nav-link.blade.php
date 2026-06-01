@props(['active'])

@php
$classes = ($active ?? false)
            ? 'block w-full ps-3 pe-4 py-2.5 border-l-4 border-[#F16A00] text-start text-base font-semibold text-[#F16A00] bg-[#FFF4EB] focus:outline-none transition-all duration-200'
            : 'block w-full ps-3 pe-4 py-2.5 border-l-4 border-transparent text-start text-base font-medium text-[#0F0F0F] hover:text-[#0F0F0F] hover:bg-[#F2F2F2] hover:border-[#F2F2F2] focus:outline-none transition-all duration-200';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
