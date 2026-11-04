@php
    $moduleContext = $moduleContext ?? \App\Support\MeDepartmentReportModuleContext::forModule('monitoring_evaluation');
    $reportRoutes = $reportRoutes ?? $moduleContext['routes'];
    $technicalPlanPdfRoute = ($moduleContext['key'] ?? '') !== ''
        ? $moduleContext['key'].'.technical-plans.pdf'
        : null;
@endphp

@extends($moduleContext['layout'])

@section('title', 'Department M&E reports')

@section($moduleContext['content_section'])
    <x-page-toolbar
        title="M&E quarterly reports"
        :meta="($viewAllDepartments ?? false) ? 'All departments · verification status' : ($scopeDepartment?->dept_name ?? 'This department').' - fill the standardised quarterly grid and submit to M&E'"
    />

    @if (session('status'))
        <div class="tich-alert tich-alert--success tich-mt-4">{{ session('status') }}</div>
    @endif

    <article class="tich-card tich-mt-6">
        <h2 class="tich-h3">Technical plans</h2>
        <p class="tich-caption tich-mt-1">A quarter opens for reporting once M&amp;E approves it, or once the whole year is baseline locked.</p>
        <ul class="tich-mt-4" style="margin:0;padding-left:1.25rem;">
            @forelse ($plans as $plan)
                <li class="tich-text tich-mt-3">
                    <strong>{{ $plan->title }}</strong>
                    @if ($viewAllDepartments ?? false)
                        <span class="tich-caption">· {{ $plan->department?->dept_name }}</span>
                    @endif
                    <div class="tich-mt-2" style="display:flex;flex-wrap:wrap;gap:0.5rem;">
                        @foreach ($plan->quarters as $q)
                            @if ($plan->isQuarterReportable((int) $q->quarter_number))
                                <a href="{{ route($reportRoutes['open'], [$plan, $q->quarter_number]) }}" class="tich-btn tich-btn-secondary">{{ $q->label() }} report</a>
                                @if ($plan->quarterlyReports->contains('quarter_id', $q->id))
                                    <a href="{{ route($reportRoutes['plan-pdf'], [$plan, $q->quarter_number]) }}" class="tich-btn tich-btn-ghost">Download {{ $q->label() }}</a>
                                @endif
                            @endif
                        @endforeach
                        @if ($plan->quarterlyReports->count() > 1)
                            <a href="{{ route($reportRoutes['plan-pdf'], [$plan, 'all']) }}" class="tich-btn tich-btn-primary">Download all reports</a>
                        @endif
                        @if ($technicalPlanPdfRoute && Route::has($technicalPlanPdfRoute))
                            <a href="{{ route($technicalPlanPdfRoute, ['plan' => $plan->id, 'quarter' => 'all']) }}" class="tich-btn tich-btn-ghost">Download technical plan</a>
                        @endif
                    </div>
                </li>
            @empty
                <li class="tich-text">No quarters have been approved by M&amp;E for this department yet.</li>
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
                                <div class="tich-flex-wrap" style="gap:0.5rem;">
                                    <a href="{{ route($reportRoutes['edit'], $report) }}" class="tich-link">
                                        {{ in_array($report->status, ['draft', 'returned'], true) ? 'Edit' : 'View' }}
                                    </a>
                                    <a href="{{ route($reportRoutes['pdf'], $report) }}" class="tich-btn tich-btn-ghost">Download PDF</a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        @include('partials.states.table-empty', ['colspan' => 4, 'title' => 'No reports yet', 'icon' => 'inbox'])
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="tich-mt-4">{{ $reports->links() }}</div>
    </div>
@endsection
