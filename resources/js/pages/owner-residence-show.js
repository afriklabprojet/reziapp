/**
 * Owner residence show — Google Maps init
 * Extracted from owner/residences/show.blade.php
 */
export default function ownerResidenceMap(config = {}) {
    return {
        infoWindow: null,
        isInfoWindowOpen: false,

        init() {
            const renderMap = () => {
                if (typeof google?.maps?.Map !== 'function') {
                    return;
                }

                const mapContainer = this.$refs.map || document.getElementById('residence-map');
                if (!mapContainer) {
                    return;
                }

                const position = {
                    lat: config.lat,
                    lng: config.lng,
                };

                const map = new google.maps.Map(mapContainer, {
                    center: position,
                    zoom: 15,
                    styles: [
                        {
                            featureType: 'poi',
                            stylers: [{ visibility: 'off' }],
                        },
                    ],
                });

                this.marker = new google.maps.Marker({
                    position,
                    map,
                    title: config.title || '',
                });

                this.infoWindow = this.createInfoWindow();
                if (this.infoWindow) {
                    this.infoWindow.addListener('closeclick', () => {
                        this.isInfoWindowOpen = false;
                    });

                    this.marker.addListener('click', () => {
                        this.toggleInfoWindow(map);
                    });
                }
            };

            if (typeof google?.maps?.Map === 'function') {
                renderMap();
            } else {
                globalThis.__googleMapsCallbacks = globalThis.__googleMapsCallbacks || [];
                globalThis.__googleMapsCallbacks.push(renderMap);
            }

        },

        toggleInfoWindow(map) {
            if (!this.infoWindow) {
                return;
            }

            if (this.isInfoWindowOpen) {
                this.infoWindow.close();
                this.isInfoWindowOpen = false;
                return;
            }

            this.infoWindow.open({ anchor: this.marker, map });
            this.isInfoWindowOpen = true;
        },

        createInfoWindow() {
            if (typeof google?.maps?.Map !== 'function') {
                return null;
            }

            const container = document.createElement('div');
            container.className = 'text-sm leading-5';

            if (config.title) {
                const title = document.createElement('strong');
                title.textContent = config.title;
                container.appendChild(title);
            }

            if (config.address) {
                const address = document.createElement('div');
                address.textContent = config.address;
                container.appendChild(address);
            }

            if (container.childNodes.length === 0) {
                return null;
            }

            return new google.maps.InfoWindow({
                content: container,
            });
        }
    };
}
