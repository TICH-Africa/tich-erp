@extends('layouts.ceo')

@section('title', 'CEO Office')

@section('ceo-content')
    <x-page-toolbar
        title="Chief Executive Officer"
        meta="Institution-wide executive oversight and authorizations"
    />

    <div class="tich-grid tich-grid--3 tich-mt-8">
        <article class="tich-card tich-card--highlight">
            <p class="tich-caption">Finance</p>
            <h3 class="tich-h3 tich-mt-2">Budget authorizations</h3>
            <p class="tich-text tich-mt-2">
                Review and authorize department budgets awaiting executive approval.
            </p>
            @if ($pendingBudgets > 0)
                <p class="tich-caption tich-mt-2">{{ $pendingBudgets }} awaiting your decision</p>
            @endif
            <a href="{{ route('ceo.budgets.index') }}" class="tich-btn tich-btn-primary tich-mt-4">Open budget queue</a>
        </article>

        <article class="tich-card">
            <p class="tich-caption">Administration</p>
            <h3 class="tich-h3 tich-mt-2">Approval workflow</h3>
            <p class="tich-text tich-mt-2">
                Track department-to-finance-to-executive budget routing across the institution.
            </p>
            <a href="{{ route('ceo.approvals.index') }}" class="tich-btn tich-btn-secondary tich-mt-4">Open approvals</a>
        </article>

        <article class="tich-card tich-card--highlight">
            <p class="tich-caption">Academics</p>
            <h3 class="tich-h3 tich-mt-2">Curriculum sign-off</h3>
            <p class="tich-text tich-mt-2">
                Approve programme curricula and versions pending CEO publication.
            </p>
            @if ($pendingCurriculum > 0)
                <p class="tich-caption tich-mt-2">{{ $pendingCurriculum }} programme(s) pending</p>
            @endif
            <a href="{{ route('ceo.curriculum.index') }}" class="tich-btn tich-btn-primary tich-mt-4">Review curriculum</a>
        </article>

        <article class="tich-card">
            <p class="tich-caption">Academics</p>
            <h3 class="tich-h3 tich-mt-2">Academics hub</h3>
            <p class="tich-text tich-mt-2">Institution-wide academics snapshot and learning department overview.</p>
            <a href="{{ route('ceo.academics.index') }}" class="tich-btn tich-btn-secondary tich-mt-4">Open academics overview</a>
        </article>

        @if (auth()->user()->hasEmployeeProfile())
            <article class="tich-card">
                <p class="tich-caption">My Portal</p>
                <h3 class="tich-h3 tich-mt-2">Employee portal</h3>
                <p class="tich-text tich-mt-2">Personal profile, leave, and workplace self-service.</p>
                <a href="{{ route('employee.dashboard') }}" class="tich-btn tich-btn-secondary tich-mt-4">Open employee portal</a>
            </article>
        @endif
    </div>
@endsection
