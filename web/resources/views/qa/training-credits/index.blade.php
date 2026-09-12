@extends('layouts.qa')

@section('title', 'Training credits')

@section('qa-content')
    <x-page-toolbar title="Training credits" meta="Staff professional development credits and capacity records" />

    <div class="tich-card tich-table-panel tich-mt-8">
        <form method="GET" class="tich-form-stack tich-mb-8" style="display:flex;flex-wrap:wrap;gap:0.75rem;align-items:end;">
            <div class="tich-form-group" style="min-width:180px;">
                <label class="tich-label" for="credit_type">Credit type</label>
                <select id="credit_type" name="credit_type" class="tich-input">
                    <option value="">All types</option>
                    <option value="CPD" @selected(request('credit_type') === 'CPD')>CPD</option>
                    <option value="Mandatory" @selected(request('credit_type') === 'Mandatory')>Mandatory</option>
                    <option value="Certification" @selected(request('credit_type') === 'Certification')>Certification</option>
                </select>
            </div>
            <div>
                <button type="submit" class="tich-btn tich-btn-primary">Filter</button>
                <a href="{{ route('qa.training-credits.index') }}" class="tich-btn tich-btn-ghost">Clear</a>
            </div>
        </form>

        <div class="tich-flex tich-flex--between tich-mb-4" style="flex-wrap:wrap;gap:0.5rem;">
            <h2 class="tich-h3">Credit ledger</h2>
            <div>
                <a href="{{ route('qa.training-credits.index') }}?export=1" class="tich-btn tich-btn-secondary tich-btn--sm">Export CSV</a>
            </div>
        </div>

        <table class="tich-admin-table">
            <thead>
                <tr>
                    <th>Staff</th>
                    <th>Credit Type</th>
                    <th>Value</th>
                    <th>Awarded</th>
                    <th>Expiry</th>
                    <th>Event</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($credits as $credit)
                    <tr>
                        <td>
                            <a href="{{ route('qa.training-credits.index') }}" class="tich-link">{{ $credit->staff?->first_name.' '.$credit->staff?->surname ?? '-' }}</a>
                        </td>
                        <td>
                            <span class="tich-badge">{{ $credit->credit_type }}</span>
                        </td>
                        <td>{{ $credit->credit_value }}</td>
                        <td>{{ $credit->awarded_at?->format('d M Y') }}</td>
                        <td>
                            @if ($credit->isExpired())
                                <span class="tich-badge tich-badge--critical">{{ $credit->expires_at?->format('d M Y') }}</span>
                            @elseif ($credit->isExpiringSoon())
                                <span class="tich-badge tich-badge--warning">{{ $credit->expires_at?->format('d M Y') }}</span>
                            @else
                                {{ $credit->expires_at?->format('d M Y') ?? '-' }}
                            @endif
                        </td>
                        <td>{{ $credit->event?->title ?? '-' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6">@include('partials.states.table-empty', ['colspan' => 6, 'title' => 'No training credits recorded', 'icon' => 'clipboard-check'])</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        @if ($credits->hasPages())
            <div class="tich-mt-4">{{ $credits->links() }}</div>
        @endif
    </div>

    <div class="tich-card tich-mt-8">
        <h2 class="tich-h3">Capacity building sessions</h2>
        <table class="tich-admin-table tich-mt-4">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Scheduled</th>
                    <th>Audience</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($sessions as $session)
                    <tr>
                        <td>
                            <strong>{{ $session->title }}</strong>
                            @if ($session->description)
                                <p class="tich-caption">{{ \Illuminate\Support\Str::limit($session->description, 100) }}</p>
                            @endif
                        </td>
                        <td>{{ $session->scheduled_at?->format('d M Y H:i') ?? '-' }}</td>
                        <td>{{ $session->audience ?: '-' }}</td>
                        <td><span class="tich-badge">{{ $session->status }}</span></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4">@include('partials.states.table-empty', ['colspan' => 4, 'title' => 'No sessions', 'icon' => 'users'])</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        @if ($sessions->hasPages())
            <div class="tich-mt-4">{{ $sessions->links() }}</div>
        @endif
    </div>
@endsection
