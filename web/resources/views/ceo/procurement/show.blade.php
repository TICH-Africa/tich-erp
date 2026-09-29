@extends('layouts.ceo')

@section('title', 'Requisition '.$requisition->requisition_number)

@section('ceo-content')
    <x-page-toolbar
        :title="'Requisition '.$requisition->requisition_number"
        meta="Executive procurement decision"
    >
        <x-slot:actions>
            <a href="{{ route('ceo.procurement.index') }}" class="tich-btn tich-btn-ghost">Back</a>
        </x-slot:actions>
    </x-page-toolbar>

    @if (session('status'))
        <div class="tich-alert tich-alert--success tich-mt-4">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="tich-alert tich-alert--error tich-mt-4">@foreach ($errors->all() as $e)<p>{{ $e }}</p>@endforeach</div>
    @endif

    <div class="tich-grid tich-grid--3 tich-mt-6">
        <article class="tich-card tich-stat">
            <p class="tich-caption">Status</p>
            <p class="tich-stat__value">{{ $requisition->statusLabel() }}</p>
        </article>
        <article class="tich-card tich-stat">
            <p class="tich-caption">Estimated cost</p>
            <p class="tich-stat__value">KES {{ number_format((float) $requisition->estimated_cost, 2) }}</p>
        </article>
        <article class="tich-card tich-stat">
            <p class="tich-caption">Department</p>
            <p class="tich-stat__value" style="font-size:1.1rem;">{{ $requisition->department?->dept_name ?? '—' }}</p>
        </article>
    </div>

    <div class="tich-grid tich-grid--2 tich-mt-6" style="gap:1.5rem;">
        <article class="tich-card">
            <h2 class="tich-h3" style="margin-top:0;">Details</h2>
            <dl class="tich-dl">
                <dt>Item / service</dt>
                <dd>{{ $requisition->requested_item ?? '—' }}</dd>
                <dt>Requested by</dt>
                <dd>{{ $requisition->requester?->fullName() ?? '—' }}</dd>
                <dt>Request date</dt>
                <dd>{{ $requisition->request_date?->format('d M Y') ?? '—' }}</dd>
                <dt>Budget code</dt>
                <dd>{{ $requisition->budget_code ?? '—' }}</dd>
                <dt>Justification</dt>
                <dd>{{ $requisition->justification }}</dd>
            </dl>
        </article>
        <article class="tich-card">
            <h2 class="tich-h3" style="margin-top:0;">Approval trail</h2>
            @if ($requisition->hod_approved_at)
                <p class="tich-caption">HOD: {{ $requisition->hodApprover?->fullName() ?? '—' }} · {{ $requisition->hod_approved_at->format('d M Y H:i') }}</p>
            @endif
            @if ($requisition->finance_approved_at)
                <p class="tich-caption">Finance: {{ $requisition->financeApprover?->fullName() ?? '—' }} · {{ $requisition->finance_approved_at->format('d M Y H:i') }}</p>
            @endif
            @if ($requisition->ceo_approved_at)
                <p class="tich-caption">CEO: {{ $requisition->ceoApprover?->fullName() ?? '—' }} · {{ $requisition->ceo_approved_at->format('d M Y H:i') }}</p>
            @endif
            <div class="tich-mt-4">
                <h3 class="tich-h4">Budget verification</h3>
                <p class="tich-text {{ $budgetCheck['passed'] ? 'tich-text--success' : 'tich-text--danger' }}">{{ $budgetCheck['message'] }}</p>
            </div>
        </article>
    </div>

    @if ($canDecide)
        <div class="tich-grid tich-grid--2 tich-mt-6" style="gap:1.5rem;">
            <form method="POST" action="{{ route('ceo.procurement.approve', $requisition) }}" class="tich-card tich-form-stack">
                @csrf
                <h2 class="tich-h3">Approve</h2>
                <div class="tich-form-group">
                    <label class="tich-label" for="approve_comments">Comments</label>
                    <textarea id="approve_comments" name="comments" class="tich-input" rows="3" maxlength="1000">{{ old('comments') }}</textarea>
                </div>
                <button type="submit" class="tich-btn tich-btn-primary">Approve requisition</button>
            </form>
            <form method="POST" action="{{ route('ceo.procurement.reject', $requisition) }}" class="tich-card tich-form-stack">
                @csrf
                <h2 class="tich-h3">Reject</h2>
                <div class="tich-form-group">
                    <label class="tich-label" for="reject_comments">Reason *</label>
                    <textarea id="reject_comments" name="comments" class="tich-input" rows="3" maxlength="1000" required>{{ old('comments') }}</textarea>
                </div>
                <button type="submit" class="tich-btn tich-btn-danger">Reject requisition</button>
            </form>
        </div>
    @endif
@endsection
