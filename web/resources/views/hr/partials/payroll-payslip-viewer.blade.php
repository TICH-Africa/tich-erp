@php
    $payslips = collect($payslipPayload ?? []);
    $defaultPayslip = $payslips->first();
    $viewerId = $viewerId ?? 'hr-payroll-payslip-viewer';
@endphp

<article class="tich-card tich-mt-8" id="{{ $viewerId }}">
    <div class="doc-viewer__header">
        <div>
            <h2 class="tich-h3">{{ $title ?? 'Payslip viewer' }}</h2>
            <p class="tich-caption tich-mt-2">{{ $subtitle ?? 'Preview monthly payslips in the dashboard, or open externally to print and download.' }}</p>
        </div>
        @if ($payslips->count() > 1)
            <div class="doc-viewer__application-select">
                <label for="{{ $viewerId }}-employee" class="tich-label">Employee</label>
                <select id="{{ $viewerId }}-employee" class="tich-input">
                    @foreach ($payslips as $payslip)
                        <option value="{{ $payslip['id'] }}" @selected($loop->first)>
                            {{ $payslip['label'] }}
                        </option>
                    @endforeach
                </select>
            </div>
        @endif
    </div>

    @if ($payslips->isEmpty())
        <p class="tich-caption tich-mt-4">No payslips available. Staff need a consolidated gross pay configured to generate payslips.</p>
    @else
        <div class="doc-viewer tich-mt-6" data-payslip-viewer-root>
            <div class="doc-viewer__panel doc-viewer__panel--full">
                <div class="doc-viewer__toolbar">
                    <div class="doc-viewer__toolbar-meta">
                        <strong data-payslip-viewer-title>{{ $defaultPayslip['label'] ?? 'Payslip' }}</strong>
                        <span data-payslip-viewer-caption class="tich-caption">Monthly payslip preview</span>
                    </div>
                    <div class="doc-viewer__toolbar-actions">
                        <a
                            data-payslip-viewer-external
                            href="{{ $defaultPayslip['external_url'] ?? '#' }}"
                            target="_blank"
                            rel="noopener"
                            class="tich-btn tich-btn-ghost"
                        >Open externally</a>
                        <button type="button" data-payslip-viewer-print class="tich-btn tich-btn-secondary">Print</button>
                        <a
                            data-payslip-viewer-download
                            href="{{ $defaultPayslip['download_url'] ?? '#' }}"
                            class="tich-btn tich-btn-secondary"
                        >Download</a>
                    </div>
                </div>

                <div data-payslip-viewer-stage class="doc-viewer__stage">
                    <iframe
                        data-payslip-viewer-frame
                        src="{{ $defaultPayslip['preview_url'] ?? 'about:blank' }}"
                        title="Payslip preview"
                        class="doc-viewer__frame"
                    ></iframe>
                </div>
            </div>
        </div>

        <script type="application/json" id="{{ $viewerId }}-payslip-data">
            {!! json_encode($payslips->values()->all()) !!}
        </script>
    @endif
</article>

@if ($payslips->isNotEmpty())
    <script src="{{ asset('js/tich-hr-payslip-viewer.js') }}"></script>
    <script>
        (function () {
            function boot() {
                if (!window.TichHrPayslipViewer) {
                    return;
                }

                window.TichHrPayslipViewer.init(
                    '{{ $viewerId }}',
                    '{{ $viewerId }}-employee',
                    '{{ $viewerId }}-payslip-data'
                );
            }

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', boot);
            } else {
                boot();
            }
        })();
    </script>
@endif
