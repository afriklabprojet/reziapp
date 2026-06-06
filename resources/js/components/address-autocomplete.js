const DEFAULT_LATITUDE = 5.36;
const DEFAULT_LONGITUDE = -4.0083;

/**
 * Alpine.js component — Google Places Autocomplete
 */
export default function addressAutocomplete() {
    return {
        autocomplete: null,
        isReady: false,
        map: null,
        marker: null,
        mapLatitude: DEFAULT_LATITUDE,
        mapLongitude: DEFAULT_LONGITUDE,

        init() {
            const latInput = document.getElementById('latitude');
            const lngInput = document.getElementById('longitude');
            const hasCustomCoords = this.hasCustomCoords(latInput, lngInput);

            if (hasCustomCoords) {
                this.mapLatitude = Number.parseFloat(latInput.value);
                this.mapLongitude = Number.parseFloat(lngInput.value);
            }

            const doInit = () => {
                this.setupAutocomplete();
                this.initMap();
            };

            if (typeof google !== 'undefined' && google.maps?.places) {
                if (hasCustomCoords) {
                    doInit();
                } else {
                    this._detectIpLocation().then(doInit);
                }

                return;
            }

            globalThis.__googleMapsCallbacks = globalThis.__googleMapsCallbacks || [];
            globalThis.__googleMapsCallbacks.push(() => {
                if (hasCustomCoords) {
                    doInit();
                } else {
                    this._detectIpLocation().then(doInit);
                }
            });
        },

        hasCustomCoords(latInput, lngInput) {
            if (!latInput?.value || !lngInput?.value) {
                return false;
            }

            const latitude = Number.parseFloat(latInput.value);
            const longitude = Number.parseFloat(lngInput.value);

            return latitude !== DEFAULT_LATITUDE || longitude !== DEFAULT_LONGITUDE;
        },

        async _detectIpLocation() {
            try {
                const response = await fetch('https://ipapi.co/json/', { signal: AbortSignal.timeout(4000) });
                if (!response.ok) return;

                const data = await response.json();
                if (!data.latitude || !data.longitude) return;

                this.mapLatitude = Number.parseFloat(data.latitude);
                this.mapLongitude = Number.parseFloat(data.longitude);
                this.syncHiddenCoordinates();
                this.selectDetectedCountry(data.country_code);
            } catch {
                // Fallback silencieux sur Abidjan
            }
        },

        syncHiddenCoordinates() {
            const latInput = document.getElementById('latitude');
            const lngInput = document.getElementById('longitude');
            if (latInput) latInput.value = this.mapLatitude;
            if (lngInput) lngInput.value = this.mapLongitude;
        },

        selectDetectedCountry(countryCode) {
            if (!countryCode) return;

            const countrySelect = document.getElementById('country_code');
            if (!countrySelect) return;

            const option = Array.from(countrySelect.options).find(
                (currentOption) => currentOption.value.toLowerCase() === countryCode.toLowerCase()
            );

            if (!option) return;

            countrySelect.value = option.value;
            countrySelect.dispatchEvent(new Event('change'));
        },

        initMap() {
            const mapContainer = this.$refs.createMap;
            if (!mapContainer || typeof google === 'undefined') return;

            const position = { lat: this.mapLatitude, lng: this.mapLongitude };

            this.map = new google.maps.Map(mapContainer, {
                center: position,
                zoom: 14,
                styles: [{ featureType: 'poi', stylers: [{ visibility: 'off' }] }],
            });

            this.marker = new google.maps.Marker({
                position,
                map: this.map,
                draggable: true,
                title: 'Glissez pour repositionner',
            });

            this.marker.addListener('dragend', () => {
                const positionAfterDrag = this.marker.getPosition();
                this._updateLatLng(positionAfterDrag.lat(), positionAfterDrag.lng());
                this._reverseGeocode(positionAfterDrag.lat(), positionAfterDrag.lng());
            });

            this.map.addListener('click', (event) => {
                this._updateLatLng(event.latLng.lat(), event.latLng.lng());
                this.marker.setPosition(event.latLng);
                this._reverseGeocode(event.latLng.lat(), event.latLng.lng());
            });
        },

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
                componentRestrictions: { country: ['ci'] },
                fields: ['formatted_address', 'geometry', 'address_components'],
                types: ['geocode', 'establishment'],
            });

            this.autocomplete.addListener('place_changed', () => {
                const place = this.autocomplete.getPlace();
                if (!place?.geometry) return;

                const lat = place.geometry.location.lat();
                const lng = place.geometry.location.lng();
                this._updateLatLng(lat, lng);

                if (this.map && this.marker) {
                    const position = new google.maps.LatLng(lat, lng);
                    this.map.setCenter(position);
                    this.map.setZoom(16);
                    this.marker.setPosition(position);
                }

                this._fillAddressComponents(place.address_components || []);
                this.isReady = true;
            });
        },

        _fillAddressComponents(components) {
            const commune =
                this.getAddressComponentValue(components, ['locality', 'administrative_area_level_2', 'sublocality_level_1']) ||
                this.getAddressComponentValue(components, ['administrative_area_level_1']);

            const quartier =
                this.getAddressComponentValue(components, ['sublocality_level_1', 'neighborhood', 'sublocality']) ||
                this.getAddressComponentValue(components, ['premise']);

            if (commune) {
                this.syncCommuneSelect(commune);
            }

            if (quartier) {
                const quartierInput = document.getElementById('quartier');
                if (quartierInput && !quartierInput.value) {
                    quartierInput.value = quartier;
                }
            }
        },

        getAddressComponentValue(components, expectedTypes) {
            for (const component of components) {
                if (expectedTypes.some((type) => component.types.includes(type))) {
                    return component.long_name;
                }
            }

            return null;
        },

        syncCommuneSelect(commune) {
            const communeSelect = document.getElementById('commune');
            if (!communeSelect) return;

            const match = Array.from(communeSelect.options).find(
                (option) =>
                    option.text.toLowerCase().includes(commune.toLowerCase()) ||
                    commune.toLowerCase().includes(option.text.toLowerCase())
            );

            if (!match) return;

            communeSelect.value = match.value;
            communeSelect.dispatchEvent(new Event('change'));
        },

        async _reverseGeocode(lat, lng) {
            try {
                const response = await fetch('/api/v1/maps/reverse-geocode', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    },
                    body: JSON.stringify({ lat, lng }),
                });
                const json = await response.json();
                if (!json.success || !json.data) return;

                this.applyReverseGeocodeData(json.data);
                this._showAddressValidation(lat, lng);
            } catch (error) {
                console.warn('Reverse geocode failed:', error);
            }
        },

        applyReverseGeocodeData(data) {
            const addressInput = this.$refs.addressInput;
            if (addressInput && data.address) {
                addressInput.value = data.address;
            }

            if (data.commune) {
                this.syncCommuneSelect(data.commune);
            }

            if (data.quartier) {
                const quartierInput = document.getElementById('quartier');
                if (quartierInput) {
                    quartierInput.value = data.quartier;
                    quartierInput.dispatchEvent(new Event('input'));
                }
            }
        },

        async _showAddressValidation(lat, lng) {
            try {
                const citySelect = document.getElementById('city');
                const city = citySelect ? citySelect.value : null;

                const response = await fetch('/api/v1/maps/validate-address', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    },
                    body: JSON.stringify({ lat, lng, city }),
                });
                const json = await response.json();
                if (!json.success || !json.data) return;

                const validation = json.data;
                const badge = document.getElementById('address-validation-badge');
                if (!badge) return;

                if (validation.valid && validation.confidence >= 70) {
                    badge.className = 'inline-flex items-center gap-1 px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-700';
                    badge.innerHTML = `✅ Adresse vérifiée (${validation.confidence}%)`;
                } else if (validation.valid) {
                    badge.className = 'inline-flex items-center gap-1 px-2 py-1 text-xs font-medium rounded-full bg-yellow-100 text-yellow-700';
                    badge.innerHTML = `⚠️ Adresse approximative (${validation.confidence}%)`;
                } else {
                    badge.className = 'inline-flex items-center gap-1 px-2 py-1 text-xs font-medium rounded-full bg-red-100 text-red-700';
                    badge.innerHTML = `❌ ${validation.issues[0] || 'Position invalide'}`;
                }

                badge.style.display = 'inline-flex';
            } catch (error) {
                console.warn('Address validation failed:', error);
            }
        },
    };
}
