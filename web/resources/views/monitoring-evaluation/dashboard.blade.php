@extends('layouts.monitoring-evaluation')

@section('title', 'M&E Command Center')

@section('monitoring-evaluation-content')
    <x-page-toolbar title="Monitoring & evaluation" meta="Policy alignment, baseline plans, PIME cycle, and executive reporting" />

    <div class="tich-grid tich-grid--4 tich-mt-8">
        <article class="tich-card"><p class="tich-caption">Plans in M&amp;E review</p><p class="tich-h2 tich-mt-2">{{ $stats['plans_review'] }}</p></article>
        <article class="tich-card"><p class="tich-caption">Baselines locked</p><p class="tich-h2 tich-mt-2">{{ $stats['baselines'] }}</p></article>
        <article class="tich-card"><p class="tich-caption">Reports to verify</p><p class="tich-h2 tich-mt-2">{{ $stats['reports_queue'] }}</p></article>
        <article class="tich-card tich-card--highlight"><p class="tich-caption">Delivered to CEO</p><p class="tich-h2 tich-mt-2">{{ $stats['ceo_delivered'] }}</p></article>
    </div>

    @if ($currentPolicy)
        <article class="tich-card tich-mt-8">
            <div class="tich-flex-wrap" style="justify-content:space-between;gap:1rem;align-items:center;">
                <div>
                    <h2 class="tich-h3" style="margin:0;">Current Standard M&amp;E Policy</h2>
                    <p class="tich-caption tich-mt-1">{{ $currentPolicy->title }} · {{ $currentPolicy->fiscal_year }}@if($currentPolicy->version) · v{{ $currentPolicy->version }}@endif</p>
                </div>
                <a href="{{ route('monitoring_evaluation.policies.show', $currentPolicy) }}" class="tich-btn tich-btn-secondary">Manage policy</a>
            </div>
            @if ($signoff)
                <p class="tich-text tich-mt-4">HOD sign-off: <strong>{{ $signoff['signed'] }}</strong> / {{ $signoff['total'] }} departments</p>
            @endif
        </article>
    @else
        <div class="tich-alert tich-alert--info tich-mt-8">
            No published M&amp;E policy yet.
            <a href="{{ route('monitoring_evaluation.policies.create') }}" class="tich-link">Upload the Standard M&amp;E Policy</a>
        </div>
    @endif

    <div class="tich-grid tich-grid--2 tich-mt-8">
        <article class="tich-card">
            <div class="tich-flex" style="justify-content:space-between;align-items:center;gap:1rem;">
                <h2 class="tich-h3">Technical plans queue</h2>
                <a href="{{ route('monitoring_evaluation.plans.index') }}" class="tich-btn tich-btn-secondary">View all</a>
            </div>
            <ul class="tich-mt-4" style="margin:0;padding-left:1.25rem;">
                @forelse ($pendingPlans as $plan)
                    <li class="tich-text tich-mt-2">
                        <a href="{{ route('monitoring_evaluation.plans.show', $plan) }}" class="tich-link">{{ $plan->department?->dept_name }}</a>
                        <span class="tich-caption">· {{ str_replace('_', ' ', $plan->status) }}</span>
                    </li>
                @empty
                    <li class="tich-text">No plans awaiting M&amp;E review.</li>
                @endforelse
            </ul>
        </article>

        <article class="tich-card">
            <div class="tich-flex" style="justify-content:space-between;align-items:center;gap:1rem;">
                <h2 class="tich-h3">Verification queue</h2>
                <a href="{{ route('monitoring_evaluation.reports.index', ['status' => 'submitted']) }}" class="tich-btn tich-btn-secondary">View all</a>
            </div>
            <ul class="tich-mt-4" style="margin:0;padding-left:1.25rem;">
                @forelse ($pendingReports as $report)
                    <li class="tich-text tich-mt-2">
                        <a href="{{ route('monitoring_evaluation.reports.show', $report) }}" class="tich-link">{{ $report->department?->dept_name }}</a>
                        <span class="tich-caption">· {{ $report->quarter?->label() }}</span>
                    </li>
                @empty
                    <li class="tich-text">No quarterly reports awaiting verification.</li>
                @endforelse
            </ul>
        </article>
    </div>

    <article class="tich-card tich-mt-8">
        <div class="tich-flex" style="justify-content:space-between;align-items:center;gap:1rem;">
            <h2 class="tich-h3">Department health (QA × M&amp;E)</h2>
            <a href="{{ route('monitoring_evaluation.pime.index') }}" class="tich-btn tich-btn-secondary">PIME workspace</a>
        </div>
        <div class="tich-table-wrap tich-mt-4">
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
                            <td>{{ $row->qa_compliance_avg !== null ? number_format($row->qa_compliance_avg, 1).'%' : '—' }}</td>
                            <td>{{ $row->me_achievement_avg !== null ? number_format($row->me_achievement_avg, 1).'%' : '—' }}</td>
                            <td>{{ $row->health_score !== null ? number_format($row->health_score, 1) : '—' }}</td>
                            <td>{{ $row->health_rating ? ucfirst($row->health_rating) : '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="tich-text">Health scores appear after QA audits and verified M&amp;E reports.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </article>
@endsection
