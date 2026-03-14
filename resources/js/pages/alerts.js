/**
 * Alerts page — notification permission request
 * Extracted from client/alerts.blade.php
 */
export function requestNotificationPermission() {
    if ('Notification' in window) {
        Notification.requestPermission().then(function (permission) {
            if (permission === 'granted') {
                alert('Notifications activées ! Vous serez alerté des nouvelles résidences.');
            } else {
                alert('Les notifications sont désactivées. Vous pouvez les activer dans les paramètres de votre navigateur.');
            }
        });
    } else {
        alert('Votre navigateur ne supporte pas les notifications.');
    }
}
