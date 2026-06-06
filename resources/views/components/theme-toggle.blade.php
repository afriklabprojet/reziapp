{{-- Dark / Light mode toggle — persists in localStorage --}}
<button
    x-data="themeToggle()"
    x-init="init()"
    @click="toggle()"
    :title="dark ? 'Mode clair' : 'Mode sombre'"
    :aria-label="dark ? 'Activer le mode clair' : 'Activer le mode sombre'"
    {{ $attributes->merge(['class' => 'p-2.5 rounded-full hover:bg-gray-100 text-gray-500 transition-colors duration-200 min-w-11 min-h-11 flex items-center justify-center cursor-pointer']) }}
>
    {{-- Sun icon (shown in dark mode → click to switch to light) --}}
    <svg x-show="dark" x-cloak class="w-5 h-5 text-amber-400" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
        <path fill-rule="evenodd" d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.95l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zm2.12-10.607a1 1 0 010 1.414l-.706.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 011.414 0zM17 11a1 1 0 100-2h-1a1 1 0 100 2h1zm-7 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM5.05 6.464A1 1 0 106.465 5.05l-.708-.707a1 1 0 00-1.414 1.414l.707.707zm1.414 8.486l-.707.707a1 1 0 01-1.414-1.414l.707-.707a1 1 0 011.414 1.414zM4 11a1 1 0 100-2H3a1 1 0 000 2h1z" clip-rule="evenodd" />
    </svg>

    {{-- Moon icon (shown in light mode → click to switch to dark) --}}
    <svg x-show="!dark" x-cloak class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
    </svg>
</button>
