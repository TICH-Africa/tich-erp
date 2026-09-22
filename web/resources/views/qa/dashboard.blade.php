@extends('layouts.qa')

@section('title', 'QA Command Center')

@section('qa-content')
<div class="tich-mod-dash">
    <header class="tich-mod-dash__hero">
        <div class="tich-mod-dash__hero-copy">
            <p class="tich-mod-dash__eyebrow">Quality assurance</p>
            <h1 class="tich-mod-dash__title">QA command center</h1>
            <p class="tich-mod-dash__lede">IQA assessments and compliance oversight — live overview.</p>
        </div>
    </header>

    <section class="tich-mod-dash__metrics" aria-label="Key QA metrics">
        <article class="tich-mod-dash__metric">
            <p class="tich-mod-dash__metric-label">Draft IQA</p>
            <p class="tich-mod-dash__metric-value">{{ $stats['draft'] }}</p>
        </article>
        <article class="tich-mod-dash__metric tich-mod-dash__metric--ok">
            <p class="tich-mod-dash__metric-label">Published IQA</p>
            <p class="tich-mod-dash__metric-value">{{ $stats['compiled'] }}</p>
        </article>
        <article class="tich-mod-dash__metric tich-mod-dash__metric--info">
            <p class="tich-mod-dash__metric-label">Overall compliance</p>
            <p class="tich-mod-dash__metric-value">{{ $totalCompliance }}%</p>
        </article>
        <article class="tich-mod-dash__metric">
            <p class="tich-mod-dash__metric-label">Compliance status</p>
            <p class="tich-mod-dash__metric-value" style="font-size:1.25rem;text-transform:uppercase;">{{ $complianceStatus }}</p>
        </article>
    </section>

    <section class="tich-mod-dash__charts" aria-label="QA analytics">
        <article class="tich-mod-dash__chart">
            <div class="tich-mod-dash__chart-head">
                <h3 class="tich-mod-dash__chart-title">Compliance overview</h3>
                <p class="tich-mod-dash__chart-meta">Overall: {{ $totalCompliance }}% ({{ $complianceStatus }})</p>
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
                <h3 class="tich-mod-dash__chart-title">IQA assessments</h3>
                <p class="tich-mod-dash__chart-meta">Current assessment lifecycle distribution</p>
            </div>
            <div class="tich-chart-card__canvas-wrap">
                <canvas id="plansDoughnut" aria-label="IQA assessments chart"></canvas>
            </div>
        </article>
    </section>

    <div class="tich-mod-dash__charts">
        <article class="tich-mod-dash__panel" style="margin-top:0;">
            <div class="tich-mod-dash__panel-head">
                <div>
                    <p class="tich-mod-dash__panel-eyebrow">Work queue</p>
                    <h2 class="tich-mod-dash__panel-title">IQA assessments</h2>
                </div>
                <form method="POST" action="{{ route('qa.assessments.store') }}" class="tich-inline-form">
                    @csrf
                    <button type="submit" class="tich-btn tich-btn-primary">New assessment</button>
                </form>
            </div>
            <ul style="margin:0;padding-left:1.25rem;">
                @forelse ($openPlans as $plan)
                    <li class="tich-text" style="margin-top:0.5rem;">
                        <a href="{{ route('qa.assessments.show', $plan) }}" class="tich-link">{{ $plan->title }} #{{ $plan->id }}</a>
                        <span class="tich-caption">· {{ str_replace('_', ' ', $plan->status) }}</span>
                    </li>
                @empty
                    <li class="tich-text">No draft IQA assessments yet.</li>
                @endforelse
            </ul>
            <a href="{{ route('qa.assessments.index') }}" class="tich-btn tich-btn-secondary" style="margin-top:1rem;">View all</a>
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
                teal: '#0f766e',
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

            var plansLabels = Object.keys(chartData.plansByStatus);
            var plansColors = plansLabels.map(function(s) {
                if (s === 'draft') return '#94a3b8';
                if (s === 'published') return chartColors.green;
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
