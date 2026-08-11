(function () {
    const form = document.getElementById('portal-mpesa-pay-form');
    const invoiceSelect = document.getElementById('portal-pay-invoice');
    const amountInput = document.getElementById('portal-pay-amount');

    if (!form || !invoiceSelect || !amountInput) {
        return;
    }

    function syncInvoiceSelection() {
        const option = invoiceSelect.options[invoiceSelect.selectedIndex];
        if (!option) {
            return;
        }

        const balance = parseFloat(option.dataset.balance || '0');
        const action = option.dataset.action || '';

        if (action) {
            form.action = action;
        }

        amountInput.max = balance.toFixed(2);
        amountInput.value = balance.toFixed(2);
    }

    invoiceSelect.addEventListener('change', syncInvoiceSelection);
    syncInvoiceSelection();
})();
