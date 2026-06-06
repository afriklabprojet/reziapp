/**
 * Map Search - Alpine.js component for Google Maps with residence markers.
 * Extracted from resources/views/components/map-search.blade.php
 */
export default function mapSearch(config = {}) {
    const fallbackCenter = {
        lat: Number.isFinite(Number(config.center?.lat)) ? Number(config.center.lat) : 5.36,
        lng: Number.isFinite(Number(config.center?.lng)) ? Number(config.center.lng) : -4.0083,
    };
    const defaultZoom = Number.isFinite(Number(config.zoom)) ? Number(config.zoom) : 12;
    const defaultRadius = Number.isFinite(Number(config.radius)) ? Number(config.radius) : 5;

    return {
        map: null,
        markers: [],
        userMarker: null,
        radiusCircle: null,
        loading: true,
        selectedResidence: null,
        popupPosition: '',
        visibleCount: 0,
        currentRadius: defaultRadius,
        showRadiusCircle: Boolean(config.showRadiusCircle),
        userLocation: null,
        config: {
            ...config,
            center: fallbackCenter,
            zoom: defaultZoom,
            radius: defaultRadius,
        },
        resizeHandler: null,
        eventsBound: false,
        queuedInit: false,

        initMap() {
            if (!globalThis.google?.maps) {
                if (!this.queuedInit) {
                    this.queuedInit = true;
                    globalThis.__googleMapsCallbacks = globalThis.__googleMapsCallbacks || [];
                    globalThis.__googleMapsCallbacks.push(() => this.initMap());
                }
                return;
            }

            if (this.map) return;

            this.map = new google.maps.Map(this.$refs.mapContainer, {
                center: this.config.center,
                zoom: this.config.zoom,
                disableDefaultUI: true,
                clickableIcons: false,
                streetViewControl: false,
                fullscreenControl: false,
                mapTypeControl: false,
            });

            this.resizeHandler = () => {
                if (!this.map) return;

                requestAnimationFrame(() => {
                    google.maps.event.trigger(this.map, 'resize');
                    this.updateVisibleCount();
                });
            };

            globalThis.addEventListener('resize', this.resizeHandler);
            globalThis.addEventListener('orientationchange', this.resizeHandler);
            globalThis.addEventListener('map:resize', this.resizeHandler);

            this.bindGlobalEvents();
            this.addMarkers(this.config.residences);

            if (this.showRadiusCircle) {
                this.drawRadiusCircle(this.config.center.lat, this.config.center.lng, this.config.radius);
            }

            this.getUserLocation();

            google.maps.event.addListener(this.map, 'idle', () => {
                this.loading = false;
                this.updateVisibleCount();

                clearTimeout(this._boundsTimer);
                this._boundsTimer = setTimeout(() => {
                    const bounds = this.map?.getBounds();
                    if (!bounds) return;

                    const northEast = bounds.getNorthEast();
                    const southWest = bounds.getSouthWest();
                    globalThis.dispatchEvent(new CustomEvent('map:bounds-changed', {
                        detail: {
                            sw_lat: southWest.lat(),
                            sw_lng: southWest.lng(),
                            ne_lat: northEast.lat(),
                            ne_lng: northEast.lng(),
                            zoom: this.map.getZoom(),
                        },
                    }));
                }, 350);
            });
        },

        bindGlobalEvents() {
            if (this.eventsBound) return;

            this.eventsBound = true;

            this.$watch('config.residences', (residences) => {
                this.clearMarkers();
                this.addMarkers(residences);
            });

            globalThis.addEventListener('map:update-residences', (event) => {
                this.clearMarkers();
                this.addMarkers(event.detail.residences);
            });

            globalThis.addEventListener('map:highlight-residence', (event) => {
                this.highlightMarker(event.detail.id);
            });

            globalThis.addEventListener('map:update-radius', (event) => {
                this.updateRadius(event.detail.radius);
            });

            globalThis.addEventListener('map:center-on', (event) => {
                this.flyTo(event.detail.lat, event.detail.lng, event.detail.zoom || 14);
            });

            globalThis.addEventListener('map:fit-residences', (event) => {
                this.fitResidences(event.detail.residences, event.detail.userLocation);
            });
        },

        createMarkerIcon(residence) {
            const price = this.formatPriceShort(residence.price);
            const priceLabel = residence.price_label || '/jour';
            const isAvailable = residence.is_available === undefined ? true : residence.is_available;
            const background = isAvailable ? '#059669' : '#9ca3af';
            const label = `${price}${priceLabel}`;
            const width = Math.max(82, label.length * 7 + 26);
            const height = 38;
            const svg = `
                <svg xmlns="http://www.w3.org/2000/svg" width="${width}" height="${height}" viewBox="0 0 ${width} ${height}">
                    <rect x="1" y="1" width="${width - 2}" height="28" rx="14" fill="${background}" />
                    <path d="M${width / 2 - 7} 28 L${width / 2} 36 L${width / 2 + 7} 28 Z" fill="${background}" />
                    <text x="${width / 2}" y="18" text-anchor="middle" font-size="12" font-weight="700" fill="#ffffff">${label}</text>
                </svg>`;

            return {
                url: `data:image/svg+xml;charset=UTF-8,${encodeURIComponent(svg)}`,
                scaledSize: new google.maps.Size(width, height),
                anchor: new google.maps.Point(width / 2, height),
            };
        },

        addMarkers(residences) {
            if (!this.map || !Array.isArray(residences)) return;

            residences.forEach((residence) => {
                const lat = Number(residence.location?.latitude || residence.latitude);
                const lng = Number(residence.location?.longitude || residence.longitude);

                if (!Number.isFinite(lat) || !Number.isFinite(lng)) return;

                const marker = new google.maps.Marker({
                    position: { lat, lng },
                    map: this.map,
                    title: residence.title,
                    icon: this.createMarkerIcon(residence),
                    optimized: true,
                });

                marker.addListener('click', () => {
                    this.showPopup(residence);
                });

                marker.addListener('mouseover', () => {
                    globalThis.dispatchEvent(new CustomEvent('map:residence-hover', {
                        detail: { id: residence.id },
                    }));
                });

                marker.addListener('mouseout', () => {
                    globalThis.dispatchEvent(new CustomEvent('map:residence-unhover', {
                        detail: { id: residence.id },
                    }));
                });

                this.markers.push({ id: residence.id, marker });
            });

            this.updateVisibleCount();
        },

        clearMarkers() {
            this.markers.forEach(({ marker }) => marker.setMap(null));
            this.markers = [];
        },

        showPopup(residence) {
            this.selectedResidence = residence;

            const lat = Number(residence.location?.latitude || residence.latitude);
            const lng = Number(residence.location?.longitude || residence.longitude);

            const mapRect = this.$refs.mapContainer.getBoundingClientRect();
            const left = Math.max(16, mapRect.left + (mapRect.width / 2) - 144);
            const top = Math.max(16, mapRect.top + 24);
            this.popupPosition = `top: ${top}px; left: ${left}px;`;

            this.map.panTo({ lat, lng });
        },

        highlightMarker(id) {
            this.markers.forEach(({ id: markerId, marker }) => {
                if (String(markerId) !== String(id)) {
                    marker.setZIndex(undefined);
                    return;
                }

                marker.setZIndex(google.maps.Marker.MAX_ZINDEX + 1);
                const position = marker.getPosition();
                if (position) {
                    this.map.panTo(position);
                }
            });
        },

        drawRadiusCircle(lat, lng, radiusKm) {
            if (this.radiusCircle) {
                this.radiusCircle.setMap(null);
            }

            this.radiusCircle = new google.maps.Circle({
                center: { lat, lng },
                radius: radiusKm * 1000,
                strokeColor: '#3b82f6',
                strokeOpacity: 0.75,
                strokeWeight: 2,
                fillColor: '#3b82f6',
                fillOpacity: 0.1,
                map: this.map,
            });

            this.currentRadius = radiusKm;
        },

        updateRadius(radiusKm) {
            const center = this.userLocation || this.config.center;
            this.drawRadiusCircle(center.lat, center.lng, radiusKm);
        },

        updateVisibleCount() {
            if (!this.map) return;

            const bounds = this.map.getBounds();
            if (!bounds) return;

            this.visibleCount = this.markers.filter(({ marker }) => {
                const position = marker.getPosition();
                return position ? bounds.contains(position) : false;
            }).length;
        },

        getUserLocation() {
            if (!navigator.geolocation) return;

            navigator.geolocation.getCurrentPosition(
                (position) => {
                    this.userLocation = {
                        lat: position.coords.latitude,
                        lng: position.coords.longitude,
                    };

                    const accuracy = Math.round(position.coords.accuracy);

                    if (this.userMarker) {
                        this.userMarker.setMap(null);
                    }

                    this.userMarker = new google.maps.Marker({
                        position: this.userLocation,
                        map: this.map,
                        title: 'Votre position',
                        icon: {
                            path: google.maps.SymbolPath.CIRCLE,
                            scale: 8,
                            fillColor: '#ef4444',
                            fillOpacity: 1,
                            strokeColor: '#ffffff',
                            strokeWeight: 3,
                        },
                    });

                    if (this.showRadiusCircle) {
                        this.drawRadiusCircle(this.userLocation.lat, this.userLocation.lng, this.currentRadius);
                    }

                    globalThis.dispatchEvent(new CustomEvent('map:user-location', {
                        detail: { ...this.userLocation, accuracy },
                    }));
                },
                (error) => {
                    console.warn('Géolocalisation non disponible:', error.message);
                },
                { enableHighAccuracy: true, timeout: 15000, maximumAge: 10000 }
            );
        },

        centerOnUser() {
            if (this.userLocation) {
                this.flyTo(this.userLocation.lat, this.userLocation.lng, 14);
                return;
            }

            this.getUserLocation();
        },

        flyTo(lat, lng, zoom = 14) {
            if (!this.map) return;
            this.map.panTo({ lat, lng });
            this.map.setZoom(zoom);
        },

        fitResidences(residences, userLocation = null) {
            if (!this.map || !globalThis.google?.maps) return;

            const bounds = new google.maps.LatLngBounds();
            let hasBounds = false;

            if (userLocation && Number.isFinite(userLocation.lat) && Number.isFinite(userLocation.lng)) {
                bounds.extend(userLocation);
                hasBounds = true;
            }

            (residences || []).forEach((residence) => {
                const lat = Number(residence.location?.latitude || residence.latitude);
                const lng = Number(residence.location?.longitude || residence.longitude);
                if (!Number.isFinite(lat) || !Number.isFinite(lng)) return;
                bounds.extend({ lat, lng });
                hasBounds = true;
            });

            if (!hasBounds) return;

            this.map.fitBounds(bounds);
            google.maps.event.addListenerOnce(this.map, 'bounds_changed', () => {
                if (this.map.getZoom() > 14) {
                    this.map.setZoom(14);
                }
            });
        },

        zoomIn() {
            if (!this.map) return;
            this.map.setZoom((this.map.getZoom() || this.config.zoom) + 1);
        },

        zoomOut() {
            if (!this.map) return;
            this.map.setZoom((this.map.getZoom() || this.config.zoom) - 1);
        },

        formatPriceShort(price) {
            if (price >= 1000000) return `${(price / 1000000).toFixed(1)}M`;
            if (price >= 1000) return `${(price / 1000).toFixed(0)}K`;
            return `${price}`;
        },

        destroy() {
            if (this.resizeHandler) {
                globalThis.removeEventListener('resize', this.resizeHandler);
                globalThis.removeEventListener('orientationchange', this.resizeHandler);
                globalThis.removeEventListener('map:resize', this.resizeHandler);
            }

            this.clearMarkers();

            if (this.userMarker) {
                this.userMarker.setMap(null);
            }

            if (this.radiusCircle) {
                this.radiusCircle.setMap(null);
            }
        },
    };
}
