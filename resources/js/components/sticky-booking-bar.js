/**
 * Sticky Booking Bar - Alpine.js component for the mobile sticky booking bar
 * on the residence detail page.
 * Extracted from resources/views/residences/show.blade.php
 * for @alpinejs/csp compatibility.
 *
 * Usage in Blade:
 *   x-data="stickyBookingBar(@js(['bookingBaseUrl' => route('bookings.create', $residence)]))"
 */
export default function stickyBookingBar(config = {}) {
    return {
        showBar: false,
        checkIn: '',
        checkOut: '',
        bookingBaseUrl: config.bookingBaseUrl || '',

        get bookingUrl() {
            const params = new URLSearchParams();
            if (this.checkIn) params.set('check_in', this.checkIn);
            if (this.checkOut) params.set('check_out', this.checkOut);
            const qs = params.toString();
            return qs ? this.bookingBaseUrl + '?' + qs : this.bookingBaseUrl;
        },

        init() {
            const bc = document.querySelector('.booking-card');
            if (bc) {
                new IntersectionObserver(
                    ([e]) => { this.showBar = !e.isIntersecting; },
                    { threshold: 0 }
                ).observe(bc);
            } else {
                this.showBar = true;
            }
            window.addEventListener('calendar-dates-selected', e => {
                this.checkIn = e.detail.checkIn || '';
                this.checkOut = e.detail.checkOut || '';
            });
        },
    };
}
