const POI_ICONS = {
    restaurant: `<svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v10.5m0 0A4.5 4.5 0 1 0 7.5 18H12m0-4.5A4.5 4.5 0 1 1 16.5 18H12M6 3v4m4-4v4M14 3v4m4-4v4"/></svg>`,
    supermarket: `<svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 0 0-16.536-1.84M7.5 14.25 5.106 5.272M6 20.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm12.75 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z"/></svg>`,
    pharmacy: `<svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v6m3-3H9m12 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>`,
    hospital: `<svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21"/></svg>`,
    bank: `<svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21v-8.25M15.75 21v-8.25M8.25 21v-8.25M3 9l9-6 9 6m-1.5 12V10.332A48.36 48.36 0 0 0 12 9.75c-2.551 0-5.056.2-7.5.582V21M3 21h18M12 6.75h.008v.008H12V6.75Z"/></svg>`,
    transport: `<svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 0 1-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 0 0-3.213-9.193 2.056 2.056 0 0 0-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 0 0-10.026 0 1.106 1.106 0 0 0-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12"/></svg>`,
    beach: `<svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3c-4.97 0-9 4.03-9 9h18c0-4.97-4.03-9-9-9ZM3 21h18M7 21v-3m5 3v-5m5 5v-3"/></svg>`,
    mall: `<svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 21v-7.5a.75.75 0 0 1 .75-.75h3a.75.75 0 0 1 .75.75V21m-4.5 0H2.36m11.14 0H18m0 0h3.64m-1.39 0V9.349M3.75 21V9.349m0 0a3.001 3.001 0 0 0 3.75-.615A2.993 2.993 0 0 0 9.75 9.75c.896 0 1.7-.393 2.25-1.016a2.993 2.993 0 0 0 2.25 1.016 2.993 2.993 0 0 0 2.25-1.016 3.001 3.001 0 0 0 3.75.614m-16.5 0a3.004 3.004 0 0 1-.621-4.72l1.189-1.19A1.5 1.5 0 0 1 5.378 3h13.243a1.5 1.5 0 0 1 1.06.44l1.19 1.189a3 3 0 0 1-.621 4.72M6.75 18h3.75a.75.75 0 0 0 .75-.75V13.5a.75.75 0 0 0-.75-.75H6.75a.75.75 0 0 0-.75.75v3.75c0 .414.336.75.75.75Z"/></svg>`,
    school: `<svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.438 60.438 0 0 0-.491 6.347A48.62 48.62 0 0 1 12 20.904a48.62 48.62 0 0 1 8.232-4.41 60.46 60.46 0 0 0-.491-6.347m-15.482 0a50.636 50.636 0 0 0-2.658-.813A59.906 59.906 0 0 1 12 3.493a59.903 59.903 0 0 1 10.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.717 50.717 0 0 1 12 13.489a50.702 50.702 0 0 1 3.741-3.342M6.75 15a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Zm0 0v-3.675A55.378 55.378 0 0 1 12 8.443m-7.007 11.55A5.981 5.981 0 0 0 6.75 15.75v-1.5"/></svg>`,
    mosque: `<svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3a5.25 5.25 0 0 0-5.25 5.25c0 2.09 1.224 3.9 3 4.74V21h4.5V12.99a5.251 5.251 0 0 0 3-4.74A5.25 5.25 0 0 0 12 3ZM4.5 21h15M7.5 21v-5.25M16.5 21v-5.25"/></svg>`,
    church: `<svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 2v4m0 0V2m0 4h4m-4 0H8m4 0v3m0 0H9.5a1.5 1.5 0 0 0-1.5 1.5V21h8V10.5A1.5 1.5 0 0 0 14.5 9H12Zm0 3v9"/></svg>`,
    park: `<svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1m0 16v1M5.636 5.636l.707.707M17.657 17.657l.707.707M3 12h1m16 0h1M5.636 18.364l.707-.707M17.657 6.343l.707-.707M12 8a4 4 0 1 0 0 8 4 4 0 0 0 0-8Z"/></svg>`,
    gym: `<svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M6.429 9.75 2.25 12l4.179 2.25m0-4.5 5.571 3 5.571-3m-11.142 0L2.25 7.5 12 2.25l9.75 5.25-4.179 2.25m0 0L21.75 12l-4.179 2.25m0 0 4.179 2.25L12 21.75 2.25 16.5l4.179-2.25m11.142 0-5.571 3-5.571-3"/></svg>`,
    other: `<svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z"/></svg>`,
};

export function nearbyPOI(residenceId, apiBase) {
    return {
        groups: [],
        loading: true,
        expanded: null,
        async init() {
            try {
                const res = await fetch(`${apiBase}/residences/${residenceId}/nearby`);
                const json = await res.json();
                if (json.success) {
                    this.groups = json.data.map(g => ({
                        ...g,
                        svg: POI_ICONS[g.type] ?? POI_ICONS.other,
                    }));
                }
            } catch (e) {
                // silently degrade — POI section simply stays empty
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
