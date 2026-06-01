/**
 * Language Selector - Alpine.js component for the language selection widget.
 * Extracted from resources/views/profiles/edit.blade.php
 * for @alpinejs/csp compatibility.
 *
 * Usage in Blade:
 *   x-data="languageSelector(@js(['languages' => old('languages', $profile->languages ?? ['Français'])]))"
 */
export default function languageSelector(config = {}) {
    return {
        languages: config.languages || ['Français'],
        newLang: '',
        suggestions: [
            'Français', 'Anglais', 'Arabe', 'Espagnol', 'Portugais',
            'Dioula', 'Baoulé', 'Bété', 'Wolof', 'Bambara', 'Peul',
            'Haoussa', 'Yoruba', 'Lingala', 'Swahili',
        ],

        get availableSuggestions() {
            return this.suggestions.filter(s => !this.languages.includes(s));
        },

        addLanguage(lang = null) {
            const toAdd = lang || this.newLang.trim();
            if (toAdd && !this.languages.includes(toAdd)) {
                this.languages = [...this.languages, toAdd];
                this.newLang = '';
            }
        },

        removeLanguage(lang) {
            this.languages = this.languages.filter(l => l !== lang);
        },
    };
}
