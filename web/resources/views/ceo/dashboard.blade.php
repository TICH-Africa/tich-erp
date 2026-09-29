@extends('layouts.ceo')

@section('title', 'CEO Office')

@section('ceo-content')
<div class="tich-mod-dash">
    <header class="tich-mod-dash__hero">
        <div class="tich-mod-dash__hero-copy">
            <p class="tich-mod-dash__eyebrow">Executive oversight</p>
            <h1 class="tich-mod-dash__title">CEO command center</h1>
            <p class="tich-mod-dash__lede">Institution-wide authorizations, policy sign-off, and operational analytics.</p>
        </div>
    </header>

    <section class="tich-mod-dash__metrics" aria-label="Executive queues">
        <article class="tich-mod-dash__metric {{ ($pendingBudgets ?? 0) > 0 ? 'tich-mod-dash__metric--alert' : 'tich-mod-dash__metric--ok' }}">
            <p class="tich-mod-dash__metric-label">Budgets awaiting</p>
            <p class="tich-mod-dash__metric-value">{{ $pendingBudgets }}</p>
        </article>
        <article class="tich-mod-dash__metric {{ ($pendingCurriculum ?? 0) > 0 ? 'tich-mod-dash__metric--alert' : '' }}">
            <p class="tich-mod-dash__metric-label">Curriculum pending</p>
            <p class="tich-mod-dash__metric-value">{{ $pendingCurriculum }}</p>
        </article>
        <article class="tich-mod-dash__metric {{ ($pendingProcurement ?? 0) > 0 ? 'tich-mod-dash__metric--alert' : '' }}">
            <p class="tich-mod-dash__metric-label">Procurement awaiting</p>
            <p class="tich-mod-dash__metric-value">{{ $pendingProcurement }}</p>
        </article>
        <article class="tich-mod-dash__metric {{ (($queues['finance_policy'] ?? 0) + ($queues['me_policy'] ?? 0)) > 0 ? 'tich-mod-dash__metric--alert' : '' }}">
            <p class="tich-mod-dash__metric-label">Policies to sign</p>
            <p class="tich-mod-dash__metric-value">{{ ($queues['finance_policy'] ?? 0) + ($queues['me_policy'] ?? 0) }}</p>
        </article>
    </section>

    <section class="tich-mod-dash__metrics tich-mt-6" aria-label="Finance analytics">
        <p class="tich-mod-dash__section-label" style="grid-column:1/-1;">Finance</p>
        <article class="tich-mod-dash__metric">
            <p class="tich-mod-dash__metric-label">Revenue</p>
            <p class="tich-mod-dash__metric-value" style="font-size:1.35rem;">KES {{ number_format((float) ($finance['revenue'] ?? 0), 0) }}</p>
        </article>
        <article class="tich-mod-dash__metric">
            <p class="tich-mod-dash__metric-label">Expenditure</p>
            <p class="tich-mod-dash__metric-value" style="font-size:1.35rem;">KES {{ number_format((float) ($finance['expenses'] ?? 0), 0) }}</p>
        </article>
        <article class="tich-mod-dash__metric">
            <p class="tich-mod-dash__metric-label">Net income</p>
            <p class="tich-mod-dash__metric-value" style="font-size:1.35rem;">KES {{ number_format((float) ($finance['net_income'] ?? 0), 0) }}</p>
        </article>
        <article class="tich-mod-dash__metric">
            <p class="tich-mod-dash__metric-label">Receivables</p>
            <p class="tich-mod-dash__metric-value" style="font-size:1.35rem;">KES {{ number_format((float) ($finance['accounts_receivable'] ?? 0), 0) }}</p>
        </article>
    </section>

    <section class="tich-mod-dash__metrics tich-mt-6" aria-label="Academics and workforce">
        <p class="tich-mod-dash__section-label" style="grid-column:1/-1;">Academics &amp; workforce</p>
        <article class="tich-mod-dash__metric">
            <p class="tich-mod-dash__metric-label">Students</p>
            <p class="tich-mod-dash__metric-value">{{ number_format((int) ($academics['total_students'] ?? 0)) }}</p>
        </article>
        <article class="tich-mod-dash__metric">
            <p class="tich-mod-dash__metric-label">Active students</p>
            <p class="tich-mod-dash__metric-value">{{ number_format((int) ($academics['active_students'] ?? 0)) }}</p>
        </article>
        <article class="tich-mod-dash__metric">
            <p class="tich-mod-dash__metric-label">Programmes</p>
            <p class="tich-mod-dash__metric-value">{{ number_format((int) ($academics['programs'] ?? 0)) }}</p>
        </article>
        <article class="tich-mod-dash__metric">
            <p class="tich-mod-dash__metric-label">Active staff</p>
            <p class="tich-mod-dash__metric-value">{{ number_format((int) ($workforce['active_staff'] ?? 0)) }}</p>
        </article>
    </section>

    @if (($academics['students_by_program'] ?? collect())->isNotEmpty())
        <section class="tich-card tich-table-panel tich-mt-6" aria-label="Students by programme">
            <h2 class="tich-h3" style="margin-top:0;">Students by programme</h2>
            <div class="tich-table-wrap tich-mt-4">
                <table class="tich-admin-table">
                    <thead><tr><th>Programme</th><th>Students</th></tr></thead>
                    <tbody>
                        @foreach ($academics['students_by_program'] as $row)
                            <tr>
                                <td>{{ $row['name'] }}</td>
                                <td>{{ number_format((int) $row['count']) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @endif

    <section class="tich-mod-dash__nav" aria-label="CEO hubs">
        <p class="tich-mod-dash__section-label">Executive hubs</p>
        <div class="tich-mod-dash__nav-grid">
            <a href="{{ route('ceo.budgets.index') }}" class="tich-mod-dash__nav-card">
                <span class="tich-mod-dash__nav-index">01</span>
                <h3 class="tich-mod-dash__nav-title">Budget authorizations</h3>
                <p class="tich-mod-dash__nav-text">Review and authorize department budgets awaiting executive approval.</p>
                @if ($pendingBudgets > 0)
                    <span class="tich-mod-dash__nav-badge">{{ $pendingBudgets }} awaiting</span>
                @endif
            </a>
            <a href="{{ route('ceo.approvals.index') }}" class="tich-mod-dash__nav-card">
                <span class="tich-mod-dash__nav-index">02</span>
                <h3 class="tich-mod-dash__nav-title">Approval workflow</h3>
                <p class="tich-mod-dash__nav-text">Track department-to-finance-to-executive budget routing.</p>
            </a>
            <a href="{{ route('ceo.procurement.index') }}" class="tich-mod-dash__nav-card">
                <span class="tich-mod-dash__nav-index">03</span>
                <h3 class="tich-mod-dash__nav-title">Procurement</h3>
                <p class="tich-mod-dash__nav-text">Approve or reject requisitions awaiting CEO decision.</p>
                @if ($pendingProcurement > 0)
                    <span class="tich-mod-dash__nav-badge">{{ $pendingProcurement }} awaiting</span>
                @endif
            </a>
            <a href="{{ route('ceo.curriculum.index') }}" class="tich-mod-dash__nav-card">
                <span class="tich-mod-dash__nav-index">04</span>
                <h3 class="tich-mod-dash__nav-title">Curriculum sign-off</h3>
                <p class="tich-mod-dash__nav-text">Approve programme curricula pending CEO publication.</p>
                @if ($pendingCurriculum > 0)
                    <span class="tich-mod-dash__nav-badge">{{ $pendingCurriculum }} pending</span>
                @endif
            </a>
            <a href="{{ route('ceo.academics.index') }}" class="tich-mod-dash__nav-card">
                <span class="tich-mod-dash__nav-index">05</span>
                <h3 class="tich-mod-dash__nav-title">Academics hub</h3>
                <p class="tich-mod-dash__nav-text">Institution-wide academics snapshot and learning departments.</p>
            </a>
            <a href="{{ route('ceo.quality.index') }}" class="tich-mod-dash__nav-card">
                <span class="tich-mod-dash__nav-index">06</span>
                <h3 class="tich-mod-dash__nav-title">Quality reports</h3>
                <p class="tich-mod-dash__nav-text">Compiled QA compliance scores and quality reports.</p>
            </a>
            <a href="{{ route('ceo.me.index') }}" class="tich-mod-dash__nav-card">
                <span class="tich-mod-dash__nav-index">07</span>
                <h3 class="tich-mod-dash__nav-title">M&amp;E reports</h3>
                <p class="tich-mod-dash__nav-text">Verified quarterly packages and department health ratings.</p>
            </a>
            <a href="{{ route('ceo.finance-policy.index') }}" class="tich-mod-dash__nav-card">
                <span class="tich-mod-dash__nav-index">08</span>
                <h3 class="tich-mod-dash__nav-title">Financial policy</h3>
                <p class="tich-mod-dash__nav-text">Review and digitally sign the published financial policy.</p>
                @if (($queues['finance_policy'] ?? 0) > 0)
                    <span class="tich-mod-dash__nav-badge">Sign required</span>
                @endif
            </a>
            <a href="{{ route('ceo.me-policy.index') }}" class="tich-mod-dash__nav-card">
                <span class="tich-mod-dash__nav-index">09</span>
                <h3 class="tich-mod-dash__nav-title">M&amp;E policy</h3>
                <p class="tich-mod-dash__nav-text">Review and digitally sign the published M&amp;E policy.</p>
                @if (($queues['me_policy'] ?? 0) > 0)
                    <span class="tich-mod-dash__nav-badge">Sign required</span>
                @endif
            </a>
            @if (auth()->user()->hasEmployeeProfile())
                <a href="{{ route('employee.dashboard') }}" class="tich-mod-dash__nav-card">
                    <span class="tich-mod-dash__nav-index">10</span>
                    <h3 class="tich-mod-dash__nav-title">Employee portal</h3>
                    <p class="tich-mod-dash__nav-text">Personal profile, leave, and workplace self-service.</p>
                </a>
            @endif
        </div>
    </section>
</div>
@endsection
