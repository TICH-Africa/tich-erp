@extends('layouts.ceo')

@section('title', 'Student waiver')

@section('ceo-content')
    <x-page-toolbar
        title="Student waiver"
        meta="Executive decision on a fee waiver, scholarship or bursary"
    >
        <x-slot:actions>
            <a href="{{ route('ceo.waivers.index') }}" class="tich-btn tich-btn-ghost">Back</a>
        </x-slot:actions>
    </x-page-toolbar>

    @if (session('status'))
        <div class="tich-alert tich-alert--success tich-mt-4">{{ session('status') }}</div>
    @endif
    @if (session('success'))
        <div class="tich-alert tich-alert--success tich-mt-4">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="tich-alert tich-alert--danger tich-mt-4">{{ session('error') }}</div>
    @endif

    <article class="tich-card tich-mt-6 ceo-waiver">
        <header class="ceo-waiver__head">
            <div class="ceo-waiver__who">
                <h2 class="tich-h3" style="margin:0;">{{ $adjustment->student?->fullName() ?? 'Student' }}</h2>
                <p class="tich-caption">{{ $adjustment->student?->registration_number ?? 'No registration number' }}</p>
            </div>
            <div class="ceo-waiver__head-side">
                <p class="ceo-waiver__amount">KES {{ number_format((float) $adjustment->amount, 2) }}</p>
                <span class="tich-badge tich-badge--{{ match($adjustment->status) {
                    'pending' => 'warning',
                    'approved' => 'success',
                    'rejected' => 'danger',
                    default => 'secondary',
                } }}">{{ ucfirst((string) $adjustment->status) }}</span>
            </div>
        </header>

        <dl class="tich-dl">
            <div class="tich-dl__row">
                <dt class="tich-dl__label">Type</dt>
                <dd class="tich-dl__value">{{ str_replace('_', ' ', ucfirst((string) $adjustment->adjustment_type)) }}</dd>
            </div>
            <div class="tich-dl__row">
                <dt class="tich-dl__label">Invoice</dt>
                <dd class="tich-dl__value">{{ $adjustment->invoice?->invoice_number ?? 'Not tied to one invoice' }}</dd>
            </div>
            <div class="tich-dl__row">
                <dt class="tich-dl__label">Requested by</dt>
                <dd class="tich-dl__value">{{ $adjustment->requestedBy?->fullName() ?? '—' }} · {{ $adjustment->created_at?->format('d M Y H:i') ?? '—' }}</dd>
            </div>
            @if ('pending' !== $adjustment->status)
                <div class="tich-dl__row">
                    <dt class="tich-dl__label">{{ 'rejected' === $adjustment->status ? 'Rejected by' : 'Approved by' }}</dt>
                    <dd class="tich-dl__value">{{ $adjustment->approvedBy?->fullName() ?? '—' }} · {{ $adjustment->approved_at?->format('d M Y H:i') ?? '—' }}</dd>
                </div>
            @endif
        </dl>

        <div class="ceo-waiver__reason">
            <p class="tich-dl__label">Reason given</p>
            <p class="ceo-waiver__reason-text">{{ $adjustment->reason ?: 'No reason was recorded.' }}</p>
        </div>

        @if ($canDecide)
            <footer class="ceo-waiver__decision">
                <p class="tich-caption">
                    Approving takes KES {{ number_format((float) $adjustment->amount, 2) }} off this student's balance straight away;
                    rejecting leaves it payable.
                </p>
                <div class="ceo-waiver__actions">
                    <form method="POST" action="{{ route('ceo.waivers.approve', $adjustment) }}">
                        @csrf
                        <button type="submit" class="tich-btn tich-btn-success" onclick="return confirm('Approve this {{ str_replace('_', ' ', (string) $adjustment->adjustment_type) }} and apply it to the student account?')">Approve waiver</button>
                    </form>
                    <form method="POST" action="{{ route('ceo.waivers.reject', $adjustment) }}">
                        @csrf
                        <button type="submit" class="tich-btn tich-btn-danger" onclick="return confirm('Reject this {{ str_replace('_', ' ', (string) $adjustment->adjustment_type) }}?')">Reject waiver</button>
                    </form>
                </div>
            </footer>
        @endif
    </article>
@endsection

@push('styles')
<style>
.ceo-waiver { display: grid; gap: 1.1rem; }
.ceo-waiver__head { display: flex; flex-wrap: wrap; gap: .75rem; align-items: flex-start; justify-content: space-between; }
.ceo-waiver__who p { margin: .15rem 0 0; }
.ceo-waiver__head-side { display: grid; justify-items: end; gap: .35rem; }
.ceo-waiver__amount { margin: 0; font-size: 1.25rem; font-weight: 700; color: var(--tich-text, #0f172a); }
.ceo-waiver__reason { padding: .8rem .9rem; border-radius: 10px; background: color-mix(in srgb, var(--tich-primary, #1565c0) 6%, var(--tich-surface, #fff)); border: 1px solid var(--tich-border, #e2e8f0); }
.ceo-waiver__reason p { margin: 0; }
.ceo-waiver__reason-text { margin-top: .3rem !important; font-size: .9375rem; line-height: 1.5; }
.ceo-waiver__decision { display: flex; flex-wrap: wrap; gap: .85rem; align-items: center; justify-content: space-between; padding-top: .35rem; border-top: 1px solid var(--tich-neutral-border, #e2e8f0); }
.ceo-waiver__decision > p { margin: 0; max-width: 34rem; }
.ceo-waiver__actions { display: flex; flex-wrap: wrap; gap: .6rem; }
.ceo-waiver__actions form { margin: 0; }
@media (max-width: 640px) {
    .ceo-waiver__head-side { justify-items: start; }
    .ceo-waiver__actions { width: 100%; }
    .ceo-waiver__actions form, .ceo-waiver__actions .tich-btn { width: 100%; }
}
</style>
@endpush
