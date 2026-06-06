export default function residenceEditForm(config = {}) {
    return {
        latitude: config.latitude || 0,
        longitude: config.longitude || 0,
        map: null,
        marker: null,

        init() {
            this.$nextTick(() => {
                if (typeof google !== 'undefined' && google.maps) {
                    this.initMap();
                } else {
                    globalThis.__googleMapsCallbacks = globalThis.__googleMapsCallbacks || [];
                    globalThis.__googleMapsCallbacks.push(() => {
                        this.initMap();
                    });
                }
            });
        },

        initMap() {
            const mapContainer = this.$refs.map || document.getElementById('location-map');
            if (!mapContainer || typeof google === 'undefined') {
                console.warn('Google Maps not available');
                return;
            }

            const position = { lat: this.latitude, lng: this.longitude };

            this.map = new google.maps.Map(mapContainer, {
                center: position,
                zoom: 15,
                styles: [
                    {
                        featureType: 'poi',
                        stylers: [{ visibility: 'off' }]
                    }
                ]
            });

            // Marqueur draggable
            this.marker = new google.maps.Marker({
                position: position,
                map: this.map,
                draggable: true,
                title: 'Glissez pour repositionner'
            });

            // Mise à jour quand on déplace le marqueur
            this.marker.addListener('dragend', () => {
                const pos = this.marker.getPosition();
                this.latitude = pos.lat();
                this.longitude = pos.lng();
                this._reverseGeocode(pos.lat(), pos.lng());
            });

            // Clic sur la carte pour repositionner
            this.map.addListener('click', (e) => {
                this.latitude = e.latLng.lat();
                this.longitude = e.latLng.lng();
                this.marker.setPosition(e.latLng);
                this._reverseGeocode(e.latLng.lat(), e.latLng.lng());
            });
        },

        /**
         * Reverse geocoding : coordonnées → auto-fill commune et quartier.
         */
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
            } catch (e) {
                console.warn('Reverse geocode failed:', e);
            }
        },

        applyReverseGeocodeData(data) {
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

        syncCommuneSelect(commune) {
            const communeSelect = document.getElementById('commune');
            if (!communeSelect) return;

            const match = Array.from(communeSelect.options).find((option) =>
                option.text.toLowerCase().includes(commune.toLowerCase()) ||
                commune.toLowerCase().includes(option.text.toLowerCase())
            );

            if (!match) return;

            communeSelect.value = match.value;
            communeSelect.dispatchEvent(new Event('change'));
        }
    };
}

export function photoUploader() {
    return {
        isDragging: false,
        previews: [],
        files: [],

        handleDrop(event) {
            this.isDragging = false;
            const files = event.dataTransfer.files;
            this.processFiles(files);
        },

        handleFiles(event) {
            const files = event.target.files;
            this.processFiles(files);
        },

        processFiles(fileList) {
            Array.from(fileList).forEach(file => {
                if (file.type.startsWith('image/')) {
                    const reader = new FileReader();
                    reader.onload = (e) => {
                        this.previews.push(e.target.result);
                    };
                    reader.readAsDataURL(file);
                    this.files.push(file);
                }
            });
        },

        removePreview(index) {
            this.previews.splice(index, 1);
            this.files.splice(index, 1);
        }
    };
}
