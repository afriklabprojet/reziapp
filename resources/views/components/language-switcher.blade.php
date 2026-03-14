<div x-data="languageSwitcher()" class="relative">
    <button 
        @click="open = !open" 
        @click.away="open = false"
        type="button"
        class="flex items-center gap-2 px-3 py-2 text-sm font-medium text-gray-700 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white transition-colors rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800"
    >
        <span class="text-base" x-text="currentLocale === 'fr' ? '🇫🇷' : '🇬🇧'"></span>
        <span x-text="currentLocale === 'fr' ? 'FR' : 'EN'" class="uppercase"></span>
        <svg class="w-4 h-4 transition-transform" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
        </svg>
    </button>

    <div 
        x-show="open"
        x-transition:enter="transition ease-out duration-100"
        x-transition:enter-start="transform opacity-0 scale-95"
        x-transition:enter-end="transform opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-75"
        x-transition:leave-start="transform opacity-100 scale-100"
        x-transition:leave-end="transform opacity-0 scale-95"
        class="absolute right-0 mt-2 w-40 origin-top-right rounded-lg bg-white dark:bg-gray-800 shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none z-50"
        style="display: none;"
    >
        <div class="py-1">
            <button 
                @click="switchLocale('fr')" 
                class="w-full flex items-center gap-3 px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700"
                :class="{ 'bg-primary-50 dark:bg-primary-900/20': currentLocale === 'fr' }"
            >
                <span class="text-base">🇫🇷</span>
                <span>Français</span>
                <svg x-show="currentLocale === 'fr'" class="w-4 h-4 ml-auto text-primary-600" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                </svg>
            </button>
            <button 
                @click="switchLocale('en')" 
                class="w-full flex items-center gap-3 px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700"
                :class="{ 'bg-primary-50 dark:bg-primary-900/20': currentLocale === 'en' }"
            >
                <span class="text-base">🇬🇧</span>
                <span>English</span>
                <svg x-show="currentLocale === 'en'" class="w-4 h-4 ml-auto text-primary-600" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                </svg>
            </button>
        </div>
    </div>
</div>

<script>
function languageSwitcher() {
    return {
        open: false,
        currentLocale: '{{ app()->getLocale() }}',
        
        switchLocale(locale) {
            if (locale === this.currentLocale) {
                this.open = false;
                return;
            }
            
            // Build URL with lang parameter
            const url = new URL(window.location.href);
            url.searchParams.set('lang', locale);
            
            // Redirect to update locale
            window.location.href = url.toString();
        }
    }
}
</script>
