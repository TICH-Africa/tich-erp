(function () {
    function getRoot(viewerId) {
        return document.getElementById(viewerId);
    }

    function findPayslip(payslips, id) {
        return payslips.find(function (entry) {
            return String(entry.id) === String(id);
        });
    }

    function applyPayslip(root, payslip) {
        if (!root || !payslip) {
            return;
        }

        var title = root.querySelector('[data-payslip-viewer-title]');
        var external = root.querySelector('[data-payslip-viewer-external]');
        var download = root.querySelector('[data-payslip-viewer-download]');
        var frame = root.querySelector('[data-payslip-viewer-frame]');

        if (title) {
            title.textContent = payslip.label || 'Payslip';
        }

        if (external) {
            external.href = payslip.external_url;
        }

        if (download) {
            download.href = payslip.download_url;
        }

        if (frame) {
            frame.src = payslip.preview_url;
        }
    }

    function bindPreviewButtons(viewerId, selectId, dataId) {
        var root = getRoot(viewerId);
        var dataNode = document.getElementById(dataId);

        if (!root || !dataNode) {
            return;
        }

        var payslips = JSON.parse(dataNode.textContent || '[]');
        var select = selectId ? document.getElementById(selectId) : null;

        document.querySelectorAll('[data-payslip-preview-id]').forEach(function (button) {
            button.addEventListener('click', function () {
                var payslip = findPayslip(payslips, button.getAttribute('data-payslip-preview-id'));

                if (!payslip) {
                    return;
                }

                if (select) {
                    select.value = String(payslip.id);
                }

                applyPayslip(root, payslip);
                root.scrollIntoView({ behavior: 'smooth', block: 'start' });
            });
        });
    }

    function init(viewerId, selectId, dataId) {
        var root = getRoot(viewerId);
        var select = selectId ? document.getElementById(selectId) : null;
        var dataNode = document.getElementById(dataId);

        if (!root || !dataNode) {
            return;
        }

        var payslips = JSON.parse(dataNode.textContent || '[]');
        var printButton = root.querySelector('[data-payslip-viewer-print]');

        if (select) {
            select.addEventListener('change', function () {
                applyPayslip(root, findPayslip(payslips, select.value));
            });
        }

        if (printButton) {
            printButton.addEventListener('click', function () {
                var frame = root.querySelector('[data-payslip-viewer-frame]');
                if (frame && frame.contentWindow) {
                    try {
                        frame.contentWindow.focus();
                        frame.contentWindow.print();
                        return;
                    } catch (error) {
                        // Fall through to external tab.
                    }
                }

                var external = root.querySelector('[data-payslip-viewer-external]');
                if (external && external.href) {
                    window.open(external.href, '_blank', 'noopener');
                }
            });
        }

        bindPreviewButtons(viewerId, selectId, dataId);
    }

    window.TichHrPayslipViewer = {
        init: init,
    };
})();
