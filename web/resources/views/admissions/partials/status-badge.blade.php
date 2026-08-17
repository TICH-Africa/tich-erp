@php
    $statusClass = match ($applicant->status) {
        'admitted' => 'is-success',
        'rejected' => 'is-danger',
        'academic_review' => 'is-info',
        'fee_pending' => 'is-warning',
        'paid' => 'is-info',
        default => 'is-pending',
    };
@endphp
<span class="tich-status-badge {{ $statusClass }}">
    {{ ucfirst(str_replace('_', ' ', $applicant->status)) }}
    @if ($applicant->academic_review_status && $applicant->academic_review_status !== 'pending')
        · {{ ucfirst(str_replace('_', ' ', $applicant->academic_review_status)) }}
    @endif
</span>
