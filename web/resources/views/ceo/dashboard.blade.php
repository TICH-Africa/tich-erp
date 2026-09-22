@extends('layouts.ceo')

@section('title', 'CEO Office')

@section('ceo-content')
<div class="tich-mod-dash">
    <header class="tich-mod-dash__hero">
        <div class="tich-mod-dash__hero-copy">
            <p class="tich-mod-dash__eyebrow">Executive oversight</p>
            <h1 class="tich-mod-dash__title">CEO command center</h1>
            <p class="tich-mod-dash__lede">Institution-wide authorizations across finance, academics, quality, and M&amp;E.</p>
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
    </section>

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
            <a href="{{ route('ceo.curriculum.index') }}" class="tich-mod-dash__nav-card">
                <span class="tich-mod-dash__nav-index">03</span>
                <h3 class="tich-mod-dash__nav-title">Curriculum sign-off</h3>
                <p class="tich-mod-dash__nav-text">Approve programme curricula pending CEO publication.</p>
                @if ($pendingCurriculum > 0)
                    <span class="tich-mod-dash__nav-badge">{{ $pendingCurriculum }} pending</span>
                @endif
            </a>
            <a href="{{ route('ceo.academics.index') }}" class="tich-mod-dash__nav-card">
                <span class="tich-mod-dash__nav-index">04</span>
                <h3 class="tich-mod-dash__nav-title">Academics hub</h3>
                <p class="tich-mod-dash__nav-text">Institution-wide academics snapshot and learning departments.</p>
            </a>
            <a href="{{ route('ceo.quality.index') }}" class="tich-mod-dash__nav-card">
                <span class="tich-mod-dash__nav-index">05</span>
                <h3 class="tich-mod-dash__nav-title">Quality reports</h3>
                <p class="tich-mod-dash__nav-text">Compiled QA compliance scores and quality reports.</p>
            </a>
            <a href="{{ route('ceo.me.index') }}" class="tich-mod-dash__nav-card">
                <span class="tich-mod-dash__nav-index">06</span>
                <h3 class="tich-mod-dash__nav-title">M&amp;E reports</h3>
                <p class="tich-mod-dash__nav-text">Verified quarterly packages and department health ratings.</p>
            </a>
            @if (auth()->user()->hasEmployeeProfile())
                <a href="{{ route('employee.dashboard') }}" class="tich-mod-dash__nav-card">
                    <span class="tich-mod-dash__nav-index">07</span>
                    <h3 class="tich-mod-dash__nav-title">Employee portal</h3>
                    <p class="tich-mod-dash__nav-text">Personal profile, leave, and workplace self-service.</p>
                </a>
            @endif
        </div>
    </section>
</div>
@endsection
