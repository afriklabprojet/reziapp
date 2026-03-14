export default function clientStatisticsCharts(config = {}) {
    const monthlyActivity = config.monthlyActivity || [];
    const preferredTypes = config.preferredTypes || [];

    // Graphique d'activité
    const activityCtx = document.getElementById('activityChart');
    if (activityCtx) {
        new Chart(activityCtx, {
            type: 'line',
            data: {
                labels: monthlyActivity.map(m => m.month),
                datasets: [
                    {
                        label: 'Visites',
                        data: monthlyActivity.map(m => m.views),
                        borderColor: 'rgb(139, 92, 246)',
                        backgroundColor: 'rgba(139, 92, 246, 0.1)',
                        tension: 0.4,
                        fill: true
                    },
                    {
                        label: 'Recherches',
                        data: monthlyActivity.map(m => m.searches),
                        borderColor: 'rgb(59, 130, 246)',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        tension: 0.4,
                        fill: true
                    },
                    {
                        label: 'Contacts',
                        data: monthlyActivity.map(m => m.contacts),
                        borderColor: 'rgb(245, 158, 11)',
                        backgroundColor: 'rgba(245, 158, 11, 0.1)',
                        tension: 0.4,
                        fill: true
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }

    // Graphique types de logement
    const typesCtx = document.getElementById('typesChart');
    if (typesCtx && preferredTypes.length > 0) {
        new Chart(typesCtx, {
            type: 'doughnut',
            data: {
                labels: preferredTypes.map(t => t.type.charAt(0).toUpperCase() + t.type.slice(1)),
                datasets: [{
                    data: preferredTypes.map(t => t.count),
                    backgroundColor: [
                        'rgba(16, 185, 129, 0.8)',
                        'rgba(59, 130, 246, 0.8)',
                        'rgba(245, 158, 11, 0.8)',
                        'rgba(139, 92, 246, 0.8)',
                        'rgba(239, 68, 68, 0.8)'
                    ],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    }
}
