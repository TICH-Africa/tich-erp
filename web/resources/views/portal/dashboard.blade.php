@extends('layouts.portal')

@section('portal-content')
    @include('portal.partials.section-' . $section)
    <script>
        window.addEventListener('tich:student-finance-updated', function (event) {
            const summary = event.detail || {};
            const moneyFields = {
                outstanding_balance: '[data-student-outstanding-balance]',
                total_paid: '[data-student-total-paid]',
                total_chargeable: '[data-student-total-chargeable]',
            };

            Object.entries(moneyFields).forEach(function ([key, selector]) {
                const amount = Number(summary[key]);
                if (!Number.isFinite(amount)) return;
                const value = 'KES ' + new Intl.NumberFormat('en-KE', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2,
                }).format(amount);
                document.querySelectorAll(selector).forEach(function (element) {
                    element.textContent = value;
                });
            });

            if (summary.fee_clearance_status) {
                document.querySelectorAll('[data-student-fee-clearance]').forEach(function (element) {
                    element.textContent = summary.fee_clearance_status
                        .replace(/_/g, ' ')
                        .replace(/\b\w/g, (letter) => letter.toUpperCase());
                });
            }
        });

        window.addEventListener('tich:student-academics-updated', function () {
            if (new URLSearchParams(window.location.search).get('section') === 'academics') {
                window.location.reload();
            }
        });
    </script>
@endsection
