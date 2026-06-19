export function nearbyPOI(residenceId, apiBase) {
    return {
        groups: [],
        loading: true,
        expanded: null,
        async init() {
            try {
                const res = await fetch(`${apiBase}/residences/${residenceId}/nearby`);
                const json = await res.json();
                if (json.success) this.groups = json.data;
            } catch (e) {
                // silently degrade — POI section stays empty
            } finally {
                this.loading = false;
            }
        },
    };
}

export function directionsWidget(residenceId, apiBase) {
    return {
        mode: 'driving',
        modes: [
            { value: 'driving', icon: '🚗', label: 'Voiture' },
            { value: 'walking', icon: '🚶', label: 'À pied' },
            { value: 'transit', icon: '🚌', label: 'Transport' },
        ],
        result: null,
        loading: false,
        error: null,
        showSteps: false,
        async getDirections() {
            this.loading = true;
            this.error = null;
            try {
                const pos = await this.getUserPosition();
                const res = await fetch(`${apiBase}/residences/${residenceId}/directions?lat=${pos.lat}&lng=${pos.lng}&mode=${this.mode}`);
                const json = await res.json();
                if (json.success) {
                    this.result = json.data;
                } else {
                    this.error = json.message || 'Itinéraire non disponible';
                }
            } catch (e) {
                console.error('Directions error:', e);
                this.error = "Activez la géolocalisation pour calculer l'itinéraire";
            } finally {
                this.loading = false;
            }
        },
        getUserPosition() {
            return new Promise((resolve, reject) => {
                if (!navigator.geolocation) return reject(new Error('Géolocalisation non supportée'));
                navigator.geolocation.getCurrentPosition(
                    p => resolve({ lat: p.coords.latitude, lng: p.coords.longitude }),
                    () => reject(new Error('Position refusée')),
                    { timeout: 10000, maximumAge: 300000 },
                );
            });
        },
    };
}

export function streetViewWidget(residenceId, apiBase) {
    return {
        available: false,
        imageUrl: null,
        panorama: [],
        async init() {
            try {
                const res = await fetch(`${apiBase}/residences/${residenceId}/streetview`);
                const json = await res.json();
                if (json.available) {
                    this.available = true;
                    this.imageUrl = json.image_url;
                    this.panorama = json.panorama || [];
                }
            } catch (e) {
                console.error('Street View error:', e);
            }
        },
    };
}

export function isochroneWidget(residenceId, apiBase, lat, lng, residenceName) {
    return {
        profile: 'walking',
        profiles: [
            { value: 'walking', icon: '🚶', label: 'À pied' },
            { value: 'cycling', icon: '🚴', label: 'Vélo' },
            { value: 'driving', icon: '🚗', label: 'Voiture' },
        ],
        data: null,
        loading: false,
        error: null,
        map: null,
        async init() {
            await this.fetchIsochrone();
        },
        async fetchIsochrone() {
            this.loading = true;
            this.error = null;
            try {
                const res = await fetch(`${apiBase}/residences/${residenceId}/isochrone?profile=${this.profile}&minutes[]=5&minutes[]=10&minutes[]=15`);
                const json = await res.json();
                if (json.success) {
                    this.data = json.data;
                    this.$nextTick(() => this.renderMap(json.data));
                } else {
                    this.error = json.message;
                }
            } catch (e) {
                console.error('Isochrone error:', e);
                this.error = 'Zones accessibles non disponibles';
            } finally {
                this.loading = false;
            }
        },
        renderMap(geojson) {
            const container = this.$refs.isochroneMap;
            if (!container || !globalThis.google?.maps) return;

            this.polygons?.forEach((polygon) => polygon.setMap(null));

            this.map = new google.maps.Map(container, {
                center: { lat, lng },
                zoom: 13,
                disableDefaultUI: true,
                streetViewControl: false,
                fullscreenControl: false,
                mapTypeControl: false,
            });

            this.marker = new google.maps.Marker({
                position: { lat, lng },
                map: this.map,
                title: residenceName,
            });

            if (!geojson?.features) return;

            const colors = ['#ef4444', '#eab308', '#22c55e'];
            this.polygons = [];
            const bounds = new google.maps.LatLngBounds();

            const buildPath = (ring) => ring.map(([pLng, pLat]) => {
                const point = { lat: pLat, lng: pLng };
                bounds.extend(point);
                return point;
            });

            const renderPolygon = (polygonSet, idx) => {
                const outerRing = polygonSet?.[0] ?? [];
                const path = buildPath(outerRing);
                if (!path.length) return;
                const polygon = new google.maps.Polygon({
                    paths: path,
                    strokeColor: colors[idx] || '#888',
                    strokeOpacity: 0.6,
                    strokeWeight: 1.5,
                    fillColor: colors[idx] || '#888',
                    fillOpacity: 0.2,
                    map: this.map,
                });
                this.polygons.push(polygon);
            };

            [...geojson.features].reverse().forEach((feature, idx) => {
                const geometry = feature.geometry || {};
                const polygonSets = geometry.type === 'MultiPolygon'
                    ? geometry.coordinates
                    : [geometry.coordinates];
                polygonSets.forEach((polygonSet) => renderPolygon(polygonSet, idx));
            });

            if (!bounds.isEmpty()) {
                this.map.fitBounds(bounds, 30);
            }
        },
    };
}

export function stickyNav() {
    return {
        visible: false,
        navActive: '',
        sectionIds: ['photos', 'equipements', 'calendrier', 'avis', 'emplacement', 'hote'],
        sentinel: null,
        ticking: false,
        init() {
            this.sentinel = document.getElementById('photo-section');
            window.addEventListener('scroll', () => {
                if (!this.ticking) {
                    requestAnimationFrame(() => {
                        this.onScroll();
                        this.ticking = false;
                    });
                    this.ticking = true;
                }
            }, { passive: true });
        },
        onScroll() {
            if (this.sentinel) {
                const rect = this.sentinel.getBoundingClientRect();
                this.visible = rect.bottom < 80;
            }
            let current = '';
            for (const id of this.sectionIds) {
                const el = document.getElementById(id);
                if (el && el.getBoundingClientRect().top <= 140) {
                    current = id;
                }
            }
            this.navActive = current;
        },
        navScrollTo(id) {
            const el = document.getElementById(id);
            if (!el) return;
            const y = el.getBoundingClientRect().top + window.scrollY - 140;
            window.scrollTo({ top: y, behavior: 'smooth' });
        },
    };
}
