@php
    $moduleContext = $moduleContext ?? \App\Support\MeDepartmentReportModuleContext::forModule('monitoring_evaluation');
    $reportRoutes = $reportRoutes ?? $moduleContext['routes'];
    $hodReadOnly = (bool) ($hodReadOnly ?? true);
@endphp

@extends($moduleContext['layout'])

@section('title', 'Department M&E reports')

@section($moduleContext['content_section'])
    <x-page-toolbar
        title="M&E quarterly reports"
        :meta="$hodReadOnly
            ? ($scopeDepartment?->dept_name ?? 'Your department').' · view status and download PDFs (M&E compiles reports on the command center)'
            : 'All departments · verification status'"
    />

    @if (session('status'))
        <div class="tich-alert tich-alert--success tich-mt-4">{{ session('status') }}</div>
    @endif

    @if ($hodReadOnly)
        <div class="tich-alert tich-alert--info tich-mt-4">
            Quarterly achievement reports are prepared by the <strong>M&amp;E office</strong> after your technical plan quarters are approved.
            You can track status and download PDFs below.
        </div>
    @endif

    <div class="tich-card tich-table-panel tich-mt-6">
        <h2 class="tich-h3">Report history</h2>
        <div class="tich-table-wrap tich-mt-4">
            <table class="tich-admin-table">
                <thead>
                    <tr>
                        @if ($viewAllDepartments ?? false)
                            <th>Department</th>
                        @endif
                        <th>Quarter</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($reports as $report)
                        <tr>
                            @if ($viewAllDepartments ?? false)
                                <td>{{ $report->department?->dept_name }}</td>
                            @endif
                            <td>{{ $report->quarter?->label() }}</td>
                            <td><x-status-badge :status="$report->status" /></td>
                            <td>
                                <div class="tich-flex-wrap" style="gap:0.5rem;">
                                    <a href="{{ route($reportRoutes['edit'], $report) }}" class="tich-link">View</a>
                                    <a href="{{ route($reportRoutes['pdf'], $report) }}" class="tich-btn tich-btn-ghost">Download PDF</a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ ($viewAllDepartments ?? false) ? 4 : 3 }}" class="tich-text tich-text--secondary">
                                No quarterly reports yet for this scope.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($reports->hasPages())
            <div class="tich-mt-4">{{ $reports->links() }}</div>
        @endif
    </div>
@endsection
