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

        const palette = ['#1669a6', '#6cab33', '#494c50', '#125a8c', '#d39b2a', '#b54a4a'];
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
            new Chart(canvas, {
                type: type,
                data: {
                    labels: labels,
                    datasets: [{ data: values, backgroundColor: palette, borderWidth: 2, borderColor: '#fff' }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { position: 'bottom' } },
                    scales: type === 'doughnut' || type === 'pie' ? undefined : {
                        y: { beginAtZero: true, ticks: { stepSize: 1 } },
                        x: { ticks: { autoSkip: false } },
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
