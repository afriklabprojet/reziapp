<button {{ $attributes->merge(['type' => 'button', 'class' => 'inline-flex items-center px-6 py-3 bg-white border border-[#222222] rounded-lg font-medium text-base text-[#222222] hover:bg-[#f7f7f7] focus:outline-none focus:ring-2 focus:ring-[#222222]/20 focus:ring-offset-2 disabled:opacity-25 transition-colors duration-150']) }}>
    {{ $slot }}
</button>
