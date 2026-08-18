(function () {
    'use strict';

    function initCharts() {
        const dataElement = document.getElementById('administration-dashboard-chart-data');
        if (!dataElement || typeof Chart === 'undefined') return;

        let chartData;
        try {
            chartData = JSON.parse(dataElement.textContent || '{}');
        } catch (error) {
            return;
        }

        const palette = ['#1669a6', '#6cab33', '#494c50', '#125a8c', '#d39b2a', '#b54a4a'];
        const render = (id, dataset, message) => {
            const canvas = document.getElementById(id);
            if (!canvas || !dataset) return;
            const values = Array.isArray(dataset.values) ? dataset.values : [];
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
                type: 'doughnut',
                data: {
                    labels: dataset.labels,
                    datasets: [{ data: values, backgroundColor: palette, borderWidth: 2, borderColor: '#fff' }],
                },
                options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } },
            });
        };

        render('admin-chart-budget-status', chartData.budgetByStatus, 'No budget requests yet.');
        render('admin-chart-budget-framework', chartData.budgetByFramework, 'No budget frameworks yet.');
        render('admin-chart-task-status', chartData.tasksByStatus, 'No weekly tasks yet.');
        render('admin-chart-procurement', chartData.procurementPipeline, 'No procurement activity yet.');
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