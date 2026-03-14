export default function ownerStatisticsChart(config = {}) {
    const dailyStats = config.dailyStats || [];

    const labels = dailyStats.map(stat => {
        const date = new Date(stat.stat_date);
        return date.toLocaleDateString('fr-FR', { day: '2-digit', month: 'short' });
    });

    const viewsData = dailyStats.map(stat => stat.views);
    const contactsData = dailyStats.map(stat => stat.contacts);

    const ctx = document.getElementById('evolutionChart');
    if (!ctx) return;

    new Chart(ctx.getContext('2d'), {
        type: 'line',
        data: {
            labels: labels.length > 0 ? labels : ['Aucune donnée'],
            datasets: [
                {
                    label: 'Vues',
                    data: viewsData.length > 0 ? viewsData : [0],
                    borderColor: 'rgb(16, 185, 129)',
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.3,
                    pointRadius: 3,
                    pointHoverRadius: 6
                },
                {
                    label: 'Contacts',
                    data: contactsData.length > 0 ? contactsData : [0],
                    borderColor: 'rgb(59, 130, 246)',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.3,
                    pointRadius: 3,
                    pointHoverRadius: 6
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                intersect: false,
                mode: 'index'
            },
            plugins: {
                legend: {
                    position: 'top',
                    align: 'end',
                    labels: {
                        usePointStyle: true,
                        padding: 20
                    }
                },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    padding: 12,
                    cornerRadius: 8,
                    titleFont: { size: 14 },
                    bodyFont: { size: 13 }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(0, 0, 0, 0.05)'
                    },
                    ticks: {
                        precision: 0
                    }
                },
                x: {
                    grid: {
                        display: false
                    }
                }
            }
        }
    });
}
