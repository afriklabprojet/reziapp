<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center px-6 py-3 bg-[#ff385c] border border-transparent rounded-lg font-medium text-base text-white hover:bg-[#e00b41] active:bg-[#b5083a] focus:outline-none focus:ring-2 focus:ring-[#ff385c]/40 focus:ring-offset-2 transition-colors duration-150']) }}>
    {{ $slot }}
</button>
