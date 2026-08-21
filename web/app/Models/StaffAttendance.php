<?php

namespace App\Models;

use App\Services\StaffClockInLocationService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StaffAttendance extends Model
{
    protected $table = 'staff_attendance';

    public $timestamps = false;

    public const HR_STATUS_PENDING = 'pending';

    public const HR_STATUS_APPROVED = 'approved';

    public const HR_STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'staff_id',
        'attendance_date',
        'clock_in_time',
        'clock_out_time',
        'work_hours',
        'is_present',
        'is_leave_day',
        'leave_request_id',
        'is_off_campus',
        'field_project_name',
        'location_lat_long',
        'clock_in_latitude',
        'clock_in_longitude',
        'clock_in_accuracy_m',
        'location_verification_status',
        'hr_review_status',
        'hr_reviewed_by_staff_id',
        'hr_reviewed_at',
        'hr_review_notes',
        'hr_rejection_reason',
        'notes',
        'recorded_by',
    ];

    protected $casts = [
        'attendance_date' => 'date',
        'work_hours' => 'decimal:2',
        'is_present' => 'boolean',
        'is_leave_day' => 'boolean',
        'clock_in_accuracy_m' => 'decimal:2',
        'clock_in_latitude' => 'decimal:7',
        'clock_in_longitude' => 'decimal:7',
        'is_off_campus' => 'boolean',
        'hr_reviewed_at' => 'datetime',
    ];

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    public function leaveRequest(): BelongsTo
    {
        return $this->belongsTo(LeaveRequest::class);
    }

    public function recordedByStaff(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'recorded_by');
    }

    public function hrReviewedBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'hr_reviewed_by_staff_id');
    }

    public function isPendingHrReview(): bool
    {
        return $this->hr_review_status === self::HR_STATUS_PENDING
            && $this->clock_in_time
            && ! $this->clock_out_time;
    }

    public function isHrApproved(): bool
    {
        return $this->hr_review_status === self::HR_STATUS_APPROVED;
    }

    public function isHrRejected(): bool
    {
        return $this->hr_review_status === self::HR_STATUS_REJECTED;
    }

    public function hrReviewBadge(): string
    {
        return match ($this->hr_review_status) {
            self::HR_STATUS_APPROVED => '<span class="tich-badge tich-badge--success">HR approved</span>',
            self::HR_STATUS_REJECTED => '<span class="tich-badge tich-badge--danger">HR rejected</span>',
            default => '<span class="tich-badge tich-badge--warning">Pending HR review</span>',
        };
    }

    public function clockInLocationLabel(): string
    {
        if ($this->needsClockInLocationVerification()) {
            return 'Not verified - clock in again with GPS';
        }

        return app(StaffClockInLocationService::class)->statusLabel($this->location_verification_status);
    }

    public function hasVerifiedClockInLocation(): bool
    {
        return in_array($this->location_verification_status, ['on_campus', 'off_campus', 'outside_geofence'], true)
            && $this->clock_in_latitude !== null
            && $this->clock_in_longitude !== null;
    }

    public function needsClockInLocationVerification(): bool
    {
        if (! $this->clock_in_time || $this->clock_out_time) {
            return false;
        }

        if (! config('hr-attendance.require_location', true)) {
            return false;
        }

        return ! $this->hasVerifiedClockInLocation();
    }

    public function clockInMapsUrl(): ?string
    {
        return app(StaffClockInLocationService::class)->mapsUrl(
            $this->clock_in_latitude !== null ? (float) $this->clock_in_latitude : null,
            $this->clock_in_longitude !== null ? (float) $this->clock_in_longitude : null,
        );
    }
}
