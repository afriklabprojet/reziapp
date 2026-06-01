<button {{ $attributes->merge(['type' => 'button', 'class' => 'inline-flex items-center px-6 py-3 bg-white border border-[#0F0F0F] rounded-lg font-medium text-base text-[#0F0F0F] hover:bg-[#F2F2F2] focus:outline-none focus:ring-2 focus:ring-[#0F0F0F]/20 focus:ring-offset-2 disabled:opacity-25 transition-colors duration-150']) }}>
    {{ $slot }}
</button>
