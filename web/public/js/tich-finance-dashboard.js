(function () {
    'use strict';

    function initFinanceCharts() {
        const dataEl = document.getElementById('finance-dashboard-chart-data');
        console.log('[Finance Charts] dataEl found:', !!dataEl);

        if (!dataEl) {
            console.warn('Finance charts: chart data element not found.');
            return;
        }

        if (typeof Chart === 'undefined') {
            console.warn('Finance charts: Chart.js is not loaded.');
            return;
        }

        let chartData;
        try {
            chartData = JSON.parse(dataEl.textContent || '{}');
            console.log('[Finance Charts] chartData parsed:', chartData);
        } catch (e) {
            console.warn('Finance charts: invalid chart data.', e);
            return;
        }

        const palette = ['#1669a6', '#6cab33', '#494c50', '#125a8c', '#5a9430', '#6b6e72', '#d6e8f5', '#e8f3dc'];
        const fontFamily = 'Arial, Helvetica, sans-serif';

        Chart.defaults.font.family = fontFamily;
        Chart.defaults.color = '#494c50';

        const emptyNote = (canvas, message) => {
            const wrap = canvas.closest('.tich-chart-card__canvas-wrap');
            if (!wrap) return;
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

        const renderDoughnut = (id, dataset, emptyMessage) => {
            const canvas = document.getElementById(id);
            console.log('[Finance Charts] rendering', id, 'canvas found:', !!canvas, 'dataset:', dataset);

            if (!canvas || !dataset) return;

            if (!hasValues(dataset.values)) {
                console.log('[Finance Charts]', id, 'has no values, showing empty note');
                emptyNote(canvas, emptyMessage);
                return;
            }

            try {
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
                console.log('[Finance Charts]', id, 'rendered successfully');
            } catch (e) {
                console.error('[Finance Charts]', id, 'render error:', e);
            }
        };

        renderDoughnut('finance-chart-invoices-status', chartData.invoicesByStatus, 'No invoices yet.');
        renderDoughnut('finance-chart-payments-method', chartData.paymentsByMethod, 'No payments yet.');
        renderDoughnut('finance-chart-accounts-clearance', chartData.accountsByClearance, 'No student accounts yet.');
        renderDoughnut('finance-chart-payroll-runs-status', chartData.payrollRunsByStatus, 'No payroll runs yet.');
        renderDoughnut('finance-chart-staff-payroll-scheme', chartData.staffByPayrollScheme, 'No staff records yet.');
        renderDoughnut('finance-chart-ledger-type', chartData.ledgerByTransactionType, 'No ledger entries yet.');
        renderDoughnut('finance-chart-invoices-type', chartData.invoicesByType, 'No invoices yet.');
    }

    if (typeof Chart !== 'undefined') {
        console.log('[Finance Charts] Chart.js already loaded, initializing immediately');
        initFinanceCharts();
        return;
    }

    console.log('[Finance Charts] Chart.js not loaded yet, polling...');
    const interval = setInterval(() => {
        if (typeof Chart !== 'undefined') {
            clearInterval(interval);
            console.log('[Finance Charts] Chart.js loaded via polling, initializing');
            initFinanceCharts();
        }
    }, 50);

    setTimeout(() => {
        clearInterval(interval);
        console.warn('[Finance Charts] Chart.js never loaded within timeout');
    }, 5000);
})();
