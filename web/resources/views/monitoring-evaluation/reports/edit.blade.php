@extends('layouts.monitoring-evaluation')

@section('title', 'Quarterly M&E report')

@section('monitoring-evaluation-content')
    <x-page-toolbar
        :title="($report->department?->dept_name ?? 'Department').' · '.($report->quarter?->label() ?? 'Quarter')"
        meta="Planned values come from the approved technical plan. Enter achieved performance here."
    >
        <x-slot:actions>
            <a href="{{ route('monitoring_evaluation.dashboard') }}#quarterly-workspace" class="tich-btn tich-btn-ghost">Command center</a>
            <a href="{{ route('monitoring_evaluation.reports.pdf', $report) }}" class="tich-btn tich-btn-secondary">Download PDF</a>
        </x-slot:actions>
    </x-page-toolbar>

    @if (session('status'))
        <div class="tich-alert tich-alert--success tich-mt-4">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="tich-alert tich-alert--error tich-mt-4">@foreach($errors->all() as $e)<p>{{ $e }}</p>@endforeach</div>
    @endif

    @if ($report->me_notes && $report->status === 'returned')
        <div class="tich-alert tich-alert--info tich-mt-4"><strong>Notes:</strong> {{ $report->me_notes }}</div>
    @endif

    <form method="POST" action="{{ route('monitoring_evaluation.reports.update', $report) }}" class="tich-mt-6" id="me-report-form">
        @csrf
        @method('PUT')
        <div class="tich-card tich-table-panel">
            <div class="tich-table-wrap">
                <table class="tich-admin-table">
                    <thead>
                        <tr>
                            <th>Output</th>
                            <th>Activity</th>
                            <th>Costable item</th>
                            <th>Planned</th>
                            <th>Achieved</th>
                            <th>Deviation</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($report->lines as $i => $line)
                            <tr>
                                <td style="white-space:pre-wrap;">{{ $line->output }}</td>
                                <td style="white-space:pre-wrap;">{{ $line->activity }}</td>
                                <td>{{ $line->costable_item }}</td>
                                <td>{{ number_format((float) $line->planned, 2) }}</td>
                                <td>
                                    <input type="hidden" name="lines[{{ $i }}][id]" value="{{ $line->id }}">
                                    <input type="number" name="lines[{{ $i }}][achieved]" class="tich-input js-achieved" data-planned="{{ $line->planned }}" value="{{ old('lines.'.$i.'.achieved', $line->achieved) }}" step="0.01" required style="min-width:6rem;">
                                </td>
                                <td class="js-deviation">{{ number_format((float) $line->deviation, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="tich-card tich-mt-4">
            <label for="me_notes" class="tich-caption">Verification notes (optional)</label>
            <textarea id="me_notes" name="me_notes" class="tich-input tich-mt-2" rows="2" placeholder="Internal M&E notes for this quarter">{{ old('me_notes') }}</textarea>
        </div>

        <div class="tich-flex-wrap tich-mt-6" style="gap:0.75rem;justify-content:flex-end;">
            <button type="submit" class="tich-btn tich-btn-secondary">Save draft</button>
            <button type="button" class="tich-btn tich-btn-primary" id="me-report-complete">
                Verify &amp; deliver to CEO
            </button>
        </div>
    </form>

    <form method="POST" action="{{ route('monitoring_evaluation.reports.complete', $report) }}" id="me-report-complete-form" style="display:none;">
        @csrf
    </form>

    <script>
    (function () {
        document.querySelectorAll('.js-achieved').forEach(function (input) {
            input.addEventListener('input', function () {
                var planned = parseFloat(input.getAttribute('data-planned') || '0');
                var achieved = parseFloat(input.value || '0');
                var cell = input.closest('tr')?.querySelector('.js-deviation');
                if (!cell || !isFinite(planned) || !isFinite(achieved)) return;
                var deviation = Math.round((achieved - planned) * 100) / 100;
                cell.textContent = deviation.toFixed(2);
            });
        });

        var mainForm = document.getElementById('me-report-form');
        var completeForm = document.getElementById('me-report-complete-form');
        var completeBtn = document.getElementById('me-report-complete');
        if (!mainForm || !completeForm || !completeBtn) return;

        completeBtn.addEventListener('click', function () {
            if (!confirm('Verify this report and deliver to the CEO dashboard?')) return;
            completeForm.innerHTML = '';
            var csrf = mainForm.querySelector('input[name=_token]');
            if (csrf) {
                var t = document.createElement('input');
                t.type = 'hidden';
                t.name = '_token';
                t.value = csrf.value;
                completeForm.appendChild(t);
            }
            mainForm.querySelectorAll('input[name^="lines"], textarea[name="me_notes"]').forEach(function (el) {
                var clone = el.cloneNode(true);
                completeForm.appendChild(clone);
            });
            completeForm.submit();
        });
    })();
    </script>
@endsection
