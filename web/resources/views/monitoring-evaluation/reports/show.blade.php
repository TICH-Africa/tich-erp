@extends('layouts.monitoring-evaluation')

@section('title', 'Verify M&E report')

@section('monitoring-evaluation-content')
    <x-page-toolbar
        :title="($report->department?->dept_name ?? 'Department').' · '.($report->quarter?->label() ?? 'Quarter')"
        :meta="'Status: '.str_replace('_', ' ', $report->status)"
    >
        <x-slot:actions>
            <a href="{{ route('monitoring_evaluation.reports.index') }}" class="tich-btn tich-btn-ghost">Back</a>
        </x-slot:actions>
    </x-page-toolbar>

    @if (session('status'))
        <div class="tich-alert tich-alert--success tich-mt-4">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="tich-alert tich-alert--error tich-mt-4">@foreach($errors->all() as $e)<p>{{ $e }}</p>@endforeach</div>
    @endif

    <div class="tich-card tich-table-panel tich-mt-6">
        <h2 class="tich-h3">Standardised report grid</h2>
        <div class="tich-table-wrap tich-mt-4">
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
                            <td>
                                {{ number_format((float) $line->deviation, 2) }}
                                @if($line->isWarning()) <span class="tich-caption tich-me-warning-label">warning</span> @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <p class="tich-caption tich-mt-4">
            Achievement rate:
            {{ $report->achievementRate() !== null ? number_format($report->achievementRate(), 1).'%' : 'n/a' }}
        </p>
    </div>

    @if ($report->status === 'submitted')
        <div class="tich-grid tich-grid--2 tich-mt-6">
            <form method="POST" action="{{ route('monitoring_evaluation.reports.verify', $report) }}" class="tich-card tich-form-stack">
                @csrf
                <h2 class="tich-h3">Verify &amp; deliver to CEO</h2>
                <textarea name="me_notes" class="tich-input" rows="3" placeholder="Validation notes">{{ old('me_notes') }}</textarea>
                <button type="submit" class="tich-btn tich-btn-primary">Verify and route to CEO</button>
            </form>
            <form method="POST" action="{{ route('monitoring_evaluation.reports.return', $report) }}" class="tich-card tich-form-stack">
                @csrf
                <h2 class="tich-h3">Return to HOD</h2>
                <textarea name="me_notes" class="tich-input" rows="3" required placeholder="Reason">{{ old('me_notes') }}</textarea>
                <button type="submit" class="tich-btn tich-btn-secondary">Return for revision</button>
            </form>
        </div>
    @endif
@endsection
