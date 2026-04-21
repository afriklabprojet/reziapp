/**
 * Booking Create — Airbnb-style Alpine.js component
 *
 * Self-contained component (no Alpine store needed).
 * Handles: inline date/guest editors, real-time AJAX pricing,
 * promo codes, form validation, submit guard.
 */

const MONTHS_FR = [
    'janvier', 'février', 'mars', 'avril', 'mai', 'juin',
    'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'
];

/**
 * Format a date string (YYYY-MM-DD) to French short format: "22 fév."
 */
function formatDateShort(dateStr) {
    if (!dateStr) return '';
    const d = new Date(dateStr + 'T00:00:00');
    const day = d.getDate();
    const monthShort = MONTHS_FR[d.getMonth()].substring(0, 3);
    return `${day} ${monthShort}.`;
}

/**
 * Format a number to FCFA currency string: "10 000 FCFA"
 */
function formatFCFA(amount) {
    if (amount == null || isNaN(amount)) return '0 FCFA';
    return Math.round(amount).toLocaleString('fr-FR') + ' FCFA';
}

/**
 * Main Alpine component for the booking creation page.
 */
export default function bookingCreateForm(config) {
    return {
        // ─── Config ───
        maxGuests: config.maxGuests || 10,
        minNights: config.minNights || 1,
        maxNights: config.maxNights || 365,
        calendar: config.calendar || {},
        residenceId: config.residenceId,
        csrfToken: config.csrfToken,
        isInstant: config.isInstant || false,
        pricePerNight: config.pricePerNight || 0,

        // ─── State ───
        checkIn: config.checkInInit || '',
        checkOut: config.checkOutInit || '',
        adults: config.adultsInit || 1,
        children: config.childrenInit || 0,
        infants: config.infantsInit || 0,
        message: '',
        paymentMethod: 'wave',

        // ─── Price ───
        price: config.pricePreview || null,
        loading: false,

        // ─── UI toggles ───
        editingDates: false,
        editingGuests: false,
        showPromo: false,
        submitting: false,

        // ─── Code de réduction (promo ou coupon unifié) ───
        discountCode: '',
        codeError: '',
        codeSuccess: '',
        appliedCode: '',
        appliedCodeType: '', // 'promo' or 'coupon'

        // ─── Errors ───
        bookingError: '',

        // ─── Calendar ───
        currentMonth: new Date(),

        // ═══════════════════════════════════════════
        // COMPUTED
        // ═══════════════════════════════════════════

        /** Label: "22 fév. – 25 fév." or "Sélectionnez des dates" */
        get datesLabel() {
            if (this.checkIn && this.checkOut) {
                const nights = this.nightsCount;
                return `${formatDateShort(this.checkIn)} – ${formatDateShort(this.checkOut)} (${nights} nuit${nights > 1 ? 's' : ''})`;
            }
            if (this.checkIn) {
                return `${formatDateShort(this.checkIn)} – Choisir le départ`;
            }
            return 'Sélectionnez des dates';
        },

        /** Label: "2 voyageurs, 1 bébé" */
        get guestsLabel() {
            const total = this.adults + this.children;
            let label = `${total} voyageur${total > 1 ? 's' : ''}`;
            if (this.infants > 0) {
                label += `, ${this.infants} bébé${this.infants > 1 ? 's' : ''}`;
            }
            return label;
        },

        /** Number of nights between check-in and check-out */
        get nightsCount() {
            if (!this.checkIn || !this.checkOut) return 0;
            const a = new Date(this.checkIn + 'T00:00:00');
            const b = new Date(this.checkOut + 'T00:00:00');
            return Math.max(0, Math.round((b - a) / 86400000));
        },

        /** Can we submit the form? */
        get canSubmit() {
            if (!this.checkIn || !this.checkOut) return false;
            if (this.nightsCount < this.minNights) return false;
            if (this.nightsCount > this.maxNights) return false;
            if (!this.price) return false;
            if (this.loading) return false;
            if (!this.paymentMethod) return false;
            return true;
        },

        // ═══════════════════════════════════════════
        // CALENDAR
        // ═══════════════════════════════════════════

        get currentMonthName() {
            const m = this.currentMonth.getMonth();
            const y = this.currentMonth.getFullYear();
            return MONTHS_FR[m].charAt(0).toUpperCase() + MONTHS_FR[m].slice(1) + ' ' + y;
        },

        get calendarDays() {
            const days = [];
            const year = this.currentMonth.getFullYear();
            const month = this.currentMonth.getMonth();
            const firstDay = new Date(year, month, 1);
            const lastDay = new Date(year, month + 1, 0);
            const today = new Date();
            today.setHours(0, 0, 0, 0);

            // Padding for start of week (Monday = 1)
            const startDay = firstDay.getDay() || 7; // Sunday = 7
            for (let i = 1; i < startDay; i++) {
                days.push({ key: `pad-${i}`, isEmpty: true, available: false, dayOfMonth: '', date: '' });
            }

            // Actual days
            for (let i = 1; i <= lastDay.getDate(); i++) {
                const date = new Date(year, month, i);
                const dateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(i).padStart(2, '0')}`;
                const calData = this.calendar[dateStr] || {};
                const isPast = date < today;

                days.push({
                    key: dateStr,
                    date: dateStr,
                    dayOfMonth: i,
                    available: !isPast && calData.available !== false,
                    isEmpty: false,
                });
            }

            return days;
        },

        previousMonth() {
            const d = new Date(this.currentMonth);
            d.setMonth(d.getMonth() - 1);
            this.currentMonth = d;
        },

        nextMonth() {
            const d = new Date(this.currentMonth);
            d.setMonth(d.getMonth() + 1);
            this.currentMonth = d;
        },

        selectDate(date) {
            if (!date) return;

            if (!this.checkIn || (this.checkIn && this.checkOut)) {
                // Start fresh selection
                this.checkIn = date;
                this.checkOut = '';
                this.price = null;
                this.bookingError = '';
            } else {
                // Second click → set checkout
                if (date > this.checkIn) {
                    this.checkOut = date;
                    this.calculatePrice();
                } else if (date < this.checkIn) {
                    // Clicked before check-in → reset
                    this.checkIn = date;
                    this.checkOut = '';
                    this.price = null;
                } else {
                    // Same date → reset
                    this.checkIn = '';
                    this.checkOut = '';
                    this.price = null;
                }
            }
        },

        isDateSelected(date) {
            return date && (date === this.checkIn || date === this.checkOut);
        },

        isDateInRange(date) {
            if (!date || !this.checkIn || !this.checkOut) return false;
            return date > this.checkIn && date < this.checkOut;
        },

        // ═══════════════════════════════════════════
        // GUESTS
        // ═══════════════════════════════════════════

        syncGuests() {
            // Recalculate price when guests change
            if (this.checkIn && this.checkOut) {
                this.calculatePrice();
            }
        },

        // ═══════════════════════════════════════════
        // PRICING
        // ═══════════════════════════════════════════

        formatCurrency(amount) {
            return formatFCFA(amount);
        },

        async calculatePrice() {
            if (!this.checkIn || !this.checkOut) return;

            // Validate nights
            const nights = this.nightsCount;
            if (nights < this.minNights) {
                this.bookingError = `Le séjour minimum est de ${this.minNights} nuit${this.minNights > 1 ? 's' : ''}.`;
                this.price = null;
                return;
            }
            if (nights > this.maxNights) {
                this.bookingError = `Le séjour maximum est de ${this.maxNights} nuits.`;
                this.price = null;
                return;
            }

            this.loading = true;
            this.bookingError = '';

            try {
                const response = await fetch(`/residences/${this.residenceId}/calculate-price`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        check_in: this.checkIn,
                        check_out: this.checkOut,
                        guests: this.adults + this.children,
                        promo_code: this.appliedCodeType === 'promo' ? this.appliedCode : null,
                        coupon_code: this.appliedCodeType === 'coupon' ? this.appliedCode : null,
                    }),
                });

                const data = await response.json();

                if (data.success) {
                    this.price = data.price;
                    this.bookingError = '';
                } else {
                    this.bookingError = data.error || 'Impossible de calculer le prix pour ces dates.';
                    this.price = null;
                }
            } catch (error) {
                console.error('Erreur calcul prix:', error);
                this.bookingError = 'Erreur de connexion. Vérifiez votre réseau et réessayez.';
                this.price = null;
            } finally {
                this.loading = false;
            }
        },

        // ═══════════════════════════════════════════
        // CODE DE RÉDUCTION (unifié promo + coupon)
        // ═══════════════════════════════════════════

        async applyCode() {
            if (!this.discountCode.trim()) return;
            if (!this.price) {
                this.codeError = 'Sélectionnez d\'abord vos dates pour appliquer un code.';
                return;
            }

            this.codeError = '';
            this.codeSuccess = '';

            try {
                const response = await fetch('/api/codes/validate', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        code: this.discountCode,
                        residence_id: this.residenceId,
                        subtotal: this.price.subtotal,
                        nights: this.price.nights,
                    }),
                });

                const data = await response.json();

                if (data.valid) {
                    this.codeSuccess = `Code appliqué ! -${data.formatted_discount || formatFCFA(data.discount)}`;
                    this.appliedCode = this.discountCode;
                    this.appliedCodeType = data.type; // 'promo' or 'coupon'
                    // Recalculate with the code
                    await this.calculatePrice();
                } else {
                    this.codeError = data.error || 'Code invalide ou expiré.';
                }
            } catch (_error) {
                this.codeError = 'Erreur de connexion lors de la validation.';
            }
        },

        // ═══════════════════════════════════════════
        // INIT
        // ═══════════════════════════════════════════

        init() {
            // If we arrived with dates from the show page, auto-calculate
            if (this.checkIn && this.checkOut && !this.price) {
                this.calculatePrice();
            }

            // Navigate calendar to check-in month
            if (this.checkIn) {
                const d = new Date(this.checkIn + 'T00:00:00');
                this.currentMonth = new Date(d.getFullYear(), d.getMonth(), 1);
            }

            // On mobile, auto-open the date editor if no dates are set
            // so users don't have to hunt for the "Modifier" button
            if (!this.checkIn && !this.checkOut && window.innerWidth < 768) {
                this.editingDates = true;
            }
        },
    };
}

/**
 * Legacy export for backward compat (store pattern removed).
 */
export function initBookingStore(config) {
    return {
        checkIn: config.checkIn || '',
        checkOut: config.checkOut || '',
        guests: config.guests || 1,
        adults: 1,
        children: 0,
        infants: 0,
        message: '',
        appliedPromoCode: '',
        appliedCouponCode: '',
        appliedCode: '',
        appliedCodeType: '',
        price: config.price || null,
        loading: false,
    };
}
