@extends('layouts.employee')

@section('employee-content')
    <x-page-toolbar title="Leave Carry-Forward" meta="Request to carry forward unused annual leave days to the next year" />

    <div class="tich-card tich-mb-6">
        <h2 class="tich-h3">Request Carry-Forward</h2>
        <p class="tich-text tich-mb-4">
            Per the HR Manual, you may request to carry forward up to 10 days of unused annual leave to the next calendar year.
            This requires written approval from HR.
        </p>

        <form method="POST" action="{{ route('employee.leave.carry-forward.store') }}">
            @csrf
            <div class="tich-form-grid" style="max-width: 32rem;">
                <div class="tich-form-group">
                    <label class="tich-label" for="days_requested">Days to carry forward (max 10)</label>
                    <input type="number" id="days_requested" name="days_requested" step="0.5" min="0.5" max="10"
                        class="tich-input" value="{{ old('days_requested') }}" required>
                    @error('days_requested')
                        <p class="tich-form-error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="tich-form-group">
                    <label class="tich-label" for="reason">Reason for carry-forward request</label>
                    <textarea id="reason" name="reason" class="tich-input" rows="3" required
                        placeholder="Explain the exceptional circumstances for this request">{{ old('reason') }}</textarea>
                    @error('reason')
                        <p class="tich-form-error">{{ $message }}</p>
                    @enderror
                </div>

                @error('carry_forward')
                    <div class="tich-notice tich-notice--warning">{{ $message }}</div>
                @enderror

                <div class="tich-form-group">
                    <button type="submit" class="tich-btn tich-btn-primary">Submit request to HR</button>
                </div>
            </div>
        </form>
    </div>

    <div class="tich-card">
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
