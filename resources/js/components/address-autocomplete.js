/**
 * Alpine.js component — Google Places Autocomplete
 *
 * Usage : x-data="addressAutocomplete()"
 *
 * Initialise le champ d'adresse avec Google Places Autocomplete.
 * Remplit automatiquement les champs cachés #latitude et #longitude
 * à partir de la sélection, et tente de pré-remplir la commune.
 *
 * Chargement asynchrone de l'API Google Maps supporté :
 *   - Si Maps est déjà chargé : initialisation immédiate.
 *   - Si Maps charge après Alpine : le callback `__addressAutocompleteCallback`
 *     déclenche l'initialisation.
 */
export default function addressAutocomplete() {
    return {
        autocomplete: null,
        isReady: false,
        map: null,
        marker: null,
        mapLatitude: 5.3600,
        mapLongitude: -4.0083,

        init() {
            // Récupérer les valeurs initiales des champs cachés
            const latInput = document.getElementById('latitude');
            const lngInput = document.getElementById('longitude');
            if (latInput && latInput.value) this.mapLatitude = parseFloat(latInput.value);
            if (lngInput && lngInput.value) this.mapLongitude = parseFloat(lngInput.value);

            if (
                typeof google !== 'undefined' &&
                google.maps &&
                google.maps.places
            ) {
                this.setupAutocomplete();
                this.initMap();
            } else {
                // Sera appelé par le callback de chargement de l'API Maps
                window.__addressAutocompleteInit = () => {
                    this.setupAutocomplete();
                    this.initMap();
                };
            }
        },

        /**
         * Initialise la carte interactive pour le positionnement.
         */
        initMap() {
            const mapContainer = this.$refs.createMap;
            if (!mapContainer || typeof google === 'undefined') return;

            const position = { lat: this.mapLatitude, lng: this.mapLongitude };

            this.map = new google.maps.Map(mapContainer, {
                center: position,
                zoom: 14,
                styles: [
                    { featureType: 'poi', stylers: [{ visibility: 'off' }] }
                ]
            });

            this.marker = new google.maps.Marker({
                position: position,
                map: this.map,
                draggable: true,
                title: 'Glissez pour repositionner'
            });

            // Déplacement du marqueur
            this.marker.addListener('dragend', () => {
                const pos = this.marker.getPosition();
                this._updateLatLng(pos.lat(), pos.lng());
            });

            // Clic sur la carte
            this.map.addListener('click', (e) => {
                this._updateLatLng(e.latLng.lat(), e.latLng.lng());
                this.marker.setPosition(e.latLng);
            });
        },

        /**
         * Met à jour les coordonnées (inputs cachés + état interne + carte).
         */
        _updateLatLng(lat, lng) {
            this.mapLatitude = lat;
            this.mapLongitude = lng;

            const latInput = document.getElementById('latitude');
            const lngInput = document.getElementById('longitude');
            if (latInput) latInput.value = lat;
            if (lngInput) lngInput.value = lng;
        },

        setupAutocomplete() {
            const input = this.$refs.addressInput;
            if (!input) return;

            this.autocomplete = new google.maps.places.Autocomplete(input, {
                // Restreint à la Côte d'Ivoire (et Burkina Faso optionnel)
                componentRestrictions: { country: ['ci'] },
                // Champs minimaux pour limiter la facturation
                fields: ['formatted_address', 'geometry', 'address_components'],
                types: ['geocode', 'establishment'],
            });

            this.autocomplete.addListener('place_changed', () => {
                const place = this.autocomplete.getPlace();

                if (!place || !place.geometry) {
                    return;
                }

                const lat = place.geometry.location.lat();
                const lng = place.geometry.location.lng();

                // Remplir les inputs cachés latitude / longitude
                this._updateLatLng(lat, lng);

                // Centrer la carte et déplacer le marqueur
                if (this.map && this.marker) {
                    const pos = new google.maps.LatLng(lat, lng);
                    this.map.setCenter(pos);
                    this.map.setZoom(16);
                    this.marker.setPosition(pos);
                }

                // Tenter de pré-remplir commune et quartier
                this._fillAddressComponents(place.address_components || []);

                this.isReady = true;
            });
        },

        /**
         * Extraire les composantes d'adresse Google et pré-remplir
         * les selects commune / quartier si disponibles.
         */
        _fillAddressComponents(components) {
            const get = (types) => {
                const comp = components.find((c) =>
                    types.some((t) => c.types.includes(t))
                );
                return comp ? comp.long_name : null;
            };

            // Commune → locality ou administrative_area_level_2
            const commune =
                get(['locality', 'administrative_area_level_2', 'sublocality_level_1']) ||
                get(['administrative_area_level_1']);

            // Quartier → sublocality_level_1 ou neighborhood
            const quartier =
                get(['sublocality_level_1', 'neighborhood', 'sublocality']) ||
                get(['premise']);

            // Pré-remplir le select commune si trouvé
            if (commune) {
                const communeSelect = document.getElementById('commune');
                if (communeSelect) {
                    const options = Array.from(communeSelect.options);
                    const match = options.find(
                        (o) =>
                            o.text.toLowerCase().includes(commune.toLowerCase()) ||
                            commune.toLowerCase().includes(o.text.toLowerCase())
                    );
                    if (match) {
                        communeSelect.value = match.value;
                        // Déclencher un événement change pour Alpine si nécessaire
                        communeSelect.dispatchEvent(new Event('change'));
                    }
                }
            }

            // Pré-remplir le champ quartier si vide
            if (quartier) {
                const quartierInput = document.getElementById('quartier');
                if (quartierInput && !quartierInput.value) {
                    quartierInput.value = quartier;
                }
            }
        },
    };
}
