export default function calendarApp(config = {}) {
    return {
        currentMonth: new Date(),
        calendar: config.calendar || {},
        selectedDates: [],

        get currentMonthName() {
            return this.currentMonth.toLocaleDateString('fr-FR', { month: 'long', year: 'numeric' });
        },

        get calendarDays() {
            const days = [];
            const year = this.currentMonth.getFullYear();
            const month = this.currentMonth.getMonth();
            const firstDay = new Date(year, month, 1);
            const lastDay = new Date(year, month + 1, 0);

            // Jours vides au début
            const startDay = firstDay.getDay() || 7;
            for (let i = 1; i < startDay; i++) {
                days.push({ isEmpty: true, dayOfMonth: '' });
            }

            // Jours du mois
            for (let i = 1; i <= lastDay.getDate(); i++) {
                const date = new Date(year, month, i);
                const dateStr = date.toISOString().split('T')[0];
                const calData = this.calendar[dateStr] || {};

                days.push({
                    date: dateStr,
                    dayOfMonth: i,
                    isEmpty: false,
                    available: calData.available !== false,
                    hasBooking: !calData.available && calData.available !== undefined,
                    isBlocked: !calData.available,
                    price: calData.price,
                    isSelected: this.selectedDates.includes(dateStr),
                });
            }

            return days;
        },

        previousMonth() {
            this.currentMonth = new Date(this.currentMonth.setMonth(this.currentMonth.getMonth() - 1));
        },

        nextMonth() {
            this.currentMonth = new Date(this.currentMonth.setMonth(this.currentMonth.getMonth() + 1));
        },

        selectDate(day) {
            const index = this.selectedDates.indexOf(day.date);
            if (index > -1) {
                this.selectedDates.splice(index, 1);
            } else {
                this.selectedDates.push(day.date);
            }
        }
    };
}
