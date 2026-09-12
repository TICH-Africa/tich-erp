@extends('layouts.monitoring-evaluation')

@section('title', 'Edit quarterly M&E report')

@php
    $canSubmit = (bool) ($canSubmit ?? false);
    $isEditable = $canSubmit && in_array($report->status, ['draft', 'returned'], true);
@endphp

@section('monitoring-evaluation-content')
    <x-page-toolbar
        :title="($report->department?->dept_name ?? 'Department').' · '.($report->quarter?->label() ?? '')"
        meta="Rigid schema: Output · Activity · Costable item · Planned · Achieved · Deviation"
    >
        <x-slot:actions>
            @if (! $canSubmit && in_array($report->status, ['submitted', 'me_verified', 'ceo_delivered'], true))
                <a href="{{ route('monitoring_evaluation.reports.show', $report) }}" class="tich-btn tich-btn-primary">Open for verification</a>
            @endif
            <a href="{{ route('monitoring_evaluation.department.index') }}" class="tich-btn tich-btn-ghost">Back</a>
        </x-slot:actions>
    </x-page-toolbar>

    @if (session('status'))
        <div class="tich-alert tich-alert--success tich-mt-4">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="tich-alert tich-alert--error tich-mt-4">@foreach($errors->all() as $e)<p>{{ $e }}</p>@endforeach</div>
    @endif

    @if ($report->me_notes && $report->status === 'returned')
        <div class="tich-alert tich-alert--info tich-mt-4"><strong>Returned by M&amp;E:</strong> {{ $report->me_notes }}</div>
    @endif

    @if (! $canSubmit && in_array($report->status, ['draft', 'returned'], true))
        <div class="tich-alert tich-alert--info tich-mt-4">
            This is {{ $report->department?->dept_name ?? 'the department' }}’s report.
            Only that department’s HOD or staff can edit and submit it to M&amp;E for verification.
        </div>
    @endif

    @if ($isEditable)
    <form method="POST" action="{{ route('monitoring_evaluation.department.reports.update', $report) }}" class="tich-mt-6">
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
                                <td>{{ $line->output }}</td>
                                <td>{{ $line->activity }}</td>
                                <td>{{ $line->costable_item }}</td>
                                <td>{{ number_format((float) $line->planned, 2) }}</td>
                                <td>
                                    <input type="hidden" name="lines[{{ $i }}][id]" value="{{ $line->id }}">
                                    <input type="number" name="lines[{{ $i }}][achieved]" class="tich-input js-achieved" data-planned="{{ $line->planned }}" value="{{ old('lines.'.$i.'.achieved', $line->achieved) }}" step="0.01" required style="min-width:6rem;">
                                </td>
                                <td class="js-deviation">
                                    {{ number_format((float) $line->deviation, 2) }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        <div class="tich-flex-wrap tich-mt-6" style="gap:0.75rem;justify-content:flex-end;">
            <button type="submit" class="tich-btn tich-btn-secondary">Save draft</button>
        </div>
    </form>

    <form method="POST" action="{{ route('monitoring_evaluation.department.reports.submit', $report) }}" class="tich-mt-4" style="text-align:right;">
        @csrf
        <button type="submit" class="tich-btn tich-btn-primary" onclick="return confirm('Submit this quarterly report to M&E for verification?')">Submit to M&amp;E Officer</button>
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
                cell.style.color = deviation < 0 ? '#c2410c' : '';
            });
        });
    })();
    </script>
    @else
    <div class="tich-card tich-table-panel tich-mt-6">
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
                    @foreach ($report->lines as $line)
                        <tr @class(['tich-me-row--warning' => $line->isWarning()])>
                            <td>{{ $line->output }}</td>
                            <td>{{ $line->activity }}</td>
                            <td>{{ $line->costable_item }}</td>
                            <td>{{ number_format((float) $line->planned, 2) }}</td>
                            <td>{{ number_format((float) $line->achieved, 2) }}</td>
                            <td>{{ number_format((float) $line->deviation, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <p class="tich-caption tich-mt-4">Status: <x-status-badge :status="$report->status" /></p>
    </div>
    @endif
@endsection
