/**
 * Fiscal page — monthly revenue bar chart (Chart.js)
 * Extracted from owner/analytics/fiscal.blade.php
 *
 * Called as window.initFiscalChart(monthlyData)
 * from a minimal inline script that passes @json($fiscalData['monthly']).
 */
export default function initFiscalChart(monthlyData) {
    const el = document.getElementById('monthlyRevenueChart');
    if (!el) return;

    new Chart(el, {
        type: 'bar',
        data: {
            labels: monthlyData.map(m => m.month.substring(0, 3)),
            datasets: [{
                label: 'Revenus (FCFA)',
                data: monthlyData.map(m => m.revenue),
                backgroundColor: monthlyData.map(m =>
                    m.revenue > 0
                        ? 'rgba(16, 185, 129, 0.8)'
                        : 'rgba(209, 213, 219, 0.5)'
                ),
                borderRadius: 6,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: (ctx) => ctx.raw.toLocaleString('fr-FR') + ' FCFA'
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: (value) => (value / 1000) + 'k'
                    }
                }
            }
        }
    });
}
