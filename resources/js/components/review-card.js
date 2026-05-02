/**
 * Review card interactions — helpful vote + report modal
 * Extracted from reviews/partials/review-card.blade.php
 *
 * These are exposed as window globals because they're invoked from
 * inline onclick="" handlers in the Blade template partial.
 */

export function toggleHelpful(reviewId) {
    fetch(`/reviews/${reviewId}/helpful`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json'
        }
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const btn = document.querySelector(`.helpful-btn-${reviewId}`);
                const count = document.querySelector(`.helpful-count-${reviewId}`);

                count.textContent = data.helpful_count;

                if (data.has_voted) {
                    btn.classList.remove('text-gray-500');
                    btn.classList.add('text-[#ff385c]');
                } else {
                    btn.classList.remove('text-[#ff385c]');
                    btn.classList.add('text-gray-500');
                }
            }
        });
}

export function openReportModal(reviewId) {
    const modal = document.getElementById('report-modal');
    if (modal) {
        document.getElementById('report-review-id').value = reviewId;
        modal.classList.remove('hidden');
    }
}
