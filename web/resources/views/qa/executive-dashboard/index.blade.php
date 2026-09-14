@extends('layouts.qa')

@section('title', 'Executive Dashboard')

@section('qa-content')
    <x-page-toolbar title="Executive Dashboard" meta="Real-time compliance status across all hubs and programmes" />

    <div class="tich-grid tich-grid--4 tich-mt-8">
        <article class="tich-card tich-card--highlight">
            <p class="tich-caption">Overall compliance</p>
            <p class="tich-h2 tich-mt-2">{{ $totalCompliance }}%</p>
            <span class="tich-badge tich-badge--{{ $complianceStatus }}">{{ $complianceStatus === 'green' ? 'GREEN' : ($complianceStatus === 'amber' ? 'AMBER' : 'RED') }}</span>
        </article>
        <article class="tich-card">
            <p class="tich-caption">Active QCA flags</p>
            <p class="tich-h2 tich-mt-2">{{ $openFlags }}</p>
        </article>
        <article class="tich-card">
            <p class="tich-caption">High / Critical</p>
            <p class="tich-h2 tich-mt-2">{{ $criticalFlags }}</p>
        </article>
        <article class="tich-card">
            <p class="tich-caption">Open corrective actions</p>
            <p class="tich-h2 tich-mt-2">{{ $actionCount }}</p>
        </article>
    </div>

    <div class="tich-grid tich-grid--2 tich-mt-8">
        <article class="tich-card">
            <h2 class="tich-h3">Compliance distribution</h2>
            <p class="tich-caption tich-mt-2">How departments are performing: passing (80%+), watch (60-79%), failing (below 60%)</p>
            <div class="tich-mt-4" style="position:relative; height:280px;">
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
            <p class="tich-caption tich-mt-2">Active (open/in-progress) flags - High and Critical severity lock downstream modules</p>
            <div class="tich-mt-4" style="position:relative; height:280px;">
                <canvas id="flagsBar"></canvas>
            </div>
        </article>
    </div>

    <div class="tich-grid tich-grid--2 tich-mt-8">
        <article class="tich-card">
            <h2 class="tich-h3">Corrective actions status</h2>
            <p class="tich-caption tich-mt-2">Distribution of corrective actions across their lifecycle stages</p>
            <div class="tich-mt-4" style="position:relative; height:280px;">
                <canvas id="actionsBar"></canvas>
            </div>
        </article>

        <article class="tich-card">
            <h2 class="tich-h3">Audit activity (14 days)</h2>
            <p class="tich-caption tich-mt-2">QA audit log entries per day - shows monitoring intensity and recent activity</p>
            <div class="tich-mt-4" style="position:relative; height:280px;">
                <canvas id="auditLine"></canvas>
            </div>
        </article>
    </div>

    <div class="tich-grid tich-grid--2 tich-mt-8">
        <article class="tich-card">
            <h2 class="tich-h3">Assessment plans by status</h2>
            <p class="tich-caption tich-mt-2">Current assessment sheet lifecycle distribution</p>
            <div class="tich-mt-4" style="position:relative; height:260px;">
                <canvas id="plansDoughnut"></canvas>
            </div>
        </article>

        <article class="tich-card">
            <h2 class="tich-h3">What's happening in QA</h2>
            <div class="tich-mt-4">
                @if ($chartData['compliance']['red'] > 0)
                    <div class="tich-mb-4">
                        <p class="tich-text"><strong>{{ $chartData['compliance']['red'] }} departments</strong> are below the 60% compliance threshold and require immediate attention. Review failing departments below.</p>
                    </div>
                @endif
                @if ($criticalFlags > 0)
                    <div class="tich-mb-4">
                        <p class="tich-text"><strong>{{ $criticalFlags }} High/Critical QCA flags</strong> are active. These have downstream lock effects on HR, Tutor Workspace, Student Portal, Grade Book, and/or Exam Engine modules.</p>
                    </div>
                @endif
                @if ($actionCount > 0)
                    <div class="tich-mb-4">
                        <p class="tich-text"><strong>{{ $actionCount }} corrective actions</strong> are currently open, in-progress, or overdue. These were triggered by compliance scores falling below the pass threshold.</p>
                    </div>
                @endif
                @if ($failingDepartments->isNotEmpty())
                    <div class="tich-mb-4">
                        <p class="tich-text"><strong>{{ $failingDepartments->count() }} failing department-plan combinations</strong> need resolution. See the table below for details.</p>
                    </div>
                @endif
                @if ($chartData['compliance']['amber'] > 0)
                    <div class="tich-mb-4">
                        <p class="tich-text"><strong>{{ $chartData['compliance']['amber'] }} departments</strong> are in the watch zone (60-79% compliance). Monitor closely to prevent further decline.</p>
                    </div>
                @endif
                @if ($chartData['compliance']['green'] > 0 && $criticalFlags === 0 && $actionCount === 0)
                    <div>
                        <p class="tich-text">All departments are meeting compliance targets. Continue monitoring and schedule next assessment cycle.</p>
                    </div>
                @endif
            </div>
        </article>
    </div>

    @if ($failingDepartments->isNotEmpty())
        <div class="tich-card tich-mt-8">
            <h2 class="tich-h3">Failing departments</h2>
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
                    @foreach ($failingDepartments as $score)
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

    <div class="tich-card tich-mt-8">
        <h2 class="tich-h3">Quick links</h2>
        <div class="tich-grid tich-grid--4 tich-mt-4">
            <a href="{{ route('qa.assessments.index') }}" class="tich-card tich-card--link">
                <p class="tich-caption">Assessment sheets</p>
                <p class="tich-h3 tich-mt-2">{{ $activePlans }} active</p>
            </a>
            <a href="{{ route('qa.qca-flags.index') }}" class="tich-card tich-card--link">
                <p class="tich-caption">QCA flags</p>
                <p class="tich-h3 tich-mt-2">{{ $openFlags }} open</p>
            </a>
            <a href="{{ route('qa.corrective-actions.index') }}" class="tich-card tich-card--link">
                <p class="tich-caption">Corrective actions</p>
                <p class="tich-h3 tich-mt-2">{{ $actionCount }} open</p>
            </a>
            <a href="{{ route('qa.training-credits.index') }}" class="tich-card tich-card--link">
                <p class="tich-caption">Training credits</p>
                <p class="tich-h3 tich-mt-2">View</p>
            </a>
        </div>
    </div>

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

            // Corrective actions by status bar
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
                    barThickness: 40,
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

            // Audit trail line chart
            fetch('{{ route('qa.executive-dashboard.audit-trail') }}?days=14&per_page=1000')
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    if (data.logs && data.logs.length > 0) {
                        var dates = {};
                        data.logs.forEach(function(l) {
                            var d = l.timestamp.slice(0, 10);
                            dates[d] = (dates[d] || 0) + 1;
                        });
                        var labels = Object.keys(dates).sort();
                        var counts = labels.map(function(d) { return dates[d]; });

                        new Chart(document.getElementById('auditLine'), {
                            type: 'line',
                            data: {
                                labels: labels,
                                datasets: [{
                                    label: 'Audit entries',
                                    data: counts,
                                    borderColor: chartColors.blue,
                                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                                    fill: true,
                                    tension: 0.3,
                                    pointRadius: 3,
                                    pointBackgroundColor: chartColors.blue,
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: {
                                    legend: { display: false },
                                    tooltip: {
                                        callbacks: {
                                            label: function(ctx) { return ctx.raw + ' entries'; }
                                        }
                                    }
                                },
                                scales: {
                                    y: { beginAtZero: true, ticks: { stepSize: 1 } },
                                    x: { grid: { display: false } },
                                }
                            }
                        });
                    }
                })
                .catch(function() {});

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
