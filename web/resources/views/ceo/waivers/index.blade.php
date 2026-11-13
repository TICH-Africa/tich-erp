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

    @php
        $tabs = [
            'pending' => 'Awaiting a decision',
            'approved' => 'Approved',
            'rejected' => 'Rejected',
            'all' => 'All waivers',
        ];
    @endphp
    <nav class="tich-tabs__nav tich-mt-6" aria-label="Waiver status">
        @foreach ($tabs as $key => $label)
            <a href="{{ route('ceo.waivers.index', ['status' => $key]) }}"
                class="tich-tabs__btn @if ($status === $key) is-active @endif">{{ $label }} ({{ number_format((int) ($counts[$key] ?? 0)) }})</a>
        @endforeach
    </nav>

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
                            <td colspan="7" class="tich-table-empty">
                                {{ 'all' === $status ? 'No waiver, scholarship or bursary has been raised yet.' : 'Nothing '.ucfirst($status).' so far.' }}
                            </td>
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
