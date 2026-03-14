/**
 * Review creation form — star ratings
 * Extracted from reviews/create.blade.php
 */
export default function reviewForm(config = {}) {
    return {
        ratings: {
            rating_cleanliness: config.rating_cleanliness || 0,
            rating_location: config.rating_location || 0,
            rating_value: config.rating_value || 0,
            rating_communication: config.rating_communication || 0
        },
        ratingLabels: {
            0: '',
            1: 'Très mauvais',
            2: 'Mauvais',
            3: 'Correct',
            4: 'Bien',
            5: 'Excellent'
        },
        get overallRating() {
            const values = Object.values(this.ratings).filter(v => v > 0);
            if (values.length === 0) return 0;
            return values.reduce((a, b) => a + b, 0) / values.length;
        },
        get isValid() {
            return Object.values(this.ratings).every(v => v > 0);
        }
    };
}
