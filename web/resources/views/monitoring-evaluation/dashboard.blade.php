@extends('layouts.monitoring-evaluation')

@section('title', 'M&E Command Center')

@section('monitoring-evaluation-content')
<div class="tich-mod-dash">
    <header class="tich-mod-dash__hero">
        <div class="tich-mod-dash__hero-copy">
            <p class="tich-mod-dash__eyebrow">Performance intelligence</p>
            <h1 class="tich-mod-dash__title">M&amp;E command center</h1>
            <p class="tich-mod-dash__lede">Policy alignment, baseline plans, PIME cycle, and executive reporting — live overview.</p>
        </div>
    </header>

    <section class="tich-mod-dash__metrics" aria-label="Key M&E metrics">
        <article class="tich-mod-dash__metric {{ ($stats['plans_review'] ?? 0) > 0 ? 'tich-mod-dash__metric--alert' : '' }}">
            <p class="tich-mod-dash__metric-label">Plans in review</p>
            <p class="tich-mod-dash__metric-value">{{ $stats['plans_review'] }}</p>
        </article>
        <article class="tich-mod-dash__metric tich-mod-dash__metric--ok">
            <p class="tich-mod-dash__metric-label">Baselines locked</p>
            <p class="tich-mod-dash__metric-value">{{ $stats['baselines'] }}</p>
        </article>
        <article class="tich-mod-dash__metric {{ ($stats['reports_queue'] ?? 0) > 0 ? 'tich-mod-dash__metric--alert' : '' }}">
            <p class="tich-mod-dash__metric-label">Reports to verify</p>
            <p class="tich-mod-dash__metric-value">{{ $stats['reports_queue'] }}</p>
        </article>
        <article class="tich-mod-dash__metric tich-mod-dash__metric--info">
            <p class="tich-mod-dash__metric-label">Delivered to CEO</p>
            <p class="tich-mod-dash__metric-value">{{ $stats['ceo_delivered'] }}</p>
        </article>
    </section>

    @if ($currentPolicy)
        <article class="tich-mod-dash__panel">
            <div class="tich-mod-dash__panel-head">
                <div>
                    <p class="tich-mod-dash__panel-eyebrow">Policy</p>
                    <h2 class="tich-mod-dash__panel-title">Current Standard M&amp;E Policy</h2>
                    <p class="tich-mod-dash__panel-meta">{{ $currentPolicy->title }} · {{ $currentPolicy->fiscal_year }}@if($currentPolicy->version) · v{{ $currentPolicy->version }}@endif</p>
                    @if ($signoff)
                        <p class="tich-mod-dash__panel-meta">HOD sign-off: <strong>{{ $signoff['signed'] }}</strong> / {{ $signoff['total'] }} departments</p>
                    @endif
                </div>
                <a href="{{ route('monitoring_evaluation.policies.show', $currentPolicy) }}" class="tich-btn tich-btn-secondary">Manage policy</a>
            </div>
        </article>
    @else
        <div class="tich-alert tich-alert--info" style="margin-top:1.5rem;">
            No published M&amp;E policy yet.
            <a href="{{ route('monitoring_evaluation.policies.create') }}" class="tich-link">Upload the Standard M&amp;E Policy</a>
        </div>
    @endif

    <div class="tich-mod-dash__charts">
        <article class="tich-mod-dash__panel" style="margin-top:0;">
            <div class="tich-mod-dash__panel-head">
                <div>
                    <p class="tich-mod-dash__panel-eyebrow">Queue</p>
                    <h2 class="tich-mod-dash__panel-title">Technical plans</h2>
                </div>
                <a href="{{ route('monitoring_evaluation.plans.index') }}" class="tich-btn tich-btn-secondary">View all</a>
            </div>
            <ul style="margin:0;padding-left:1.25rem;">
                @forelse ($pendingPlans as $plan)
                    <li class="tich-text" style="margin-top:0.5rem;">
                        <a href="{{ route('monitoring_evaluation.plans.show', $plan) }}" class="tich-link">{{ $plan->department?->dept_name }}</a>
                        <span class="tich-caption">· {{ \App\Support\StatusTone::label($plan->status) }}</span>
                    </li>
                @empty
                    <li class="tich-text">No plans awaiting M&amp;E review.</li>
                @endforelse
            </ul>
        </article>

        <article class="tich-mod-dash__panel" style="margin-top:0;">
            <div class="tich-mod-dash__panel-head">
                <div>
                    <p class="tich-mod-dash__panel-eyebrow">Queue</p>
                    <h2 class="tich-mod-dash__panel-title">Verification</h2>
                </div>
                <a href="{{ route('monitoring_evaluation.reports.index', ['status' => 'submitted']) }}" class="tich-btn tich-btn-secondary">View all</a>
            </div>
            <ul style="margin:0;padding-left:1.25rem;">
                @forelse ($pendingReports as $report)
                    <li class="tich-text" style="margin-top:0.5rem;">
                        <a href="{{ route('monitoring_evaluation.reports.show', $report) }}" class="tich-link">{{ $report->department?->dept_name }}</a>
                        <span class="tich-caption">· {{ $report->quarter?->label() }}</span>
                    </li>
                @empty
                    <li class="tich-text">No quarterly reports awaiting verification.</li>
                @endforelse
            </ul>
        </article>
    </div>

    <article class="tich-mod-dash__panel">
        <div class="tich-mod-dash__panel-head">
            <div>
                <p class="tich-mod-dash__panel-eyebrow">Health</p>
                <h2 class="tich-mod-dash__panel-title">Department health (QA × M&amp;E)</h2>
            </div>
            <a href="{{ route('monitoring_evaluation.pime.index') }}" class="tich-btn tich-btn-secondary">PIME workspace</a>
        </div>
        <div class="tich-table-wrap">
            <table class="tich-admin-table">
                <thead>
                    <tr>
                        <th>Department</th>
                        <th>QA avg</th>
                        <th>M&amp;E achievement</th>
                        <th>Health</th>
                        <th>Rating</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($health as $row)
                        <tr>
                            <td>{{ $row->department?->dept_name }}</td>
                            <td>{{ $row->qa_compliance_avg !== null ? number_format($row->qa_compliance_avg, 1).'%' : '-' }}</td>
                            <td>{{ $row->me_achievement_avg !== null ? number_format($row->me_achievement_avg, 1).'%' : '-' }}</td>
                            <td>{{ $row->health_score !== null ? number_format($row->health_score, 1) : '-' }}</td>
                            <td>{{ $row->health_rating ? ucfirst($row->health_rating) : '-' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="tich-text">Health scores appear after QA audits and verified M&amp;E reports.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </article>
</div>
@endsection
