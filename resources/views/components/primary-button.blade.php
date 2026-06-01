<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center px-6 py-3 bg-[#F16A00] border border-transparent rounded-lg font-medium text-base text-white hover:bg-[#CC5A00] active:bg-[#A34700] focus:outline-none focus:ring-2 focus:ring-[#F16A00]/40 focus:ring-offset-2 transition-colors duration-150']) }}>
    {{ $slot }}
</button>
