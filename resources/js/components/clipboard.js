/**
 * Clipboard copy utility — used across multiple pages
 * Exposed as window globals for inline onclick handlers.
 */
export function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        alert('Copié !');
    });
}

export function copyShareLink(fallbackUrl) {
    const el = document.getElementById('shareUrl');
    const url = el ? el.value : fallbackUrl;
    navigator.clipboard.writeText(url).then(() => {
        alert('Lien copié !');
    });
}
