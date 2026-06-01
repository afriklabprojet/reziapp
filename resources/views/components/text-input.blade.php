@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'border-[#F2F2F2] focus:border-[#0F0F0F] focus:ring-[#0F0F0F] rounded-lg shadow-none text-[#0F0F0F] placeholder:text-[#555555]']) }}>
