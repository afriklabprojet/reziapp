@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'border-[#dddddd] focus:border-[#222222] focus:ring-[#222222] rounded-lg shadow-none text-[#222222] placeholder:text-[#929292]']) }}>
