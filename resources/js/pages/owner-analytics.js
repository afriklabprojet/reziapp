export default function analyticsPage(config = {}) {
    return {
        init() {
            this.initCharts();
        },

        initCharts() {
            const revenueData = config.revenueData || [];
            const viewsData = config.viewsData || [];
            const contactsData = config.contactsData || [];

            // Graphique des revenus
            new Chart(document.getElementById('revenueChart'), {
                type: 'bar',
                data: {
                    labels: revenueData.map(d => d.label),
                    datasets: [{
                        label: 'Revenus',
                        data: revenueData.map(d => d.revenue),
                        backgroundColor: 'rgba(16, 185, 129, 0.8)',
                        borderRadius: 4,
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
                                callback: (value) => value.toLocaleString('fr-FR')
                            }
                        }
                    }
                }
            });

            // Graphique des vues
            new Chart(document.getElementById('viewsChart'), {
                type: 'line',
                data: {
                    labels: viewsData.map(d => d.label),
                    datasets: [{
                        label: 'Vues',
                        data: viewsData.map(d => d.views),
                        borderColor: 'rgb(147, 51, 234)',
                        backgroundColor: 'rgba(147, 51, 234, 0.1)',
                        fill: true,
                        tension: 0.3,
                    }, {
                        label: 'Contacts',
                        data: contactsData.map(d => d.contacts),
                        borderColor: 'rgb(249, 115, 22)',
                        backgroundColor: 'rgba(249, 115, 22, 0.1)',
                        fill: true,
                        tension: 0.3,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        y: { beginAtZero: true }
                    }
                }
            });
        }
    };
}
