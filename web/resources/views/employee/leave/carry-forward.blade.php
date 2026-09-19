@extends('layouts.employee')

@section('employee-content')
    <x-page-toolbar title="Leave Carry-Forward" meta="Request to carry forward unused annual leave days to the next year" />

    <div class="uf-form">
        <form method="POST" action="{{ route('employee.leave.carry-forward.store') }}" data-uf="ready">
            @csrf

            <div class="uf-amount-bar">
                <div>
                    <div class="uf-amount-bar__ref">LVE · Carry-forward</div>
                    <div class="uf-amount-bar__sum">Request unused days</div>
                </div>
                <span class="uf-badge">Draft</span>
            </div>

            <div class="uf-form-section">
                <div class="uf-section-head">Request Carry-Forward</div>
                <div class="uf-section-body">
                    <p class="uf-hint">
                        Per the HR Manual, you may request to carry forward up to 10 days of unused annual leave to the next calendar year.
                        This requires written approval from HR.
                    </p>
                    <div class="uf-field">
                        <label for="days_requested">Days to carry forward (max 10) <span class="uf-req">*</span></label>
                        <input type="number" id="days_requested" name="days_requested" step="0.5" min="0.5" max="10"
                            class="{{ $errors->has('days_requested') ? 'is-invalid' : '' }}"
                            value="{{ old('days_requested') }}" required>
                        @error('days_requested')
                            <span class="uf-error">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="uf-field">
                        <label for="reason">Reason for carry-forward request <span class="uf-req">*</span></label>
                        <textarea id="reason" name="reason" rows="3" required
                            class="{{ $errors->has('reason') ? 'is-invalid' : '' }}"
                            placeholder="Explain the exceptional circumstances for this request">{{ old('reason') }}</textarea>
                        @error('reason')
                            <span class="uf-error">{{ $message }}</span>
                        @enderror
                    </div>

                    @error('carry_forward')
                        <div class="tich-alert tich-alert--warning">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="uf-form-section">
                <div class="uf-section-head">Submit</div>
                <div class="uf-section-body">
                    <div class="uf-form-actions">
                        <button type="submit" class="uf-btn uf-btn-primary">Submit request to HR</button>
                    </div>
                </div>
            </div>

            <p class="uf-form-footnote"><span class="uf-req">*</span> Required field</p>
        </form>
    </div>

    <div class="tich-card tich-mt-6">
        <h2 class="tich-h3 tich-mb-4">Your Carry-Forward History</h2>

        <div class="tich-table-wrap">
            <table class="tich-admin-table">
                <thead>
                    <tr>
                        <th>Year</th>
                        <th>Days requested</th>
                        <th>Days approved</th>
                        <th>Status</th>
                        <th>Submitted</th>
                        <th>Review notes</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($carryForwardRequests as $cfr)
                        <tr>
                            <td>{{ $cfr->from_year }} &rarr; {{ $cfr->to_year }}</td>
                            <td>{{ number_format($cfr->days_requested, 1) }}</td>
                            <td>{{ $cfr->days_approved !== null ? number_format($cfr->days_approved, 1) : '-' }}</td>
                            <td>
                                <span class="tich-badge tich-badge--{{ match($cfr->status) {
                                    'approved' => 'success',
                                    'rejected' => 'danger',
                                    default => 'warning',
                                } }}">{{ $cfr->statusLabel() }}</span>
                            </td>
                            <td class="tich-caption">{{ $cfr->created_at?->format('d M Y') }}</td>
                            <td class="tich-caption">{{ $cfr->review_notes ?? '-' }}</td>
                        </tr>
                    @empty
                        @include('partials.states.table-empty', ['colspan' => 6, 'title' => 'No carry-forward requests yet', 'icon' => 'inbox'])
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
