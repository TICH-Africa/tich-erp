@extends('layouts.monitoring-evaluation')

@section('title', 'Department M&E reports')

@section('monitoring-evaluation-content')
    <x-page-toolbar title="My department M&E reports" meta="Fill the standardised quarterly grid and submit to M&E" />

    @if (session('status'))
        <div class="tich-alert tich-alert--success tich-mt-4">{{ session('status') }}</div>
    @endif

    <article class="tich-card tich-mt-6">
        <h2 class="tich-h3">Baseline plans</h2>
        <ul class="tich-mt-4" style="margin:0;padding-left:1.25rem;">
            @forelse ($plans as $plan)
                <li class="tich-text tich-mt-3">
                    <strong>{{ $plan->title }}</strong>
                    <span class="tich-caption">· {{ $plan->department?->dept_name }}</span>
                    <div class="tich-mt-2" style="display:flex;flex-wrap:wrap;gap:0.5rem;">
                        @foreach ($plan->quarters as $q)
                            <a href="{{ route('monitoring_evaluation.department.open', [$plan, $q->quarter_number]) }}" class="tich-btn tich-btn-secondary">{{ $q->label() }} report</a>
                        @endforeach
                    </div>
                </li>
            @empty
                <li class="tich-text">No baseline-locked plans available for your department yet.</li>
            @endforelse
        </ul>
    </article>

    <div class="tich-card tich-table-panel tich-mt-6">
        <h2 class="tich-h3">Report history</h2>
        <div class="tich-table-wrap tich-mt-4">
            <table class="tich-admin-table">
                <thead>
                    <tr>
                        <th>Department</th>
                        <th>Quarter</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($reports as $report)
                        <tr>
                            <td>{{ $report->department?->dept_name }}</td>
                            <td>{{ $report->quarter?->label() }}</td>
                            <td><x-status-badge :status="$report->status" /></td>
                            <td>
                                <a href="{{ route('monitoring_evaluation.department.reports.edit', $report) }}" class="tich-link">
                                    {{ in_array($report->status, ['draft', 'returned'], true) ? 'Edit' : 'View' }}
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4">No reports yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="tich-mt-4">{{ $reports->links() }}</div>
    </div>
@endsection
