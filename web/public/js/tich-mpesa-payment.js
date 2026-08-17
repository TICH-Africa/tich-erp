(function () {
    const banner = document.getElementById('mpesa-payment-banner');
    if (!banner) {
        return;
    }

    const statusUrl = banner.dataset.statusUrl;
    const pollMs = 4000;
    let attempts = 0;
    const maxAttempts = 45;

    function setMessage(text, tone) {
        banner.textContent = text;
        banner.dataset.tone = tone || 'info';
    }

    async function poll() {
        attempts += 1;

        try {
            const response = await fetch(statusUrl, {
                headers: { Accept: 'application/json' },
                credentials: 'same-origin',
            });

            if (!response.ok) {
                throw new Error('Could not check payment status.');
            }

            const data = await response.json();

            if (data.is_success) {
                setMessage(
                    'Payment received. M-Pesa receipt: ' + (data.mpesa_receipt_number || 'confirmed') + '. Refreshing…',
                    'success'
                );
                window.setTimeout(function () {
                    window.location.href = banner.dataset.redirectUrl || (window.location.pathname + '?section=finance');
                }, 1500);

                return;
            }

            if (data.is_complete && !data.is_success) {
                setMessage(data.result_desc || 'Payment was not completed.', 'error');

                return;
            }

            if (attempts >= maxAttempts) {
                setMessage('Payment is still pending. If you completed it on your phone, refresh this page shortly.', 'warning');

                return;
            }

            setMessage(
                (data.result_desc || 'Waiting for M-Pesa confirmation…') + ' Check your phone and enter your PIN.',
                'info'
            );
            window.setTimeout(poll, pollMs);
        } catch (error) {
            if (attempts >= maxAttempts) {
                setMessage('Could not confirm payment status. Refresh the page to check your balance.', 'warning');

                return;
            }

            window.setTimeout(poll, pollMs);
        }
    }

    poll();
})();
