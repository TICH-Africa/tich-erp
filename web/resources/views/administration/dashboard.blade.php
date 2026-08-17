@extends('layouts.administration')

@section('title', 'Administration Dashboard')

@section('administration-content')
    <x-page-toolbar title="Administration" meta="Institutional planning, admissions ops, compliance, and procurement visibility" />

    <div class="tich-stat-row tich-stat-row--4 tich-mt-8">
        <div class="tich-stat">
            <p class="tich-stat__label">Open planning cycles</p>
            <p class="tich-stat__value">{{ number_format($planningOpen) }}</p>
        </div>
        <div class="tich-stat">
            <p class="tich-stat__label">Pending approvals</p>
            <p class="tich-stat__value">{{ number_format($pendingApprovals) }}</p>
        </div>
        <div class="tich-stat">
            <p class="tich-stat__label">Funds released</p>
            <p class="tich-stat__value">KES {{ number_format($releasedFunds, 0) }}</p>
        </div>
        <div class="tich-stat">
            <p class="tich-stat__label">Inspection readiness</p>
            <p class="tich-stat__value">{{ number_format($inspectionScore, 0) }}%</p>
        </div>
    </div>

    <div class="tich-grid tich-grid--2 tich-mt-8" style="gap: 1.5rem; align-items: start;">
        <article class="tich-card">
            <h2 class="tich-h3">Admissions lifecycle</h2>
            <p class="tich-caption tich-mt-2">5-step flow across submission, verification, payment, approval, and letters.</p>
            <ul class="tich-program-card__meta tich-mt-4">
                <li><span class="tich-caption">1. Submission</span> {{ $lifecycle['submission'] }}</li>
                <li><span class="tich-caption">2. Academic verification</span> {{ $lifecycle['academic_verification'] }}</li>
                <li><span class="tich-caption">3. Payment</span> {{ $lifecycle['payment'] }}</li>
                <li><span class="tich-caption">4. Admin approval</span> {{ $lifecycle['admin_approval'] }}</li>
                <li><span class="tich-caption">5. Letter generation</span> {{ $lifecycle['letter_generation'] }}</li>
            </ul>
            <a href="{{ route('administration.lifecycle.index') }}" class="tich-btn tich-btn-secondary tich-mt-4">Open lifecycle</a>
        </article>

        <article class="tich-card">
            <h2 class="tich-h3">Procurement-to-pay</h2>
            <p class="tich-caption tich-mt-2">Supplier vetting through invoice verification and three-way match.</p>
            <ul class="tich-program-card__meta tich-mt-4">
                <li><span class="tich-caption">Suppliers</span> {{ $p2p['suppliers'] }}</li>
                <li><span class="tich-caption">Purchase orders</span> {{ $p2p['purchase_orders'] }}</li>
                <li><span class="tich-caption">Open AP</span> {{ $p2p['ap_open'] }}</li>
                <li><span class="tich-caption">Awaiting 3-way match</span> {{ $p2p['three_way_pending'] }}</li>
            </ul>
            <a href="{{ route('administration.procurement-pay.index') }}" class="tich-btn tich-btn-secondary tich-mt-4">View pipeline</a>
        </article>
    </div>

    <h3 class="tich-h3 tich-mt-8 tich-mb-4">Module hubs</h3>
    <div class="tich-grid tich-grid--3" style="gap: 0.75rem;">
        <a href="{{ route('administration.planning.index') }}" class="tich-card tich-card--hover" style="text-decoration:none;color:inherit;">
            <h3 class="tich-h4">Multi-tier planning</h3>
            <p class="tich-caption tich-mt-2">Annual, monthly, and weekly cycles with requisition deadlines.</p>
        </a>
        <a href="{{ route('administration.budget-aggregation.index') }}" class="tich-card tich-card--hover" style="text-decoration:none;color:inherit;">
            <h3 class="tich-h4">Budget aggregation</h3>
            <p class="tich-caption tich-mt-2">Cross-department consolidation and CBE frameworks.</p>
        </a>
        <a href="{{ route('administration.approvals.index') }}" class="tich-card tich-card--hover" style="text-decoration:none;color:inherit;">
            <h3 class="tich-h4">Approval workflow</h3>
            <p class="tich-caption tich-mt-2">Department → Finance → Executive/CEO authorization.</p>
        </a>
        <a href="{{ route('administration.fund-distribution.index') }}" class="tich-card tich-card--hover" style="text-decoration:none;color:inherit;">
            <h3 class="tich-h4">Fund distribution</h3>
            <p class="tich-caption tich-mt-2">Digital release of monthly allocations.</p>
        </a>
        <a href="{{ route('administration.statutory.index') }}" class="tich-card tich-card--hover" style="text-decoration:none;color:inherit;">
            <h3 class="tich-h4">Statutory tracking</h3>
            <p class="tich-caption tich-mt-2">KRA, TVETA, and MoE certifications.</p>
        </a>
        <a href="{{ route('administration.ledger-sync.index') }}" class="tich-card tich-card--hover" style="text-decoration:none;color:inherit;">
            <h3 class="tich-h4">QuickBooks sync</h3>
            <p class="tich-caption tich-mt-2">Payment and AP ledger synchronization.</p>
        </a>
    </div>
@endsection
