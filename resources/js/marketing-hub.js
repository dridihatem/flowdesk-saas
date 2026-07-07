import Chart from 'chart.js/auto';

document.addEventListener('DOMContentLoaded', () => {
    const root = document.getElementById('flowdesk-marketing-hub-root');
    if (!root) {
        return;
    }

    let payload = {};
    try {
        payload = JSON.parse(root.dataset.chart || '{}');
    } catch {
        payload = {};
    }

    const labels = payload.labels ?? [];
    const views = payload.views ?? [];
    const submits = payload.submits ?? [];
    const pageviews = payload.pageviews ?? [];
    const pageviewsAligned = Array.isArray(pageviews) && pageviews.length === labels.length ? pageviews : null;

    const isDark = document.documentElement.classList.contains('dark');
    const grid = isDark ? 'rgba(148, 163, 184, 0.15)' : 'rgba(15, 23, 42, 0.06)';
    const tick = isDark ? '#94a3b8' : '#64748b';

    const ctx = document.getElementById('chart-widget-traffic');
    if (ctx && labels.length > 0) {
        const datasets = [];
        if (pageviewsAligned) {
            datasets.push({
                label: root.dataset.labelPageviews ?? 'Site page views',
                data: pageviewsAligned,
                tension: 0.35,
                borderColor: 'rgb(6, 182, 212)',
                backgroundColor: 'rgba(6, 182, 212, 0.08)',
                fill: true,
                pointRadius: 3,
            });
        }
        datasets.push(
            {
                label: root.dataset.labelViews ?? 'Views',
                data: views,
                tension: 0.35,
                borderColor: 'rgb(99, 102, 241)',
                backgroundColor: 'rgba(99, 102, 241, 0.08)',
                fill: true,
                pointRadius: 3,
            },
            {
                label: root.dataset.labelSubmits ?? 'Submissions',
                data: submits,
                tension: 0.35,
                borderColor: 'rgb(16, 185, 129)',
                backgroundColor: 'rgba(16, 185, 129, 0.08)',
                fill: true,
                pointRadius: 3,
            },
        );
        new Chart(ctx, {
            type: 'line',
            data: {
                labels,
                datasets,
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: { legend: { labels: { color: tick } } },
                scales: {
                    x: { grid: { color: grid }, ticks: { color: tick } },
                    y: { beginAtZero: true, grid: { color: grid }, ticks: { color: tick, precision: 0 } },
                },
            },
        });
    }
});
