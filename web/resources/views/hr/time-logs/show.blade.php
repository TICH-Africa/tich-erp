@extends('layouts.hr')

@section('title', 'Time log · '.$log->log_code)

@section('hr-content')
    <x-page-toolbar title="STAFF WEEKLY TIME LOG" meta="{{ $log->log_code }} · {{ $log->week_ref }}">
        <x-slot:actions>
            <a href="{{ route('hr.time-logs.index') }}" class="tich-btn tich-btn-ghost">Back to queue</a>
        </x-slot:actions>
    </x-page-toolbar>

    @error('time_log')
        <div class="tich-alert tich-alert--error tich-mt-4">{{ $message }}</div>
    @enderror

    <p class="tich-caption tich-mt-4">Status: <x-status-badge :status="$log->status" /></p>

    @include('partials.weekly-time-log-form', ['log' => $log, 'departments' => $departments, 'editable' => false])

    @if ($log->status === 'pending_hr')
        <div class="tich-card tich-mt-6">
            <h2 class="tich-h3">HR actions</h2>
            <div class="tich-grid tich-grid--3 tich-mt-4" style="gap:1rem; align-items:start;">
                <form method="POST" action="{{ route('hr.time-logs.approve', $log) }}" class="tich-form-stack">
                    @csrf
                    <div class="tich-form-group">
                        <label class="tich-label">Notes (optional)</label>
                        <textarea name="notes" class="tich-input" rows="2" maxlength="2000"></textarea>
                    </div>
                    <button type="submit" class="tich-btn tich-btn-primary">Approve</button>
                </form>
                <form method="POST" action="{{ route('hr.time-logs.return', $log) }}" class="tich-form-stack" onsubmit="return confirm('Return this log to the employee?')">
                    @csrf
                    <div class="tich-form-group">
                        <label class="tich-label">Return reason <span class="tich-text--danger">*</span></label>
                        <textarea name="notes" class="tich-input" rows="2" maxlength="2000" required></textarea>
                    </div>
                    <button type="submit" class="tich-btn tich-btn-secondary">Return for revision</button>
                </form>
                <form method="POST" action="{{ route('hr.time-logs.reject', $log) }}" class="tich-form-stack" onsubmit="return confirm('Reject this time log permanently?')">
                    @csrf
                    <div class="tich-form-group">
                        <label class="tich-label">Reject notes <span class="tich-text--danger">*</span></label>
                        <textarea name="notes" class="tich-input" rows="2" maxlength="2000" required></textarea>
                    </div>
                    <button type="submit" class="tich-btn tich-btn-danger">Reject</button>
                </form>
            </div>
        </div>
    @elseif ($log->hr_notes)
        <div class="tich-card tich-mt-6">
            <h2 class="tich-h3">HR notes</h2>
            <p class="tich-text tich-mt-2">{{ $log->hr_notes }}</p>
            <p class="tich-caption tich-mt-2">{{ $log->hrReviewer?->fullName() }} · {{ $log->hr_reviewed_at?->format('d M Y H:i') }}</p>
        </div>
    @endif
@endsection
