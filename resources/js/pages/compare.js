/**
 * Compare page — toggleFavorite
 * Extracted from client/compare.blade.php
 */
export function toggleFavorite(residenceId) {
    fetch(`/favorites/${residenceId}/toggle`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        }
    })
        .then(response => response.json())
        .then(() => {
            window.location.reload();
        })
        .catch(error => console.error('Error:', error));
}
