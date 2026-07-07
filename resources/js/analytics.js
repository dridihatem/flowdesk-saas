import Chart from 'chart.js/auto';

document.addEventListener('DOMContentLoaded', () => {
    const root = document.getElementById('flowdesk-analytics-root');
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
    const counts = payload.counts ?? [];
    const paidMinor = payload.paidMinor ?? [];
    const byChannel = payload.paymentsByChannel ?? { labels: [], amounts_minor: [] };

    const minorScale = Number(payload.minorScale) > 0 ? Number(payload.minorScale) : 100;
    const minorToMajor = (v) => Number(v) / minorScale;

    const isDark = document.documentElement.classList.contains('dark');
    const grid = isDark ? 'rgba(148, 163, 184, 0.15)' : 'rgba(15, 23, 42, 0.06)';
    const tick = isDark ? '#94a3b8' : '#64748b';

    const ctx1 = document.getElementById('chart-invoices');
    if (ctx1) {
        new Chart(ctx1, {
            type: 'bar',
            data: {
                labels,
                datasets: [
                    {
                        label: root.dataset.labelInvoices ?? 'Payments',
                        data: counts,
                        borderRadius: 8,
                        backgroundColor: 'rgba(99, 102, 241, 0.55)',
                        borderColor: 'rgba(99, 102, 241, 1)',
                        borderWidth: 1,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { labels: { color: tick } } },
                scales: {
                    x: { grid: { color: grid }, ticks: { color: tick } },
                    y: { beginAtZero: true, grid: { color: grid }, ticks: { color: tick, precision: 0 } },
                },
            },
        });
    }

    const ctx2 = document.getElementById('chart-revenue');
    if (ctx2) {
        new Chart(ctx2, {
            type: 'line',
            data: {
                labels,
                datasets: [
                    {
                        label: root.dataset.labelPaid ?? 'Volume (major units)',
                        data: paidMinor.map(minorToMajor),
                        fill: true,
                        tension: 0.35,
                        borderColor: 'rgb(6, 182, 212)',
                        backgroundColor: 'rgba(6, 182, 212, 0.12)',
                        pointRadius: 4,
                        pointBackgroundColor: 'rgb(6, 182, 212)',
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { labels: { color: tick } } },
                scales: {
                    x: { grid: { color: grid }, ticks: { color: tick } },
                    y: { beginAtZero: true, grid: { color: grid }, ticks: { color: tick } },
                },
            },
        });
    }

    const palette = [
        'rgba(99, 102, 241, 0.75)',
        'rgba(6, 182, 212, 0.75)',
        'rgba(16, 185, 129, 0.75)',
        'rgba(245, 158, 11, 0.75)',
        'rgba(244, 63, 94, 0.72)',
        'rgba(139, 92, 246, 0.72)',
        'rgba(100, 116, 139, 0.72)',
    ];

    const ctx3 = document.getElementById('chart-payments-channel');
    if (ctx3 && Array.isArray(byChannel.labels) && byChannel.labels.length > 0) {
        const chLabels = byChannel.labels;
        const chData = (byChannel.amounts_minor ?? []).map(minorToMajor);
        new Chart(ctx3, {
            type: 'doughnut',
            data: {
                labels: chLabels,
                datasets: [
                    {
                        data: chData,
                        backgroundColor: chLabels.map((_, i) => palette[i % palette.length]),
                        borderColor: isDark ? 'rgba(15, 23, 42, 0.9)' : '#fff',
                        borderWidth: 2,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom', labels: { color: tick } },
                    tooltip: {
                        callbacks: {
                            label: (ctx) => {
                                const v = ctx.raw ?? 0;
                                const total = ctx.dataset.data.reduce((a, b) => a + b, 0) || 1;
                                const pct = Math.round((v / total) * 1000) / 10;
                                return `${ctx.label}: ${v} (${pct}%)`;
                            },
                        },
                    },
                },
            },
        });
    }
});
