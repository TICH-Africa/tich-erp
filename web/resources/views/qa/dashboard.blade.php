@extends('layouts.qa')

@section('title', 'QA Command Center')

@section('qa-content')
<div class="tich-mod-dash">
    <header class="tich-mod-dash__hero">
        <div class="tich-mod-dash__hero-copy">
            <p class="tich-mod-dash__eyebrow">Quality assurance</p>
            <h1 class="tich-mod-dash__title">QA command center</h1>
            <p class="tich-mod-dash__lede">Quality plans, assessment sheets, capacity building, and compliance oversight — live overview.</p>
        </div>
        <div class="tich-mod-dash__hero-actions">
            <a href="{{ route('qa.assessments.create') }}" class="tich-btn tich-btn-primary">Build sheet</a>
            <a href="{{ route('qa.corrective-actions.index') }}" class="tich-btn tich-btn-secondary">Corrective actions</a>
        </div>
    </header>

    <section class="tich-mod-dash__metrics" aria-label="Key QA metrics">
        <article class="tich-mod-dash__metric">
            <p class="tich-mod-dash__metric-label">Draft sheets</p>
            <p class="tich-mod-dash__metric-value">{{ $stats['draft'] }}</p>
        </article>
        <article class="tich-mod-dash__metric tich-mod-dash__metric--info">
            <p class="tich-mod-dash__metric-label">In the field</p>
            <p class="tich-mod-dash__metric-value">{{ $stats['active'] }}</p>
        </article>
        <article class="tich-mod-dash__metric tich-mod-dash__metric--ok">
            <p class="tich-mod-dash__metric-label">Compiled reports</p>
            <p class="tich-mod-dash__metric-value">{{ $stats['compiled'] }}</p>
        </article>
        <article class="tich-mod-dash__metric {{ ($stats['corrective'] ?? 0) > 0 ? 'tich-mod-dash__metric--alert' : '' }}">
            <p class="tich-mod-dash__metric-label">Open corrective actions</p>
            <p class="tich-mod-dash__metric-value">{{ $stats['corrective'] }}</p>
        </article>
    </section>

    <section class="tich-mod-dash__charts" aria-label="QA analytics">
        <article class="tich-mod-dash__chart">
            <div class="tich-mod-dash__chart-head">
                <h3 class="tich-mod-dash__chart-title">Compliance overview</h3>
                <p class="tich-mod-dash__chart-meta">Overall: {{ $totalCompliance }}% ({{ $complianceStatus }}) — {{ $openFlags }} open QCA flags ({{ $criticalFlags }} high/critical)</p>
            </div>
            <div class="tich-chart-card__canvas-wrap">
                <canvas id="complianceDoughnut" aria-label="Compliance overview chart"></canvas>
            </div>
            <div class="tich-flex tich-flex--between tich-mt-3" style="font-size:0.75rem;">
                <span class="tich-caption"><span style="color:#22c55e;">&#9679;</span> Pass: {{ $chartData['compliance']['green'] }}</span>
                <span class="tich-caption"><span style="color:#f59e0b;">&#9679;</span> Watch: {{ $chartData['compliance']['amber'] }}</span>
                <span class="tich-caption"><span style="color:#ef4444;">&#9679;</span> Fail: {{ $chartData['compliance']['red'] }}</span>
            </div>
        </article>

        <article class="tich-mod-dash__chart">
            <div class="tich-mod-dash__chart-head">
                <h3 class="tich-mod-dash__chart-title">QCA flags by severity</h3>
                <p class="tich-mod-dash__chart-meta">Active flags — High/Critical lock downstream modules</p>
            </div>
            <div class="tich-chart-card__canvas-wrap">
                <canvas id="flagsBar" aria-label="QCA flags chart"></canvas>
            </div>
        </article>

        <article class="tich-mod-dash__chart">
            <div class="tich-mod-dash__chart-head">
                <h3 class="tich-mod-dash__chart-title">Corrective actions status</h3>
                <p class="tich-mod-dash__chart-meta">Lifecycle of all corrective actions</p>
            </div>
            <div class="tich-chart-card__canvas-wrap">
                <canvas id="actionsBar" aria-label="Corrective actions chart"></canvas>
            </div>
        </article>

        <article class="tich-mod-dash__chart">
            <div class="tich-mod-dash__chart-head">
                <h3 class="tich-mod-dash__chart-title">Assessment plans</h3>
                <p class="tich-mod-dash__chart-meta">Current plan lifecycle distribution</p>
            </div>
            <div class="tich-chart-card__canvas-wrap">
                <canvas id="plansDoughnut" aria-label="Assessment plans chart"></canvas>
            </div>
        </article>
    </section>

    <div class="tich-mod-dash__charts">
        <article class="tich-mod-dash__panel" style="margin-top:0;">
            <div class="tich-mod-dash__panel-head">
                <div>
                    <p class="tich-mod-dash__panel-eyebrow">Work queue</p>
                    <h2 class="tich-mod-dash__panel-title">Assessment sheets</h2>
                </div>
                <a href="{{ route('qa.assessments.create') }}" class="tich-btn tich-btn-primary">Build sheet</a>
            </div>
            <ul style="margin:0;padding-left:1.25rem;">
                @forelse ($openPlans as $plan)
                    <li class="tich-text" style="margin-top:0.5rem;">
                        <a href="{{ route('qa.assessments.show', $plan) }}" class="tich-link">{{ $plan->plan_name }}</a>
                        <span class="tich-caption">· {{ str_replace('_', ' ', $plan->status) }}</span>
                    </li>
                @empty
                    <li class="tich-text">No open assessment sheets yet.</li>
                @endforelse
            </ul>
            <a href="{{ route('qa.assessments.index') }}" class="tich-btn tich-btn-secondary" style="margin-top:1rem;">View all</a>
        </article>

        <article class="tich-mod-dash__panel" style="margin-top:0;">
            <div class="tich-mod-dash__panel-head">
                <div>
                    <p class="tich-mod-dash__panel-eyebrow">Work queue</p>
                    <h2 class="tich-mod-dash__panel-title">Corrective actions</h2>
                </div>
            </div>
            <ul style="margin:0;padding-left:1.25rem;">
                @forelse ($openActions as $action)
                    <li class="tich-text" style="margin-top:0.5rem;">
                        <strong>{{ $action->department?->dept_name }}</strong>
                        <span class="tich-caption">due {{ $action->resolution_deadline?->format('d M Y') }}</span>
                        <p class="tich-caption">{{ \Illuminate\Support\Str::limit($action->flagged_reason, 120) }}</p>
                    </li>
                @empty
                    <li class="tich-text">No open corrective actions.</li>
                @endforelse
            </ul>
            <a href="{{ route('qa.corrective-actions.index') }}" class="tich-btn tich-btn-secondary" style="margin-top:1rem;">Manage actions</a>
        </article>
    </div>

    @if ($topFailing->isNotEmpty())
        <article class="tich-mod-dash__panel tich-mod-dash__panel--attention">
            <div class="tich-mod-dash__panel-head">
                <div>
                    <p class="tich-mod-dash__panel-eyebrow">Action needed</p>
                    <h2 class="tich-mod-dash__panel-title">Failing departments (top 5)</h2>
                </div>
            </div>
            <div class="tich-table-wrap">
                <table class="tich-admin-table">
                    <thead>
                        <tr>
                            <th>Department</th>
                            <th>Plan</th>
                            <th>Score</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($topFailing as $score)
                            <tr>
                                <td>{{ $score->department?->dept_name }}</td>
                                <td>{{ $score->plan?->plan_name }}</td>
                                <td>{{ number_format((float) $score->weighted_score, 1) }}%</td>
                                <td><span class="tich-badge tich-badge--critical">FAIL</span></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </article>
    @endif
</div>
@endsection

@section('scripts')
    @parent
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var isDark = document.documentElement.getAttribute('data-theme') === 'dark';
            var ink = isDark ? '#e2e8f0' : '#494c50';
            Chart.defaults.font.family = 'Arial, Calibri, ui-sans-serif, sans-serif';
            Chart.defaults.font.size = 11;
            Chart.defaults.color = ink;

            var chartColors = {
                green: '#22c55e',
                amber: '#f59e0b',
                red: '#ef4444',
                blue: '#1669a6',
                indigo: '#125a8c',
                teal: '#0f766e',
                orange: '#f97316',
            };

            var chartData = @json($chartData);

            new Chart(document.getElementById('complianceDoughnut'), {
                type: 'doughnut',
                data: {
                    labels: ['Pass (80%+)', 'Watch (60-79%)', 'Fail (below 60%)'],
                    datasets: [{
                        data: [chartData.compliance.green, chartData.compliance.amber, chartData.compliance.red],
                        backgroundColor: [chartColors.green, chartColors.amber, chartColors.red],
                        borderWidth: 0,
                        hoverBorderWidth: 0,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '65%',
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: function(ctx) {
                                    return ctx.label + ': ' + ctx.raw + ' departments';
                                }
                            }
                        }
                    },
                }
            });

            new Chart(document.getElementById('flagsBar'), {
                type: 'bar',
                data: {
                    labels: ['Low', 'Medium', 'High', 'Critical'],
                    datasets: [{
                        label: 'Active flags',
                        data: [chartData.flagsBySeverity.Low, chartData.flagsBySeverity.Medium, chartData.flagsBySeverity.High, chartData.flagsBySeverity.Critical],
                        backgroundColor: [chartColors.blue, chartColors.indigo, chartColors.orange, chartColors.red],
                        borderRadius: 6,
                        barThickness: 40,
                        borderWidth: 0,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: { beginAtZero: true, ticks: { stepSize: 1, color: ink }, border: { display: false } },
                        x: { grid: { display: false }, ticks: { color: ink }, border: { display: false } },
                    }
                }
            });

            var actionsData = {
                labels: Object.keys(chartData.actionsByStatus),
                datasets: [{
                    label: 'Count',
                    data: Object.values(chartData.actionsByStatus),
                    backgroundColor: Object.keys(chartData.actionsByStatus).map(function(s) {
                        if (s === 'overdue') return chartColors.red;
                        if (s === 'open') return chartColors.orange;
                        if (s === 'in_progress') return chartColors.blue;
                        return chartColors.green;
                    }),
                    borderRadius: 6,
                    barThickness: 30,
                    borderWidth: 0,
                }]
            };
            if (actionsData.labels.length > 0) {
                new Chart(document.getElementById('actionsBar'), {
                    type: 'bar',
                    data: actionsData,
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        indexAxis: 'y',
                        plugins: { legend: { display: false } },
                        scales: {
                            x: { beginAtZero: true, ticks: { stepSize: 1, color: ink }, border: { display: false } },
                            y: { grid: { display: false }, ticks: { color: ink }, border: { display: false } },
                        }
                    }
                });
            }

            var plansLabels = Object.keys(chartData.plansByStatus);
            var plansColors = plansLabels.map(function(s) {
                if (s === 'draft') return '#94a3b8';
                if (s === 'dispatched') return chartColors.blue;
                if (s === 'in_progress') return chartColors.amber;
                if (s === 'compiled') return chartColors.green;
                return chartColors.teal;
            });
            new Chart(document.getElementById('plansDoughnut'), {
                type: 'doughnut',
                data: {
                    labels: plansLabels.map(function(s) { return s.replace('_', ' '); }),
                    datasets: [{
                        data: Object.values(chartData.plansByStatus),
                        backgroundColor: plansColors,
                        borderWidth: 0,
                        hoverBorderWidth: 0,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '60%',
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: { color: ink, boxBorderWidth: 0, padding: 12, boxWidth: 10, boxHeight: 10 }
                        }
                    },
                }
            });
        });
    </script>
@endsection
