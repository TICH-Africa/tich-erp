@extends('layouts.qa')

@section('title', 'QA Command Center')

@section('qa-content')
    <x-page-toolbar title="QA Command Center" meta="Quality plans, assessment sheets, capacity building, and compliance oversight" />

    <div class="tich-grid tich-grid--4 tich-mt-8">
        <article class="tich-card"><p class="tich-caption">Draft sheets</p><p class="tich-h2 tich-mt-2">{{ $stats['draft'] }}</p></article>
        <article class="tich-card"><p class="tich-caption">In the field</p><p class="tich-h2 tich-mt-2">{{ $stats['active'] }}</p></article>
        <article class="tich-card"><p class="tich-caption">Compiled reports</p><p class="tich-h2 tich-mt-2">{{ $stats['compiled'] }}</p></article>
        <article class="tich-card tich-card--highlight"><p class="tich-caption">Open corrective actions</p><p class="tich-h2 tich-mt-2">{{ $stats['corrective'] }}</p></article>
    </div>

    <div class="tich-grid tich-grid--2 tich-mt-8">
        <article class="tich-card">
            <h2 class="tich-h3">Compliance overview</h2>
            <p class="tich-caption tich-mt-2">Overall: {{ $totalCompliance }}% ({{ $complianceStatus }}) - {{ $openFlags }} open QCA flags ({{ $criticalFlags }} high/critical)</p>
            <div class="tich-mt-4" style="position:relative; height:240px;">
                <canvas id="complianceDoughnut"></canvas>
            </div>
            <div class="tich-flex tich-flex--between tich-mt-3" style="font-size:0.75rem;">
                <span class="tich-caption"><span style="color:#22c55e;">&#9679;</span> Pass: {{ $chartData['compliance']['green'] }}</span>
                <span class="tich-caption"><span style="color:#f59e0b;">&#9679;</span> Watch: {{ $chartData['compliance']['amber'] }}</span>
                <span class="tich-caption"><span style="color:#ef4444;">&#9679;</span> Fail: {{ $chartData['compliance']['red'] }}</span>
            </div>
        </article>

        <article class="tich-card">
            <h2 class="tich-h3">QCA flags by severity</h2>
            <p class="tich-caption tich-mt-2">Active flags - High/Critical lock downstream modules</p>
            <div class="tich-mt-4" style="position:relative; height:240px;">
                <canvas id="flagsBar"></canvas>
            </div>
        </article>
    </div>

    <div class="tich-grid tich-grid--2 tich-mt-8">
        <article class="tich-card">
            <h2 class="tich-h3">Corrective actions status</h2>
            <p class="tich-caption tich-mt-2">Lifecycle of all corrective actions</p>
            <div class="tich-mt-4" style="position:relative; height:240px;">
                <canvas id="actionsBar"></canvas>
            </div>
        </article>

        <article class="tich-card">
            <h2 class="tich-h3">Assessment plans</h2>
            <p class="tich-caption tich-mt-2">Current plan lifecycle distribution</p>
            <div class="tich-mt-4" style="position:relative; height:240px;">
                <canvas id="plansDoughnut"></canvas>
            </div>
        </article>
    </div>

    <div class="tich-grid tich-grid--2 tich-mt-8">
        <article class="tich-card">
            <div class="tich-flex" style="justify-content:space-between;align-items:center;gap:1rem;">
                <h2 class="tich-h3">Assessment sheets</h2>
                <a href="{{ route('qa.assessments.create') }}" class="tich-btn tich-btn-primary">Build sheet</a>
            </div>
            <ul class="tich-mt-4" style="margin:0;padding-left:1.25rem;">
                @forelse ($openPlans as $plan)
                    <li class="tich-text tich-mt-2">
                        <a href="{{ route('qa.assessments.show', $plan) }}" class="tich-link">{{ $plan->plan_name }}</a>
                        <span class="tich-caption">· {{ str_replace('_', ' ', $plan->status) }}</span>
                    </li>
                @empty
                    <li class="tich-text">No open assessment sheets yet.</li>
                @endforelse
            </ul>
            <a href="{{ route('qa.assessments.index') }}" class="tich-btn tich-btn-secondary tich-mt-4">View all</a>
        </article>

        <article class="tich-card">
            <h2 class="tich-h3">Corrective actions</h2>
            <ul class="tich-mt-4" style="margin:0;padding-left:1.25rem;">
                @forelse ($openActions as $action)
                    <li class="tich-text tich-mt-2">
                        <strong>{{ $action->department?->dept_name }}</strong>
                        <span class="tich-caption">due {{ $action->resolution_deadline?->format('d M Y') }}</span>
                        <p class="tich-caption">{{ \Illuminate\Support\Str::limit($action->flagged_reason, 120) }}</p>
                    </li>
                @empty
                    <li class="tich-text">No open corrective actions.</li>
                @endforelse
            </ul>
            <a href="{{ route('qa.corrective-actions.index') }}" class="tich-btn tich-btn-secondary tich-mt-4">Manage actions</a>
        </article>
    </div>

    @if ($topFailing->isNotEmpty())
        <div class="tich-card tich-mt-8">
            <h2 class="tich-h3">Failing departments (top 5)</h2>
            <table class="tich-admin-table tich-mt-4">
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
                            <td>
                                <span class="tich-badge tich-badge--critical">FAIL</span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            Chart.defaults.font.family = "'Instrument Sans', ui-sans-serif, system-ui, sans-serif";
            Chart.defaults.font.size = 11;

            var chartColors = {
                green: '#22c55e',
                amber: '#f59e0b',
                red: '#ef4444',
                blue: '#3b82f6',
                indigo: '#6366f1',
                purple: '#8b5cf6',
                orange: '#f97316',
            };

            var chartData = @json($chartData);

            // Compliance distribution doughnut
            new Chart(document.getElementById('complianceDoughnut'), {
                type: 'doughnut',
                data: {
                    labels: ['Pass (80%+)', 'Watch (60-79%)', 'Fail (below 60%)'],
                    datasets: [{
                        data: [chartData.compliance.green, chartData.compliance.amber, chartData.compliance.red],
                        backgroundColor: [chartColors.green, chartColors.amber, chartColors.red],
                        borderWidth: 2,
                        borderColor: '#fff',
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
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
                    cutout: '65%',
                }
            });

            // QCA flags by severity bar
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
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: function(ctx) {
                                    return ctx.raw + ' active flag(s)';
                                }
                            }
                        }
                    },
                    scales: {
                        y: { beginAtZero: true, ticks: { stepSize: 1 } },
                        x: { grid: { display: false } },
                    }
                }
            });

            // Corrective actions by status bar (horizontal)
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
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                callbacks: {
                                    label: function(ctx) { return ctx.raw + ' action(s)'; }
                                }
                            }
                        },
                        scales: {
                            x: { beginAtZero: true, ticks: { stepSize: 1 } },
                            y: { grid: { display: false } },
                        }
                    }
                });
            }

            // Plans by status doughnut
            var plansLabels = Object.keys(chartData.plansByStatus);
            var plansColors = plansLabels.map(function(s) {
                if (s === 'draft') return '#94a3b8';
                if (s === 'dispatched') return chartColors.blue;
                if (s === 'in_progress') return chartColors.amber;
                if (s === 'compiled') return chartColors.green;
                return chartColors.purple;
            });
            new Chart(document.getElementById('plansDoughnut'), {
                type: 'doughnut',
                data: {
                    labels: plansLabels.map(function(s) { return s.replace('_', ' '); }),
                    datasets: [{
                        data: Object.values(chartData.plansByStatus),
                        backgroundColor: plansColors,
                        borderWidth: 2,
                        borderColor: '#fff',
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'bottom', labels: { padding: 12 } },
                        tooltip: {
                            callbacks: {
                                label: function(ctx) { return ctx.label + ': ' + ctx.raw; }
                            }
                        }
                    },
                    cutout: '60%',
                }
            });
        });
    </script>
@endsection