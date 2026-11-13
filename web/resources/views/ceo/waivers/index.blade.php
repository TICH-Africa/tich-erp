@extends('layouts.ceo')

@section('title', 'Student waivers')

@section('ceo-content')
    <x-page-toolbar
        title="Student waivers"
        meta="Fee waivers, scholarships and bursaries the executive office decides"
    />

    <div class="tich-grid tich-grid--2 tich-mt-4" style="gap:1rem;">
        <article class="tich-card">
            <p class="tich-caption" style="margin:0;">Awaiting a decision</p>
            <p class="tich-h2" style="margin:.25rem 0 0;">{{ number_format((int) $pending) }}</p>
        </article>
        <article class="tich-card">
            <p class="tich-caption" style="margin:0;">Value awaiting</p>
            <p class="tich-h2" style="margin:.25rem 0 0;">KES {{ number_format((float) $pendingValue, 2) }}</p>
        </article>
    </div>

    <form method="get" class="tich-flex tich-mt-6 tich-mb-4" style="gap:0.5rem; flex-wrap:wrap;">
        <select name="status" class="tich-input" style="width:auto;">
            <option value="pending" @selected('pending' === $status)>Awaiting a decision</option>
            <option value="approved" @selected('approved' === $status)>Approved</option>
            <option value="rejected" @selected('rejected' === $status)>Rejected</option>
            <option value="all" @selected('all' === $status)>All waivers</option>
        </select>
        <button type="submit" class="tich-btn tich-btn-secondary">Filter</button>
    </form>

    <div class="tich-card tich-table-panel">
        <div class="tich-table-wrap">
            <table class="tich-admin-table">
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Type</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Requested by</th>
                        <th>Date</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($adjustments as $adjustment)
                        <tr>
                            <td>
                                <strong>{{ $adjustment->student?->fullName() ?? 'Student' }}</strong>
                                <p class="tich-caption">{{ $adjustment->student?->registration_number ?? '—' }}</p>
                            </td>
                            <td class="tich-caption">{{ ucfirst((string) $adjustment->adjustment_type) }}</td>
                            <td>KES {{ number_format((float) $adjustment->amount, 2) }}</td>
                            <td>
                                <span class="tich-badge tich-badge--{{ match($adjustment->status) {
                                    'pending' => 'warning',
                                    'approved' => 'success',
                                    'rejected' => 'danger',
                                    default => 'secondary',
                                } }}">{{ ucfirst((string) $adjustment->status) }}</span>
                            </td>
                            <td class="tich-caption">{{ $adjustment->requestedBy?->fullName() ?? '—' }}</td>
                            <td class="tich-caption">{{ ($adjustment->approved_at ?? $adjustment->created_at)?->format('d M Y') ?? '—' }}</td>
                            <td>
                                <a href="{{ route('ceo.waivers.show', $adjustment) }}" class="tich-btn tich-btn-primary">
                                    {{ 'pending' === $adjustment->status ? 'Review' : 'Open' }}
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="tich-table-empty">No waivers in this queue.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($adjustments->hasPages())
            <div class="tich-mt-4">{{ $adjustments->links() }}</div>
        @endif
    </div>
@endsection
