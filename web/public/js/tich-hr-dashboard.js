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

    const palette = ['#1669a6', '#6cab33', '#0f766e', '#125a8c', '#5a9430', '#64748b', '#38bdf8', '#86efac'];
    const fontFamily = 'Arial, Calibri, ui-sans-serif, sans-serif';
    const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
    const ink = isDark ? '#e2e8f0' : '#494c50';
    const grid = isDark ? 'rgba(148, 163, 184, 0.12)' : 'rgba(22, 105, 166, 0.08)';
    const surface = isDark ? '#0f172a' : '#ffffff';

    Chart.defaults.font.family = fontFamily;
    Chart.defaults.color = ink;

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
        cutout: '68%',
        plugins: {
            legend: {
                position: 'bottom',
                labels: {
                    color: ink,
                    boxWidth: 10,
                    boxHeight: 10,
                    boxBorderWidth: 0,
                    borderRadius: 2,
                    useBorderRadius: true,
                    padding: 16,
                    font: { size: 11 },
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
                ticks: { precision: 0, color: ink },
                grid: { color: grid, drawBorder: false },
                border: { display: false },
            },
            y: {
                ticks: { color: ink },
                grid: { display: false },
                border: { display: false },
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
                    borderWidth: 0,
                    borderColor: surface,
                    hoverBorderWidth: 0,
                    hoverOffset: 4,
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
                    borderRadius: 8,
                    maxBarThickness: 26,
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
