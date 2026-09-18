(function () {
    'use strict';

    function initCharts() {
        const dataElement = document.getElementById('academics-chart-data');
        if (!dataElement || typeof Chart === 'undefined') return;

        let chartData;
        try {
            chartData = JSON.parse(dataElement.textContent || '{}');
        } catch (error) {
            return;
        }

        const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
        const ink = isDark ? '#e2e8f0' : '#494c50';
        const palette = ['#1669a6', '#6cab33', '#0f766e', '#125a8c', '#5a9430', '#64748b'];
        Chart.defaults.color = ink;
        Chart.defaults.font.family = 'Arial, Calibri, ui-sans-serif, sans-serif';

        const render = (id, dataset, type, message) => {
            const canvas = document.getElementById(id);
            if (!canvas || !dataset) return;
            const labels = Array.isArray(dataset.labels) ? dataset.labels : [];
            const values = Array.isArray(dataset.data) ? dataset.data : [];
            if (!values.some((value) => Number(value) > 0)) {
                const wrap = canvas.closest('.tich-chart-card__canvas-wrap');
                if (wrap) {
                    wrap.classList.add('is-empty');
                    const note = document.createElement('p');
                    note.className = 'tich-chart-card__empty';
                    note.textContent = message;
                    wrap.appendChild(note);
                    canvas.remove();
                }
                return;
            }
            const isRing = type === 'doughnut' || type === 'pie';
            new Chart(canvas, {
                type: type,
                data: {
                    labels: labels,
                    datasets: [{
                        data: values,
                        backgroundColor: palette,
                        borderWidth: 0,
                        hoverBorderWidth: 0,
                        borderRadius: isRing ? 0 : 6,
                        maxBarThickness: isRing ? undefined : 28,
                    }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: isRing ? '68%' : undefined,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                color: ink,
                                boxBorderWidth: 0,
                                boxWidth: 10,
                                boxHeight: 10,
                                padding: 14,
                                font: { size: 11 },
                            },
                        },
                    },
                    scales: isRing ? undefined : {
                        y: {
                            beginAtZero: true,
                            ticks: { stepSize: 1, color: ink },
                            grid: { color: isDark ? 'rgba(148,163,184,0.12)' : 'rgba(22,105,166,0.08)' },
                            border: { display: false },
                        },
                        x: {
                            ticks: { autoSkip: false, color: ink },
                            grid: { display: false },
                            border: { display: false },
                        },
                    },
                },
            });
        };

        render('academics-chart-programs-by-department', chartData.programsByDepartment, 'bar', 'No programmes assigned yet.');
        render('academics-chart-program-status', chartData.programStatus, 'doughnut', 'No programme status data yet.');
        render('academics-chart-unit-status', chartData.unitStatus, 'doughnut', 'No unit status data yet.');
    }

    if (typeof Chart !== 'undefined') {
        initCharts();
    } else {
        const interval = setInterval(() => {
            if (typeof Chart !== 'undefined') {
                clearInterval(interval);
                initCharts();
            }
        }, 50);
        setTimeout(() => clearInterval(interval), 5000);
    }
})();
