/**
 * Map Search - Alpine.js component for Mapbox map with residence markers & clustering
 * Extracted from resources/views/components/map-search.blade.php
 */
export default function mapSearch(config) {
    return {
        map: null,
        markers: [],
        userMarker: null,
        radiusCircle: null,
        loading: true,
        selectedResidence: null,
        popupPosition: '',
        visibleCount: 0,
        currentRadius: config.radius,
        showRadiusCircle: config.showRadiusCircle,
        userLocation: null,
        config: config,
        useClustering: true,

        async initMap() {
            // Attendre que Mapbox GL JS soit chargé
            if (!window.mapboxgl) {
                let attempts = 0;
                while (!window.mapboxgl && attempts < 50) {
                    await new Promise(r => setTimeout(r, 100));
                    attempts++;
                }
                if (!window.mapboxgl) {
                    console.error('Mapbox GL JS non chargé après attente');
                    this.loading = false;
                    return;
                }
            }

            mapboxgl.accessToken = config.accessToken;

            this.map = new mapboxgl.Map({
                container: this.$refs.mapContainer,
                style: config.style,
                center: [config.center.lng, config.center.lat],
                zoom: config.zoom,
                attributionControl: false,
            });

            this.map.on('load', () => {
                this.loading = false;
                this.addClusterSource(config.residences);
                this.addMarkers(config.residences);

                if (config.showRadiusCircle) {
                    this.drawRadiusCircle(config.center.lat, config.center.lng, config.radius);
                }

                this.getUserLocation();
            });

            this.map.on('moveend', () => {
                this.updateVisibleCount();
            });

            // Écouter les événements Alpine pour la synchronisation
            this.$watch('config.residences', (residences) => {
                this.clearMarkers();
                this.updateClusterSource(residences);
                this.addMarkers(residences);
            });

            // Écouter les événements globaux
            window.addEventListener('map:update-residences', (e) => {
                this.clearMarkers();
                this.updateClusterSource(e.detail.residences);
                this.addMarkers(e.detail.residences);
            });

            window.addEventListener('map:highlight-residence', (e) => {
                this.highlightMarker(e.detail.id);
            });

            window.addEventListener('map:update-radius', (e) => {
                this.updateRadius(e.detail.radius);
            });

            window.addEventListener('map:center-on', (e) => {
                this.flyTo(e.detail.lat, e.detail.lng, e.detail.zoom || 14);
            });
        },

        buildGeoJSON(residences) {
            if (!residences || !Array.isArray(residences)) return { type: 'FeatureCollection', features: [] };

            const features = residences
                .filter(r => {
                    const lat = r.location?.latitude || r.latitude;
                    const lng = r.location?.longitude || r.longitude;
                    return lat && lng;
                })
                .map(r => ({
                    type: 'Feature',
                    geometry: {
                        type: 'Point',
                        coordinates: [
                            r.location?.longitude || r.longitude,
                            r.location?.latitude || r.latitude,
                        ],
                    },
                    properties: {
                        id: r.id,
                        title: r.title,
                        price: r.price,
                        price_label: r.price_label || '/jour',
                        thumbnail: r.thumbnail,
                        commune: r.location?.commune || r.commune || '',
                        quartier: r.location?.quartier || r.quartier || '',
                        is_available: r.is_available !== undefined ? r.is_available : true,
                    },
                }));

            return { type: 'FeatureCollection', features };
        },

        addClusterSource(residences) {
            const geojson = this.buildGeoJSON(residences);

            this.map.addSource('residences-cluster', {
                type: 'geojson',
                data: geojson,
                cluster: true,
                clusterMaxZoom: 14,
                clusterRadius: 50,
            });

            // Cluster circles
            this.map.addLayer({
                id: 'clusters',
                type: 'circle',
                source: 'residences-cluster',
                filter: ['has', 'point_count'],
                paint: {
                    'circle-color': [
                        'step', ['get', 'point_count'],
                        '#10b981', 10,
                        '#059669', 30,
                        '#047857'
                    ],
                    'circle-radius': [
                        'step', ['get', 'point_count'],
                        20, 10,
                        25, 30,
                        30
                    ],
                    'circle-stroke-width': 3,
                    'circle-stroke-color': 'rgba(255,255,255,0.6)',
                },
            });

            // Cluster count labels
            this.map.addLayer({
                id: 'cluster-count',
                type: 'symbol',
                source: 'residences-cluster',
                filter: ['has', 'point_count'],
                layout: {
                    'text-field': ['get', 'point_count_abbreviated'],
                    'text-font': ['DIN Offc Pro Medium', 'Arial Unicode MS Bold'],
                    'text-size': 13,
                },
                paint: {
                    'text-color': '#ffffff',
                },
            });

            // Click on cluster to zoom in
            this.map.on('click', 'clusters', (e) => {
                const features = this.map.queryRenderedFeatures(e.point, { layers: ['clusters'] });
                const clusterId = features[0].properties.cluster_id;
                this.map.getSource('residences-cluster').getClusterExpansionZoom(clusterId, (err, zoom) => {
                    if (err) return;
                    this.map.easeTo({
                        center: features[0].geometry.coordinates,
                        zoom: zoom,
                    });
                });
            });

            this.map.on('mouseenter', 'clusters', () => {
                this.map.getCanvas().style.cursor = 'pointer';
            });
            this.map.on('mouseleave', 'clusters', () => {
                this.map.getCanvas().style.cursor = '';
            });
        },

        updateClusterSource(residences) {
            const source = this.map.getSource('residences-cluster');
            if (source) {
                source.setData(this.buildGeoJSON(residences));
            }
        },

        addMarkers(residences) {
            if (!residences || !Array.isArray(residences)) return;

            residences.forEach((residence) => {
                const lat = residence.location?.latitude || residence.latitude;
                const lng = residence.location?.longitude || residence.longitude;

                if (!lat || !lng) return;

                const el = document.createElement('div');
                el.className = 'residence-marker';
                el.innerHTML = this.createMarkerHTML(residence);
                el.dataset.id = residence.id;

                el.addEventListener('click', (e) => {
                    e.stopPropagation();
                    this.showPopup(residence, e);
                });

                el.addEventListener('mouseenter', () => {
                    window.dispatchEvent(new CustomEvent('map:residence-hover', {
                        detail: { id: residence.id }
                    }));
                });

                el.addEventListener('mouseleave', () => {
                    window.dispatchEvent(new CustomEvent('map:residence-unhover', {
                        detail: { id: residence.id }
                    }));
                });

                const marker = new mapboxgl.Marker({
                    element: el,
                    anchor: 'bottom'
                })
                    .setLngLat([lng, lat])
                    .addTo(this.map);

                this.markers.push({ id: residence.id, marker, element: el });
            });

            this.updateVisibleCount();
        },

        createMarkerHTML(residence) {
            const price = this.formatPriceShort(residence.price);
            const priceLabel = residence.price_label || '/jour';
            const isAvailable = residence.is_available !== undefined ? residence.is_available : true;
            const bgColor = isAvailable ? 'bg-emerald-600' : 'bg-gray-400';
            const borderColor = isAvailable ? 'border-t-emerald-600' : 'border-t-gray-400';
            const statusDot = isAvailable
                ? '<span class="w-1.5 h-1.5 rounded-full bg-emerald-300 inline-block mr-0.5 animate-pulse"></span>'
                : '<span class="w-1.5 h-1.5 rounded-full bg-red-300 inline-block mr-0.5"></span>';
            return `
                <div class="relative">
                    <div class="${bgColor} text-white px-2 py-1 rounded-lg text-xs font-bold shadow-lg whitespace-nowrap flex items-center gap-0.5">
                        ${statusDot}${price}<span class="font-normal text-white/80">${priceLabel}</span>
                    </div>
                    <div class="absolute left-1/2 -translate-x-1/2 w-0 h-0 border-l-4 border-r-4 border-t-4 border-transparent ${borderColor}"></div>
                </div>
            `;
        },

        clearMarkers() {
            this.markers.forEach(({ marker }) => marker.remove());
            this.markers = [];
        },

        showPopup(residence, event) {
            this.selectedResidence = residence;

            // Calculer la position du popup
            const rect = event.target.getBoundingClientRect();
            this.popupPosition = `top: ${rect.bottom + 10}px; left: ${rect.left - 100}px;`;

            // Centrer la carte sur le marker
            const lat = residence.location?.latitude || residence.latitude;
            const lng = residence.location?.longitude || residence.longitude;
            this.map.easeTo({
                center: [lng, lat],
                duration: 500
            });
        },

        highlightMarker(id) {
            this.markers.forEach(({ element, marker }) => {
                if (element.dataset.id === String(id)) {
                    element.classList.add('active');
                    // Amener au premier plan
                    const lat = marker.getLngLat().lat;
                    const lng = marker.getLngLat().lng;
                    this.map.easeTo({ center: [lng, lat], duration: 300 });
                } else {
                    element.classList.remove('active');
                }
            });
        },

        drawRadiusCircle(lat, lng, radiusKm) {
            // Supprimer l'ancien cercle
            if (this.map.getSource('radius-circle')) {
                this.map.removeLayer('radius-circle-fill');
                this.map.removeLayer('radius-circle-border');
                this.map.removeSource('radius-circle');
            }

            // Créer un cercle GeoJSON
            const circle = this.createGeoJSONCircle([lng, lat], radiusKm);

            this.map.addSource('radius-circle', {
                type: 'geojson',
                data: circle
            });

            this.map.addLayer({
                id: 'radius-circle-fill',
                type: 'fill',
                source: 'radius-circle',
                paint: {
                    'fill-color': '#3b82f6',
                    'fill-opacity': 0.1
                }
            });

            this.map.addLayer({
                id: 'radius-circle-border',
                type: 'line',
                source: 'radius-circle',
                paint: {
                    'line-color': '#3b82f6',
                    'line-width': 2,
                    'line-dasharray': [3, 2]
                }
            });

            this.currentRadius = radiusKm;
        },

        createGeoJSONCircle(center, radiusKm, points = 64) {
            const coords = {
                latitude: center[1],
                longitude: center[0]
            };

            const km = radiusKm;
            const ret = [];
            const distanceX = km / (111.32 * Math.cos(coords.latitude * Math.PI / 180));
            const distanceY = km / 110.574;

            for (let i = 0; i < points; i++) {
                const theta = (i / points) * (2 * Math.PI);
                const x = distanceX * Math.cos(theta);
                const y = distanceY * Math.sin(theta);
                ret.push([coords.longitude + x, coords.latitude + y]);
            }
            ret.push(ret[0]);

            return {
                type: 'Feature',
                geometry: {
                    type: 'Polygon',
                    coordinates: [ret]
                }
            };
        },

        updateRadius(radiusKm) {
            if (this.userLocation) {
                this.drawRadiusCircle(this.userLocation.lat, this.userLocation.lng, radiusKm);
            } else {
                this.drawRadiusCircle(this.config.center.lat, this.config.center.lng, radiusKm);
            }
        },

        updateVisibleCount() {
            if (!this.map) return;

            const bounds = this.map.getBounds();
            this.visibleCount = this.markers.filter(({ marker }) => {
                const lngLat = marker.getLngLat();
                return bounds.contains(lngLat);
            }).length;
        },

        getUserLocation() {
            if (!navigator.geolocation) return;

            navigator.geolocation.getCurrentPosition(
                (position) => {
                    this.userLocation = {
                        lat: position.coords.latitude,
                        lng: position.coords.longitude
                    };

                    const accuracy = Math.round(position.coords.accuracy);

                    // Ajouter un marker pour l'utilisateur avec cercle de précision
                    const el = document.createElement('div');
                    el.className = 'user-marker';
                    el.innerHTML = `
                        <div class="relative flex items-center justify-center">
                            <div class="w-4 h-4 bg-red-500 rounded-full border-2 border-white shadow-lg z-10"></div>
                            <div class="absolute w-8 h-8 bg-red-500/20 rounded-full animate-ping" style="animation-duration: 2s;"></div>
                        </div>
                    `;

                    this.userMarker = new mapboxgl.Marker({ element: el })
                        .setLngLat([this.userLocation.lng, this.userLocation.lat])
                        .addTo(this.map);

                    // Mettre à jour le cercle de rayon
                    if (this.showRadiusCircle) {
                        this.drawRadiusCircle(this.userLocation.lat, this.userLocation.lng, this.currentRadius);
                    }

                    // Émettre l'événement avec précision
                    window.dispatchEvent(new CustomEvent('map:user-location', {
                        detail: { ...this.userLocation, accuracy }
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
            } else {
                this.getUserLocation();
            }
        },

        flyTo(lat, lng, zoom = 14) {
            this.map.flyTo({
                center: [lng, lat],
                zoom: zoom,
                duration: 1500,
                essential: true
            });
        },

        zoomIn() {
            this.map.zoomIn();
        },

        zoomOut() {
            this.map.zoomOut();
        },

        formatPrice(price) {
            return new Intl.NumberFormat('fr-FR').format(price);
        },

        formatPriceShort(price) {
            if (price >= 1000000) {
                return (price / 1000000).toFixed(1) + 'M';
            } else if (price >= 1000) {
                return (price / 1000).toFixed(0) + 'K';
            }
            return price;
        },

        // Nettoyer au démontage
        destroy() {
            if (this.map) {
                this.map.remove();
            }
        }
    };
}
