/**
 * City Selector - Alpine.js component for country/city/commune selection.
 * Used in residence create and edit forms.
 * Extracted for @alpinejs/csp compatibility.
 *
 * Usage in Blade:
 *   x-data="citySelector(@js([
 *     'selectedCountry' => old('country_code', 'CI'),
 *     'selectedCity'    => old('city', ''),
 *     'countries'       => $countries->map(fn($c) => ['code' => $c->code, 'name' => $c->name]),
 *     'allCities'       => $cities->map(fn($c) => [...]),
 *   ]))"
 */
export default function citySelector(config = {}) {
    return {
        selectedCountry: config.selectedCountry || 'CI',
        selectedCity: config.selectedCity || '',
        countries: config.countries || [],
        allCities: config.allCities || [],

        get filteredCities() {
            return this.allCities.filter(c => c.country_code === this.selectedCountry);
        },

        get filteredCommunes() {
            const city = this.allCities.find(
                c => c.name === this.selectedCity && c.country_code === this.selectedCountry
            );
            return city ? city.communes : [];
        },
    };
}
