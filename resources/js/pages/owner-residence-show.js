/**
 * Owner residence show — Google Maps init
 * Extracted from owner/residences/show.blade.php
 */
export default function ownerResidenceMap(config = {}) {
    return {
        init() {
            const position = {
                lat: config.lat,
                lng: config.lng
            };

            if (typeof google === 'undefined') return;

            const map = new google.maps.Map(document.getElementById('map'), {
                center: position,
                zoom: 15,
                styles: [
                    {
                        featureType: 'poi',
                        stylers: [{ visibility: 'off' }]
                    }
                ]
            });

            new google.maps.Marker({
                position: position,
                map: map,
                title: config.title || ''
            });
        }
    };
}
