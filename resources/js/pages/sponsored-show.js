/**
 * Sponsored show — performance chart (Chart.js)
 * Extracted from owner/marketing/sponsored/show.blade.php
 *
 * Called as window.initSponsoredPerformanceChart(performanceData)
 * from a minimal inline script that passes @json($performanceData).
 */
export default function initSponsoredPerformanceChart(performanceData) {
    const ctx = document.getElementById('performanceChart');
    if (!ctx) return;

    new Chart(ctx.getContext('2d'), {
        type: 'line',
        data: {
            labels: performanceData.labels,
            datasets: [
                {
                    label: 'Impressions',
                    data: performanceData.impressions,
                    borderColor: 'rgb(59, 130, 246)',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    tension: 0.3,
                    fill: true,
                },
                {
                    label: 'Clics',
                    data: performanceData.clicks,
                    borderColor: 'rgb(16, 185, 129)',
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    tension: 0.3,
                    fill: true,
                }
            ]
        },
        options: {
            responsive: true,
            interaction: {
                intersect: false,
                mode: 'index'
            },
            plugins: {
                legend: {
                    position: 'bottom',
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(0, 0, 0, 0.05)'
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
