export default function searchForm(config = {}) {
    return {
        latitude: config.latitude || null,
        longitude: config.longitude || null,
        radius: config.radius || 5,
        showFilters: false,
        autocomplete: null,

        init() {
            const initAutocomplete = () => {
                if (this.autocomplete || typeof google === 'undefined' || !google.maps?.places) {
                    return;
                }

                this.autocomplete = new google.maps.places.Autocomplete(
                    this.$refs.locationInput,
                    {
                        componentRestrictions: { country: ['ci', 'bf'] },
                        types: ['geocode'],
                    }
                );

                this.autocomplete.addListener('place_changed', () => {
                    const place = this.autocomplete.getPlace();
                    if (place.geometry) {
                        this.latitude = place.geometry.location.lat();
                        this.longitude = place.geometry.location.lng();
                    }
                });
            };

            if (typeof google !== 'undefined' && google.maps?.places) {
                initAutocomplete();
            } else {
                globalThis.__googleMapsCallbacks = globalThis.__googleMapsCallbacks || [];
                globalThis.__googleMapsCallbacks.push(initAutocomplete);
            }

            // Géolocalisation automatique — haute précision
            if (this.latitude === null && navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                    (position) => {
                        this.latitude = position.coords.latitude;
                        this.longitude = position.coords.longitude;
                    },
                    (error) => {
                        console.warn('Géolocalisation échouée:', error.message);
                    },
                    { enableHighAccuracy: true, timeout: 15000, maximumAge: 10000 }
                );
            }
        }
    };
}
