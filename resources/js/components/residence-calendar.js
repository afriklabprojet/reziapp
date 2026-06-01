/**
 * Residence Calendar - Alpine.js component for the booking calendar.
 * Extracted from resources/views/residences/show.blade.php
 * for @alpinejs/csp compatibility.
 *
 * Usage in Blade:
 *   x-data="residenceCalendar(@js(['unavailable' => $unavailableDates ?? []]))"
 */
export default function residenceCalendar(config = {}) {
    return {
        cm: new Date().getMonth(),
        cy: new Date().getFullYear(),
        checkIn: '',
        checkOut: '',
        hoverDate: '',
        selecting: 'checkin',
        unavailable: config.unavailable || [],

        mn: ['Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'],
        dn: ['Di', 'Lu', 'Ma', 'Me', 'Je', 'Ve', 'Sa'],

        get m2() {
            return this.cm === 11 ? 0 : this.cm + 1;
        },

        get y2() {
            return this.cm === 11 ? this.cy + 1 : this.cy;
        },

        next() {
            if (this.cm === 11) { this.cm = 0; this.cy++; }
            else { this.cm++; }
        },

        prev() {
            const t = new Date();
            t.setHours(0, 0, 0, 0);
            const first = new Date(this.cy, this.cm, 1);
            if (first <= t) return;
            if (this.cm === 0) { this.cm = 11; this.cy--; }
            else { this.cm--; }
        },

        dim(m, y) { return new Date(y, m + 1, 0).getDate(); },
        fdm(m, y) { return new Date(y, m, 1).getDay(); },

        fmt(d, m, y) {
            return y + '-' + String(m + 1).padStart(2, '0') + '-' + String(d).padStart(2, '0');
        },

        isT(d, m, y) {
            const t = new Date();
            return d === t.getDate() && m === t.getMonth() && y === t.getFullYear();
        },

        isP(d, m, y) {
            const t = new Date();
            t.setHours(0, 0, 0, 0);
            return new Date(y, m, d) < t;
        },

        isUnavailable(d, m, y) {
            return this.unavailable.includes(this.fmt(d, m, y));
        },

        isBlocked(d, m, y) { return this.isP(d, m, y) || this.isUnavailable(d, m, y); },
        isCheckIn(d, m, y) { return this.checkIn === this.fmt(d, m, y); },
        isCheckOut(d, m, y) { return this.checkOut === this.fmt(d, m, y); },

        inRange(d, m, y) {
            if (!this.checkIn) return false;
            const end = this.checkOut || this.hoverDate;
            if (!end) return false;
            const dt = new Date(y, m, d);
            const s = new Date(this.checkIn);
            const e = new Date(end);
            return dt > s && dt < e;
        },

        isRangeEdge(d, m, y) {
            return this.isCheckIn(d, m, y) || this.isCheckOut(d, m, y);
        },

        selectDate(d, m, y) {
            if (this.isBlocked(d, m, y)) return;
            const ds = this.fmt(d, m, y);
            if (this.selecting === 'checkin' || !this.checkIn) {
                this.checkIn = ds;
                this.checkOut = '';
                this.selecting = 'checkout';
            } else {
                if (ds <= this.checkIn) {
                    this.checkIn = ds;
                    this.checkOut = '';
                    this.selecting = 'checkout';
                } else {
                    const hasBlocked = this.unavailable.some(u => u > this.checkIn && u < ds);
                    if (hasBlocked) {
                        this.checkIn = ds;
                        this.checkOut = '';
                        this.selecting = 'checkout';
                    } else {
                        this.checkOut = ds;
                        this.selecting = 'checkin';
                        this.$dispatch('calendar-dates-selected', { checkIn: this.checkIn, checkOut: this.checkOut });
                        window.dispatchEvent(new CustomEvent('calendar-dates-selected', {
                            detail: { checkIn: this.checkIn, checkOut: this.checkOut },
                        }));
                    }
                }
            }
        },

        clearDates() {
            this.checkIn = '';
            this.checkOut = '';
            this.selecting = 'checkin';
            this.hoverDate = '';
            window.dispatchEvent(new CustomEvent('calendar-dates-selected', {
                detail: { checkIn: '', checkOut: '' },
            }));
        },

        dayClass(d, m, y) {
            if (this.isBlocked(d, m, y)) return 'text-gray-300 line-through cursor-not-allowed';
            if (this.isCheckIn(d, m, y) || this.isCheckOut(d, m, y)) return 'bg-gray-900 text-white font-semibold cursor-pointer rounded-full';
            if (this.inRange(d, m, y)) return 'bg-[#ffd1da] text-[#8e0730] cursor-pointer rounded-none';
            if (this.isT(d, m, y)) return 'ring-2 ring-[#ff4d6d] text-gray-900 font-semibold cursor-pointer rounded-full hover:bg-gray-100';
            return 'text-gray-700 hover:bg-gray-100 cursor-pointer rounded-full';
        },
    };
}
