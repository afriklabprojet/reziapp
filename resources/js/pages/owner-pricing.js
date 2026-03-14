export default function pricingCalendar(config = {}) {
    return {
        currentDate: new Date(),
        calendarDays: [],
        selectedDates: [],
        residenceId: config.residenceId,
        basePrice: config.basePrice || 0,

        init() {
            this.loadCalendar();
        },

        get currentMonthName() {
            return this.currentDate.toLocaleDateString('fr-FR', { month: 'long', year: 'numeric' });
        },

        previousMonth() {
            this.currentDate = new Date(this.currentDate.getFullYear(), this.currentDate.getMonth() - 1, 1);
            this.loadCalendar();
        },

        nextMonth() {
            this.currentDate = new Date(this.currentDate.getFullYear(), this.currentDate.getMonth() + 1, 1);
            this.loadCalendar();
        },

        async loadCalendar() {
            const year = this.currentDate.getFullYear();
            const month = this.currentDate.getMonth();
            const firstDay = new Date(year, month, 1);
            const lastDay = new Date(year, month + 1, 0);

            // Fetch calendar data
            const response = await fetch(`/owner/residences/${this.residenceId}/pricing/calendar?start=${firstDay.toISOString().split('T')[0]}&end=${lastDay.toISOString().split('T')[0]}`);
            const data = await response.json();
            const priceMap = {};
            data.calendar.forEach(d => priceMap[d.date] = d);

            // Build calendar days
            this.calendarDays = [];
            const startWeekDay = (firstDay.getDay() + 6) % 7; // Monday = 0

            // Empty cells before first day
            for (let i = 0; i < startWeekDay; i++) {
                this.calendarDays.push({ date: null, dayNumber: '' });
            }

            // Days of the month
            const today = new Date();
            today.setHours(0, 0, 0, 0);

            for (let day = 1; day <= lastDay.getDate(); day++) {
                const date = new Date(year, month, day);
                const dateStr = date.toISOString().split('T')[0];
                const priceData = priceMap[dateStr] || {};

                this.calendarDays.push({
                    date: dateStr,
                    dayNumber: day,
                    price: priceData.price || this.basePrice,
                    isAvailable: priceData.is_available !== false,
                    priceType: priceData.price_type || 'base',
                    isPast: date < today
                });
            }
        },

        selectDate(day) {
            if (!day.date || day.isPast) return;

            const index = this.selectedDates.indexOf(day.date);
            if (index > -1) {
                this.selectedDates.splice(index, 1);
            } else {
                this.selectedDates.push(day.date);
            }
        },

        clearSelection() {
            this.selectedDates = [];
        },

        formatPrice(price) {
            return new Intl.NumberFormat('fr-FR').format(price);
        },

        openPriceModal() {
            this.$dispatch('open-price-modal', { currentPrice: this.basePrice });
        },

        async toggleAvailability(available) {
            await this.updateDailyPrices({ is_available: available });
        },

        async updateDailyPrices(data) {
            const response = await fetch(`/owner/residences/${this.residenceId}/pricing/daily`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                    dates: this.selectedDates,
                    ...data
                })
            });

            if (response.ok) {
                this.loadCalendar();
                this.clearSelection();
            }
        }
    };
}

// Listen for price confirmation — auto-register on import
document.addEventListener('confirm-price', async (e) => {
    const el = document.querySelector('[x-data*="pricingCalendar"]');
    if (el && window.Alpine) {
        const calendar = window.Alpine.$data(el);
        await calendar.updateDailyPrices({ price: e.detail.price, reason: e.detail.reason });
    }
});
