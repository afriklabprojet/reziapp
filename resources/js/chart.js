/**
 * Chart.js — Bundled via Vite (remplace les CDN)
 * Usage dans Blade : @vite('resources/js/chart.js')
 */
import Chart from 'chart.js/auto';

window.Chart = Chart;

/**
 * Charts de la page statistiques locataire.
 * Appelé via : window.clientStatisticsCharts({ monthlyActivity, preferredTypes })
 */
window.clientStatisticsCharts = function ({ monthlyActivity, preferredTypes }) {
    const typeLabels = {
        studio: 'Studio', appartement: 'Appartement', villa: 'Villa',
        maison: 'Maison', duplex: 'Duplex', chambre: 'Chambre',
    };

    // Graphique d'activité (6 mois)
    const activityCanvas = document.getElementById('activityChart');
    if (activityCanvas) {
        new Chart(activityCanvas, {
            type: 'line',
            data: {
                labels: monthlyActivity.map(m => m.month),
                datasets: [
                    {
                        label: 'Visites',
                        data: monthlyActivity.map(m => m.views),
                        borderColor: '#8b5cf6',
                        backgroundColor: 'rgba(139,92,246,0.08)',
                        fill: true,
                        tension: 0.4,
                        pointRadius: 4,
                    },
                    {
                        label: 'Recherches',
                        data: monthlyActivity.map(m => m.searches),
                        borderColor: '#f97316',
                        backgroundColor: 'rgba(249,115,22,0.08)',
                        fill: true,
                        tension: 0.4,
                        pointRadius: 4,
                    },
                    {
                        label: 'Contacts',
                        data: monthlyActivity.map(m => m.contacts),
                        borderColor: '#22c55e',
                        backgroundColor: 'rgba(34,197,94,0.08)',
                        fill: true,
                        tension: 0.4,
                        pointRadius: 4,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom' } },
                scales: { y: { beginAtZero: true, ticks: { precision: 0 } } },
            },
        });
    }

    // Graphique types de logement (donut)
    const typesCanvas = document.getElementById('typesChart');
    if (typesCanvas && preferredTypes.length > 0) {
        new Chart(typesCanvas, {
            type: 'doughnut',
            data: {
                labels: preferredTypes.map(t => typeLabels[t.type] ?? t.type),
                datasets: [{
                    data: preferredTypes.map(t => t.count),
                    backgroundColor: ['#f97316', '#fb923c', '#fdba74', '#a78bfa', '#60a5fa', '#34d399'],
                    borderWidth: 2,
                    borderColor: '#ffffff',
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'right' } },
            },
        });
    }
};
