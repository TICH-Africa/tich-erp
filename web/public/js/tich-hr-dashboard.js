(function () {
    const dataEl = document.getElementById('hr-dashboard-chart-data');
    if (!dataEl || typeof Chart === 'undefined') {
        return;
    }

    let chartData;
    try {
        chartData = JSON.parse(dataEl.textContent || '{}');
    } catch {
        return;
    }

    const palette = ['#1669a6', '#6cab33', '#494c50', '#125a8c', '#5a9430', '#6b6e72', '#d6e8f5', '#e8f3dc'];
    const fontFamily = 'Arial, Helvetica, sans-serif';

    Chart.defaults.font.family = fontFamily;
    Chart.defaults.color = '#494c50';

    const emptyNote = (canvas, message) => {
        const wrap = canvas.closest('.tich-chart-card__canvas-wrap');
        if (!wrap) {
            return;
        }

        wrap.classList.add('is-empty');
        const note = document.createElement('p');
        note.className = 'tich-chart-card__empty';
        note.textContent = message;
        wrap.appendChild(note);
        canvas.remove();
    };

    const hasValues = (values) => Array.isArray(values) && values.some((value) => Number(value) > 0);

    const makeColors = (count) => Array.from({ length: count }, (_, index) => palette[index % palette.length]);

    const doughnutOptions = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom',
                labels: {
                    boxWidth: 12,
                    padding: 14,
                },
            },
        },
    };

    const barOptions = {
        responsive: true,
        maintainAspectRatio: false,
        indexAxis: 'y',
        plugins: {
            legend: { display: false },
        },
        scales: {
            x: {
                beginAtZero: true,
                ticks: { precision: 0 },
                grid: { color: 'rgba(73, 76, 80, 0.08)' },
            },
            y: {
                grid: { display: false },
            },
        },
    };

    const renderDoughnut = (id, dataset, emptyMessage) => {
        const canvas = document.getElementById(id);
        if (!canvas || !dataset) {
            return;
        }

        if (!hasValues(dataset.values)) {
            emptyNote(canvas, emptyMessage);
            return;
        }

        new Chart(canvas, {
            type: 'doughnut',
            data: {
                labels: dataset.labels,
                datasets: [{
                    data: dataset.values,
                    backgroundColor: makeColors(dataset.values.length),
                    borderWidth: 2,
                    borderColor: '#ffffff',
                }],
            },
            options: doughnutOptions,
        });
    };

    const renderBar = (id, dataset, emptyMessage) => {
        const canvas = document.getElementById(id);
        if (!canvas || !dataset) {
            return;
        }

        if (!hasValues(dataset.values)) {
            emptyNote(canvas, emptyMessage);
            return;
        }

        new Chart(canvas, {
            type: 'bar',
            data: {
                labels: dataset.labels,
                datasets: [{
                    data: dataset.values,
                    backgroundColor: '#1669a6',
                    borderRadius: 6,
                    maxBarThickness: 28,
                }],
            },
            options: barOptions,
        });
    };

    renderDoughnut('hr-chart-staff-status', chartData.staffByStatus, 'No staff records yet.');
    renderBar('hr-chart-staff-departments', chartData.staffByDepartment, 'No department assignments yet.');
    renderDoughnut('hr-chart-leave-status', chartData.leaveByStatus, 'No leave requests yet.');
    renderDoughnut('hr-chart-applications-status', chartData.applicationsByStatus, 'No job applications yet.');
})();
