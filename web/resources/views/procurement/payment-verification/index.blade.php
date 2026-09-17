@extends('layouts.procurement')

@section('title', 'Payment & Verification')

@section('procurement-content')
    <x-page-toolbar
        title="Payment & Verification"
        meta="Three-way matching, discrepancy resolution, payment routing, and M-Pesa STK workflow"
    >
        <x-slot:actions>
            <a href="{{ route('procurement.payment-verification.matching') }}" class="tich-btn tich-btn-primary">Open matching engine</a>
            <a href="{{ route('procurement.payment-verification.invoices.create') }}" class="tich-btn tich-btn-secondary">Create Invoice</a>
        </x-slot:actions>
    </x-page-toolbar>

    <div class="tich-grid tich-grid--4 tich-mt-6">
        @foreach($summary as $metric)
            <article class="tich-card tich-stat">
                <p class="tich-caption">{{ $metric['label'] }}</p>
                <p class="tich-stat__value">{{ $metric['value'] }}</p>
            </article>
        @endforeach
    </div>

    <div class="tich-grid tich-grid--2 tich-mt-6">
        <a href="{{ route('procurement.payment-verification.matching') }}" class="tich-card tich-card--link">
            <strong>Three-way matching</strong>
            <span class="tich-text">PO, quotation, and invoice comparison with discrepancy detection.</span>
        </a>
        <a href="{{ route('procurement.payment-verification.discrepancies') }}" class="tich-card tich-card--link">
            <strong>Discrepancy queue</strong>
            <span class="tich-text">Supplier queries, escalations, and resolution records.</span>
        </a>
        <a href="{{ route('procurement.payment-verification.payments') }}" class="tich-card tich-card--link">
            <strong>Finance routing</strong>
            <span class="tich-text">Approved matched invoices and payment routing status.</span>
        </a>
        <a href="{{ route('procurement.payment-verification.mpesa-stk') }}" class="tich-card tich-card--link">
            <strong>M-Pesa STK push</strong>
            <span class="tich-text">Daraja workflow, callback handling, and payment confirmation.</span>
        </a>
    </div>

    @if(session('success'))
        <div class="tich-alert tich-alert--success tich-mt-6">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="tich-alert tich-alert--danger tich-mt-6">{{ session('error') }}</div>
    @endif

    <article class="tich-card tich-mt-6">
        <h2 class="tich-h3">Workflow in sequence</h2>
        <ol class="tich-list tich-mt-3">
            @foreach($workflowStages as $stage)
                <li>{{ $stage }}</li>
            @endforeach
        </ol>
    </article>

    <article class="tich-card tich-mt-6">
        <h2 class="tich-h3">Payment Summary</h2>
        <div class="tich-grid tich-grid--4 tich-mt-3">
            <div><strong>Pending:</strong> KES {{ number_format($paymentSummary['total_pending'] ?? 0, 2) }}</div>
            <div><strong>Success:</strong> KES {{ number_format($paymentSummary['total_success'] ?? 0, 2) }}</div>
            <div><strong>M-Pesa:</strong> KES {{ number_format($paymentSummary['total_mpesa'] ?? 0, 2) }}</div>
            <div><strong>Bank:</strong> KES {{ number_format($paymentSummary['total_bank'] ?? 0, 2) }}</div>
        </div>
    </article>
@endsection
