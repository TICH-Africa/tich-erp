@extends('layouts.hr')

@section('title', 'Review attendance — ' . $attendance->staff->fullName())

@section('hr-content')
    @php
        $canReview = $attendance->hr_review_status === \App\Models\StaffAttendance::HR_STATUS_PENDING;
    @endphp

    <div class="tich-mb-6 tich-flex" style="gap:0.5rem; flex-wrap:wrap;">
        <a href="{{ route('hr.attendance.index') }}" class="tich-btn tich-btn-ghost">← Attendance reviews</a>
        <a href="{{ route('hr.dashboard') }}#attendance-reviews" class="tich-btn tich-btn-ghost">HR dashboard</a>
    </div>

    <section class="tich-leave-hero tich-mb-8">
        <div>
            <p class="tich-caption">Clock-in record</p>
            <h1 class="tich-leave-hero__title">{{ $attendance->staff->fullName() }}</h1>
            <div class="tich-leave-hero__meta">
                @if ($canReview)
                    <span class="tich-badge tich-badge--warning">Pending HR review</span>
                @elseif ($attendance->isHrApproved())
                    <span class="tich-badge tich-badge--success">Approved</span>
                @elseif ($attendance->isHrRejected())
                    <span class="tich-badge tich-badge--danger">Rejected</span>
                @else
                    <span class="tich-badge tich-badge--info">{{ ucfirst($attendance->hr_review_status ?? 'pending') }}</span>
                @endif
                <span class="tich-caption">{{ $attendance->attendance_date->format('l, d M Y') }}</span>
            </div>
        </div>
    </section>

    <div class="tich-grid tich-grid--2 tich-mb-8" style="align-items:start; gap:1.5rem;">
        <article class="tich-card">
            <h2 class="tich-h3">Employee</h2>
            <div class="tich-kv-grid tich-mt-4">
                <div><span class="tich-kv-grid__label">Name</span><span class="tich-kv-grid__value">{{ $attendance->staff->fullName() }}</span></div>
                <div><span class="tich-kv-grid__label">Employee no.</span><span class="tich-kv-grid__value">{{ $attendance->staff->employee_number }}</span></div>
                <div><span class="tich-kv-grid__label">Department</span><span class="tich-kv-grid__value">{{ $attendance->staff->department?->dept_name ?? '-' }}</span></div>
                <div><span class="tich-kv-grid__label">Job title</span><span class="tich-kv-grid__value">{{ $attendance->staff->job_title ?? '-' }}</span></div>
                <div><span class="tich-kv-grid__label">Clock in</span><span class="tich-kv-grid__value">{{ $attendance->clock_in_time ? substr((string) $attendance->clock_in_time, 0, 5) : '-' }}</span></div>
                <div><span class="tich-kv-grid__label">Clock out</span><span class="tich-kv-grid__value">{{ $attendance->clock_out_time ? substr((string) $attendance->clock_out_time, 0, 5) : '-' }}</span></div>
                <div><span class="tich-kv-grid__label">Hours worked</span><span class="tich-kv-grid__value">{{ $attendance->work_hours ? number_format((float) $attendance->work_hours, 2) : '-' }}</span></div>
                <div><span class="tich-kv-grid__label">Off-campus</span><span class="tich-kv-grid__value">{{ $attendance->is_off_campus ? 'Yes' : 'No' }}</span></div>
            </div>
            <a href="{{ route('hr.staff.show', $attendance->staff) }}" class="tich-btn tich-btn-ghost tich-mt-4">View staff record</a>
        </article>

        <article class="tich-card">
            <h2 class="tich-h3">Location</h2>
            <div class="tich-kv-grid tich-mt-4">
                <div><span class="tich-kv-grid__label">Verification</span><span class="tich-kv-grid__value">{{ app(\App\Services\StaffClockInLocationService::class)->statusLabel($attendance->location_verification_status) }}</span></div>
                <div><span class="tich-kv-grid__label">Lat / Long</span><span class="tich-kv-grid__value">{{ $attendance->location_lat_long ?? '-' }}</span></div>
                <div><span class="tich-kv-grid__label">GPS accuracy</span><span class="tich-kv-grid__value">{{ $attendance->clock_in_accuracy_m ? number_format((float) $attendance->clock_in_accuracy_m, 0) . ' m' : '-' }}</span></div>
            </div>
            @if ($attendance->clockInMapsUrl())
                <a href="{{ $attendance->clockInMapsUrl() }}" class="tich-btn tich-btn-ghost tich-mt-4" target="_blank" rel="noopener">View on map</a>
            @endif
        </article>
    </div>

    @if ($attendance->hr_rejection_reason)
        <article class="tich-card tich-mb-8" style="border-left:4px solid #dc2626;">
            <h2 class="tich-h3">Rejection reason</h2>
            <p class="tich-text tich-mt-4">{{ $attendance->hr_rejection_reason }}</p>
        </article>
    @endif

    @if ($attendance->hr_review_notes)
        <article class="tich-card tich-mb-8">
            <h2 class="tich-h3">HR notes</h2>
            <p class="tich-text tich-mt-4">{{ $attendance->hr_review_notes }}</p>
        </article>
    @endif

    @if ($canReview)
        @if ($errors->has('form'))
            <div class="tich-alert tich-alert--danger tich-mb-4">{{ $errors->first('form') }}</div>
        @endif

        <div class="tich-grid tich-grid--2" style="gap:1.5rem; align-items:start;">
            <article class="tich-card">
                <h2 class="tich-h3">Approve clock-in</h2>
                <form method="POST" action="{{ route('hr.attendance.approve', $attendance) }}" class="tich-mt-4">
                    @csrf
                    <label for="approve_hr_notes" class="tich-label">HR notes (optional)</label>
                    <textarea id="approve_hr_notes" name="hr_notes" rows="3" class="tich-input tich-mt-2"></textarea>
                    <button type="submit" class="tich-btn tich-btn-primary tich-mt-4">Approve</button>
                </form>
            </article>

            <article class="tich-card">
                <h2 class="tich-h3">Reject clock-in</h2>
                <form method="POST" action="{{ route('hr.attendance.reject', $attendance) }}" class="tich-mt-4">
                    @csrf
                    <label for="rejection_reason" class="tich-label">Reason for rejection *</label>
                    <textarea id="rejection_reason" name="rejection_reason" rows="3" class="tich-input tich-mt-2 @error('rejection_reason') tich-input--error @enderror" required></textarea>
                    @error('rejection_reason')<p class="tich-form-error">{{ $message }}</p>@enderror
                    <label for="reject_hr_notes" class="tich-label tich-mt-4">HR notes (optional)</label>
                    <textarea id="reject_hr_notes" name="hr_notes" rows="2" class="tich-input tich-mt-2"></textarea>
                    <button type="submit" class="tich-btn tich-btn-ghost tich-mt-4" style="color:#b91c1c; border-color:#fecaca;">Reject clock-in</button>
                </form>
            </article>
        </div>
    @else
        <article class="tich-card">
            <h2 class="tich-h3">Review status</h2>
            <div class="tich-kv-grid tich-mt-4">
                <div><span class="tich-kv-grid__label">Reviewed by</span><span class="tich-kv-grid__value">{{ $attendance->hrReviewedBy?->fullName() ?? '-' }}</span></div>
                <div><span class="tich-kv-grid__label">Reviewed at</span><span class="tich-kv-grid__value">{{ $attendance->hr_reviewed_at?->format('d M Y H:i') ?? '-' }}</span></div>
            </div>
        </article>
    @endif
@endsection
