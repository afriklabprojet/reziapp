export default function residenceEditForm(config = {}) {
    return {
        latitude: config.latitude || 0,
        longitude: config.longitude || 0,
        map: null,
        marker: null,

        init() {
            this.$nextTick(() => {
                this.initMap();
            });
        },

        initMap() {
            const mapContainer = document.getElementById('location-map');
            if (!mapContainer || typeof google === 'undefined') {
                console.log('Google Maps not available');
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
            });

            // Clic sur la carte pour repositionner
            this.map.addListener('click', (e) => {
                this.latitude = e.latLng.lat();
                this.longitude = e.latLng.lng();
                this.marker.setPosition(e.latLng);
            });
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
