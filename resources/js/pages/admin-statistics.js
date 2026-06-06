import Chart from 'chart.js/auto';

const COLORS = {
    orange: '#F16A00',
    orangeSoft: 'rgba(241, 106, 0, 0.14)',
    ink: '#0F0F0F',
    inkSoft: 'rgba(15, 15, 15, 0.12)',
    muted: '#555555',
    canvas: '#F2F2F2',
    emerald: '#10B981',
    sky: '#0EA5E9',
    rose: '#F43F5E',
    amber: '#F59E0B',
    violet: '#8B5CF6',
    slate: '#64748B',
};

const chartInstances = [];

function destroyCharts() {
    while (chartInstances.length > 0) {
        const instance = chartInstances.pop();
        instance?.destroy();
    }
}

function parseData() {
    const node = document.getElementById('admin-statistics-data');

    if (!node?.textContent) {
        return null;
    }

    try {
        return JSON.parse(node.textContent);
    } catch (error) {
        return null;
    }
}

function createChart(elementId, configBuilder) {
    const canvas = document.getElementById(elementId);

    if (!canvas) {
        return;
    }

    const context = canvas.getContext('2d');

    if (!context) {
        return;
    }

    chartInstances.push(new Chart(context, configBuilder()));
}

function initRevenueChart(data) {
    createChart('adminRevenueChart', () => ({
        type: 'line',
        data: {
            labels: data.revenueByMonth.map((item) => item.month),
            datasets: [
                {
                    label: 'Revenus',
                    data: data.revenueByMonth.map((item) => item.revenue),
                    borderColor: COLORS.orange,
                    backgroundColor: COLORS.orangeSoft,
                    pointBackgroundColor: COLORS.orange,
                    pointRadius: 4,
                    tension: 0.35,
                    fill: true,
                    yAxisID: 'y',
                },
                {
                    label: 'Réservations',
                    data: data.revenueByMonth.map((item) => item.bookings),
                    borderColor: COLORS.ink,
                    backgroundColor: COLORS.inkSoft,
                    pointBackgroundColor: COLORS.ink,
                    pointRadius: 3,
                    tension: 0.35,
                    fill: false,
                    yAxisID: 'y1',
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                mode: 'index',
                intersect: false,
            },
            plugins: {
                legend: {
                    position: 'bottom',
                },
                tooltip: {
                    callbacks: {
                        label(context) {
                            if (context.dataset.label === 'Revenus') {
                                return `${context.dataset.label}: ${Number(context.raw).toLocaleString('fr-FR')} FCFA`;
                            }

                            return `${context.dataset.label}: ${context.raw}`;
                        },
                    },
                },
            },
            scales: {
                y: {
                    beginAtZero: true,
                    position: 'left',
                    ticks: {
                        callback(value) {
                            return Number(value).toLocaleString('fr-FR');
                        },
                    },
                    grid: {
                        color: 'rgba(15, 15, 15, 0.08)',
                    },
                },
                y1: {
                    beginAtZero: true,
                    position: 'right',
                    grid: {
                        drawOnChartArea: false,
                    },
                    ticks: {
                        precision: 0,
                    },
                },
            },
        },
    }));
}

function initBookingsChart(data) {
    createChart('adminBookingsStatusChart', () => ({
        type: 'doughnut',
        data: {
            labels: data.bookingsByStatus.map((item) => item.label),
            datasets: [{
                data: data.bookingsByStatus.map((item) => item.count),
                backgroundColor: [COLORS.orange, COLORS.sky, COLORS.emerald, COLORS.rose, COLORS.violet, COLORS.amber, COLORS.slate],
                borderColor: '#ffffff',
                borderWidth: 2,
                hoverOffset: 6,
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                },
            },
            cutout: '68%',
        },
    }));
}

function initRegistrationsChart(data) {
    createChart('adminRegistrationsChart', () => ({
        type: 'bar',
        data: {
            labels: data.registrationsByDay.map((item) => item.date),
            datasets: [
                {
                    label: 'Utilisateurs',
                    data: data.registrationsByDay.map((item) => item.users),
                    backgroundColor: COLORS.orange,
                    borderRadius: 8,
                    maxBarThickness: 18,
                },
                {
                    label: 'Propriétaires',
                    data: data.registrationsByDay.map((item) => item.owners),
                    backgroundColor: COLORS.ink,
                    borderRadius: 8,
                    maxBarThickness: 18,
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                },
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0,
                    },
                    grid: {
                        color: 'rgba(15, 15, 15, 0.08)',
                    },
                },
                x: {
                    grid: {
                        display: false,
                    },
                },
            },
        },
    }));
}

function initCommuneChart(data) {
    createChart('adminCommuneChart', () => ({
        type: 'bar',
        data: {
            labels: data.residencesByCommune.map((item) => item.label),
            datasets: [{
                label: 'Résidences actives',
                data: data.residencesByCommune.map((item) => item.count),
                backgroundColor: [
                    COLORS.orange,
                    'rgba(241, 106, 0, 0.88)',
                    'rgba(241, 106, 0, 0.8)',
                    'rgba(241, 106, 0, 0.72)',
                    'rgba(241, 106, 0, 0.66)',
                    'rgba(241, 106, 0, 0.58)',
                ],
                borderRadius: 10,
                maxBarThickness: 26,
            }],
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false,
                },
            },
            scales: {
                x: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0,
                    },
                    grid: {
                        color: 'rgba(15, 15, 15, 0.08)',
                    },
                },
                y: {
                    grid: {
                        display: false,
                    },
                },
            },
        },
    }));
}

function initAdminStatisticsCharts() {
    const data = parseData();

    if (!data) {
        return;
    }

    destroyCharts();
    initRevenueChart(data);
    initBookingsChart(data);
    initRegistrationsChart(data);
    initCommuneChart(data);
}

document.addEventListener('DOMContentLoaded', initAdminStatisticsCharts);
document.addEventListener('livewire:navigated', initAdminStatisticsCharts);
